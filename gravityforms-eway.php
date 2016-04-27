<?php
/*
Plugin Name: Gravity Forms eWAY
Plugin URI: http://shop.webaware.com.au/downloads/gravity-forms-eway/
Description: Integrate Gravity Forms with eWAY payment gateway, enabling end users to purchase goods and services through Gravity Forms.
Version: 2.2.0
Author: WebAware
Author URI: http://webaware.com.au/
Text Domain: gravityforms-eway
Domain Path: /languages/
*/

/*
copyright (c) 2012-2016 WebAware Pty Ltd (email : support@webaware.com.au)

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

if (!defined('ABSPATH')) {
	exit;
}

define('GFEWAY_PLUGIN_FILE', __FILE__);
define('GFEWAY_PLUGIN_ROOT', dirname(__FILE__) . '/');
define('GFEWAY_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));
define('GFEWAY_PLUGIN_OPTIONS', 'gfeway_plugin');
define('GFEWAY_PLUGIN_VERSION', '2.2.0');

// error message names
define('GFEWAY_ERROR_ALREADY_SUBMITTED',	'gfeway_err_already');
define('GFEWAY_ERROR_NO_AMOUNT',			'gfeway_err_no_amount');
define('GFEWAY_ERROR_REQ_CARD_HOLDER',		'gfeway_err_req_card_holder');
define('GFEWAY_ERROR_REQ_CARD_NAME',		'gfeway_err_req_card_name');
define('GFEWAY_ERROR_EWAY_FAIL',			'gfeway_err_eway_fail');

// custom fields
define('GFEWAY_FIELD_RECURRING', 'gfewayrecurring');


// instantiate the plug-in
require GFEWAY_PLUGIN_ROOT . 'includes/class.GFEwayPlugin.php';
GFEwayPlugin::getInstance();
