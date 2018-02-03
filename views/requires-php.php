<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="notice notice-error">
	<p><?php printf(__('Gravity Forms eWAY requires PHP %1$s or higher; your website has PHP %2$s which is <a rel="noopener" target="_blank" href="%3$s">old, obsolete, and unsupported</a>.', 'gravityforms-eway'),
			esc_html($php_min), esc_html(PHP_VERSION), 'http://php.net/eol.php'); ?></p>
	<p><?php printf(__('Please upgrade your website hosting. At least PHP %s is recommended.', 'gravityforms-eway'), '7.1'); ?></p>
</div>
