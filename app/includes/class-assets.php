<?php

namespace WPOP_Example\V_1_0\Includes;

/**
 * Class Assets
 * @package Wpop_example_panel
 */
class Assets {

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
	 * Assets constructor.
	 *
	 * @param $dir
	 * @param $url
	 * @param $version
	 */
	function __construct( $dir, $url, $version ) {
		$this->installed_dir = $dir;
		$this->installed_url = $url;
		$this->asset_url     = $this->installed_url . 'app/assets/';

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
	 * Enqueue Assets
	 * @package Wpop_example_panel
	 */
	function enqueue_auth_assets() {
		wp_enqueue_style( 'wpop-example-panel-main' );
		wp_enqueue_script( 'wpop-example-panel-main' );
	}

	/**
	 * Register CSS with WordPress
	 * @package Wpop_example_panel
	 */
	function register_stylesheets() {
		wp_register_style(
			'wpop-example-panel-main',
			$this->asset_url . 'wpop-example-panel-main.css'
		);
	}

	/**
	 * Register JavaScript with WordPress
	 * @package Wpop_example_panel
	 */
	function register_scripts() {
		wp_register_script(
			'wpop-example-panel-main',
			$this->asset_url . 'wpop-example-panel-main.js'
		);
	}

} // END class Assets
