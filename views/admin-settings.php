
<?php settings_errors(GFEWAY_PLUGIN_OPTIONS); ?>

<h3>Gravity Forms eWAY Payments</h3>

<form action="<?php echo admin_url('options.php'); ?>" method="POST" id="eway-settings-form">
	<?php settings_fields(GFEWAY_PLUGIN_OPTIONS); ?>

	<table class="form-table">

		<tr>
			<th>eWAY Customer ID</th>
			<td>
				<input type="text" class="regular-text" name="gfeway_plugin[customerID]" value="<?php echo esc_attr($options['customerID']); ?>" />
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
				<label><input type="radio" name="gfeway_plugin[useStored]" value="1" <?php checked($options['useStored'], '1'); ?> />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="gfeway_plugin[useStored]" value="" <?php checked($options['useStored'], ''); ?> />&nbsp;no</label>
			</td>
		</tr>

		<tr valign='top'>
			<th>Use Sandbox (testing environment)</th>
			<td>
				<label><input type="radio" name="gfeway_plugin[useTest]" value="1" <?php checked($options['useTest'], '1'); ?> />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="gfeway_plugin[useTest]" value="" <?php checked($options['useTest'], ''); ?> />&nbsp;no</label>
			</td>
		</tr>

		<tr valign='top'>
			<th>Round Amounts for Sandbox</th>
			<td>
				<label><input type="radio" name="gfeway_plugin[roundTestAmounts]" value="1" <?php checked($options['roundTestAmounts'], '1'); ?> />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="gfeway_plugin[roundTestAmounts]" value="" <?php checked($options['roundTestAmounts'], ''); ?> />&nbsp;no</label>
			</td>
		</tr>

		<tr valign='top'>
			<th>Force Test Customer ID in Sandbox</th>
			<td>
				<label><input type="radio" name="gfeway_plugin[forceTestAccount]" value="1" <?php checked($options['forceTestAccount'], '1'); ?> />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="gfeway_plugin[forceTestAccount]" value="" <?php checked($options['forceTestAccount'], ''); ?> />&nbsp;no</label>
			</td>
		</tr>

		<tr valign='top'>
			<th>Use <a href="http://www.eway.com.au/developers/resources/beagle-(free)-rules" target="_blank">Beagle</a>
				<span id="gfeway-opt-admin-stored-beagle">
					<br />Beagle is not available for Stored Payments.
				</span>
			</th>
			<td>
				<label><input type="radio" name="gfeway_plugin[useBeagle]" value="1" <?php checked($options['useBeagle'], '1'); ?> />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="gfeway_plugin[useBeagle]" value="" <?php checked($options['useBeagle'], ''); ?> />&nbsp;no</label>
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
				<label><input type="radio" name="gfeway_plugin[sslVerifyPeer]" value="Y" <?php checked($options['sslVerifyPeer'], '1'); ?> />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="gfeway_plugin[sslVerifyPeer]" value="N" <?php checked($options['sslVerifyPeer'], ''); ?> />&nbsp;no</label>
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
			$defmsg = $this->plugin->getErrMsg($errName, true);
			$msg    = isset($options[$errName]) ? $options[$errName] : get_option($errName);
			?>

			<tr>
				<th><?php echo esc_html($defmsg); ?></th>
				<td><input type="text" name="gfeway_plugin[<?php echo esc_attr($errName); ?>]" class="large-text" value="<?php echo esc_attr($msg); ?>" /></td>
			</tr>

			<?php
		}

		?>
	</table>

	<?php submit_button(); ?>

</form>

<script>
(function($) {

	/**
	* check whether both the sandbox (test) mode and Stored Payments are selected,
	* show warning message if they are
	*/
	function setVisibility() {
		var	useTest   = ($("input[name='gfeway_plugin[useTest]']:checked").val()   === "1"),
			useBeagle = ($("input[name='gfeway_plugin[useBeagle]']:checked").val() === "1"),
			useStored = ($("input[name='gfeway_plugin[useStored]']:checked").val() === "1");

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

	$("#eway-settings-form").on("change", "input[name='gfeway_plugin[useTest]'],input[name='gfeway_plugin[useBeagle]'],input[name='gfeway_plugin[useStored]']", setVisibility);

	setVisibility();

})(jQuery);
</script>
