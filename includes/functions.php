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
 */
function gform_version_compare(string $target, string $operator) : bool {
	if (class_exists('GFCommon', false)) {
		return version_compare(GFCommon::$version, $target, $operator);
	}

	return false;
}

/**
 * test whether the minimum required Gravity Forms is installed / activated
 */
function has_required_gravityforms() : bool {
	return gform_version_compare(GFEWAY_MIN_VERSION_GF, '>=');
}

/**
 * sanitise the customer title, to avoid error V6058: Invalid Customer Title
 */
function sanitise_customer_title(?string $title) : string {
	if (empty($title)) {
		return '';
	}

	$valid = [
		'mr'			=> 'Mr.',
		'master'		=> 'Mr.',
		'ms'			=> 'Ms.',
		'mrs'			=> 'Mrs.',
		'missus'		=> 'Mrs.',
		'miss'			=> 'Miss',
		'dr'			=> 'Dr.',
		'doctor'		=> 'Dr.',
		'sir'			=> 'Sir',
		'prof'			=> 'Prof.',
		'professor'		=> 'Prof.',
	];

	$simple = rtrim(strtolower(trim($title)), '.');

	return $valid[$simple] ?? '';
}

/**
 * get the customer's IP address dynamically from server variables
 */
function get_customer_IP(bool $is_test_site) : string {
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
 */
function is_IP_address(string $maybeIP) : bool {
	// check for IPv4 and IPv6 addresses
	return !!inet_pton($maybeIP);
}

/**
 * format amount per currency for the gateway
 */
function format_currency_for_eway(float $amount, string $currency) : string {
	if (currency_has_decimals($currency)) {
		$value = number_format($amount * 100, 0, '', '');
	}
	else {
		$value = number_format($amount, 0, '', '');
	}

	return $value;
}

/**
 * check for currency with decimal places (e.g. "cents")
 */
function currency_has_decimals(string $currencyCode) : bool {
	$no_decimals = [
		'BIF',
		'CLP',
		'DJF',
		'GNF',
		'ISK',
		'JPY',
		'KMF',
		'KRW',
		'PYG',
		'RWF',
		'UGX',
		'UYI',
		'VND',
		'VUV',
		'XAF',
		'XOF',
		'XPF',
	];
	return !in_array($currencyCode, $no_decimals);
}

/**
 * send data via HTTP and return response
 * @deprecated only used now for legacy Direct API and its friends
 * @throws GFEwayCurlException
 */
function send_xml_request(string $url, string $data, bool $sslVerifyPeer = true) : string {
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
