<?php
/*
Plugin Name: GravityForms eWAY
Plugin URI: http://snippets.webaware.com.au/wordpress-plugins/gravityforms-eway/
Description: Integrates Gravity Forms with eWAY payment gateway, enabling end users to purchase goods and services through Gravity Forms.
Version: 1.3.0
Author: WebAware
Author URI: http://www.webaware.com.au/
*/

/*
copyright (c) 2012 WebAware Pty Ltd (email : rmckay@webaware.com.au)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
useful references:
http://www.gravityhelp.com/forums/topic/credit-card-validating#post-44438
http://www.gravityhelp.com/documentation/page/Gform_creditcard_types
http://www.gravityhelp.com/documentation/page/Gform_enable_credit_card_field
http://www.gravityhelp.com/documentation/page/Form_Object
http://www.gravityhelp.com/documentation/page/Entry_Object
*/

if (!defined('GFEWAY_PLUGIN_ROOT')) {
	define('GFEWAY_PLUGIN_ROOT', dirname(__FILE__) . '/');
	define('GFEWAY_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));
	define('GFEWAY_PLUGIN_OPTIONS', 'gfeway_plugin');
	define('GFEWAY_PLUGIN_VERSION', '1.2.2');

	// error message names
	define('GFEWAY_ERROR_ALREADY_SUBMITTED', 'gfeway_err_already');
	define('GFEWAY_ERROR_NO_AMOUNT', 'gfeway_err_no_amount');
	define('GFEWAY_ERROR_REQ_CARD_HOLDER', 'gfeway_err_req_card_holder');
	define('GFEWAY_ERROR_REQ_CARD_NAME', 'gfeway_err_req_card_name');
	define('GFEWAY_ERROR_EWAY_FAIL', 'gfeway_err_eway_fail');
}

/**
* autoload classes as/when needed
*
* @param string $class_name name of class to attempt to load
*/
function gfeway_autoload($class_name) {
	static $classMap = array (
		'GFEwayAdmin'						=> 'class.GFEwayAdmin.php',
		'GFEwayFormData'					=> 'class.GFEwayFormData.php',
		'GFEwayOptionsAdmin'				=> 'class.GFEwayOptionsAdmin.php',
		'GFEwayPayment'						=> 'class.GFEwayPayment.php',
		'GFEwayPlugin'						=> 'class.GFEwayPlugin.php',
		'GFEwayRecurringField'				=> 'class.GFEwayRecurringField.php',
		'GFEwayRecurringPayment'			=> 'class.GFEwayRecurringPayment.php',
		'GFEwayRecurringResponse'			=> 'class.GFEwayRecurringResponse.php',
		'GFEwayResponse'					=> 'class.GFEwayResponse.php',
	);

	if (isset($classMap[$class_name])) {
		require GFEWAY_PLUGIN_ROOT . $classMap[$class_name];
	}
}

// register a class (static) method for autoloading required classes
spl_autoload_register('gfeway_autoload');

// instantiate the plug-in
GFEwayPlugin::getInstance();
