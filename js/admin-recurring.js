/*!
WordPress plugin gravityforms-eway
copyright (c) 2012 WebAware Pty Ltd, released under LGPL v2.1
form editor for Recurring Payments field
*/

// create namespace to avoid collisions
var GFEwayRecurring = (function() {

	var	$ = jQuery;

	return {
		/**
		* set the label on a subfield in the recurring field
		* @param {HTMLElement} field
		* @param {String} defaultValue
		*/
		SetFieldLabel : function(field, defaultValue) {
			var newLabel = field.value;

			// if new label value is empty, pick up the default value instead
			if (!(/\S/.test(newLabel)))
				newLabel = defaultValue;

			// set the new label, and record for the field
			$("." + field.id).text(newLabel);
			SetFieldProperty(field.id, newLabel);
		},

		/**
		* toggle whether to show the Initial Amount and Initial Date fields
		* @param {HTMLElement} field
		*/
		ToggleInitialSetting : function(field) {
			SetFieldProperty(field.id, field.checked);
			if (field.checked) {
				$("#gfeway_initial_fields").slideDown();
			}
			else {
				$("#gfeway_initial_fields").slideUp();
			}
		},

		/**
		* toggle whether to show the Start Date and End Date fields
		* @param {HTMLElement} field
		*/
		ToggleRecurringDateSetting : function(field) {
			SetFieldProperty(field.id, field.checked);
			if (field.checked) {
				$("#gfeway_recurring_date_fields").slideDown();
			}
			else {
				$("#gfeway_recurring_date_fields").slideUp();
			}
		}
	};

})();

// initialise form on page load
jQuery(function($) {

	// add required classes to the field on the admin form
	fieldSettings["gfewayrecurring"] = ".conditional_logic_field_setting, .error_message_setting, .label_setting, .admin_label_setting, .rules_setting, .description_setting, .css_class_setting, .gfewayrecurring_setting";

	// binding to the load field settings event to initialize custom inputs
	$(document).bind("gform_load_field_settings", function(event, field, form) {

		$("#gfeway_initial_setting").prop("checked", !!field["gfeway_initial_setting"]);
		if (!field["gfeway_initial_setting"]) {
			$("#gfeway_initial_fields").hide();
		}

		$("#gfeway_recurring_date_setting").prop("checked", !!field["gfeway_recurring_date_setting"]);
		if (!field["gfeway_recurring_date_setting"]) {
			$("#gfeway_recurring_date_fields").hide();
		}

		$("#gfeway_initial_amount_label").val(field["gfeway_initial_amount_label"]);
		$("#gfeway_recurring_amount_label").val(field["gfeway_recurring_amount_label"]);
		$("#gfeway_initial_date_label").val(field["gfeway_initial_date_label"]);
		$("#gfeway_start_date_label").val(field["gfeway_start_date_label"]);
		$("#gfeway_end_date_label").val(field["gfeway_end_date_label"]);
		$("#gfeway_interval_type_label").val(field["gfeway_interval_type_label"]);

	});

});
