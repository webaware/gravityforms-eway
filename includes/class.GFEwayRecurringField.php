<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * with thanks to Travis Smith's excellent tutorial:
 * @link http://wpsmith.net/2011/plugins/how-to-create-a-custom-form-field-in-gravity-forms-with-a-terms-of-service-form-field-example/
 */
final class GFEwayRecurringField {

	private static $defaults;

	/**
	 * @param GFEwayPlugin $plugin
	 */
	public function __construct() {
		add_action('init', [$this, 'setDefaults'], 20);

		// WordPress script hooks -- NB: must happen after Gravity Forms registers scripts
		add_action('wp_enqueue_scripts', [$this, 'registerScripts'], 20);
		add_action('admin_enqueue_scripts', [$this, 'registerScripts'], 20);

		// add Gravity Forms hooks
		add_action('gform_enqueue_scripts', [$this, 'gformEnqueueScripts'], 20);
		add_action('gform_editor_js', [$this, 'gformEditorJS']);
		add_action('gform_field_standard_settings', [$this, 'gformFieldStandardSettings']);
		add_filter('gform_add_field_buttons', [$this, 'gformAddFieldButtons']);
		add_filter('gform_field_css_class', [$this, 'gformFieldClasses'], 10, 2);
		add_filter('gform_field_type_title', [$this, 'gformFieldTypeTitle'], 10, 2);
		add_filter('gform_field_input', [$this, 'gformFieldInput'], 10, 5);
		add_filter('gform_pre_validation', [$this, 'gformPreValidation']);
		add_filter('gform_field_validation', [$this, 'gformFieldValidation'], 10, 4);
		add_filter('gform_tooltips', [$this, 'gformTooltips']);
		add_filter('gform_pre_submission', [$this, 'gformPreSubmit']);

		if (is_admin()) {
			add_filter('gform_field_css_class', [$this, 'watchFieldType'], 10, 2);
		}
	}

	/**
	 * set default strings; run this after load_plugin_textdomain()
	 */
	public function setDefaults() : void {
		self::$defaults = [
			'gfeway_initial_amount_label'		=> _x('Initial Amount',   'recurring field', 'gravityforms-eway'),
			'gfeway_recurring_amount_label'		=> _x('Recurring Amount', 'recurring field', 'gravityforms-eway'),
			'gfeway_initial_date_label'			=> _x('Initial Date',     'recurring field', 'gravityforms-eway'),
			'gfeway_start_date_label'			=> _x('Start Date',       'recurring field', 'gravityforms-eway'),
			'gfeway_end_date_label'				=> _x('End Date',         'recurring field', 'gravityforms-eway'),
			'gfeway_interval_type_label'		=> _x('Interval Type',    'recurring field', 'gravityforms-eway'),
		];
	}

	/**
	 * register and enqueue required scripts
	 * NB: must happen after Gravity Forms registers scripts
	 */
	public function registerScripts() : void {
		$min = SCRIPT_DEBUG ? '' : '.min';
		$ver = SCRIPT_DEBUG ? time() : GFEWAY_PLUGIN_VERSION;

		wp_register_script('gfeway_recurring', plugins_url("static/js/recurring$min.js", GFEWAY_PLUGIN_FILE), ['gform_datepicker_init'], $ver, true);
		wp_register_style('gfeway', plugins_url("static/css/style$min.css", GFEWAY_PLUGIN_FILE), [], $ver);
	}

	/**
	 * enqueue additional scripts if required by form
	 * @param array $form
	 */
	public function gformEnqueueScripts($form) : void {
		if (GFEwayPlugin::hasFieldType($form['fields'], GFEWAY_FIELD_RECURRING)) {
			// enqueue script for field
			wp_enqueue_script('gfeway_recurring');

			// add datepicker style if Gravity Forms hasn't done so already
			if (!wp_style_is('gforms_datepicker_css', 'done')) {
				wp_enqueue_style('gforms_datepicker_css', GFCommon::get_base_url() . '/css/datepicker.css', null, GFCommon::$version);
				wp_print_styles(['gforms_datepicker_css']);
			}

			// enqueue default styling
			wp_enqueue_style('gfeway');
		}
	}

	/**
	 * load custom script for editor form
	 */
	public function gformEditorJS() : void {
		$min = SCRIPT_DEBUG ? '' : '.min';

		$strings = [
			'only_one'	=> __('Only one Recurring field can be added to the form', 'gravityforms-eway'),
		];

		echo '<script>';
		printf('var gfeway_editor_admin_strings_recurring = %s;', json_encode($strings));
		readfile(GFEWAY_PLUGIN_ROOT . "static/js/admin-recurring$min.js");
		echo '</script>';
	}

	/**
	 * filter hook for modifying the field buttons on the forms editor
	 */
	public function gformAddFieldButtons(array $field_groups) : array {
		foreach ($field_groups as &$group) {
			if ($group['name'] === 'pricing_fields') {
				$group['fields'][] = [
					'class'		=> 'button',
					'value'		=> _x('Recurring', 'form editor button label', 'gravityforms-eway'),
					'data-type'	=> GFEWAY_FIELD_RECURRING,
					'onclick'	=> sprintf("StartAddField('%s');", GFEWAY_FIELD_RECURRING),
				];
				break;
			}
		}
		return $field_groups;
	}

	/**
	 * filter hook for modifying the field title (e.g. on custom fields)
	 */
	public function gformFieldTypeTitle(string $title, string $field_type) : string {
		if ($field_type === GFEWAY_FIELD_RECURRING) {
			$title = _x('Recurring Payments', 'form editor field label', 'gravityforms-eway');
		}

		return $title;
	}

	/**
	 * add custom fields to form editor
	 */
	public function gformFieldStandardSettings(int $position) : void {
		// add inputs for labels right after the field label input
		if ($position === 25) {
			require GFEWAY_PLUGIN_ROOT . 'views/admin-recurring-field-settings.php';
		}
	}

	/**
	 * add custom tooltips for fields on form editor
	 */
	public function gformTooltips(array $tooltips) : array {
		$tooltips['gfeway_initial_setting']			= sprintf('<h6>%s</h6>%s',
														_x('Show Initial Amount', 'form editor tooltip heading', 'gravityforms-eway'),
														__('Select this option to show Initial Amount and Initial Date fields.', 'gravityforms-eway'));
		$tooltips['gfeway_initial_amount_label']	= sprintf('<h6>%s</h6>%s',
														_x('Initial Amount', 'form editor tooltip heading', 'gravityforms-eway'),
														__('The label shown for the Initial Amount field.', 'gravityforms-eway'));
		$tooltips['gfeway_initial_date_label']		= sprintf('<h6>%s</h6>%s',
														_x('Initial Date', 'form editor tooltip heading', 'gravityforms-eway'),
														__('The label shown for the Initial Date field.', 'gravityforms-eway'));
		$tooltips['gfeway_recurring_amount_label']	= sprintf('<h6>%s</h6>%s',
														_x('Recurring Amount', 'form editor tooltip heading', 'gravityforms-eway'),
														__('The label shown for the Recurring Amount field.', 'gravityforms-eway'));
		$tooltips['gfeway_recurring_date_start']	= sprintf('<h6>%s</h6>%s',
														_x('Show Start Date', 'form editor tooltip heading', 'gravityforms-eway'),
														__('Select this option to show the Start Date field.', 'gravityforms-eway'));
		$tooltips['gfeway_recurring_date_end']		= sprintf('<h6>%s</h6>%s',
														_x('Show End Date', 'form editor tooltip heading', 'gravityforms-eway'),
														__('Select this option to show the End Date field.', 'gravityforms-eway'));
		$tooltips['gfeway_start_date_label']		= sprintf('<h6>%s</h6>%s',
														_x('Start Date', 'form editor tooltip heading', 'gravityforms-eway'),
														__('The label shown for the Start Date field.', 'gravityforms-eway'));
		$tooltips['gfeway_end_date_label']			= sprintf('<h6>%s</h6>%s',
														_x('End Date', 'form editor tooltip heading', 'gravityforms-eway'),
														__('The label shown for the End Date field.', 'gravityforms-eway'));
		$tooltips['gfeway_interval_type_label']		= sprintf('<h6>%s</h6>%s',
														_x('Interval Type', 'form editor tooltip heading', 'gravityforms-eway'),
														__('The label shown for the Interval Type field.', 'gravityforms-eway'));

		return $tooltips;
	}

	/**
	 * grab values and concatenate into a string before submission is accepted
	 */
	public function gformPreSubmit(array $form) : void {
		foreach ($form['fields'] as $field) {
			if ($field->type === GFEWAY_FIELD_RECURRING && !GFFormsModel::is_field_hidden($form, $field, GFForms::post('gform_field_values'))) {
				$recurring = self::getPost($field->id);
				if ($recurring) {
					$_POST["input_{$field->id}"] = $this->getRecurringDescription($recurring);
				}
			}
		}
	}

	/**
	 * prime the inputs that will be checked by standard validation tests,
	 * e.g. so that "required" fields don't fail
	 */
	public function gformPreValidation(array $form) : array {
		foreach($form['fields'] as $field) {
			if ($field->type === GFEWAY_FIELD_RECURRING && !GFFormsModel::is_field_hidden($form, $field, GFForms::post('gform_field_values'))) {
				$recurring = self::getPost($field->id);
				if ($recurring) {
					$_POST["input_{$field->id}"] = $this->getRecurringDescription($recurring);
				}
			}
		}

		return $form;
	}

	/**
	 * formulate a single string representing the recurring payment
	 */
	private function getRecurringDescription(array $recurring) : string {
		$interval = _x($recurring['intervalTypeDesc'], 'recurring interval label', 'gravityforms-eway');

		// format payment amount as currency
		if (!empty($recurring['amountRecur'])) {
			$amountRecur = GFCommon::format_number($recurring['amountRecur'], 'currency');
		}
		else {
			$amountRecur = '';
		}

		$desc = '';
		if ($recurring['dateStart'] && $recurring['dateEnd']) {
			if ($recurring['dateEnd']->format('Y-m-d') === '2099-12-31') {
				// start but no defined end
				// translators: %1$s = amount; %2$s = interval; %3$s = start date
				$desc = sprintf(_x('%1$s %2$s from %3$s', 'recurring payment description', 'gravityforms-eway'),
							$amountRecur, $interval, $recurring['dateStart']->format(get_option('date_format')));
			}
			else {
				// defined start and end
				// translators: %1$s = amount; %2$s = interval; %3$s = start date; %4$s = end date
				$desc = sprintf(_x('%1$s %2$s from %3$s until %4$s', 'recurring payment description', 'gravityforms-eway'),
							$amountRecur, $interval, $recurring['dateStart']->format(get_option('date_format')), $recurring['dateEnd']->format(get_option('date_format')));
			}
		}

		return $desc;
	}

	/**
	 * validate inputs
	 * @param string $value
	 */
	public function gformFieldValidation(array $validation_result, $value, array $form, GF_Field $field) : array {
		if ($field->type === GFEWAY_FIELD_RECURRING) {
			if (!GFFormsModel::is_field_hidden($form, $field, GFForms::post('gform_field_values'))) {
				// get the real values
				$value = self::getPost($field->id);

				if (!is_array($value)) {
					$validation_result['is_valid']	= false;
					$validation_result['message']	= __('This field is required', 'gravityforms-eway');
				}

				else {
					$messages = [];

					if ($value['amountInit'] === false || $value['amountInit'] < 0) {
						$messages[] = __('Please enter a valid initial amount.', 'gravityforms-eway');
					}

					if (empty($value['dateInit'])) {
						$messages[] = __('Please enter a valid initial date in the format dd/mm/yyyy', 'gravityforms-eway');
					}

					if (empty($value['amountRecur']) || $value['amountRecur'] < 0) {
						$messages[] = __('Please enter a valid recurring amount', 'gravityforms-eway');
					}

					if (empty($value['dateStart'])) {
						$messages[] = __('Please enter a valid start date in the format dd/mm/yyyy', 'gravityforms-eway');
					}

					if (empty($value['dateEnd'])) {
						$messages[] = __('Please enter a valid end date in the format dd/mm/yyyy', 'gravityforms-eway');
					}

					if ($value['intervalType'] === -1) {
						$messages[] = __('Please select a valid interval type', 'gravityforms-eway');
					}

					if (count($messages) > 0) {
						$validation_result['is_valid'] = false;
						$validation_result['message'] = implode("<br />\n", $messages);
					}
				}
			}
		}

		return $validation_result;
	}

	/**
	 * add custom classes to field container
	 */
	public function gformFieldClasses(string $classes, GF_Field $field) : string {
		if ($field->type === GFEWAY_FIELD_RECURRING) {
			$classes .= ' gfeway-contains-recurring';
		}

		return $classes;
	}

	/**
	 * watch the field type so that we can use hooks that don't pass enough information
	 */
	public function watchFieldType(string $classes, GF_Field $field) : string {
		// if field type matches, add filters that don't allow testing for field type
		if ($field->type === GFEWAY_FIELD_RECURRING) {
			add_filter('gform_duplicate_field_link', [$this, 'gformDuplicateFieldLink']);
		}

		return $classes;
	}

	/**
	 * filter the field duplication link, we don't want one for this field type
	 * @param string $duplicate_field_link
	 */
	public function gformDuplicateFieldLink($duplicate_field_link) : string {
		// remove filter once called, only process current field
		remove_filter('gform_duplicate_field_link', [$this, __FUNCTION__]);

		// erase duplicate field link for this field
		return '';
	}

	/**
	 * filter hook for modifying a field's input tag (e.g. on custom fields)
	 * @param string $input the input tag before modification
	 * @param GF_Field $field
	 * @param string $value
	 * @param integer $lead_id
	 * @param integer $form_id
	 * @return string
	 */
	public function gformFieldInput($input, $field, $value, $lead_id, $form_id) {
		if ($field->type === GFEWAY_FIELD_RECURRING) {

			// pick up the real value
			$value = rgpost('gfeway_' . $field->id);

			$css = isset($field->cssClass) ? esc_attr($field->cssClass) : '';

			$today				= date_create('now', timezone_open('Australia/Sydney'));
			$initial_amount		= empty($value[1]) ? '0.00' : $value[1];
			$initial_date		= empty($value[2]) ? $today->format('d-m-Y') : $value[2];
			$recurring_amount	= empty($value[3]) ? '0.00' : $value[3];
			$start_date			= empty($value[4]) ? $today->format('d-m-Y') : $value[4];
			$end_date			= empty($value[5]) ? '31-12-2099' : $value[5];
			$interval_type		= empty($value[6]) ? 'monthly' : $value[6];

			$input = "<div class='ginput_complex ginput_container gfeway_recurring_complex $css' id='input_{$field->id}'>";

			// initial amount
			$sub_field = [
				'type'			=> 'donation',
				'id'			=> $field->id,
				'sub_id'		=> '1',
				'label'			=> empty($field->gfeway_initial_amount_label) ? self::$defaults['gfeway_initial_amount_label'] : $field->gfeway_initial_amount_label,
				'isRequired'	=> false,
				'size'			=> 'medium',
				'label_class'	=> 'gfeway_initial_amount_label',
				'hidden'		=> (isset($field->gfeway_initial_setting) ? !$field->gfeway_initial_setting : false),
			];
			$input .= $this->fieldDonation($sub_field, $initial_amount, $lead_id, $form_id);

			// initial date
			$sub_field = [
				'type'			=> 'date',
				'id'			=> $field->id,
				'sub_id'		=> '2',
				'label'			=> empty($field->gfeway_initial_date_label) ? self::$defaults['gfeway_initial_date_label'] : $field->gfeway_initial_date_label,
				'dateFormat'	=> 'dmy',
				'dateType'		=> 'datepicker',
				'dateMin'		=> '+0',
				'dateMax'		=> '+2Y',
				'calendarIconType' => 'calendar',
				'isRequired'	=> false,
				'size'			=> 'medium',
				'label_class'	=> 'gfeway_initial_date_label',
				'hidden'		=> (isset($field->gfeway_initial_setting) ? !$field->gfeway_initial_setting : false),
			];
			$input .= $this->fieldDate($sub_field, $initial_date, $lead_id, $form_id);

			$input .= '<br />';

			// recurring amount
			$sub_field = [
				'type'			=> 'donation',
				'id'			=> $field->id,
				'sub_id'		=> '3',
				'label'			=> empty($field->gfeway_recurring_amount_label) ? self::$defaults['gfeway_recurring_amount_label'] : $field->gfeway_recurring_amount_label,
				'isRequired'	=> true,
				'size'			=> 'medium',
				'label_class'	=> 'gfeway_recurring_amount_label',
			];
			$input .= $this->fieldDonation($sub_field, $recurring_amount, $lead_id, $form_id);

			// start date
			$sub_field = [
				'type'			=> 'date',
				'id'			=> $field->id,
				'sub_id'		=> '4',
				'label'			=> empty($field->gfeway_start_date_label) ? self::$defaults['gfeway_start_date_label'] : $field->gfeway_start_date_label,
				'dateFormat'	=> 'dmy',
				'dateType'		=> 'datepicker',
				'dateMin'		=> '+0',
				'dateMax'		=> '+2Y',
				'calendarIconType' => 'calendar',
				'isRequired'	=> true,
				'size'			=> 'medium',
				'label_class'	=> 'gfeway_start_date_label',
				'hidden'		=> (empty($field->gfeway_recurring_date_start) && empty($field->gfeway_recurring_date_setting)),
			];
			$input .= $this->fieldDate($sub_field, $start_date, $lead_id, $form_id);

			// end date
			$sub_field = [
				'type'			=> 'date',
				'id'			=> $field->id,
				'sub_id'		=> '5',
				'label'			=> empty($field->gfeway_end_date_label) ? self::$defaults['gfeway_end_date_label'] : $field->gfeway_end_date_label,
				'dateFormat'	=> 'dmy',
				'dateType'		=> 'datepicker',
				'dateMin'		=> '+0',
				'dateMax'		=> '2099-12-31',
				'calendarIconType' => 'calendar',
				'isRequired'	=> true,
				'size'			=> 'medium',
				'label_class'	=> 'gfeway_end_date_label',
				'hidden'		=> (empty($field->gfeway_recurring_date_end) && empty($field->gfeway_recurring_date_setting)),
			];
			$input .= $this->fieldDate($sub_field, $end_date, $lead_id, $form_id);

			$input .= '<br />';

			// recurrance interval type drop-down
			$sub_field = [
				'type'			=> 'number',
				'id'			=> $field->id,
				'sub_id'		=> '6',
				'label'			=> empty($field->gfeway_interval_type_label) ? self::$defaults['gfeway_interval_type_label'] : $field->gfeway_interval_type_label,
				'isRequired'	=> true,
				'size'			=> 'medium',
				'label_class'	=> 'gfeway_interval_type_label',
			];
			$input .= $this->fieldIntervalType($sub_field, $interval_type, $lead_id, $form_id);

			// concatenated value added to database
			$sub_field = [
				'type'			=> 'hidden',
				'id'			=> $field->id,
				'isRequired'	=> true,
			];
			$input .= $this->fieldConcatenated($sub_field, $interval_type, $lead_id, $form_id);

			$input .= '</div>';
		}

		return $input;
	}

	/**
	 * get HTML for input and label for date field (as date picker)
	 * @param array $field
	 * @param string $value
	 * @param integer $lead_id
	 * @param integer $form_id
	 * @return string
	 */
	private function fieldDate($field, $value = '', $lead_id = 0, $form_id = 0) {
		$current_view	= GFForms::get('view');

		$id				= $field['id'];
		$sub_id			= $field['sub_id'];
		$field_id		= IS_ADMIN || $form_id === 0 ? "gfeway_{$id}_{$sub_id}" : "gfeway_{$form_id}_{$id}_{$sub_id}";
		$form_id		= IS_ADMIN && empty($form_id) ? rgget('id') : $form_id;

		$format			= empty($field['dateFormat']) ? 'dmy' : esc_attr($field['dateFormat']);
		$size			= rgar($field, 'size');
		$disabled_text	= (IS_ADMIN && $current_view !== 'entry') ? 'disabled="disabled"' : '';
		$class_suffix	= $current_view === 'entry' ? '_admin' : '';

		$value			= GFCommon::date_display($value, $format);
		$icon_class		= $field['calendarIconType'] === 'none' ? 'datepicker_no_icon' : 'datepicker_with_icon';
		$icon_url		= empty($field['calendarIconUrl']) ? GFCommon::get_base_url() . '/images/calendar.png' : $field['calendarIconUrl'];
		$tabindex		= GFCommon::get_tabindex();

		$inputClass		= [$size . $class_suffix, $format, $icon_class];
		$spanClass		= ['gfeway_recurring_left', 'gfeway_recurring_date'];

		if (empty($field['hidden'])) {
			$inputClass[] = 'datepicker';
		}
		else {
			$spanClass[] = 'gf_hidden';
		}

		$dataMin = '';
		if (!empty($field['dateMin'])) {
			$dataMin = sprintf('data-gfeway-minDate="%s"', esc_attr($field['dateMin']));
		}

		$dataMax = '';
		if (!empty($field['dateMax'])) {
			$dataMax = sprintf('data-gfeway-maxDate="%s"', esc_attr($field['dateMax']));
		}

		$value			= esc_attr($value);
		$spanClass		= esc_attr(implode(' ', $spanClass));
		$inputClass		= esc_attr(implode(' ', $inputClass));
		$inputName		= sprintf('gfeway_%s[%s]', $id, $sub_id);

		$label			= esc_html($field['label']);

		ob_start();
		require GFEWAY_PLUGIN_ROOT . 'views/recurring-field-input-date.php';
		$input = ob_get_clean();

		return $input;
	}

	/**
	 * get HTML for input and label for donation (amount) field
	 * @param array $field
	 * @param string $value
	 * @param integer $lead_id
	 * @param integer $form_id
	 * @return string
	 */
	private function fieldDonation($field, $value = '', $lead_id = 0, $form_id = 0) {
		$current_view	= GFForms::get('view');

		$id				= $field['id'];
		$sub_id			= $field['sub_id'];
		$field_id		= IS_ADMIN || $form_id === 0 ? "gfeway_{$id}_{$sub_id}" : "gfeway_{$form_id}_{$id}_{$sub_id}";
		$form_id		= IS_ADMIN && empty($form_id) ? rgget('id') : $form_id;

		$size			= rgar($field, 'size');
		$disabled_text	= (IS_ADMIN && $current_view !== 'entry') ? 'disabled="disabled"' : '';
		$class_suffix	= $current_view === 'entry' ? '_admin' : '';
		$class			= $size . $class_suffix;

		$tabindex		= GFCommon::get_tabindex();

		$spanClass		= '';
		if (!empty($field['hidden'])) {
			$spanClass	= 'gf_hidden';
		}

		$value			= esc_attr($value);
		$class			= esc_attr($class);
		$inputName		= sprintf('gfeway_%s[%s]', $id, $sub_id);

		$label			= esc_html($field['label']);

		ob_start();
		require GFEWAY_PLUGIN_ROOT . 'views/recurring-field-input-donation.php';
		$input = ob_get_clean();

		return $input;
	}

	/**
	 * get HTML for input and label for Interval Type field
	 * @param array $field
	 * @param string $value
	 * @param integer $lead_id
	 * @param integer $form_id
	 * @return string
	 */
	private function fieldIntervalType($field, $value = '', $lead_id = 0, $form_id = 0) {
		$current_view	= GFForms::get('view');

		$id				= $field['id'];
		$sub_id			= $field['sub_id'];
		$field_id		= IS_ADMIN || $form_id === 0 ? "gfeway_{$id}_{$sub_id}" : "gfeway_{$form_id}_{$id}_{$sub_id}";
		$form_id		= IS_ADMIN && empty($form_id) ? rgget('id') : $form_id;

		$size			= rgar($field, 'size');
		$disabled_text	= (IS_ADMIN && $current_view !== 'entry') ? 'disabled="disabled"' : '';
		$class_suffix	= $current_view === 'entry' ? '_admin' : '';
		$class			= $size . $class_suffix;

		$tabindex		= GFCommon::get_tabindex();

		$spanClass		= '';
		if (!empty($field['hidden'])) {
			$spanClass	= 'gf_hidden';
		}

		$class			= esc_attr($class);
		$inputName		= sprintf('gfeway_%s[%s]', $id, $sub_id);

		$label			= esc_html($field['label']);

		$interval_labels = [
			'weekly'		=> _x('weekly', 'recurring interval label', 'gravityforms-eway'),
			'fortnightly'	=> _x('fortnightly', 'recurring interval label', 'gravityforms-eway'),
			'monthly'		=> _x('monthly', 'recurring interval label', 'gravityforms-eway'),
			'quarterly'		=> _x('quarterly', 'recurring interval label', 'gravityforms-eway'),
			'yearly'		=> _x('yearly', 'recurring interval label', 'gravityforms-eway'),
		];
		$intervals = apply_filters('gfeway_recurring_periods', array_keys($interval_labels), $form_id, $field);

		ob_start();
		if (count($intervals) === 1) {
			// build a hidden field and label
			$label = sprintf('%s: %s', $label, $interval_labels[$intervals[0]]);
			require GFEWAY_PLUGIN_ROOT . 'views/recurring-field-hidden-interval.php';
		}
		else {
			// build a drop-down list
			require GFEWAY_PLUGIN_ROOT . 'views/recurring-field-select-interval.php';
		}
		$input = ob_get_clean();

		return $input;
	}

	/**
	 * get HTML for hidden input with concatenated value for complex field
	 * @param array $field
	 * @param string $value
	 * @param integer $lead_id
	 * @param integer $form_id
	 * @return string
	 */
	private function fieldConcatenated($field, $value = '', $lead_id = 0, $form_id = 0) {
		$id				= $field['id'];
		$field_id		= IS_ADMIN || $form_id === 0 ? "input_{$id}" : "input_{$form_id}_{$id}";
		$form_id		= IS_ADMIN && empty($form_id) ? rgget('id') : $form_id;

		$input = "<input type='hidden' name='input_{$id}' id='$field_id' />";

		return $input;
	}

	/**
	 * safe checkdate function that verifies each component as numeric and not empty, before calling PHP's function
	 * @param string $month
	 * @param string $day
	 * @param string $year
	 * @return boolean
	 */
	private static function checkdate($month, $day, $year) {
		if (empty($month) || !is_numeric($month) || empty($day) || !is_numeric($day) || empty($year) || !is_numeric($year) || strlen($year) !== 4)
			return false;

		return checkdate($month, $day, $year);
	}

	/**
	 * get input values for recurring payments field
	 * @param integer $field_id
	 * @return array
	 */
	public static function getPost($field_id) {
		$recurring = rgpost('gfeway_' . $field_id);

		if (is_array($recurring)) {
			$intervalSize = 1;

			switch ($recurring[6]) {
				case 'weekly':
					$intervalType = GFEwayRecurringPayment::WEEKS;
					break;

				case 'fortnightly':
					$intervalType = GFEwayRecurringPayment::WEEKS;
					$intervalSize = 2;
					break;

				case 'monthly':
					$intervalType = GFEwayRecurringPayment::MONTHS;
					break;

				case 'quarterly':
					$intervalType = GFEwayRecurringPayment::MONTHS;
					$intervalSize = 3;
					break;

				case 'yearly':
					$intervalType = GFEwayRecurringPayment::YEARS;
					break;

				default:
					// invalid or not selected
					$intervalType = -1;
					break;
			}

			$today = date_create('now', timezone_open('Australia/Sydney'));

			$recurring = [
				'amountInit'			=> empty($recurring[1]) ? 0 : GFCommon::to_number($recurring[1]),
				'dateInit'				=> empty($recurring[2]) ? $today : self::parseDate($recurring[2]),
				'amountRecur'			=> GFCommon::to_number($recurring[3]),
				'dateStart'				=> empty($recurring[4]) ? $today : self::parseDate($recurring[4]),
				'dateEnd'				=> self::parseDate(empty($recurring[5]) ? '31/12/2099' : $recurring[5]),
				'intervalSize'			=> $intervalSize,
				'intervalType'			=> $intervalType,
				'intervalTypeDesc'		=> $recurring[6],
			];
		}
		else {
			$recurring = false;
		}

		return $recurring;
	}

	/**
	 * no date_create_from_format before PHP 5.3, so roll-your-own
	 * @param string $value date value in dd/mm/yyyy format
	 * @return DateTime
	 */
	private static function parseDate($value) {
		if (preg_match('#([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})#', $value, $matches)) {
			$date = date_create();
			$date->setDate($matches[3], $matches[2], $matches[1]);
			return $date;
		}

		return false;
	}

}
