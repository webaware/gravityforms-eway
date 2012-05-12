<?php
/**
* Class for dealing with an eWAY payment response
* copyright (c) 2008-2012 WebAware Pty Ltd, released under GPL v2.1
*/
class GFEwayResponse {
	/**
	* For a successful transaction "True" is passed and for a failed transaction "False" is passed.
	* @var boolean
	*/
	public $status;

	/**
	* eWAYTrxnNumber
	* @var string max. 16 characters
	*/
	public $transactionNumber;

	/**
	* eWAYTrxnNumber referenced in transaction (e.g. invoice number)
	* @var string max. 16 characters
	*/
	public $transactionReference;

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

	/**
	* If the transaction is successful, this is the bank authorisation number. This is also sent in the email receipt.
	* @var string max. 6 characters
	*/
	public $authCode;

	/**
	* total amount of payment as processed, in dollars and cents as a floating-point number
	* @var float
	*/
	public $amount;

	/**
	* the response returned by the bank, and can be related to both successful and failed transactions.
	* @var string max. 100 characters
	*/
	public $error;

	/**
	* load eWAY response data as XML string
	*
	* @param string $response eWAY response as a string (hopefully of XML data)
	*/
	public function loadResponseXML($response) {
		try {
			$xml = new SimpleXMLElement($response);

			$this->status = (strcasecmp($xml->ewayTrxnStatus, 'true') === 0);
			$this->transactionNumber = (string) $xml->ewayTrxnNumber;
			$this->transactionReference = (string) $xml->ewayTrxnReference;
			$this->option1 = (string) $xml->ewayTrxnOption1;
			$this->option2 = (string) $xml->ewayTrxnOption2;
			$this->option3 = (string) $xml->ewayTrxnOption3;
			$this->authCode = (string) $xml->ewayAuthCode;
			$this->error = (string) $xml->ewayTrxnError;

			// if we got an amount, convert it back into dollars.cents from just cents
			if (!empty($xml->ewayReturnAmount))
				$this->amount = floatval($xml->ewayReturnAmount) / 100.0;
			else
				$this->amount = NULL;
		}
		catch (Exception $e) {
			throw new Exception('Error parsing eWAY response: ' . $e->getMessage());
		}
	}
}
