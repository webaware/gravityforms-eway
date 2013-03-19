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
	public $urlBase;									// string: base URL path to files in plugin
	public $options;									// array of plugin options

	private $acceptedCards;								// hash map of accepted credit cards
	private $txResult = null;							// results from credit card payment transaction

	/**
	* static method for getting the instance of this singleton object
	*
	* @return GFEwayPlugin
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
		$this->urlBase = plugin_dir_url(__FILE__);

		// filter the cards array to just Visa, MasterCard and Amex
		$this->acceptedCards = array('amex' => 1, 'mastercard' => 1, 'visa' => 1);

		add_action('init', array($this, 'init'));
	}

	/**
	* initialise plug-in options, handling undefined options by setting defaults
	*/
	private function initOptions() {
		static $defaults = array (
			'customerID' => '87654321',
			'useStored' => false,
			'useTest' => true,
			'useBeagle' => false,
			'roundTestAmounts' => true,
			'forceTestAccount' => true,
			'sslVerifyPeer' => true,
		);

		$this->options = (array) get_option(GFEWAY_PLUGIN_OPTIONS);

		if (count(array_diff_assoc($defaults, $this->options)) > 0) {
			$this->options = array_merge($defaults, $this->options);
			update_option(GFEWAY_PLUGIN_OPTIONS, $this->options);
		}
	}

	/**
	* handle the plugin's init action
	*/
	public function init() {
		// do nothing if Gravity Forms isn't enabled
		if (class_exists('GFCommon')) {
			// hook into Gravity Forms to enable credit cards and trap form submissions
			add_action('gform_enable_credit_card_field', '__return_true');		// just return true to enable CC fields
			add_filter('gform_creditcard_types', array($this, 'gformCCTypes'));
			add_filter('gform_currency', array($this, 'gformCurrency'));
			add_filter('gform_currency_disabled', '__return_true');
			add_filter('gform_validation', array($this, 'gformValidation'));
			add_action('gform_after_submission', array($this, 'gformAfterSubmission'), 10, 2);
			add_filter('gform_custom_merge_tags', array($this, 'gformCustomMergeTags'), 10, 4);
			add_filter('gform_replace_merge_tags', array($this, 'gformReplaceMergeTags'), 10, 7);

			// hook into Gravity Forms to handle Recurring Payments custom field
			new GFEwayRecurringField($this);

			// recurring payments field has datepickers, register required scripts / stylesheets
			$gfBaseUrl = GFCommon::get_base_url();
			wp_register_script('gforms_ui_datepicker', $gfBaseUrl . '/js/jquery-ui/ui.datepicker.js', array('jquery'), GFCommon::$version, true);
			wp_register_script('gforms_datepicker', $gfBaseUrl . '/js/datepicker.js', array('gforms_ui_datepicker'), GFCommon::$version, true);
			wp_register_script('gfeway_recurring', $this->urlBase . 'js/recurring.min.js', array('gforms_datepicker'), GFEWAY_PLUGIN_VERSION, true);
			wp_register_style('gfeway', $this->urlBase . 'style.css', false, GFEWAY_PLUGIN_VERSION);
		}

		if (is_admin()) {
			// kick off the admin handling
			new GFEwayAdmin($this);
		}
	}

	/**
	* process a form validation filter hook; if last page and has credit card field and total, attempt to bill it
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @return array
	*/
	public function gformValidation($data) {

		// make sure all other validations passed
		if ($data['is_valid']) {
			$formData = new GFEwayFormData($data['form']);

			// make sure form hasn't already been submitted / processed
			if ($this->hasFormBeenProcessed($data['form'])) {
				$data['is_valid'] = false;
				$formData->ccField['failed_validation'] = true;
				$formData->ccField['validation_message'] = $this->getErrMsg(GFEWAY_ERROR_ALREADY_SUBMITTED);
			}

			// make that this is the last page of the form and that we have a credit card field and something to bill
			// and that credit card field is not hidden (which indicates that payment is being made another way)
			else if (!$formData->isCcHidden() && $formData->isLastPage() && is_array($formData->ccField)) {
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
								if (!empty($formData->ccField['validation_message']))
									$formData->ccField['validation_message'] .= '<br />';
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
	private function hasFormBeenProcessed($form) {
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
	private function getCustomerID() {
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
	private function processSinglePayment($data, $formData) {
		try {
			if ($this->options['useStored'])
				$eway = new GFEwayStoredPayment($this->getCustomerID(), !$this->options['useTest']);
			else
				$eway = new GFEwayPayment($this->getCustomerID(), !$this->options['useTest']);

			$eway->sslVerifyPeer = $this->options['sslVerifyPeer'];
			$eway->invoiceDescription = get_bloginfo('name') . " -- {$data['form']['title']}";
			$eway->invoiceReference = $data['form']['id'];
			if (empty($formData->firstName) && empty($formData->lastName)) {
				$eway->lastName = $formData->ccName;				// pick up card holder's name for last name
			}
			else {
				$eway->firstName = $formData->firstName;
				$eway->lastName = $formData->lastName;
			}
			$eway->cardHoldersName = $formData->ccName;
			$eway->cardNumber = $formData->ccNumber;
			$eway->cardExpiryMonth = $formData->ccExpMonth;
			$eway->cardExpiryYear = $formData->ccExpYear;
			$eway->emailAddress = $formData->email;
			$eway->address = $formData->address;
			$eway->postcode = $formData->postcode;
			$eway->cardVerificationNumber = $formData->ccCVN;

			// if Beagle is enabled, get the country code
			if ($this->options['useBeagle']) {
				$eway->customerCountryCode = GFCommon::get_country_code($formData->address_country);
			}

			// allow plugins/themes to modify invoice description and reference, and set option fields
			$eway->invoiceDescription = apply_filters('gfeway_invoice_desc', $eway->invoiceDescription, $data['form']);
			$eway->invoiceReference = apply_filters('gfeway_invoice_ref', $eway->invoiceReference, $data['form']);
			$eway->transactionNumber = apply_filters('gfeway_invoice_trans_number', $eway->transactionNumber, $data['form']);
			$eway->option1 = apply_filters('gfeway_invoice_option1', '', $data['form']);
			$eway->option2 = apply_filters('gfeway_invoice_option2', '', $data['form']);
			$eway->option3 = apply_filters('gfeway_invoice_option3', '', $data['form']);

			// if live, pass through amount exactly, but if using test site, round up to whole dollars or eWAY will fail
			if ($this->options['useTest'] && $this->options['roundTestAmounts'])
				$eway->amount = ceil($formData->total);
			else
				$eway->amount = $formData->total;

//~ error_log(__METHOD__ . "\n" . print_r($eway,1));
//~ error_log(__METHOD__ . "\n" . $eway->getPaymentXML());

			$response = $eway->processPayment();
			if ($response->status) {
				// transaction was successful, so record transaction number and continue
				$this->txResult = array (
					'transaction_id' => $response->transactionNumber,
					'payment_status' => ($this->options['useStored'] ? 'Pending' : 'Approved'),
					'payment_date' => date('Y-m-d H:i:s'),
					'payment_amount' => $response->amount,
					'transaction_type' => 1,
					'authcode' => $response->authCode,
					'beagle_score' => $response->beagleScore,
				);
			}
			else {
				$data['is_valid'] = false;
				$formData->ccField['failed_validation'] = true;
				$formData->ccField['validation_message'] = nl2br($this->getErrMsg(GFEWAY_ERROR_EWAY_FAIL) . ":\n{$response->error}");
				$this->txResult = array (
					'payment_status' => 'Failed',
				);
			}
		}
		catch (GFEwayException $e) {
			$data['is_valid'] = false;
			$this->txResult = array (
				'payment_status' => 'Failed',
			);
			$formData->ccField['failed_validation'] = true;
			$formData->ccField['validation_message'] = nl2br($this->getErrMsg(GFEWAY_ERROR_EWAY_FAIL) . ":\n{$e->getMessage()}");
		}

		return $data;
	}

	/**
	* process recurring payments
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @param GFEwayFormData $formData pre-parsed data from $data
	* @return array
	*/
	private function processRecurringPayment($data, $formData) {
		try {
			$eway = new GFEwayRecurringPayment($this->getCustomerID(), !$this->options['useTest']);
			$eway->sslVerifyPeer = $this->options['sslVerifyPeer'];
			if (empty($formData->firstName) && empty($formData->lastName)) {
				$eway->firstName = '-';								// no first name,
				$eway->lastName = $formData->ccName;				// pick up card holder's name for last name
			}
			else {
				$eway->title = $formData->namePrefix;
				$eway->firstName = $formData->firstName;
				$eway->lastName = $formData->lastName;
			}
			$eway->emailAddress = $formData->email;
			$eway->address = $formData->address_street;
			$eway->suburb = $formData->address_suburb;
			$eway->state = $formData->address_state;
			$eway->postcode = $formData->postcode;
			$eway->country = $formData->address_country;
			$eway->phone = $formData->phone;
			$eway->invoiceReference = $data['form']['id'];
			$eway->invoiceDescription = get_bloginfo('name') . " -- {$data['form']['title']}";
			$eway->cardHoldersName = $formData->ccName;
			$eway->cardNumber = $formData->ccNumber;
			$eway->cardExpiryMonth = $formData->ccExpMonth;
			$eway->cardExpiryYear = $formData->ccExpYear;
			$eway->amountInit = $formData->recurring['amountInit'];
			$eway->dateInit = $formData->recurring['dateInit'];
			$eway->amountRecur = $formData->recurring['amountRecur'];
			$eway->dateStart = $formData->recurring['dateStart'];
			$eway->dateEnd = $formData->recurring['dateEnd'];
			$eway->intervalSize = $formData->recurring['intervalSize'];
			$eway->intervalType = $formData->recurring['intervalType'];

			// allow plugins/themes to modify invoice description and reference, and set option fields
			$eway->invoiceDescription = apply_filters('gfeway_invoice_desc', $eway->invoiceDescription, $data['form']);
			$eway->invoiceReference = apply_filters('gfeway_invoice_ref', $eway->invoiceReference, $data['form']);
			$eway->transactionNumber = apply_filters('gfeway_invoice_trans_number', $eway->transactionNumber, $data['form']);
			$eway->customerComments = apply_filters('gfeway_invoice_cust_comments', '', $data['form']);

//~ error_log(__METHOD__ . "\n" . print_r($eway,1));
//~ error_log(__METHOD__ . "\n" . $eway->getPaymentXML());

			$response = $eway->processPayment();
			if ($response->status) {
				// transaction was successful, so record transaction number and continue
				$this->txResult = array (
					'payment_status' => 'Approved',
					'payment_date' => date('Y-m-d H:i:s'),
					'transaction_type' => 1,
				);
			}
			else {
				$data['is_valid'] = false;
				$formData->ccField['failed_validation'] = true;
				$formData->ccField['validation_message'] = nl2br($this->getErrMsg(GFEWAY_ERROR_EWAY_FAIL) . ":\n{$response->error}");
				$this->txResult = array (
					'payment_status' => 'Failed',
				);
			}
		}
		catch (GFEwayException $e) {
			$data['is_valid'] = false;
			$this->txResult = array (
				'payment_status' => 'Failed',
			);
			$formData->ccField['failed_validation'] = true;
			$formData->ccField['validation_message'] = nl2br($this->getErrMsg(GFEWAY_ERROR_EWAY_FAIL) . ":\n{$e->getMessage()}");
		}

		return $data;
	}

	/**
	* save the transaction details to the entry after it has been created
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @return array
	*/
	public function gformAfterSubmission($entry, $form) {
		$formData = new GFEwayFormData($form);

		if (!empty($this->txResult)) {
			foreach ($this->txResult as $key => $value) {
				switch ($key) {
					case 'authcode':
					case 'beagle_score':
						// record bank authorisation code, Beagle score
						gform_update_meta($entry['id'], $key, $value);
						break;

					default:
						$entry[$key] = $value;
						break;
				}
			}
			RGFormsModel::update_lead($entry);

			// record entry's unique ID in database
			$unique_id = RGFormsModel::get_form_unique_id($form['id']);

			gform_update_meta($entry['id'], 'gfeway_unique_id', $unique_id);

			// record payment gateway
			gform_update_meta($entry['id'], 'payment_gateway', 'gfeway');
		}
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
		if ($fields && $this->hasFieldType($fields, 'creditcard')) {
			$merge_tags[] = array('label' => 'Transaction ID', 'tag' => '{transaction_id}');
			$merge_tags[] = array('label' => 'Auth Code', 'tag' => '{authcode}');
			$merge_tags[] = array('label' => 'Payment Amount', 'tag' => '{payment_amount}');
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
		if (is_null($this->txResult)) {
			// lead loaded from database, get values from lead meta
			$transaction_id = isset($lead['transaction_id']) ? $lead['transaction_id'] : '';
			$payment_amount = isset($lead['payment_amount']) ? $lead['payment_amount'] : '';
			$authcode = (string) gform_get_meta($lead['id'], 'authcode');
			$beagle_score = (string) gform_get_meta($lead['id'], 'beagle_score');
		}
		else {
			// lead not yet saved, get values from transaction results
			$transaction_id = isset($this->txResult['transaction_id']) ? $this->txResult['transaction_id'] : '';
			$payment_amount = isset($this->txResult['payment_amount']) ? $this->txResult['payment_amount'] : '';
			$authcode = isset($this->txResult['authcode']) ? $this->txResult['authcode'] : '';
			$beagle_score = isset($this->txResult['beagle_score']) ? $this->txResult['beagle_score'] : '';
		}

		$tags = array (
			'{transaction_id}',
			'{payment_amount}',
			'{authcode}',
			'{beagle_score}',
		);
		$values = array (
			$transaction_id,
			$payment_amount,
			$authcode,
			$beagle_score,
		);
		return str_replace($tags, $values, $text);
	}

	/**
	* tell Gravity Forms what credit cards we can process
	* @param array $cards
	* @return array
	*/
	public function gformCCTypes($cards) {
		$new_cards = array();
		foreach ($cards as $i => $card) {
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
		return 'AUD';
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
				if (RGFormsModel::get_input_type($field) == $type)
					return true;
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
			GFEWAY_ERROR_ALREADY_SUBMITTED		=> 'Payment already submitted and processed - please close your browser window',
			GFEWAY_ERROR_NO_AMOUNT				=> 'This form has credit card fields, but no products or totals',
			GFEWAY_ERROR_REQ_CARD_HOLDER		=> 'Card holder name is required for credit card processing',
			GFEWAY_ERROR_REQ_CARD_NAME			=> 'Card number is required for credit card processing',
			GFEWAY_ERROR_EWAY_FAIL				=> 'Error processing card transaction',
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
	* send data via cURL and return result
	* @param string $url
	* @param string $data
	* @param bool $sslVerifyPeer whether to validate the SSL certificate
	* @return string $response
	* @throws GFEwayCurlException
	*/
	public static function curlSendRequest($url, $data, $sslVerifyPeer = true) {
		// send data via HTTPS and receive response
		$response = wp_remote_post($url, array(
			'user-agent' => GFEWAY_CURL_USER_AGENT,
			'sslverify' => $sslVerifyPeer,
			'timeout' => 60,
			'headers' => array('Content-Type' => 'text/xml; charset=utf-8'),
			'body' => $data,
		));

//~ error_log(__METHOD__ . "\n" . print_r($response,1));

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
		if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '127.0.0.1' && get_option('eway_test')) {
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
	* display a message (already HTML-conformant)
	* @param string $msg HTML-encoded message to display inside a paragraph
	*/
	public static function showMessage($msg) {
		echo "<div class='updated fade'><p><strong>$msg</strong></p></div>\n";
	}

	/**
	* display an error message (already HTML-conformant)
	* @param string $msg HTML-encoded message to display inside a paragraph
	*/
	public static function showError($msg) {
		echo "<div class='error'><p><strong>$msg</strong></p></div>\n";
	}
}
