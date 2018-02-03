<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="notice notice-error">
	<p><?php printf(__('Gravity Forms eWAY requires <a rel="noopener" target="_blank" href="%1$s">PCRE</a> version %2$s or higher; your website has PCRE version %3$s', 'gravityforms-eway'),
		'http://php.net/manual/en/book.pcre.php', esc_html($pcre_min), esc_html(PCRE_VERSION)); ?></p>
</div>
