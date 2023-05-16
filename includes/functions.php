<?php
namespace webaware\gfeway;

use GFCommon;
use GFEwayException;
use GFEwayCurlException;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * compare Gravity Forms version against target
 * @param string $target
 * @param string $operator
 * @return bool
 */
function gform_version_compare($target, $operator) {
	if (class_exists('GFCommon', false)) {
		return version_compare(GFCommon::$version, $target, $operator);
	}

	return false;
}

/**
 * test whether the minimum required Gravity Forms is installed / activated
 * @return bool
 */
function has_required_gravityforms() {
	return gform_version_compare(GFEWAY_MIN_VERSION_GF, '>=');
}

/**
 * get the customer's IP address dynamically from server variables
 * @param bool $is_test_site
 * @return string
 */
function get_customer_IP($is_test_site) {
	$ip = '';

	if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
		$ip = is_IP_address($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '';
	}

	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$proxies = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$ip = trim(current($proxies));
		$ip = is_IP_address($ip) ? $ip : '';
	}

	elseif (!empty($_SERVER['REMOTE_ADDR'])) {
		$ip = is_IP_address($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
	}

	// if test mode and running on localhost, then kludge to an Aussie IP address
	if ($ip === '127.0.0.1' && $is_test_site) {
		$ip = '103.29.100.101';
	}

	// allow hookers to override for network-specific fixes
	$ip = apply_filters('gfeway_customer_ip', $ip);

	return $ip;
}

/**
 * check whether a given string is an IP address
 * @param string $maybeIP
 * @return bool
 */
function is_IP_address($maybeIP) {
	// check for IPv4 and IPv6 addresses
	return !!inet_pton($maybeIP);
}

/**
 * send data via HTTP and return response
 * @deprecated only used now for legacy Direct API and its friends
 * @param string $url
 * @param string $data
 * @param bool $sslVerifyPeer whether to validate the SSL certificate
 * @return string $response
 * @throws GFEwayCurlException
 */
function send_xml_request($url, $data, $sslVerifyPeer = true) {
	// send data via HTTPS and receive response
	$response = wp_remote_post($url, [
		'user-agent'	=> 'Gravity Forms Eway ' . GFEWAY_PLUGIN_VERSION,
		'sslverify'		=> $sslVerifyPeer,
		'timeout'		=> 60,
		'headers'		=> ['Content-Type' => 'text/xml; charset=utf-8'],
		'body'			=> $data,
	]);

	// failure to handle the http request
	if (is_wp_error($response)) {
		throw new GFEwayCurlException($response->get_error_message());
	}

	// error code returned by request
	$code = wp_remote_retrieve_response_code($response);
	if ($code !== 200) {
		$msg = wp_remote_retrieve_response_message($response);

		if (empty($msg)) {
			$msg = sprintf(__('Error posting Eway request: %s', 'gravityforms-eway'), $code);
		}
		else {
			$msg = sprintf(__('Error posting Eway request: %1$s, %2$s', 'gravityforms-eway'), $code, $msg);
		}
		throw new GFEwayException($msg);
	}

	return wp_remote_retrieve_body($response);
}
