<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* Class for dealing with an eWAY Rapid API response
* @link https://eway.io/api-v3/
*/
class GFEwayRapidAPIResponse {

	#region members

	/**
	* bank authorisation code
	* @var string
	*/
	public $AuthorisationCode;

	/**
	* 2-digit bank response code
	* @var string
	*/
	public $ResponseCode;

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
	* eWAY transaction type
	* @var string
	*/
	public $TransactionType;

	/**
	* Beagle fraud detection score
	* @var string
	*/
	public $BeagleScore;

	/**
	* verification results object
	* @var object
	*/
	public $Verification;

	/**
	* customer details object (includes card details object)
	* @var object
	*/
	public $Customer;

	/**
	* payment details object
	* @var object
	*/
	public $Payment;

	/**
	* a list of errors
	* @var array
	*/
	public $Errors;

	#endregion

	/**
	* load eWAY response data as JSON string
	* @param string $json eWAY response as a string (hopefully of JSON data)
	*/
	public function loadResponse($json) {
		$response = json_decode($json);

		if (is_null($response)) {
			$errmsg = __('invalid response from eWAY for Direct payment', 'gravityforms-eway');
			throw new GFEwayException($errmsg);
		}

		foreach (get_object_vars($response) as $name => $value) {
			if (property_exists($this, $name)) {
				switch ($name) {

					case 'ResponseMessage':
					case 'Errors':
						$this->$name = $this->getResponseDetails($value);
						break;

					default:
						$this->$name = $value;
						break;

				}
			}
		}

		// if we got an amount, convert it back into dollars.cents from just cents
		if (isset($this->Payment) && !empty($this->Payment->TotalAmount)) {
			$this->Payment->TotalAmount = floatval($this->Payment->TotalAmount) / 100.0;
		}
	}

	/**
	* separate response codes into individual errors
	* @param string $codes
	* @return array
	*/
	protected function getResponseDetails($codes) {
		$responses = array();

		if (!empty($codes)) {
			foreach (explode(',', $codes) as $code) {
				$code = trim($code);
				$responses[$code] = $this->getCodeDescription($code);
			}
		}

		return $responses;
	}

	/**
	* get description for response code
	* @param string $code
	* @return string
	*/
	protected function getCodeDescription($code) {
		static $messages = false;

		if ($messages === false) {
			// source @link https://github.com/eWAYPayment/eway-rapid-php/blob/master/resource/lang/en.ini
			// NB: translated into en_US for consistency with base locale
			$messages = array(
				'A2000' => _x('%s: Transaction Approved', 'eWAY coded response', 'gravityforms-eway'),
				'A2008' => _x('%s: Honor With Identification', 'eWAY coded response', 'gravityforms-eway'),
				'A2010' => _x('%s: Approved For Partial Amount', 'eWAY coded response', 'gravityforms-eway'),
				'A2011' => _x('%s: Approved, VIP', 'eWAY coded response', 'gravityforms-eway'),
				'A2016' => _x('%s: Approved, Update Track 3', 'eWAY coded response', 'gravityforms-eway'),
				'D4401' => _x('%s: Refer to Issuer', 'eWAY coded response', 'gravityforms-eway'),
				'D4402' => _x('%s: Refer to Issuer, special', 'eWAY coded response', 'gravityforms-eway'),
				'D4403' => _x('%s: No Merchant', 'eWAY coded response', 'gravityforms-eway'),
				'D4404' => _x('%s: Pick Up Card', 'eWAY coded response', 'gravityforms-eway'),
				'D4405' => _x('%s: Do Not Honor', 'eWAY coded response', 'gravityforms-eway'),
				'D4406' => _x('%s: Error', 'eWAY coded response', 'gravityforms-eway'),
				'D4407' => _x('%s: Pick Up Card, Special', 'eWAY coded response', 'gravityforms-eway'),
				'D4409' => _x('%s: Request In Progress', 'eWAY coded response', 'gravityforms-eway'),
				'D4412' => _x('%s: Invalid Transaction', 'eWAY coded response', 'gravityforms-eway'),
				'D4413' => _x('%s: Invalid Amount', 'eWAY coded response', 'gravityforms-eway'),
				'D4414' => _x('%s: Invalid Card Number', 'eWAY coded response', 'gravityforms-eway'),
				'D4415' => _x('%s: No Issuer', 'eWAY coded response', 'gravityforms-eway'),
				'D4419' => _x('%s: Re-enter Last Transaction', 'eWAY coded response', 'gravityforms-eway'),
				'D4421' => _x('%s: No Action Taken', 'eWAY coded response', 'gravityforms-eway'),
				'D4422' => _x('%s: Suspected Malfunction', 'eWAY coded response', 'gravityforms-eway'),
				'D4423' => _x('%s: Unacceptable Transaction Fee', 'eWAY coded response', 'gravityforms-eway'),
				'D4425' => _x('%s: Unable to Locate Record On File', 'eWAY coded response', 'gravityforms-eway'),
				'D4430' => _x('%s: Format Error', 'eWAY coded response', 'gravityforms-eway'),
				'D4431' => _x('%s: Bank Not Supported By Switch', 'eWAY coded response', 'gravityforms-eway'),
				'D4433' => _x('%s: Expired Card, Capture', 'eWAY coded response', 'gravityforms-eway'),
				'D4434' => _x('%s: Suspected Fraud, Retain Card', 'eWAY coded response', 'gravityforms-eway'),
				'D4435' => _x('%s: Card Acceptor, Contact Acquirer, Retain Card', 'eWAY coded response', 'gravityforms-eway'),
				'D4436' => _x('%s: Restricted Card, Retain Card', 'eWAY coded response', 'gravityforms-eway'),
				'D4437' => _x('%s: Contact Acquirer Security Department, Retain Card', 'eWAY coded response', 'gravityforms-eway'),
				'D4438' => _x('%s: PIN Tries Exceeded, Capture', 'eWAY coded response', 'gravityforms-eway'),
				'D4439' => _x('%s: No Credit Account', 'eWAY coded response', 'gravityforms-eway'),
				'D4440' => _x('%s: Function Not Supported', 'eWAY coded response', 'gravityforms-eway'),
				'D4441' => _x('%s: Lost Card', 'eWAY coded response', 'gravityforms-eway'),
				'D4442' => _x('%s: No Universal Account', 'eWAY coded response', 'gravityforms-eway'),
				'D4443' => _x('%s: Stolen Card', 'eWAY coded response', 'gravityforms-eway'),
				'D4444' => _x('%s: No Investment Account', 'eWAY coded response', 'gravityforms-eway'),
				'D4450' => _x('%s: Visa Checkout Transaction Error', 'eWAY coded response', 'gravityforms-eway'),
				'D4451' => _x('%s: Insufficient Funds', 'eWAY coded response', 'gravityforms-eway'),
				'D4452' => _x('%s: No Check Account', 'eWAY coded response', 'gravityforms-eway'),
				'D4453' => _x('%s: No Savings Account', 'eWAY coded response', 'gravityforms-eway'),
				'D4454' => _x('%s: Expired Card', 'eWAY coded response', 'gravityforms-eway'),
				'D4455' => _x('%s: Incorrect PIN', 'eWAY coded response', 'gravityforms-eway'),
				'D4456' => _x('%s: No Card Record', 'eWAY coded response', 'gravityforms-eway'),
				'D4457' => _x('%s: Function Not Permitted to Cardholder', 'eWAY coded response', 'gravityforms-eway'),
				'D4458' => _x('%s: Function Not Permitted to Terminal', 'eWAY coded response', 'gravityforms-eway'),
				'D4459' => _x('%s: Suspected Fraud', 'eWAY coded response', 'gravityforms-eway'),
				'D4460' => _x('%s: Acceptor Contact Acquirer', 'eWAY coded response', 'gravityforms-eway'),
				'D4461' => _x('%s: Exceeds Withdrawal Limit', 'eWAY coded response', 'gravityforms-eway'),
				'D4462' => _x('%s: Restricted Card', 'eWAY coded response', 'gravityforms-eway'),
				'D4463' => _x('%s: Security Violation', 'eWAY coded response', 'gravityforms-eway'),
				'D4464' => _x('%s: Original Amount Incorrect', 'eWAY coded response', 'gravityforms-eway'),
				'D4466' => _x('%s: Acceptor Contact Acquirer, Security', 'eWAY coded response', 'gravityforms-eway'),
				'D4467' => _x('%s: Capture Card', 'eWAY coded response', 'gravityforms-eway'),
				'D4475' => _x('%s: PIN Tries Exceeded', 'eWAY coded response', 'gravityforms-eway'),
				'D4482' => _x('%s: CVV Validation Error', 'eWAY coded response', 'gravityforms-eway'),
				'D4490' => _x('%s: Cut off In Progress', 'eWAY coded response', 'gravityforms-eway'),
				'D4491' => _x('%s: Card Issuer Unavailable', 'eWAY coded response', 'gravityforms-eway'),
				'D4492' => _x('%s: Unable To Route Transaction', 'eWAY coded response', 'gravityforms-eway'),
				'D4493' => _x('%s: Cannot Complete, Violation Of The Law', 'eWAY coded response', 'gravityforms-eway'),
				'D4494' => _x('%s: Duplicate Transaction', 'eWAY coded response', 'gravityforms-eway'),
				'D4495' => _x('%s: Amex Declined', 'eWAY coded response', 'gravityforms-eway'),
				'D4496' => _x('%s: System Error', 'eWAY coded response', 'gravityforms-eway'),
				'D4497' => _x('%s: MasterPass Error', 'eWAY coded response', 'gravityforms-eway'),
				'D4498' => _x('%s: PayPal Create Transaction Error', 'eWAY coded response', 'gravityforms-eway'),
				'D4499' => _x('%s: Invalid Transaction for Auth/Void', 'eWAY coded response', 'gravityforms-eway'),
				'F7000' => _x('%s: Undefined Fraud Error', 'eWAY coded response', 'gravityforms-eway'),
				'F7001' => _x('%s: Challenged Fraud', 'eWAY coded response', 'gravityforms-eway'),
				'F7002' => _x('%s: Country Match Fraud', 'eWAY coded response', 'gravityforms-eway'),
				'F7003' => _x('%s: High Risk Country Fraud', 'eWAY coded response', 'gravityforms-eway'),
				'F7004' => _x('%s: Anonymous Proxy Fraud', 'eWAY coded response', 'gravityforms-eway'),
				'F7005' => _x('%s: Transparent Proxy Fraud', 'eWAY coded response', 'gravityforms-eway'),
				'F7006' => _x('%s: Free Email Fraud', 'eWAY coded response', 'gravityforms-eway'),
				'F7007' => _x('%s: International Transaction Fraud', 'eWAY coded response', 'gravityforms-eway'),
				'F7008' => _x('%s: Risk Score Fraud', 'eWAY coded response', 'gravityforms-eway'),
				'F7009' => _x('%s: Denied Fraud', 'eWAY coded response', 'gravityforms-eway'),
				'F7010' => _x('%s: Denied by PayPal Fraud Rules', 'eWAY coded response', 'gravityforms-eway'),
				'F9001' => _x('%s: Custom Fraud Rule', 'eWAY coded response', 'gravityforms-eway'),
				'F9010' => _x('%s: High Risk Billing Country', 'eWAY coded response', 'gravityforms-eway'),
				'F9011' => _x('%s: High Risk Credit Card Country', 'eWAY coded response', 'gravityforms-eway'),
				'F9012' => _x('%s: High Risk Customer IP Address', 'eWAY coded response', 'gravityforms-eway'),
				'F9013' => _x('%s: High Risk Email Address', 'eWAY coded response', 'gravityforms-eway'),
				'F9014' => _x('%s: High Risk Shipping Country', 'eWAY coded response', 'gravityforms-eway'),
				'F9015' => _x('%s: Multiple card numbers for single email address', 'eWAY coded response', 'gravityforms-eway'),
				'F9016' => _x('%s: Multiple card numbers for single location', 'eWAY coded response', 'gravityforms-eway'),
				'F9017' => _x('%s: Multiple email addresses for single card number', 'eWAY coded response', 'gravityforms-eway'),
				'F9018' => _x('%s: Multiple email addresses for single location', 'eWAY coded response', 'gravityforms-eway'),
				'F9019' => _x('%s: Multiple locations for single card number', 'eWAY coded response', 'gravityforms-eway'),
				'F9020' => _x('%s: Multiple locations for single email address', 'eWAY coded response', 'gravityforms-eway'),
				'F9021' => _x('%s: Suspicious Customer First Name', 'eWAY coded response', 'gravityforms-eway'),
				'F9022' => _x('%s: Suspicious Customer Last Name', 'eWAY coded response', 'gravityforms-eway'),
				'F9023' => _x('%s: Transaction Declined', 'eWAY coded response', 'gravityforms-eway'),
				'F9024' => _x('%s: Multiple transactions for same address with known credit card', 'eWAY coded response', 'gravityforms-eway'),
				'F9025' => _x('%s: Multiple transactions for same address with new credit card', 'eWAY coded response', 'gravityforms-eway'),
				'F9026' => _x('%s: Multiple transactions for same email with new credit card', 'eWAY coded response', 'gravityforms-eway'),
				'F9027' => _x('%s: Multiple transactions for same email with known credit card', 'eWAY coded response', 'gravityforms-eway'),
				'F9028' => _x('%s: Multiple transactions for new credit card', 'eWAY coded response', 'gravityforms-eway'),
				'F9029' => _x('%s: Multiple transactions for known credit card', 'eWAY coded response', 'gravityforms-eway'),
				'F9030' => _x('%s: Multiple transactions for same email address', 'eWAY coded response', 'gravityforms-eway'),
				'F9031' => _x('%s: Multiple transactions for same credit card', 'eWAY coded response', 'gravityforms-eway'),
				'F9032' => _x('%s: Invalid Customer Last Name', 'eWAY coded response', 'gravityforms-eway'),
				'F9033' => _x('%s: Invalid Billing Street', 'eWAY coded response', 'gravityforms-eway'),
				'F9034' => _x('%s: Invalid Shipping Street', 'eWAY coded response', 'gravityforms-eway'),
				'F9037' => _x('%s: Suspicious Customer Email Address', 'eWAY coded response', 'gravityforms-eway'),
				'F9050' => _x('%s: High Risk Email Address and amount', 'eWAY coded response', 'gravityforms-eway'),
				'F9113' => _x('%s: Card issuing country differs from IP address country', 'eWAY coded response', 'gravityforms-eway'),
				'S5000' => _x('%s: System Error', 'eWAY coded response', 'gravityforms-eway'),
				'S5011' => _x('%s: PayPal Connection Error', 'eWAY coded response', 'gravityforms-eway'),
				'S5012' => _x('%s: PayPal Settings Error', 'eWAY coded response', 'gravityforms-eway'),
				'S5085' => _x('%s: Started 3dSecure', 'eWAY coded response', 'gravityforms-eway'),
				'S5086' => _x('%s: Routed 3dSecure', 'eWAY coded response', 'gravityforms-eway'),
				'S5087' => _x('%s: Completed 3dSecure', 'eWAY coded response', 'gravityforms-eway'),
				'S5088' => _x('%s: PayPal Transaction Created', 'eWAY coded response', 'gravityforms-eway'),
				'S5099' => _x('%s: Incomplete (Access Code in progress/incomplete)', 'eWAY coded response', 'gravityforms-eway'),
				'S5010' => _x('%s: Unknown error returned by gateway', 'eWAY coded response', 'gravityforms-eway'),
				'V6000' => _x('%s: Validation error', 'eWAY coded response', 'gravityforms-eway'),
				'V6001' => _x('%s: Invalid CustomerIP', 'eWAY coded response', 'gravityforms-eway'),
				'V6002' => _x('%s: Invalid DeviceID', 'eWAY coded response', 'gravityforms-eway'),
				'V6003' => _x('%s: Invalid Request PartnerID', 'eWAY coded response', 'gravityforms-eway'),
				'V6004' => _x('%s: Invalid Request Method', 'eWAY coded response', 'gravityforms-eway'),
				'V6010' => _x('%s: Invalid TransactionType, account not certified for eCome only MOTO or Recurring available', 'eWAY coded response', 'gravityforms-eway'),
				'V6011' => _x('%s: Invalid Payment TotalAmount', 'eWAY coded response', 'gravityforms-eway'),
				'V6012' => _x('%s: Invalid Payment InvoiceDescription', 'eWAY coded response', 'gravityforms-eway'),
				'V6013' => _x('%s: Invalid Payment InvoiceNumber', 'eWAY coded response', 'gravityforms-eway'),
				'V6014' => _x('%s: Invalid Payment InvoiceReference', 'eWAY coded response', 'gravityforms-eway'),
				'V6015' => _x('%s: Invalid Payment CurrencyCode', 'eWAY coded response', 'gravityforms-eway'),
				'V6016' => _x('%s: Payment Required', 'eWAY coded response', 'gravityforms-eway'),
				'V6017' => _x('%s: Payment CurrencyCode Required', 'eWAY coded response', 'gravityforms-eway'),
				'V6018' => _x('%s: Unknown Payment CurrencyCode', 'eWAY coded response', 'gravityforms-eway'),
				'V6021' => _x('%s: Cardholder Name Required', 'eWAY coded response', 'gravityforms-eway'),
				'V6022' => _x('%s: Card Number Required', 'eWAY coded response', 'gravityforms-eway'),
				'V6023' => _x('%s: Card Security Code (CVN) Required', 'eWAY coded response', 'gravityforms-eway'),
				'V6033' => _x('%s: Invalid Expiry Date', 'eWAY coded response', 'gravityforms-eway'),
				'V6034' => _x('%s: Invalid Issue Number', 'eWAY coded response', 'gravityforms-eway'),
				'V6035' => _x('%s: Invalid Valid From Date', 'eWAY coded response', 'gravityforms-eway'),
				'V6040' => _x('%s: Invalid TokenCustomerID', 'eWAY coded response', 'gravityforms-eway'),
				'V6041' => _x('%s: Customer Required', 'eWAY coded response', 'gravityforms-eway'),
				'V6042' => _x('%s: Customer FirstName Required', 'eWAY coded response', 'gravityforms-eway'),
				'V6043' => _x('%s: Customer LastName Required', 'eWAY coded response', 'gravityforms-eway'),
				'V6044' => _x('%s: Customer CountryCode Required', 'eWAY coded response', 'gravityforms-eway'),
				'V6045' => _x('%s: Customer Title Required', 'eWAY coded response', 'gravityforms-eway'),
				'V6046' => _x('%s: TokenCustomerID Required', 'eWAY coded response', 'gravityforms-eway'),
				'V6047' => _x('%s: RedirectURL Required', 'eWAY coded response', 'gravityforms-eway'),
				'V6048' => _x('%s: CheckoutURL Required when CheckoutPayment specified', 'eWAY coded response', 'gravityforms-eway'),
				'V6049' => _x('%s: Invalid Checkout URL', 'eWAY coded response', 'gravityforms-eway'),
				'V6051' => _x('%s: Invalid Customer FirstName', 'eWAY coded response', 'gravityforms-eway'),
				'V6052' => _x('%s: Invalid Customer LastName', 'eWAY coded response', 'gravityforms-eway'),
				'V6053' => _x('%s: Invalid Customer CountryCode', 'eWAY coded response', 'gravityforms-eway'),
				'V6058' => _x('%s: Invalid Customer Title', 'eWAY coded response', 'gravityforms-eway'),
				'V6059' => _x('%s: Invalid RedirectURL', 'eWAY coded response', 'gravityforms-eway'),
				'V6060' => _x('%s: Invalid TokenCustomerID', 'eWAY coded response', 'gravityforms-eway'),
				'V6061' => _x('%s: Invalid Customer Reference', 'eWAY coded response', 'gravityforms-eway'),
				'V6062' => _x('%s: Invalid Customer CompanyName', 'eWAY coded response', 'gravityforms-eway'),
				'V6063' => _x('%s: Invalid Customer JobDescription', 'eWAY coded response', 'gravityforms-eway'),
				'V6064' => _x('%s: Invalid Customer Street1', 'eWAY coded response', 'gravityforms-eway'),
				'V6065' => _x('%s: Invalid Customer Street2', 'eWAY coded response', 'gravityforms-eway'),
				'V6066' => _x('%s: Invalid Customer City', 'eWAY coded response', 'gravityforms-eway'),
				'V6067' => _x('%s: Invalid Customer State', 'eWAY coded response', 'gravityforms-eway'),
				'V6068' => _x('%s: Invalid Customer PostalCode', 'eWAY coded response', 'gravityforms-eway'),
				'V6069' => _x('%s: Invalid Customer Email', 'eWAY coded response', 'gravityforms-eway'),
				'V6070' => _x('%s: Invalid Customer Phone', 'eWAY coded response', 'gravityforms-eway'),
				'V6071' => _x('%s: Invalid Customer Mobile', 'eWAY coded response', 'gravityforms-eway'),
				'V6072' => _x('%s: Invalid Customer Comments', 'eWAY coded response', 'gravityforms-eway'),
				'V6073' => _x('%s: Invalid Customer Fax', 'eWAY coded response', 'gravityforms-eway'),
				'V6074' => _x('%s: Invalid Customer URL', 'eWAY coded response', 'gravityforms-eway'),
				'V6075' => _x('%s: Invalid ShippingAddress FirstName', 'eWAY coded response', 'gravityforms-eway'),
				'V6076' => _x('%s: Invalid ShippingAddress LastName', 'eWAY coded response', 'gravityforms-eway'),
				'V6077' => _x('%s: Invalid ShippingAddress Street1', 'eWAY coded response', 'gravityforms-eway'),
				'V6078' => _x('%s: Invalid ShippingAddress Street2', 'eWAY coded response', 'gravityforms-eway'),
				'V6079' => _x('%s: Invalid ShippingAddress City', 'eWAY coded response', 'gravityforms-eway'),
				'V6080' => _x('%s: Invalid ShippingAddress State', 'eWAY coded response', 'gravityforms-eway'),
				'V6081' => _x('%s: Invalid ShippingAddress PostalCode', 'eWAY coded response', 'gravityforms-eway'),
				'V6082' => _x('%s: Invalid ShippingAddress Email', 'eWAY coded response', 'gravityforms-eway'),
				'V6083' => _x('%s: Invalid ShippingAddress Phone', 'eWAY coded response', 'gravityforms-eway'),
				'V6084' => _x('%s: Invalid ShippingAddress Country', 'eWAY coded response', 'gravityforms-eway'),
				'V6085' => _x('%s: Invalid ShippingAddress ShippingMethod', 'eWAY coded response', 'gravityforms-eway'),
				'V6086' => _x('%s: Invalid ShippingAddress Fax', 'eWAY coded response', 'gravityforms-eway'),
				'V6091' => _x('%s: Unknown Customer CountryCode', 'eWAY coded response', 'gravityforms-eway'),
				'V6092' => _x('%s: Unknown ShippingAddress CountryCode', 'eWAY coded response', 'gravityforms-eway'),
				'V6100' => _x('%s: Invalid Cardholder Name', 'eWAY coded response', 'gravityforms-eway'),
				'V6101' => _x('%s: Invalid Card Expiry Month', 'eWAY coded response', 'gravityforms-eway'),
				'V6102' => _x('%s: Invalid Card Expiry Year', 'eWAY coded response', 'gravityforms-eway'),
				'V6103' => _x('%s: Invalid Card Start Month', 'eWAY coded response', 'gravityforms-eway'),
				'V6104' => _x('%s: Invalid Card Start Year', 'eWAY coded response', 'gravityforms-eway'),
				'V6105' => _x('%s: Invalid Card Issue Number', 'eWAY coded response', 'gravityforms-eway'),
				'V6106' => _x('%s: Invalid Card Security Code (CVN)', 'eWAY coded response', 'gravityforms-eway'),
				'V6107' => _x('%s: Invalid Access Code', 'eWAY coded response', 'gravityforms-eway'),
				'V6108' => _x('%s: Invalid CustomerHostAddress', 'eWAY coded response', 'gravityforms-eway'),
				'V6109' => _x('%s: Invalid UserAgent', 'eWAY coded response', 'gravityforms-eway'),
				'V6110' => _x('%s: Invalid Card Number', 'eWAY coded response', 'gravityforms-eway'),
				'V6111' => _x('%s: Unauthorized API Access, Account Not PCI Certified', 'eWAY coded response', 'gravityforms-eway'),
				'V6112' => _x('%s: Redundant card details other than expiry year and month', 'eWAY coded response', 'gravityforms-eway'),
				'V6113' => _x('%s: Invalid transaction for refund', 'eWAY coded response', 'gravityforms-eway'),
				'V6114' => _x('%s: Gateway validation error', 'eWAY coded response', 'gravityforms-eway'),
				'V6115' => _x('%s: Invalid DirectRefundRequest, Transaction ID', 'eWAY coded response', 'gravityforms-eway'),
				'V6116' => _x('%s: Invalid card data on original TransactionID', 'eWAY coded response', 'gravityforms-eway'),
				'V6117' => _x('%s: Invalid CreateAccessCodeSharedRequest, FooterText', 'eWAY coded response', 'gravityforms-eway'),
				'V6118' => _x('%s: Invalid CreateAccessCodeSharedRequest, HeaderText', 'eWAY coded response', 'gravityforms-eway'),
				'V6119' => _x('%s: Invalid CreateAccessCodeSharedRequest, Language', 'eWAY coded response', 'gravityforms-eway'),
				'V6120' => _x('%s: Invalid CreateAccessCodeSharedRequest, LogoUrl', 'eWAY coded response', 'gravityforms-eway'),
				'V6121' => _x('%s: Invalid TransactionSearch, Filter Match Type', 'eWAY coded response', 'gravityforms-eway'),
				'V6122' => _x('%s: Invalid TransactionSearch, Non numeric Transaction ID', 'eWAY coded response', 'gravityforms-eway'),
				'V6123' => _x('%s: Invalid TransactionSearch,no TransactionID or AccessCode specified', 'eWAY coded response', 'gravityforms-eway'),
				'V6124' => _x('%s: Invalid Line Items. The line items have been provided however the totals do not match the TotalAmount field', 'eWAY coded response', 'gravityforms-eway'),
				'V6125' => _x('%s: Selected Payment Type not enabled', 'eWAY coded response', 'gravityforms-eway'),
				'V6126' => _x('%s: Invalid encrypted card number, decryption failed', 'eWAY coded response', 'gravityforms-eway'),
				'V6127' => _x('%s: Invalid encrypted cvn, decryption failed', 'eWAY coded response', 'gravityforms-eway'),
				'V6128' => _x('%s: Invalid Method for Payment Type', 'eWAY coded response', 'gravityforms-eway'),
				'V6129' => _x('%s: Transaction has not been authorized for Capture/Cancellation', 'eWAY coded response', 'gravityforms-eway'),
				'V6130' => _x('%s: Generic customer information error', 'eWAY coded response', 'gravityforms-eway'),
				'V6131' => _x('%s: Generic shipping information error', 'eWAY coded response', 'gravityforms-eway'),
				'V6132' => _x('%s: Transaction has already been completed or voided, operation not permitted', 'eWAY coded response', 'gravityforms-eway'),
				'V6133' => _x('%s: Checkout not available for Payment Type', 'eWAY coded response', 'gravityforms-eway'),
				'V6134' => _x('%s: Invalid Auth Transaction ID for Capture/Void', 'eWAY coded response', 'gravityforms-eway'),
				'V6135' => _x('%s: PayPal Error Processing Refund', 'eWAY coded response', 'gravityforms-eway'),
				'V6140' => _x('%s: Merchant account is suspended', 'eWAY coded response', 'gravityforms-eway'),
				'V6141' => _x('%s: Invalid PayPal account details or API signature', 'eWAY coded response', 'gravityforms-eway'),
				'V6142' => _x('%s: Authorize not available for Bank/Branch', 'eWAY coded response', 'gravityforms-eway'),
				'V6150' => _x('%s: Invalid Refund Amount', 'eWAY coded response', 'gravityforms-eway'),
				'V6151' => _x('%s: Refund amount greater than original transaction', 'eWAY coded response', 'gravityforms-eway'),
				'V6152' => _x('%s: Original transaction already refunded for total amount', 'eWAY coded response', 'gravityforms-eway'),
				'V6153' => _x('%s: Card type not support by merchant', 'eWAY coded response', 'gravityforms-eway'),
				'V6160' => _x('%s: Encryption Method Not Supported', 'eWAY coded response', 'gravityforms-eway'),
				'V6165' => _x('%s: Invalid Visa Checkout data or decryption failed', 'eWAY coded response', 'gravityforms-eway'),
				'V6170' => _x('%s: Invalid TransactionSearch, Invoice Number is not unique', 'eWAY coded response', 'gravityforms-eway'),
				'V6171' => _x('%s: Invalid TransactionSearch, Invoice Number not found', 'eWAY coded response', 'gravityforms-eway'),
			);
		}

		$msg = isset($messages[$code]) ? sprintf($messages[$code], $code) : $code;
		return apply_filters('gfeway_code_description', $msg, $code);
	}

}
