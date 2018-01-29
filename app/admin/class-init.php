<?php

namespace WPOP_Example\V_1_0\Admin;

/**
 * Class Init
 * @package Wpop_example_panel
 */
class Init {

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

		// handle authenticated stylesheets and scripts
		new Auth_Assets(
			$this->installed_dir,
			$this->installed_url,
			$this->version
		);

		// initialize site options panel
		new Options_Panel(
			$this->installed_dir,
			$this->installed_url
		);

		// initialize site options panel
		new Network_Options_Panel(
			$this->installed_dir,
			$this->installed_url
		);

		// initialize term meta panel
		new Term_Panel(
			$this->installed_dir,
			$this->installed_url
		);

		new User_Network_Panel(
			$this->installed_dir,
			$this->installed_url
		);
	}

}
