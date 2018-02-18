/*! gravityforms-eway: form editor for Recurring Payments field */

// initialise form on page load
(function($) {

	var TYPE_RECURRING = "gfewayrecurring";

	// add required classes to the field on the admin form
	fieldSettings.gfewayrecurring = ".conditional_logic_field_setting, .error_message_setting, .label_setting, .admin_label_setting, .rules_setting, .description_setting, .css_class_setting, .gfewayrecurring_setting";

	// binding to the load field settings event to initialize custom inputs
	$(document).on("gform_load_field_settings", function(event, field /* , form */) {

		$("#gfeway_initial_setting").prop("checked", field.gfeway_initial_setting);
		setFieldsVisibility("#gfeway_initial_fields", field.gfeway_initial_setting);

		// NB: for backwards compatibility, check for combined start/end setting
		var dateStart = field.gfeway_recurring_date_start || field.gfeway_recurring_date_setting;
		$("#gfeway_recurring_date_start").prop("checked", dateStart);
		setFieldsVisibility("#gfeway_recurring_start_date_fields", dateStart);

		// NB: for backwards compatibility, check for combined start/end setting
		var dateEnd = field.gfeway_recurring_date_end || field.gfeway_recurring_date_setting;
		$("#gfeway_recurring_date_end").prop("checked", dateEnd);
		setFieldsVisibility("#gfeway_recurring_end_date_fields", dateEnd);

		$("#gfeway_initial_amount_label").val(field.gfeway_initial_amount_label);
		$("#gfeway_recurring_amount_label").val(field.gfeway_recurring_amount_label);
		$("#gfeway_initial_date_label").val(field.gfeway_initial_date_label);
		$("#gfeway_start_date_label").val(field.gfeway_start_date_label);
		$("#gfeway_end_date_label").val(field.gfeway_end_date_label);
		$("#gfeway_interval_type_label").val(field.gfeway_interval_type_label);

	});

	/**
	* toggle whether to show the Initial Amount and Initial Date fields
	*/
	$("#gfeway_initial_setting").on("change", function() {
		SetFieldProperty(this.id, this.checked);
		setFieldsVisibility("#gfeway_initial_fields", this.checked);
	});

	/**
	* toggle whether to show the Start Date field
	*/
	$("#gfeway_recurring_date_start").on("change", function() {
		SetFieldProperty(this.id, this.checked);

		// cleanup old combined start/end setting
		var field = GetSelectedField();
		if ("gfeway_recurring_date_setting" in field) {
			field.gfeway_recurring_date_end = !!field.gfeway_recurring_date_setting;
			delete(field.gfeway_recurring_date_setting);
		}

		setFieldsVisibility("#gfeway_recurring_start_date_fields", this.checked);
	});

	/**
	* toggle whether to show the End Date field
	*/
	$("#gfeway_recurring_date_end").on("change", function() {
		SetFieldProperty(this.id, this.checked);

		// cleanup old combined start/end setting
		var field = GetSelectedField();
		if ("gfeway_recurring_date_setting" in field) {
			field.gfeway_recurring_date_start = !!field.gfeway_recurring_date_setting;
			delete(field.gfeway_recurring_date_setting);
		}

		setFieldsVisibility("#gfeway_recurring_end_date_fields", this.checked);
	});

	/**
	* set the label on a subfield in the recurring field
	*/
	$("li.gfewayrecurring_setting").on("keyup", "input.gfeway-field-label", function() {
		var newLabel = this.value;

		// if new label value is empty, pick up the default value instead
		if (!(/\S/.test(newLabel))) {
			newLabel = $(this).data("gfeway-field-label");
		}

		// set the new label, and record for the field
		$("." + this.id).text(newLabel);
		SetFieldProperty(this.id, newLabel);
	});

	/**
	* prevent multiple instances of Recurring field on form
	* @param {bool} can_be_added
	* @param {String} field_type
	* @return {bool}
	*/
	gform.addFilter("gform_form_editor_can_field_be_added", function(can_be_added, field_type) {
		if (field_type === TYPE_RECURRING && GetFieldsByType([field_type]).length > 0) {
			window.alert(gfeway_editor_admin_strings_recurring.only_one);
			return false;
		}

		return can_be_added;
	});

	/**
	* set visibility of selected fields
	* @param {String} selector
	* @param {bool} show
	*/
	function setFieldsVisibility(selector, show) {
		var fields = $(selector);

		if (show) {
			fields.slideDown();
		}
		else {
			fields.slideUp();
		}
	}

})(jQuery);
