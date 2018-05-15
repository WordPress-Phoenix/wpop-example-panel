<?php

namespace WordPressPhoenix\WPOP_Example\Admin;

use WPOP\V_4_1 as Opts;

/**
 * Class Example_Blog_Options
 *
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
	 * @param $dir     string
	 * @param $url     string
	 * @param $version string
	 */
	function __construct( $dir, $url, $version, $wpop ) {
		$this->installed_dir = $dir;
		$this->installed_url = $url;
		$this->installed_ver = $version;

		$this->build_all_panels( $wpop );
	}

	/**
	 * Base config for all pages in this factory.
	 *
	 * @param string $type
	 * @param \WordPress_Options_Panels $wpop
	 *
	 * @return array
	 */
	function base_page_config( $type, $wpop ) {
		$parent_page_id = ( 'network' === $type || 'user network' === $type ) ? 'settings.php' : 'options-general.php';

		return [
			'parent_page_id'    => $parent_page_id,
			'id'                => 'wpop-example-' . sanitize_title_with_dashes( $type ) . '-opts',
			'page_title'        => 'WPOP Example ' . ucwords( $type ) . ' Settings',
			'menu_title'        => 'WPOP ' . ucwords( $type ) . ' Example',
			'dashicon'          => 'dashicons-admin-settings',
			'api'               => $type,
			'installed_dir'     => $wpop->installed_dir,
			'installed_dir_uri' => $wpop->installed_dir_uri,
		];
	}

	/**
	 * @return array
	 */
	function base_sections_config() {
		return [
			'simple'    => $this->general_section(),
			'advanced'  => $this->advanced_section(),
			'media'     => $this->media_section(),
			'editors'   => $this->editors_section(),
			'wordpress' => $this->wordpress_section(),
			'encrypted' => $this->encrypted_section(),
			'includes'  => $this->include_section(),
		];
	}

	/**
	 * Build All Panels from factories.
	 *
	 * @param \WordPress_Options_Panels $wpop Options panel instance.
	 */
	function build_all_panels( $wpop ) {
		/**
		 * Build Normal Site Options
		 */
		$this->site_options = new Opts\Page(
			$this->base_page_config( 'site', $wpop ),
			$this->base_sections_config()
		);
		$this->site_options->initialize_panel();
		/**
		 * Maybe Build Network Options
		 */
		if ( is_multisite() && false === Opts\panel::is_wordpress_vip_or_vip_go() ) {
			$this->network_options = new Opts\Page(
				$this->base_page_config( 'network', $wpop ),
				$this->base_sections_config()
			);
			$this->network_options->initialize_panel();

			$this->user_network_meta = new Opts\Page(
				$this->base_page_config( 'user network', $wpop ),
				$this->base_sections_config()
			);
			$this->user_network_meta->initialize_panel();
		}
		/**
		 * Build Example Metadata API Panels
		 */

		$this->post_metadata = new Opts\Page(
			$this->base_page_config( 'post', $wpop ),
			$this->base_sections_config()
		);
		$this->post_metadata->initialize_panel();

		$this->term_metadata = new Opts\Page(
			$this->base_page_config( 'term', $wpop ),
			$this->base_sections_config()
		);
		$this->term_metadata->initialize_panel();

		$this->user_metadata = new Opts\Page(
			$this->base_page_config( 'user', $wpop ),
			$this->base_sections_config()
		);
		$this->user_metadata->initialize_panel();

	}

	/**
	 * @return array
	 */
	function general_section() {
		return [
			'label'    => 'General',
			'dashicon' => 'dashicons-admin-generic',
			'parts'    => [
				'wpop_example_plugin_text'     => [
					'label' => 'Text Field',
					'part'  => 'text',
				],
				'wpop_example_plugin_textarea' => [
					'label' => 'Textarea Field',
					'part'  => 'textarea',
				],
				'wpop_example_plugin_number'   => [
					'label' => 'Number Field',
					'part'  => 'number',
				],
				'wpop_example_plugin_url'      => [
					'label' => 'URL Field',
					'part'  => 'url',
				],
				'wpop_example_plugin_email'    => [
					'label' => 'Email Field',
					'part'  => 'email',
				],
			],
		];
	}

	/**
	 * @return array
	 */
	function advanced_section() {
		return [
			'label'    => 'Advanced',
			'dashicon' => 'dashicons-forms',
			'parts'    => [
				'wpop_example_plugin_select'        => [
					'label'  => 'Select Field',
					'part'   => 'select',
					'values' => [
						'uno'  => 'First',
						'dos'  => 'Second',
						'tres' => 'Third',
					],
				],
				'wpop_example_plugin_multiselect'   => [
					'label'  => 'Multiselect Field',
					'part'   => 'multiselect',
					'values' => [
						'party'   => 'Party',
						'fiesta'  => 'Fiesta',
						'cookout' => 'Cookout',
					],
				],
				'wpop_example_plugin_toggle_switch' => [
					'label' => 'Toggle Switch Field',
					'part'  => 'toggle_switch',
				],
				'wpop_example_plugin_radios'        => [
					'label'  => 'Radio Field',
					'part'   => 'radio_buttons',
					'values' => [
						'party'   => 'Party',
						'fiesta'  => 'Fiesta',
						'cookout' => 'Cookout',
					],
				],
			],
		];
	}

	/**
	 * @return array
	 */
	function media_section() {
		return [
			'label'    => 'Media',
			'dashicon' => 'dashicons-admin-media',
			'parts'    => [
				'wpop_example_plugin_media' => [
					'label' => 'Media Field',
					'part'  => 'media',
				],
			],
		];
	}

	/**
	 * @return array
	 */
	function editors_section() {
		return [
			'label'    => 'Editors',
			'dashicon' => 'dashicons-edit',
			'parts'    => [
				'wpop_example_plugin_editor' => [
					'label' => 'Editor Field',
					'part'  => 'editor',
				],
				'wpop_example_plugin_nohtml' => [
					'label'        => 'Editor - No HTML Toggle',
					'part'         => 'Editor',
					'no_quicktags' => 'true',
				],
				'wpop_example_plugin_simple' => [
					'label'        => 'Editor Simple',
					'part'         => 'editor',
					'teeny'        => 'true',
					'no_media'     => 'true',
					'no_quicktags' => 'true',
				],
			],
		];
	}

	/**
	 * @return array
	 */
	function wordpress_section() {
		return [
			'label'    => 'WordPress',
			'dashicon' => 'dashicons-wordpress-alt',
			'parts'    => [
				'wpop_example_plugin_color' => [
					'label' => 'Color Field',
					'part'  => 'color',
				],
			],
		];
	}

	/**
	 * @return array
	 */
	function encrypted_section() {
		return [
			'label'    => 'Encrypted',
			'dashicon' => 'dashicons-lock',
			'parts'    => [
				'wpop_example_plugin_password' => [
					'label' => 'Password Field',
					'part'  => 'password',
				],
			],
		];
	}

	/**
	 * @return array
	 */
	function include_section() {
		return [
			'label'     => 'Includes',
			'dashicons' => 'dashicons-file',
			'parts'     => [
				'wpop_example_plugin_markdown_file' => [
					'label'    => 'Markdown Field',
					'part'     => 'markdown',
					'filename' => $this->installed_dir . 'assets/example_include_markdown.md',
				],
			],
		];
	}
}
