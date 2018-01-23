<?php
/**
 * [WPOP] WordPress Phoenix Options Panel - Field Builder Classes
 *
 * @authors 🌵 WordPress Phoenix 🌵 / Seth Carstens, David Ryan
 * @package wpop
 * @version 3.5.0
 * @license GPL-2.0+ - please retain comments that express original build of this file by the author.
 */

namespace WPOP\V_3_5;

if ( ! function_exists( 'add_filter' ) ) { // avoid direct calls to file
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Class Panel
 * @package WPOP\V_3_0
 */
class Panel {

	/**
	 * @var null - string used by class to determine wordpress data api
	 */
	public $api = null;

	/**
	 * @var null|string - string/slug for a panel
	 */
	public $id = null;

	/**
	 * @var string - capability user must have for panel to display.
	 */
	public $capability = 'manage_options';

	/**
	 * @var array - array of fields (aka parts because fields can also be file includes or markup)
	 */
	public $parts = [];

	/**
	 * @var bool - abstract boolean used to toggle primary hooks (network options, network user metadata, etc use this)
	 */
	public $network_admin = false;

	/**
	 * @var array - string notifications to print at top of panel
	 */
	public $notifications = [];

	/**
	 * @var null|void - preset with WP Core Object ID from query param
	 * @see $this->maybe_capture_wp_object_id();
	 */
	public $obj_id = null;

	/**
	 * Container constructor.
	 *
	 * @param array $args
	 * @param array $sections
	 */
	public function __construct( $args = [], $sections = [] ) {
		if ( ! isset( $args['id'] ) ) {
			echo "Setting a panel ID is required";
			exit;
		}
		$this->id = preg_replace( '/_/', '-', $args['id'] );


		foreach ( $args as $key => $val ) {
			$this->$key = $val;
		}

		$this->api    = $this->detect_data_api_and_permissions();
		$this->obj_id = $this->maybe_capture_wp_object_id();

		foreach ( $sections as $section_id => $section ) {
			if ( isset( $section['parts'] ) ) {
				foreach ( $section['parts'] as $part_id => $part_config ) {
					$current_part_classname    = __NAMESPACE__ . '\\' . $part_config['field'];
					$part_config['panel_id']   = $this->id;
					$part_config['section_id'] = $section_id;
					$part_config['panel_api']  = $this->api;

					$this->add_part(
						$section_id,
						$section,
						new $current_part_classname( $part_id, $part_config )
					);
				}
			}
		}
	}

	public function __toString() {
		return $this->id;
	}

	/**
	 * Listen for query parameters denoting Post, User or Term object IDs for metadata api or network/site option apis
	 */
	public function detect_data_api_and_permissions() {
		$error = null;
		$api   = null;
		if ( isset( $_GET['page'] ) ) {
			if ( isset( $_GET['post'] ) && is_numeric( $_GET['post'] ) ) {
				$api = 'post';
			} elseif ( isset( $_GET['user'] ) && is_numeric( $_GET['user'] ) ) {
				if ( is_multisite() && is_network_admin() ) {
					$this->network_admin = true;
					$api                 = 'user-network';
				} else {
					$api = 'user';
				}
			} elseif ( isset( $_GET['term'] ) && is_numeric( $_GET['term'] ) ) {
				$api = 'term';
			} elseif ( is_multisite() && is_network_admin() ) {
				$this->network_admin = true;
				$api                 = 'network';
			} else {
				$api = 'site';
			}
		} else {
			$api = '';
		}

		// allow api auto detection if not set in config, but if its set and doesn't match then ignore and use config
		// value for safety (tl;dr - will ignore &term=1 param on a site options panel when 'api' is defined to prevent
		// accidental API override)
		if ( isset( $this->api ) && $api !== $this->api ) {
			return $this->api;
		}

		return $api;
	}

	public function maybe_capture_wp_object_id() {
		switch ( $this->api ) {
			case 'post':
				return absint( $_GET['post'] );
				break;
			case 'user':
				return absint( $_GET['user'] );
				break;
			case 'term':
				return absint( $_GET['term'] );
				break;
			default:
				return null;
				break;
		}
	}

	/**
	 * Main external developer method used to add parts (sections/fields/markup/etc) to a Panel
	 *
	 * @param $section_id
	 * @param $section
	 * @param $part object - one of the part classes from this file
	 */
	public function add_part( $section_id, $section, $part ) {
		if ( ! isset( $this->parts[ $section_id ] ) ) {
			$this->parts[ $section_id ]          = $section;
			$this->parts[ $section_id ]['parts'] = array();
		}

		array_push( $this->parts[ $section_id ]['parts'], $part );
	}


	/**
	 * @return bool
	 */
	public function run_options_save_process() {
		if ( ! isset( $_POST['submit'] )
		     || ! is_string( $_POST['submit'] )
		     || 'Save All' !== $_POST['submit']
		) {
			return false; // only run logic if submiting
		}
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->id ) ) {
			return false; // check for nonce
		}

		$any_updated = false;


		// note $_POST[ $part->id ] that taps the key's value from the submit array
		foreach ( $this->parts as $section ) {
			foreach ( $section->parts as $part ) {
				$part             = get_object_Vars( $part );
				$part['panel_id'] = $this->id;
				$field_type       = ( isset( $part['field_type'] ) && ! empty( $part['field_type'] ) ) ? $part['field_type'] :
					$part['input_type'];
				$sanitize_input   = $this->sanitize_options_panel( $field_type, $part['id'], $_POST[ $part['id'] ] );
				$obj_id           = isset( $part['obj_id'] ) ? $part['obj_id'] : null;

				if ( 'wpop-encrypted-pwd-field-unchanged' !== $sanitize_input ) {
					error_log( 'the part' );
					error_log( var_export( $part, true ) );
					$updated = new Save_Single_Field(
						$part['panel_id'],
						$part['panel_api'],
						$part['id'],
						$sanitize_input,
						$obj_id
					);
				}

				$any_updated = ( $updated && ! $any_updated ) ? true : $any_updated;
			}
		}

		if ( $any_updated ) {
			$this->notifications[] = 'Some options were saved';
		}

		return $any_updated;
	}

	protected function sanitize_options_panel( $input_type, $id, $value ) {
		switch ( $input_type ) {
			case 'password':
				if ( isset( $_POST[ 'stored_' . $id ] ) && ! empty( $_POST[ 'stored_' . $id ] ) ) {
					if ( $_POST[ 'stored_' . $id ] === $_POST[ $id ] ) {
						// unchanged password? do nothing
						return 'wpop-encrypted-pwd-field-val-unchanged';
					} else {
						// stored password but field updated so overwrite
						return Password::encrypt( $value );
					}
				} else {
					// insert new password
					return Password::encrypt( $value );
				}
				break;
			case 'color':
				return sanitize_hex_color_no_hash( $value );
				break;
			case '':
				return sanitize_key( $value );
				break;
			case 'editor':
				return wp_kses_post( $value );
				break;
			case 'textarea':
				return sanitize_textarea_field( $value );
				break;
			case 'text':
			default:
				return sanitize_text_field( $value );
				break;
		}
	}

	/**
	 * Print WordPress Admin Notifications
	 * @example $note_data = array( 'notification' => 'My text', 'type' => 'notice-success' )
	 */
	public function echo_notifications() {
		foreach ( $this->notifications as $note_data ) {
			$data         = is_array( $note_data ) ? $note_data : [ 'notification' => $note_data ];
			$data['type'] = isset( $data['type'] ) ? $data['type'] : 'notice-success';
			echo HTML::tag(
				'div',
				[ 'class' => 'notice ' . $data['type'] ],
				HTML::tag( 'p', [], $data['notification'] )
			);
		}
	}

	/**
	 * Get class name without versioned namespace.
	 *
	 * @return string
	 */
	public function get_clean_classname() {
		return strtolower( explode( '\\', get_called_class() )[2] );
	}

	/**
	 *
	 * @param      $key
	 * @param      $value
	 * @param bool $network
	 * @param null $obj_id
	 *
	 * @return bool
	 */
	protected function do_options_save( $key, $value, $network = false, $obj_id = null ) {
		if ( ! empty( $obj_id ) && absint( $obj_id ) ) {
			return false; // TODO: build term meta API saving for multisite
		}
		switch ( $network ) {
			case true:
				return ! empty( $value ) ? update_site_option( $key, $value ) : delete_site_option( $key );
				break;
			case false:
			default:
				return ! empty( $value ) ? update_option( $key, $value ) : delete_option( $key );
				break;
		}
	}
} // END Container

/**
 * Class Page
 * @package WPOP\V_3_0
 */
class Page extends Panel {

	/**
	 * @var string
	 */
	public $parent_page_id = '';

	/**
	 * @var string
	 */
	public $page_title = 'Custom Site Options';

	/**
	 * @var string
	 */
	public $menu_title = 'Custom Site Options';

	/**
	 * @var
	 */
	public $dashicon;

	/**
	 * @var bool
	 */
	public $disable_styles = false;

	/**
	 * @var bool
	 */
	public $theme_page = false;

	/**
	 * Page constructor.
	 *
	 * @param array $args
	 * @param array $fields
	 */
	public function __construct( $args = [], $fields ) {
		parent::__construct( $args, $fields );
	}

	/**
	 * !!! USE ME TO RUN THE PANEL !!!
	 *
	 * Main method called by extending class to initialize the panel
	 */
	public function initialize_panel() {
		if ( ! empty( $this->api ) && is_string( $this->api ) ) {
			$decide_network_or_single_site_admin = $this->network_admin ? 'network_admin_menu' : 'admin_menu';
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dependencies' ) );
			add_action( $decide_network_or_single_site_admin, array( $this, 'add_settings_submenu_page' ) );
			add_action( 'admin_init', array( $this, 'run_options_save_process' ) );
		}
	}

	/**
	 * Register Submenu Page with WordPress to display the panel on
	 */
	public function add_settings_submenu_page() {
		add_submenu_page(
			$this->parent_page_id, // file.php to hook into
			$this->page_title,
			$this->menu_title,
			'read',
			$this->id,
			array( $this, 'build_parts' )
		);
	}


	/**
	 *
	 */
	public function build_parts() {
		$page_icon = ! empty( $this->dashicon ) ? HTML::dashicon( $this->dashicon . ' page-icon' ) . ' ' : '';
		ob_start(); ?>
		<div id="wpopOptions">
			<?php
			if ( ! $this->disable_styles ) {
				$this->inline_styles_and_scripts();
			}
			?>
			<!-- IMPORTANT: allows core admin notices -->
			<section class="wrap wp">
				<header><h2></h2></header>
			</section>
			<section id="wpop" class="wrap">
				<form method="post" class="pure-form wpop-form">
					<header class="wpop-head">
						<div class="inner">
							<?php echo HTML::tag( 'h1', [], $page_icon . $this->page_title ); ?>
							<input type="submit"
								   class="button button-primary button-hero save-all"
								   value="Save All"
								   name="submit">
						</div>
					</header>
					<?php
					if ( isset( $_POST['submit'] ) && $_POST['submit'] ) {
						$this->echo_notifications();
					}
					?>
					<div id="wpopContent" class="pure-g">
						<div id="wpopNav" class="pure-u-1 pure-u-md-6-24">
							<div class="pure-menu wpop-options-menu">
								<ul class="pure-menu-list">
									<?php
									foreach ( $this->parts as $section_id => $section ) {
										$section_icon = ! empty( $section['dashicon'] ) ?
											HTML::tag( 'span', [
												'class' => 'dashicons ' . $section['dashicon'] . ' menu-icon'
											] ) : '';
										echo HTML::tag(
											'li',
											[
												'id'    => $section_id . '-nav',
												'class' => 'pure-menu-item',
											],
											HTML::tag(
												'a',
												[
													'href'  => '#' . $section_id,
													'class' => 'pure-menu-link',
												],
												$section_icon . $section['label']
											)
										);
									}
									?>
								</ul>
							</div>
							<?php echo wp_nonce_field( $this->id, '_wpnonce', true, false ); ?>
						</div>
						<div id="wpopMain" class="pure-u-1 pure-u-md-18-24">
							<ul id="wpopOptNavUl" style="list-style: none;">
								<?php
								foreach ( $this->parts as $section_key => $section ) {
									$built_section = new Section( $section_key, $section );
									$built_section->echo_html();
								} ?>
							</ul>
						</div>
						<footer class="pure-u-1">
							<div class="pure-g">
								<div class="pure-u-1 pure-u-md-1-3">
									<div>
										<span>Stored in: <?php echo HTML::tag( 'code', [], $this->get_storage_table() ); ?></span>
									</div>
								</div>
								<div class="pure-u-1 pure-u-md-1-3">
									<div>

									</div>
								</div>
								<div class="pure-u-1 pure-u-md-1-3">
									<?php if ( is_super_admin() ) { ?>

									<?php } ?>
								</div>
							</div>
						</footer>
					</div>
				</form>
			</section>
		</div> <!-- end #wpopOptions -->
		<?php
		echo ob_get_clean();
	}

	/**
	 *
	 */
	public function inline_styles_and_scripts() {
		ob_start(); ?>
		<style>
			.onOffSwitch-inner, .onOffSwitch-switch {
				transition: all .5s cubic-bezier(1, 0, 0, 1)
			}

			.onOffSwitch {
				position: relative;
				width: 110px;
				-webkit-user-select: none;
				-moz-user-select: none;
				-ms-user-select: none;
				margin-left: auto;
				margin-right: 12px
			}

			input[type=checkbox].onOffSwitch-checkbox {
				display: none
			}

			.onOffSwitch-label {
				display: block;
				overflow: hidden;
				cursor: pointer;
				border: 2px solid #EEE;
				border-radius: 28px
			}

			.onOffSwitch-inner {
				display: block;
				width: 200%;
				margin-left: -100%
			}

			.onOffSwitch-inner:after, .onOffSwitch-inner:before {
				display: block;
				float: left;
				width: 50%;
				height: 40px;
				padding: 0;
				line-height: 40px;
				font-size: 17px;
				font-family: Trebuchet, Arial, sans-serif;
				font-weight: 700;
				box-sizing: border-box
			}

			.cb, .save-all, .wpop-option.color_picker .iris-picker {
				float: right
			}

			.onOffSwitch-inner:before {
				content: "ON";
				padding-left: 10px;
				background-color: #21759B;
				color: #FFF
			}

			.onOffSwitch-inner:after {
				content: "OFF";
				padding-right: 10px;
				background-color: #EEE;
				color: #BCBCBC;
				text-align: right
			}

			.onOffSwitch-switch {
				display: block;
				width: 28px;
				margin: 6px;
				background: #BCBCBC;
				position: absolute;
				top: 0;
				bottom: 0;
				right: 66px;
				border: 2px solid #EEE;
				border-radius: 20px
			}

			.onOffSwitch-checkbox:checked + .onOffSwitch-label .onOffSwitch-inner {
				margin-left: 0
			}

			.onOffSwitch-checkbox:checked + .onOffSwitch-label .onOffSwitch-switch {
				right: 0;
				background-color: #D54E21
			}

			.cb, .cb-wrap, .desc:after, .pwd-clear, .save-all, span.menu-icon, span.spacer {
				position: relative
			}

			.wpop-form {
				margin-bottom: 0;
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
				width: 100% !important;
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

			.pure-menu-disabled, .pure-menu-heading, .pure-menu-link {
				padding: 1.3em 2em
			}

			.pure-menu-active > .pure-menu-link, .pure-menu-link:focus, .pure-menu-link:hover {
				background: inherit
			}

			#wpopOptions header {
				overflow: hidden;
				max-height: 88px
			}

			#wpopNav li.pure-menu-item {
				height: 55px;
			}

			#wpopNav p.submit input {
				width: 100%
			}

			#wpop {
				border: 1px solid #D8D8D8;
				background: #fff
			}

			.opn a.pure-menu-link {
				color: #fff !important
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
				margin: 0 1.5rem 0 0
			}

			span.menu-icon {
				left: -.5rem
			}

			span.page-icon:before {
				font-size: 2.5rem;
				position: relative;
				top: -4px;
				right: 4px;
				color: #777
			}

			.clear {
				clear: both
			}

			.section {
				padding: 0 0 5px
			}

			.section h3 {
				margin: 0 0 10px;
				padding: 2rem 1.5rem
			}

			.section h4.label {
				margin: 0;
				display: table-cell;
				border: 1px solid #e9e9e9;
				background: #f1f1f1;
				padding: .33rem .66rem .5rem;
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
				border-top: 1px solid rgba(0, 0, 0, .1);
				border-bottom: 1px solid rgba(255, 255, 255, .3)
			}

			li.even.option {
				background-color: #ccc
			}

			input[disabled=disabled] {
				background-color: #CCC
			}

			.cb {
				right: 20px
			}

			.card-wrap {
				width: 100%
			}

			.fullwidth {
				width: 100% !important;
				max-width: 100% !important
			}

			.wpop-head {
				background: #f1f1f1
			}

			.wpop-head > .inner {
				padding: 1rem 1.5rem 0
			}

			.save-all {
				top: -48px
			}

			.desc {
				margin: .5rem 0 0 .25rem;
				font-weight: 300;
				font-size: 12px;
				line-height: 16px;
				transition: all 1s ease;
				color: #888;
				-webkit-transition: all 1s ease;
				-moz-transition: all 1s ease;
				-o-transition: all 1s ease
			}

			.desc:after {
				display: block;
				width: 98%;
				border-top: 1px solid rgba(0, 0, 0, .1);
				border-bottom: 1px solid rgba(255, 255, 255, .3)
			}

			.wpop-option input[type=text] {
				width: 90%
			}

			input[data-assigned] {
				width: 100% !important
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
				border: 2px solid #cd1713 !important;
				background: #f1f1f1 !important;
				color: #cd1713 !important;
				box-shadow: none;
				-webkit-box-shadow: none;
				margin-left: 1rem !important
			}

			.pwd-clear {
				margin-left: .5rem !important;
				top: 1px
			}

			.pure-form footer {
				background: #f1f1f1;
				border-top: 1px solid #D8D8D8
			}

			.pure-form footer div div > * {
				padding: 1rem .33rem
			}

			.wpop-option .wp-editor-wrap {
				margin-top: .5rem
			}

			.wpop-option.color_picker input {
				width: 50%
			}

			.cb-wrap {
				display: block;
				right: 1.33rem;
				max-width: 110px;
				margin-left: auto;
				top: -1.66rem
			}
		</style>
		<?php
		$css = ob_get_clean();
		ob_start(); ?>
		<script type="text/javascript">
			!function( t, o ) {
				"use strict";
				t.wp = t.wp || {}, t.wp.hooks = t.wp.hooks || new function() {
					function t( t, o, i, n ) {
						var e, a, p;
						if ( r[t][o] ) if ( i ) if ( e = r[t][o], n ) for ( p = e.length; p--; ) (a = e[p]).callback === i && a.context === n && e.splice( p, 1 ); else for ( p = e.length; p--; ) e[p].callback === i && e.splice( p, 1 ); else r[t][o] = []
					}

					function o( t, o, n, e, a ) {
						var p = { callback: n, priority: e, context: a }, c = r[t][o];
						c ? (c.push( p ), c = i( c )) : c = [p], r[t][o] = c
					}

					function i( t ) {
						for ( var o, i, n, e = 1, a = t.length; a > e; e++ ) {
							for ( o = t[e], i = e; (n = t[i - 1]) && n.priority > o.priority; ) t[i] = t[i - 1], --i;
							t[i] = o
						}
						return t
					}

					function n( t, o, i ) {
						var n, e, a = r[t][o];
						if ( !a ) return "filters" === t && i[0];
						if ( e = a.length, "filters" === t ) for ( n = 0; e > n; n++ ) i[0] = a[n].callback.apply( a[n].context, i ); else for ( n = 0; e > n; n++ ) a[n].callback.apply( a[n].context, i );
						return "filters" !== t || i[0]
					}

					var e = Array.prototype.slice, a = {
						removeFilter: function( o, i ) {
							return "string" == typeof o && t( "filters", o, i ), a
						}, applyFilters: function() {
							var t = e.call( arguments ), o = t.shift();
							return "string" == typeof o ? n( "filters", o, t ) : a
						}, addFilter: function( t, i, n, e ) {
							return "string" == typeof t && "function" == typeof i && (n = parseInt( n || 10, 10 ), o( "filters", t, i, n, e )), a
						}, removeAction: function( o, i ) {
							return "string" == typeof o && t( "actions", o, i ), a
						}, doAction: function() {
							var t = e.call( arguments ), o = t.shift();
							return "string" == typeof o && n( "actions", o, t ), a
						}, addAction: function( t, i, n, e ) {
							return "string" == typeof t && "function" == typeof i && (n = parseInt( n || 10, 10 ), o( "actions", t, i, n, e )), a
						}
					}, r = { actions: {}, filters: {} };
					return a
				}
			}( window ), jQuery( document ).ready( function( t ) {
				function o() {
					wp.hooks.addAction( "wpopPreInit", a ), wp.hooks.addAction( "wpopInit", e ), wp.hooks.addAction( "wpopInit", r ), wp.hooks.addAction( "wpopInit", p ), wp.hooks.addAction( "wpopSectionNav", i ), wp.hooks.addAction( "wpopPwdClear", c ), wp.hooks.addAction( "wpopImgUpload", l ), wp.hooks.addAction( "wpopImgRemove", s ), wp.hooks.addAction( "wpopSubmit", n )
				}

				function i( o, i ) {
					i.preventDefault();
					var n = t( t( o ).attr( "href" ) ).addClass( "active" ),
						e = t( t( o ).attr( "href" ) + "-nav" ).addClass( "active wp-ui-primary opn" );
					return window.location.hash = t( o ).attr( "href" ), window.scrollTo( 0, 0 ), t( n ).siblings().removeClass( "active" ), t( e ).siblings().removeClass( "active wp-ui-primary opn" ), !1
				}

				function n() {
					t( ".wpop-loader-wrapper" ).css( "display", "inherit" )
				}

				function e() {
					(hash = window.location.hash) ? t( hash + "-nav a" ).trigger( "click" ) : t( "#wpopNav li:first a" ).trigger( "click" )
				}

				function a() {
					t( "html, body" ).animate( { scrollTop: 0 } )
				}

				function r() {
					"undefined" != typeof iris && t( '[data-field="color_picker"]' ).iris( { width: 320, hide: !1 } )
				}

				function p() {
					t( "[data-select]" ).selectize( {
						allowEmptyOption: !1,
						placeholder: t( this ).attr( "data-placeholder" )
					} );
					t( "[data-multiselect]" ).selectize( { plugins: ["restore_on_backspace", "remove_button", "drag_drop", "optgroup_columns"] } )
				}

				function c( o, i ) {
					i.preventDefault(), t( o ).prev().val( null )
				}

				function l( o, i ) {
					i.preventDefault();
					var n = t( o ).data();
					d || (d = wp.media.frames.wpModal || wp.media( {
						title: n.title,
						button: { text: n.button },
						library: { type: "image" },
						multiple: !1
					} ), d.on( "select", function() {
						var i = d.state().get( "selection" ).first().toJSON();
						if ( "object" == typeof i ) {
							var n = t( o ).closest( ".wpop-option" );
							n.find( '[type="hidden"]' ).val( i.id ), n.find( "img" ).attr( "src", i.url ).show(), t( o ).attr( "value", "Replace " + t( o ).attr( "data-media-label" ) ), n.find( ".img-remove" ).show()
						}
					} )), d.open()
				}

				function s( o, i ) {
					i.preventDefault();
					var n = confirm( "Remove " + t( o ).attr( "data-media-label" ) + "?" );
					if ( n ) {
						var e = t( o ).closest( ".wpop-option" ), a = e.find( ".blank-img" ).html();
						e.find( '[type="hidden"]' ).val( null ), e.find( "img" ).attr( "src", a ), e.find( ".button-hero" ).val( "Set Image" ), t( o ).hide()
					}
				}

				var d;
				o(), wp.hooks.doAction( "wpopPreInit" ), t( "#wpopNav li a" ).click( function( t ) {
					wp.hooks.doAction( "wpopSectionNav", this, t )
				} ), wp.hooks.doAction( "wpopInit" ), t( 'input[type="submit"]' ).click( function( t ) {
					wp.hooks.doAction( "wpopSubmit", this, t )
				} ), t( ".pwd-clear" ).click( function( t ) {
					wp.hooks.doAction( "wpopPwdClear", this, t )
				} ), t( ".img-upload" ).on( "click", function( t ) {
					wp.hooks.doAction( "wpopImgUpload", this, t )
				} ), t( ".img-remove" ).on( "click", function( t ) {
					wp.hooks.doAction( "wpopImgRemove", this, t )
				} ), t( ".add-button" ).on( "click", function( t ) {
					wp.hooks.doAction( "wpopRepeaterAdd", this, t )
				} )
			} );
		</script>
		<?php
		$js = ob_get_clean();
		echo PHP_EOL . $css . PHP_EOL . $js . PHP_EOL;
	}

	/**
	 * @return string
	 */
	public function get_storage_table() {
		switch ( is_multisite() ) {
			case true:
				return $this->network_admin ? 'wp_sitemeta' : $this->get_multisite_table( get_current_blog_id() );
				break;
			case false:
			default:
				return 'wp_options';
				break;
		}
	}

	/**
	 * @param $blog_id
	 *
	 * @return string
	 */
	public function get_multisite_table( $blog_id ) {
		return 1 === intval( $blog_id ) ? 'wp_options' : 'wp_' . $blog_id . '_options';
	}

	/**
	 *
	 */
	public function enqueue_dependencies() {
		$unpkg = 'https://unpkg.com/purecss@1.0.0/build/';
		wp_register_style( 'wpop-pure-base', $unpkg . 'base-min.css' );
		wp_register_style( 'wpop-pure-grids', $unpkg . 'grids-min.css', array( 'wpop-pure-base' ) );
		wp_register_style( 'wpop-pure-grids-r', $unpkg . 'grids-responsive-min.css', array( 'wpop-pure-grids' ) );
		wp_register_style( 'wpop-pure-menus', $unpkg . 'menus-min.css', array( 'wpop-pure-grids-r' ) );
		wp_register_style( 'wpop-pure-forms', $unpkg . 'forms-min.css', array( 'wpop-pure-menus' ) );
		wp_enqueue_style( 'wpop-pure-forms' ); // cue enqueue cascade

		// Enqueue media (needed for media modal)
		wp_enqueue_media();

		wp_enqueue_script( 'iris' ); // core color picker
		$selectize_cdn = 'https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.4/';
		wp_register_script( 'wpop-selectize', $selectize_cdn . 'js/standalone/selectize.min.js', array( 'jquery-ui-sortable' ) );
		wp_enqueue_script( 'wpop-selectize' );
		wp_register_style( 'wpop-selectize', $selectize_cdn . 'css/selectize.default.min.css' );
		wp_enqueue_style( 'wpop-selectize' );
		wp_register_script( 'clipboard', 'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.7.1/clipboard.min.js' );
		wp_enqueue_script( 'clipboard' );
	}
}

/**
 * Class Section
 * @package WPOP\V_3_0
 */
class Section {

	/**
	 * @var
	 */
	public $id;

	/**
	 * @var array
	 */
	public $classes = array( 'section' );

	/**
	 * @var string
	 */
	public $label = 'My Custom Section';

	/**
	 * @var
	 */
	public $dashicon;

	/**
	 * @var
	 */
	protected $parts;

	/**
	 * Section constructor.
	 *
	 * @param string $id
	 * @param array  $args
	 */
	public function __construct( $id, $args = [] ) {
		$this->id = $id;
		foreach ( $args as $name => $value ) {
			$this->$name = $value;
		}
	}

	/**
	 * Print Panel Markup
	 */
	public function echo_html() {
		ob_start();
		$section_content = '';
		foreach ( $this->parts as $part ) {
			$section_content .= $part->get_html();
		}
		echo HTML::tag( 'li', [
			'id'    => $this->id,
			'class' => implode( ' ', $this->classes )
		], HTML::tag( 'ul', [], $section_content ) );
		echo apply_filters( 'echo_html_option', ob_get_clean() );
	}
}

/**
 * Class Option
 * @package WPOP\V_3_0
 */
class Part {

	public $id;
	public $field_id;
	public $part_type = 'option';
	public $label = 'Option';
	public $description = '';
	public $default_value = '';
	public $classes = array( 'option' );
	public $atts = array( 'disabled' => null );
	public $wrapper;
	public $field_before = null;
	public $field_after = null;
	public $panel_api = false;
	public $panel_id = false;

	public function __construct( $i, $args = [] ) {
		$this->id       = $i;
		$this->field_id = $this->id;
		$this->wrapper  = array(
			'<li class="wpop-option ' . $this->get_clean_classname() . '">',
			'</li><span class="spacer"></span>',
		);
		foreach ( $args as $name => $value ) {
			$this->$name = $value;
		}
	}

	public function get_clean_classname() {
		return explode( '\\', get_called_class() )[2];
	}

	public function html_process_atts( $atts ) {
		$att_markup = [];
		foreach ( $atts as $key => $att ) {
			if ( false === empty( $att ) ) {
				$att_markup[] = sprintf( '%s="%s"', $key, $att );
			}
		}

		return implode( ' ', $att_markup );
	}

	public function get_classes( $class_str = '' ) {
		$maybe_classes = ! empty( $this->classes ) ? implode( ' ', $this->classes ) : null;
		$clean_return  = ( ! empty( $maybe_classes ) || ! empty( $passed_str_classes ) ) ? 'class="' . $maybe_classes . $class_str . '"' : null;

		return $clean_return;
	}

	public function build_base_markup( $field ) {
		ob_start();
		echo $this->wrapper[0] . '<h4 class="label">' . $this->label . '</h4>';
		echo $this->field_before . $field . $this->field_after;
		echo ( $this->description ) ? '<div class="desc clear">' . $this->description . '</div>' : '';
		echo '<div class="clear"></div>' . $this->wrapper[1];

		return ob_get_clean();
	}

	public function get_saved() {
		$pre_ = apply_filters( 'wpop_custom_option_enabled', false ) ? SM_SITEOP_PREFIX : '';
		switch ( $this->panel_api ) {
			case 'post':
				return new Get_Single_Field(
					$this->panel_id,
					$this->panel_api,
					$pre_ . $this->id,
					sanitize_text_field( $_GET['post'] ) // if string condition exists, param already checked
				);
				break;
			case 'term':
				return new Get_Single_Field(
					$this->panel_id,
					$this->panel_api,
					$pre_ . $this->id,
					sanitize_text_field( $_GET['term'] ) // if string condition exists, param already checked
				);
				break;
			case 'user-site':
			case 'user':
			case 'user-network':
				return new Get_Single_Field(
					$this->panel_id,
					$this->panel_api,
					$pre_ . $this->id,
					sanitize_text_field( $_GET['user'] ) // if string condition exists, param already checked
				);
				break;
			case 'network':
			case 'site':
			default:
				return new Get_Single_Field(
					$this->panel_id,
					$this->panel_api,
					$pre_ . $this->id,
					$this->default_value
				);
				break;
		}
	}

}

/**
 * Class Section_Desc
 * @package WPOP\V_3_0
 */
class Section_Desc extends Part {

	public function get_html() {
		ob_start();
		echo $this->wrapper[0];
		echo $this->description;
		echo $this->wrapper[1];
		echo '<span class="spacer"></span>';

		return ob_get_clean();
	}

}


/**
 * Class Input
 * @package WPOP\V_3_0
 */
class Input extends Part {
	public $input_type;
	public $password = false;

	public function get_html() {
		$option_val = ( false === $this->get_saved() || empty( $this->get_saved() ) ) ? $this->default_value : $this->get_saved();

		$type = ! empty( $this->input_type ) ? $this->input_type : 'hidden';
		ob_start();
		echo '<input id="' . esc_attr( $this->field_id ) . '" name="' . esc_attr( $this->field_id ) . '" type="' .
		     esc_attr( $type ) . '" value="' . esc_attr( $option_val ) .
		     '" data-field="' . esc_attr( $this->get_clean_classname() ) . '" ' . $this->get_classes() . ' ' . $this->html_process_atts( $this->atts ) . ' />';

		return $this->build_base_markup( ob_get_clean() );
	}

}

/**
 * Class Text
 * @package WPOP\V_3_0
 */
class Text extends Input {
	public $input_type = 'text';
}

/**
 * Class Color_Picker
 * @package WPOP\V_3_0
 */
class Color_Picker extends Input {
	public $input_type = 'text';
	public $field_type = 'color';
}

/**
 * Class Number
 * @package WPOP\V_3_0
 */
class Number extends Input {
	public $input_type = 'number';
}

/**
 * Class Url
 * @package WPOP\V_3_0
 */
class Url extends Input {
	public $input_type = 'url';
}

/**
 * Class password
 * @package WPOP\V_2_8
 * @notes   how to use: echo $this->decrypt( get_option( $this->id ) );
 */
class Password extends Input {
	public $input_type = 'password';

	public function __construct( $i, $args = [] ) {
		parent::__construct( $i, $args );
		$this->field_after = $this->pwd_clear_and_hidden_field();
		$this->password    = true;
		if ( ! defined( 'WPOP_ENCRYPTION_KEY' ) ) {
			// IMPORTANT: If you don't define a key, the class hashes the AUTH_KEY found in wp-config.php,
			// effectively locking the encrypted value to the current environment.
			$trimmed_key = substr( wp_salt(), 0, 15 );
			define( 'WPOP_ENCRYPTION_KEY', static::pad_key( sha1( $trimmed_key, true ) ) );
		}
	}

	public function pwd_clear_and_hidden_field() {
		$hidden_val = $this->get_saved();
		ob_start();
		echo '<a href="#" class="button button-secondary pwd-clear">clear</a>';
		echo '<input id="' . esc_attr( 'stored_' . $this->id ) . '" name="' . esc_attr( 'stored_' . $this->id ) . '" type="hidden"' .
		     ' value="' . esc_attr( $hidden_val ) . '" readonly="readonly" />';

		return ob_get_clean();
	}

	/**
	 * Fixes PHP7 issues where mcrypt_decrypt expects a specific key size. Used on MYSECRETKEY constant.
	 * You'll still have to run trim on the end result when decrypting,as seen in the "unencrypted_pass" function.
	 *
	 * @see http://stackoverflow.com/questions/27254432/mcrypt-decrypt-error-change-key-size
	 *
	 * @param $key
	 *
	 * @return bool|string
	 */
	static function pad_key( $key ) {

		if ( strlen( $key ) > 32 ) { // key too large
			return false;
		}

		$sizes = array( 16, 24, 32 );

		foreach ( $sizes as $s ) { // loop sizes, pad key
			while ( strlen( $key ) < $s ) {
				$key = $key . "\0";
			}
			if ( strlen( $key ) == $s ) {
				break; // finish if the key matches a size
			}
		}

		return $key;
	}

	public static function encrypt( $unencrypted_string ) {
		return base64_encode(
			mcrypt_encrypt(
				MCRYPT_RIJNDAEL_256,
				WPOP_ENCRYPTION_KEY,
				$unencrypted_string,
				MCRYPT_MODE_ECB
			)
		);
	}

	public static function decrypt( $encrypted_encoded ) {
		// Only call in server-side actions -- never use to print in markup or risk theft
		return trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, WPOP_ENCRYPTION_KEY, base64_decode( $encrypted_encoded ), MCRYPT_MODE_ECB ) );
	}
}

/**
 * Class Textarea
 * @package WPOP\V_3_0
 */
class Textarea extends Part {

	public $cols;
	public $rows;
	public $input_type = 'textarea';

	public function get_html() {
		$option_val = $this->get_saved();
		$att_markup = $this->html_process_atts( $this->atts );
		$this->cols = ! empty( $this->cols ) ? $this->cols : 80;
		$this->rows = ! empty( $this->rows ) ? $this->rows : 10;

		ob_start();
		echo '<textarea id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->id ) . '" cols="' .
		     esc_attr( $this->cols ) . '" rows="' . esc_attr( $this->rows ) . '" ' . $att_markup . '>' . stripslashes( $option_val ) . '</textarea>';

		return $this->build_base_markup( ob_get_clean() );
	}

}

/**
 * Class Editor
 * @package WPOP\V_3_0
 */
class Editor extends Part {

	public $input_type = 'editor';

	public function get_html() {
		return $this->build_base_markup(
			wp_editor(
				stripslashes( $this->get_saved() ),
				$this->id . '_editor',
				array(
					'textarea_name'    => $this->id, // used for saving val
					'drag_drop_upload' => false, // no work if multiple
					'tinymce'          => array( 'min_height' => 300 ),
					'editor_class'     => 'edit',
					'quicktags'        => true,
				)
			)
		);
	}
}

/**
 * Class Select
 * @package WPOP\V_3_0
 */
class Select extends Part {

	public $values;
	public $meta;
	public $empty_default = true;
	public $input_type = 'select';

	public function __construct( $i, $m ) {
		parent::__construct( $i, $m );
		$this->values = ( ! empty( $m['values'] ) ) ? $m['values'] : [];
		$this->meta   = ( ! empty( $m ) ) ? $m : [];
	}

	public function get_html() {
		$option_val     = $this->get_saved();
		$default_option = isset( $this->meta['option_default'] ) ? $this->meta['option_default'] : 'Select an option';

		ob_start();
		echo '<select id="' . $this->id . '" name="' . $this->id . '" value="' . $this->get_saved()
		     . '" data-select data-placeholder="' . $default_option . '">';
		if ( $this->empty_default ) {
			echo '<option value=""></option>';
		}
		foreach ( $this->values as $label => $value ) {
			$selected = ( $value === $option_val ) ? 'selected="selected"' : '';
			echo '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
		}
		echo '</select>';

		return $this->build_base_markup( ob_get_clean() );
	}

}

/**
 * Class Multiselect
 * @package WPOP\V_3_0
 */
class Multiselect extends Part {

	public $values;
	public $meta;
	public $allow_reordering = false;
	public $create_options = false;
	public $input_type = 'multiselect';

	public function __construct( $i, $m ) {
		parent::__construct( $i, $m );
		$this->values = ( ! empty( $m['values'] ) ) ? $m['values'] : [];
		$this->meta   = ( ! empty( $m ) ) ? $m : [];
	}

	public function get_html() {
		$save = $this->get_saved();
		ob_start();
		echo '<select multiple="multiple" id="' . $this->id . '" name="' . $this->id . '[]" data-multiselect />';
		$ordered_vals = ! empty( $save ) ? $this->multi_atts( $this->values, $save ) + $this->values : $this->values;
		foreach ( $ordered_vals as $key => $value ) {
			$selected = in_array( $key, $save, true ) ? 'selected="selected"' : '';
			echo '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
		}
		echo '</select>'; ?>
		<?php

		return $this->build_base_markup( ob_get_clean() );
	}

	function multi_atts( $pairs, $atts ) {
		$return = [];
		foreach ( $atts as $key ) {
			$return[ $key ] = $pairs[ $key ];
		}

		return $return;
	}

}

/**
 * Class Checkbox
 * @package WPOP\V_3_0
 */
class Checkbox extends Part {

	public $value;
	public $label_markup;
	public $input_type = 'checkbox';

	public function __construct( $i, $args = [] ) {
		parent::__construct( $i, $args );
		foreach ( $args as $name => $value ) {
			$this->$name = $value;
		}
	}

	public function get_html() {
		$checked = ( $this->get_saved() === $this->value ) ? ' checked="checked"' : '';
		$classes = ! empty( $this->label_markup ) ? 'onOffSwitch-checkbox' : 'cb';
		ob_start();
		echo '<div class="cb-wrap"><input type="checkbox" name="' . $this->id . '" id="' . $this->id . '" '
		     . $checked . ' class="' . $classes . '" value="' . $this->value . '" />' . $this->label_markup . '</div>';

		return $this->build_base_markup( ob_get_clean() );
	}

}

/**
 * Class Toggle_Switch
 * @package WPOP\V_3_0
 */
class Toggle_Switch extends Checkbox {
	public $input_type = 'toggle_switch';

	function __construct( $i, array $args = [] ) {
		parent::__construct( $i, $args );
		$this->label_markup = '<label class="onOffSwitch-label" for="' . $this->id .
		                      '"><div class="onOffSwitch-inner"></div><span class="onOffSwitch-switch"></span></label>';
	}
}

/**
 * Class Radio_Buttons
 * @package WPOP\V_3_0
 */
class Radio_Buttons extends Part {

	public $values;
	public $default_value;
	public $input_type = 'radio_buttons';

	public function __construct( $i, $c ) {
		parent::__construct( $i, $c );
		$this->values        = ( ! empty( $c['values'] ) ) ? $c['values'] : [];
		$this->default_value = ! empty( $this->default_value ) ? $this->default_value : '';
	}

	public function get_html() {
		ob_start();
		echo '<div class="radio-wrap">';
		foreach ( $this->values as $key => $value ) {
			$selected_val = $this->get_saved() ? $this->get_saved() : $this->default_value;
			$checked      = ( $selected_val === $value ) ? ' checked="checked"' : '';
			$echo         = ! is_numeric( $key ) ? $key : $value;
			echo '<input type="radio" name="' . $this->field_id . '" value="' . $value . '"' . ' id="' . $this->id .
			     '" ' . $checked . '/><label class="option-label" for="' . $this->field_id . '">' . $echo . '</label>';
			echo '<div class="clear"></div>';
		}
		echo '</div>';

		return $this->build_base_markup( ob_get_clean() );
	}

}

/**
 * Class Media
 * @package WPOP\V_3_0
 */
class Media extends Part {
	public $media_label = 'Image';
	public $input_type = 'media';

	public function get_html() {
		$empty        = ''; // TODO: REPLACE EMPTY IMAGE WITH CSS YO
		$saved        = array( 'url' => $empty, 'id' => '' );
		$option_val   = $this->get_saved();
		$insert_label = 'Insert ' . $this->media_label;
		if ( ! empty( $option_val ) && absint( $option_val ) ) {
			$img          = wp_get_attachment_image_src( $option_val );
			$saved        = array( 'url' => is_array( $img ) ? $img[0] : 'err', 'id' => $option_val );
			$insert_label = 'Replace ' . $this->media_label;
		}
		$vis        = empty( $option_val ) ? ' style="display:none;"' : '';
		$att_markup = $this->html_process_atts( $this->atts );

		ob_start();
		echo '<div class="blank-img" style="display:none;">' . $empty . '</div>';
		echo '<img src="' . $saved['url'] . '" class="img-preview" />';
		echo '<input id="' . $this->id . '_button" data-media-label="' . $this->media_label . '" '
		     . 'type="button" class="button button-secondary button-hero img-upload" value="' . $insert_label
		     . '" data-id="' . $this->id . '" data-button="Use ' . $this->media_label
		     . '" data-title="Select or Upload ' . $this->media_label . '"' . $att_markup . '/>';
		echo '<input id="' . $this->id . '" name="' . $this->id
		     . '" type="hidden" value="' . $saved['id'] . '"' . $att_markup . ' />';
		echo '<a href="#" class="button button-secondary img-remove" ' . ' data-media-label="'
		     . $this->media_label . '" ' . $vis . '>Remove ' . $this->media_label . '</a>';

		return $this->build_base_markup( ob_get_clean() );
	}

}

/**
 * Class Include_Partial
 * @package WPOP\V_3_0
 */
class Include_Partial extends Part {

	public $filename;
	public $input_type = 'include_partial';

	public function __construct( $i, $config ) {
		parent::__construct( $i, [] );
		$this->filename = ( ! empty( $config['filename'] ) ) ? $config['filename'] : 'set_the_filename.php';
	}

	public function get_html() {
		return $this->echo_html();
	}

	public function echo_html() {
		if ( ! empty( $this->filename ) && is_file( $this->filename ) ) {
			include_once $this->filename;
		}
	}
}

/**
 * Class Include_Markup
 * @package WPOP\V_3_0
 */
class Include_Markup extends Part {
	public $markup;
	public $input_type = 'include_markup';

	public function __construct( $i, $v = [] ) {
		parent::__construct( $i, $v );
		$this->markup = ( ! empty( $v['markup'] ) ) ? $v['markup'] : null;
	}

	public function get_html() {
		return $this->echo_html();
	}

	public function echo_html() {
		if ( is_string( $this->markup ) && ! empty( $this->markup ) ) {
			echo $this->markup;
		}
	}
}

/**
 * Class HTML
 * @package WPOP\V_2_10
 * @link    https://github.com/Automattic/amp-wp/blob/master/includes/utils/class-amp-html-utils.php
 */
class HTML {
	/**
	 * Dashicon Markup Helper
	 *
	 * @param $class_str - the dashicons-* class and any addl
	 *
	 * @return string
	 */
	public static function dashicon( $class_str ) {
		return self::tag( 'span', [ 'class' => 'dashicons ' . $class_str, 'data-dashicon' ] );
	}

	/**
	 * Create markup for HTML tag from array fully sanitized and prepared
	 *
	 * @param        $tag_name
	 * @param array  $attributes
	 * @param string $content
	 *
	 * @return string
	 */
	public static function tag( $tag_name, $attributes = array(), $content = '' ) {
		$attr_string = self::build_attributes_string( $attributes );

		return sprintf( '<%1$s %2$s>%3$s</%1$s>', sanitize_key( $tag_name ), $attr_string, $content );
	}

	/**
	 * Built Escaped, Sanitized Attribute String for HTML Tag
	 *
	 * @param $attributes
	 *
	 * @return string
	 */
	public static function build_attributes_string( $attributes ) {
		$string = array();
		foreach ( $attributes as $name => $value ) {
			if ( '' === $value ) {
				$string[] = sprintf( '%s', sanitize_key( $name ) );
			} else {
				$string[] = sprintf( '%s="%s"', sanitize_key( $name ), esc_attr( $value ) );
			}
		}

		return implode( ' ', $string );
	}

	/**
	 * WordPress Admin Notification Markup (can be printed anywhere in the DOM and will be relocated to top of page)
	 *
	 * @param $class_str - the dashicons-* class and any addl
	 *
	 * @return string
	 */
	public static function notification( $class_str ) {
		return self::tag( 'div', [ 'class' => 'dashicons ' . $class_str, 'data-dashicon' ] );
	}
}

/**
 * Helper used by panel for tapping various WordPress APIs
 *
 * Class Get_Single_Field
 * @package WPOP\V_3_0
 */
class Get_Single_Field {
	protected $type;
	protected $key;
	protected $obj_id;
	protected $single;

	/**
	 * Get_Single_Field constructor.
	 *
	 * @param      $panel_id
	 * @param      $type
	 * @param      $key
	 * @param null $default
	 * @param null $obj_id
	 * @param bool $single
	 */
	function __construct( $panel_id, $type, $key, $default = null, $obj_id = null, $single = true ) {
//		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $panel_id ) ) {
//			return false; // check for nonce, only allow panel to use this class
//		}
		$this->type   = $type;
		$this->key    = $key;
		$this->obj_id = $obj_id;
		$this->single = $single;

		return $this->get_data();
	}

	function __toString() {
		return ! empty( $this->get_data() ) ? strval( $this->get_data() ) : '';
	}

	function get_data() {
		switch ( $this->type ) {
			case 'single':
			case 'site':
				return get_option( $this->key, '' );
				break;
			case 'network':
				return get_site_option( $this->key );
				break;
			case 'user-site':
			case 'user':
				return is_multisite() ? get_user_option( $this->key, $this->obj_id )
					: get_user_meta( $this->obj_id, $this->key, $this->single );
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
				return '';
				break;
		}
	}

}

/**
 * Class Save_Single_Field
 * @package WPOP\V_3_0
 */
class Save_Single_Field {
	/**
	 * Save_Single_Field constructor.
	 *
	 * @param      $panel_id
	 * @param      $type
	 * @param      $key
	 * @param      $value
	 * @param null $obj_id
	 * @param bool $autoload
	 */
	function __construct( $panel_id, $type, $key, $value, $obj_id = null, $autoload = true ) {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $panel_id ) ) {
			return false; // check for nonce, only allow panel to use this class
		}
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
				return self::handle_term_meta_save( $obj_id, $key, $value );
				break;
			case 'post':
				return self::handle_post_meta_save( $obj_id, $key, $value );
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
