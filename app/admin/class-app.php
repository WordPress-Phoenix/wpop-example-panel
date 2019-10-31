<?php

namespace WordPressPhoenix\WPOP_Example\Admin;

/**
 * Class App
 *
 * @package Wpop_example_panel
 */
class App {

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
	public $version;

	/**
	 * Add auth'd/admin functionality via new Class() instantiation, add_action() and add_filter() in this method.
	 *
	 * @param string $installed_dir
	 * @param string $installed_url
	 * @param string $version
	 */
	function __construct( $installed_dir, $installed_url, $version ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;
		$this->version       = $version;

		// Initialize the panels class object.
		$wpop = new \WordPress_Options_Panels( $this->installed_dir, $this->installed_url );

		new Example_Panels(
			$this->installed_dir,
			$this->installed_url,
			$this->version,
			$wpop
		);
	}

}
