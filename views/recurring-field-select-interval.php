<span class="gfeway_recurring_left <?= $spanClass; ?>">
<select size="1" name="<?= $inputName; ?>" id="<?= $field_id; ?>" <?= $tabindex; ?> class="gfield_select <?= $class; ?>" <?= $disabled_text; ?>>
<?php foreach ($intervals as $interval) { ?>
	<option value="<?= esc_attr($interval); ?>" <?php selected($interval, $value); ?>><?= esc_html($interval_labels[$interval]); ?></option>
<?php } ?>
</select>
<label class="<?= esc_attr($field['label_class']); ?>" for="<?= $field_id; ?>" id="<?= $field_id; ?>_label"><?= $label; ?></label>
</span>
