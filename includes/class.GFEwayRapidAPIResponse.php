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
		// but not if it's in JPY which is already at the target format
		if (isset($this->Payment) && !empty($this->Payment->TotalAmount) && $this->Payment->CurrencyCode !== 'JPY') {
			$this->Payment->TotalAmount = floatval($this->Payment->TotalAmount) / 100.0;
		}
	}

	/**
	* separate response codes into individual errors
	* @param string $codes
	* @return array
	*/
	protected function getResponseDetails($codes) {
		$responses = [];

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
		// source @link https://github.com/eWAYPayment/eway-rapid-php/blob/master/resource/lang/en.ini
		// NB: translated into en_US for consistency with base locale
		switch ($code) {

			case 'A2000': $msg = _x('%s: Transaction Approved', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'A2008': $msg = _x('%s: Honor With Identification', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'A2010': $msg = _x('%s: Approved For Partial Amount', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'A2011': $msg = _x('%s: Approved, VIP', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'A2016': $msg = _x('%s: Approved, Update Track 3', 'eWAY coded response', 'gravityforms-eway'); break;

			case 'D4401': $msg = _x('%s: Refer to Issuer', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4402': $msg = _x('%s: Refer to Issuer, special', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4403': $msg = _x('%s: No Merchant', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4404': $msg = _x('%s: Pick Up Card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4405': $msg = _x('%s: Do Not Honor', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4406': $msg = _x('%s: Error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4407': $msg = _x('%s: Pick Up Card, Special', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4409': $msg = _x('%s: Request In Progress', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4412': $msg = _x('%s: Invalid Transaction', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4413': $msg = _x('%s: Invalid Amount', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4414': $msg = _x('%s: Invalid Card Number', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4415': $msg = _x('%s: No Issuer', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4419': $msg = _x('%s: Re-enter Last Transaction', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4421': $msg = _x('%s: No Action Taken', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4422': $msg = _x('%s: Suspected Malfunction', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4423': $msg = _x('%s: Unacceptable Transaction Fee', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4425': $msg = _x('%s: Unable to Locate Record On File', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4430': $msg = _x('%s: Format Error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4431': $msg = _x('%s: Bank Not Supported By Switch', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4433': $msg = _x('%s: Expired Card, Capture', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4434': $msg = _x('%s: Suspected Fraud, Retain Card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4435': $msg = _x('%s: Card Acceptor, Contact Acquirer, Retain Card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4436': $msg = _x('%s: Restricted Card, Retain Card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4437': $msg = _x('%s: Contact Acquirer Security Department, Retain Card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4438': $msg = _x('%s: PIN Tries Exceeded, Capture', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4439': $msg = _x('%s: No Credit Account', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4440': $msg = _x('%s: Function Not Supported', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4441': $msg = _x('%s: Lost Card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4442': $msg = _x('%s: No Universal Account', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4443': $msg = _x('%s: Stolen Card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4444': $msg = _x('%s: No Investment Account', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4450': $msg = _x('%s: Visa Checkout Transaction Error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4451': $msg = _x('%s: Insufficient Funds', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4452': $msg = _x('%s: No Check Account', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4453': $msg = _x('%s: No Savings Account', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4454': $msg = _x('%s: Expired Card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4455': $msg = _x('%s: Incorrect PIN', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4456': $msg = _x('%s: No Card Record', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4457': $msg = _x('%s: Function Not Permitted to Cardholder', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4458': $msg = _x('%s: Function Not Permitted to Terminal', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4459': $msg = _x('%s: Suspected Fraud', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4460': $msg = _x('%s: Acceptor Contact Acquirer', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4461': $msg = _x('%s: Exceeds Withdrawal Limit', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4462': $msg = _x('%s: Restricted Card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4463': $msg = _x('%s: Security Violation', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4464': $msg = _x('%s: Original Amount Incorrect', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4466': $msg = _x('%s: Acceptor Contact Acquirer, Security', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4467': $msg = _x('%s: Capture Card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4475': $msg = _x('%s: PIN Tries Exceeded', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4482': $msg = _x('%s: CVV Validation Error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4490': $msg = _x('%s: Cut off In Progress', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4491': $msg = _x('%s: Card Issuer Unavailable', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4492': $msg = _x('%s: Unable To Route Transaction', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4493': $msg = _x('%s: Cannot Complete, Violation Of The Law', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4494': $msg = _x('%s: Duplicate Transaction', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4495': $msg = _x('%s: Amex Declined', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4496': $msg = _x('%s: System Error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4497': $msg = _x('%s: MasterPass Error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4498': $msg = _x('%s: PayPal Create Transaction Error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'D4499': $msg = _x('%s: Invalid Transaction for Auth/Void', 'eWAY coded response', 'gravityforms-eway'); break;

			case 'F7000': $msg = _x('%s: Undefined Fraud Error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F7001': $msg = _x('%s: Challenged Fraud', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F7002': $msg = _x('%s: Country Match Fraud', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F7003': $msg = _x('%s: High Risk Country Fraud', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F7004': $msg = _x('%s: Anonymous Proxy Fraud', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F7005': $msg = _x('%s: Transparent Proxy Fraud', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F7006': $msg = _x('%s: Free Email Fraud', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F7007': $msg = _x('%s: International Transaction Fraud', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F7008': $msg = _x('%s: Risk Score Fraud', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F7009': $msg = _x('%s: Denied Fraud', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F7010': $msg = _x('%s: Denied by PayPal Fraud Rules', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9001': $msg = _x('%s: Custom Fraud Rule', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9010': $msg = _x('%s: High Risk Billing Country', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9011': $msg = _x('%s: High Risk Credit Card Country', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9012': $msg = _x('%s: High Risk Customer IP Address', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9013': $msg = _x('%s: High Risk Email Address', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9014': $msg = _x('%s: High Risk Shipping Country', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9015': $msg = _x('%s: Multiple card numbers for single email address', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9016': $msg = _x('%s: Multiple card numbers for single location', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9017': $msg = _x('%s: Multiple email addresses for single card number', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9018': $msg = _x('%s: Multiple email addresses for single location', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9019': $msg = _x('%s: Multiple locations for single card number', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9020': $msg = _x('%s: Multiple locations for single email address', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9021': $msg = _x('%s: Suspicious Customer First Name', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9022': $msg = _x('%s: Suspicious Customer Last Name', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9023': $msg = _x('%s: Transaction Declined', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9024': $msg = _x('%s: Multiple transactions for same address with known credit card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9025': $msg = _x('%s: Multiple transactions for same address with new credit card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9026': $msg = _x('%s: Multiple transactions for same email with new credit card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9027': $msg = _x('%s: Multiple transactions for same email with known credit card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9028': $msg = _x('%s: Multiple transactions for new credit card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9029': $msg = _x('%s: Multiple transactions for known credit card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9030': $msg = _x('%s: Multiple transactions for same email address', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9031': $msg = _x('%s: Multiple transactions for same credit card', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9032': $msg = _x('%s: Invalid Customer Last Name', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9033': $msg = _x('%s: Invalid Billing Street', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9034': $msg = _x('%s: Invalid Shipping Street', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9037': $msg = _x('%s: Suspicious Customer Email Address', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9050': $msg = _x('%s: High Risk Email Address and amount', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'F9113': $msg = _x('%s: Card issuing country differs from IP address country', 'eWAY coded response', 'gravityforms-eway'); break;

			case 'S5000': $msg = _x('%s: System Error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'S5011': $msg = _x('%s: PayPal Connection Error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'S5012': $msg = _x('%s: PayPal Settings Error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'S5085': $msg = _x('%s: Started 3dSecure', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'S5086': $msg = _x('%s: Routed 3dSecure', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'S5087': $msg = _x('%s: Completed 3dSecure', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'S5088': $msg = _x('%s: PayPal Transaction Created', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'S5099': $msg = _x('%s: Incomplete (Access Code in progress/incomplete)', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'S5010': $msg = _x('%s: Unknown error returned by gateway', 'eWAY coded response', 'gravityforms-eway'); break;

			case 'V6000': $msg = _x('%s: Validation error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6001': $msg = _x('%s: Invalid CustomerIP', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6002': $msg = _x('%s: Invalid DeviceID', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6003': $msg = _x('%s: Invalid Request PartnerID', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6004': $msg = _x('%s: Invalid Request Method', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6010': $msg = _x('%s: Invalid TransactionType, account not certified for eCome only MOTO or Recurring available', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6011': $msg = _x('%s: Invalid Payment TotalAmount', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6012': $msg = _x('%s: Invalid Payment InvoiceDescription', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6013': $msg = _x('%s: Invalid Payment InvoiceNumber', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6014': $msg = _x('%s: Invalid Payment InvoiceReference', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6015': $msg = _x('%s: Invalid Payment CurrencyCode', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6016': $msg = _x('%s: Payment Required', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6017': $msg = _x('%s: Payment CurrencyCode Required', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6018': $msg = _x('%s: Unknown Payment CurrencyCode', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6021': $msg = _x('%s: Cardholder Name Required', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6022': $msg = _x('%s: Card Number Required', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6023': $msg = _x('%s: Card Security Code (CVN) Required', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6033': $msg = _x('%s: Invalid Expiry Date', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6034': $msg = _x('%s: Invalid Issue Number', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6035': $msg = _x('%s: Invalid Valid From Date', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6040': $msg = _x('%s: Invalid TokenCustomerID', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6041': $msg = _x('%s: Customer Required', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6042': $msg = _x('%s: Customer FirstName Required', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6043': $msg = _x('%s: Customer LastName Required', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6044': $msg = _x('%s: Customer CountryCode Required', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6045': $msg = _x('%s: Customer Title Required', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6046': $msg = _x('%s: TokenCustomerID Required', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6047': $msg = _x('%s: RedirectURL Required', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6048': $msg = _x('%s: CheckoutURL Required when CheckoutPayment specified', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6049': $msg = _x('%s: Invalid Checkout URL', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6051': $msg = _x('%s: Invalid Customer FirstName', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6052': $msg = _x('%s: Invalid Customer LastName', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6053': $msg = _x('%s: Invalid Customer CountryCode', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6058': $msg = _x('%s: Invalid Customer Title', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6059': $msg = _x('%s: Invalid RedirectURL', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6060': $msg = _x('%s: Invalid TokenCustomerID', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6061': $msg = _x('%s: Invalid Customer Reference', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6062': $msg = _x('%s: Invalid Customer CompanyName', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6063': $msg = _x('%s: Invalid Customer JobDescription', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6064': $msg = _x('%s: Invalid Customer Street1', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6065': $msg = _x('%s: Invalid Customer Street2', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6066': $msg = _x('%s: Invalid Customer City', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6067': $msg = _x('%s: Invalid Customer State', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6068': $msg = _x('%s: Invalid Customer PostalCode', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6069': $msg = _x('%s: Invalid Customer Email', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6070': $msg = _x('%s: Invalid Customer Phone', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6071': $msg = _x('%s: Invalid Customer Mobile', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6072': $msg = _x('%s: Invalid Customer Comments', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6073': $msg = _x('%s: Invalid Customer Fax', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6074': $msg = _x('%s: Invalid Customer URL', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6075': $msg = _x('%s: Invalid ShippingAddress FirstName', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6076': $msg = _x('%s: Invalid ShippingAddress LastName', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6077': $msg = _x('%s: Invalid ShippingAddress Street1', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6078': $msg = _x('%s: Invalid ShippingAddress Street2', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6079': $msg = _x('%s: Invalid ShippingAddress City', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6080': $msg = _x('%s: Invalid ShippingAddress State', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6081': $msg = _x('%s: Invalid ShippingAddress PostalCode', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6082': $msg = _x('%s: Invalid ShippingAddress Email', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6083': $msg = _x('%s: Invalid ShippingAddress Phone', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6084': $msg = _x('%s: Invalid ShippingAddress Country', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6085': $msg = _x('%s: Invalid ShippingAddress ShippingMethod', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6086': $msg = _x('%s: Invalid ShippingAddress Fax', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6091': $msg = _x('%s: Unknown Customer CountryCode', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6092': $msg = _x('%s: Unknown ShippingAddress CountryCode', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6100': $msg = _x('%s: Invalid Cardholder Name', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6101': $msg = _x('%s: Invalid Card Expiry Month', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6102': $msg = _x('%s: Invalid Card Expiry Year', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6103': $msg = _x('%s: Invalid Card Start Month', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6104': $msg = _x('%s: Invalid Card Start Year', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6105': $msg = _x('%s: Invalid Card Issue Number', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6106': $msg = _x('%s: Invalid Card Security Code (CVN)', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6107': $msg = _x('%s: Invalid Access Code', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6108': $msg = _x('%s: Invalid CustomerHostAddress', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6109': $msg = _x('%s: Invalid UserAgent', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6110': $msg = _x('%s: Invalid Card Number', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6111': $msg = _x('%s: Unauthorized API Access, Account Not PCI Certified', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6112': $msg = _x('%s: Redundant card details other than expiry year and month', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6113': $msg = _x('%s: Invalid transaction for refund', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6114': $msg = _x('%s: Gateway validation error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6115': $msg = _x('%s: Invalid DirectRefundRequest, Transaction ID', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6116': $msg = _x('%s: Invalid card data on original TransactionID', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6117': $msg = _x('%s: Invalid CreateAccessCodeSharedRequest, FooterText', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6118': $msg = _x('%s: Invalid CreateAccessCodeSharedRequest, HeaderText', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6119': $msg = _x('%s: Invalid CreateAccessCodeSharedRequest, Language', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6120': $msg = _x('%s: Invalid CreateAccessCodeSharedRequest, LogoUrl', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6121': $msg = _x('%s: Invalid TransactionSearch, Filter Match Type', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6122': $msg = _x('%s: Invalid TransactionSearch, Non numeric Transaction ID', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6123': $msg = _x('%s: Invalid TransactionSearch,no TransactionID or AccessCode specified', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6124': $msg = _x('%s: Invalid Line Items. The line items have been provided, however the totals do not match the TotalAmount field', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6125': $msg = _x('%s: Selected Payment Type not enabled', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6126': $msg = _x('%s: Invalid encrypted card number, decryption failed', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6127': $msg = _x('%s: Invalid encrypted cvn, decryption failed', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6128': $msg = _x('%s: Invalid Method for Payment Type', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6129': $msg = _x('%s: Transaction has not been authorized for Capture/Cancellation', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6130': $msg = _x('%s: Generic customer information error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6131': $msg = _x('%s: Generic shipping information error', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6132': $msg = _x('%s: Transaction has already been completed or voided, operation not permitted', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6133': $msg = _x('%s: Checkout not available for Payment Type', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6134': $msg = _x('%s: Invalid Auth Transaction ID for Capture/Void', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6135': $msg = _x('%s: PayPal Error Processing Refund', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6140': $msg = _x('%s: Merchant account is suspended', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6141': $msg = _x('%s: Invalid PayPal account details or API signature', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6142': $msg = _x('%s: Authorize not available for Bank/Branch', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6143': $msg = _x('%s: Invalid Public Key', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6146': $msg = _x('%s: Client Side Encryption Key Missing or Invalid', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6147': $msg = _x('%s: Unable to Create One Time Code for Secure Field', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6148': $msg = _x('%s: Secure Field has Expired', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6149': $msg = _x('%s: Invalid Secure Field One Time Code', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6150': $msg = _x('%s: Invalid Refund Amount', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6151': $msg = _x('%s: Refund amount greater than original transaction', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6152': $msg = _x('%s: Original transaction already refunded for total amount', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6153': $msg = _x('%s: Card type not support by merchant', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6160': $msg = _x('%s: Encryption Method Not Supported', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6161': $msg = _x('%s: Encryption failed, missing or invalid key', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6165': $msg = _x('%s: Invalid Visa Checkout data or decryption failed', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6170': $msg = _x('%s: Invalid TransactionSearch, Invoice Number is not unique', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6171': $msg = _x('%s: Invalid TransactionSearch, Invoice Number not found', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6210': $msg = _x('%s: Secure Field Invalid Type', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6211': $msg = _x('%s: Secure Field Invalid Div', 'eWAY coded response', 'gravityforms-eway'); break;
			case 'V6212': $msg = _x('%s: Invalid Style string for Secure Field', 'eWAY coded response', 'gravityforms-eway'); break;

			default:
				$msg = false;
				break;

		}

		$msg = $msg ? sprintf($msg, $code) : $code;

		return apply_filters('gfeway_code_description', $msg, $code);
	}

}
