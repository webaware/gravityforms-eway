<?php
/*
Plugin Name: Gravity Forms eWAY
Plugin URI: http://shop.webaware.com.au/downloads/gravity-forms-eway/
Description: Integrates Gravity Forms with eWAY payment gateway, enabling end users to purchase goods and services through Gravity Forms.
Version: 1.6.1
Author: WebAware
Author URI: http://webaware.com.au/
*/

/*
copyright (c) 2012-2014 WebAware Pty Ltd (email : support@webaware.com.au)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if (!defined('GFEWAY_PLUGIN_ROOT')) {
	define('GFEWAY_PLUGIN_ROOT', dirname(__FILE__) . '/');
	define('GFEWAY_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));
	define('GFEWAY_PLUGIN_FILE', __FILE__);
	define('GFEWAY_PLUGIN_OPTIONS', 'gfeway_plugin');
	define('GFEWAY_PLUGIN_VERSION', '1.6.1');

	// error message names
	define('GFEWAY_ERROR_ALREADY_SUBMITTED', 'gfeway_err_already');
	define('GFEWAY_ERROR_NO_AMOUNT', 'gfeway_err_no_amount');
	define('GFEWAY_ERROR_REQ_CARD_HOLDER', 'gfeway_err_req_card_holder');
	define('GFEWAY_ERROR_REQ_CARD_NAME', 'gfeway_err_req_card_name');
	define('GFEWAY_ERROR_EWAY_FAIL', 'gfeway_err_eway_fail');

	// custom fields
	define('GFEWAY_FIELD_RECURRING', 'gfewayrecurring');
}

/**
* autoload classes as/when needed
*
* @param string $class_name name of class to attempt to load
*/
function gfeway_autoload($class_name) {
	static $classMap = array (
		'GFEwayAdmin'						=> 'includes/class.GFEwayAdmin.php',
		'GFEwayFormData'					=> 'includes/class.GFEwayFormData.php',
		'GFEwayOptionsAdmin'				=> 'includes/class.GFEwayOptionsAdmin.php',
		'GFEwayPayment'						=> 'includes/class.GFEwayPayment.php',
		'GFEwayPlugin'						=> 'includes/class.GFEwayPlugin.php',
		'GFEwayRecurringField'				=> 'includes/class.GFEwayRecurringField.php',
		'GFEwayRecurringPayment'			=> 'includes/class.GFEwayRecurringPayment.php',
		'GFEwayStoredPayment'				=> 'includes/class.GFEwayStoredPayment.php',
	);

	if (isset($classMap[$class_name])) {
		require GFEWAY_PLUGIN_ROOT . $classMap[$class_name];
	}
}
spl_autoload_register('gfeway_autoload');

// instantiate the plug-in
GFEwayPlugin::getInstance();
