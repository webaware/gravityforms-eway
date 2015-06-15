<div class='wrap'>
<h3>Gravity Forms eWAY Payments</h3>

<form action="<?php echo esc_url($this->scriptURL); ?>" method="post" id="eway-settings-form">
	<table class="form-table">

		<tr>
			<th>eWAY Customer ID</th>
			<td>
				<input type='text' class="regular-text" name='customerID' value="<?php echo esc_attr($this->frm->customerID); ?>" />
			</td>
		</tr>

		<tr valign='top'>
			<th>Use Stored Payments
				<span id="gfeway-opt-admin-stored-test">
					<br />Stored Payments use the Direct Payments sandbox;
					<br />there is no Stored Payments sandbox.
				</span>
			</th>
			<td>
				<label><input type="radio" name="useStored" value="Y" <?php echo checked($this->frm->useStored, 'Y'); ?> />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="useStored" value="N" <?php echo checked($this->frm->useStored, 'N'); ?> />&nbsp;no</label>
			</td>
		</tr>

		<tr valign='top'>
			<th>Use Sandbox (testing environment)</th>
			<td>
				<label><input type="radio" name="useTest" value="Y" <?php echo checked($this->frm->useTest, 'Y'); ?> />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="useTest" value="N" <?php echo checked($this->frm->useTest, 'N'); ?> />&nbsp;no</label>
			</td>
		</tr>

		<tr valign='top'>
			<th>Round Amounts for Sandbox</th>
			<td>
				<label><input type="radio" name="roundTestAmounts" value="Y" <?php echo checked($this->frm->roundTestAmounts, 'Y'); ?> />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="roundTestAmounts" value="N" <?php echo checked($this->frm->roundTestAmounts, 'N'); ?> />&nbsp;no</label>
			</td>
		</tr>

		<tr valign='top'>
			<th>Force Test Customer ID in Sandbox</th>
			<td>
				<label><input type="radio" name="forceTestAccount" value="Y" <?php echo checked($this->frm->forceTestAccount, 'Y'); ?> />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="forceTestAccount" value="N" <?php echo checked($this->frm->forceTestAccount, 'N'); ?> />&nbsp;no</label>
			</td>
		</tr>

		<tr valign='top'>
			<th>Use <a href="http://www.eway.com.au/developers/resources/beagle-(free)-rules" target="_blank">Beagle</a>
				<span id="gfeway-opt-admin-stored-beagle">
					<br />Beagle is not available for Stored Payments.
				</span>
			</th>
			<td>
				<label><input type="radio" name="useBeagle" value="Y" <?php echo checked($this->frm->useBeagle, 'Y'); ?> />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="useBeagle" value="N" <?php echo checked($this->frm->useBeagle, 'N'); ?> />&nbsp;no</label>
				<span id="gfeway-opt-admin-beagle-address">
					<br />You will also need to add an Address field to your form, and make it required. Beagle works by comparing
					the country of the address with the country where the purchaser is using the Internet; if you don't set it to Required,
					then Beagle won't be used when submitting the form without a country selected.
				</span>
			</td>
		</tr>

		<tr valign='top'>
			<th>Verify remote SSL certificate<br />
				(<i>only disable if your website can't be
				 <a target="_blank" title="Stop turning off CURLOPT_SSL_VERIFYPEER and fix your PHP config"
				  href="http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/">correctly configured</a>!</i>)
			</th>
			<td>
				<label><input type="radio" name="sslVerifyPeer" value="Y" <?php echo checked($this->frm->sslVerifyPeer, 'Y'); ?> />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="sslVerifyPeer" value="N" <?php echo checked($this->frm->sslVerifyPeer, 'N'); ?> />&nbsp;no</label>
			</td>
		</tr>

		<tr>
			<th colspan="2" style="font-weight: bold">You may customise the error messages below.
				Leave a message blank to use the default error message.</th>
		</tr>

		<?php
		$errNames = array (
			GFEWAY_ERROR_ALREADY_SUBMITTED,
			GFEWAY_ERROR_NO_AMOUNT,
			GFEWAY_ERROR_REQ_CARD_HOLDER,
			GFEWAY_ERROR_REQ_CARD_NAME,
			GFEWAY_ERROR_EWAY_FAIL,
		);
		foreach ($errNames as $errName) {
			$defmsg = esc_html($this->plugin->getErrMsg($errName, true));
			$msg = esc_attr(get_option($errName));
			?>

			<tr>
				<th><?php echo $defmsg; ?></th>
				<td><input type="text" name="<?php echo esc_attr($errName); ?>" class="large-text" value="<?php echo $msg; ?>" /></td>
			</tr>

			<?php
		}

		?>
	</table>
	<p class="submit">
	<input type="submit" name="Submit" class="button-primary" value="Save Changes" />
	<input type="hidden" name="action" value="save" />
	<?php wp_nonce_field('save', $this->menuPage . '_wpnonce', false); ?>
	</p>
</form>

</div>

<script>
(function($) {

	/**
	* check whether both the sandbox (test) mode and Stored Payments are selected,
	* show warning message if they are
	*/
	function setVisibility() {
		var	useTest   = ($("input[name='useTest']:checked").val()   == "Y"),
			useBeagle = ($("input[name='useBeagle']:checked").val() == "Y"),
			useStored = ($("input[name='useStored']:checked").val() == "Y");

		function display(element, visible) {
			if (visible)
				element.css({display: "none"}).show(750);
			else
				element.hide();
		}

		display($("#gfeway-opt-admin-stored-test"), (useTest && useStored));
		display($("#gfeway-opt-admin-stored-beagle"), (useBeagle && useStored));
		display($("#gfeway-opt-admin-beagle-address"), useBeagle);
	}

	$("#eway-settings-form").on("change", "input[name='useTest'],input[name='useBeagle'],input[name='useStored']", setVisibility);

	setVisibility();

})(jQuery);
</script>
