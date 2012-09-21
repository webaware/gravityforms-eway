<?php
/**
* Class for dealing with an eWAY recurring payment response
*/
class GFEwayRecurringResponse {
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

	/**
	* load eWAY response data as XML string
	*
	* @param string $response eWAY response as a string (hopefully of XML data)
	*/
	public function loadResponseXML($response) {
		try {
			// prevent XML injection attacks
			$oldDisableEntityLoader = libxml_disable_entity_loader(TRUE);

			$xml = new SimpleXMLElement($response);

			$this->status = (strcasecmp((string) $xml->Result, 'success') === 0);
			$this->errorType = (string) $xml->ErrorSeverity;
			$this->error = (string) $xml->ErrorDetails;

			// restore default XML inclusion and expansion
			libxml_disable_entity_loader($oldDisableEntityLoader);
		}
		catch (Exception $e) {
			// restore default XML inclusion and expansion
			libxml_disable_entity_loader($oldDisableEntityLoader);

			throw new Exception('Error parsing eWAY recurring payments response: ' . $e->getMessage());
		}
	}
}
