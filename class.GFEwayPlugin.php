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

		// hook into Gravity Forms to enable credit cards and trap form submissions
		add_action('gform_enable_credit_card_field', '__return_true');		// just return true to enable CC fields
		add_filter('gform_creditcard_types', array($this, 'gformCCTypes'));
		add_filter('gform_currency', array($this, 'gformCurrency'));
		add_filter('gform_currency_disabled', '__return_true');
		add_filter('gform_validation',array($this, "gformValidation"));
		add_action('gform_after_submission',array($this, "gformAfterSubmission"), 10, 2);

		if (is_admin()) {
			// kick off the admin handling
			new GFEwayAdmin($this);
		}
	}

	/**
	* initialise plug-in options, handling undefined options by setting defaults
	*/
	private function initOptions() {
		static $defaults = array (
			'customerID' => '87654321',
			'useTest' => TRUE,
		);

		$this->options = get_option(GFEWAY_PLUGIN_OPTIONS);
		if (!is_array($this->options)) {
			$this->options = array();
		}

		$optsUpdate = FALSE;
		foreach ($defaults as $option => $value) {
			if (!isset($this->options[$option])) {
				$this->options[$option] = $value;
				$optsUpdate = TRUE;
			}
		}
		if ($optsUpdate) {
			update_option(GFEWAY_PLUGIN_OPTIONS, $this->options);
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
			if (!$formData->isCcHidden() && $formData->isLastPage() && is_array($formData->ccField) && $formData->total > 0) {
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
					$isLiveSite = ($this->options['useTest'] != 'Y');

					try {
						$eway = new GFEwayPayment($this->options['customerID'], $isLiveSite);
						$eway->invoiceDescription = get_bloginfo('name') . " -- {$data['form']['title']}";
						$eway->invoiceReference = $data['form']['id'];
						$eway->lastName = $formData->ccName;
						$eway->cardHoldersName = $formData->ccName;
						$eway->cardNumber = $formData->ccNumber;
						$eway->cardExpiryMonth = $formData->ccExpMonth;
						$eway->cardExpiryYear = $formData->ccExpYear;
						$eway->emailAddress = $formData->email;
						$eway->address = $formData->address;
						$eway->postcode = $formData->postcode;
						$eway->cardVerificationNumber = $formData->ccCVN;

						// if live, pass through amount exactly, but if using test site, round up to whole dollars or eWAY will fail
						$eway->amount = $isLiveSite ? $formData->total : ceil($formData->total);

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

				}

				// if errors, send back to credit card page
				if (!$data['is_valid']) {
					GFFormDisplay::set_current_page($data['form']['id'], $formData->ccField['pageNumber']);
				}
			}

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
