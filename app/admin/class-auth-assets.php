<?php

namespace WPOP_Example\V_1_0\Admin;

/**
 * Register and enqueue assets used in the WordPress Admin or only when a user is authenticated here.
 *
 * Class Auth_Assets
 */
class Auth_Assets {

	/**
	 * @var string
	 */
	public $installed_dir;

	/**
	 * @var string
	 */
	public $installed_url;

	/**
	 * @var string
	 */
	public $asset_url;

	/**
	 * @var string
	 */
	public $version;

	/**
	 * Auth_Assets constructor.
	 *
	 * @param string $dir
	 * @param string $url
	 * @param string $version
	 */
	function __construct( $dir, $url, $version ) {
		$this->installed_dir = $dir;
		$this->installed_url = $url;
		$this->asset_url     = $this->installed_url . 'app/assets/';
		$this->version       = $version;

		/**
		 * Enqueue Assets
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_auth_assets' ) );

		/**
		 * Register Assets
		 */
		add_action( 'init', array( $this, 'register_stylesheets' ) );
		add_action( 'init', array( $this, 'register_scripts' ) );
	}

	/**
	 * Enqueue Assets for Authenticated Users
	 * @package Wpop_example_panel
	 */
	function enqueue_auth_assets() {
		wp_enqueue_style( 'wpop-example-panel-admin' );
		wp_enqueue_script( 'wpop-example-panel-admin' );
	}

	/**
	 * Register CSS with WordPress
	 * @package Wpop_example_panel
	 */
	function register_stylesheets() {
		wp_register_style(
			'wpop-example-panel-admin',
			$this->asset_url . 'wpop-example-panel-admin.css',
			array(),
			$this->version
		);
	}

	/**
	 * Register JavaScript with WordPress for Wpop_example_panel
	 * @package Wpop_example_panel
	 */
	function register_scripts() {
		wp_register_script(
			'wpop-example-panel-admin',
			$this->asset_url . 'wpop-example-panel-admin.js',
			array( 'jquery' ), // jquery is loaded everywhere in the wp-admin, so not enqueueing addl. scripts
			false,
			true // load in footer
		);
	}

} // END class Auth_Assets