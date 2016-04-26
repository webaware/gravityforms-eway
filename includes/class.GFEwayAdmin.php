<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* class for admin screens
*/
class GFEwayAdmin {

	private $plugin;

	/**
	* @param GFEwayPlugin $plugin
	*/
	public function __construct($plugin) {
		$this->plugin = $plugin;

		// handle basic plugin actions and filters
		add_action('admin_init', array($this, 'adminInit'));
		add_action('admin_notices', array($this, 'checkPrerequisites'));
		add_filter('plugin_row_meta', array($this, 'addPluginDetailsLinks'), 10, 2);
		add_filter('admin_enqueue_scripts', array($this, 'enqueueScripts'));
		add_action('wp_ajax_gfeway_dismiss', array($this, 'dismissNotice'));

		// only if Gravity Forms is activated and of minimum version
		if (GFEwayPlugin::hasMinimumGF()) {
			// add settings link
			add_action('plugin_action_links_' . GFEWAY_PLUGIN_NAME, array($this, 'addPluginActionLinks'));

			// let Gravity Forms determine who has access to settings
			add_filter('option_page_capability_' . GFEWAY_PLUGIN_OPTIONS, array($this, 'optionPageCapability'));

			// add Gravity Forms hooks
			add_action('gform_payment_status', array($this, 'gformPaymentStatus'), 10, 3);
			add_action('gform_after_update_entry', array($this, 'gformAfterUpdateEntry'), 10, 2);

			// tell Gravity Forms not to put payment details into info (i.e. do put them into the new payment details box!)
			add_filter('gform_enable_entry_info_payment_details', '__return_false');
			add_action('gform_payment_details', array($this, 'gformPaymentDetails'), 10, 2);
		}
	}

	/**
	* handle admin init action
	*/
	public function adminInit() {
		if (isset($_GET['page'])) {
			switch ($_GET['page']) {
				case 'gf_settings':
					// add our settings page to the Gravity Forms settings menu
					RGForms::add_settings_page(_x('eWAY Payments', 'settings page', 'gravityforms-eway'), array($this, 'settingsPage'));
					break;
			}
		}

		add_settings_section(GFEWAY_PLUGIN_OPTIONS, false, false, GFEWAY_PLUGIN_OPTIONS);
		register_setting(GFEWAY_PLUGIN_OPTIONS, GFEWAY_PLUGIN_OPTIONS, array($this, 'settingsValidate'));

		// check for non-AJAX dismissable notices from click-through links
		if (isset($_GET['gfeway_dismiss']) && !(defined('DOING_AJAX') && DOING_AJAX)) {
			$this->dismissNotice();
			wp_safe_redirect(remove_query_arg('gfeway_dismiss', wp_get_referer()));
		}
	}

	/**
	* only output our stylesheet if this is our admin page
	* @param string $hook
	*/
	public function enqueueScripts($hook) {
		if ($hook === 'forms_page_gf_settings' || $hook === 'toplevel_page_gf_edit_forms') {
			$ver = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? time() : GFEWAY_PLUGIN_VERSION;
			wp_enqueue_style('gfeway-admin', plugins_url('css/admin.css', GFEWAY_PLUGIN_FILE), false, $ver);
		}
	}

	/**
	* check for required PHP extensions, tell admin if any are missing
	*/
	public function checkPrerequisites() {
		// only bother admins / plugin installers / option setters with this stuff
		if (!current_user_can('activate_plugins') && !current_user_can('manage_options')) {
			return;
		}

		$options = $this->plugin->options;
		$gfSettingsURL = admin_url('admin.php?page=gf_settings');
		$ewaySettingsURL = admin_url('admin.php?page=gf_settings&subview=eWAY+Payments');

		// need at least PHP 5.2.11 for libxml_disable_entity_loader()
		$php_min = '5.2.11';
		if (version_compare(PHP_VERSION, $php_min, '<')) {
			include GFEWAY_PLUGIN_ROOT . 'views/requires-php.php';
		}

		// need these PHP extensions too
		$prereqs = array('libxml', 'SimpleXML', 'xmlwriter');
		$missing = array();
		foreach ($prereqs as $ext) {
			if (!extension_loaded($ext)) {
				$missing[] = $ext;
			}
		}
		if (!empty($missing)) {
			include GFEWAY_PLUGIN_ROOT . 'views/requires-extensions.php';
		}

		// and of course, we need Gravity Forms
		if (!class_exists('GFCommon')) {
			include GFEWAY_PLUGIN_ROOT . 'views/requires-gravity-forms.php';
		}
		elseif (!GFEwayPlugin::hasMinimumGF()) {
			include GFEWAY_PLUGIN_ROOT . 'views/requires-gravity-forms-upgrade.php';
		}

		$noticeFlags = $this->getNoticeFlags();

		// if we upgraded from 1.x, tell admins about upgrade changes and checks to perform
		if (!empty($noticeFlags['upgrade_from_v1'])) {
			include GFEWAY_PLUGIN_ROOT . 'views/notice-upgrade-from-v1.php';
			add_action('admin_print_footer_scripts', array($this, 'footerDismissableNotices'));
		}
	}

	/**
	* record dismiss for a dismissable notice
	*/
	public function dismissNotice() {
		if (isset($_GET['gfeway_dismiss'])) {
			$notice = wp_unslash($_GET['gfeway_dismiss']);
			$notices = $this->getNoticeFlags();

			// only reset notices that exist, and are set
			if (isset($notices[$notice]) && $notices[$notice]) {
				$notices[$notice] = false;
				$this->saveNoticeFlags($notices);
			}
		}

		if (defined('DOING_AJAX') && DOING_AJAX) {
			wp_send_json(array('dismissed' => $notice));
		}

		// click-through link needs to be redirected with action removed
		wp_safe_redirect(remove_query_arg('gfeway_dismiss', wp_get_referer()));
	}

	/**
	* get flags for notices
	* @return array
	*/
	protected function getNoticeFlags() {
		$options = get_option(GFEWAY_PLUGIN_OPTIONS);
		$flags   = get_option('gfeway_notices', array());

		$defaults = array (
			'upgrade_from_v1'		=> (empty($flags) && !empty($options)),
		);
		$flags = wp_parse_args($flags, $defaults);

		return $flags;
	}

	/**
	* save flags for notices
	* @param array $flags
	*/
	protected function saveNoticeFlags($flags) {
		update_option('gfeway_notices', $flags);
	}

	/**
	* add footer script for dismissable notices
	*/
	public function footerDismissableNotices() {
		require GFEWAY_PLUGIN_ROOT . 'views/script-dismissable.php';
	}

	/**
	* add plugin action links
	*/
	public function addPluginActionLinks($links) {
		$url = esc_url(admin_url('admin.php?page=gf_settings&subview=eWAY+Payments'));
		$settings_link = sprintf('<a href="%s">%s</a>', $url, _x('Settings', 'plugin details links', 'gravityforms-eway'));
		array_unshift($links, $settings_link);

		return $links;
	}

	/**
	* add plugin details links
	*/
	public static function addPluginDetailsLinks($links, $file) {
		if ($file === GFEWAY_PLUGIN_NAME) {
			$links[] = sprintf('<a href="https://wordpress.org/support/plugin/gravityforms-eway" target="_blank">%s</a>', _x('Get help', 'plugin details links', 'gravityforms-eway'));
			$links[] = sprintf('<a href="https://wordpress.org/plugins/gravityforms-eway/" target="_blank">%s</a>', _x('Rating', 'plugin details links', 'gravityforms-eway'));
			$links[] = sprintf('<a href="https://translate.wordpress.org/projects/wp-plugins/gravityforms-eway" target="_blank">%s</a>', _x('Translate', 'plugin details links', 'gravityforms-eway'));
			$links[] = sprintf('<a href="http://shop.webaware.com.au/donations/?donation_for=Gravity+Forms+eWAY" target="_blank">%s</a>', _x('Donate', 'plugin details links', 'gravityforms-eway'));
		}

		return $links;
	}

	/**
	* let Gravity Forms determine who can save settings
	* @param string $capability
	* @return string
	*/
	public function optionPageCapability($capability) {
		if (current_user_can('gform_full_access')) {
			return 'gform_full_access';
		}
		if (current_user_can('gravityforms_edit_settings')) {
			return 'gravityforms_edit_settings';
		}
		return $capability;
	}

	/**
	* build the entry details view
	* @param int $form_id
	* @param array $lead
	*/
	public function gformPaymentDetails($form_id, $lead) {
		$payment_gateway = gform_get_meta($lead['id'], 'payment_gateway');
		if ($payment_gateway === 'gfeway') {
			$authcode = gform_get_meta($lead['id'], 'authcode');
			if ($authcode) {
				printf(_x('AuthCode: %s', 'entry details', 'gravityforms-eway'), esc_html($authcode));
				echo "<br /><br />\n";
			}

			$beagle_score = gform_get_meta($lead['id'], 'beagle_score');
			if ($beagle_score) {
				printf(_x('Beagle Score: %s', 'entry details', 'gravityforms-eway'), esc_html($beagle_score));
				echo "<br /><br />\n";
			}
		}
	}

	/**
	* settings admin
	*/
	public function settingsPage() {
		$options = $this->plugin->options;
		require GFEWAY_PLUGIN_ROOT . 'views/admin-settings.php';
	}

	/**
	* validate settings on save
	* @param array $input
	* @return array
	*/
	public function settingsValidate($input) {
		$output = array();

		$output['customerID']			= trim(sanitize_text_field($input['customerID']));
		$output['apiKey']				= trim(strip_tags($input['apiKey']));
		$output['apiPassword']			= trim(strip_tags($input['apiPassword']));
		$output['ecryptKey']			= trim($input['ecryptKey']);
		$output['useStored']			= empty($input['useStored']) ? '' : 1;
		$output['useTest']				= empty($input['useTest']) ? '' : 1;
		$output['useBeagle']			= empty($input['useBeagle']) ? '' : 1;
		$output['roundTestAmounts']		= empty($input['roundTestAmounts']) ? '' : 1;
		$output['forceTestAccount']		= empty($input['forceTestAccount']) ? '' : 1;
		$output['sslVerifyPeer']		= empty($input['sslVerifyPeer']) ? '' : 1;

		$errNames = array (
			GFEWAY_ERROR_ALREADY_SUBMITTED,
			GFEWAY_ERROR_NO_AMOUNT,
			GFEWAY_ERROR_REQ_CARD_HOLDER,
			GFEWAY_ERROR_REQ_CARD_NAME,
			GFEWAY_ERROR_EWAY_FAIL,
		);
		foreach ($errNames as $name) {
			$output[$name] = trim(sanitize_text_field($input[$name]));
		}

		$msg = '';

		if (empty($output['apiKey']) xor empty($output['apiPassword'])) {
			$msg = __('Please enter both your eWAY API key and password', 'gravityforms-eway');
			add_settings_error(GFEWAY_PLUGIN_OPTIONS, '', $msg);
		}
		elseif (empty($output['customerID']) && empty($output['apiKey']) && empty($output['apiPassword'])) {
			$msg = __('Please enter your eWAY API key and password, or your customer ID', 'gravityforms-eway');
			add_settings_error(GFEWAY_PLUGIN_OPTIONS, '', $msg);
		}

		if (empty($msg)) {
			add_settings_error(GFEWAY_PLUGIN_OPTIONS, 'settings_updated', __('Settings saved.', 'gravityforms-eway'), 'updated');
		}

		return $output;
	}

	/**
	* allow edits to payment status
	* @param string $payment_status
	* @param array $form
	* @param array $lead
	* @return string
	*/
    public function gformPaymentStatus($payment_status, $form, $lead) {
		// make sure payment is not Approved, and that we're editing the lead
		if ($payment_status === 'Approved' || strtolower(rgpost('save')) <> 'edit') {
			return $payment_status;
		}

		// make sure payment is one of ours (probably)
		$payment_gateway = gform_get_meta($lead['id'], 'payment_gateway');
		if ((empty($payment_gateway) && GFEwayPlugin::isEwayForm($form['id'], $form['fields'])) || $payment_gateway != 'gfeway') {
			return $payment_status;
		}

		// make sure payment isn't a recurring payment
		if (GFEwayPlugin::hasFieldType($form['fields'], GFEWAY_FIELD_RECURRING)) {
			return $payment_status;
		}

		// create drop down for payment status
		ob_start();
		include GFEWAY_PLUGIN_ROOT . 'views/admin-entry-payment-status.php';
		$input = ob_get_clean();

		return $input;
    }

	/**
	* update payment status if it has changed
	* @param array $form
	* @param int $lead_id
	*/
	public function gformAfterUpdateEntry($form, $lead_id) {
		// make sure we have permission
		check_admin_referer('gforms_save_entry', 'gforms_save_entry');

		// check that save action is for update
		if (strtolower(rgpost('save')) <> 'update')
			return;

		// make sure payment is one of ours (probably)
		$payment_gateway = gform_get_meta($lead_id, 'payment_gateway');
		if ((empty($payment_gateway) && GFEwayPlugin::isEwayForm($form['id'], $form['fields'])) || $payment_gateway != 'gfeway') {
			return;
		}

		// make sure we have a new payment status
		$payment_status = rgpost('payment_status');
		if (empty($payment_status)) {
			return;
		}

		// update payment status
		$lead = GFFormsModel::get_lead($lead_id);
		$lead['payment_status'] = $payment_status;

		GFAPI::update_entry($lead);
	}

}
