<?php
/*
Plugin Name: Gravity Forms Eway
Plugin URI: https://shop.webaware.com.au/downloads/gravity-forms-eway/
Description: Easily create online payment forms with Gravity Forms and Eway.
Version: 2.4.0
Author: WebAware
Author URI: https://shop.webaware.com.au/
Text Domain: gravityforms-eway
*/

/*
copyright (c) 2012-2021 WebAware Pty Ltd (email : support@webaware.com.au)

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
define('GFEWAY_PLUGIN_MIN_PHP', '7.0');
define('GFEWAY_PLUGIN_VERSION', '2.4.0');

require GFEWAY_PLUGIN_ROOT . 'includes/functions-global.php';
require GFEWAY_PLUGIN_ROOT . 'includes/class.Requires.php';

if (version_compare(PHP_VERSION, GFEWAY_PLUGIN_MIN_PHP, '<')) {
	add_action('admin_notices', 'gfeway_fail_php_version');
	return;
}

require GFEWAY_PLUGIN_ROOT . 'includes/bootstrap.php';
