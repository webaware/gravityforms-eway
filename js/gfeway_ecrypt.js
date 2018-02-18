// gravityforms-eway: Client Side Encryption

(function($) {

	/**
	* if form has Client Side Encryption key, hook its submit action for maybe encrypting
	* @param {jQuery.Event} event
	* @param {Number} form_id int ID of Gravity Forms form
	*/
	$(document).on("gform_post_render", function(event, form_id) {
		$("#gform_" + form_id + "[data-gfeway-encrypt-key]").on("submit", maybeEncryptForm);
	});

	/**
	* check form for conditions to encrypt sensitive fields
	*/
	function maybeEncryptForm() {

		var frm = $(this);

		// don't encrypt if sending to the Recurring Payment XML API
		if (frm.find(".gfeway-recurring-active").length) {
			return true;
		}

		var key = frm.data("gfeway-encrypt-key");

		function maybeEncryptField(field_selector) {
			var field = frm.find(field_selector);

			if (field.length && field.val().length) {
				var encrypted = eCrypt.encryptValue(field.val(), key);
				$("<input type='hidden'>").attr("name", field.data("gfeway-encrypt-name")).val(encrypted).appendTo(frm);
				field.val("");
			}
		}

		maybeEncryptField("input[data-gfeway-encrypt-name='EWAY_CARDNUMBER']");
		maybeEncryptField("input[data-gfeway-encrypt-name='EWAY_CARDCVN']");

		return true;

	}

})(jQuery);
