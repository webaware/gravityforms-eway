<?php
if (!defined('ABSPATH')) {
	exit;
}

const GFEWAY_PLUGIN_OPTIONS					= 'gfeway_plugin';

// error message names
const GFEWAY_ERROR_ALREADY_SUBMITTED		= 'gfeway_err_already';
const GFEWAY_ERROR_NO_AMOUNT				= 'gfeway_err_no_amount';
const GFEWAY_ERROR_REQ_CARD_HOLDER			= 'gfeway_err_req_card_holder';
const GFEWAY_ERROR_REQ_CARD_NAME			= 'gfeway_err_req_card_name';
const GFEWAY_ERROR_EWAY_FAIL				= 'gfeway_err_eway_fail';

// custom fields
const GFEWAY_FIELD_RECURRING				= 'gfewayrecurring';

// minimum versions required
const GFEWAY_MIN_VERSION_GF					= '1.9.15';

/**
 * custom exception types
 */
class GFEwayException extends Exception {}
class GFEwayCurlException extends Exception {}

/**
 * kick start the plugin
 */
add_action('plugins_loaded', function() {
	require GFEWAY_PLUGIN_ROOT . 'includes/functions.php';
	require GFEWAY_PLUGIN_ROOT . 'includes/class.GFEwayPlugin.php';
	GFEwayPlugin::getInstance()->addHooks();
}, 5);

/**
 * autoload classes as/when needed
 * @param string $class_name name of class to attempt to load
 */
spl_autoload_register(function($class_name) {
	static $classMap = [
		'GFEwayPayment'						=> 'includes/class.GFEwayPayment.php',
		'GFEwayRapidAPI'					=> 'includes/class.GFEwayRapidAPI.php',
		'GFEwayRapidAPIResponse'			=> 'includes/class.GFEwayRapidAPIResponse.php',
		'GFEwayRecurringPayment'			=> 'includes/class.GFEwayRecurringPayment.php',
		'GFEwayStoredPayment'				=> 'includes/class.GFEwayStoredPayment.php',
	];

	if (isset($classMap[$class_name])) {
		require GFEWAY_PLUGIN_ROOT . $classMap[$class_name];
	}
});
