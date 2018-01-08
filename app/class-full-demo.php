<?php

namespace WPOP\Example;

use WPOP\V3\Panel;

class Full_Demo {
	/**
	 * Full_Demo constructor.
	 */
	function __construct() {
		$sections = [
			[
				'key'   => 'general',
				'label' => 'General',
				'dashicon' => 'dashicons-lock',
				'parts' => [
					[
						'key' => 'wpop_example_text',
						'label' => 'Example Text',
						'type'  => 'text',
						'desc'  => 'A simple text input',
						'placehold' => 'Enter your text...'
					],
					[
						'key' => 'wpop_example_number',
						'label' => 'Example Number',
						'type'  => 'number',
						'desc'  => 'A simple number input',
						'placehold' => 'Enter your number...'
					],
					[
						'key' => 'wpop_example_color',
						'label' => 'Example Color',
						'type'  => 'color',
						'desc'  => 'A powerful color input'
					],
					[
						'key' => 'wpop_example_pwd',
						'label' => 'Example Password',
						'type'  => 'password',
						'desc'  => 'an secure password input',
						'placehold' => 'Enter a secret...'
					],
					[
						'key' => 'wpop_example_textarea',
						'label' => 'Example Textarea',
						'type'  => 'textarea',
						'desc'  => 'a simple textarea',
						'placehold' => 'Pewpewpew textarea'
					],
					[
						'key' => 'wpop_example_editor',
						'label' => 'Example Editor',
						'type'  => 'editor',
						'desc' => 'a tinymce-powered textarea',
						'placehold' => 'awwyiss dat editor yo'
					],
					[
						'key' => 'wpop_example_select',
						'label' => 'Example Select',
						'type'  => 'select',
						'choices' => [
							'choiceA' => 'Choice A',
							'choiceB' => 'Choice B',
							'choiceC' => 'Choice C',
						],
						'desc' => 'a powerful select dropdown'
					],
					[
						'key' => 'wpop_example_multiselect',
						'label' => 'Example Multiselect',
						'type'  => 'multiselect',
						'choices' => [
							'choiceA' => 'Choice A',
							'choiceB' => 'Choice B',
							'choiceC' => 'Choice C',
						],
						'desc' => 'a powerful multiselect'
					],
					[
						'key' => 'wpop_example_switch',
						'label' => 'Example Switch',
						'type'  => 'switch',
						'desc' => 'a simple switch',
					],
					[
						'key' => 'wpop_example_radios',
						'label' => 'Example Radio Buttons',
						'type'  => 'radios',
						'desc' => 'a radio list',
						'choices' => [
							'choiceA' => 'Choice A',
							'choiceB' => 'Choice B',
							'choiceC' => 'Choice C',
						],
					],
					[
						'key' => 'wpop_example_toggles',
						'label' => 'Example Toggle Switches',
						'type'  => 'toggles',
						'desc' => 'a tooggle list',
						'choices' => [
							'choiceA' => 'Choice A',
							'choiceB' => 'Choice B',
							'choiceC' => 'Choice C',
						],
					],
					[
						'key' => 'wpop_example_media',
						'label' => 'Example Media',
						'type'  => 'media',
						'desc' => 'a media picker via modal'
					],
					[
						'key' => 'wpop_example_wptemplate',
						'label' => 'Example wp.template',
						'type'  => 'wptemplate'
					],
				],
			],
			[
				'key'   => 'advanced',
				'label' => 'Advanced',
				'dashicon' => 'dashicons-no',
				'parts' => [],
			],
		];
		new Panel( [
			'id'         => 'full-demo',
			'type'       => 'top', // 'submenu'
			'data_type'  => 'site', // 'network', 'user-site', 'user-network', 'term', 'post'
			'menu_label' => 'Options Demo',
			'page_label' => 'Full Options Panel Demo',
			'part_sort'  => 'current', // 'order', 'label-alpha'
			'sections'   => $sections, // 'key' => [] <!--parts -->
			'capability' => 'read',
			'object_id'  => '',
			'dashicon'   => 'dashicons-admin-settings',
			'menu_pos'   => 100,
		] );
	}
}