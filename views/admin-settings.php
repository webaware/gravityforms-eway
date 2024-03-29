<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<?php settings_errors(); ?>

<div class="gfeway-settings-promote">
	<a rel="noopener" target="_blank" href="https://gfeway.webaware.net.au/"><?php esc_html_e('Go Pro for more flexibility!', 'gravityforms-eway'); ?></a>
</div>

<h3><span><i class="fa fa-credit-card"></i> <?= esc_html_x('Eway Payments', 'settings page', 'gravityforms-eway'); ?></span></h3>

<form action="<?= admin_url('options.php'); ?>" method="POST" id="eway-settings-form">
	<?php settings_fields(GFEWAY_PLUGIN_OPTIONS); ?>

	<h4 class="gf_settings_subgroup_title"><?php esc_html_e('Live settings', 'gravityforms-eway'); ?></h4>

	<table class="form-table gforms_form_settings">

		<tr>
			<th scope="row">
				<label for="gfeway_plugin_apiKey"><?= esc_html_x('API Key', 'settings field', 'gravityforms-eway'); ?></label>
				<?php gform_tooltip(esc_html__('Eway Rapid API key, from your MyEway console.', 'gravityforms-eway')); ?>
			</th>
			<td>
				<input type="text" class="large-text" name="gfeway_plugin[apiKey]" id="gfeway_plugin_apiKey"
					autocorrect="off" autocapitalize="off" spellcheck="false"
					value="<?= esc_attr($options['apiKey']); ?>" />
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="gfeway_plugin_apiPassword"><?= esc_html_x('API Password', 'settings field', 'gravityforms-eway'); ?></label>
				<?php gform_tooltip(esc_html__('Eway Rapid API password, from your MyEway console.', 'gravityforms-eway')); ?>
			</th>
			<td>
				<input type="password" class="regular-text" name="gfeway_plugin[apiPassword]" id="gfeway_plugin_apiPassword"
					autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false"
					value="<?= esc_attr($options['apiPassword']); ?>" />
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="gfeway_plugin_ecryptKey"><?= esc_html_x('Client Side Encryption Key', 'settings field', 'gravityforms-eway'); ?></label>
				<?php gform_tooltip(esc_html__("Securely encrypts sensitive credit card information in the customer's browser, so that you can accept credit cards on your website without full PCI certification.", 'gravityforms-eway')); ?>
			</th>
			<td>
				<textarea name="gfeway_plugin[ecryptKey]" id="gfeway_plugin_ecryptKey" rows="5" class="large-text"
					autocorrect="off" autocapitalize="off" spellcheck="false"><?= esc_attr($options['ecryptKey']); ?></textarea>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="gfeway_plugin_customerID"><?= esc_html_x('Customer ID', 'settings field', 'gravityforms-eway'); ?></label>
				<?php gform_tooltip(esc_html__('Eway customer ID, required for Recurring Payments and legacy XML API; from your MyEway console.', 'gravityforms-eway')); ?>
			</th>
			<td>
				<input type="text" class="regular-text" name="gfeway_plugin[customerID]" id="gfeway_plugin_customerID" value="<?= esc_attr($options['customerID']); ?>" />
			</td>
		</tr>

	</table>

	<h4 class="gf_settings_subgroup_title"><?php esc_html_e('Sandbox settings', 'gravityforms-eway'); ?></h4>

	<table class="form-table gforms_form_settings">

		<tr>
			<th scope="row">
				<label for="gfeway_plugin_sandboxApiKey"><?= esc_html_x('API Key', 'settings field', 'gravityforms-eway'); ?></label>
				<?php gform_tooltip(esc_html__('Eway Rapid API key, from your MyEway console.', 'gravityforms-eway')); ?>
			</th>
			<td>
				<input type="text" class="large-text" name="gfeway_plugin[sandboxApiKey]" id="gfeway_plugin_sandboxApiKey"
					autocorrect="off" autocapitalize="off" spellcheck="false"
					value="<?= esc_attr($options['sandboxApiKey']); ?>" />
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="gfeway_plugin_sandboxPassword"><?= esc_html_x('API Password', 'settings field', 'gravityforms-eway'); ?></label>
				<?php gform_tooltip(esc_html__('Eway Rapid API password, from your MyEway console.', 'gravityforms-eway')); ?>
			</th>
			<td>
				<input type="password" class="regular-text" name="gfeway_plugin[sandboxPassword]" id="gfeway_plugin_sandboxPassword"
					autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false"
					value="<?= esc_attr($options['sandboxPassword']); ?>" />
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="gfeway_plugin_sandboxEcryptKey"><?= esc_html_x('Client Side Encryption Key', 'settings field', 'gravityforms-eway'); ?></label>
				<?php gform_tooltip(esc_html__("Securely encrypts sensitive credit card information in the customer's browser, so that you can accept credit cards on your website without full PCI certification.", 'gravityforms-eway')); ?>
			</th>
			<td>
				<textarea name="gfeway_plugin[sandboxEcryptKey]" id="gfeway_plugin_sandboxEcryptKey" rows="5" class="large-text"
					autocorrect="off" autocapitalize="off" spellcheck="false"><?= esc_attr($options['sandboxEcryptKey']); ?></textarea>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="gfeway_plugin_sandboxCustomerID"><?= esc_html_x('Customer ID', 'settings field', 'gravityforms-eway'); ?></label>
				<?php gform_tooltip(esc_html__('Eway customer ID, for the legacy XML API sandbox (not used for the Recurring Payments sandbox); from your MyEway console.', 'gravityforms-eway')); ?>
			</th>
			<td>
				<input type="text" class="regular-text" name="gfeway_plugin[sandboxCustomerID]" id="gfeway_plugin_sandboxCustomerID" value="<?= esc_attr($options['sandboxCustomerID']); ?>" />
			</td>
		</tr>

	</table>

	<h4 class="gf_settings_subgroup_title"><?= esc_html_x('Options', 'settings page', 'gravityforms-eway'); ?></h4>

	<table class="form-table gforms_form_settings">

		<tr valign='top'>
			<th>&nbsp;</th>
			<td>

				<fieldset>
					<legend><?= esc_html_x('Payment Method', 'settings field', 'gravityforms-eway'); ?></legend>
					<input type="radio" name="gfeway_plugin[useStored]" id="gfeway_plugin_useStored_no" value="" <?php checked($options['useStored'], ''); ?> />
					<label for="gfeway_plugin_useStored_no"><?= esc_html_x('Capture', 'payment method', 'gravityforms-eway'); ?></label>
					<input type="radio" name="gfeway_plugin[useStored]" id="gfeway_plugin_useStored_yes" value="1" <?php checked($options['useStored'], '1'); ?> />
					<label for="gfeway_plugin_useStored_yes"><?= esc_html_x('Authorize', 'payment method', 'gravityforms-eway'); ?></label>
					<p id="gfeway-opt-admin-stored-test"><em><?php esc_html_e('The Stored Payments legacy XML API has no sandbox, so transactions are simulated via the Direct Payments XML API sandbox. Add your API key and password to use Rapid API and see PreAuth transactions in your sandbox.', 'gravityforms-eway'); ?></em></p>
				</fieldset>

				<fieldset>
					<legend><?php esc_html_e('Use Sandbox (testing environment)', 'gravityforms-eway'); ?></legend>
					<input type="radio" name="gfeway_plugin[useTest]" id="gfeway_plugin_useTest_yes" value="1" <?php checked($options['useTest'], '1'); ?> />
					<label for="gfeway_plugin_useTest_yes"><?= esc_html_x('Yes', 'settings', 'gravityforms-eway'); ?></label>
					<input type="radio" name="gfeway_plugin[useTest]" id="gfeway_plugin_useTest_no" value="" <?php checked($options['useTest'], ''); ?> />
					<label for="gfeway_plugin_useTest_no"><?= esc_html_x('No', 'settings', 'gravityforms-eway'); ?></label>
				</fieldset>

				<fieldset>
					<legend><?php esc_html_e('Round Amounts for Sandbox', 'gravityforms-eway'); ?></legend>
					<input type="radio" name="gfeway_plugin[roundTestAmounts]" id="gfeway_plugin_roundTestAmounts_yes" value="1" <?php checked($options['roundTestAmounts'], '1'); ?> />
					<label for="gfeway_plugin_roundTestAmounts_yes"><?= esc_html_x('Yes', 'settings', 'gravityforms-eway'); ?></label>
					<input type="radio" name="gfeway_plugin[roundTestAmounts]" id="gfeway_plugin_roundTestAmounts_no" value="" <?php checked($options['roundTestAmounts'], ''); ?> />
					<label for="gfeway_plugin_roundTestAmounts_no"><?= esc_html_x('No', 'settings', 'gravityforms-eway'); ?></label>
					<p><em><?php esc_html_e('Ensures successful transactions when the sandbox behavior is set to "Use Cents Value".', 'gravityforms-eway'); ?></em></p>
				</fieldset>

				<fieldset>
					<legend><?php esc_html_e('Force Customer ID 87654321 in Sandbox', 'gravityforms-eway'); ?></legend>
					<input type="radio" name="gfeway_plugin[forceTestAccount]" id="gfeway_plugin_forceTestAccount_yes" value="1" <?php checked($options['forceTestAccount'], '1'); ?> />
					<label for="gfeway_plugin_forceTestAccount_yes"><?= esc_html_x('Yes', 'settings', 'gravityforms-eway'); ?></label>
					<input type="radio" name="gfeway_plugin[forceTestAccount]" id="gfeway_plugin_forceTestAccount_no" value="" <?php checked($options['forceTestAccount'], ''); ?> />
					<label for="gfeway_plugin_forceTestAccount_no"><?= esc_html_x('No', 'settings', 'gravityforms-eway'); ?></label>
				</fieldset>

				<fieldset id="gfeway-opt-admin-beagle">
					<legend><?php printf(__('Use <a href="%s" rel="noopener" target="_blank">Beagle Lite</a> for legacy XML API', 'gravityforms-eway'), 'https://eway.io/features/antifraud-beagle-lite'); ?></legend>
					<input type="radio" name="gfeway_plugin[useBeagle]" id="gfeway_plugin_useBeagle_yes" value="1" <?php checked($options['useBeagle'], '1'); ?> />
					<label for="gfeway_plugin_useBeagle_yes"><?= esc_html_x('Yes', 'settings', 'gravityforms-eway'); ?></label>
					<input type="radio" name="gfeway_plugin[useBeagle]" id="gfeway_plugin_useBeagle_no" value="" <?php checked($options['useBeagle'], ''); ?> />
					<label for="gfeway_plugin_useBeagle_no"><?= esc_html_x('No', 'settings', 'gravityforms-eway'); ?></label>
					<p id="gfeway-opt-admin-stored-beagle"><em><?php esc_html_e('Beagle Lite is not available for the Stored Payments legacy XML API.', 'gravityforms-eway'); ?></em></p>
					<p id="gfeway-opt-admin-beagle-address"><em><?php esc_html_e('Beagle Lite fraud detection requires an address for each transaction. Be sure to add an Address field to your forms, and make it a required field.', 'gravityforms-eway'); ?></em></p>
				</fieldset>

				<fieldset>
					<legend><?php esc_html_e('Verify remote SSL certificate', 'gravityforms-eway'); ?></legend>
					<input type="radio" name="gfeway_plugin[sslVerifyPeer]" id="gfeway_plugin_sslVerifyPeer_yes" value="Y" <?php checked($options['sslVerifyPeer'], '1'); ?> />
					<label for="gfeway_plugin_sslVerifyPeer_yes"><?= esc_html_x('Yes', 'settings', 'gravityforms-eway'); ?></label>
					<input type="radio" name="gfeway_plugin[sslVerifyPeer]" id="gfeway_plugin_sslVerifyPeer_no" value="N" <?php checked($options['sslVerifyPeer'], ''); ?> />
					<label for="gfeway_plugin_sslVerifyPeer_no"><?= esc_html_x('No', 'settings', 'gravityforms-eway'); ?></label>
					<p><em><?php printf(__("Only choose 'no' if you can't <a rel='noopener' target='_blank' href='%s'>fix your website SSL configuration</a> due to a technical reason.", 'gravityforms-eway'),
						'https://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/'); ?></em></p>
				</fieldset>

			</td>
		</tr>

	</table>

	<h4 class="gf_settings_subgroup_title"><?= esc_html_x('Error messages', 'settings', 'gravityforms-eway'); ?></h4>

	<table class="form-table gforms_form_settings">

		<tr>
			<th colspan="2"><?php esc_html_e('You may customize the error messages below. Leave a message blank to use the default error message.', 'gravityforms-eway'); ?></th>
		</tr>

		<tr colspan="2">
			<td colspan="2">
		<?php
		$errNames = [
			GFEWAY_ERROR_ALREADY_SUBMITTED,
			GFEWAY_ERROR_NO_AMOUNT,
			GFEWAY_ERROR_REQ_CARD_HOLDER,
			GFEWAY_ERROR_REQ_CARD_NAME,
			GFEWAY_ERROR_EWAY_FAIL,
		];
		foreach ($errNames as $errName) {
			$defmsg = $this->plugin->getErrMsg($errName, true);
			$msg    = isset($options[$errName]) ? $options[$errName] : get_option($errName);
			?>

				<label for="<?= esc_attr($errName); ?>"><?= esc_html($defmsg); ?></label>
				<input type="text" class="large-text" id="<?= esc_attr($errName); ?>" name="gfeway_plugin[<?= esc_attr($errName); ?>]"
					value="<?= esc_attr($msg); ?>" placeholder="<?= esc_attr($defmsg); ?>" />

			<?php
		}
		?>
			</td>
		</tr>

	</table>

	<?php submit_button(); ?>

</form>

<script>
(function($) {

	function setVisibility() {
		var	useTest   = ($("input[name='gfeway_plugin[useTest]']:checked").val()   === "1"),
			useBeagle = ($("input[name='gfeway_plugin[useBeagle]']:checked").val() === "1"),
			useStored = ($("input[name='gfeway_plugin[useStored]']:checked").val() === "1"),
			useAPI    = ($("input[name='gfeway_plugin[apiKey]']").val() !== "" && $("input[name='gfeway_plugin[apiPassword]']").val() !== "");

		function display(element, visible) {
			if (visible)
				element.show();
			else
				element.hide();
		}

		display($("#gfeway-opt-admin-stored-test"), (useTest && useStored && !useAPI));
		display($("#gfeway-opt-admin-stored-beagle"), (useBeagle && useStored && !useAPI));
		display($("#gfeway-opt-admin-beagle"), !useAPI);
		display($("#gfeway-opt-admin-beagle-address"), useBeagle);
	}

	$("#eway-settings-form").on("change", "input[name='gfeway_plugin[apiKey]'],input[name='gfeway_plugin[apiPassword]'],input[name='gfeway_plugin[useTest]'],input[name='gfeway_plugin[useBeagle]'],input[name='gfeway_plugin[useStored]']", setVisibility);

	setVisibility();

})(jQuery);
</script>
