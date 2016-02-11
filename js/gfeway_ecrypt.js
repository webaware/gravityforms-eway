(function($) {

	$("form[data-eway-encrypt-key]").on("submit", function() {

		var frm = $(this);

		// don't encrypt if sending to the Recurring Payment XML API
		if (frm.find(".gfeway-recurring-active").length) {
			return true;
		}

		var ccnumber = frm.find("input[data-gfeway-encrypt-name='EWAY_CARDNUMBER']");
		var cvn = frm.find("input[data-gfeway-encrypt-name='EWAY_CARDCVN']");

		if (ccnumber.length && cvn.length && ccnumber.val().length) {
			var encNumber = eCrypt.encryptValue(ccnumber.val());
			$("<input type='hidden'>").attr("name", ccnumber.data("gfeway-encrypt-name")).val(encNumber).appendTo(frm);
			ccnumber.val("").removeAttr("data-gfeway-encrypt-name");

			var encCvn = eCrypt.encryptValue(cvn.val());
			$("<input type='hidden'>").attr("name", cvn.data("gfeway-encrypt-name")).val(encCvn).appendTo(frm);
			cvn.val("").removeAttr("data-gfeway-encrypt-name");
		}

		return true;

	});

})(jQuery);

