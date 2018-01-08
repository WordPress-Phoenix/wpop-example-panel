<?php
/**
 * Plugin Name: Options Panel Examples
 * Plugin URI: https://github.com/
 */

//avoid direct calls to this file, because now WP core and framework has been used
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
// use composer to load
//include_once 'vendor/autoload.php';

// manual-load composer dependencies because it ain't hard
if ( ! class_exists( 'WPAZ_Plugin_Base\\V_2_5\\Abstract_Plugin') ) {
	include_once 'vendor/wordpress-phoenix/abstract-plugin-base/src/abstract-plugin.php';
}
if ( ! class_exists( 'WPOP\V3' ) ) {
	include_once 'vendor/wordpress-phoenix/wordpress-options-builder-class/builder.php';
}

include_once 'app/class-plugin.php';
WPOP\Example\Plugin::run( __FILE__ );