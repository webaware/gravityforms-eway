<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * maybe show notice of minimum PHP version failure
 */
function gfeway_fail_php_version() {
	gfeway_load_text_domain();

	$requires = new GfEwayRequires();

	$requires->addNotice(
		gfeway_external_link(
			/* translators: %1$s: minimum required version number, %2$s: installed version number */
			sprintf(esc_html__('It requires PHP %1$s or higher; your website has PHP %2$s which is {{a}}old, obsolete, and unsupported{{/a}}.', 'gravityforms-eway'),
				esc_html(GFEWAY_PLUGIN_MIN_PHP), esc_html(PHP_VERSION)),
			'https://www.php.net/supported-versions.php'
		)
	);
	$requires->addNotice(
		/* translators: %s: minimum recommended version number */
		sprintf(esc_html__('Please upgrade your website hosting. At least PHP %s is recommended.', 'gravityforms-eway'), '7.4')
	);
}

/**
 * load text translations
 */
function gfeway_load_text_domain() {
	load_plugin_textdomain('gravityforms-eway');
}

/**
 * replace link placeholders with an external link
 * @param string $template
 * @param string $url
 * @return string
 */
function gfeway_external_link($template, $url) {
	$search = array(
		'{{a}}',
		'{{/a}}',
	);
	$replace = array(
		sprintf('<a rel="noopener" target="_blank" href="%s">', esc_url($url)),
		'</a>',
	);
	return str_replace($search, $replace, $template);
}

/**
 * replace link placeholders with an internal link
 * @param string $template
 * @param string $url
 * @return string
 */
function gfeway_internal_link($template, $url) {
	$search = array(
		'{{a}}',
		'{{/a}}',
	);
	$replace = array(
		sprintf('<a href="%s">', esc_url($url)),
		'</a>',
	);
	return str_replace($search, $replace, $template);
}
