
<li class="gfewayrecurring_setting field_setting">

	<input type="checkbox" id="gfeway_initial_setting" />
	<label for="gfeway_initial_setting" class="inline">
		<?php _ex('Show Initial Amount', 'form editor subfield label', 'gravityforms-eway'); ?>
		<?php gform_tooltip('gfeway_initial_setting') ?>
		<?php gform_tooltip('gfeway_initial_setting_html') ?>
	</label>
	<br />
	<br />

	<div id="gfeway_initial_fields">

	<label for="gfeway_initial_date_label">
		<?php _ex('Initial Date Label', 'form editor subfield label', 'gravityforms-eway'); ?>
		<?php gform_tooltip('gfeway_initial_date_label') ?>
		<?php gform_tooltip('gfeway_initial_date_label_html') ?>
	</label>
	<input type="text" id="gfeway_initial_date_label" class="fieldwidth-3 gfeway-field-label" size="35"
		data-gfeway-field-label="<?php echo esc_attr(self::$defaults['gfeway_initial_date_label']); ?>" />

	<label for="gfeway_initial_amount_label">
		<?php _ex('Initial Amount Label', 'form editor subfield label', 'gravityforms-eway'); ?>
		<?php gform_tooltip('gfeway_initial_amount_label') ?>
		<?php gform_tooltip('gfeway_initial_amount_label_html') ?>
	</label>
	<input type="text" id="gfeway_initial_amount_label" class="fieldwidth-3 gfeway-field-label" size="35"
		data-gfeway-field-label="<?php echo esc_attr(self::$defaults['gfeway_initial_amount_label']); ?>" />

	</div>

	<label for="gfeway_recurring_amount_label">
		<?php _ex('Recurring Amount Label', 'form editor subfield label', 'gravityforms-eway'); ?>
		<?php gform_tooltip('gfeway_recurring_amount_label') ?>
		<?php gform_tooltip('gfeway_recurring_amount_label_html') ?>
	</label>
	<input type="text" id="gfeway_recurring_amount_label" class="fieldwidth-3 gfeway-field-label" size="35"
		data-gfeway-field-label="<?php echo esc_attr(self::$defaults['gfeway_recurring_amount_label']); ?>" />

	<p>
	<input type="checkbox" id="gfeway_recurring_date_start" />
	<label for="gfeway_recurring_date_start" class="inline">
		<?php _ex('Show Start Date', 'form editor subfield label', 'gravityforms-eway'); ?>
		<?php gform_tooltip('gfeway_recurring_date_start') ?>
		<?php gform_tooltip('gfeway_recurring_date_start_html') ?>
	</label>
	</p>

	<div id="gfeway_recurring_start_date_fields">

	<label for="gfeway_start_date_label">
		<?php _ex('Start Date Label', 'form editor subfield label', 'gravityforms-eway'); ?>
		<?php gform_tooltip('gfeway_start_date_label') ?>
		<?php gform_tooltip('gfeway_start_date_label_html') ?>
	</label>
	<input type="text" id="gfeway_start_date_label" class="fieldwidth-3 gfeway-field-label" size="35"
		data-gfeway-field-label="<?php echo esc_attr(self::$defaults['gfeway_start_date_label']); ?>" />

	</div>

	<p>
	<input type="checkbox" id="gfeway_recurring_date_end" />
	<label for="gfeway_recurring_date_end" class="inline">
		<?php _ex('Show End Date', 'form editor subfield label', 'gravityforms-eway'); ?>
		<?php gform_tooltip('gfeway_recurring_date_end') ?>
		<?php gform_tooltip('gfeway_recurring_date_end_html') ?>
	</label>
	</p>

	<div id="gfeway_recurring_end_date_fields">

	<label for="gfeway_end_date_label">
		<?php _ex('End Date Label', 'form editor subfield label', 'gravityforms-eway'); ?>
		<?php gform_tooltip('gfeway_end_date_label') ?>
		<?php gform_tooltip('gfeway_end_date_label_html') ?>
	</label>
	<input type="text" id="gfeway_end_date_label" class="fieldwidth-3 gfeway-field-label" size="35"
		data-gfeway-field-label="<?php echo esc_attr(self::$defaults['gfeway_end_date_label']); ?>" />

	</div>

	<label for="gfeway_interval_type_label">
		<?php _ex('Interval Type Label', 'form editor subfield label', 'gravityforms-eway'); ?>
		<?php gform_tooltip('gfeway_interval_type_label') ?>
		<?php gform_tooltip('gfeway_interval_type_label_html') ?>
	</label>
	<input type="text" id="gfeway_interval_type_label" class="fieldwidth-3 gfeway-field-label" size="35"
		data-gfeway-field-label="<?php echo esc_attr(self::$defaults['gfeway_interval_type_label']); ?>" />

</li>

