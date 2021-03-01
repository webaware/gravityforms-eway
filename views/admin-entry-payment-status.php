<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<select name="payment_status">
	<option value="<?= esc_attr($payment_status); ?>" selected="selected"><?= esc_html($payment_status); ?></option>
	<option value="Approved"><?= _x('Approved', 'payment status', 'gravityforms-eway'); ?></option>
</select>

