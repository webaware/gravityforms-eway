<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<select name="payment_status">
	<option value="<?php echo esc_attr($payment_status); ?>" selected="selected"><?php echo esc_html($payment_status); ?></option>
	<option value="Approved"><?php echo _x('Approved', 'payment status', 'gravityforms-eway'); ?></option>
</select>

