<?php

namespace WPOP\Example;

use WPAZ_Plugin_Base\V_2_5\Abstract_Plugin;

/**
 * Class App
 */
class Plugin extends Abstract_Plugin {

	public static $autoload_class_prefix = __NAMESPACE__;
	protected static $current_file = __FILE__;
	public static $autoload_type = 'psr-4';
	// Set to 2 when you use 2 namespaces in the main app file
	public static $autoload_ns_match_depth = 2;

	public function onload( $instance ) {
		// Nothing yet
	} // END public function __construct

	public function init() {
		do_action( get_called_class() . '_before_init' );

		do_action( get_called_class() . '_after_init' );
	}

	public function authenticated_init() {
		if ( is_user_logged_in() ) {
			new Full_Demo();
		}
	}

	protected function defines_and_globals() {
		// None yet.
	}

} // END class