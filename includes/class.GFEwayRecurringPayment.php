<?php

use function webaware\gfeway\send_xml_request;

/**
* Classes for dealing with Eway recurring payments
*/

if (!defined('ABSPATH')) {
	exit;
}

/**
* Class for dealing with an Eway recurring payment request
*
* @link https://www.eway.com.au/eway-partner-portal/resources/eway-api/recurring-payments
*/
class GFEwayRecurringPayment {

	#region members

	// environment / website specific members
	/**
	* default FALSE, use Eway sandbox unless set to TRUE
	* @var boolean
	*/
	public $isLiveSite;

	/**
	* default TRUE, whether to validate the remote SSL certificate
	* @var boolean
	*/
	public $sslVerifyPeer;

	// payment specific members
	/**
	* account name / email address at Eway
	* @var string max. 8 characters
	*/
	public $accountID;

	/**
	* customer's title
	* @var string max. 20 characters
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
	* customer's email address
	* @var string max. 50 characters
	*/
	public $emailAddress;

	/**
	* customer's address line 1
	* @var string (combined max. 255 for line1 + line2)
	*/
	public $address1;

	/**
	* customer's address line 2
	* @var string
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
	* @var string max. 6 characters
	*/
	public $postcode;

	/**
	* customer's country
	* @var string max. 50 characters
	*/
	public $country;

	/**
	* customer's phone number
	* @var string max. 20 characters
	*/
	public $phone;

	/**
	* customer's comments
	* @var string max. 255 characters
	*/
	public $customerComments;

	/**
	* an customer reference to track by (NB: see also invoiceReference)
	* @var string max. 20 characters
	*/
	public $customerReference;

	/**
	* an invoice reference to track by
	* @var string max. 50 characters
	*/
	public $invoiceReference;

	/**
	* description of what is being purchased / paid for
	* @var string max. 10000 characters
	*/
	public $invoiceDescription;

	/**
	* name on credit card
	* @var string max. 50 characters
	*/
	public $cardHoldersName;

	/**
	* credit card number, with no spaces
	* @var string max. 20 characters
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
	* total amount of intial payment, in dollars and cents as a floating-point number (will be converted to just cents for transmission)
	* may be 0 (i.e. nothing upfront, only on recurring billings)
	* @var float
	*/
	public $amountInit;

	/**
	* total amount of recurring payment, in dollars and cents as a floating-point number (will be converted to just cents for transmission)
	* @var float
	*/
	public $amountRecur;

	/**
	* the date of the initial payment (e.g. today, when the customer signed up)
	* @var DateTime
	*/
	public $dateInit;

	/**
	* the date of the first recurring payment
	* @var DateTime
	*/
	public $dateStart;

	/**
	* the date of the last recurring payment
	* @var DateTime
	*/
	public $dateEnd;

	/**
	* size of the interval between recurring payments (be it days, months, years, etc.) in range 1-31
	* @var integer
	*/
	public $intervalSize;

	/**
	* type of interval (see interval type constants below)
	* @var integer
	*/
	public $intervalType;

	#endregion

	#region constants

	/** interval type Days */
	const DAYS   = 1;
	/** interval type Weeks */
	const WEEKS  = 2;
	/** interval type Months */
	const MONTHS = 3;
	/** interval type Years */
	const YEARS  = 4;

	/** host for the Eway Real Time API in the developer sandbox environment */
	const REALTIME_API_SANDBOX = 'https://www.eway.com.au/gateway/rebill/test/upload_test.aspx';
	/** host for the Eway Real Time API in the production environment */
	const REALTIME_API_LIVE    = 'https://www.eway.com.au/gateway/rebill/upload.aspx';

	#endregion

	/**
	* populate members with defaults, and set account and environment information
	*
	* @param string $accountID Eway account ID
	* @param boolean $isLiveSite running on the live (production) website
	*/
	public function __construct($accountID, $isLiveSite = false) {
		$this->sslVerifyPeer = true;
		$this->isLiveSite = $isLiveSite;
		$this->accountID = $accountID;
	}

	/**
	* process a payment against Eway; throws exception on error with error described in exception message.
	*/
	public function processPayment() {
		$this->validate();
		$xml = $this->getPaymentXML();
		return $this->sendPayment($xml);
	}

	/**
	* validate the data members to ensure that sufficient and valid information has been given
	*/
	private function validate() {
		$errors = [];

		if (strlen($this->accountID) === 0) {
			$errors[] = __('CustomerID cannot be empty', 'gravityforms-eway');
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

		// ensure that amounts are numeric and positive, and that recurring amount > 0
		if (!is_numeric($this->amountInit) || $this->amountInit < 0) {	// NB: initial amount can be 0
			$errors[] = __('initial amount must be given as a number in dollars and cents, or 0', 'gravityforms-eway');
		}
		elseif (!is_float($this->amountInit)) {
			$this->amountInit = (float) $this->amountInit;
		}
		if (!is_numeric($this->amountRecur) || $this->amountRecur <= 0) {
			$errors[] = __('recurring amount must be given as a number in dollars and cents', 'gravityforms-eway');
		}
		elseif (!is_float($this->amountRecur)) {
			$this->amountRecur = (float) $this->amountRecur;
		}

		// ensure that interval is numeric and within range, and interval type is valid
		if (!is_numeric($this->intervalSize) || $this->intervalSize < 1 || $this->intervalSize > 31) {
			$errors[] = __('interval must be numeric and between 1 and 31', 'gravityforms-eway');
		}
		if (!is_numeric($this->intervalType) || !in_array(intval($this->intervalType), [self::DAYS, self::WEEKS, self::MONTHS, self::YEARS])) {
			$errors[] = __('interval type is invalid', 'gravityforms-eway');
		}

		// ensure that dates are DateTime objects
		if (empty($this->dateInit)) {
			$this->dateInit = date_create();
		}
		if (!(is_object($this->dateInit) && get_class($this->dateInit) === 'DateTime')) {
			$errors[] = __('initial payment date must be a date', 'gravityforms-eway');
		}
		if (!(is_object($this->dateStart) && get_class($this->dateStart) === 'DateTime')) {
			$errors[] = __('recurring payment start date must be a date', 'gravityforms-eway');
		}
		if (!(is_object($this->dateEnd) && get_class($this->dateEnd) === 'DateTime')) {
			$errors[] = __('recurring payment end date must be a date', 'gravityforms-eway');
		}

		if (count($errors) > 0) {
			throw new GFEwayException(implode("\n", $errors));
		}
	}

	/**
	* create XML request document for payment parameters
	*
	* @return string
	*/
	public function getPaymentXML() {
		// aggregate street address1 & address2 into one string
		$parts = [$this->address1, $this->address2];
		$address = implode(', ', array_filter($parts, 'strlen'));

		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');
		$xml->startElement('RebillUpload');
		$xml->startElement('NewRebill');
		$xml->writeElement('eWayCustomerID', $this->accountID);

		// customer data
		$xml->startElement('Customer');
		$xml->writeElement('CustomerRef', $this->customerReference);	// req?
		$xml->writeElement('CustomerTitle', $this->title);
		$xml->writeElement('CustomerFirstName', $this->firstName);		// req
		$xml->writeElement('CustomerLastName', $this->lastName);		// req
		$xml->writeElement('CustomerCompany', '');
		$xml->writeElement('CustomerJobDesc', '');
		$xml->writeElement('CustomerEmail', $this->emailAddress);
		$xml->writeElement('CustomerAddress', $address);
		$xml->writeElement('CustomerSuburb', $this->suburb);
		$xml->writeElement('CustomerState', $this->state);				// req
		$xml->writeElement('CustomerPostCode', $this->postcode);		// req
		$xml->writeElement('CustomerCountry', $this->country);			// req
		$xml->writeElement('CustomerPhone1', self::cleanPhone($this->phone));
		$xml->writeElement('CustomerPhone2', '');
		$xml->writeElement('CustomerFax', '');
		$xml->writeElement('CustomerURL', '');
		$xml->writeElement('CustomerComments', $this->customerComments);
		$xml->endElement();		// Customer

		// billing data
		$xml->startElement('RebillEvent');
		$xml->writeElement('RebillInvRef', $this->invoiceReference);
		$xml->writeElement('RebillInvDesc', $this->invoiceDescription);
		$xml->writeElement('RebillCCName', $this->cardHoldersName);
		$xml->writeElement('RebillCCNumber', $this->cardNumber);
		$xml->writeElement('RebillCCExpMonth', sprintf('%02d', $this->cardExpiryMonth));
		$xml->writeElement('RebillCCExpYear', sprintf('%02d', $this->cardExpiryYear % 100));
		$xml->writeElement('RebillInitAmt', number_format($this->amountInit * 100, 0, '', ''));
		$xml->writeElement('RebillInitDate', $this->dateInit->format('d/m/Y'));
		$xml->writeElement('RebillRecurAmt', number_format($this->amountRecur * 100, 0, '', ''));
		$xml->writeElement('RebillStartDate', $this->dateStart->format('d/m/Y'));
		$xml->writeElement('RebillInterval', $this->intervalSize);
		$xml->writeElement('RebillIntervalType', $this->intervalType);
		$xml->writeElement('RebillEndDate', $this->dateEnd->format('d/m/Y'));
		$xml->endElement();		// RebillEvent

		$xml->endElement();		// NewRebill
		$xml->endElement();		// RebillUpload

		return $xml->outputMemory();
	}

	/**
	* clean phone number field value for legacy XML API
	* @param string $phone
	* @return string
	*/
	protected static function cleanPhone($phone) {
		return preg_replace('#[^0-9 +-]#', '', $phone);
	}

	/**
	* send the Eway payment request and retrieve and parse the response
	* @return GFEwayRecurringResponse
	* @param string $xml Eway payment request as an XML document, per Eway specifications
	*/
	private function sendPayment($xml) {
		// use sandbox if not from live website
		$url = $this->isLiveSite ? self::REALTIME_API_LIVE : self::REALTIME_API_SANDBOX;

		// execute the cURL request, and retrieve the response
		try {
			$responseXML = send_xml_request($url, $xml, $this->sslVerifyPeer);
		}
		catch (GFEwayCurlException $e) {
			throw new GFEwayException(sprintf(__('Error posting Eway recurring payment to %1$s: %2$s', 'gravityforms-eway'), $url, $e->getMessage()));
		}

		$response = new GFEwayRecurringResponse();
		$response->loadResponseXML($responseXML);
		return $response;
	}

}

/**
* Class for dealing with an Eway recurring payment response
*/
class GFEwayRecurringResponse {

	#region members

	/**
	* For a successful transaction "True" is passed and for a failed transaction "False" is passed.
	* @var boolean
	*/
	public $status;

	/**
	* the error severity, either Error or Warning
	* @var string max. 16 characters
	*/
	public $errorType;

	/**
	* the error response returned by the bank
	* @var string max. 255 characters
	*/
	public $error;

	#endregion

	/**
	* load Eway response data as XML string
	*
	* @param string $response Eway response as a string (hopefully of XML data)
	*/
	public function loadResponseXML($response) {
		GFEwayPlugin::log_debug(sprintf('%s: Eway says "%s"', __METHOD__, $response));

		// make sure we actually got something from Eway
		if (strlen($response) === 0) {
			throw new GFEwayException(__('Eway payment request returned nothing; please check your card details', 'gravityforms-eway'));
		}

		// prevent XML injection attacks, and handle errors without warnings
		$oldDisableEntityLoader = PHP_VERSION_ID >= 80000 ? true : libxml_disable_entity_loader(true);
		$oldUseInternalErrors = libxml_use_internal_errors(true);

		try {
			$xml = simplexml_load_string($response);
			if ($xml === false) {
				$errmsg = '';
				foreach (libxml_get_errors() as $error) {
					$errmsg .= $error->message;
				}
				throw new Exception($errmsg);
			}

			$this->status = (strcasecmp((string) $xml->Result, 'success') === 0);
			$this->errorType = (string) $xml->ErrorSeverity;
			$this->error = (string) $xml->ErrorDetails;

			// restore old libxml settings
			if (!$oldDisableEntityLoader) {
				libxml_disable_entity_loader($oldDisableEntityLoader);
			}
			libxml_use_internal_errors($oldUseInternalErrors);
		}
		catch (Exception $e) {
			// restore old libxml settings
			if (!$oldDisableEntityLoader) {
				libxml_disable_entity_loader($oldDisableEntityLoader);
			}
			libxml_use_internal_errors($oldUseInternalErrors);

			throw new GFEwayException(sprintf(__('Error parsing Eway recurring payments response: %s', 'gravityforms-eway'), $e->getMessage()));
		}
	}

}
