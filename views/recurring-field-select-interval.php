<span class="gfeway_recurring_left <?php echo $spanClass; ?>">
<select size="1" name="<?php echo $inputName; ?>" id="<?php echo $field_id; ?>" <?php echo $tabindex; ?> class="gfield_select <?php echo $class; ?>" <?php echo $disabled_text; ?>>
<?php foreach ($periods as $period) { ?>
	<option value="<?php echo esc_attr($period); ?>" <?php selected($period, $value); ?>><?php echo esc_html($period); ?></option>
<?php } ?>
</select>
<label class="<?php echo $field['label_class']; ?>" for="<?php echo $field_id; ?>" id="<?php echo $field_id; ?>_label"><?php echo $label; ?></label>
</span>
