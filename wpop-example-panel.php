<?php
/**
 * WPOP Example Panel
 *
 * @wordpress-plugin
 * @package     Wpop_example_panel
 * @author      David Ryan - WordPress Phoenix
 * @license     GNU GPL v2.0+
 * @link        https://github.com/wordpress-phoenix
 * @version     2.0.0
 *
 * Built with WP PHX WordPress Development Toolkit v3.0.0 on Sunday 8th of April 2018 05:54:29 PM
 * @link https://github.com/WordPress-Phoenix/wordpress-development-toolkit
 *
 * Plugin Name: WPOP Example Panel
 * Plugin URI: https://github.com/wordpress-phoenix
 * Description: Examples of implementing the WPOP class as options and metadata fields.
 * Version: 2.0.0
 * Author: David Ryan  - WordPress Phoenix
 * Text Domain: wpop-example-panel
 * License: GNU GPL v2.0+
 */

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit(); /* protects plugin source from public view */
}

$current_dir = trailingslashit( dirname( __FILE__ ) );

/**
 * 3RD PARTY DEPENDENCIES
 * (manually include_once dependencies installed via composer for safety)
 */
if ( ! class_exists( '\\WPAZ_Plugin_Base\\V_2_6\\Abstract_Plugin' ) ) {
	include_once $current_dir . 'lib/wordpress-phoenix/abstract-plugin-base/src/abstract-plugin.php';
}

if ( ! class_exists( '\\Parsedown' ) ) {
	include_once  $current_dir . 'lib/erusev/parsedown/Parsedown.php';
}

if ( ! class_exists( '\\WPOP\\V_4_1\\Panel' ) ) {
	include_once $current_dir . 'lib/wordpress-phoenix/wordpress-phoenix-options-panel/wpop-init.php';
}

/**
 * INTERNAL DEPENDENCIES (autoloader defined in main plugin class)
 */
include_once $current_dir . 'app/class-plugin.php';

WordPressPhoenix\WPOP_Example\Plugin::run( __FILE__ );
// Please don't edit below this line.
