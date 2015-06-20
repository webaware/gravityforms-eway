<?php

/**
* class for managing form data
*/
class GFEwayFormData {

	public $amount					= 0;
	public $shipping				= 0;
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
	public $address					= '';						// simple address, for regular payments
	public $address_street			= '';						// street address, for recurring payments
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
        $current_page	= GFFormDisplay::get_source_page($form['id']);
        $target_page	= GFFormDisplay::get_target_page($form, $current_page, rgpost('gform_field_values'));
        $this->isLastPageFlag = ($target_page == 0);

		// load the form data
		$this->loadForm($form);
	}

	/**
	* load the form data we care about from the form array
	* @param array $form
	*/
	private function loadForm(&$form) {
		foreach ($form['fields'] as &$field) {
			$id = $field['id'];

			switch(GFFormsModel::get_input_type($field)){
				case 'name':
					// only pick up the first name field (assume later ones are additional info)
					if (empty($this->firstName) && empty($this->lastName)) {
						$this->namePrefix			= rgpost("input_{$id}_2");
						$this->firstName			= rgpost("input_{$id}_3");
						$this->lastName				= rgpost("input_{$id}_6");
					}
					break;

				case 'email':
					// only pick up the first email address field (assume later ones are additional info)
					if (empty($this->email))
						$this->email = rgpost("input_{$id}");
					break;

				case 'phone':
					// only pick up the first phone number field (assume later ones are additional info)
					if (empty($this->phone))
						$this->phone = rgpost("input_{$id}");
					break;

				case 'address':
					// only pick up the first address field (assume later ones are additional info, e.g. shipping)
					if (empty($this->address) && empty($this->postcode)) {
						$parts = array(trim(rgpost("input_{$id}_1")), trim(rgpost("input_{$id}_2")));
						$this->address_street		= implode(', ', array_filter($parts, 'strlen'));
						$this->address_suburb		= trim(rgpost("input_{$id}_3"));
						$this->address_state		= trim(rgpost("input_{$id}_4"));
						$this->address_country		= trim(rgpost("input_{$id}_6"));
						$this->postcode				= trim(rgpost("input_{$id}_5"));

						// aggregate street, city, state, country into a single string (for regular one-off payments)
						$parts = array($this->address_street, $this->address_suburb, $this->address_state, $this->address_country);
						$this->address = implode(', ', array_filter($parts, 'strlen'));
					}
					break;

				case 'creditcard':
					$this->isCcHiddenFlag			= GFFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'));
					$this->ccField					=& $field;
					$this->ccName					= rgpost("input_{$id}_5");
					$this->ccNumber					= self::cleanCcNumber(rgpost("input_{$id}_1"));
					$ccExp							= rgpost("input_{$id}_2");
					if (is_array($ccExp)) {
						list($this->ccExpMonth, $this->ccExpYear) = $ccExp;
					}
					$this->ccCVN					= rgpost("input_{$id}_3");
					break;

				case 'total':
					$this->total					= GFCommon::to_number(rgpost("input_{$id}"));
					$this->hasPurchaseFieldsFlag	= true;
					break;

				case GFEWAY_FIELD_RECURRING:
					// only pick it up if it isn't hidden
					if (!GFFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'))) {
						$this->recurring			= GFEwayRecurringField::getPost($id);
					}
					break;

				default:
					// check for shipping field
					if ($field['type'] == 'shipping') {
						$this->shipping += self::getShipping($form, $field);
						$this->hasPurchaseFieldsFlag = true;
					}
					// check for product field
					elseif (in_array($field['type'], array('option', 'donation', 'product', 'calculation'))) {
						$this->amount += self::getProductPrice($form, $field);
						$this->hasPurchaseFieldsFlag = true;
					}
					break;
			}
		}

		// if form didn't pass the total, use sum of the product and shipping fields
		if ($this->total === 0) {
			$this->total = $this->amount + $this->shipping;
		}
	}

	/**
	* extract the price from a product field, and multiply it by the quantity
	* @return float
	*/
	private static function getProductPrice($form, $field) {
		$price = $qty = 0;
		$isProduct = false;
		$id = $field['id'];

		if (!GFFormsModel::is_field_hidden($form, $field, array())) {
			$lead_value = rgpost("input_{$id}");

			// look for a quantity field for product
			$qty_fields = GFCommon::get_product_fields_by_type($form, array('quantity'), $id);
			if (empty($qty_fields)) {
				$qty_field = false;
				$qty = 1;
			}
			else {
				$qty_field = $qty_fields[0];
				$qty = (float) rgpost("input_{$qty_field['id']}");
			}

			switch ($field["inputType"]) {
				case 'singleproduct':
				case 'hiddenproduct':
					$price = GFCommon::to_number(rgpost("input_{$id}_2"));
					if (!$qty_field) {
						// no quantity field, pick it up from input
						$qty = (float) GFCommon::to_number(rgpost("input_{$id}_3"));
					}
					$isProduct = true;
					break;

				case 'donation':
				case 'price':
					$price = GFCommon::to_number($lead_value);
					$isProduct = true;
					break;

				default:
					// handle drop-down lists
					if (!empty($lead_value)) {
						list($name, $price) = rgexplode('|', $lead_value, 2);
						$isProduct = true;
					}
					break;
			}

			// pick up extra costs from any options
			if ($isProduct) {
				$options = GFCommon::get_product_fields_by_type($form, array('option'), $id);
				foreach($options as $option){
					if (!GFFormsModel::is_field_hidden($form, $option, array())) {
						$option_value = rgpost("input_{$option['id']}");

						if (is_array(rgar($option, 'inputs'))) {
							foreach($option['inputs'] as $input){
								$input_value = rgpost('input_' . str_replace('.', '_', $input['id']));
								$option_info = GFCommon::get_option_info($input_value, $option, true);
								if(!empty($option_info))
									$price += GFCommon::to_number(rgar($option_info, 'price'));
							}
						}
						elseif (!empty($option_value)){
							$option_info = GFCommon::get_option_info($option_value, $option, true);
							$price += GFCommon::to_number(rgar($option_info, 'price'));
						}
					}
				}

				$price *= $qty;
			}

		}

		return $price;
	}

	/**
	* extract the shipping amount from a shipping field
	* @return float
	*/
	private static function getShipping($form, $field) {
		$shipping = 0;
		$id = $field['id'];

		if (!GFFormsModel::is_field_hidden($form, $field, array())) {
			$value = rgpost("input_{$id}");

			if (!empty($value) && $field["inputType"] != 'singleshipping') {
				// drop-down list / radio buttons
				list($name, $value) = rgexplode('|', $value, 2);
			}

			$shipping = GFCommon::to_number($value);
		}

		return $shipping;
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
