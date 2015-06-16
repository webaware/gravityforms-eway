
<li class="gfewayrecurring_setting field_setting">

	<input type="checkbox" id="gfeway_initial_setting" />
	<label for="gfeway_initial_setting" class="inline">
		Show Initial Amount
		<?php gform_tooltip('gfeway_initial_setting') ?>
		<?php gform_tooltip('gfeway_initial_setting_html') ?>
	</label>
	<br />
	<br />

	<div id="gfeway_initial_fields">

	<label for="gfeway_initial_date_label">
		Initial Date Label
		<?php gform_tooltip('gfeway_initial_date_label') ?>
		<?php gform_tooltip('gfeway_initial_date_label_html') ?>
	</label>
	<input type="text" id="gfeway_initial_date_label" class="fieldwidth-3 gfeway-field-label" size="35"
		data-gfeway-field-label="<?php echo esc_attr(self::$defaults['gfeway_initial_date_label']); ?>" />

	<label for="gfeway_initial_amount_label">
		Initial Amount Label
		<?php gform_tooltip('gfeway_initial_amount_label') ?>
		<?php gform_tooltip('gfeway_initial_amount_label_html') ?>
	</label>
	<input type="text" id="gfeway_initial_amount_label" class="fieldwidth-3 gfeway-field-label" size="35"
		data-gfeway-field-label="<?php echo esc_attr(self::$defaults['gfeway_initial_amount_label']); ?>" />

	</div>

	<label for="gfeway_recurring_amount_label">
		Recurring Amount Label
		<?php gform_tooltip('gfeway_recurring_amount_label') ?>
		<?php gform_tooltip('gfeway_recurring_amount_label_html') ?>
	</label>
	<input type="text" id="gfeway_recurring_amount_label" class="fieldwidth-3 gfeway-field-label" size="35"
		data-gfeway-field-label="<?php echo esc_attr(self::$defaults['gfeway_recurring_amount_label']); ?>" />

	<p>
	<input type="checkbox" id="gfeway_recurring_date_start" />
	<label for="gfeway_recurring_date_start" class="inline">
		Show Start Date
		<?php gform_tooltip('gfeway_recurring_date_start') ?>
		<?php gform_tooltip('gfeway_recurring_date_start_html') ?>
	</label>
	</p>

	<div id="gfeway_recurring_start_date_fields">

	<label for="gfeway_start_date_label">
		Start Date Label
		<?php gform_tooltip('gfeway_start_date_label') ?>
		<?php gform_tooltip('gfeway_start_date_label_html') ?>
	</label>
	<input type="text" id="gfeway_start_date_label" class="fieldwidth-3 gfeway-field-label" size="35"
		data-gfeway-field-label="<?php echo esc_attr(self::$defaults['gfeway_start_date_label']); ?>" />

	</div>

	<p>
	<input type="checkbox" id="gfeway_recurring_date_end" />
	<label for="gfeway_recurring_date_end" class="inline">
		Show End Date
		<?php gform_tooltip('gfeway_recurring_date_end') ?>
		<?php gform_tooltip('gfeway_recurring_date_end_html') ?>
	</label>
	</p>

	<div id="gfeway_recurring_end_date_fields">

	<label for="gfeway_end_date_label">
		End Date Label
		<?php gform_tooltip('gfeway_end_date_label') ?>
		<?php gform_tooltip('gfeway_end_date_label_html') ?>
	</label>
	<input type="text" id="gfeway_end_date_label" class="fieldwidth-3 gfeway-field-label" size="35"
		data-gfeway-field-label="<?php echo esc_attr(self::$defaults['gfeway_end_date_label']); ?>" />

	</div>

	<label for="gfeway_interval_type_label">
		Interval Type Label
		<?php gform_tooltip('gfeway_interval_type_label') ?>
		<?php gform_tooltip('gfeway_interval_type_label_html') ?>
	</label>
	<input type="text" id="gfeway_interval_type_label" class="fieldwidth-3 gfeway-field-label" size="35"
		data-gfeway-field-label="<?php echo esc_attr(self::$defaults['gfeway_interval_type_label']); ?>" />

</li>

