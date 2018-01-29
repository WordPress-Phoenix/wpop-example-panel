<?php

namespace WPOP_Example\V_1_0\Admin;

use WPOP\V_3_1 as Opts;

/**
 * Class Post_Panel
 */
class Post_Panel {

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
	protected $post_meta;

	/**
	 * Post_Panel constructor.
	 *
	 * @param string $installed_dir
	 * @param string $installed_url
	 */
	function __construct( $installed_dir, $installed_url ) {
		$this->installed_dir = $installed_dir;
		$this->installed_url = $installed_url;

		$this->setup_post_meta_options();
	}

	/**
	 * Register Options Panel
	 */
	function setup_post_meta_options() {

		$sections = array(
			'simple'    => $this->general_section(),
			'advanced'  => $this->advanced_section(),
			'media'     => $this->media_section(),
			'editors'   => $this->editors_section(),
			'wordpress' => $this->wordpress_section(),
			'encrypted' => $this->encrypted_section(),
			'includes'  => $this->include_section(),
		);

		$this->post_meta = new Opts\page(
			$this->post_meta_config(),
			$sections
		);

		// initialize_panel() is a function in the opt panel Container class
		$this->post_meta->initialize_panel();
	}

	function post_meta_config() {
		return array(
			'parent_page_id' => 'edit.php',
			'id'             => 'wpop-example-post-meta-opts',
			'page_title'     => 'WPOP Post Meta Panel',
			'menu_title'     => 'WPOP Post Panel',
			'dashicon'       => 'dashicons-admin-settings',
			'api'            => 'post'
		);
	}

	function general_section() {
		return array(
			'label'    => 'General',
			'dashicon' => 'dashicons-admin-generic',
			'parts'    => array(
				'wpop_example_plugin_text'     => array(
					'label' => 'Text Field',
					'part' => 'text',
				),
				'wpop_example_plugin_textarea' => array(
					'label' => 'Textarea Field',
					'part' => 'textarea',
				),
				'wpop_example_plugin_number'   => array(
					'label' => 'Number Field',
					'part' => 'number',
				),
				'wpop_example_plugin_url'      => array(
					'label' => 'URL Field',
					'part' => 'url',
				),
				'wpop_example_plugin_email'    => array(
					'label' => 'Email Field',
					'part' => 'email',
				),
			),
		);
	}

	function advanced_section() {
		return array(
			'label'    => 'Advanced',
			'dashicon' => 'dashicons-forms',
			'parts'    => array(
				'wpop_example_plugin_select'        => array(
					'label'  => 'Select Field',
					'part'  => 'select',
					'values' => array(
						'uno'  => 'First',
						'dos'  => 'Second',
						'tres' => 'Third',
					),
				),
				'wpop_example_plugin_multiselect'   => array(
					'label'  => 'Multiselect Field',
					'part'  => 'multiselect',
					'values' => array(
						'party'   => 'Party',
						'fiesta'  => 'Fiesta',
						'cookout' => 'Cookout',
					),
				),
				'wpop_example_plugin_toggle_switch' => array(
					'label' => 'Toggle Switch Field',
					'part' => 'toggle_switch',
				),
				'wpop_example_plugin_radios'        => array(
					'label'  => 'Radio Field',
					'part'  => 'radio_buttons',
					'values' => array(
						'party'   => 'Party',
						'fiesta'  => 'Fiesta',
						'cookout' => 'Cookout',
					),
				),
			),
		);
	}

	function media_section() {
		return array(
			'label'    => 'Media',
			'dashicon' => 'dashicons-admin-media',
			'parts'    => array(
				'wpop_example_plugin_media' => array(
					'label' => 'Media Field',
					'part' => 'media'
				)
			),
		);
	}

	function editors_section() {
		return array(
			'label'    => 'Editors',
			'dashicon' => 'dashicons-edit',
			'parts'    => array(
				'wpop_example_plugin_editor' => array(
					'label' => 'Editor Field',
					'part' => 'editor',
				),
				'wpop_example_plugin_nohtml' => array(
					'label'        => 'Editor - No HTML Toggle',
					'part'        => 'Editor',
					'no_quicktags' => 'true',
				),
				'wpop_example_plugin_simple' => array(
					'label'        => 'Editor Simple',
					'part'        => 'editor',
					'teeny'        => 'true',
					'no_media'     => 'true',
					'no_quicktags' => 'true'
				),
			)
		);
	}

	function wordpress_section() {
		return array(
			'label'    => 'WordPress',
			'dashicon' => 'dashicons-wordpress-alt',
			'parts'    => array(
				'wpop_example_plugin_color' => array(
					'label' => 'Color Field',
					'part' => 'color',
				)
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
					'part' => 'password',
				),
			),
		);
	}

	function include_section() {
		return array(
			'label'     => 'Includes',
			'dashicons' => 'dashicons-file',
			'parts'     => array(
				'wpop_example_plugin_markdown_file' => array(
					'label' => 'Markdown Field',
					'part' => 'markdown',
					'filename' => $this->installed_dir . 'assets/example_include_markdown.md'
				),
			),
		);
	}

}
