<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="notice notice-error gfeway-dismissable" data-gfeway-dismiss="upgrade_from_v1">
	<p><?php esc_html_e('Gravity Forms eWAY has been upgraded. Please check your settings.', 'gravityforms-eway'); ?></p>

	<ul style="list-style-type:circle;padding-left:1em;margin-left:0">
		<?php if (empty($options['apiKey']) && empty($options['apiPassword'])):  ?>
			<li><?= gfeway_internal_link(
					esc_html__('upgrade to Rapid API by entering your eWAY API key and password, and Client Side Encryption Key, at {{a}}eWAY Payments settings{{/a}}', 'gravityforms-eway'),
					$ewaySettingsURL
				); ?></li>
		<?php endif; ?>
		<li><?= gfeway_internal_link(
					esc_html__('currencies other than AUD are now supported; please check the {{a}}currency selected for Gravity Forms{{/a}}', 'gravityforms-eway'),
					$gfSettingsURL
			); ?></li>
		<li><?php esc_html_e('accepted credit cards are no longer restricted; please check your forms for accepted credit cards', 'gravityforms-eway'); ?></li>
	</ul>

	<p><a class="gfeway-dismissable" href="<?= esc_url(add_query_arg('gfeway_dismiss', 'upgrade_from_v1')); ?>"><?= esc_html_x('Dismiss', 'dismissable notice', 'gravityforms-eway'); ?></a></p>
</div>
