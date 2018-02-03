<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="notice notice-error gfeway-dismissable" data-gfeway-dismiss="upgrade_from_v1">
	<p><?php _e('Gravity Forms eWAY has been upgraded. Please check your settings.', 'gravityforms-eway'); ?></p>

	<ul style="padding-left: 2em">
		<?php if (empty($options['apiKey']) && empty($options['apiPassword'])):  ?>
		<li style="list-style-type:disc"><?php printf(__('upgrade to Rapid API by entering your eWAY API key and password, and Client Side Encryption Key, at <a href="%s">eWAY Payments settings</a>', 'gravityforms-eway'), esc_url($ewaySettingsURL)); ?></li>
		<?php endif; ?>
		<li style="list-style-type:disc"><?php printf(__('currencies other than AUD are now supported; please check the <a href="%s">currency selected for Gravity Forms</a>', 'gravityforms-eway'), esc_url($gfSettingsURL)); ?></li>
		<li style="list-style-type:disc"><?php _e('accepted credit cards are no longer restricted; please check your forms for accepted credit cards', 'gravityforms-eway'); ?></li>
	</ul>

	<p><a class="gfeway-dismissable" href="<?php echo esc_url(add_query_arg('gfeway_dismiss', 'upgrade_from_v1')); ?>"><?php _ex('Dismiss', 'dismissable notice', 'gravityforms-eway'); ?></a></p>
</div>
