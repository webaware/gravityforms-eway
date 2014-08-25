<?php

/**
* class for admin screens
*/
class GFEwayAdmin {

	public $settingsURL;

	private $plugin;

	/**
	* @param GFEwayPlugin $plugin
	*/
	public function __construct($plugin) {
		$this->plugin = $plugin;

		// handle basic plugin actions and filters
		add_action('admin_init', array($this, 'adminInit'));
		add_action('admin_notices', array($this, 'checkPrerequisites'));
		add_action('plugin_action_links_' . GFEWAY_PLUGIN_NAME, array($this, 'addPluginActionLinks'));
		add_filter('plugin_row_meta', array($this, 'addPluginDetailsLinks'), 10, 2);
		add_filter('admin_enqueue_scripts', array($this, 'enqueueScripts'));

		// only if Gravity Forms is activated
		if (class_exists('GFCommon')) {
			// handle change in settings pages
			if (version_compare(GFCommon::$version, '1.6.99999', '<')) {
				// pre-v1.7 settings
				$this->settingsURL = admin_url('admin.php?page=gf_settings&addon=eWAY+Payments');
			}
			else {
				// post-v1.7 settings
				$this->settingsURL = admin_url('admin.php?page=gf_settings&subview=eWAY+Payments');
			}

			// add Gravity Forms hooks
			add_filter('gform_currency_setting_message', array($this, 'gformCurrencySettingMessage'));
			add_action('gform_payment_status', array($this, 'gformPaymentStatus'), 10, 3);
			add_action('gform_after_update_entry', array($this, 'gformAfterUpdateEntry'), 10, 2);

			// tell Gravity Forms not to put payment details into info (i.e. do put them into the new payment details box!)
			add_filter('gform_enable_entry_info_payment_details', '__return_false');

			// handle the new Payment Details box if supported
			if (version_compare(GFCommon::$version, '1.8.7.99999', '<')) {
				// pre-v1.8.8 settings
				add_action('gform_entry_info', array($this, 'gformPaymentDetails'), 10, 2);
			}
			else {
				// post-v1.8.8 settings
				add_action('gform_payment_details', array($this, 'gformPaymentDetails'), 10, 2);
			}
		}
	}

	/**
	* test whether GravityForms plugin is installed and active
	* @return boolean
	*/
	public static function isGfActive() {
		return class_exists('RGForms');
	}

	/**
	* handle admin init action
	*/
	public function adminInit() {
		if (isset($_GET['page'])) {
			switch ($_GET['page']) {
				case 'gf_settings':
					// add our settings page to the Gravity Forms settings menu
					RGForms::add_settings_page('eWAY Payments', array($this, 'optionsAdmin'));
					break;
			}
		}
	}

	/**
	* only output our stylesheet if this is our admin page
	*/
	public function enqueueScripts() {
		$ver = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? time() : GFEWAY_PLUGIN_VERSION;
		wp_enqueue_style('gfeway-admin', $this->plugin->urlBase . 'style-admin.css', false, $ver);
	}

	/**
	* check for required PHP extensions, tell admin if any are missing
	*/
	public function checkPrerequisites() {
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
		if (!self::isGfActive()) {
			include GFEWAY_PLUGIN_ROOT . 'views/requires-gravity-forms.php';
		}
	}

	/**
	* action hook for adding plugin action links
	*/
	public function addPluginActionLinks($links) {
		// add settings link, but only if GravityForms plugin is active
		if (self::isGfActive()) {
			$settings_link = sprintf('<a href="%s">%s</a>', $this->settingsURL, __('Settings'));
			array_unshift($links, $settings_link);
		}

		return $links;
	}

	/**
	* action hook for adding plugin details links
	*/
	public static function addPluginDetailsLinks($links, $file) {
		if ($file == GFEWAY_PLUGIN_NAME) {
			$links[] = '<a href="http://wordpress.org/support/plugin/gravityforms-eway">' . __('Get help') . '</a>';
			$links[] = '<a href="http://wordpress.org/plugins/gravityforms-eway/">' . __('Rating') . '</a>';
			$links[] = '<a href="http://shop.webaware.com.au/downloads/gravity-forms-eway/">' . __('Donate') . '</a>';
		}

		return $links;
	}

	/**
	* action hook for showing currency setting message
	* @param array $menus
	* @return array
	*/
	public function gformCurrencySettingMessage() {
		echo "<div class='gform_currency_message'>NB: Gravity Forms eWAY only supports Australian Dollars (AUD).</div>\n";
	}

	/**
	* action hook for building the entry details view
	* @param int $form_id
	* @param array $lead
	*/
	public function gformPaymentDetails($form_id, $lead) {
		$payment_gateway = gform_get_meta($lead['id'], 'payment_gateway');
		if ($payment_gateway == 'gfeway') {
			$authcode = gform_get_meta($lead['id'], 'authcode');
			if ($authcode) {
				echo 'Auth Code: ', esc_html($authcode), "<br /><br />\n";
			}

			$beagle_score = gform_get_meta($lead['id'], 'beagle_score');
			if ($beagle_score) {
				echo 'Beagle Score: ', esc_html($beagle_score), "<br /><br />\n";
			}
		}
	}

	/**
	* action hook for processing admin menu item
	*/
	public function optionsAdmin() {
		$admin = new GFEwayOptionsAdmin($this->plugin, 'gfeway-options', $this->settingsURL);
		$admin->process();
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
		if ($payment_status == 'Approved' || strtolower(rgpost('save')) <> 'edit') {
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
		//~ $payment_string = gform_tooltip("paypal_edit_payment_status","",true);
		$input = <<<HTML
<select name="payment_status">
 <option value="$payment_status" selected="selected">$payment_status</option>
 <option value="Approved">Approved</option>
</select>

HTML;

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
		if (strtolower(rgpost("save")) <> 'update')
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
		$lead["payment_status"] = $payment_status;

		GFFormsModel::update_lead($lead);
	}
}
