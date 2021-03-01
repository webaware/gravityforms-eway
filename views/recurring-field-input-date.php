<span class="<?= $spanClass; ?>">
<input name="<?= $inputName; ?>" id="<?= $field_id; ?>" type="text" value="<?= $value; ?>" <?= $dataMin; ?> <?= $dataMax; ?> class="<?= $inputClass; ?>" <?= $tabindex; ?> <?= $disabled_text; ?> />
<input type="hidden" id="gforms_calendar_icon_<?= $field_id; ?>" class="gform_hidden" value="<?= esc_url($icon_url); ?>" />
<label class="<?= esc_attr($field['label_class']); ?>" for="<?= $field_id; ?>" id="<?= $field_id; ?>_label"><?= $label; ?></label>
</span>
