// gravityforms-eway: Recurring Payments field

// initialise form on page load
(function($) {

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
		const target = $(targetId);

		if (target.hasClass("gfeway-contains-recurring")) {
			if (action === "show") {
				target.addClass("gfeway-recurring-active").removeClass("gfeway-recurring-inactive");

				// reset fields to initial values
				target.find("input[type='date']").each(function() {
					this.value = this.getAttribute("value");
				});
			}
			else {
				target.addClass("gfeway-recurring-inactive").removeClass("gfeway-recurring-active");
			}
		}
	});

})(jQuery);
