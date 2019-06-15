<?php

namespace WordPressPhoenix\WPOP_Example\Admin;

use WPOP\V_5_0\Fields\Password;
use WPOP\V_5_0\WordPress_Options_Panels;

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
	 * @var string
	 */
	public $plugin_basedir;

	/**
	 * Add auth'd/admin functionality via new Class() instantiation, add_action() and add_filter() in this method.
	 *
	 * @param string $installed_dir
	 * @param string $installed_url
	 * @param string $version
	 * @param string $plugin_basedir
	 *
	 * @throws \Exception
	 */
	function __construct( $installed_dir, $installed_url, $version, $plugin_basedir ) {
		$this->installed_dir  = $installed_dir;
		$this->installed_url  = $installed_url;
		$this->version        = $version;
		$this->plugin_basedir = $plugin_basedir;

		$wordpress_options = new WordPress_Options_Panels( $installed_dir, $installed_url, $plugin_basedir );
		// TODO: figure out how to register a network admin page.
		/**
		 * @var \WPOP\V_5_0\Page $top_level_page
		 */
		$use_defaults_page = $wordpress_options->register_page( 'my_defaults_page', 'main_menu' );
		$use_defaults_page->initialize();

		$top_level_page             = $wordpress_options->register_page( 'my_tl_page', 'main_menu' );
		$top_level_page->page_title = "My Top Level Page";
		$top_level_page->menu_title = "My TL Page";
		$top_level_page->capability = "manage_options";
		$top_level_page->initialize();


		$sub_level_page             = &$wordpress_options->register_page( 'my_sl_page', 'sub_menu', 'tools.php' );
		$sub_level_page->page_title = "My Sub Level Page";
		$sub_level_page->menu_title = "My SL Page";
		$sub_level_page->capability = "manage_options";
		$sub_level_page->initialize();
		/**
		 * Pass the sub_level_page slug to the Panel so that it knows which "do_action" to print on. See build_parts().
		 *
		 * FIND THE SUB PAGE AT /wp-admin/tools.php?page=my_sl_page
		 * @var \WPOP\V_5_0\Panel   $sub_level_page_panel
		 * @var \WPOP\V_5_0\Section $sub_level_page_panel_section_1
		 * @var \WPOP\V_5_0\Section $sub_level_page_panel_section_2
		 * @var \WPOP\V_5_0\Section $sub_level_page_panel_section_3
		 * @var \WPOP\V_5_0\Section $sub_level_page_panel_section_4
		 * @var \WPOP\V_5_0\Section $sub_level_page_panel_section_5
		 */
		$sub_level_page_panel                   = $sub_level_page->add_panel( 'wp_sl_page_panel' );
		$sub_level_page_panel_section_1         = $sub_level_page_panel->add_section( 'first_section', '1st Section' );
		$sub_level_page_panel_section_1_field_1 = $sub_level_page_panel_section_1->add_field(
			'text',
			'my_text_field',
			[
				'label' => 'My Text Field 1',
			]
		);
		$sub_level_page_panel_section_1_field_2 = $sub_level_page_panel_section_1->add_field(
			'text',
			'my_text_field_2',
			[
				'label' => 'My Text Field 2',
			]
		);
		$sub_level_page_panel_section_2         = $sub_level_page_panel->add_section( 'second_section', '2nd Section' );
		$sub_level_page_panel_section_2_field_1 = $sub_level_page_panel_section_2->add_field(
			'password',
			'my_encrypted_api_key',
			[
				'label'       => 'My Encrypted API Key',
				'description' => 'Saving data into this field will encrypt the data into the DB, inspecting the field after save will only show you the encrypted value. ',
			]
		);
		$sub_level_page_panel_section_2_field_2 = $sub_level_page_panel_section_2->add_field(
			'value',
			'my_decrypted_value',
			[
				'label'       => 'My Decrypted API Key (Value Field)',
				'description' => 'Testing API Key Decryption (may require extra refresh upon change)',
				'value'       => Password::decrypt( get_option( 'my_encrypted_api_key' ) ),
			]
		);
		$sub_level_page_panel_section_2_field_3 = $sub_level_page_panel_section_2->add_field(
			'toggle_switch',
			'my_toggle_switch',
			[
				'label' => 'My Toggle Switch (extends checkbox)',
			]
		);
		$sub_level_page_panel_section_3         = $sub_level_page_panel->add_section( 'third_section', '3rd Section' );
		$sub_level_page_panel_section_3_field_1 = $sub_level_page_panel_section_3->add_field(
			'checkbox',
			'my_checkbox',
			[
				'label' => 'My Checkbox',
			]
		);
		$sub_level_page_panel_section_3_field_2 = $sub_level_page_panel_section_3->add_field(
			'color',
			'my_color',
			[
				'label' => 'My Color',
			]
		);
		$sub_level_page_panel_section_3_field_3 = $sub_level_page_panel_section_3->add_field(
			'editor',
			'my_editor',
			[
				'label' => 'My Editor',
			]
		);
		$sub_level_page_panel_section_3_field_4 = $sub_level_page_panel_section_3->add_field(
			'email',
			'my_email',
			[
				'label' => 'My Email',
			]
		);

		$sub_level_page_panel_section_4         = $sub_level_page_panel->add_section( 'forth_section', '4th Section' );
		$sub_level_page_panel_section_4_field_1 = $sub_level_page_panel_section_4->add_field(
			'media',
			'my_media',
			[
				'label' => 'My Media',
			]
		);
		$sub_level_page_panel_section_4_field_2 = $sub_level_page_panel_section_4->add_field(
			'multiselect',
			'my_multiselect',
			[
				'label'  => 'My Multiselect',
				'values' => [
					'party'   => 'Party',
					'fiesta'  => 'Fiesta',
					'cookout' => 'Cookout',
				],
			]
		);
		$sub_level_page_panel_section_4_field_3 = $sub_level_page_panel_section_4->add_field(
			'number',
			'my_number',
			[
				'label' => 'My Number',
			]
		);
		$sub_level_page_panel_section_4_field_4 = $sub_level_page_panel_section_4->add_field(
			'select',
			'my_select',
			[
				'label'  => 'My Select',
				'values' => [
					'small'  => 'Small',
					'medium' => 'Medium',
					'large'  => 'Large',
				],
			]
		);
		$sub_level_page_panel_section_4_field_5 = $sub_level_page_panel_section_4->add_field(
			'url',
			'my_url',
			[
				'label' => 'My URL',
			]
		);
		$sub_level_page_panel_section_4_field_6 = $sub_level_page_panel_section_4->add_field(
			'radio_buttons',
			'my_radio_buttons',
			[
				'label'  => 'My Radio Buttons',
				'values' => [
					'beginning' => 'Beginning',
					'middle'    => 'Middle',
					'end'       => 'End',
				],
			]
		);
		$sub_level_page_panel_section_5         = $sub_level_page_panel->add_section( 'fifth_section', '5th Section' );
		$sub_level_page_panel_section_5_field_1 = $sub_level_page_panel_section_5->add_field(
			'include_partial',
			'my_partial_php_file',
			[
				'label'    => 'My Partial PHP File',
				'filepath' => $plugin_basedir . '/app/partial/sample-component.php',
			]
		);
	}

}
