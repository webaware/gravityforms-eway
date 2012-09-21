<?php
/**
* class for managing the plugin
*/
class GFEwayPlugin {
	public $urlBase;									// string: base URL path to files in plugin

	private $acceptedCards;								// hash map of accepted credit cards
	private $txResult;									// results from credit card payment transaction

	/**
	* static method for getting the instance of this singleton object
	*
	* @return GFEwayPlugin
	*/
	public static function getInstance() {
		static $instance = NULL;

		if (is_null($instance)) {
			$class = __CLASS__;
			$instance = new $class;
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
			'useTest' => TRUE,
			'roundTestAmounts' => TRUE,
			'forceTestAccount' => TRUE,
			'sslVerifyPeer' => TRUE,
		);

		$this->options = (array) get_option(GFEWAY_PLUGIN_OPTIONS);

		// if haven't defined whether to verify peer (i.e. older version), then default to TRUE
		if (count($this->options) > 1 && !array_key_exists('sslVerifyPeer', $this->options)) {
			$this->options['sslVerifyPeer'] = TRUE;
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
		// hook into Gravity Forms to enable credit cards and trap form submissions
		add_action('gform_enable_credit_card_field', '__return_true');		// just return true to enable CC fields
		add_filter('gform_creditcard_types', array($this, 'gformCCTypes'));
		add_filter('gform_currency', array($this, 'gformCurrency'));
		add_filter('gform_currency_disabled', '__return_true');
		add_filter('gform_validation', array($this, "gformValidation"));
		add_action('gform_after_submission', array($this, "gformAfterSubmission"), 10, 2);
		add_action('gform_enqueue_scripts', array($this, "gformEnqueueScripts"), 20, 2);

		// hook into Gravity Forms to handle Recurring Payments custom field
		new GFEwayRecurringField($this);

		if (is_admin()) {
			// kick off the admin handling
			new GFEwayAdmin($this);
		}
	}

	/**
	* enqueue additional scripts if required by form
	* @param array $form
	* @param boolean $ajax
	*/
	public function gformEnqueueScripts($form, $ajax) {
		$version = GFEWAY_PLUGIN_VERSION;

		// scripts/styling for recurring payments field
		if (self::has_field_type($form, 'gfewayrecurring')) {
			// recurring payments field has datepickers
			$gfBaseUrl = GFCommon::get_base_url();
			wp_enqueue_script('gforms_ui_datepicker', $gfBaseUrl . '/js/jquery-ui/ui.datepicker.js', array('jquery'), GFCommon::$version, true);
			wp_enqueue_script('gforms_datepicker', $gfBaseUrl . '/js/datepicker.js', array('gforms_ui_datepicker'), GFCommon::$version, true);

			// enqueue script for recurring payments
			wp_enqueue_script('gfeway_recurring', $this->urlBase . 'js/recurring.min.js', array('gforms_ui_datepicker'), $version, true);

			// enqueue default styling
			wp_enqueue_style('gfeway', $this->urlBase . 'style.css', false, $version);
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

			// make that this is the last page of the form and that we have a credit card field and something to bill
			// and that credit card field is not hidden (which indicates that payment is being made another way)
			if (!$formData->isCcHidden() && $formData->isLastPage() && is_array($formData->ccField)) {
				if (!$formData->hasPurchaseFields()) {
					$data['is_valid'] = false;
					$formData->ccField['failed_validation'] = true;
					$formData->ccField['validation_message'] = 'This form has credit card fields, but no products or totals';
				}
				else {
					// only check credit card details if we've got something to bill
					if ($formData->total > 0 || $formData->hasRecurringPayments()) {
						// check for required fields
						$required = array(
							'ccName' => 'Card holder name is required for credit card processing.',
							'ccNumber' => 'Card number is required for credit card processing.',
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

						// if errors, send back to credit card page
						if (!$data['is_valid']) {
							GFFormDisplay::set_current_page($data['form']['id'], $formData->ccField['pageNumber']);
						}
					}
				}
			}

		}

		return $data;
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

			// allow plugins/themes to modify invoice description and reference, and set option fields
			$eway->invoiceDescription = apply_filters('gfeway_invoice_desc', $eway->invoiceDescription, $data['form']);
			$eway->invoiceReference = apply_filters('gfeway_invoice_ref', $eway->invoiceReference, $data['form']);
			$eway->option1 = apply_filters('gfeway_invoice_option1', '', $data['form']);
			$eway->option2 = apply_filters('gfeway_invoice_option2', '', $data['form']);
			$eway->option3 = apply_filters('gfeway_invoice_option3', '', $data['form']);

//~ $data['is_valid'] = false;
//~ $formData->ccField['failed_validation'] = true;
//~ $formData->ccField['validation_message'] = nl2br("success:\n" . htmlspecialchars($eway->getPaymentXML()));

			// if live, pass through amount exactly, but if using test site, round up to whole dollars or eWAY will fail
			if ($isLiveSite || $this->options['roundTestAmounts'] != 'Y')
				$eway->amount = $formData->total;
			else
				$eway->amount = ceil($formData->total);

			$response = $eway->processPayment();
			if ($response->status) {
				// transaction was successful, so record transaction number and continue
				$this->txResult = array (
					'transaction_id' => $response->transactionNumber,
					'payment_status' => 'Approved',
					'payment_date' => date('Y-m-d H:i:s'),
					'payment_amount' => $response->amount,
					'transaction_type' => 1,
				);
			}
			else {
				$data['is_valid'] = false;
				$formData->ccField['failed_validation'] = true;
				$formData->ccField['validation_message'] = nl2br("Error processing card transaction:\n{$response->error}");
				$this->txResult = array (
					'payment_status' => 'Failed',
				);
			}
		}
		catch (Exception $e) {
			$data['is_valid'] = false;
			$this->txResult = array (
				'payment_status' => 'Failed',
			);
			$formData->ccField['failed_validation'] = true;
			$formData->ccField['validation_message'] = nl2br("Error processing card transaction:\n{$e->getMessage()}");
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
			$eway->customerComments = apply_filters('gfeway_invoice_cust_comments', '', $data['form']);

//~ $data['is_valid'] = false;
//~ $formData->ccField['failed_validation'] = true;
//~ $formData->ccField['validation_message'] = nl2br("success:\n" . htmlspecialchars($eway->getPaymentXML()));

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
				$formData->ccField['validation_message'] = nl2br("Error processing card transaction:\n{$response->error}");
				$this->txResult = array (
					'payment_status' => 'Failed',
				);
			}
		}
		catch (Exception $e) {
			$data['is_valid'] = false;
			$this->txResult = array (
				'payment_status' => 'Failed',
			);
			$formData->ccField['failed_validation'] = true;
			$formData->ccField['validation_message'] = nl2br("Error processing card transaction:\n{$e->getMessage()}");
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
				$entry[$key] = $value;
			}
			RGFormsModel::update_lead($entry);
		}
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
	* check form to see if it has a Recurring Payments field
	* @param array $form form object
	* @param string $type name of field type
	* @return boolean
	*/
	public static function has_field_type($form, $type) {
		if (is_array($form['fields'])) {
			foreach ($form['fields'] as $field) {
				if (RGFormsModel::get_input_type($field) == $type)
					return true;
			}
		}
		return false;
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
