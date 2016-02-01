(function($) {
	$("form[data-eway-encrypt-key]").on("submit", function() {

		var	frm = $(this),
			ccnumber = frm.find("input[data-eway-encrypt-name='EWAY_CARDNUMBER']"),
			cvn = frm.find("input[data-eway-encrypt-name='EWAY_CARDCVN']");

		if (ccnumber.length && cvn.length && ccnumber.val().length > 0) {
			var encNumber = eCrypt.encryptValue(ccnumber.val());
			$("<input type='hidden'>").attr("name", ccnumber.data("eway-encrypt-name")).val(encNumber).appendTo(frm);
			ccnumber.val("").removeAttr("data-eway-encrypt-name");

			var envCvn = eCrypt.encryptValue(cvn.val());
			$("<input type='hidden'>").attr("name", cvn.data("eway-encrypt-name")).val(envCvn).appendTo(frm);
			cvn.val("").removeAttr("data-eway-encrypt-name");
		}

		return true;

	});
})(jQuery);

