<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* class for managing form data
*/
class GFEwayFormData {

	public $total					= 0;
	public $ccName					= '';
	public $ccNumber				= '';
	public $ccExpMonth				= '';
	public $ccExpYear				= '';
	public $ccCVN					= '';
	public $namePrefix				= '';
	public $firstName				= '';
	public $lastName				= '';
	public $email					= '';
	public $address_street1			= '';						// street address line 1
	public $address_street2			= '';						// street address line 2
	public $address_suburb			= '';						// suburb, for recurring payments
	public $address_state			= '';						// state, for recurring payments
	public $address_country			= '';						// country, for recurring payments
	public $postcode				= '';						// postcode, for both regular and recurring payments
	public $phone					= '';						// phone number, for recurring payments
	public $recurring				= false;					// false, or an array of inputs from complex field
	public $ccField					= false;					// handle to meta-"field" for credit card in form

	private $isLastPageFlag			= false;
	private $isCcHiddenFlag			= false;
	private $hasPurchaseFieldsFlag	= false;

	/**
	* initialise instance
	* @param array $form
	*/
	public function __construct(&$form) {
		// check for last page
        $current_page	= (int) GFFormDisplay::get_source_page($form['id']);
        $target_page	= (int) GFFormDisplay::get_target_page($form, $current_page, rgpost('gform_field_values'));
        $this->isLastPageFlag = ($target_page === 0);

		// load the form data
		$this->loadForm($form);
	}

	/**
	* load the form data we care about from the form array
	* @param array $form
	*/
	private function loadForm(&$form) {
		foreach ($form['fields'] as &$field) {
			$id = $field->id;

			switch (GFFormsModel::get_input_type($field)) {

				case 'name':
					// only pick up the first name field (assume later ones are additional info)
					if (empty($this->firstName) && empty($this->lastName)) {
						$this->namePrefix			= trim(rgpost("input_{$id}_2"));
						$this->firstName			= trim(rgpost("input_{$id}_3"));
						$this->lastName				= trim(rgpost("input_{$id}_6"));
					}
					break;

				case 'email':
					// only pick up the first email address field (assume later ones are additional info)
					if (empty($this->email)) {
						$this->email				= trim(rgpost("input_{$id}"));
					}
					break;

				case 'phone':
					// only pick up the first phone number field (assume later ones are additional info)
					if (empty($this->phone)) {
						$this->phone				= trim(rgpost("input_{$id}"));
					}
					break;

				case 'address':
					// only pick up the first address field (assume later ones are additional info, e.g. shipping)
					if (empty($this->address) && empty($this->postcode)) {
						$this->address_street1		= trim(rgpost("input_{$id}_1"));
						$this->address_street2		= trim(rgpost("input_{$id}_2"));
						$this->address_suburb		= trim(rgpost("input_{$id}_3"));
						$this->address_state		= trim(rgpost("input_{$id}_4"));
						$this->address_country		= trim(rgpost("input_{$id}_6"));
						$this->postcode				= trim(rgpost("input_{$id}_5"));
					}
					break;

				case 'creditcard':
					$this->isCcHiddenFlag			= GFFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'));
					$this->ccField					=& $field;
					$this->ccName					= trim(rgpost("input_{$id}_5"));
					$this->ccNumber					= self::cleanCcNumber(trim(rgpost("input_{$id}_1")));
					$ccExp							= rgpost("input_{$id}_2");
					if (is_array($ccExp)) {
						list($this->ccExpMonth, $this->ccExpYear) = $ccExp;
					}
					$this->ccCVN					= trim(rgpost("input_{$id}_3"));

					// handle eWAY Client Side Encryption
					if (!empty($_POST['EWAY_CARDNUMBER']) && !empty($_POST['EWAY_CARDCVN'])) {
						$this->ccNumber				= wp_unslash($_POST['EWAY_CARDNUMBER']);
						$this->ccCVN				= wp_unslash($_POST['EWAY_CARDCVN']);
					}
					break;

				case 'total':
					$this->hasPurchaseFieldsFlag	= true;
					break;

				case GFEWAY_FIELD_RECURRING:
					// only pick it up if it isn't hidden
					if (!GFFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'))) {
						$this->recurring			= GFEwayRecurringField::getPost($id);
					}
					break;

				default:
					if ($field->type === 'shipping' || $field->type === 'product') {
						$this->hasPurchaseFieldsFlag = true;
					}
					break;

			}
		}

		$entry = GFFormsModel::get_current_lead();
		$this->total = GFCommon::get_order_total($form, $entry);
	}

	/**
	* clean up credit card number, removing spaces and dashes, so that it should only be digits if correctly submitted
	* @param string $ccNumber
	* @return string
	*/
	private static function cleanCcNumber($ccNumber) {
		return strtr($ccNumber, array(' ' => '', '-' => ''));
	}

	/**
	* check whether we're on the last page of the form
	* @return boolean
	*/
	public function isLastPage() {
		return $this->isLastPageFlag;
	}

	/**
	* check whether CC field is hidden (which indicates that payment is being made another way)
	* @return boolean
	*/
	public function isCcHidden() {
		return $this->isCcHiddenFlag;
	}

	/**
	* check whether form has any product fields or a recurring payment field (because CC needs something to bill against)
	* @return boolean
	*/
	public function hasPurchaseFields() {
		return $this->hasPurchaseFieldsFlag || !!$this->recurring;
	}

	/**
	* check whether form a recurring payment field
	* @return boolean
	*/
	public function hasRecurringPayments() {
		return !!$this->recurring;
	}

}
