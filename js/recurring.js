/*!
gravityforms-eway
copyright (c) 2012-2016 WebAware Pty Ltd
Recurring Payments field
*/

// initialise form on page load
(function($) {

	var	thisYear			= (new Date()).getFullYear(),
		yearRange			= thisYear + ":2099",				// year range for max date settings, mumble mumble jquery-ui mumble
		reDatePattern		= /^[0-9]{4}-[0-9]{2}-[0-9]{2}$/,	// regex test for ISO date string
		setPickerOptions	= false;

	// set datepicker minimum date if given
	$("input[data-gfeway-minDate]").each(function() {
		var	input   = $(this),
			minDate = this.getAttribute("data-gfeway-minDate");

		// if minDate is an ISO date string, convert to a Date object as a reliable way to set minDate
		if (reDatePattern.test(minDate)) {
			minDate = new Date(minDate);
		}

		input.datepicker("option", "minDate", minDate);
		setPickerOptions = true;
	});

	// set datepicker maximum date if given
	$("input[data-gfeway-maxDate]").each(function() {
		var	input   = $(this),
			maxDate = this.getAttribute("data-gfeway-maxDate");

		// if maxDate is an ISO date string, convert to a Date object as a reliable way to set maxDate
		if (reDatePattern.test(maxDate)) {
			maxDate = new Date(maxDate);
		}

		input.datepicker("option", "yearRange", yearRange);		// need to reset year range so can extend max date!
		input.datepicker("option", "maxDate", maxDate);
		setPickerOptions = true;
	});

	// hack: setting options on datepicker fields after initialisation makes the datepicker div show at the bottom of the page
	if (setPickerOptions) {
		$("#ui-datepicker-div").hide();
	}

	/**
	* set recurring field to active, until otherwise advised by conditional logic triggers
	* @param {jQuery.Event} event
	* @param {Number} form_id int ID of Gravity Forms form
	*/
	$(document).on("gform_post_render", function(event, form_id) {
		$("#gform_" + form_id + " .gfeway-contains-recurring").addClass("gfeway-recurring-active");
	});

	// watch for conditional logic changes
	gform.addAction("gform_post_conditional_logic_field_action", function(formId, action, targetId /* , defaultValues, isInit */) {
		var target = $(targetId);

		if (target.hasClass("gfeway-contains-recurring")) {
			if (action === "show") {
				target.addClass("gfeway-recurring-active").removeClass("gfeway-recurring-inactive");

				// reset fields to initial values
				target.find("input.datepicker").each(function() {
					this.value = this.getAttribute("value");
				});
			}
			else {
				target.addClass("gfeway-recurring-inactive").removeClass("gfeway-recurring-active");
			}
		}
	});

})(jQuery);
