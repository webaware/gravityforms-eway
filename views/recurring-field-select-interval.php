<span class="gfeway_recurring_left <?php echo $spanClass; ?>">
<select size="1" name="<?php echo $inputName; ?>" id="<?php echo $field_id; ?>" <?php echo $tabindex; ?> class="gfield_select <?php echo $class; ?>" <?php echo $disabled_text; ?>>
<?php foreach ($intervals as $interval) { ?>
	<option value="<?php echo esc_attr($interval); ?>" <?php selected($interval, $value); ?>><?php echo esc_html($interval_labels[$interval]); ?></option>
<?php } ?>
</select>
<label class="<?php echo esc_attr($field['label_class']); ?>" for="<?php echo $field_id; ?>" id="<?php echo $field_id; ?>_label"><?php echo $label; ?></label>
</span>
