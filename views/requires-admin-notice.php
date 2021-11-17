<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="notice notice-error">
	<p><?php esc_html_e('Gravity Forms Eway is not fully active.', 'gravityforms-eway'); ?></p>
	<ul style="list-style:disc;padding-left: 2em">
		<?php foreach ($notices as $notice): ?>
			<li style="list-style:disc"><?php echo $notice; ?></li>
		<?php endforeach; ?>
	</ul>
</div>
