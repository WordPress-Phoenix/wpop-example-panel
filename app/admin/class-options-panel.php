<?php

namespace WPOP_Example\V_1_0\Admin;

use WPOP\V_3_0 as Opts;

/**
 * Class Options_Panel
 */
class Options_Panel {

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
	protected $site_options;

	/**
	 * Options_Panel constructor.
	 *
	 * @param string $installed_dir
	 * @param string $installed_url
	 */
	function __construct( $installed_dir, $installed_url ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;

		$this->setup_site_options();
	}

	/**
	 * Register Options Panel
	 */
	function setup_site_options() {

		$sections = array(
			'simple'   => $this->general_section(),
			'advanced' => $this->advanced_section(),
//			'wordpress' => $this->wordpress_section(),
			'encrypted' => $this->encrypted_section()
		);

		$this->site_options = new Opts\page( $this->site_options_config(), $sections );

		// initialize_panel() is a function in the opt panel Container class
		$this->site_options->initialize_panel();
	}

	function general_section() {
		return array(
			'label'    => 'General',
			'dashicon' => 'dashicons-admin-generic',
			'parts'    => array(
				'wpop_example_plugin_text'     => array(
					'label' => 'Text Field',
					'field' => 'Text',
				),
				'wpop_example_plugin_textarea' => array(
					'label' => 'Textarea Field',
					'field' => 'Textarea',
				),
				'wpop_example_plugin_number'   => array(
					'label' => 'Number Field',
					'field' => 'Number',
				),
				'wpop_example_plugin_url'      => array(
					'label' => 'Url Field',
					'field' => 'Url',
				),
			),
		);
	}

	function advanced_section() {
		return array(
			'label'    => 'Advanced',
			'dashicon' => 'dashicons-admin-generic',
			'parts'    => array(
				'wpop_example_plugin_select'        => array(
					'label'  => 'Select Field',
					'field'  => 'Select',
					'values' => array(
						'uno'  => 'First',
						'dos'  => 'Second',
						'tres' => 'Third',
					),
				),
				'wpop_example_plugin_multiselect'   => array(
					'label'  => 'Multiselect Field',
					'field'  => 'Multiselect',
					'values' => array(
						'party'   => 'Party',
						'fiesta'  => 'Fiesta',
						'cookout' => 'Cookout',
					),
				),
				'wpop_example_plugin_toggle_switch' => array(
					'label' => 'Toggle Switch Field',
					'field' => 'Toggle_Switch',
				),
				'wpop_example_plugin_radios'        => array(
					'label' => 'Radio Field',
					'field' => 'Radio_Buttons',
					'values' => array(
						'party'   => 'Party',
						'fiesta'  => 'Fiesta',
						'cookout' => 'Cookout',
					),
				),
				'wpop_example_plugin_partial'       => array(
					'label' => 'Partial Field',
					'field' => 'Include_Partial',
				),
				'wpop_example_plugin_markup'        => array(
					'label' => 'Radio Field',
					'field' => 'Include_Markup',
				),
			),
		);
	}

	function wordpress_section() {
		return array(
			'label'    => 'WordPress',
			'dashicon' => 'dashicons-wordpress-alt',
			'parts'    => array(
				'wpop_example_plugin_color_picker' => array(
					'label' => 'Color Field',
					'field' => 'Color_Picker',
				),
//				'wpop_example_plugin_editor' => array(
//					'label' => 'Editor Field',
//					'field' => 'Editor',
//				),
				'wpop_example_plugin_media'        => array(
					'label' => 'Media Field',
					'field' => 'Media'
				),
			),
		);
	}

	function encrypted_section() {
		return array(
			'label'    => 'Encrypted',
			'dashicon' => 'dashicons-lock',
			'parts'    => array(
				'wpop_example_plugin_password' => array(
					'label' => 'Password Field',
					'field' => 'Password',
				),
			),
		);
	}

	function site_options_config() {
		return array(
			'parent_page_id' => 'options-general.php',
			'id'             => 'wpop-example-panel-opts',
			'page_title'     => 'WPOP Example Panel Settings',
			'menu_title'     => 'WPOP Example Panel',
			'dashicon'       => 'dashicons-admin-settings',
			'api'            => 'site'
		);
	}

}
