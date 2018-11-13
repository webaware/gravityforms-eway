<?php
/**
* Classes for dealing with eWAY stored payments
*
* NB: for testing, the only account number recognised is '87654321' and the only card number seen as valid is '4444333322221111'
*/

if (!defined('ABSPATH')) {
	exit;
}

/**
* Class for dealing with an eWAY stored payment request
*/
class GFEwayStoredPayment {

	#region members

	// environment / website specific members
	/**
	* NB: Stored Payments use the Direct Payments sandbox; there is no Stored Payments sandbox
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
	* account name / email address at eWAY
	* @var string max. 8 characters
	*/
	public $accountID;

	/**
	* an invoice reference to track by (NB: see transactionNumber which is intended for invoice number or similar)
	* @var string max. 50 characters
	*/
	public $invoiceReference;

	/**
	* description of what is being purchased / paid for
	* @var string max. 10000 characters
	*/
	public $invoiceDescription;

	/**
	* total amount of payment, in dollars and cents as a floating-point number (will be converted to just cents for transmission)
	* @var float
	*/
	public $amount;

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
	* @var string max. 50 characters
	*/
	public $address1;

	/**
	* customer's address line 2
	* @var string max. 50 characters
	*/
	public $address2;

	/**
	* customer's postcode
	* @var string max. 6 characters
	*/
	public $postcode;

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
	* country name
	* @var string
	*/
	public $countryName;

	/**
	* country code for billing address
	* @var string 2 characters
	*/
	public $country;

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
	* CVN (Creditcard Verification Number) for verifying physical card is held by buyer
	* NB: this is ignored for Stored Payments!
	* @var string max. 3 or 4 characters (depends on type of card)
	*/
	public $cardVerificationNumber;

	/**
	* eWAYTrxnNumber - This value is returned to your website.
	*
	* You can pass a unique transaction number from your site. You can update and track the status of a transaction when eWAY
	* returns to your site.
	*
	* NB. This number is returned as 'ewayTrxnReference', member transactionReference of GFEwayStoredResponse.
	*
	* @var string max. 16 characters
	*/
	public $transactionNumber;

	/**
	* optional additional information for use in shopping carts, etc.
	* @var array[string] max. 255 characters, up to 3 elements
	*/
	public $options = array();

	#endregion

	#region constants

	/** host for the eWAY Real Time API in the developer sandbox environment */
	const REALTIME_API_SANDBOX = 'https://www.eway.com.au/gateway/xmltest/testpage.asp';
	/** host for the eWAY Real Time API in the production environment */
	const REALTIME_API_LIVE = 'https://www.eway.com.au/gateway/xmlstored.asp';

	#endregion

	/**
	* populate members with defaults, and set account and environment information
	*
	* @param string $accountID eWAY account ID
	* @param boolean $isLiveSite running on the live (production) website
	*/
	public function __construct($accountID, $isLiveSite = false) {
		$this->sslVerifyPeer	= true;
		$this->isLiveSite		= $isLiveSite;
		$this->accountID		= $accountID;
	}

	/**
	* process a payment against eWAY; throws exception on error with error described in exception message.
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
		$errors = array();

		if (strlen($this->accountID) === 0) {
			$errors[] = __('CustomerID cannot be empty', 'gravityforms-eway');
		}
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
	* create XML request document for payment parameters
	*
	* @return string
	*/
	public function getPaymentXML() {
		// aggregate street, city, state, country into a single string
		$parts = array($this->address1, $this->address2, $this->suburb, $this->state, $this->countryName);
		$address = implode(', ', array_filter($parts, 'strlen'));

		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');
		$xml->startElement('ewaygateway');

		$xml->writeElement('ewayCustomerID', $this->accountID);
		$xml->writeElement('ewayTotalAmount', number_format($this->amount * 100, 0, '', ''));
		$xml->writeElement('ewayCustomerFirstName', $this->firstName);
		$xml->writeElement('ewayCustomerLastName', $this->lastName);
		$xml->writeElement('ewayCustomerEmail', $this->emailAddress);
		$xml->writeElement('ewayCustomerAddress', $address);
		$xml->writeElement('ewayCustomerPostcode', $this->postcode);
		$xml->writeElement('ewayCustomerInvoiceDescription', $this->invoiceDescription);
		$xml->writeElement('ewayCustomerInvoiceRef', $this->invoiceReference);
		$xml->writeElement('ewayCardHoldersName', $this->cardHoldersName);
		$xml->writeElement('ewayCardNumber', $this->cardNumber);
		$xml->writeElement('ewayCardExpiryMonth', sprintf('%02d', $this->cardExpiryMonth));
		$xml->writeElement('ewayCardExpiryYear', sprintf('%02d', $this->cardExpiryYear % 100));
		$xml->writeElement('ewayTrxnNumber', $this->transactionNumber);
		$xml->writeElement('ewayOption1', empty($this->option[0]) ? '' : $this->option[0]);
		$xml->writeElement('ewayOption2', empty($this->option[1]) ? '' : $this->option[1]);
		$xml->writeElement('ewayOption3', empty($this->option[2]) ? '' : $this->option[2]);

		$xml->endElement();		// ewaygateway

		return $xml->outputMemory();
	}

	/**
	* send the eWAY payment request and retrieve and parse the response
	* @return GFEwayStoredResponse
	* @param string $xml eWAY payment request as an XML document, per eWAY specifications
	*/
	private function sendPayment($xml) {
		// use sandbox if not from live website
		$url = $this->isLiveSite ? self::REALTIME_API_LIVE : self::REALTIME_API_SANDBOX;

		// execute the cURL request, and retrieve the response
		try {
			$responseXML = GFEwayPlugin::curlSendRequest($url, $xml, $this->sslVerifyPeer);
		}
		catch (GFEwayCurlException $e) {
			throw new GFEwayException(sprintf(__('Error posting eWAY payment to %1$s: %2$s', 'gravityforms-eway'), $url, $e->getMessage()));
		}

		$response = new GFEwayStoredResponse();
		$response->loadResponseXML($responseXML);
		return $response;
	}

}

/**
* Class for dealing with an eWAY stored payment response
*/
class GFEwayStoredResponse {

	#region members

	/**
	* bank authorisation code
	* @var string
	*/
	public $AuthorisationCode;

	/**
	* array of codes describing the result (including Beagle failure codes)
	* @var array
	*/
	public $ResponseMessage;

	/**
	* eWAY transacation ID
	* @var string
	*/
	public $TransactionID;

	/**
	* eWAY transaction status: true for success
	* @var boolean
	*/
	public $TransactionStatus;

	/**
	* Beagle fraud detection score
	* @var string
	*/
	public $BeagleScore;

	/**
	* payment details object
	* @var object
	*/
	public $Payment;

	/**
	* a list of errors -- just the one for the Direct API
	* @var
	*/
	public $Errors;

	#endregion

	/**
	* load eWAY response data as XML string
	*
	* @param string $response eWAY response as a string (hopefully of XML data)
	*/
	public function loadResponseXML($response) {
		GFEwayPlugin::log_debug(sprintf('%s: eWAY says "%s"', __METHOD__, $response));

		// make sure we actually got something from eWAY
		if (strlen($response) === 0) {
			throw new GFEwayException(__('eWAY payment request returned nothing; please check your card details', 'gravityforms-eway'));
		}

		// prevent XML injection attacks, and handle errors without warnings
		$oldDisableEntityLoader = libxml_disable_entity_loader(true);
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

			$this->AuthorisationCode			= (string) $xml->ewayAuthCode;
			$this->ResponseMessage				= array();
			$this->TransactionStatus			= (strcasecmp((string) $xml->ewayTrxnStatus, 'true') === 0);
			$this->TransactionID				= (string) $xml->ewayTrxnNumber;
			$this->BeagleScore					= '';		// Stored Payment Legacy XML API does not support Beagle
			$this->Errors						= array('ERROR' => (string) $xml->ewayTrxnError);

			// if we got an amount, convert it back into dollars.cents from just cents
			$this->Payment						= new stdClass;
			$this->Payment->TotalAmount			= empty($xml->ewayReturnAmount) ? null : floatval($xml->ewayReturnAmount) / 100.0;
			$this->Payment->InvoiceReference	= (string) $xml->ewayTrxnReference;

			// restore old libxml settings
			libxml_disable_entity_loader($oldDisableEntityLoader);
			libxml_use_internal_errors($oldUseInternalErrors);
		}
		catch (Exception $e) {
			// restore old libxml settings
			libxml_disable_entity_loader($oldDisableEntityLoader);
			libxml_use_internal_errors($oldUseInternalErrors);

			throw new GFEwayException(sprintf(__('Error parsing eWAY response: %s', 'gravityforms-eway'), $e->getMessage()));
		}
	}

}
