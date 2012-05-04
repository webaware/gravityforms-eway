<?php

/**
* class for admin screens
*/
class GFEwayAdmin {

	const MENU_PAGE = 'gfeway';					// slug for menu page(s)

	private $plugin;

	/**
	* @param GFEwayPlugin $plugin
	*/
	public function __construct($plugin) {
		$this->plugin = $plugin;

		// add GravityForms hooks
		add_filter("gform_addon_navigation", array($this, 'gformAddonNavigation'));
		add_filter('gform_currency_setting_message', array($this, 'gformCurrencySettingMessage'));

		// add action hook for adding plugin action links
		add_action('plugin_action_links_' . GFEWAY_PLUGIN_NAME, array($this, 'addPluginActionLinks'));

		// hook for adding links to plugin info
		add_filter('plugin_row_meta', array($this, 'addPluginDetailsLinks'), 10, 2);

		// hook for enqueuing admin styles
		add_filter('admin_print_styles', array($this, 'printStyles'));
	}

	/**
	* only output our stylesheet if this is our admin page
	*/
	public function printStyles() {
		$page = stripslashes($_GET['page']);

		if (stripos($page, self::MENU_PAGE) === 0)
			wp_enqueue_style('gfeway-admin', "{$this->plugin->urlBase}style-admin.css", FALSE, '1');
	}

	/**
	* action hook for adding plugin action links
	*/
	public function addPluginActionLinks($links) {
		// add settings link
		$settings_link = '<a href="admin.php?page=' . self::MENU_PAGE . '-options">' . __('Settings') . '</a>';
		array_unshift($links, $settings_link);

		return $links;
	}

	/**
	* action hook for adding plugin details links
	*/
	public static function addPluginDetailsLinks($links, $file) {
		// add settings link
		if ($file == GFEWAY_PLUGIN_NAME) {
			$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8V9YCKATQHKEN" title="Please consider making a donation to help support maintenance and further development of this plugin.">'
				. __('Donate') . '</a>';
		}

		return $links;
	}

	/**
	* action hook for building GravityForms navigation
	* @param array $menus
	* @return array
	*/
	public function gformAddonNavigation($menus) {
		// add menu item for options
		$menus[] = array('name' => self::MENU_PAGE.'-options', 'label' => 'eWAY Payments', 'callback' => array($this, 'optionsAdmin'), 'permission' => 'manage_options');

        return $menus;
	}

	/**
	* action hook for building GravityForms navigation
	* @param array $menus
	* @return array
	*/
	public function gformCurrencySettingMessage() {
		echo "<div class='gform_currency_message'>eWAY payments only supports Australian Dollars (AUD).</div>\n";
	}

	/**
	* action hook for processing admin menu item
	*/
	public function optionsAdmin() {
		$admin = new GFEwayOptionsAdmin($this->plugin, self::MENU_PAGE.'-options');
		$admin->process();
	}
}
