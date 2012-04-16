<?php

/**
* class for managing form data
*/
class GFEwayFormData {

	public $amount = 0;
	public $total = 0;
	public $ccName = '';
	public $ccNumber = '';
	public $ccExpMonth = '';
	public $ccExpYear = '';
	public $ccCVN = '';
	public $email = '';
	public $address = '';
	public $postcode = '';
	public $ccField = FALSE;					// handle to meta-"field" for credit card in form

	private $isLastPageFlag = FALSE;

	/**
	* initialise instance
	* @param array $form
	*/
	public function __construct(&$form) {
		// check for last page
        $current_page = GFFormDisplay::get_source_page($form['id']);
        $target_page = GFFormDisplay::get_target_page($form, $current_page, rgpost('gform_field_values'));
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
			$fieldName = empty($field['adminLabel']) ? $field['label'] : $field['adminLabel'];
			$id = $field['id'];

			switch(RGFormsModel::get_input_type($field)){
				case 'email':
					// only pick up the first email address (assume later ones are additional info)
					if (empty($this->email))
						$this->email = rgpost("input_{$id}");
					break;

				case 'address':
					// only pick up the first address (assume later ones are additional info, e.g. shipping)
					if (empty($this->address) || empty($this->postcode)) {
						// street, city, state, country
						$parts = array();
						foreach (array(1, 3, 4, 6) as $subID) {
							$value = rgpost("input_{$id}_$subID");
							if (!empty($value))
								$parts[] = $value;
						}
						$this->address = implode(', ', $parts);
						$this->postcode = rgpost("input_{$id}_5");
					}
					break;

				case 'creditcard':
					$this->ccField =& $field;
					$this->ccName = rgpost("input_{$id}_5");
					$this->ccNumber = rgpost("input_{$id}_1");
					$ccExp = rgpost("input_{$id}_2");
					if (is_array($ccExp))
						list($this->ccExpMonth, $this->ccExpYear) = $ccExp;
					$this->ccCVN = rgpost("input_{$id}_3");
					break;

				case 'total':
					$this->total = GFCommon::to_number(rgpost("input_{$id}"));
					break;

				default:
					// check for product field
					if ($field['type'] == 'product') {
						$this->amount += self::getProductPrice($form, $field);
					}
					break;
			}
		}

		// TODO: shipping?

		// if form didn't pass the total, pick it up from calculated amount
		if ($this->total == 0)
			$this->total = $this->amount;
	}

	/**
	* extract the price from a product field, and multiply it by the quantity
	* @return float
	*/
	private static function getProductPrice($form, $field) {
		$price = $qty = 0;
		$isProduct = false;
		$id = $field['id'];

		if (!RGFormsModel::is_field_hidden($form, $field, array())) {
			$lead_value = rgpost("input_{$id}");

			$qty_field = GFCommon::get_product_fields_by_type($form, array('quantity'), $id);
			$qty = sizeof($qty_field) > 0 ? rgpost("input_{$qty_field[0]['id']}") : 1;

			switch ($field["inputType"]) {
				case 'singleproduct':
					$price = GFCommon::to_number(rgpost("input_{$id}_2"));
					$isProduct = true;
					break;

				case 'hiddenproduct':
					$price = GFCommon::to_number($field["basePrice"]);
					$isProduct = true;
					break;

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
					if (!RGFormsModel::is_field_hidden($form, $option, array())) {
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
			}

			$price *= $qty;
		}

		return $price;
	}

	/**
	* check whether we're on the last page of the form
	* @return boolean
	*/
	public function isLastPage() {
		return $this->isLastPageFlag;
	}
}
