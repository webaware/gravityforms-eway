<?php

/**
* custom exception types
*/
class GFEwayException extends Exception {}
class GFEwayCurlException extends Exception {}

/**
* class for managing the plugin
*/
class GFEwayPlugin {
	public $urlBase;                                    // string: base URL path to files in plugin
	public $options;                                    // array of plugin options

	protected $acceptedCards;                           // hash map of accepted credit cards
	protected $txResult = null;                         // results from credit card payment transaction
	protected $formHasCcField = false;                  // true if current form has credit card field

	// minimum versions required
	const MIN_VERSION_GF	= '1.7';

	/**
	* static method for getting the instance of this singleton object
	* @return self
	*/
	public static function getInstance() {
		static $instance = NULL;

		if (is_null($instance)) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	* initialise plugin
	*/
	private function __construct() {
		// grab options, setting new defaults for any that are missing
		$this->initOptions();

		// record plugin URL base
		$this->urlBase = plugin_dir_url(GFEWAY_PLUGIN_FILE);

		// filter the cards array to just Visa, MasterCard and Amex
		$this->acceptedCards = array('amex' => 1, 'mastercard' => 1, 'visa' => 1);

		add_action('init', array($this, 'init'));
	}

	/**
	* initialise plug-in options, handling undefined options by setting defaults
	*/
	protected function initOptions() {
		$defaults = array (
			'customerID'			=> '87654321',
			'useStored'				=> false,
			'useTest'				=> true,
			'useBeagle'				=> false,
			'roundTestAmounts'		=> true,
			'forceTestAccount'		=> true,
			'sslVerifyPeer'			=> true,
		);

		$this->options = get_option(GFEWAY_PLUGIN_OPTIONS);
		if (!is_array($this->options)) {
			$this->options = array();
		}

		if (count(array_diff_assoc($defaults, $this->options)) > 0) {
			$this->options = array_merge($defaults, $this->options);
			update_option(GFEWAY_PLUGIN_OPTIONS, $this->options);
		}
	}

	/**
	* handle the plugin's init action
	*/
	public function init() {
		// do nothing if Gravity Forms isn't enabled or doesn't meet required minimum version
		if (self::versionCompareGF(self::MIN_VERSION_GF, '>=')) {
			// hook into Gravity Forms to enable credit cards and trap form submissions
			add_filter('gform_logging_supported', array($this, 'enableLogging'));
			add_filter('gform_pre_render', array($this, 'gformPreRenderSniff'));
			add_filter('gform_admin_pre_render', array($this, 'gformPreRenderSniff'));
			add_action('gform_enable_credit_card_field', '__return_true');
			add_filter('gform_creditcard_types', array($this, 'gformCCTypes'));
			add_filter('gform_currency', array($this, 'gformCurrency'));
			add_filter('gform_validation', array($this, 'gformValidation'));
			add_action('gform_after_submission', array($this, 'gformAfterSubmission'), 10, 2);
			add_action('gform_entry_post_save', array($this, 'gformEntryPostSave'), 10, 2);
			add_filter('gform_custom_merge_tags', array($this, 'gformCustomMergeTags'), 10, 4);
			add_filter('gform_replace_merge_tags', array($this, 'gformReplaceMergeTags'), 10, 7);

			// hook into Gravity Forms to handle Recurring Payments custom field
			new GFEwayRecurringField($this);
		}

		if (is_admin()) {
			// kick off the admin handling
			new GFEwayAdmin($this);
		}
	}

	/**
	* check current form for information
	* @param array $form
	* @return array
	*/
	public function gformPreRenderSniff($form) {
		// test whether form has a credit card field
		$this->formHasCcField = self::isEwayForm($form['id'], $form['fields']);

		return $form;
	}

	/**
	* process a form validation filter hook; if last page and has credit card field and total, attempt to bill it
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @return array
	*/
	public function gformValidation($data) {

		// make sure all other validations passed
		if ($data['is_valid'] && self::isEwayForm($data['form']['id'], $data['form']['fields'])) {
			$formData = new GFEwayFormData($data['form']);

			// make sure form hasn't already been submitted / processed
			if ($this->hasFormBeenProcessed($data['form'])) {
				$data['is_valid'] = false;
				$formData->ccField['failed_validation'] = true;
				$formData->ccField['validation_message'] = $this->getErrMsg(GFEWAY_ERROR_ALREADY_SUBMITTED);
			}

			// make that this is the last page of the form and that we have a credit card field and something to bill
			// and that credit card field is not hidden (which indicates that payment is being made another way)
			else if (!$formData->isCcHidden() && $formData->isLastPage() && $formData->ccField !== false) {
				if (!$formData->hasPurchaseFields()) {
					$data['is_valid'] = false;
					$formData->ccField['failed_validation'] = true;
					$formData->ccField['validation_message'] = $this->getErrMsg(GFEWAY_ERROR_NO_AMOUNT);
				}
				else {
					// only check credit card details if we've got something to bill
					if ($formData->total > 0 || $formData->hasRecurringPayments()) {
						// check for required fields
						$required = array(
							'ccName' => $this->getErrMsg(GFEWAY_ERROR_REQ_CARD_HOLDER),
							'ccNumber' => $this->getErrMsg(GFEWAY_ERROR_REQ_CARD_NAME),
						);
						foreach ($required as $name => $message) {
							if (empty($formData->$name)) {
								$data['is_valid'] = false;
								$formData->ccField['failed_validation'] = true;
								if (!empty($formData->ccField['validation_message'])) {
									$formData->ccField['validation_message'] .= '<br />';
								}
								$formData->ccField['validation_message'] .= $message;
							}
						}

						// if no errors, try to bill it
						if ($data['is_valid']) {
							if ($formData->hasRecurringPayments()) {
								$data = $this->processRecurringPayment($data, $formData);
							}
							else {
								$data = $this->processSinglePayment($data, $formData);
							}
						}
					}
				}
			}

			// if errors, send back to credit card page
			if (!$data['is_valid']) {
				GFFormDisplay::set_current_page($data['form']['id'], $formData->ccField['pageNumber']);
			}
		}

		return $data;
	}

	/**
	* check whether this form entry's unique ID has already been used; if so, we've already done a payment attempt.
	* @param array $form
	* @return boolean
	*/
	protected function hasFormBeenProcessed($form) {
		global $wpdb;

		$unique_id = RGFormsModel::get_form_unique_id($form['id']);

		$sql = "select lead_id from {$wpdb->prefix}rg_lead_meta where meta_key='gfeway_unique_id' and meta_value = %s";
		$lead_id = $wpdb->get_var($wpdb->prepare($sql, $unique_id));

		return !empty($lead_id);
	}

	/**
	* get customer ID to use with payment gateway
	* @return string
	*/
	protected function getCustomerID() {
		if ($this->options['useTest'] && $this->options['forceTestAccount']) {
			return '87654321';
		}

		return $this->options['customerID'];
	}

	/**
	* process regular one-off payment
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @param GFEwayFormData $formData pre-parsed data from $data
	* @return array
	*/
	protected function processSinglePayment($data, $formData) {
		try {
			if ($this->options['useStored']) {
				$eway = new GFEwayStoredPayment($this->getCustomerID(), !$this->options['useTest']);
			}
			else {
				$eway = new GFEwayPayment($this->getCustomerID(), !$this->options['useTest']);
			}

			$eway->sslVerifyPeer = $this->options['sslVerifyPeer'];
			$eway->invoiceDescription = get_bloginfo('name') . " -- {$data['form']['title']}";
			$eway->invoiceReference = $data['form']['id'];
			if (empty($formData->firstName) && empty($formData->lastName)) {
				$eway->lastName = $formData->ccName;                // pick up card holder's name for last name
			}
			else {
				$eway->firstName = $formData->firstName;
				$eway->lastName  = $formData->lastName;
			}
			$eway->cardHoldersName			= $formData->ccName;
			$eway->cardNumber				= $formData->ccNumber;
			$eway->cardExpiryMonth			= $formData->ccExpMonth;
			$eway->cardExpiryYear			= $formData->ccExpYear;
			$eway->emailAddress				= $formData->email;
			$eway->address					= $formData->address;
			$eway->postcode					= $formData->postcode;
			$eway->cardVerificationNumber	= $formData->ccCVN;

			// if Beagle is enabled, get the country code
			if ($this->options['useBeagle']) {
				$eway->customerCountryCode = GFCommon::get_country_code($formData->address_country);
			}

			// allow plugins/themes to modify invoice description and reference, and set option fields
			$eway->invoiceDescription	= apply_filters('gfeway_invoice_desc', $eway->invoiceDescription, $data['form']);
			$eway->invoiceReference		= apply_filters('gfeway_invoice_ref', $eway->invoiceReference, $data['form']);
			$eway->transactionNumber	= apply_filters('gfeway_invoice_trans_number', $eway->transactionNumber, $data['form']);
			$eway->option1				= apply_filters('gfeway_invoice_option1', '', $data['form']);
			$eway->option2				= apply_filters('gfeway_invoice_option2', '', $data['form']);
			$eway->option3				= apply_filters('gfeway_invoice_option3', '', $data['form']);

			// if live, pass through amount exactly, but if using test site, round up to whole dollars or eWAY will fail
			if ($this->options['useTest'] && $this->options['roundTestAmounts']) {
				$eway->amount = ceil($formData->total);
				if ($eway->amount != $formData->total) {
					self::log_debug(sprintf('%s: amount rounded up from %s to %s to pass sandbox gateway',
						__FUNCTION__, number_format($formData->total, 2), number_format($eway->amount, 2)));
				}
			}
			else {
				$eway->amount = $formData->total;
			}

			self::log_debug(sprintf('%s: %s gateway, invoice ref: %s, transaction: %s, amount: %s, cc: %s',
				__FUNCTION__, $eway->isLiveSite ? 'live' : 'test', $eway->invoiceReference, $eway->transactionNumber,
				$eway->amount, $eway->cardNumber));

			// record basic transaction data, for updating the entry with later
			$this->txResult = array (
				'payment_gateway'		=> 'gfeway',
				'gfeway_unique_id'		=> GFFormsModel::get_form_unique_id($data['form']['id']),	// reduces risk of double-submission
			);

			$response = $eway->processPayment();
			if ($response->status) {
				// transaction was successful, so record details and continue
				$this->txResult['payment_status']	= $this->options['useStored'] ? 'Pending' : 'Approved';
				$this->txResult['payment_date']		= date('Y-m-d H:i:s');
				$this->txResult['payment_amount']	= $response->amount;
				$this->txResult['transaction_id']	= $response->transactionNumber;
				$this->txResult['transaction_type']	= 1;
				$this->txResult['authcode']			= $response->authCode;
				$this->txResult['beagle_score']		= $response->beagleScore;

				self::log_debug(sprintf('%s: success, date = %s, id = %s, status = %s, amount = %s, authcode = %s, Beagle = %s',
					__FUNCTION__, $this->txResult['payment_date'], $response->transactionNumber, $this->txResult['payment_status'],
					$response->amount, $response->authCode, $response->beagleScore));
			}
			else {
				$data['is_valid'] = false;
				$formData->ccField['failed_validation'] = true;
				$formData->ccField['validation_message'] = nl2br($this->getErrMsg(GFEWAY_ERROR_EWAY_FAIL) . ":\n{$response->error}");
				$this->txResult['payment_status'] = 'Failed';

				self::log_debug(sprintf('%s: failed; %s', __FUNCTION__, $response->error));
			}
		}
		catch (GFEwayException $e) {
			$data['is_valid'] = false;
			$this->txResult['payment_status'] = 'Failed';
			$formData->ccField['failed_validation'] = true;
			$formData->ccField['validation_message'] = nl2br($this->getErrMsg(GFEWAY_ERROR_EWAY_FAIL) . ":\n{$e->getMessage()}");

			self::log_error(__METHOD__ . ": " . $e->getMessage());
		}

		return $data;
	}

	/**
	* process recurring payments
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @param GFEwayFormData $formData pre-parsed data from $data
	* @return array
	*/
	protected function processRecurringPayment($data, $formData) {
		try {
			$eway = new GFEwayRecurringPayment($this->getCustomerID(), !$this->options['useTest']);
			$eway->sslVerifyPeer = $this->options['sslVerifyPeer'];
			if (empty($formData->firstName) && empty($formData->lastName)) {
				$eway->firstName = '-';                             // no first name,
				$eway->lastName = $formData->ccName;                // pick up card holder's name for last name
			}
			else {
				$eway->title = $formData->namePrefix;
				$eway->firstName = $formData->firstName;
				$eway->lastName  = $formData->lastName;
			}
			$eway->emailAddress			= $formData->email;
			$eway->address				= $formData->address_street;
			$eway->suburb				= $formData->address_suburb;
			$eway->state				= $formData->address_state;
			$eway->postcode				= $formData->postcode;
			$eway->country				= $formData->address_country;
			$eway->phone				= $formData->phone;
			$eway->customerReference	= $data['form']['id'];
			$eway->invoiceReference		= $data['form']['id'];
			$eway->invoiceDescription	= get_bloginfo('name') . " -- {$data['form']['title']}";
			$eway->cardHoldersName		= $formData->ccName;
			$eway->cardNumber			= $formData->ccNumber;
			$eway->cardExpiryMonth		= $formData->ccExpMonth;
			$eway->cardExpiryYear		= $formData->ccExpYear;
			$eway->amountInit			= $formData->recurring['amountInit'];
			$eway->dateInit				= $formData->recurring['dateInit'];
			$eway->amountRecur			= $formData->recurring['amountRecur'];
			$eway->dateStart			= $formData->recurring['dateStart'];
			$eway->dateEnd				= $formData->recurring['dateEnd'];
			$eway->intervalSize			= $formData->recurring['intervalSize'];
			$eway->intervalType			= $formData->recurring['intervalType'];

			// allow plugins/themes to modify invoice description and reference, and set option fields
			$eway->invoiceDescription	= apply_filters('gfeway_invoice_desc', $eway->invoiceDescription, $data['form']);
			$eway->customerReference	= apply_filters('gfeway_invoice_ref', $eway->customerReference, $data['form']);
			$eway->invoiceReference		= apply_filters('gfeway_invoice_trans_number', $eway->invoiceReference, $data['form']);
			$eway->customerComments		= apply_filters('gfeway_invoice_cust_comments', '', $data['form']);

			self::log_debug(sprintf('%s: %s gateway, invoice ref: %s, customer ref: %s, init amount: %s, recurring amount: %s, date start: %s, date end: %s, interval size: %s, interval type: %s, cc: %s',
				__FUNCTION__, $eway->isLiveSite ? 'live' : 'test', $eway->invoiceReference, $eway->customerReference,
				$eway->amountInit, $eway->amountRecur, $eway->dateStart->format('Y-m-d'), $eway->dateEnd->format('Y-m-d'),
				$eway->intervalSize, $eway->intervalType, $eway->cardNumber));

			// record basic transaction data, for updating the entry with later
			$this->txResult = array (
				'payment_gateway'		=> 'gfeway',
				'gfeway_unique_id'		=> GFFormsModel::get_form_unique_id($data['form']['id']),	// reduces risk of double-submission
			);

			$response = $eway->processPayment();
			if ($response->status) {
				// transaction was successful, so record transaction number and continue
				$this->txResult['payment_status']	= 'Approved';
				$this->txResult['payment_date']		= date('Y-m-d H:i:s');
				$this->txResult['transaction_type']	= 1;

				self::log_debug(sprintf('%s: success, date = %s, status = %s',
					__FUNCTION__, $this->txResult['payment_date'], $this->txResult['payment_status']));
			}
			else {
				$data['is_valid'] = false;
				$formData->ccField['failed_validation'] = true;
				$formData->ccField['validation_message'] = nl2br($this->getErrMsg(GFEWAY_ERROR_EWAY_FAIL) . ":\n{$response->error}");
				$this->txResult['payment_status'] = 'Failed';

				self::log_debug(sprintf('%s: failed; %s', __FUNCTION__, $response->error));
			}
		}
		catch (GFEwayException $e) {
			$data['is_valid'] = false;
			$this->txResult['payment_status'] = 'Failed';
			$formData->ccField['failed_validation'] = true;
			$formData->ccField['validation_message'] = nl2br($this->getErrMsg(GFEWAY_ERROR_EWAY_FAIL) . ":\n{$e->getMessage()}");

			self::log_error(__METHOD__ . ": " . $e->getMessage());
		}

		return $data;
	}

	/**
	* save the transaction details to the entry after it has been created
	* @param array $entry
	* @param array $form
	*/
	public function gformAfterSubmission($entry, $form) {
		if (!self::isEwayForm($form['id'], $form['fields'])) {
			return;
		}

		if (!empty($this->txResult)) {
			// record lead ID for post-processing, so we can update the lead
			$this->txResult['lead_id'] = $entry['id'];
		}
	}

	/**
	* form entry post-submission processing
	* @param array $entry
	* @param array $form
	* @return array
	*/
	public function gformEntryPostSave($entry, $form) {
		if (!empty($this->txResult['payment_status'])) {

			foreach ($this->txResult as $key => $value) {
				switch ($key) {
					case 'payment_status':
					case 'payment_date':
					case 'payment_amount':
					case 'transaction_id':
					case 'transaction_type':
						// update entry
						$entry[$key] = $value;
						break;

					default:
						// update entry meta
						gform_update_meta($entry['id'], $key, $value);
						break;
				}
			}

			// update the entry
			if (class_exists('GFAPI')) {
				GFAPI::update_entry($entry);
			}
			else {
				GFFormsModel::update_lead($entry);
			}

		}

		return $entry;
	}

	/**
	* add custom merge tags
	* @param array $merge_tags
	* @param int $form_id
	* @param array $fields
	* @param int $element_id
	* @return array
	*/
	public function gformCustomMergeTags($merge_tags, $form_id, $fields, $element_id) {
		if ($fields && self::isEwayForm($form_id, $fields)) {
			$merge_tags[] = array('label' => 'Transaction ID', 'tag' => '{transaction_id}');
			$merge_tags[] = array('label' => 'Auth Code', 'tag' => '{authcode}');
			$merge_tags[] = array('label' => 'Payment Amount', 'tag' => '{payment_amount}');
			$merge_tags[] = array('label' => 'Payment Status', 'tag' => '{payment_status}');
			$merge_tags[] = array('label' => 'Beagle Score', 'tag' => '{beagle_score}');
		}

		return $merge_tags;
	}

	/**
	* replace custom merge tags
	* @param string $text
	* @param array $form
	* @param array $lead
	* @param bool $url_encode
	* @param bool $esc_html
	* @param bool $nl2br
	* @param string $format
	* @return string
	*/
	public function gformReplaceMergeTags($text, $form, $lead, $url_encode, $esc_html, $nl2br, $format) {
		if (self::isEwayForm($form['id'], $form['fields'])) {
			if (is_null($this->txResult)) {
				// lead loaded from database, get values from lead meta
				$transaction_id = isset($lead['transaction_id']) ? $lead['transaction_id'] : '';
				$payment_amount = isset($lead['payment_amount']) ? $lead['payment_amount'] : '';
				$payment_status = isset($lead['payment_status']) ? $lead['payment_status'] : '';
				$authcode = (string) gform_get_meta($lead['id'], 'authcode');
				$beagle_score = (string) gform_get_meta($lead['id'], 'beagle_score');
			}
			else {
				// lead not yet saved, get values from transaction results
				$transaction_id = isset($this->txResult['transaction_id']) ? $this->txResult['transaction_id'] : '';
				$payment_amount = isset($this->txResult['payment_amount']) ? $this->txResult['payment_amount'] : '';
				$payment_status = isset($this->txResult['payment_status']) ? $this->txResult['payment_status'] : '';
				$authcode = isset($this->txResult['authcode']) ? $this->txResult['authcode'] : '';
				$beagle_score = isset($this->txResult['beagle_score']) ? $this->txResult['beagle_score'] : '';
			}

			// format payment amount as currency
			$payment_amount = GFCommon::format_number($payment_amount, 'currency');

			$tags = array (
				'{transaction_id}',
				'{payment_amount}',
				'{payment_status}',
				'{authcode}',
				'{beagle_score}',
			);
			$values = array (
				$transaction_id,
				$payment_amount,
				$payment_status,
				$authcode,
				$beagle_score,
			);

			$text = str_replace($tags, $values, $text);
		}

		return $text;
	}

	/**
	* tell Gravity Forms what credit cards we can process
	* @param array $cards
	* @return array
	*/
	public function gformCCTypes($cards) {
		$new_cards = array();
		foreach ($cards as $card) {
			if (isset($this->acceptedCards[$card['slug']])) {
				$new_cards[] = $card;
			}
		}
		return $new_cards;
	}

	/**
	* tell Gravity Forms what currencies we can process
	* @param string $currency
	* @return string
	*/
	public function gformCurrency($currency) {
		// only force currency to AUD if current form has a CC field
		if ($this->formHasCcField) {
			$currency = 'AUD';
		}

		return $currency;
	}

	/**
	* see if form is an eWAY credit card form
	* @param int $form_id
	* @param array $fields
	* @return bool
	*/
	public static function isEwayForm($form_id, $fields) {
		static $mapFormsHaveCC = array();

		// see whether we've already checked
		if (isset($mapFormsHaveCC[$form_id])) {
			return $mapFormsHaveCC[$form_id];
		}

		$isEwayForm = self::hasFieldType($fields, 'creditcard');

		$isEwayForm = apply_filters('gfeway_form_is_eway', $isEwayForm, $form_id);
		$isEwayForm = apply_filters('gfeway_form_is_eway_' . $form_id, $isEwayForm);

		$mapFormsHaveCC[$form_id] = $isEwayForm;

		return $isEwayForm;
	}

	/**
	* check form to see if it has a field of specified type
	* @param array $fields array of fields
	* @param string $type name of field type
	* @return boolean
	*/
	public static function hasFieldType($fields, $type) {
		if (is_array($fields)) {
			foreach ($fields as $field) {
				if (RGFormsModel::get_input_type($field) == $type) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	* get nominated error message, checking for custom error message in WP options
	* @param string $errName the fixed name for the error message (a constant)
	* @param boolean $useDefault whether to return the default, or check for a custom message
	* @return string
	*/
	public function getErrMsg($errName, $useDefault = false) {
		static $messages = array (
			GFEWAY_ERROR_ALREADY_SUBMITTED      => 'Payment already submitted and processed - please close your browser window',
			GFEWAY_ERROR_NO_AMOUNT              => 'This form has credit card fields, but no products or totals',
			GFEWAY_ERROR_REQ_CARD_HOLDER        => 'Card holder name is required for credit card processing',
			GFEWAY_ERROR_REQ_CARD_NAME          => 'Card number is required for credit card processing',
			GFEWAY_ERROR_EWAY_FAIL              => 'Error processing card transaction',
		);

		// default
		$msg = isset($messages[$errName]) ? $messages[$errName] : 'Unknown error';

		// check for custom message
		if (!$useDefault) {
			$msg = get_option($errName, $msg);
		}

		return $msg;
	}

	/**
	* enable Gravity Forms Logging Add-On support for this plugin
	* @param array $plugins
	* @return array
	*/
	public function enableLogging($plugins){
		$plugins['gfeway'] = 'Gravity Forms eWAY';

		return $plugins;
	}

	/**
	* write an error log via the Gravity Forms Logging Add-On
	* @param string $message
	*/
	public static function log_error($message){
		if (class_exists('GFLogging')) {
			GFLogging::include_logger();
			GFLogging::log_message('gfeway', self::sanitiseLog($message), KLogger::ERROR);
		}
	}

	/**
	* write an debug message log via the Gravity Forms Logging Add-On
	* @param string $message
	*/
	public static function log_debug($message){
		if (class_exists('GFLogging')) {
			GFLogging::include_logger();
			GFLogging::log_message('gfeway', self::sanitiseLog($message), KLogger::DEBUG);
		}
	}

	/**
	* sanitise a logging message to obfuscate credit card details before storing in plain text!
	* @param string $message
	* @return string
	*/
	protected static function sanitiseLog($message) {
		// credit card number, a string of at least 12 numeric digits
		$message = preg_replace('#[0-9]{8,}([0-9]{4})#', '************$1', $message);

		return $message;
	}

	/**
	* compare Gravity Forms version against target
	* @param string $target
	* @param string $operator
	* @return bool
	*/
	public static function versionCompareGF($target, $operator) {
		if (class_exists('GFCommon')) {
			return version_compare(GFCommon::$version, $target, $operator);
		}

		return false;
	}

	/**
	* send data via HTTP and return response
	* @param string $url
	* @param string $data
	* @param bool $sslVerifyPeer whether to validate the SSL certificate
	* @return string $response
	* @throws GFEwayCurlException
	*/
	public static function curlSendRequest($url, $data, $sslVerifyPeer = true) {
		// send data via HTTPS and receive response
		$response = wp_remote_post($url, array(
			'user-agent'	=> 'Gravity Forms eWAY ' . GFEWAY_PLUGIN_VERSION,
			'sslverify'		=> $sslVerifyPeer,
			'timeout'		=> 60,
			'headers'		=> array('Content-Type' => 'text/xml; charset=utf-8'),
			'body'			=> $data,
		));

		if (is_wp_error($response)) {
			throw new GFEwayCurlException($response->get_error_message());
		}

		return $response['body'];
	}

	/**
	* get the customer's IP address dynamically from server variables
	* @return string
	*/
	public static function getCustomerIP() {
		// if test mode and running on localhost, then kludge to an Aussie IP address
		$plugin = self::getInstance();
		if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '127.0.0.1' && $plugin->options['useTest']) {
			return '210.1.199.10';
		}

		// check for remote address, ignore all other headers as they can be spoofed easily
		if (isset($_SERVER['REMOTE_ADDR']) && self::isIpAddress($_SERVER['REMOTE_ADDR'])) {
			return $_SERVER['REMOTE_ADDR'];
		}

		return '';
	}

	/**
	* check whether a given string is an IP address
	* @param string $maybeIP
	* @return bool
	*/
	protected static function isIpAddress($maybeIP) {
		if (function_exists('inet_pton')) {
			// check for IPv4 and IPv6 addresses
			return !!inet_pton($maybeIP);
		}

		// just check for IPv4 addresses
		return !!ip2long($maybeIP);
	}

}
