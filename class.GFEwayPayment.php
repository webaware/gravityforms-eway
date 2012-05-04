<?php
/**
* Class for dealing with an eWAY payment
*
* NB: for testing, the only account number recognised is '87654321' and the only card number seen as valid is '4444333322221111'
*
* copyright (c) 2008-2012 WebAware Pty Ltd, released under GPL v2.1
*/
class GFEwayPayment {
	// environment / website specific members
	/**
	* default FALSE, use eWAY sandbox unless set to TRUE
	* @var boolean
	*/
	public $isLiveSite;

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
	* customer's address, including state, city and country
	* @var string max. 255 characters
	*/
	public $address;

	/**
	* customer's postcode
	* @var string max. 6 characters
	*/
	public $postcode;

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
	* @var string max. 3 or 4 characters (depends on type of card)
	*/
	public $cardVerificationNumber;

	/**
	* eWAYTrxnNumber - This value is returned to your website.
	*
	* You can pass a unique transaction number from your site. You can update and track the status of a transaction when eWAY
	* returns to your site.
	*
	* NB. This number is returned as 'ewayTrxnReference', member transactionReference of GFEwayResponse.
	*
	* @var string max. 16 characters
	*/
	public $transactionNumber;

	/**
	* optional additional information for use in shopping carts, etc.
	* @var string max. 255 characters
	*/
	public $option1;

	/**
	* optional additional information for use in shopping carts, etc.
	* @var string max. 255 characters
	*/
	public $option2;

	/**
	* optional additional information for use in shopping carts, etc.
	* @var string max. 255 characters
	*/
	public $option3;

	/** host for the eWAY Real Time API in the developer sandbox environment */
	const REALTIME_API_SANDBOX = "https://www.eway.com.au/gateway/xmltest/testpage.asp";
	/** host for the eWAY Real Time API in the production environment */
	const REALTIME_API_LIVE = "https://www.eway.com.au/gateway/xmlpayment.asp";
	/** host for the eWAY Real Time API with CVN verification in the developer sandbox environment */
	const REALTIME_CVN_API_SANDBOX = "https://www.eway.com.au/gateway_cvn/xmltest/testpage.asp";
	/** host for the eWAY Real Time API with CVN verification in the production environment */
	const REALTIME_CVN_API_LIVE = "https://www.eway.com.au/gateway_cvn/xmlpayment.asp";

	/**
	* populate members with defaults, and set account and environment information
	*
	* @param string $accountID eWAY account ID
	* @param boolean $isLiveSite running on the live (production) website
	*/
	public function __construct($accountID, $isLiveSite = FALSE) {
		$this->isLiveSite = $isLiveSite;
		$this->accountID = $accountID;
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
		$errmsg = '';

		if (strlen($this->accountID) === 0)
			$errmsg .= "accountID cannot be empty.\n";
		if (!is_numeric($this->amount) || $this->amount <= 0)
			$errmsg .= "amount must be given as a number in dollars and cents.\n";
		else if (!is_float($this->amount))
			$this->amount = (float) $this->amount;
		if (strlen($this->cardHoldersName) === 0)
			$errmsg .= "card holder's name cannot be empty.\n";
		if (strlen($this->cardNumber) === 0)
			$errmsg .= "card number cannot be empty.\n";

		// make sure that card expiry month is a number from 1 to 12
		if (gettype($this->cardExpiryMonth) != 'integer') {
			if (strlen($this->cardExpiryMonth) === 0)
				$errmsg .= "card expiry month cannot be empty.\n";
			else if (!is_numeric($this->cardExpiryMonth))
				$errmsg .= "card expiry month must be a number between 1 and 12.\n";
			else
				$this->cardExpiryMonth = intval($this->cardExpiryMonth);
		}
		if (gettype($this->cardExpiryMonth) == 'integer') {
			if ($this->cardExpiryMonth < 1 || $this->cardExpiryMonth > 12)
				$errmsg .= "card expiry month must be a number between 1 and 12.\n";
		}

		// make sure that card expiry year is a 2-digit or 4-digit year >= this year
		if (gettype($this->cardExpiryYear) != 'integer') {
			if (strlen($this->cardExpiryYear) === 0)
				$errmsg .= "card expiry year cannot be empty.\n";
			else if (!preg_match('/^\d\d(\d\d)?$/', $this->cardExpiryYear))
				$errmsg .= "card expiry year must be a two or four digit year.\n";
			else
				$this->cardExpiryYear = intval($this->cardExpiryYear);
		}
		if (gettype($this->cardExpiryYear) == 'integer') {
			$thisYear = intval(date_create()->format('Y'));
			if ($this->cardExpiryYear < 0 || $this->cardExpiryYear >= 100 && $this->cardExpiryYear < 2000 || $this->cardExpiryYear > $thisYear + 20)
				$errmsg .= "card expiry year must be a two or four digit year.\n";
			else {
				if ($this->cardExpiryYear > 100 && $this->cardExpiryYear < $thisYear)
					$errmsg .= "card expiry year can't be in the past.\n";
				else if ($this->cardExpiryYear < 100 && $this->cardExpiryYear < ($thisYear - 2000))
					$errmsg .= "card expiry year can't be in the past.\n";
			}
		}

		if (strlen($errmsg) > 0)
			throw new Exception($errmsg);
	}

	/**
	* create XML request document for payment parameters
	*
	* @return string
	*/
	private function getPaymentXML() {
		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');
		$xml->startElement('ewaygateway');

		$xml->writeElement('ewayCustomerID', $this->accountID);
		$xml->writeElement('ewayCardHoldersName', $this->cardHoldersName);
		$xml->writeElement('ewayCardNumber', $this->cardNumber);
		$xml->writeElement('ewayCardExpiryMonth', sprintf('%02d', $this->cardExpiryMonth));
		$xml->writeElement('ewayCardExpiryYear', sprintf('%02d', $this->cardExpiryYear % 100));
		$xml->writeElement('ewayTotalAmount', number_format($this->amount * 100, 0, '', ''));
		$xml->writeElement('ewayTrxnNumber', $this->transactionNumber);
		$xml->writeElement('ewayCustomerInvoiceRef', $this->invoiceReference);

		// optional data
		$xml->writeElement('ewayCustomerFirstName', $this->firstName);
		$xml->writeElement('ewayCustomerLastName', $this->lastName);
		$xml->writeElement('ewayCustomerEmail', $this->emailAddress);
		$xml->writeElement('ewayCustomerAddress', $this->address);
		$xml->writeElement('ewayCustomerPostcode', $this->postcode);
		$xml->writeElement('ewayCustomerInvoiceDescription', $this->invoiceDescription);
		$xml->writeElement('ewayCVN', $this->cardVerificationNumber);
		$xml->writeElement('ewayOption1', $this->option1);
		$xml->writeElement('ewayOption2', $this->option2);
		$xml->writeElement('ewayOption3', $this->option3);

		$xml->endElement();		// ewaygateway

		return $xml->outputMemory();
	}

	/**
	* send the eWAY payment request and retrieve and parse the response
	*
	* @return GFEwayResponse
	* @param string $xml eWAY payment request as an XML document, per eWAY specifications
	*/
	private function sendPayment($xml) {
		// use sandbox if not from live website, and use CVN API if we have a card verification number
		if (empty($this->cardVerificationNumber))
			$url = $this->isLiveSite ? self::REALTIME_API_LIVE : self::REALTIME_API_SANDBOX;
		else
			$url = $this->isLiveSite ? self::REALTIME_CVN_API_LIVE : self::REALTIME_CVN_API_SANDBOX;

		// build a cURL request
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_USERAGENT, 'PHP/' . phpversion());
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);		// don't validate the certificate
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);			// do verify the hostname
//		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);			// do not verify the hostname

		// execute the cURL request, and retrieve the response
		$responseXML = @curl_exec($curl);
		if (curl_errno($curl)) {
			$errmsg = "Error posting eWAY payment to $url: " . curl_error($curl);
			curl_close($curl);
			throw new Exception($errmsg);
		}

		curl_close($curl);

		$response = new GFEwayResponse();
		$response->loadResponseXML($responseXML);
		return $response;
	}
}
