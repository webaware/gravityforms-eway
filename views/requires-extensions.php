<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<p><?php esc_html_e('Requires these missing PHP extensions. Please contact your website host to have these extensions installed.', 'gravityforms-eway'); ?></p>
<ul style="list-style-type:circle;padding-left:1em;margin-left:0">
	<?php foreach ($missing as $ext): ?>
		<li><?= esc_html($ext); ?></li>
	<?php endforeach; ?>
</ul>
