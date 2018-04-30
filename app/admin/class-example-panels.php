<?php

namespace WordPressPhoenix\WPOP_Example\Admin;

use WPOP\V_4_1 as Opts;

/**
 * Class Example_Blog_Options
 * @package WordPressPhoenix\WPOP_Example\Admin
 */
class Example_Panels {
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
	public $installed_ver;
	/**
	 * @var object|\stdClass
	 */
	protected $site_options;
	/**
	 * @var
	 */
	protected $network_options;
	/**
	 * @var
	 */
	protected $user_network_meta;
	/**
	 * @var
	 */
	protected $post_metadata;
	/**
	 * @var
	 */
	protected $user_metadata;
	/**
	 * @var
	 */
	protected $term_metadata;

	/**
	 * Example_Blog_Options constructor.
	 *
	 * @param $dir string
	 * @param $url string
	 * @param $version string
	 */
	function __construct( $dir, $url, $version ) {
		$this->installed_dir = $dir;
		$this->installed_url = $url;
		$this->installed_ver = $version;

		$this->build_all_panels();
	}

	function base_page_config( $type ) {
		$parent_page_id = ( 'network' === $type || 'user network' === $type ) ? 'settings.php' : 'options-general.php';
		return array(
			'parent_page_id' => $parent_page_id,
			'id'             => 'wpop-example-' . sanitize_title_with_dashes( $type ) . '-opts',
			'page_title'     => 'WPOP Example ' . ucwords( $type ) . ' Settings',
			'menu_title'     => 'WPOP ' . ucwords( $type ) . ' Example',
			'dashicon'       => 'dashicons-admin-settings',
			'api'            => $type,
		);
	}

	/**
	 * @return array
	 */
	function base_sections_config() {
		return array(
			'simple'    => $this->general_section(),
			'advanced'  => $this->advanced_section(),
			'media'     => $this->media_section(),
			'editors'   => $this->editors_section(),
			'wordpress' => $this->wordpress_section(),
			'encrypted' => $this->encrypted_section(),
			'includes'  => $this->include_section(),
		);
	}

	/**
	 *
	 */
	function build_all_panels() {
		/**
		 * Build Normal Site Options
		 */
		$this->site_options = new Opts\page(
			$this->base_page_config( 'site' ),
			$this->base_sections_config()
		);
		$this->site_options->initialize_panel();
		/**
		 * Maybe Build Network Options
		 */
		if ( is_multisite() && false === Opts\panel::is_wordpress_vip_or_vip_go() ) {
			$this->network_options = new Opts\page(
				$this->base_page_config( 'network' ),
				$this->base_sections_config()
			);
			$this->network_options->initialize_panel();

			$this->user_network_meta = new Opts\page(
				$this->base_page_config( 'user network' ),
				$this->base_sections_config()
			);
			$this->user_network_meta->initialize_panel();
		}
		/**
		 * Build Example Metadata API Panels
		 */

		$this->post_metadata = new Opts\page(
			$this->base_page_config( 'post' ),
			$this->base_sections_config()
		);
		$this->post_metadata->initialize_panel();

		$this->term_metadata = new Opts\page(
			$this->base_page_config( 'term' ),
			$this->base_sections_config()
		);
		$this->term_metadata->initialize_panel();

		$this->user_metadata = new Opts\page(
			$this->base_page_config( 'user' ),
			$this->base_sections_config()
		);
		$this->user_metadata->initialize_panel();

	}

	function general_section() {
		return array(
			'label'    => 'General',
			'dashicon' => 'dashicons-admin-generic',
			'parts'    => array(
				'wpop_example_plugin_text'     => array(
					'label' => 'Text Field',
					'part'  => 'text',
				),
				'wpop_example_plugin_textarea' => array(
					'label' => 'Textarea Field',
					'part'  => 'textarea',
				),
				'wpop_example_plugin_number'   => array(
					'label' => 'Number Field',
					'part'  => 'number',
				),
				'wpop_example_plugin_url'      => array(
					'label' => 'URL Field',
					'part'  => 'url',
				),
				'wpop_example_plugin_email'    => array(
					'label' => 'Email Field',
					'part'  => 'email',
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
					'part'   => 'select',
					'values' => array(
						'uno'  => 'First',
						'dos'  => 'Second',
						'tres' => 'Third',
					),
				),
				'wpop_example_plugin_multiselect'   => array(
					'label'  => 'Multiselect Field',
					'part'   => 'multiselect',
					'values' => array(
						'party'   => 'Party',
						'fiesta'  => 'Fiesta',
						'cookout' => 'Cookout',
					),
				),
				'wpop_example_plugin_toggle_switch' => array(
					'label' => 'Toggle Switch Field',
					'part'  => 'toggle_switch',
				),
				'wpop_example_plugin_radios'        => array(
					'label'  => 'Radio Field',
					'part'   => 'radio_buttons',
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
					'part'  => 'media'
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
					'part'  => 'editor',
				),
				'wpop_example_plugin_nohtml' => array(
					'label'        => 'Editor - No HTML Toggle',
					'part'         => 'Editor',
					'no_quicktags' => 'true',
				),
				'wpop_example_plugin_simple' => array(
					'label'        => 'Editor Simple',
					'part'         => 'editor',
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
					'part'  => 'color',
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
					'part'  => 'password',
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
					'label'    => 'Markdown Field',
					'part'     => 'markdown',
					'filename' => $this->installed_dir . 'assets/example_include_markdown.md'
				),
			),
		);
	}
}
