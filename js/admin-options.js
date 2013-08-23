// Gravity Forms eWAY options admin script

jQuery(function($) {

	/**
	* check whether both the sandbox (test) mode and Stored Payments are selected,
	* show warning message if they are
	*/
	function checkStoredSandbox() {
		var	useTest = ($("input[name='useTest']:checked").val() == "Y"),
			useBeagle = ($("input[name='useBeagle']:checked").val() == "Y"),
			useStored = ($("input[name='useStored']:checked").val() == "Y");

		if (useTest && useStored) {
			$("#gfeway-opt-admin-stored-test").show(750);
		}
		else {
			$("#gfeway-opt-admin-stored-test").hide();
		}

		if (useBeagle && useStored) {
			$("#gfeway-opt-admin-stored-beagle").show(750);
		}
		else {
			$("#gfeway-opt-admin-stored-beagle").hide();
		}
	}

	$("input[name='useTest'],input[name='useBeagle'],input[name='useStored']").change(checkStoredSandbox);


	checkStoredSandbox();

});
