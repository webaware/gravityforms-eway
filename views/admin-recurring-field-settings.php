
<li class="gfewayrecurring_setting field_setting">

	<input type="checkbox" id="gfeway_initial_setting" onchange="GFEwayRecurring.ToggleInitialSetting(this)" />
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
	<input type="text" id="gfeway_initial_date_label" class="fieldwidth-3" size="35"
		onkeyup="GFEwayRecurring.SetFieldLabel(this, '<?php echo esc_attr(self::$defaults['gfeway_initial_date_label']) ?>')" />

	<label for="gfeway_initial_amount_label">
		Initial Amount Label
		<?php gform_tooltip('gfeway_initial_amount_label') ?>
		<?php gform_tooltip('gfeway_initial_amount_label_html') ?>
	</label>
	<input type="text" id="gfeway_initial_amount_label" class="fieldwidth-3" size="35"
		onkeyup="GFEwayRecurring.SetFieldLabel(this, '<?php echo esc_attr(self::$defaults['gfeway_initial_amount_label']) ?>')" />

	</div>

	<label for="gfeway_recurring_amount_label">
		Recurring Amount Label
		<?php gform_tooltip('gfeway_recurring_amount_label') ?>
		<?php gform_tooltip('gfeway_recurring_amount_label_html') ?>
	</label>
	<input type="text" id="gfeway_recurring_amount_label" class="fieldwidth-3" size="35"
		onkeyup="GFEwayRecurring.SetFieldLabel(this, '<?php echo esc_attr(self::$defaults['gfeway_recurring_amount_label']) ?>')" />

	<br />
	<br />
	<input type="checkbox" id="gfeway_recurring_date_setting" onchange="GFEwayRecurring.ToggleRecurringDateSetting(this)" />
	<label for="gfeway_recurring_date_setting" class="inline">
		Show Start/End Dates
		<?php gform_tooltip('gfeway_recurring_date_setting') ?>
		<?php gform_tooltip('gfeway_recurring_date_setting_html') ?>
	</label>
	<br />
	<br />

	<div id="gfeway_recurring_date_fields">

	<label for="gfeway_start_date_label">
		Start Date Label
		<?php gform_tooltip('gfeway_start_date_label') ?>
		<?php gform_tooltip('gfeway_start_date_label_html') ?>
	</label>
	<input type="text" id="gfeway_start_date_label" class="fieldwidth-3" size="35"
		onkeyup="GFEwayRecurring.SetFieldLabel(this, '<?php echo esc_attr(self::$defaults['gfeway_start_date_label']) ?>')" />

	<label for="gfeway_end_date_label">
		End Date Label
		<?php gform_tooltip('gfeway_end_date_label') ?>
		<?php gform_tooltip('gfeway_end_date_label_html') ?>
	</label>
	<input type="text" id="gfeway_end_date_label" class="fieldwidth-3" size="35"
		onkeyup="GFEwayRecurring.SetFieldLabel(this, '<?php echo esc_attr(self::$defaults['gfeway_end_date_label']) ?>')" />

	</div>

	<label for="gfeway_interval_type_label">
		Interval Type Label
		<?php gform_tooltip('gfeway_interval_type_label') ?>
		<?php gform_tooltip('gfeway_interval_type_label_html') ?>
	</label>
	<input type="text" id="gfeway_interval_type_label" class="fieldwidth-3" size="35"
		onkeyup="GFEwayRecurring.SetFieldLabel(this, '<?php echo esc_attr(self::$defaults['gfeway_interval_type_label']) ?>')" />

</li>

