<?php
/**
 * WPOP Example Panel
 *
 * @package     Wpop_example_panel
 * @author      David Ryan - WordPress Phoenix
 * @license     GNU GPL v2.0+
 * @link        https://github.com/wordpress-phoenix
 * @version     1.0.0
 *
 * Built using WP PHX Plugin Generator v1.1.0 on Tuesday 23rd of January 2018 04:50:38 AM
 * @link https://github.com/WordPress-Phoenix/wordpress-development-toolkit
 *
 * @wordpress-plugin
 * Plugin Name: WPOP Example Panel
 * Plugin URI: https://github.com/wordpress-phoenix
 * Description: Working example of the WordPress Phoenix Options Panel
 * Version: 1.0.0
 * Author: David Ryan  - WordPress Phoenix
 * Text Domain: wpop-example-panel
 * License: GNU GPL v2.0+
 */
if ( ! function_exists( 'add_filter' ) ) { // prevent snooping file source, check wp loaded
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Check Abstract_Plugin Instantiated
 */
if ( ! class_exists( 'WPAZ_Plugin_Base\\V_2_5\\Abstract_Plugin' ) ) {
	include_once trailingslashit( dirname( __FILE__ ) ) . 'vendor/wordpress-phoenix/abstract-plugin-base/src/abstract-plugin.php';
}
				
// Load Options Panel
if ( ! class_exists( 'WPOP\V_3_5\\Page' ) ) {
	include_once  trailingslashit( dirname( __FILE__ ) )  . 'vendor/wordpress-phoenix/wordpress-options-builder-class/wordpress-phoenix-options-panel.php';
}

/**
 * Check WPOP_Example\V_1_0\Plugin Instantiated
 * (The check prevents fatal error if multiple copies of plugin are activated or namespaces aren't unique)
 */
if ( ! class_exists( 'WPOP_Example\\V_1_0\\Plugin' ) ) {
	include_once trailingslashit( dirname( __FILE__ ) ) . 'app/class-plugin.php';
} else {
	new WP_Error( '500', 'Multiple copies of WPOP_Example\V_1_0\Plugin are active' );
}

/**
 * Start WPOP Example Panel Main Plugin Class
 */
WPOP_Example\V_1_0\Plugin::run( __FILE__ );
// Please don't edit below this line.