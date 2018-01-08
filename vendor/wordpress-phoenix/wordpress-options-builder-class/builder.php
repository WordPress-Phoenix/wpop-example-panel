<?php

namespace WPOP\V3;

class Panel {

	public $id;
	public $panel;
	public $sections;
	public $data;

	function __construct( $args ) {
		$this->id    = preg_replace( '/_/', '-', $args['id'] );
		$this->panel = get_called_class();
		$parser = new PanelConfig( $this->id, $args );
		new PanelInterface( $this->id );
		$this->data = $parser->final_data();
		add_action( 'network' === $this->data['data_type'] ? 'network_admin_menu' : 'admin_menu', array( $this, 'register_wordpress_page' ) );
		if ( is_admin() || is_network_admin() ) {
			$this->page_selective_initialization();
		}
	}

	function page_selective_initialization() {
		if ( isset( $_GET['page'] ) && $this->id === $_GET['page'] ) {
			add_action( 'admin_init', array( $this, 'initialize_save_process' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_panel_assets' ) );
			add_action( 'admin_footer-toplevel_page_full-demo', array( $this, 'page_initialization_scripts'), 999 );
		}
	}

	function register_wordpress_page() {
		$d = $this->data;
		if ( 'top' === $this->data['type'] ) {
			add_menu_page( $d['page_label'], $d['menu_label'], $d['capability'], $d['id'], array( $this, 'page_callback' ), $d['dashicon'], $d['menu_pos'] );
		} else {
			$hook = ! empty( $this->data['parent'] ) ? $this->data['parent'] : 'options.php';
			add_submenu_page( $hook, $d['page_label'], $d['menu_label'], $d['capability'], $d['id'], array( $this, 'page_callback' ) );
		}
	}

	function enqueue_panel_assets() {
		$unpkg = 'https://unpkg.com/purecss@1.0.0/build/';
		wp_register_style( 'wpop-pure-base', $unpkg . 'base-min.css' );
		wp_register_style( 'wpop-pure-grids', $unpkg . 'grids-min.css', array( 'wpop-pure-base' ) );
		wp_register_style( 'wpop-pure-grids-r', $unpkg . 'grids-responsive-min.css', array( 'wpop-pure-grids' ) );
		wp_register_style( 'wpop-pure-menus', $unpkg . 'menus-min.css', array( 'wpop-pure-grids-r' ) );
		wp_register_style( 'wpop-pure-forms', $unpkg . 'forms-min.css', array( 'wpop-pure-menus' ) );
		wp_enqueue_style( 'wpop-pure-forms' ); // cue enqueue cascade

		// Enqueue media (needed for media modal)
		wp_enqueue_editor();
		wp_enqueue_media();

		wp_register_script( 'spectrum', 'https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.js' );
		wp_register_style( 'spectrum', 'https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.css' );

		wp_enqueue_script( 'spectrum' );
		wp_enqueue_style( 'spectrum' );

		$selectize_cdn = 'https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.4/';
		wp_register_script( 'wpop-selectize', $selectize_cdn . 'js/standalone/selectize.min.js', array( 'jquery-ui-sortable' ) );
		wp_enqueue_script( 'wpop-selectize' );
		wp_register_style( 'wpop-selectize', $selectize_cdn . 'css/selectize.default.min.css' );
		wp_enqueue_style( 'wpop-selectize' );
		wp_register_script( 'clipboard', 'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.7.1/clipboard.min.js' );
		wp_enqueue_script( 'clipboard' );
	}

	function initialize_save_process() {
		if (
			isset( $_POST['submit'] )
			&& is_string( $_POST['submit'] )
			&& "Save All" === $_POST['submit']
			&& wp_verify_nonce( $_POST['nonce'], $this->id )
		) {
			foreach ( $this->data['sections'] as $section ) {
				foreach ( $section['parts'] as $part ) {
					new SaveSingleField(
						$this->data['data_type'],
						$part['key'],
						$this->santitize_input( $part['type'], $_POST[ $part['key'] ] ),
						$this->data['object_id'],
						$part['autoload']
					);
				}
			}
		}
	}

	function santitize_input( $type, $input ) {
		switch ( $type ) {
			case 'text':
				return sanitize_text_field( $input );
				break;
			case 'editor':
			case 'textarea':
				return wp_kses_post( $input );
				break;
			default:
				return $input;
		}
	}

	function determine_db_storage_table() {

	}

	public function page_callback() {
		wp_enqueue_script( [ 'wp-util', 'shortcode' ] );
		$this->data['nonce'] = wp_nonce_field( $this->id, 'nonce', true, false );
		if ( 'site' || 'network' !== $this->data['data_type'] ) { // maybe capture the WordPress Core Object on the fly
			$maybe_object_data = $this->get_wordpress_object_data();
			if ( ! empty( $maybe_object_data ) && is_array( $maybe_object_data ) ) {
				$this->data['wpObject'] = $maybe_object_data;
			}
		}
		wp_localize_script( 'wp-util', 'wpopPanelData', $this->data );
		ob_start();
		if ( isset( $_GET['wpop_debug'] ) ) {
			echo "<h1>WPOP Debug Mode</h1>";
			echo "<br >";
			echo "<pre>";
			var_dump( $this->data );
			echo "</pre>";
			exit;
		} else {
			ob_start(); ?>
			<style type="text/css">
				.onoffswitch-wrap {
					float: right;
					position: relative;
					top: -1rem;
				}
				.onoffswitch {
					position: relative; width: 90px;
					-webkit-user-select:none; -moz-user-select:none; -ms-user-select: none;
				}
				.onoffswitch input[type="checkbox"] {
					display: none;
				}
				.onoffswitch-label {
					display: block; overflow: hidden; cursor: pointer;
					border: 2px solid #999999; border-radius: 4px;
				}
				.onoffswitch-inner {
					display: block; width: 200%; margin-left: -100%;
					transition: margin 0.3s ease-in 0s;
				}
				.onoffswitch-inner:before, .onoffswitch-inner:after {
					display: block; float: left; width: 50%; height: 30px; padding: 0; line-height: 30px;
					font-size: 14px; color: white; font-family: Trebuchet, Arial, sans-serif; font-weight: bold;
					box-sizing: border-box;
				}
				.onoffswitch-inner:before {
					content: "ON";
					padding-left: 10px;
					background-color: #34A7C1; color: #FFFFFF;
				}
				.onoffswitch-inner:after {
					content: "OFF";
					padding-right: 10px;
					background-color: #EEEEEE; color: #999999;
					text-align: right;
				}
				.onoffswitch-switch {
					display: block; width: 18px; margin: 6px;
					background: #FFFFFF;
					position: absolute; top: 0; bottom: 0;
					right: 56px;
					border: 2px solid #999999; border-radius: 4px;
					transition: all 0.3s ease-in 0s;
				}
				.onoffswitch-checkbox:checked + .onoffswitch-label .onoffswitch-inner {
					margin-left: 0;
				}
				.onoffswitch-checkbox:checked + .onoffswitch-label .onoffswitch-switch {
					right: 0px;
				}
				.toggleswitch-wrap {
					display: inline-block !important;
					margin-left: 0.5rem;
				}
				.toggleswitch {
					position: relative; width: 40px;
					-webkit-user-select:none; -moz-user-select:none; -ms-user-select: none;
				}
				.toggleswitch input[type="checkbox"] {
					display: none;
				}
				.toggleswitch-label {
					display: block; overflow: hidden; cursor: pointer;
					border: 2px solid #999999; border-radius: 2px;
				}
				.toggleswitch-inner {
					display: block; width: 200%; margin-left: -100%;
					transition: margin 0.3s ease-in 0s;
				}
				.toggleswitch-inner:before, .toggleswitch-inner:after {
					display: block; float: left; width: 50%; height: 8px; padding: 0; line-height: 8px;
					font-size: 14px; color: white; font-family: Trebuchet, Arial, sans-serif; font-weight: bold;
					box-sizing: border-box;
				}
				.toggleswitch-inner:before {
					content: "";
					padding-left: 10px;
					background-color: #34A7C1; color: #FFFFFF;
				}
				.toggleswitch-inner:after {
					content: "";
					padding-right: 10px;
					background-color: #EEEEEE; color: #999999;
					text-align: right;
				}
				.toggleswitch-switch {
					display: block; width: 14px; margin: -3px;
					background: #FFFFFF;
					position: absolute; top: 0; bottom: 0;
					right: 28px;
					border: 2px solid #999999; border-radius: 2px;
					transition: all 0.3s ease-in 0s;
				}
				.toggleswitch-checkbox:checked + .toggleswitch-label .toggleswitch-inner {
					margin-left: 0;
				}
				.toggleswitch-checkbox:checked + .toggleswitch-label .toggleswitch-switch {
					right: 0px;
				}


				.wpop-loader-wrapper {
					position: fixed;
					top: 45%;
					right: 45%;
					z-index: 99999;
					display: none
				}

				.ball-clip-rotate-multiple {
					position: relative
				}

				.ball-clip-rotate-multiple > div {
					position: absolute;
					left: -20px;
					top: -20px;
					border: 3px solid #cd1713;
					border-bottom-color: transparent;
					border-top-color: transparent;
					border-radius: 100%;
					height: 35px;
					width: 35px;
					-webkit-animation: rotate .99s 0 ease-in-out infinite;
					animation: rotate 1s 0 ease-in-out infinite
				}

				.cb,
				.save-all,
				span.spacer {
					position: relative
				}

				#wpopMain {
					background: #fff
				}

				#wpopOptNavUl {
					margin-top: 0
				}

				.wpop-options-menu {
					margin-bottom: 8em
				}

				#wpopContent {
					background: #F1F1F1;
					width: 100%!important;
					border-top: 1px solid #D8D8D8
				}

				.pure-g [class*=pure-u] {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif
				}

				.pure-form select {
					min-width: 320px
				}

				.selectize-control {
					max-width: 98.5%
				}

				.pure-menu-disabled,
				.pure-menu-heading,
				.pure-menu-link {
					padding: 1.3em 2em
				}

				.pure-menu-active > .pure-menu-link,
				.pure-menu-link:focus,
				.pure-menu-link:hover {
					background: inherit
				}

				.pure-menu-link:focus {
					box-shadow: none !important;
				}

				.pure-menu-link.dashicons-before:before {
					position: relative;
					top: -0.12rem;
					left: -0.66rem;
					font-size: 1.25rem;
					opacity: 0.8;
				}

				#wpopOptions header {
					overflow: hidden;
					max-height: 115px
				}

				#wpopNav p.submit input {
					width: 100%
				}

				#wpop {
					border: 1px solid #D8D8D8;
					background: #fff;
					margin-top: -0.5rem;
				}

				.opn a.pure-menu-link {
					color: #fff!important
				}

				.opn a.pure-menu-link:focus {
					box-shadow: none;
					-webkit-box-shadow: none
				}

				#wpopContent .section {
					display: none;
					width: 100%
				}

				#wpopContent .section.active {
					display: inline-block
				}

				span.page-icon {
					margin: 0 1.5vw 0 0
				}

				span.menu-icon {
					position: relative;
					left: -.5rem
				}

				span.page-icon:before {
					font-size: 2rem;
					position: relative;
					top: -1px;
					right: 5px;
					opacity: 0.66;
				}

				.clear {
					clear: both
				}

				.section {
					padding: 0 0 5px
				}

				.section h3 {
					margin: 0 0 10px;
					padding: 2vw 1.5vw
				}

				.section h4.label {
					margin: 0;
					display: table-cell;
					border: 1px solid #e9e9e9;
					background: #f1f1f1;
					padding: .33vw .66vw .5vw;
					font-weight: 500;
					font-size: 16px
				}

				.section li.wpop-option {
					margin: 1rem 1rem 1.25rem
				}

				.twothirdfat {
					width: 66.6%
				}

				span.spacer {
					display: block;
					width: 100%;
					border: 0;
					height: 0;
					border-top: 1px solid rgba(33, 33, 33, .25);
					border-bottom: 1px solid rgba(255, 255, 255, .25)
				}

				li.even.option {
					background-color: #ccc
				}

				input[disabled=disabled] {
					background-color: #CCC
				}

				.cb {
					float: right;
					right: 20px
				}

				.card-wrap {
					width: 100%
				}

				.fullwidth {
					width: 100%!important;
					max-width: 100%!important
				}

				.wpop-head {
					background: #f1f1f1
				}

				.wpop-head > .inner {
					padding: 1vw 1.5vw 0
				}

				.save-all {
					float: right;
					top: -2.15rem;
				}

				@media( max-width: 640px ) {
					.save-all {
						width: 100%;
						margin-top: 42px !important;
						margin-bottom: 0.5rem;
						display: block;
						float: none;
					}
				}

				.desc {
					margin: .5rem 0 0 .25rem;
					font-weight: 300;
					font-size: 12px;
					line-height: 16px;
					color: #888;
				}

				.desc:after {
					display: block;
					position: relative;
					width: 98%;
					border-top: 1px solid rgba(0, 0, 0, .1);
					border-bottom: 1px solid rgba(255, 255, 255, .3)
				}

				.wpop-option input[type="text"],
				.wpop-option input[type="password"],
				.wpop-option input[type="number"],
				.wpop-option input[type="email"] {
					width: 90%;
				}

				@media( max-width: 767px ) {
					.wpop-option input[type="text"],
					.wpop-option input[type="password"],
					.wpop-option input[type="number"],
					.wpop-option input[type="email"] {
						width: 95%
					}
				}

				.wpop-option input[type="password"] {
					letter-spacing: 0.5rem;
					font-size:2rem;
					height:26px !important;
					color: #555;
				}

				.wpop-option input[type="password"]::placeholder {
					letter-spacing: normal;
					font-size:0.9rem;
					position:relative;
					top:-7px;
				}

				.wpop-option textarea {
					width: 66%;
					min-height: 60px;
				}

				.wpop-option.media .dashicons-admin-media:before {
					text-align: center;
					width: 100%;
					margin-top: 1.66rem;
					color: #ccc;
					font-size: 3rem;
				}

				@media( max-width: 767px ) {
					.wpop-option textarea {
						width: 95%
					}
				}

				input[data-assigned] {
					width: 100%!important
				}

				.add-button {
					margin: 3em auto;
					display: block;
					width: 100%;
					text-align: center
				}

				.img-preview {
					max-width: 320px;
					display: block;
					margin: 0 0 1rem
				}

				.img-remove {
					border: 2px solid #cd1713!important;
					background: #f1f1f1!important;
					color: #cd1713!important;
					box-shadow: none;
					-webkit-box-shadow: none;
					margin-left: 1rem!important
				}

				.pwd-clear {
					margin-left: .5rem!important;
					position: relative;
					top: 1px
				}

				.pure-form footer {
					background: #f1f1f1;
					border-top: 1px solid #D8D8D8
				}

				.pure-form footer div div > * {
					padding: 1rem .33rem
				}

				.wpop-option.color_picker input {
					width: 50%
				}

				.wpop-option.color_picker .iris-picker {
					float: right
				}

				.cb-wrap {
					position: relative;
					display: block;
					right: 1.33vw;
					max-width: 110px;
					margin-left: auto;
					top: -1.66rem
				}
			</style>
			<?php echo ob_get_clean();
			echo '<div id="panel"></div>';
		}
	}

	public function get_wordpress_object_data() {
		if ( isset( $_GET['post'] ) && is_numeric( $_GET['post'] ) ) {
			$post = get_post( $_GET['post'] );
			if ( is_object( $post ) ) {
				$post = get_object_vars( $post );
				if ( ! empty( get_the_post_thumbnail_url( $post['ID'] ) ) ) {
					$post['thumbnail'] = get_the_post_thumbnail_url( $post['ID'] );
				}
			}
			$post['wpop_type'] = 'post';
			return is_array( $post ) ? $post : null;
		} elseif ( isset( $_GET['term'] ) && is_numeric( $_GET['term'] ) ) {
			$term = get_term( $_GET['term'] );
			$post['wpop_type'] = 'term';
			return is_object( $term ) ? get_object_vars( $term ) : null;
		} elseif ( isset( $_GET['user'] ) && is_numeric( $_GET['user'] ) ) {
			$user = get_userdata( $_GET['user'] );
			if ( is_object( $user ) ) {
				$user = get_object_vars( $user->data );
				$user['avatar'] = get_avatar_url( $user['ID'] );
				unset( $user['user_pass'] ); // no password exposed please
			}
			$post['wpop_type'] = is_multisite() && is_network_admin() ? 'user-network' : 'user';
			return is_array( $user ) ? $user : null;
		}

		return null;
	}

	public function page_initialization_scripts() {
		ob_start(); ?>
		<script type="text/javascript">
			/**
			 * Module: WP-JS-Hooks
			 * Props: Carl Danley & 10up
			 */
			!function(t,n){"use strict";t.wp=t.wp||{},t.wp.hooks=t.wp.hooks||new function(){function t(t,n,r,i){var e,o,c;if(f[t][n])if(r)if(e=f[t][n],i)for(c=e.length;c--;)(o=e[c]).callback===r&&o.context===i&&e.splice(c,1);else for(c=e.length;c--;)e[c].callback===r&&e.splice(c,1);else f[t][n]=[]}function n(t,n,i,e,o){var c={callback:i,priority:e,context:o},l=f[t][n];l?(l.push(c),l=r(l)):l=[c],f[t][n]=l}function r(t){for(var n,r,i,e=1,o=t.length;e<o;e++){for(n=t[e],r=e;(i=t[r-1])&&i.priority>n.priority;)t[r]=t[r-1],--r;t[r]=n}return t}function i(t,n,r){var i,e,o=f[t][n];if(!o)return"filters"===t&&r[0];if(e=o.length,"filters"===t)for(i=0;i<e;i++)r[0]=o[i].callback.apply(o[i].context,r);else for(i=0;i<e;i++)o[i].callback.apply(o[i].context,r);return"filters"!==t||r[0]}var e=Array.prototype.slice,o={removeFilter:function(n,r){return"string"==typeof n&&t("filters",n,r),o},applyFilters:function(){var t=e.call(arguments),n=t.shift();return"string"==typeof n?i("filters",n,t):o},addFilter:function(t,r,i,e){return"string"==typeof t&&"function"==typeof r&&(i=parseInt(i||10,10),n("filters",t,r,i,e)),o},removeAction:function(n,r){return"string"==typeof n&&t("actions",n,r),o},doAction:function(){var t=e.call(arguments),n=t.shift();return"string"==typeof n&&i("actions",n,t),o},addAction:function(t,r,i,e){return"string"==typeof t&&"function"==typeof r&&(i=parseInt(i||10,10),n("actions",t,r,i,e)),o}},f={actions:{},filters:{}};return o}}(window);
			jQuery( document ).ready( function( $ ) {
				var appTemplate = wp.template('wpop-panel'), // @see <script id="tmpl-wpop-panel">
					appMountTarget = $('#panel'), // div to load underscores app
					appData = window.wpopPanelData, // wp_localize_script() data passthrough (contains nonce)
					wpModal; // var used for media modal instance

				// initialize app
				appMountTarget.html( appTemplate( appData ) );

				// register all actions
				registerAllActions();
				wp.hooks.doAction( 'wpopPreInit' );

				$( '#wpopNav li a' ).click( function( evt ) {
					wp.hooks.doAction( 'wpopSectionNav', this, evt ); // reg. here to allow "click" from hash to select a section
				} );

				wp.hooks.doAction( 'wpopInit' ); // main init

				$( 'input[type="submit"]' ).click( function( evt ) {
					wp.hooks.doAction( 'wpopSubmit', this, evt );
				} );

				$( '.pwd-clear' ).click( function( evt ) {
					wp.hooks.doAction( 'wpopPwdClear', this, evt );
				} );

				$( '[data-wp-media]' ).on( 'click', function( event ) {
					wp.hooks.doAction( 'wpopImgUpload', this, event );
				} );

				$( '.img-remove' ).on( 'click', function( event ) {
					wp.hooks.doAction( 'wpopImgRemove', this, event );
				} );

				$( '.add-button' ).on( 'click', function( event ) {
					wp.hooks.doAction( 'wpopRepeaterAdd', this, event );
				} );

				function registerAllActions() {
					wp.hooks.addAction( 'wpopPreInit', nixHashJumpJank );
					wp.hooks.addAction( 'wpopInit', handleInitHashSelection );
					wp.hooks.addAction( 'wpopInit', initColorSwatches() );
					wp.hooks.addAction( 'wpopInit', initSelectizeInputs );

					wp.hooks.addAction( 'wpopSectionNav', handleSectionNavigation );
					wp.hooks.addAction( 'wpopSectionNav', handleActiveSection );

					wp.hooks.addAction( 'wpopPwdClear', doPwdFieldClear );
					wp.hooks.addAction( 'wpopImgUpload', doImgUpload );
					// wp.hooks.addAction( 'wpopImgRemove', doImgRemove );
					//
					wp.hooks.addAction( 'wpopSubmit', wpopShowSpinner );
				}

				/* CORE */
				function handleSectionNavigation( elem, event ) { // handle menu transition and click prevent
					event.preventDefault();
					var page_active = $( ( $( elem ).attr( 'href' ) ) ).addClass( 'active' );
					var menu_active = $( ($( elem ).attr( 'href' ) + '-nav') ).addClass( 'active wp-ui-primary opn' );

					// add tab's location to URL but stay at the top of the page
					window.location.hash = $( elem ).attr( 'href' );
					window.scrollTo( 0, 0 );
					$( page_active ).siblings().removeClass( 'active' );
					$( menu_active ).siblings().removeClass( 'active wp-ui-primary opn' );

					return false;
				}

				function handleActiveSection( elem, event ) { // handle section insertion and fields setup
					var sectionTemplate	  = wp.template( 'wpop-section' ),
						sectionMountPoint = $('#panel-main'),
						activeSection 	  = $( elem ).attr( 'data-key' ), // clicked or spoof-clicked menu item
						activeSectionData = _.findWhere( appData.sections, { key: activeSection } );

					sectionMountPoint.html( null ); // clear fields area of any markup
					sectionMountPoint.html( sectionTemplate( activeSectionData ) ); // send section template data
				}

				function wpopShowSpinner() {
					$( '.wpop-loader-wrapper' ).css( 'display', 'inherit' );
				}

				function handleInitHashSelection() {
					var hash; // if no sections are selected, use a spoof-click to select the first section
					if ( hash = window.location.hash ) {
						$( hash + '-nav a' ).trigger( 'click' );
					} else {
						$( '#wpopNav li:first a' ).trigger( 'click' );
					}
				}

				function nixHashJumpJank() {
					$( 'html, body' ).animate( { scrollTop: 0 } ); // jank handler
				}

				/* FIELDS JS */
				function initColorSwatches() {
					//
				}

				function initSelectizeInputs() {
					// if ( 'undefined' !== typeof selectize ) {
						console.log( 'iniitljsdlfjklsdf');
						var select = $( '[data-field="select"]' ).selectize( {
							allowEmptyOption: false,
							placeholder: $( this ).attr( 'data-placeholder' )
						} );
						$( '[data-field="multiselect"]' ).selectize( {
							placeholder: 'Click to select items...',
							plugins: ["restore_on_backspace", "remove_button", "drag_drop", "optgroup_columns"]
						} );
					// }
				}

				function doPwdFieldClear( elem, event ) {
					event.preventDefault();
					$( elem ).prev().val( null );
				}

				function doImgUpload( elem, event ) {
					event.preventDefault();
					// var config = $( elem ).data();
					// Initialize the modal the first time.
					if ( !wpModal ) {
						wpModal = wp.media.frames.wpModal || wp.media( {
							title: 'Modal Title',
							button: { text: 'Slamma bing pao' },
							library: { type: 'image' },
							multiple: false
						} );

						// Picking an image
						wpModal.on( 'select', function() {
							// Get the image URL
							var image = wpModal.state().get( 'selection' ).first().toJSON();
							if ( 'object' === typeof image ) {
								console.log( image );
								handleImageField( $( elem ).data( 'field' ) , image );
							}
						} );
					}

					// Open the modal
					wpModal.open();
				}

				function handleImageField( key, data ) {
					var metaTemplate = wp.template( 'wpop-media-meta' );
					$('#' + key + '-edit' ).attr( 'href', data.editLink );
					$('#' + key + '-slot' ).css( 'background-image', 'url( ' + data.sizes.thumbnail.url + ')' );
					$('#' + key + '-meta' ).html( metaTemplate( data ) );
				}

				function doImgRemove( elem, event ) {
					event.preventDefault();
					var remove = confirm( 'Remove ' + $( elem ).attr( 'data-media-label' ) + '?' );
					if ( remove ) {
						var item = $( elem ).closest( '.wpop-option' );
						var blank = item.find( '.blank-img' ).html();
						item.find( '[type="hidden"]' ).val( null );
						item.find( 'img' ).attr( 'src', blank );
						item.find( '.button-hero' ).val( 'Set Image' );
						$( elem ).hide();
					}
				}
			} );
		</script>
		<?php echo ob_get_clean();
	}
}

class PanelConfig {
	public static $panel_defualts = [
		'id'         => '', // string-based slug
		'type'       => 'top', // 'submenu'
		'data_type'  => 'site', // 'network', 'user-site', 'user-network', 'term', 'post'
		'menu_label' => '',
		'page_label' => '',
		'part_sort'  => 'current', // 'order', 'label-alpha'
		'sections'   => [], // 'key' => [] <!--parts -->
		'capability' => 'manage_options',
		'object_id'  => '',
		'dashicon'   => null,
		'menu_pos'   => 100,
	];

	public static $section_defaults = [];

	public static $part_defaults = [
		'key'          => '',
		'saved'        => '',
		'label'        => '',
		'type'         => 'text',
		'order'        => null,
		'desc'         => '',
		'default'      => '',
		'choices'      => '',
		'classes'      => '',
		'wrapper_atts' => '',
		'field_atts'   => '',
		'field_before' => '',
		'field_after'  => '',
		'legacy_value' => '',
	];

	public $id;
	public $raw_data;
	public $data;

	function __construct( $id, $data ) {
		$this->id = $id;
		$this->raw_data = $data;

		$this->process_panel_defaults();
		$this->process_section_defaults();

		// part processing happens in $this->process_section_defaults()
		return $this->data;
	}

	function final_data() {
		return $this->data;
	}

	function process_panel_defaults() {
		$this->data = wp_parse_args( $this->raw_data, self::$panel_defualts );
	}

	function process_section_defaults() {
		$sections = [];
		foreach( $this->data['sections'] as $section ) {
			$section['parts'] = $this->process_part_defaults( $this->data['data_type'], $section['parts'] );
			$sections[] = wp_parse_args( $section, self::$section_defaults );
		}
		$this->data['sections'] = $sections;
	}

	function process_part_defaults( $type, $parts, $obj_id = null ) {
		$built = [];
		foreach( $parts as $part ) {
			$wordpressAPIs = new GetSingleField( $type, $part['key'], $obj_id );
			$part['saved'] = isset( $_POST[ $part['key'] ] ) ? $_POST[ $part['key'] ] : $wordpressAPIs->get_data();
			$part['fieldType'] = $part['type']; // dupe to allow later separation of field type from input type
			$built[] = wp_parse_args( $part, self::$part_defaults );
		}

		return $built;
	}
}

class GetSingleField {
	protected $type;
	protected $key;
	protected $obj_id;
	protected $single;
	function __construct( $type, $key, $obj_id, $single = true ) {
		$this->type = $type;
		$this->key = $key;
		$this->obj_id = $obj_id;
		$this->single = $single;

		return $this->get_data();
	}

	function get_data() {
		switch ( $this->type ) {
			case 'site':
				return get_option( $this->key );
				break;
			case 'network':
				return get_site_option( $this->key );
				break;
			case 'user-site':
			case 'user':
				return is_multisite() ? get_user_option( $this->key, $this->obj_id ) : get_user_meta( $this->obj_id, $this->key, $this->single );
				break; // traditional user meta
			case 'user-network':
				return get_user_meta( $this->obj_id, $this->key, $this->single );
				break;
			case 'term':
			case 'category':
			case 'tag':
				return get_metadata( 'term', $this->obj_id, $this->key, $this->single );
				break;
			case 'post':
				return get_metadata( 'post', $this->obj_id, $this->key, $this->single );
				break;
			default:
//				return new \WP_Error(
//					'400',
//					'WPOP failed to select proper WordPress Data API -- check your config.',
//					compact( $type, $key, $value, $obj_id, $autoload )
//				);
				return '';
				break;
		}
	}
}

class SaveSingleField {
	function __construct( $type, $key, $value, $obj_id = null, $autoload = true ) {
		switch ( $type ) {
			case 'site':
				return self::handle_site_option_save( $key, $value, $autoload );
				break;
			case 'network':
				return self::handle_network_option_save( $key, $value );
				break;
			case 'user-site':
			case 'user':
				return self::handle_user_site_meta_save( $obj_id, $key, $value );
				break; // traditional user meta
			case 'user-network':
				return self::handle_user_network_meta_save( $obj_id, $key, $value );
				break;
			case 'term':
			case 'category':
			case 'tag':
				return self::handle_term_meta_save( $obj_id, $key, $value );
				break;
			case 'post':
				self::handle_post_meta_save( $obj_id, $key, $value );
				break;
			default:
				return new \WP_Error(
					'400',
					'WPOP failed to select proper WordPress Data API -- check your config.',
					compact( $type, $key, $value, $obj_id, $autoload )
				);
				break;
		}
	}

	private static function handle_site_option_save( $key, $value, $autoload ) {
		return empty( $value ) ? delete_option( $key ) : update_option( $key, $value, $autoload );
	}

	private static function handle_network_option_save( $key, $value ) {
		return empty( $value ) ? delete_site_option( $key ) : update_site_option( $key, $value );
	}

	private static function handle_user_site_meta_save( $user_id, $key, $value ) {
		return empty( $value ) ? delete_user_meta( $user_id, $key ) : update_user_meta( $user_id, $key, $value );
	}

	private static function handle_user_network_meta_save( $id, $key, $value ) {
		return empty( $value ) ? delete_user_option( $id, $key, true ) : update_user_option( $id, $key, true );
	}

	private static function handle_term_meta_save( $id, $key, $value ) {
		return empty( $value ) ? delete_metadata( 'term', $id, $key ) : update_metadata( 'term', $id, $key, $value );
	}

	private static function handle_post_meta_save( $id, $key, $value ) {
		return empty( $value ) ? delete_post_meta( $id, $key ) : update_post_meta( $id, $key, $value );
	}
}

class PanelInterface {
	function __construct( $id ) {
		add_action( 'admin_footer-toplevel_page_' . $id, array( $this, 'initialize_underscore_templates' ), 5 );
		add_action( 'load-toplevel_page_' . $id, array( $this, 'enqueue_assets' ) );
	}

	function enqueue_assets() {

	}

	function initialize_underscore_templates() {
		ob_start(); ?>
		<!-- panel templates -->
		<script type="text/html" id="tmpl-wpop-footer">
			<footer class="pure-u-1">
				<div class="pure-g">
					<div class="pure-u-1 pure-u-md-1-3">
						<div><span>Stored in: <code>wp_some_storage_table</code></span></div>
					</div>
					<div class="pure-u-1 pure-u-md-1-3">
						<div></div>
					</div>
					<div class="pure-u-1 pure-u-md-1-3">
						<div><a href="#" id="wipe-all-legacy" name="wipe-all-legacy" class="button">Wipe Legacy Values</a></div>
					</div>
				</div>
			</footer>
		</script>
		<script type="text/html" id="tmpl-wpop-sidebar">
			<div id="wpopContent" class="pure-g">
				<div id="wpopNav" class="pure-u-1 pure-u-md-6-24">
					<div class="pure-menu wpop-options-menu">
						<ul class="pure-menu-list">
						<# _.each( data.sections, function( section ) {
							var theClasses;
							if ( '' !== section.dashicon ) {
								theClasses = 'pure-menu-link dashicons-before ' + section.dashicon;
							} else {
								theClasses = 'pure-menu-link';
							}
							var nav = wp.html.string( { tag:'a', attrs: { href: '#' + section.key, class:theClasses, 'data-key': section.key }, content: section.label } );
							var final = { tag:'li', content: nav, attrs: { id: section.key + '-nav', class:'pure-menu-item' } };
							print( wp.html.string( final ) );
						} ); #>
						</ul>
					</div>
				</div>
		</script>
		<script type="text/html" id="tmpl-wpop-section">
			<ul>
				<#
				var field = wp.template( 'wpop-field' );
				_.each( data.parts, function( part ) {
					if ( 'wptemplate' === part.type ) {
<!--						field = wp.template( part.wptemplate );-->
					}
					print( field( part ) );
				} ); #>
			</ul>
		</script>
		<script type="text/html" id="tmpl-wpop-field">
			<li class="wpop-option {{{ data.type }}} {{{ data.key }}}">
				<h4 class="label">{{{ data.label }}}</h4>
				<# var field = function() {};
				switch( data.type ) {
					case 'text':
					case 'number':
						field = wp.template( 'wpop-input' );
						break;
					case 'color':
						field = wp.template( 'wpop-color' );
						break;
					case 'switch':
						field = wp.template( 'wpop-switch' );
						break;
					case 'select':
					case 'multiselect':
						field = wp.template( 'wpop-select' );
						break;
					case 'password':
						field = wp.template('wpop-encrypted');
						break;
					case 'radios':
						field = wp.template( 'wpop-radios' );
						break;
					case 'toggles':
						field = wp.template( 'wpop-toggles' );
						break;
					case 'media':
						field = wp.template( 'wpop-media' );
						break;
					case 'textarea':
						field = wp.template( 'wpop-textarea' );
						break;
					case 'editor':
						field = wp.template( 'wpop-editor' );
						break;
				};
				print( field( data ) ); #>
				<p class="desc desc-{{{ data.key }}}">{{{ data.desc }}}</p>
				<div class="clear"></div>
			</li>
			<span class="spacer"></span>
		</script>
		<script type="text/html" id="tmpl-wpop-panel">
			<# var sidebarTemplate = wp.template( 'wpop-sidebar' ),
				   footerTemplate = wp.template( 'wpop-footer' ); #>
			<div id="wpopOptions">
				<section class="wrap wp"><header><h2></h2></header></section>
				<section id="wpop" class="wrap">
					<form method="post" class="pure-form" autocomplete="off" onReset="return confirm('Reset ALL ' + 'options? (Save still req.)">
						<header class="wpop-head">
							<div class="inner">
								<h1><# if( '' !== data.dashicon ) { print( wp.html.string( {
										tag: 'span',
										attrs: { class: 'dashicons-before ' + data.dashicon + ' page-icon' }
									} ) ); } #>{{{ data.page_label }}}</h1>
								<input type="submit"
									   class="button button-primary button-hero save-all"
									   value="Save All"
									   name="submit">
							</div>
						</header>
						<!-- notifications -->
						<div id="wpopContent" class="pure-g">
							<# print( sidebarTemplate( data ) ); #>
							<div id="wpopMain" class="pure-u-1 pure-u-md-18-24">
								<ul id="wpopOptNavUl"><li id="panel-main" class="section active"></li></ul>
							</div>
							<# print( footerTemplate( data ) ); #>
						</div>
						<# print( data.nonce ); #>
					</form>
				</section>
			</div>
		</script>
		<!-- fields -->
		<script type="text/html" id="tmpl-wpop-switch">
			<div class="onoffswitch-wrap">
				<div class="onoffswitch">
					<# 	var inputTemplate = wp.template( 'wpop-input' );
					data.classStr = 'onoffswitch-checkbox';
					data.checked = 'checked';
					data.type = 'checkbox';
					print( inputTemplate( data ) ); #>
					<label class="onoffswitch-label" for="{{{ data.key }}}">
						<span class="onoffswitch-inner"></span>
						<span class="onoffswitch-switch"></span>
					</label>
				</div>
			</div>
		</script>
		<script type="text/html" id="tmpl-wpop-input">
			<# 	var fieldAtts = {
					id: data.key,
					name: data.key,
					type: data.type,
					'data-wpop-field': data.fieldType
				};
				if ( false !== data.saved ) {
					fieldAtts.value = data.saved;
				}
				if ( data.placehold ) {
					fieldAtts.placeholder = data.placehold;
				}
				if ( 'password' === data.type ) {
					fieldAtts.autocomplete = 'off';
				}
				if ( 'color' === data.type ) {
					fieldAtts.type = 'color';
				}
				if ( '' !== data.classStr ) {
					fieldAtts.class = data.classStr;
				}
				var inputField = { tag: 'input', attrs: fieldAtts };
				if ( 'checkbox' === data.type ) {
					inputField.single = true;
				}
				print( wp.html.string( inputField ) ); #>
		</script>
		<script type="text/html" id="tmpl-wpop-color">
			<# var inputTemplate = wp.template( 'wpop-input' );
			   var colorField = inputTemplate( data );
			   print( colorField );
			   jQuery('#wpop_example_color').spectrum(); #>
		</script>
		<script type="text/html" id="tmpl-wpop-encrypted">
			<# 	var inputTemplate = wp.template( 'wpop-input' );
				data.classStr = 'wp-ui-text-notification';
				print( inputTemplate( data ) ); #><br />
			<code style="color:#777;font-size:0.6rem"><span class="dashicons dashicons-admin-network"></span> Secured using 256-bit encryption</code>
			<br />
			<# if ( '' !== data.saved ) {
				print( inputTemplate( {
					key: data.key + '-stored',
					type: 'hidden',
					saved: data.saved
				} ) );
			} #>
		</script>
		<script type="text/html" id="tmpl-wpop-textarea">
			<textarea id="{{{ data.key }}}" name="{{{ data.key}}}" data-field="{{{ data.type }}}">{{{ data.saved }}}</textarea>
		</script>
		<script type="text/html" id="tmpl-wpop-media-meta">
			<br ><strong></strong>
			<table id="{{{ data.key }}}-meta" class="widefat striped">
				<thead>
					<tr>
						<td><span class="dashicons dashicons-wordpress-alt"></span> ID</td>
						<td>Dimensions</td>
						<td>Size</td>
						<td>Orientation</td>
						<td>Added</td>
					</tr>
				</thead>
				<tbody>
					<td>{{{ data.id }}}</td>
					<td>{{{ data.width }}}x{{{ data.height }}}</td>
					<td>{{{ data.filesizeHumanReadable }}}</td>
					<td>{{{ data.orientation }}}</td>
					<td>{{{ data.dateFormatted }}}</td>
				</tbody>
			</table><br />
		</script>
		<script type="text/html" id="tmpl-wpop-media">
			<div id="{{{ data.key }}}-wrap">
				<div style="float:right;text-align:center;">
					<div id="{{{ data.key }}}-slot" style="width:100px;height:100px;background:#efefef;border: 2px
					dashed #ccc;position:relative;top:-0.75rem;" class=""></div>
					<a id="{{{ data.key }}}-edit" href="#">See in Library</a>
				</div>
				<div style="margin-top:0.5rem;">
					<a href="#" class="button button-secondary" data-wp-media data-field="{{{ data.key }}}">Select Media</a>
					<a href="#" class="button button-seoncdary" data-remove-media>Remove Media</a>
				</div>
				<div id="{{{ data.key }}}-meta" style="display:flex;padding:0.5rem 1rem 0.5rem 0;"></div>
			</div>
		</script>
		<script type="text/html" id="tmpl-wpop-select">
			<select id="{{{ data.key }}}" name="{{{ data.key }}}" data-field="{{{ data.type }}}"
			<# if ( 'multiselect' === data.fieldType ) { #> multiple <# } #>>
				<# _.each( data.choices, function( val, key ) { #>
				<option value="{{{ key }}}">{{{ val }}}</option>
				<# } ); #>
			</select>
		</script>
		<script type="text/html" id="tmpl-wpop-radios">
			<# console.log( 'radio data');
			console.log( data ); #>
			<div style="float:right;">
				<# _.each( data.choices, function( choice ) { #>
					{{{ choice }}}
					<# print( wp.html.string( { tag: 'input', attrs: { type: 'radio', name: data.key, value: choice } } )); #>
					<br />
				<# } ); #>
			</div>
		</script>
		<script type="text/html" id="tmpl-wpop-toggles">
			<div style="float:right;">
				<# _.each( data.choices, function( choice, key ) { #>
					<span>{{{ choice }}}</span>
					<div class="toggleswitch-wrap">
						<div class="toggleswitch">
							<# 	var inputTemplate = wp.template( 'wpop-input' );
							data.classStr = 'toggleswitch-checkbox';
							data.checked = 'checked';
							data.type = 'checkbox';
							print( inputTemplate( { key: choice, type: 'checkbox', saved: key } ) ); #>
							<label class="toggleswitch-label" for="{{{ choice }}}">
								<span class="toggleswitch-inner"></span>
								<span class="toggleswitch-switch"></span>
							</label>
						</div>
					</div><br />
				<# } ); #>
			</div>
		</script>
		<script type="text/html" id="tmpl-wpop-editor">
			<# console.log( 'editor field running' ); #>
			<textarea id="{{{ data.key }}}" name="{{{ data.key }}}">{{{ data.saved }}}</textarea>
			<# var editorId = data.key;
			   wp.editor.initialize( editorId ); console.log( 'awwyiss it did it'); #>
		</script>
		<?php echo ob_get_clean();
	}

}