<?php

use GfEwayRequires as Requires;

use function webaware\gfeway\has_required_gravityforms;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * class for admin screens
 */
final class GFEwayAdmin {

	private $plugin;
	private $slug;

	/**
	 * @param GFEwayPlugin $plugin
	 */
	public function __construct($plugin) {
		$this->plugin = $plugin;
		$this->slug   = 'gravityforms-eway';

		// handle basic plugin actions and filters
		add_action('admin_init', [$this, 'adminInit']);
		add_action('admin_init', [$this, 'checkPrerequisites']);
		add_filter('plugin_row_meta', [$this, 'addPluginDetailsLinks'], 10, 2);
		add_filter('admin_enqueue_scripts', [$this, 'enqueueScripts']);
		add_action('wp_ajax_gfeway_dismiss', [$this, 'dismissNotice']);

		// only if Gravity Forms is activated and of minimum version
		if (has_required_gravityforms()) {
			// add settings link to Plugins page
			if (current_user_can('gform_full_access') || current_user_can('gravityforms_edit_settings')) {
				add_action('plugin_action_links_' . GFEWAY_PLUGIN_NAME, [$this, 'addPluginActionLinks']);
			}

			// let Gravity Forms determine who has access to settings
			add_filter('option_page_capability_' . GFEWAY_PLUGIN_OPTIONS, [$this, 'optionPageCapability']);

			// add Gravity Forms hooks
			add_action('gform_payment_status', [$this, 'gformPaymentStatus'], 10, 3);
			add_action('gform_after_update_entry', [$this, 'gformAfterUpdateEntry'], 10, 2);

			// tell Gravity Forms not to put payment details into info (i.e. do put them into the new payment details box!)
			add_filter('gform_enable_entry_info_payment_details', '__return_false');
			add_action('gform_payment_details', [$this, 'gformPaymentDetails'], 10, 2);
		}
	}

	/**
	 * handle admin init action
	 */
	public function adminInit() : void {
		global $plugin_page;

		if ($plugin_page === 'gf_settings') {
			// add our settings page to the Gravity Forms settings menu
			$title = esc_html_x('Eway Payments', 'settings page', 'gravityforms-eway');
			GFForms::add_settings_page([
				'name'			=> $this->slug,
				'tab_label'		=> $title,
				'title'			=> $title,
				'handler'		=> [$this, 'settingsPage'],
				'icon'			=> 'fa-credit-card',
			]);
		}

		add_settings_section(GFEWAY_PLUGIN_OPTIONS, false, false, GFEWAY_PLUGIN_OPTIONS);
		register_setting(GFEWAY_PLUGIN_OPTIONS, GFEWAY_PLUGIN_OPTIONS, [$this, 'settingsValidate']);

		// check for non-AJAX dismissable notices from click-through links
		if (isset($_GET['gfeway_dismiss']) && !wp_doing_ajax()) {
			$this->dismissNotice();
			wp_safe_redirect(remove_query_arg('gfeway_dismiss', wp_get_referer()));
		}
	}

	/**
	 * only output our stylesheet if this is our admin page
	 */
	public function enqueueScripts() : void {
		global $plugin_page;
		$subview = isset($_GET['subview']) ? $_GET['subview'] : '';

		if (($plugin_page === 'gf_settings' && $subview === $this->slug) || $plugin_page === 'gf_edit_forms') {
			$ver = SCRIPT_DEBUG ? time() : GFEWAY_PLUGIN_VERSION;
			$min = SCRIPT_DEBUG ? '.dev' : '.min';
			wp_enqueue_style('gfeway-admin', plugins_url("static/css/admin$min.css", GFEWAY_PLUGIN_FILE), false, $ver);
		}
	}

	/**
	 * check for required PHP extensions, tell admin if any are missing
	 */
	public function checkPrerequisites() : void {
		$requires = new Requires();

		// need these PHP extensions
		// NB: libxml / SimpleXML used for version update functions
		$prereqs = ['libxml', 'pcre', 'SimpleXML', 'xmlwriter'];
		$missing = [];
		foreach ($prereqs as $ext) {
			if (!extension_loaded($ext)) {
				$missing[] = $ext;
			}
		}
		if (!empty($missing)) {
			ob_start();
			include GFEWAY_PLUGIN_ROOT . 'views/requires-extensions.php';
			$requires->addNotice(ob_get_clean());
		}

		// and PCRE needs to be v8+ or we break! e.g. \K not present until v7.2 and some sites still use v6.6!
		$pcre_min = '8';
		if (defined('PCRE_VERSION') && version_compare(PCRE_VERSION, $pcre_min, '<')) {
			$requires->addNotice(
				gfeway_external_link(
					sprintf(esc_html__('Requires {{a}}PCRE{{/a}} version %1$s or higher; your website has PCRE version %2$s.', 'gravityforms-eway'),
						esc_html($pcre_min), esc_html(PCRE_VERSION)),
					'https://www.php.net/manual/en/book.pcre.php'
				)
			);
		}

		// and of course, we need Gravity Forms
		if (!class_exists('GFCommon', false)) {
			$requires->addNotice(
				gfeway_external_link(
					esc_html__('Requires {{a}}Gravity Forms{{/a}} to be installed and activated.', 'gravityforms-eway'),
					'https://webaware.com.au/get-gravity-forms'
				)
			);
		}
		elseif (!has_required_gravityforms()) {
			$requires->addNotice(
				gfeway_external_link(
					sprintf(esc_html__('Requires {{a}}Gravity Forms{{/a}} version %1$s or higher; your website has Gravity Forms version %2$s', 'gravityforms-eway'),
						esc_html(GFEWAY_MIN_VERSION_GF), esc_html(GFCommon::$version)),
					'https://webaware.com.au/get-gravity-forms'
				)
			);
		}

		$noticeFlags = $this->getNoticeFlags();

		// if we upgraded from 1.x, tell admins about upgrade changes and checks to perform
		if (!empty($noticeFlags['upgrade_from_v1'])) {
			add_action('admin_notice', [$this, 'showUpgradeNotice1']);
			add_action('admin_print_footer_scripts', [$this, 'footerDismissableNotices']);
		}
	}

	/**
	 * display 1.x upgrade notice
	 */
	public function showUpgradeNotice1() : void {
		$options = $this->plugin->options;
		$gfSettingsURL = admin_url('admin.php?page=gf_settings');
		$ewaySettingsURL = admin_url('admin.php?page=gf_settings&subview=' . urlencode($this->slug));
		include GFEWAY_PLUGIN_ROOT . 'views/notice-upgrade-from-v1.php';
	}

	/**
	 * record dismiss for a dismissable notice
	 */
	public function dismissNotice() : void {
		if (isset($_GET['gfeway_dismiss'])) {
			$notice = wp_unslash($_GET['gfeway_dismiss']);
			$notices = $this->getNoticeFlags();

			// only reset notices that exist, and are set
			if (isset($notices[$notice]) && $notices[$notice]) {
				$notices[$notice] = false;
				$this->saveNoticeFlags($notices);
			}
		}

		if (wp_doing_ajax()) {
			wp_send_json(['dismissed' => $notice]);
		}

		// click-through link needs to be redirected with action removed
		wp_safe_redirect(remove_query_arg('gfeway_dismiss', wp_get_referer()));
	}

	/**
	 * get flags for notices
	 */
	private function getNoticeFlags() : array {
		$options = get_option(GFEWAY_PLUGIN_OPTIONS);
		$flags   = get_option('gfeway_notices', []);

		$defaults = [
			'upgrade_from_v1'		=> (empty($flags) && !empty($options)),
		];
		$flags = wp_parse_args($flags, $defaults);

		return $flags;
	}

	/**
	 * save flags for notices
	 */
	private function saveNoticeFlags(array $flags) : void {
		update_option('gfeway_notices', $flags);
	}

	/**
	 * add footer script for dismissable notices
	 */
	public function footerDismissableNotices() : void {
		require GFEWAY_PLUGIN_ROOT . 'views/script-dismissable.php';
	}

	/**
	 * add plugin action links
	 */
	public function addPluginActionLinks(array $links) : array {
		$url = esc_url(admin_url('admin.php?page=gf_settings&subview=' . $this->slug));
		$settings_link = sprintf('<a href="%s">%s</a>', $url, _x('Settings', 'plugin details links', 'gravityforms-eway'));
		array_unshift($links, $settings_link);

		return $links;
	}

	/**
	 * add plugin details links
	 */
	public static function addPluginDetailsLinks(array $links, string $file) : array {
		if ($file === GFEWAY_PLUGIN_NAME) {
			$links[] = sprintf('<a href="https://wordpress.org/support/plugin/gravityforms-eway" rel="noopener" target="_blank">%s</a>', _x('Get help', 'plugin details links', 'gravityforms-eway'));
			$links[] = sprintf('<a href="https://wordpress.org/plugins/gravityforms-eway/" rel="noopener" target="_blank">%s</a>', _x('Rating', 'plugin details links', 'gravityforms-eway'));
			$links[] = sprintf('<a href="https://translate.wordpress.org/projects/wp-plugins/gravityforms-eway" rel="noopener" target="_blank">%s</a>', _x('Translate', 'plugin details links', 'gravityforms-eway'));
			$links[] = sprintf('<a href="https://shop.webaware.com.au/donations/?donation_for=Gravity+Forms+Eway" rel="noopener" target="_blank">%s</a>', _x('Donate', 'plugin details links', 'gravityforms-eway'));
		}

		return $links;
	}

	/**
	 * let Gravity Forms determine who can save settings
	 */
	public function optionPageCapability(string $capability) : string {
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
	public function gformPaymentDetails($form_id, $lead) : void {
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
	public function settingsPage() : void {
		$options = $this->plugin->options;
		require GFEWAY_PLUGIN_ROOT . 'views/admin-settings.php';
	}

	/**
	 * validate settings on save
	 */
	public function settingsValidate(array $input) : array {
		$output = [];

		$output['customerID']			= trim(sanitize_text_field($input['customerID']));
		$output['apiKey']				= trim(strip_tags($input['apiKey']));
		$output['apiPassword']			= trim(strip_tags($input['apiPassword']));
		$output['ecryptKey']			= trim($input['ecryptKey']);

		$output['sandboxCustomerID']	= trim(sanitize_text_field($input['sandboxCustomerID']));
		$output['sandboxApiKey']		= trim(strip_tags($input['sandboxApiKey']));
		$output['sandboxPassword']		= trim(strip_tags($input['sandboxPassword']));
		$output['sandboxEcryptKey']		= trim($input['sandboxEcryptKey']);

		$output['useStored']			= empty($input['useStored']) ? '' : 1;
		$output['useTest']				= empty($input['useTest']) ? '' : 1;
		$output['useBeagle']			= empty($input['useBeagle']) ? '' : 1;
		$output['roundTestAmounts']		= empty($input['roundTestAmounts']) ? '' : 1;
		$output['forceTestAccount']		= empty($input['forceTestAccount']) ? '' : 1;
		$output['sslVerifyPeer']		= empty($input['sslVerifyPeer']) ? '' : 1;

		$errNames = [
			GFEWAY_ERROR_ALREADY_SUBMITTED,
			GFEWAY_ERROR_NO_AMOUNT,
			GFEWAY_ERROR_REQ_CARD_HOLDER,
			GFEWAY_ERROR_REQ_CARD_NAME,
			GFEWAY_ERROR_EWAY_FAIL,
		];
		foreach ($errNames as $name) {
			$output[$name] = trim(sanitize_text_field($input[$name]));
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
	public function gformAfterUpdateEntry($form, $lead_id) : void {
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
