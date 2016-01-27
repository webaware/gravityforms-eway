<span class="<?php echo $spanClass; ?>">
<input name="<?php echo $inputName; ?>" id="<?php echo $field_id; ?>" type="text" value="<?php echo $value; ?>" <?php echo $dataMin; ?> <?php echo $dataMax; ?> class="<?php echo $inputClass; ?>" <?php echo $tabindex; ?> <?php echo $disabled_text; ?> />
<input type="hidden" id="gforms_calendar_icon_<?php echo $field_id; ?>" class="gform_hidden" value="<?php echo esc_url($icon_url); ?>" />
<label class="<?php echo esc_attr($field['label_class']); ?>" for="<?php echo $field_id; ?>" id="<?php echo $field_id; ?>_label"><?php echo $label; ?></label>
</span>
