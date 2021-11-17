<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* Class for dealing with an Eway Rapid API payment
* @link https://eway.io/api-v3/
*/
class GFEwayRapidAPI {

	#region "constants"

	// API hosts
	const API_HOST_LIVE						= 'https://api.ewaypayments.com';
	const API_HOST_SANDBOX					= 'https://api.sandbox.ewaypayments.com';

	// API endpoints for REST/JSON
	const API_DIRECT_PAYMENT				= 'Transaction';

	// valid actions
	const METHOD_PAYMENT					= 'ProcessPayment';
	const METHOD_AUTHORISE					= 'Authorise';

	// valid transaction types
	const TRANS_PURCHASE					= 'Purchase';
	const TRANS_RECURRING					= 'Recurring';
	const TRANS_MOTO						= 'MOTO';

	const PARTNER_ID						= '4577fd8eb9014c7188d7be672c0e0d88';

	#endregion // constants

	#region "members"

	#region "connection specific members"

	/**
	* use Eway sandbox
	* @var boolean
	*/
	public $useSandbox;

	/**
	* capture payment (alternative is just authorise, no capture)
	* @var boolean
	*/
	public $capture;

	/**
	* default TRUE, whether to validate the remote SSL certificate
	* @var boolean
	*/
	public $sslVerifyPeer;

	/**
	* API key
	* @var string
	*/
	public $apiKey;

	/**
	* API password
	* @var string
	*/
	public $apiPassword;

	/**
	* Beagle: IP address of purchaser (from REMOTE_ADDR)
	* @var string max. 50 characters
	*/
	public $customerIP;

	/**
	* ID of device or application processing the transaction
	* @var string max. 50 characters
	*/
	public $deviceID;

	#endregion // "connection specific members"

	#region "payment specific members"

	/**
	* action to perform: one of the METHOD_* values
	* @var string
	*/
	public $method;

	/**
	* a unique transaction number from your site
	* @var string max. 12 characters
	*/
	public $transactionNumber;

	/**
	* an invoice reference to track by (NB: see transactionNumber which is intended for invoice number or similar)
	* @var string max. 50 characters
	*/
	public $invoiceReference;

	/**
	* description of what is being purchased / paid for
	* @var string max. 64 characters
	*/
	public $invoiceDescription;

	/**
	* total amount of payment, in dollars and cents as a floating-point number (will be converted to just cents for transmission)
	* @var float
	*/
	public $amount;

	/**
	* ISO 4217 currency code
	* @var string 3 characters in uppercase
	*/
	public $currencyCode;

	// customer and billing details

	/**
	* customer's title
	* @var string max. 5 characters
	*/
	public $title;

	/**
	* customer's first name
	* @var string max. 50 characters
	*/
	public $firstName;

	/**
	* customer's last name
	* @var string max. 50 characters
	*/
	public $lastName;

	/**
	* customer's company name
	* @var string max. 50 characters
	*/
	public $companyName;

	/**
	* customer's job description (e.g. position)
	* @var string max. 50 characters
	*/
	public $jobDescription;

	/**
	* customer's address line 1
	* @var string max. 50 characters
	*/
	public $address1;

	/**
	* customer's address line 2
	* @var string max. 50 characters
	*/
	public $address2;

	/**
	* customer's suburb/city/town
	* @var string max. 50 characters
	*/
	public $suburb;

	/**
	* customer's state/province
	* @var string max. 50 characters
	*/
	public $state;

	/**
	* customer's postcode
	* @var string max. 30 characters
	*/
	public $postcode;

	/**
	* customer's country code
	* @var string 2 characters lowercase
	*/
	public $country;

	/**
	* customer's email address
	* @var string max. 50 characters
	*/
	public $emailAddress;

	/**
	* customer's phone number
	* @var string max. 32 characters
	*/
	public $phone;

	/**
	* customer's mobile phone number
	* @var string max. 32 characters
	*/
	public $mobile;

	/**
	* customer's fax number
	* @var string max. 32 characters
	*/
	public $fax;

	/**
	* customer's website URL
	* @var string max. 512 characters
	*/
	public $website;

	/**
	* comments about the customer
	* @var string max. 255 characters
	*/
	public $comments;

	// card details

	/**
	* name on credit card
	* @var string max. 50 characters
	*/
	public $cardHoldersName;

	/**
	* credit card number, with no spaces
	* @var string max. 50 characters
	*/
	public $cardNumber;

	/**
	* month of expiry, numbered from 1=January
	* @var integer max. 2 digits
	*/
	public $cardExpiryMonth;

	/**
	* year of expiry
	* @var integer will be truncated to 2 digits, can accept 4 digits
	*/
	public $cardExpiryYear;

	/**
	* start month, numbered from 1=January
	* @var integer max. 2 digits
	*/
	public $cardStartMonth;

	/**
	* start year
	* @var integer will be truncated to 2 digits, can accept 4 digits
	*/
	public $cardStartYear;

	/**
	* card issue number
	* @var string
	*/
	public $cardIssueNumber;

	/**
	* CVN (Creditcard Verification Number) for verifying physical card is held by buyer
	* @var string max. 3 or 4 characters (depends on type of card)
	*/
	public $cardVerificationNumber;

	/**
	* optional additional information for use in shopping carts, etc.
	* @var array[string] max. 254 characters each
	*/
	public $options = [];

	#endregion "payment specific members"

	#endregion "members"

	/**
	* populate members with defaults, and set account and environment information
	* @param string $apiKey Eway API key
	* @param string $apiPassword Eway API password
	* @param boolean $useSandbox use Eway sandbox
	* @param boolean $capture capture payment now, or authorise for later capture
	*/
	public function __construct($apiKey, $apiPassword, $useSandbox = true, $capture = true) {
		$this->apiKey		= $apiKey;
		$this->apiPassword	= $apiPassword;
		$this->useSandbox	= $useSandbox;
		$this->capture		= $capture;
	}

	/**
	* process a payment against Eway; throws exception on error with error described in exception message.
	* @throws GFEwayException
	*/
	public function processPayment() {
		$this->validate();
		$json = $this->getPayment();
		return $this->sendPaymentDirect($json);
	}

	/**
	* validate the data members to ensure that sufficient and valid information has been given
	*/
	protected function validate() {
		$errors = [];

		if (!is_numeric($this->amount) || $this->amount <= 0) {
			$errors[] = __('amount must be given as a number in dollars and cents', 'gravityforms-eway');
		}
		else if (!is_float($this->amount)) {
			$this->amount = (float) $this->amount;
		}
		if (strlen($this->cardHoldersName) === 0) {
			$errors[] = __('cardholder name cannot be empty', 'gravityforms-eway');
		}
		if (strlen($this->cardNumber) === 0) {
			$errors[] = __('card number cannot be empty', 'gravityforms-eway');
		}

		// make sure that card expiry month is a number from 1 to 12
		if (!is_int($this->cardExpiryMonth)) {
			if (strlen($this->cardExpiryMonth) === 0) {
				$errors[] = __('card expiry month cannot be empty', 'gravityforms-eway');
			}
			elseif (!ctype_digit($this->cardExpiryMonth)) {
				$errors[] = __('card expiry month must be a number between 1 and 12', 'gravityforms-eway');
			}
			else {
				$this->cardExpiryMonth = intval($this->cardExpiryMonth);
			}
		}
		if (is_int($this->cardExpiryMonth)) {
			if ($this->cardExpiryMonth < 1 || $this->cardExpiryMonth > 12) {
				$errors[] = __('card expiry month must be a number between 1 and 12', 'gravityforms-eway');
			}
		}

		// make sure that card expiry year is a 2-digit or 4-digit year >= this year
		if (!is_int($this->cardExpiryYear)) {
			if (strlen($this->cardExpiryYear) === 0) {
				$errors[] = __('card expiry year cannot be empty', 'gravityforms-eway');
			}
			elseif (!ctype_digit($this->cardExpiryYear)) {
				$errors[] = __('card expiry year must be a two or four digit year', 'gravityforms-eway');
			}
			else {
				$this->cardExpiryYear = intval($this->cardExpiryYear);
			}
		}
		if (is_int($this->cardExpiryYear)) {
			$thisYear = intval(date_create()->format('Y'));
			if ($this->cardExpiryYear < 0 || $this->cardExpiryYear >= 100 && $this->cardExpiryYear < 2000 || $this->cardExpiryYear > $thisYear + 20) {
				$errors[] = __('card expiry year must be a two or four digit year', 'gravityforms-eway');
			}
			else {
				if ($this->cardExpiryYear > 100 && $this->cardExpiryYear < $thisYear) {
					$errors[] = __("card expiry can't be in the past", 'gravityforms-eway');
				}
				else if ($this->cardExpiryYear < 100 && $this->cardExpiryYear < ($thisYear - 2000)) {
					$errors[] = __("card expiry can't be in the past", 'gravityforms-eway');
				}
			}
		}

		if (count($errors) > 0) {
			throw new GFEwayException(implode("\n", $errors));
		}
	}

	/**
	* create JSON request document for payment
	* @return string
	*/
	public function getPayment() {
		$request = new stdClass();

		$request->Customer				= $this->getCustomerRecord();
		$request->Payment				= $this->getPaymentRecord();
		$request->TransactionType		= self::TRANS_PURCHASE;
		$request->PartnerID				= self::PARTNER_ID;
		$request->Method				= $this->capture ? self::METHOD_PAYMENT : self::METHOD_AUTHORISE;
		$request->CustomerIP			= $this->customerIP;

		if (!empty($this->options)) {
			$request->Options			= $this->getOptionsRecord();
		}

		if (!empty($this->deviceID)) {
			$request->DeviceID 			= substr($this->deviceID, 0, 50);
		}

		return wp_json_encode($request);
	}

	/**
	* build Customer record for request
	* @return stdClass
	*/
	protected function getCustomerRecord() {
		$record = new stdClass;

		$record->Title				= $this->title ? substr(self::sanitiseCustomerTitle($this->title), 0, 5) : '';
		$record->FirstName			= $this->firstName ? substr($this->firstName, 0, 50) : '';
		$record->LastName			= $this->lastName ? substr($this->lastName, 0, 50) : '';
		$record->Street1			= $this->address1 ? substr($this->address1, 0, 50) : '';
		$record->Street2			= $this->address2 ? substr($this->address2, 0, 50) : '';
		$record->City				= $this->suburb ? substr($this->suburb, 0, 50) : '';
		$record->State				= $this->state ? substr($this->state, 0, 50) : '';
		$record->PostalCode			= $this->postcode ? substr($this->postcode, 0, 30) : '';
		$record->Country			= $this->country ? strtolower($this->country) : '';
		$record->Email				= $this->emailAddress ? substr($this->emailAddress, 0, 50) : '';
		$record->CardDetails		= $this->getCardDetailsRecord();

		if (!empty($this->companyName)) {
			$record->CompanyName	= substr($this->companyName, 0, 50);
		}

		if (!empty($this->jobDescription)) {
			$record->JobDescription	= substr($this->jobDescription, 0, 50);
		}

		if (!empty($this->phone)) {
			$record->Phone			= substr($this->phone, 0, 32);
		}

		if (!empty($this->mobile)) {
			$record->Mobile			= substr($this->mobile, 0, 32);
		}

		if (!empty($this->fax)) {
			$record->Fax			= substr($this->fax, 0, 32);
		}

		if (!empty($this->website)) {
			$record->Url			= substr($this->website, 0, 512);
		}

		if (!empty($this->comments)) {
			$record->Comments		= substr($this->comments, 0, 255);
		}

		return $record;
	}

	/**
	* build CardDetails record for request
	* NB: TODO: does not currently handle StartMonth, StartYear, IssueNumber (used in UK)
	* NB: card number and CVN can be very lengthy encrypted values
	* @return stdClass
	*/
	protected function getCardDetailsRecord() {
		$record = new stdClass;

		$record->Name				= $this->cardHoldersName ? substr($this->cardHoldersName, 0, 50) : '';
		$record->Number				= $this->cardNumber ? $this->cardNumber : '';
		$record->ExpiryMonth		= sprintf('%02d', $this->cardExpiryMonth);
		$record->ExpiryYear			= sprintf('%02d', $this->cardExpiryYear % 100);
		$record->CVN				= $this->cardVerificationNumber ? $this->cardVerificationNumber : '';

		return $record;
	}

	/**
	* build Payment record for request
	* @return stdClass
	*/
	protected function getPaymentRecord() {
		$record = new stdClass;

		$record->TotalAmount		= self::formatCurrency($this->amount, $this->currencyCode);
		$record->InvoiceNumber		= $this->transactionNumber ? substr($this->transactionNumber, 0, 12) : '';
		$record->InvoiceDescription	= $this->invoiceDescription ? substr($this->invoiceDescription, 0, 64) : '';
		$record->InvoiceReference	= $this->invoiceReference ? substr($this->invoiceReference, 0, 50) : '';
		$record->CurrencyCode		= $this->currencyCode ? substr($this->currencyCode, 0, 3) : '';

		return $record;
	}

	/**
	* build Options record for request
	* @return array
	*/
	protected function getOptionsRecord() {
		$options = [];

		foreach ($this->options as $option) {
			if (!empty($option)) {
				$options[] = ['Value' => substr($option, 0, 254)];
			}
		}

		return $options;
	}

	/**
	* send the Eway payment request and retrieve and parse the response
	* @param string $request Eway payment request as a JSON document, per Eway specifications
	* @return GFEwayRapidAPIResponse
	* @throws GFEwayException
	*/
	protected function sendPaymentDirect($request) {
		// select host and endpoint
		$host		= $this->useSandbox ? self::API_HOST_SANDBOX : self::API_HOST_LIVE;
		$endpoint	= self::API_DIRECT_PAYMENT;
		$url		= "$host/$endpoint";

		// execute the request, and retrieve the response
		$response = wp_remote_post($url, [
			'user-agent'	=> 'Gravity Forms Eway ' . GFEWAY_PLUGIN_VERSION,
			'sslverify'		=> $this->sslVerifyPeer,
			'timeout'		=> 60,
			'headers'		=> [
									'Content-Type'		=> 'application/json',
									'Authorization'		=> 'Basic ' . base64_encode("{$this->apiKey}:{$this->apiPassword}"),
							],
			'body'			=> $request,
		]);

		// failure to handle the http request
		if (is_wp_error($response)) {
			$msg = $response->get_error_message();
			throw new GFEwayException(sprintf(__('Error posting Eway request: %s', 'gravityforms-eway'), $msg));
		}

		// error code returned by request
		$code = wp_remote_retrieve_response_code($response);
		if ($code !== 200) {
			$msg = wp_remote_retrieve_response_message($response);

			if (empty($msg)) {
				$msg = sprintf(__('Error posting Eway request: %s', 'gravityforms-eway'), $code);
			}
			else {
				/* translators: 1. the error code; 2. the error message */
				$msg = sprintf(__('Error posting Eway request: %1$s, %2$s', 'gravityforms-eway'), $code, $msg);
			}
			throw new GFEwayException($msg);
		}

		$responseJSON = wp_remote_retrieve_body($response);

		$response = new GFEwayRapidAPIResponse();
		$response->loadResponse($responseJSON);
		return $response;
	}

	/**
	* format amount per currency
	* @param float $amount
	* @param string $currencyCode
	* @return string
	*/
	protected static function formatCurrency($amount, $currencyCode) {
		switch ($currencyCode) {

			// Japanese Yen already has no decimal fraction
			case 'JPY':
				$value = number_format($amount, 0, '', '');
				break;

			default:
				$value = number_format($amount * 100, 0, '', '');
				break;

		}

		return $value;
	}

	/**
	* sanitise the customer title, to avoid error V6058: Invalid Customer Title
	* @param string $title
	* @return string
	*/
	protected static function sanitiseCustomerTitle($title) {
		$valid = [
			'mr'			=> 'Mr.',
			'master'		=> 'Mr.',
			'ms'			=> 'Ms.',
			'mrs'			=> 'Mrs.',
			'missus'		=> 'Mrs.',
			'miss'			=> 'Miss',
			'dr'			=> 'Dr.',
			'doctor'		=> 'Dr.',
			'sir'			=> 'Sir',
			'prof'			=> 'Prof.',
			'professor'		=> 'Prof.',
		];

		$simple = rtrim(strtolower(trim($title)), '.');

		return isset($valid[$simple]) ? $valid[$simple] : '';
	}

}
