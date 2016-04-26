<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* custom exception types
*/
class GFEwayException extends Exception {}
class GFEwayCurlException extends Exception {}

/**
* class for managing the plugin
*/
class GFEwayPlugin {
	public $options;                                    // array of plugin options

	protected $txResult = null;                         // results from credit card payment transaction
	protected $formHasCcField = false;                  // true if current form has credit card field
	protected $ecryptKey;								// active ecrypt key

	// minimum versions required
	const MIN_VERSION_GF	= '1.9';

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
		spl_autoload_register(array(__CLASS__, 'autoload'));

		add_action('plugins_loaded', array($this, 'load'));
		add_action('init', array($this, 'loadTextDomain'));
	}

	/**
	* handle the plugins_loaded action
	*/
	public function load() {
		// grab options, setting new defaults for any that are missing
		$defaults = array (
			'customerID'			=> '87654321',
			'apiKey'				=> '',
			'apiPassword'			=> '',
			'ecryptKey'				=> '',
			'sandboxCustomerID'		=> '',
			'sandboxApiKey'			=> '',
			'sandboxPassword'		=> '',
			'sandboxEcryptKey'		=> '',
			'useTest'				=> true,
			'useBeagle'				=> false,
			'roundTestAmounts'		=> true,
			'forceTestAccount'		=> true,
			'sslVerifyPeer'			=> true,
		);
		$this->options = wp_parse_args(get_option(GFEWAY_PLUGIN_OPTIONS, array()), $defaults);

		// do nothing if Gravity Forms isn't enabled or doesn't meet required minimum version
		if (self::hasMinimumGF()) {
			add_action('wp_enqueue_scripts', array($this, 'registerScripts'), 20);
			add_action('gform_preview_footer', array($this, 'registerScripts'), 5);

			// hook into Gravity Forms to enable credit cards and trap form submissions
			add_action('gform_enqueue_scripts', array($this, 'gformEnqueueScripts'), 20, 2);
			add_filter('gform_logging_supported', array($this, 'enableLogging'));
			add_filter('gform_pre_render', array($this, 'ecryptModifyForm'));
			add_filter('gform_pre_render', array($this, 'gformPreRenderSniff'));
			add_filter('gform_admin_pre_render', array($this, 'gformPreRenderSniff'));
			add_action('gform_enable_credit_card_field', '__return_true');
			add_filter('gform_pre_validation', array($this, 'ecryptPreValidation'));
			add_filter('gform_validation', array($this, 'gformValidation'));
			add_action('gform_entry_post_save', array($this, 'gformEntryPostSave'), 10, 2);
			add_filter('gform_custom_merge_tags', array($this, 'gformCustomMergeTags'), 10, 4);
			add_filter('gform_replace_merge_tags', array($this, 'gformReplaceMergeTags'), 10, 7);
			add_filter('gform_entry_meta', array($this, 'gformEntryMeta'), 10, 2);

			// hook into Gravity Forms to handle Recurring Payments custom field
			require GFEWAY_PLUGIN_ROOT . 'includes/class.GFEwayRecurringField.php';
			new GFEwayRecurringField($this);
		}

		if (is_admin()) {
			// kick off the admin handling
			require GFEWAY_PLUGIN_ROOT . 'includes/class.GFEwayAdmin.php';
			new GFEwayAdmin($this);
		}
	}

	/**
	* load text translations
	*/
	public function loadTextDomain() {
		load_plugin_textdomain('gravityforms-eway', false, plugin_basename(dirname(GFEWAY_PLUGIN_FILE)) . '/languages/');
	}

	/**
	* register and enqueue required scripts
	* NB: must happen after Gravity Forms registers scripts
	*/
	public function registerScripts() {
		wp_register_script('eway-ecrypt', 'https://secure.ewaypayments.com/scripts/eCrypt.js', array('jquery'), null, true);
	}

	/**
	* enqueue additional scripts if required by form
	* @param array $form
	* @param boolean $ajax
	*/
	public function gformEnqueueScripts($form, $ajax) {
		if ($this->canEncryptCardDetails($form)) {
			wp_enqueue_script('eway-ecrypt');
			add_action('wp_print_footer_scripts', array($this, 'ecryptInitScript'));
			add_action('gform_preview_footer', array($this, 'ecryptInitScript'));
		}
	}

	/**
	* register inline scripts for client-side encryption if form posts with AJAX
	*/
	public function ecryptInitScript() {
		// when previewing form, will not have printed footer scripts
		if (!wp_script_is('eway-ecrypt', 'done')) {
			wp_print_scripts(array('eway-ecrypt'));
		}

		$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

		echo '<script>';
		readfile(GFEWAY_PLUGIN_ROOT . "js/gfeway_ecrypt$min.js");
		echo '</script>';
	}

	/**
	* check current form for information (front-end and admin)
	* @param array $form
	* @return array
	*/
	public function gformPreRenderSniff($form) {
		// test whether form has a credit card field
		$this->formHasCcField = self::isEwayForm($form['id'], $form['fields']);

		return $form;
	}

	/**
	* set form modifiers for eWAY client side encryption
	* @param array $form
	* @return array
	*/
	public function ecryptModifyForm($form) {
		if ($this->canEncryptCardDetails($form)) {
			// inject eWAY Client Side Encryption
			add_filter('gform_form_tag', array($this, 'ecryptFormTag'), 10, 2);
			add_filter('gform_field_content', array($this, 'ecryptCcField'), 10, 5);
			add_filter('gform_get_form_filter_' . $form['id'], array($this, 'ecryptEndRender'), 10, 2);

			// clear any previously set credit card data set for fooling GF validation
			foreach ($form['fields'] as $field) {
				if (GFFormsModel::get_input_type($field) === 'creditcard') {
					$field_name    = "input_{$field['id']}";
					$ccnumber_name = $field_name . '_1';
					$cvn_name      = $field_name . '_3';

					// clear dummy credit card details used for Gravity Forms validation
					if (!empty($_POST[$ccnumber_name]) || !empty($_POST[$cvn_name])) {
						$_POST[$ccnumber_name] = '';
						$_POST[$cvn_name]      = '';
					}

					// exit loop
					break;
				}
			}
		}

		return $form;
	}

	/**
	* stop injecting eWAY Client Side Encryption
	* @param string $html form html
	* @param array $form
	* @return string
	*/
	public function ecryptEndRender($html, $form) {
		remove_filter('gform_form_tag', array($this, 'ecryptFormTag'), 10, 2);
		remove_filter('gform_field_content', array($this, 'ecryptCcField'), 10, 5);

		return $html;
	}

	/**
	* inject eWAY Client Side Encryption into form tag
	* @param string $tag
	* @param array $form
	* @return string
	*/
	public function ecryptFormTag($tag, $form) {
		$attr = sprintf('data-eway-encrypt-key="%s"', esc_attr($this->ecryptKey));
		$tag = str_replace('<form ', "<form $attr ", $tag);

		return $tag;
	}

	/**
	* inject eWAY Client Side Encryption into credit card field
	* @param string $field_content
	* @param GF_Field $field
	* @param string $value
	* @param int $zero
	* @param int $form_id
	* @return string
	*/
	public function ecryptCcField($field_content, $field, $value, $zero, $form_id) {
		if (RGFormsModel::get_input_type($field) === 'creditcard') {
			$field_id    = "input_{$form_id}_{$field['id']}";
			$ccnumber_id = $field_id . '_1';
			$cvn_id      = $field_id . '_3';

			$field_content = preg_replace("#<input[^>]+id='$ccnumber_id'\K#", ' data-gfeway-encrypt-name="EWAY_CARDNUMBER"', $field_content);
			$field_content = preg_replace("#<input[^>]+id='$cvn_id'\K#",      ' data-gfeway-encrypt-name="EWAY_CARDCVN"', $field_content);
		}

		return $field_content;
	}

	/**
	* put something back into Credit Card field inputs, to enable validation when using eWAY Client Side Encryption
	* @param array $form
	* @return array
	*/
	public function ecryptPreValidation($form) {
		if ($this->canEncryptCardDetails($form)) {

			if (!empty($_POST['EWAY_CARDNUMBER']) && !empty($_POST['EWAY_CARDCVN'])) {
				foreach ($form['fields'] as $field) {
					if (GFFormsModel::get_input_type($field) === 'creditcard') {
						$field_name    = "input_{$field['id']}";
						$ccnumber_name = $field_name . '_1';
						$cvn_name      = $field_name . '_3';

						// fake some credit card details for Gravity Forms to validate
						$_POST[$ccnumber_name] = $this->getTestCardNumber($field->creditCards);
						$_POST[$cvn_name]      = '***';

						add_action("gform_save_field_value_{$form['id']}_{$field['id']}", array($this, 'ecryptSaveCreditCard'), 10, 5);

						// exit loop
						break;
					}
				}
			}

		}

		return $form;
	}

	/**
	* change the credit card field value so that it doesn't imply an incorrect card type when using Client Side Encryption
	* @param string $value
	* @param array $lead
	* @param GF_Field $field
	* @param array $form
	* @param string $input_id
	* @return string
	*/
	public function ecryptSaveCreditCard($value, $lead, $field, $form, $input_id) {
		switch (substr($input_id, -2, 2)) {

			case '.1':
				// card number
				$value = 'XXXXXXXXXXXXXXXX';
				break;

			case '.4':
				// card type
				// translators: credit card type reported when card type is unknown due to client-side encryption
				$value = _x('Card', 'credit card type', 'gravityforms-eway');
				break;

		}

		return $value;
	}

	/**
	* find a test card number for a supported credit card, for faking card number validation when encrypting card details
	* @param array $supportedCards
	* @return string
	*/
	protected function getTestCardNumber($supportedCards) {
		if (empty($supportedCards)) {
			$cardType = 'visa';
		}
		else {
			$cardType = $supportedCards[0];
		}

		$testNumbers = array(
			'amex'			=> '378282246310005',
			'discover'		=> '6011111111111117',
			'mastercard'	=> '5105105105105100',
			'visa'			=> '4444333322221111',
		);

		return isset($testNumbers[$cardType]) ? $testNumbers[$cardType] : $testNumbers['visa'];
	}

	/**
	* process a form validation filter hook; if last page and has credit card field and total, attempt to bill it
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @return array
	*/
	public function gformValidation($data) {

		// make sure all other validations passed
		if ($data['is_valid'] && self::isEwayForm($data['form']['id'], $data['form']['fields'])) {
			require GFEWAY_PLUGIN_ROOT . 'includes/class.GFEwayFormData.php';
			$formData = new GFEwayFormData($data['form']);

			// make sure form hasn't already been submitted / processed
			if ($this->hasFormBeenProcessed($data['form'])) {
				$data['is_valid'] = false;
				$formData->ccField['failed_validation']		= true;
				$formData->ccField['validation_message']	= $this->getErrMsg(GFEWAY_ERROR_ALREADY_SUBMITTED);
			}

			// make that this is the last page of the form and that we have a credit card field and something to bill
			// and that credit card field is not hidden (which indicates that payment is being made another way)
			else if (!$formData->isCcHidden() && $formData->isLastPage() && $formData->ccField !== false) {
				if (!$formData->hasPurchaseFields()) {
					$data['is_valid'] = false;
					$formData->ccField['failed_validation']		= true;
					$formData->ccField['validation_message']	= $this->getErrMsg(GFEWAY_ERROR_NO_AMOUNT);
				}
				else {
					// only check credit card details if we've got something to bill
					if ($formData->total > 0 || $formData->hasRecurringPayments()) {
						// check for required fields
						$required = array(
							'ccName'	=> $this->getErrMsg(GFEWAY_ERROR_REQ_CARD_HOLDER),
							'ccNumber'	=> $this->getErrMsg(GFEWAY_ERROR_REQ_CARD_NAME),
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
	* get payment object
	* @param string $type either 'single' or 'recurring'
	* @return object
	* @throws GFEwayException
	*/
	protected function getPaymentRequestor($type) {
		$eway = null;
		$isLiveSite = !$this->options['useTest'];

		$creds = $this->getEwayCredentials($this->options['useTest']);

		if ($type === 'recurring') {
			// Recurring XML API
			$customerID = $this->options['useTest'] ? '87654321' : $creds['customerID'];
			if (empty($customerID)) {
				throw new GFEwayException(__("Can't request recurring payment; no eWAY customer ID.", 'gravityforms-eway'));
			}
			$eway = new GFEwayRecurringPayment($customerID, $isLiveSite);
		}
		else {
			// single payments
			if (!empty($creds['apiKey']) && !empty($creds['password'])) {
				// Rapid API
				$capture = !$this->options['useStored'];
				$eway = new GFEwayRapidAPI($creds['apiKey'], $creds['password'], $this->options['useTest'], $capture);
			}
			else {
				// legacy XML APIs
				$customerID = ($this->options['useTest'] && $this->options['forceTestAccount']) ? '87654321' : $creds['customerID'];
				if (empty($customerID)) {
					throw new GFEwayException(__("Can't request payment; no eWAY credentials.", 'gravityforms-eway'));
				}
				if ($this->options['useStored']) {
					$eway = new GFEwayStoredPayment($customerID, $isLiveSite);
				}
				else {
					$eway = new GFEwayPayment($customerID, $isLiveSite, $this->options['useBeagle']);
				}
			}
		}

		return $eway;
	}

	/**
	* process regular one-off payment
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @param GFEwayFormData $formData pre-parsed data from $data
	* @return array
	*/
	protected function processSinglePayment($data, $formData) {
		try {
			$eway = $this->getPaymentRequestor('single');

			$eway->sslVerifyPeer			= $this->options['sslVerifyPeer'];
			$eway->customerIP				= self::getCustomerIP();
			$eway->invoiceDescription		= get_bloginfo('name') . " -- {$data['form']['title']}";
			$eway->invoiceReference			= $data['form']['id'];
			$eway->currencyCode				= GFCommon::get_currency();
			if (empty($formData->firstName) && empty($formData->lastName)) {
				$eway->lastName				= $formData->ccName;                // pick up card holder's name for last name
			}
			else {
				$eway->firstName			= $formData->firstName;
				$eway->lastName				= $formData->lastName;
			}
			$eway->cardHoldersName			= $formData->ccName;
			$eway->cardNumber				= $formData->ccNumber;
			$eway->cardExpiryMonth			= $formData->ccExpMonth;
			$eway->cardExpiryYear			= $formData->ccExpYear;
			$eway->emailAddress				= $formData->email;
			$eway->address1					= $formData->address_street1;
			$eway->address2					= $formData->address_street2;
			$eway->suburb					= $formData->address_suburb;
			$eway->state					= $formData->address_state;
			$eway->postcode					= $formData->postcode;
			$eway->countryName				= $formData->address_country;
			$eway->country					= $formData->address_country ? GFCommon::get_country_code($formData->address_country) : '';
			$eway->cardVerificationNumber	= $formData->ccCVN;

			// generate a unique transaction ID to avoid collisions, e.g. between different installations using the same eWAY account
			// uniqid() generates 13-character string, trim back to last 12 characters which is max for field
			$eway->transactionNumber = substr(uniqid(), -12);

			// allow plugins/themes to modify invoice description and reference, and set option fields
			$eway->invoiceDescription		= apply_filters('gfeway_invoice_desc', $eway->invoiceDescription, $data['form']);
			$eway->invoiceReference			= apply_filters('gfeway_invoice_ref', $eway->invoiceReference, $data['form']);
			$eway->transactionNumber		= apply_filters('gfeway_invoice_trans_number', $eway->transactionNumber, $data['form']);
			$eway->options					= array_filter(array(
												apply_filters('gfeway_invoice_option1', '', $data['form']),
												apply_filters('gfeway_invoice_option2', '', $data['form']),
												apply_filters('gfeway_invoice_option3', '', $data['form']),
											), 'strlen');

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

			self::log_debug(sprintf('%s: %s gateway, invoice ref: %s, transaction: %s, amount: %s, currency: %s, cc: %s',
				__FUNCTION__, $this->options['useTest'] ? 'test' : 'live', $eway->invoiceReference, $eway->transactionNumber,
				$eway->amount, $eway->currencyCode, $eway->cardNumber));

			// record basic transaction data, for updating the entry with later
			$this->txResult = array (
				'payment_gateway'		=> 'gfeway',
				'gfeway_unique_id'		=> GFFormsModel::get_form_unique_id($data['form']['id']),	// reduces risk of double-submission
			);

			$response = $eway->processPayment();
			if ($response->TransactionStatus) {
				// transaction was successful, so record details and continue
				$this->txResult['payment_status']	= $this->options['useStored'] ? 'Pending' : 'Approved';
				$this->txResult['payment_date']		= date('Y-m-d H:i:s');
				$this->txResult['payment_amount']	= $response->Payment->TotalAmount;
				$this->txResult['transaction_id']	= $response->TransactionID;
				$this->txResult['transaction_type']	= 1;
				$this->txResult['authcode']			= $response->AuthorisationCode;
				$this->txResult['beagle_score']		= $response->BeagleScore >= 0 ? $response->BeagleScore : '';

				self::log_debug(sprintf('%s: success, date = %s, id = %s, status = %s, amount = %s, authcode = %s, Beagle = %s',
					__FUNCTION__, $this->txResult['payment_date'], $response->TransactionID, $this->txResult['payment_status'],
					$response->Payment->TotalAmount, $response->AuthorisationCode, $response->BeagleScore));
				if (!empty($response->ResponseMessage)) {
					self::log_debug(sprintf('%s: %s', __FUNCTION__, implode('; ', $response->ResponseMessage)));
				}
			}
			else {
				$data['is_valid'] = false;
				$formData->ccField['failed_validation']		= true;
				$formData->ccField['validation_message']	= $this->getErrMsg(GFEWAY_ERROR_EWAY_FAIL);
				$this->txResult['payment_status']			= 'Failed';
				$this->txResult['authcode']					= '';			// empty bank authcode, for conditional logic

				if (!empty($response->Errors)) {
					$formData->ccField['validation_message'] .= ':<br/>' . nl2br(esc_html(implode("\n", $response->Errors)));
				}
				elseif (!empty($response->ResponseMessage)) {
					$formData->ccField['validation_message'] .= ' (' . esc_html(implode(',', array_keys($response->ResponseMessage))) . ')';
				}

				self::log_debug(sprintf('%s: failed; %s', __FUNCTION__, implode('; ', array_merge($response->Errors, $response->ResponseMessage))));
				if ($response->BeagleScore > 0) {
					self::log_debug(sprintf('%s: BeagleScore = %s', __FUNCTION__, $response->BeagleScore));
				}
			}
		}
		catch (GFEwayException $e) {
			$data['is_valid'] = false;
			$formData->ccField['failed_validation']			= true;
			$formData->ccField['validation_message']		= nl2br($this->getErrMsg(GFEWAY_ERROR_EWAY_FAIL) . esc_html(":\n{$e->getMessage()}"));
			$this->txResult['payment_status']				= 'Failed';
			$this->txResult['authcode']						= '';			// empty bank authcode, for conditional logic

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
			$eway = $this->getPaymentRequestor('recurring');

			$eway->sslVerifyPeer		= $this->options['sslVerifyPeer'];
			if (empty($formData->firstName) && empty($formData->lastName)) {
				$eway->firstName		= '-';                             // no first name,
				$eway->lastName			= $formData->ccName;                // pick up card holder's name for last name
			}
			else {
				$eway->title			= $formData->namePrefix;
				$eway->firstName		= $formData->firstName;
				$eway->lastName			= $formData->lastName;
			}
			$eway->emailAddress			= $formData->email;
			$eway->address1				= $formData->address_street1;
			$eway->address2				= $formData->address_street2;
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
				$formData->ccField['failed_validation']		= true;
				$formData->ccField['validation_message']	= $this->getErrMsg(GFEWAY_ERROR_EWAY_FAIL);
				$this->txResult['payment_status']			= 'Failed';

				if (!empty($response->error)) {
					$formData->ccField['validation_message'] .= ':<br/>' . nl2br(esc_html($response->error));
				}

				self::log_debug(sprintf('%s: failed; %s', __FUNCTION__, $response->error));
			}
		}
		catch (GFEwayException $e) {
			$data['is_valid'] = false;
			$formData->ccField['failed_validation']			= true;
			$formData->ccField['validation_message']		= nl2br($this->getErrMsg(GFEWAY_ERROR_EWAY_FAIL) . esc_html(":\n{$e->getMessage()}"));
			$this->txResult['payment_status']				= 'Failed';

			self::log_error(__METHOD__ . ": " . $e->getMessage());
		}

		return $data;
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
					case 'payment_gateway':				// custom entry meta must be saved with entry
					case 'authcode':					// custom entry meta must be saved with entry
						// update entry
						$entry[$key] = $value;
						break;

					default:
						// update entry meta
						gform_update_meta($entry['id'], $key, $value);
						break;
				}
			}

			GFAPI::update_entry($entry);

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
			$merge_tags[] = array('label' => _x('Transaction ID', 'merge tag label', 'gravityforms-eway'), 'tag' => '{transaction_id}');
			$merge_tags[] = array('label' => _x('AuthCode',       'merge tag label', 'gravityforms-eway'), 'tag' => '{authcode}');
			$merge_tags[] = array('label' => _x('Payment Amount', 'merge tag label', 'gravityforms-eway'), 'tag' => '{payment_amount}');
			$merge_tags[] = array('label' => _x('Payment Status', 'merge tag label', 'gravityforms-eway'), 'tag' => '{payment_status}');
			$merge_tags[] = array('label' => _x('Beagle Score',   'merge tag label', 'gravityforms-eway'), 'tag' => '{beagle_score}');
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
				$transaction_id		= isset($lead['transaction_id']) ? $lead['transaction_id'] : '';
				$payment_amount		= isset($lead['payment_amount']) ? $lead['payment_amount'] : '';
				$payment_status		= isset($lead['payment_status']) ? $lead['payment_status'] : '';
				$authcode			= (string) gform_get_meta($lead['id'], 'authcode');
				$beagle_score		= (string) gform_get_meta($lead['id'], 'beagle_score');
			}
			else {
				// lead not yet saved, get values from transaction results
				$transaction_id		= isset($this->txResult['transaction_id']) ? $this->txResult['transaction_id'] : '';
				$payment_amount		= isset($this->txResult['payment_amount']) ? $this->txResult['payment_amount'] : '';
				$payment_status		= isset($this->txResult['payment_status']) ? $this->txResult['payment_status'] : '';
				$authcode			= isset($this->txResult['authcode'])       ? $this->txResult['authcode'] : '';
				$beagle_score		= isset($this->txResult['beagle_score'])   ? $this->txResult['beagle_score'] : '';
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
	* activate and configure custom entry meta
	* @param array $entry_meta
	* @param int $form_id
	* @return array
	*/
	public function gformEntryMeta($entry_meta, $form_id) {

		$entry_meta['payment_gateway'] = array(
			'label'					=> _x('Payment Gateway', 'entry meta label', 'gravityforms-eway'),
			'is_numeric'			=> false,
			'is_default_column'		=> false,
			'filter'				=> array(
											'operators' => array('is', 'isnot')
										),
		);

		$entry_meta['authcode'] = array(
			'label'					=> _x('AuthCode', 'entry meta label', 'gravityforms-eway'),
			'is_numeric'			=> false,
			'is_default_column'		=> false,
			'filter'				=> array(
											'operators' => array('is', 'isnot')
										),
		);

		return $entry_meta;
	}

	/**
	* look at config to see whether client-side encryption is possible
	* @param array $form
	* @return bool
	*/
	protected function canEncryptCardDetails($form) {
		$creds = $this->getEwayCredentials();

		// must have Rapid API key/password and Client Side Encryption key
		if (empty($creds['ecryptKey']) || empty($creds['apiKey']) || empty($creds['password'])) {
			return false;
		}

		// must have a credit card field, and not disabled by another plugin
		if (!self::isEwayForm($form['id'], $form['fields'])) {
			return false;
		}

		$this->ecryptKey = $creds['ecryptKey'];

		return true;
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
				if (RGFormsModel::get_input_type($field) === $type) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	* get eWAY credentials
	* @return string
	*/
	protected function getEwayCredentials() {
		// get defaults from add-on settings
		$creds = array(
			'apiKey'		=> $this->options['apiKey'],
			'password'		=> $this->options['apiPassword'],
			'ecryptKey'		=> $this->options['ecryptKey'],
			'customerID'	=> $this->options['customerID'],
		);

		// override with Sandbox settings if set for Sandbox
		if ($this->options['useTest']) {
			$credsSandbox = array_filter(array(
				'apiKey'		=> $this->options['sandboxApiKey'],
				'password'		=> $this->options['sandboxPassword'],
				'ecryptKey'		=> $this->options['sandboxEcryptKey'],
				'customerID'	=> $this->options['sandboxCustomerID'],
			));
			$creds = array_merge($creds, $credsSandbox);
		}

		return $creds;
	}

	/**
	* get nominated error message, checking for custom error message in WP options
	* @param string $errName the fixed name for the error message (a constant)
	* @param boolean $useDefault whether to return the default, or check for a custom message
	* @return string
	*/
	public function getErrMsg($errName, $useDefault = false) {
		static $messages = false;

		if ($messages === false) {
			$messages = array (
				GFEWAY_ERROR_ALREADY_SUBMITTED  => __('Payment already submitted and processed - please close your browser window', 'gravityforms-eway'),
				GFEWAY_ERROR_NO_AMOUNT          => __('This form has credit card fields, but no products or totals', 'gravityforms-eway'),
				GFEWAY_ERROR_REQ_CARD_HOLDER    => __('Card holder name is required for credit card processing', 'gravityforms-eway'),
				GFEWAY_ERROR_REQ_CARD_NAME      => __('Card number is required for credit card processing', 'gravityforms-eway'),
				GFEWAY_ERROR_EWAY_FAIL          => __('Transaction failed', 'gravityforms-eway'),
			);
		}

		// default
		$msg = isset($messages[$errName]) ? $messages[$errName] : __('Unknown error', 'gravityforms-eway');

		// check for custom message
		if (!$useDefault) {
			// check that messages are stored in options array; only since v1.8.0
			if (isset($this->options[$errName])) {
				if (!empty($this->options[$errName])) {
					$msg = $this->options[$errName];
				}
			}
			else {
				// pre-1.8.0 settings stored individually, not using settings API
				$msg = get_option($errName, $msg);
			}
		}

		return $msg;
	}

	/**
	* enable Gravity Forms Logging Add-On support for this plugin
	* @param array $plugins
	* @return array
	*/
	public function enableLogging($plugins){
		$plugins['gfeway'] = __('Gravity Forms eWAY', 'gravityforms-eway');

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
	* compare Gravity Forms version against minimum required version
	* @return bool
	*/
	public static function hasMinimumGF() {
		return self::versionCompareGF(self::MIN_VERSION_GF, '>=');
	}

	/**
	* send data via HTTP and return response
	* @deprecated only used now for legacy Direct API and its friends
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
		if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1' && $plugin->options['useTest']) {
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

	/**
	* autoload classes as/when needed
	*
	* @param string $class_name name of class to attempt to load
	*/
	public static function autoload($class_name) {
		static $classMap = array (
			'GFEwayPayment'						=> 'includes/class.GFEwayPayment.php',
			'GFEwayRapidAPI'					=> 'includes/class.GFEwayRapidAPI.php',
			'GFEwayRapidAPIResponse'			=> 'includes/class.GFEwayRapidAPIResponse.php',
			'GFEwayRecurringPayment'			=> 'includes/class.GFEwayRecurringPayment.php',
			'GFEwayStoredPayment'				=> 'includes/class.GFEwayStoredPayment.php',
		);

		if (isset($classMap[$class_name])) {
			require GFEWAY_PLUGIN_ROOT . $classMap[$class_name];
		}
	}

}
