<?php
/*
 Plugin Name: Latest Post Shortcode
 Plugin URI: http://iuliacazan.ro/latest-post-shortcode/
 Description: This plugin allows you to create a dynamic content selection from your posts, pages and custom post types that can be embedded with a UI configurable shortcode.
 Version: 7.2
 Author: Iulia Cazan
 Author URI: https://profiles.wordpress.org/iulia-cazan
 Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ
 License: GPL2

 Copyright (C) 2015-2017 Iulia Cazan

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License, version 2, as
 published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Define the plugin version.
define( 'LPS_PLUGIN_VERSION', '7.2' );

/**
 * Class for Latest Post Shortcode
 */
class Latest_Post_Shortcode
{
	/**
	 * Class instance.
	 * @var object
	 */
	private static $instance;

	/**
	 * Tile pattern.
	 * @var array
	 */
	var $tile_pattern = array();

	/**
	 * Tile pattern links.
	 * @var array
	 */
	var $tile_pattern_links;

	/**
	 * Tile pattern with no links.
	 * @var array
	 */
	var $tile_pattern_nolinks;

	/**
	 * Get active object instance
	 *
	 * @access public
	 * @static
	 * @return object
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new Latest_Post_Shortcode();
		}
		return self::$instance;
	}

	/**
	 * Class constructor. Includes constants and init methods.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Run action and filter hooks.
	 *
	 * @access private
	 * @return void
	 */
	private function init() {
		// Allow to hook into tile patterns.
		add_action( 'init', array( $this, 'tile_pattern_setup' ), 1 );

		// Text domain load.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Apply the tiles shortcodes.
		add_shortcode( 'latest-selected-content', array( $this, 'latest_selected_content' ) );

		if ( is_admin() ) {
			add_action( 'media_buttons_context', array( $this, 'add_shortcode_button' ) );
			add_action( 'admin_footer', array( $this, 'add_shortcode_popup_container' ) );
			add_action( 'admin_head', array( $this, 'load_admin_assets' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		} else {
			add_action( 'wp_head', array( $this, 'load_assets' ) );
		}

		add_action( 'wp_ajax_nopriv_lps_navigate_to_page', array( $this, 'lps_navigate_callback' ) );
		add_action( 'wp_ajax_lps_navigate_to_page', array( $this, 'lps_navigate_callback' ) );
	}

	/**
	 * Define the tile patterns.
	 * @return void
	 */
	function tile_pattern_setup() {
		$this->tile_pattern = array(
			0  => '[image][title][text][read_more_text]',
			3  => '[a][image][title][text][read_more_text][/a]',
			5  => '[image][title][text][a][read_more_text][/a]',
			1  => '[title][image][text][read_more_text]',
			11 => '[a][title][image][text][read_more_text][/a]',
			13 => '[title][image][text][a][read_more_text][/a]',
			2  => '[title][text][image][read_more_text]',
			14 => '[a][title][text][image][read_more_text][/a]',
			17 => '[title][text][image][a][read_more_text][/a]',
			18 => '[title][text][read_more_text][image]',
			19 => '[a][title][text][read_more_text][image][/a]',
			22 => '[title][text][a][read_more_text][/a][image]',
		);

		// Allow to hook into tile patterns.
		$this->tile_pattern = apply_filters( 'lps_filter_tile_patterns', $this->tile_pattern );

		$this->tile_pattern_links = array();
		$this->tile_pattern_nolinks = array();
		foreach ( $this->tile_pattern as $k => $v ) {
			if ( substr_count( $v, '[a]' ) != 0 ) {
				array_push( $this->tile_pattern_links, $k );
			} else {
				array_push( $this->tile_pattern_nolinks, $k );
			}
		}
	}

	/**
	 * Load text domain for internalization
	 * @return void
	 */
	function load_textdomain() {
		load_plugin_textdomain( 'lps', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
	}

	/**
	 * Load the plugin assets.
	 * @return void
	 */
	function load_assets() {
		wp_enqueue_style(
			'lps-style',
			plugins_url( '/assets/css/style.css', __FILE__ ),
			array(),
			LPS_PLUGIN_VERSION,
			false
		);

		wp_register_script(
			'lps-ajax-pagination-js',
			plugins_url( '/assets/js/custom-pagination.js', __FILE__ ),
			array( 'jquery' ),
			LPS_PLUGIN_VERSION,
			true
		);
		wp_localize_script(
			'lps-ajax-pagination-js',
			'LPS',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);
		wp_enqueue_script( 'lps-ajax-pagination-js' );
	}

	/**
	 * Load the admin assets.
	 * @return void
	 */
	function load_admin_assets() {
		wp_enqueue_style(
			'lps-admin-style',
			plugins_url( '/assets/css/admin-style.css', __FILE__ ),
			array(),
			LPS_PLUGIN_VERSION,
			false
		);

		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script(
			'lps-admin-shortcode-button',
			plugins_url( '/assets/js/custom.js', __FILE__ ),
			array( 'jquery', 'jquery-ui-tabs' ),
			LPS_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Add a button to the content editor, next to the media button, this button will show
	 * a popup that contains inline content.
	 *
	 * @param string $context The context.
	 */
	function add_shortcode_button( $context ) {
		$context .= '<a class="thickbox button" title="' . esc_attr__( 'Content Selection', 'lps' ) . '"
		href="#TB_inline?width=100%25&inlineId=lps_shortcode_popup_container" id="lps_shortcode_button_open"><span class="dashicons dashicons-format-aside"></span> ' . esc_html__( 'Content Selection', 'lps' ) . '</a>';
		echo wp_kses_post( $context );
	}

	/**
	 * Return all private and all public statuses defined.
	 * @return array
	 */
	function get_statuses() {
		global $wp_post_statuses;
		$arr = array(
			'public' => array(),
			'private' => array(),
		);
		if ( ! empty( $wp_post_statuses ) ) {
			foreach ( $wp_post_statuses as $t => $v ) {
				if ( $v->public ) {
					$arr['public'][] = $t;
				} else {
					if ( ! in_array( $t, array( 'auto-draft', '' ), true ) ) {
						$arr['private'][] = $t;
					}
				}
			}
		}
		return $arr;
	}

	/**
	 * The custom patterns start with _custom_.
	 * @param  string $tile_pattern A tile pattern
	 * @return boolean
	 */
	function tile_markup_is_custom( $tile_pattern ) {
		$use_custom_markup = false;
		if ( '_custom_' === substr( $tile_pattern, 1, 8 ) ) {
			$use_custom_markup = true;
		}
		return $use_custom_markup;
	}

	/**
	 * The list of filtered taxonomies
	 * @return array
	 */
	function filtered_taxonomies() {
		$list = array();
		$exclude_tax = array( 'post_tag', 'nav_menu', 'link_category', 'post_format' );
		$tax = get_taxonomies( array(), 'objects' );
		if ( ! empty( $tax ) ) {
			foreach ( $tax as $k => $v ) {
				if ( ! in_array( $k, $exclude_tax ) ) {
					$list[ $k ] = $v;
				}
			}
		}
		return $list;
	}

	/**
	 * Add some content to the bottom of the page.
	 * This will be shown in the inline modal.
	 */
	function add_shortcode_popup_container() {
		$display_posts_list = array(
			'title' => esc_html__( 'Title', 'lps' ),
			'title,excerpt' => esc_html__( 'Title + Post Excerpt', 'lps' ),
			'title,content' => esc_html__( 'Title + Post Content', 'lps' ),
			'title,excerpt-small' => esc_html__( 'Title + Few Chars From The Excerpt', 'lps' ),
			'title,content-small' => esc_html__( 'Title + Few Chars From The Content', 'lps' ),
			'date' => esc_html__( 'Date', 'lps' ),
			'title,date' => esc_html__( 'Title + Date', 'lps' ),
			'title,date,excerpt' => esc_html__( 'Title + Date + Post Excerpt', 'lps' ),
			'title,date,content' => esc_html__( 'Title + Date + Post Content', 'lps' ),
			'title,date,excerpt-small' => esc_html__( 'Title + Date + Few Chars From The Excerpt', 'lps' ),
			'title,date,content-small' => esc_html__( 'Title + Date + Few Chars From The Content', 'lps' ),
			'date,title' => esc_html__( 'Date + Title', 'lps' ),
			'date,title,excerpt' => esc_html__( 'Date + Title + Post Excerpt', 'lps' ),
			'date,title,content' => esc_html__( 'Date + Title + Post Content', 'lps' ),
			'date,title,excerpt-small' => esc_html__( 'Date + Title + Few Chars From The Excerpt', 'lps' ),
			'date,title,content-small' => esc_html__( 'Date + Title + Few Chars From The Content', 'lps' ),
		);
		// Maybe apply extra type.
		$display_posts_list = apply_filters( 'lps_filter_display_posts_list', $display_posts_list );
		?>

		<div id="lps_shortcode_popup_container" style="display:none; width:100%; height:100%">
			
			<table width="100%" cellpadding="0" cellspacing="0" class="lps_shortcode_popup_container_table">
				<tr>
					<td style="height: 48px">
						<h2><?php esc_html_e( 'Create Your Custom Content Selection Shortcode By Combining What You Need', 'lps' ); ?></h2>
					</td>
				</tr>
				<tr>
					<td>

						<div class="lps_tabs">
							<ul>
								<li><a href="#lps-preview"><?php esc_html_e( 'Shortcode Preview', 'lps' ); ?></a></li>
								<li>
									<a class="button button-primary" id="lps_button_embed_shortcode"><?php esc_html_e( 'Embed The Shortcode', 'lps' ); ?></a>
								</li>
							</ul>
							<div id="lps-preview">
								<div id="lps_preview_embed_shortcode">[latest-selected-content type="post" limit="1" tag="news"]</div>
							</div>
						</div>

						<div class="lps_tabs">
							<ul>
								<li><a href="#tabs-1"><?php esc_html_e( 'Type & Filters', 'lps' ); ?></a></li>
								<li><a href="#tabs-2"><?php esc_html_e( 'Limits', 'lps' ); ?></a></li>
								<li><a href="#tabs-3"><?php esc_html_e( 'Output', 'lps' ); ?></a></li>
								<li><a href="#tabs-4"><?php esc_html_e( 'Extra Options', 'lps' ); ?></a></li>
							</ul>
							<div id="tabs-1">
								<table width="100%" cellspacing="0" cellpadding="2">
									<tr>
										<td colspan="4">
											<h3><?php esc_html_e( 'Content Type, Status & Order', 'lps' ); ?></h3><hr>
										</td>
									</tr>
									<tr>
										<td class="lps_title_td"><?php esc_html_e( 'Post Type', 'lps' ); ?></td>
										<td>
											<select name="lps_post_type" id="lps_post_type" onchange="lps_preview_configures_shortcode()">
												<option value=""><?php esc_html_e( 'Any', 'lps' ); ?></option>
												<?php
												$post_types = get_post_types( array(), 'objects' );
												if ( ! empty( $post_types ) ) :
													foreach ( $post_types as $k => $v ) :
														if ( 'revision' !== $k && 'nav_menu_item' !== $k ) : ?>
															<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $k ); ?></option>
														<?php endif;
													endforeach;
												endif; ?>
											</select>
										</td>
										<td class="lps_title_td"><?php esc_html_e( 'Order by', 'lps' ); ?></td>
										<td>
											<select name="lps_orderby" id="lps_orderby" onchange="lps_preview_configures_shortcode()">
												<option value="dateD"><?php esc_html_e( 'Date DESC', 'lps' ); ?></option>
												<option value="dateA"><?php esc_html_e( 'Date ASC', 'lps' ); ?></option>
												<option value="menuA"><?php esc_html_e( 'Menu Order ASC', 'lps' ); ?></option>
												<option value="menuD"><?php esc_html_e( 'Menu Order DESC', 'lps' ); ?></option>
												<option value="titleA"><?php esc_html_e( 'Title ASC', 'lps' ); ?></option>
												<option value="titleD"><?php esc_html_e( 'Title DESC', 'lps' ); ?></option>
												<option value="random"><?php esc_html_e( 'Random*', 'lps' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<td class="lps_title_td"><?php esc_html_e( 'Status', 'lps' ); ?></td>
										<td colspan="3">
											<?php $st = $this->get_statuses();

											foreach ( $st['public'] as $pu ) : ?>
												<label><input type="checkbox" name="lps_status[]" id="lps_status_<?php echo esc_attr( $pu ); ?>" value="<?php echo esc_attr( $pu ); ?>" onclick="lps_preview_configures_shortcode()" class="lps_status" /><b><?php echo esc_html( $pu ); ?></b></label>
											<?php endforeach;

											foreach ( $st['private'] as $pr ) : ?>
												<label><input type="checkbox" name="lps_status[]" id="lps_status_<?php echo esc_attr( $pr ); ?>" value="<?php echo esc_attr( $pr ); ?>" onclick="lps_preview_configures_shortcode()" class="lps_status" /><em><?php echo esc_html( $pr ); ?></em></label>
											<?php endforeach; ?>
										</td>
									</tr>
									<tr>
										<td colspan="4">
											<em><?php esc_html_e( '* Please note that ordering the items by random might present performance risks, please use this careful. Also, using a random order and pagination will output unexpected and potentially redundant content.', 'lps' ); ?></em>
										</td>
									</tr>
									<tr>
										<td colspan="4">
											<h3><?php esc_html_e( 'Filter By Taxonomy', 'lps' ); ?></h3><hr />
										</td>
									</tr>

									<tr>
										<td class="lps_title_td"><?php esc_html_e( 'Taxonomy', 'lps' ); ?></td>
										<td>
											<select name="lps_taxonomy" id="lps_taxonomy" onchange="lps_preview_configures_shortcode()">
												<option value=""><?php esc_html_e( 'Any', 'lps' ); ?></option>
												<?php $tax = $this->filtered_taxonomies();
												if ( ! empty( $tax ) ) :
													foreach ( $tax as $k => $v ) : ?>
														<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v->labels->name ); ?></option>
													<?php endforeach;
												endif; ?>
											</select>
										</td>
										<td class="lps_title_td"><?php esc_html_e( 'Term', 'lps' ); ?></td>
										<td>
											<input type="text" name="lps_term" id="lps_term"  placeholder="<?php esc_attr_e( 'Term slug (ex: news)', 'lps' ); ?>" onchange="lps_preview_configures_shortcode()" />
										</td>
									</tr>
									<tr>
										<td colspan="4">
											<h3><?php esc_html_e( 'Filter By Tag', 'lps' ); ?></h3><hr />
										</td>
									</tr>
									<tr>
										<td class="lps_title_td"><?php esc_html_e( 'Tag', 'lps' ); ?></b></td>
										<td><input type="text" name="lps_tag" id="lps_tag" onchange="lps_preview_configures_shortcode()" /></td>
										<td class="lps_title_td"><?php esc_html_e( 'Dynamic', 'lps' ); ?></td>
										<td>
											<select name="lps_dtag" id="lps_dtag" onchange="lps_preview_configures_shortcode()">
												<option value=""><?php esc_html_e( 'No, use the selected ones', 'lps' ); ?></option>
												<option value="yes"><?php esc_html_e( 'Yes, use the current post tags', 'lps' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<td colspan="4">
											<h3><?php esc_html_e( 'Filter By Specific IDs', 'lps' ); ?></h3><hr />
										</td>
									</tr>
									<tr>
										<td class="lps_title_td" valign="top"><?php esc_html_e( 'Post ID', 'lps' ); ?></td>
										<td>
											<input type="text" name="lps_post_id" id="lps_post_id" onchange="lps_preview_configures_shortcode()" placeholder="<?php esc_attr_e( 'Separate IDs with comma', 'lps' ); ?>" />
											<br><?php esc_attr_e( '(will show only objects with the selected IDs)', 'lps' ); ?>
										</td>
										<td class="lps_title_td" valign="top"><?php esc_html_e( 'Parent ID', 'lps' ); ?></td>
										<td>
											<input type="text" name="lps_parent_id" id="lps_parent_id" onchange="lps_preview_configures_shortcode()" placeholder="<?php esc_attr_e( 'Separate IDs with comma', 'lps' ); ?>" />
											<br><?php esc_attr_e( '(will show only objects with the selected parents)', 'lps' ); ?>
										</td>
									</tr>


									<tr>
										<td colspan="4">
											<h3><?php esc_html_e( 'Exclude Content', 'lps' ); ?></h3><hr />
										</td>
									</tr>
									<tr>
										<td class="lps_title_td" valign="top"><?php esc_html_e( 'Current', 'lps' ); ?></td>
										<td>
											<label><input type="checkbox" name="lps_show_extra_current_id" id="lps_show_extra_current_id" value="current_id" checked="checked" disabled="disabled" readonly="readonly" /> <?php esc_html_e( 'the current post', 'lps' ); ?></label>
										</td>
										<td class="lps_title_td" valign="top"><?php esc_html_e( 'Dynamic', 'lps' ); ?></td>
										<td>
											<label><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_exclude_previous_content" value="exclude_previous_content" onclick="lps_preview_configures_shortcode()" class="lps_show_extra" /> <?php esc_html_e( 'previous shortcodes*', 'lps' ); ?></label>
										</td>
									</tr>
									<tr>
										<td colspan="4">
											<em><?php esc_html_e( '* The exclude content dynamic option will filter the content so that the posts that were already embedded by previous shortcodes on this page will not show up (so that the content does not repeat).', 'lps' ); ?></em>
										</td>
									</tr>

								</table>
							</div>
							<div id="tabs-2">
								<table width="100%" cellspacing="0" cellpadding="2">
									<tr>
										<td colspan="4">
											<h3><?php esc_html_e( 'Content Type, Status & Order', 'lps' ); ?></h3><hr>
										</td>
									</tr>
									<tr>
										<td class="lps_title_td"><?php esc_html_e( 'Number of Posts', 'lps' ); ?></td>
										<td colspan="3">
											<input type="text" name="lps_limit" id="lps_limit" value="1" onchange="lps_preview_configures_shortcode()" size="5" />
										</td>
									</tr>
									<tr>
										<td></td>
										<td><?php esc_html_e( '(if you are using pagination, the number of posts does not apply, it will be overwritten by the number of records per page)', 'lps' ); ?></td>
									</tr>
									<tr>
										<td colspan="4">
											<h3><?php esc_html_e( 'Pagination Visibility & Settings', 'lps' ); ?></h3><hr>
										</td>
									</tr>
									<tr>
										<td class="lps_title_td"><?php esc_html_e( 'Use Pagination', 'lps' ); ?></td>		
										<td colspan="3">
											<select name="lps_use_pagination" id="lps_use_pagination" onchange="lps_preview_configures_shortcode()">
												<option value=""><?php esc_html_e( 'No Pagination', 'lps' ); ?></option>
												<option value="yes"><?php esc_html_e( 'Paginate Results', 'lps' ); ?></option>
											</select>
										</td>
									</tr>
								</table>

								<div id="lps_pagination_options">
									<table width="100%" cellspacing="0" cellpadding="2">
										<tr>
											<td class="lps_title_td"><?php esc_html_e( 'Records Per Page', 'lps' ); ?></td>
											<td>
												<input type="text" name="lps_per_page" id="lps_per_page" value="0" onchange="lps_preview_configures_shortcode()" size="5" />
											</td>
											<td class="lps_title_td"><?php esc_html_e( 'Offset', 'lps' ); ?></td>
											<td>
												<input type="text" name="lps_offset" id="lps_offset" value="0" onchange="lps_preview_configures_shortcode()" size="5" />
											</td>
										</tr>
										<tr>
											<td class="lps_title_td"><?php esc_html_e( 'Visibility', 'lps' ); ?></td>
											<td>
												<select name="lps_showpages" id="lps_showpages" onchange="lps_preview_configures_shortcode()">
													<option value=""><?php esc_html_e( 'Hide Navigation', 'lps' ); ?></option>
													<option value="4"><?php esc_html_e( 'Show Navigation (range of 4)', 'lps' ); ?></option>
													<option value="5"><?php esc_html_e( 'Show Navigation (range of 5)', 'lps' ); ?></option>
													<option value="10"><?php esc_html_e( 'Show Navigation (range of 10)', 'lps' ); ?></option>
												</select>
											</td>
											<td class="lps_title_td"><?php esc_html_e( 'Position', 'lps' ); ?></td>
											<td>
												<select name="lps_showpages_pos" id="lps_showpages_pos" onchange="lps_preview_configures_shortcode()">
													<option value=""><?php esc_html_e( 'Above the results', 'lps' ); ?></option>
													<option value="1"><?php esc_html_e( 'Below the results', 'lps' ); ?></option>
													<option value="2"><?php esc_html_e( 'Above & below the result', 'lps' ); ?></option>
												</select>
											</td>
										</tr>

										<tr>
											<td class="lps_title_td"><?php esc_html_e( 'AJAX Pagination', 'lps' ); ?></td>
											<td>
												<label><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_ajax_pagination" value="ajax_pagination" onclick="lps_preview_configures_shortcode()" class="lps_show_extra" /> <?php esc_html_e( 'yes', 'lps' ); ?></label>
											</td>
										</tr>
									</table>
								</div>
								
							</div>
							<div id="tabs-3">
								<table width="100%" cellspacing="0" cellpadding="2">
									<tr>
										<td colspan="2">
											<h3><?php esc_html_e( 'Tile Appearance', 'lps' ); ?></h3><hr>
										</td>
									</tr>
									<tr>
										<td class="lps_title_td"><?php esc_html_e( 'Display Post', 'lps' ); ?></td>
										<td>
											<select name="lps_display" id="lps_display" onchange="lps_preview_configures_shortcode()">
												<?php foreach ( $display_posts_list as $k => $v ) : ?>
													<?php
													$key = array_keys( $this->tile_pattern, '['. $k . ']' );
													if( ! empty( $key ) ) {
														$key = reset( $key );
													} else {
														$key = '';
													} ?>
													<option value="<?php echo esc_attr( $k ); ?>" data-template-id="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $v ); ?> </option>
												<?php endforeach; ?>
											</select>
											<div id="lps_display_limit">
												<input type="text" name="lps_chrlimit" id="lps_chrlimit" onchange="lps_preview_configures_shortcode()" placeholder="Ex: 120" value="120" size="5" />
												<?php esc_html_e( 'chars from excerpt / content', 'lps' ); ?>
											</div>
										</td>
									</tr>

									<tbody id="lps_url_wrap">
										<tr>
											<td class="lps_title_td"><?php esc_html_e( 'Use Post URL', 'lps' ); ?></td>
											<td>
												<select name="lps_url" id="lps_url" onchange="lps_preview_configures_shortcode()">
													<option value="">No link to the post</option>
													<option value="yes">Link to the post</option>
													<option value="yes_blank">Link to the post (_blank)</option>
												</select>
												<div id="lps_url_options">
													<input type="text" name="lps_linktext" id="lps_linktext" onchange="lps_preview_configures_shortcode()" placeholder="<?php esc_html_e( 'Custom \'Read more\' message', 'lps' ); ?>" size="32" />
													<br>
													<em><?php esc_html_e( 'Do not use brackets for the custom read more message, these are shortcodes delimiters.', 'lps' ); ?></em>
												</div>
											</td>
										</tr>
									</tbody>

									<tbody id="lps_image_wrap">
										<tr>
											<td class="lps_title_td"><?php esc_html_e( 'Use Image', 'lps' ); ?></td>
											<td>
												<select name="lps_image" id="lps_image" onchange="lps_preview_configures_shortcode()">
													<option value=""><?php esc_html_e( 'No Image', 'lps' ); ?></option>
													<?php
													$app_sizes = get_intermediate_image_sizes();
													if ( ! empty( $app_sizes ) ) :
														foreach ( $app_sizes as $s ) : ?>
															<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
														<?php endforeach;
													endif; ?>
													<option value="full"><?php esc_html_e( 'full (original size)', 'lps' ); ?></option>
												</select>
											</td>
										</tr>
									</tbody>

									<tr>
										<td class="lps_title_td"><?php esc_html_e( 'Tile Pattern', 'lps' ); ?></td>
										<td>
											<input type="hidden" name="lps_elements" id="lps_elements" value="0" onchange="lps_preview_configures_shortcode()" />
											<?php
											foreach ( $this->tile_pattern as $k => $p ) :
												$cl = ( in_array( $k, $this->tile_pattern_links, true ) ) ? 'with-link' : 'without-link';
												$cl = ( $this->tile_markup_is_custom( $p ) ) ? ' custom-type' : $cl;
												?>
												<label class="<?php echo esc_attr( $cl ); ?>">
													<?php if ( $this->tile_markup_is_custom( $p ) ) : ?>
														<input type="radio" name="lps_elements_img" id="lps_elements_img_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $k ); ?>" onclick="jQuery('#lps_elements').val('<?php echo esc_attr( $k ); ?>'); lps_preview_configures_shortcode();" readonly="readonly">
														<?php echo esc_html( $display_posts_list[ str_replace( ']', '', str_replace( '[', '', $p ) ) ] ); ?>
													<?php else : ?>
														<img src="<?php echo esc_url( plugins_url( '/assets/images/post_tiles' . esc_attr( $k ) . '.png', __FILE__ ) ); ?>" title="<?php echo esc_attr( $p ); ?>" />
														<input type="radio" name="lps_elements_img" id="lps_elements_img_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $k ); ?>" onclick="jQuery('#lps_elements').val('<?php echo esc_attr( $k ); ?>'); lps_preview_configures_shortcode();">
													<?php endif; ?>
												</label>
											<?php endforeach; ?>
										</td>
									</tr>
									<tbody id="tile_description_wrap">
										<tr>
											<td></td>
											<td><?php esc_html_e( '(order of the html tags and the link - marked with red)', 'lps' ); ?></td>
										</tr>
									</tbody>
									<tbody id="custom_tile_description_wrap">
										<tr>
											<td></td>
											<td><?php esc_html_e( '(you are using a custom output)', 'lps' ); ?></td>
										</tr>
									</tbody>
								</table>
								
							</div>

							<div id="tabs-4">
								<?php
								/** Introduce the slider extension options */
								do_action( 'latest_selected_content_slider_configuration' );
								?>
								<table width="100%" cellspacing="0" cellpadding="2">
									<tr>
										<td>
											<h3><?php esc_html_e( 'Extra Options', 'lps' ); ?></h3><hr>
										</td>
									</tr>
									<tr>
										<td>
											<label><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_author" value="author" onclick="lps_preview_configures_shortcode()" class="lps_show_extra" /> <b><?php esc_html_e( 'Show Author', 'lps' ); ?></b>,</label>
											<?php $tax = $this->filtered_taxonomies();
											if ( ! empty( $tax ) ) :
												foreach ( $tax as $k => $v ) : ?>
													<br><label><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $k ); ?>" onclick="lps_preview_configures_shortcode()" class="lps_show_extra" /> <b><?php esc_html_e( 'Show ', 'lps' ); ?> <?php echo esc_html( $v->labels->name ); ?></b>,</label>

													<?php if ( 'category' === $k ) : ?>
														<label><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_hide_uncategorized_<?php echo esc_attr( $k ); ?>" value="hide_uncategorized_<?php echo esc_attr( $k ); ?>" onclick="lps_preview_configures_shortcode()" class="lps_show_extra" /> <b><?php esc_html_e( 'Do not display Uncategorized term', 'lps' ); ?></b>,</label>
													<?php endif; ?>
												<?php endforeach;
											endif; ?>
											<br><label><input type="checkbox" name="lps_show_extra[]" id="lps_show_extra_tags" value="tags" onclick="lps_preview_configures_shortcode()" class="lps_show_extra" /> <b><?php esc_html_e( 'Show Tags', 'lps' ); ?></b></label>
											<br>
											<em>(<?php esc_html_e( 'please note that if you are using a custom output template defined in your theme, the author, the taxonomies and the tags extra options will not function, since your custom template is overriding the output and the default behavior', 'lps' ); ?>)</em>
										</td>
									</tr>
									<tr>
										<td>
											<h3><?php esc_html_e( 'CSS Class Selector', 'lps' ); ?></h3><hr>
										</td>
									</tr>
									<tr>
										<td>
											<input type="text" name="lps_css" id="lps_css" onchange="lps_preview_configures_shortcode()" placeholder="<?php esc_attr_e( 'Ex: two-columns, three-columns', 'lps' ); ?>" size="32" />
											<br>
											<em>(<?php esc_html_e( 'the CSS class/classes you can use to customize the appearance of the shortcode output.', 'lps' ); ?></em>
										</td>
									</tr>
								</table>
							</div>
						</div>

					</td>
				</tr>
			</table>

		</div>
		<?php
	}

	/**
	 * Get short text of maximum x chars.
	 * @param  string  $text       Text
	 * @param  integer $limit      Limit of chars.
	 * @param  boolean $is_excerpt True if this represents an excerpt.
	 * @return string
	 */
	function get_short_text( $text, $limit, $is_excerpt = false ) {
		$filter = ( $is_excerpt ) ? 'the_excerpt' : 'the_content';
		
		$text = strip_tags( $text );
		$text = preg_replace( '~\[[^\]]+\]~', '', $text );
		$text = strip_shortcodes( $text );
		$text = apply_filters( $filter, strip_shortcodes( $text ) );
		$text = preg_replace( '~\[[^\]]+\]~', '', $text );
		$text = strip_tags( $text );
		$text = preg_replace( '~\[[^\]]+\]~', '', $text );
		/** This is a trick to replace the unicode whitespace :) */
		$text = preg_replace( '/\xA0/u', ' ', $text );
		$text = str_replace( '&nbsp;', ' ', $text );
		$text = preg_replace( '/\s\s+/', ' ', $text );
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );
		if ( ! empty( $text ) ) {
			$content = explode( ' ', $text );
			$len = $i = 0;
			$max = count( $content );
			$text = '';
			while ( $len < $limit ) {
				$text .= $content[$i] . ' ';
				$i ++;
				$len = strlen( $text );
				if ( $i >= $max ) {
					break;
				}
			}
			$text = trim( $text );
			$text = preg_replace( '/\[.+\]/', '', $text );
			$text = apply_filters( $filter, $text );
			$text = str_replace( ']]>', ']]&gt;', $text );
		}
		return $text;
	}

	/**
	 * Return the content generated after an ajax call for the pagination.
	 * @return void
	 */
	function lps_navigate_callback() {
		if ( ! empty( $_POST['args'] ) ) {
			header ('Content-type: text/html; charset=utf-8');
			$_args = $_POST['args'];
			if ( is_array( $_POST['args'] ) ) {
				$args = $_POST['args'];
				foreach ( $args as $key => $value ) {
					$args[ $key ] = sanitize_text_field( $value );
				}
			} else {
				$_args = stripslashes( stripslashes( $_POST['args'] ) );
				$args = ( ! empty( $_args ) ) ? json_decode( $_args ) : false;
			}

			if ( ! empty( $_POST['page'] ) && $args ) {
				$args = (array) $args;
				if ( ! empty( $args['linktext'] ) ) {
					$args['linktext'] = preg_replace( '/u([0-9a-z]{4})+/', '&#x$1;', $args['linktext'] );
				}
				set_query_var( 'page', (int) $_POST['page'] );
				echo $this->latest_selected_content( $args );
			}
		}
		die();
	}

	/**
	 * Return the content generated for plugin pagination with the specific arguments
	 * @param  integer $total        Total of records.
	 * @param  integer $per_page     How many per page.
	 * @param  integer $range        Range size.
	 * @param  string  $shortcode_id Shortcode id (element selector).
	 * @return string
	 */
	function lps_pagination( $total = 1, $per_page = 10, $range = 4, $shortcode_id = '' ) {
		wp_reset_query();
		$body = '';
		$total = intval( $total );
		$per_page = ( ! empty( $per_page ) ) ? intval( $per_page ) : 1;
		$range = abs( intval( $range ) - 1 );
		$range = ( empty( $range ) ) ? 1 : $range;
		$total_pages = ceil( $total / $per_page );
		if ( $total_pages > 1 ) {
			$current_page = get_query_var( 'page' ) ? intval( get_query_var( 'page' ) ) : 1;
			$body .= '
			<ul class="latest-post-selection pages ' . esc_attr( $shortcode_id ) . '">
				<li>' . __( 'Page', 'lps' ) . ' ' . $current_page . ' ' . __( 'of', 'lps' ) . ' ' . $total_pages . '</li>';

			if ( $total_pages > $range && $current_page > 1 ) {
				$body .= '<li><a href="' . get_permalink() . '" data-page="1">&lsaquo;&nbsp;</a></li>';
			}
			if ( $current_page > $range && $current_page > 1 ) {
				$body .= '<li><a href="' . get_pagenum_link( $current_page - 1 ) . '" data-page="' . ( $current_page - 1 ) . '">&laquo;</a></li>';
			}

			$lrang = ceil( ( $current_page % $range ) );
			$start = $current_page - $lrang;
			$start = ( $start <= 1 ) ? 1 : $start;
			$end = $start + $range;

			if ( $end >= $total_pages ) {
				$end = $total_pages;
				$start = $end - $range;
				$start = ( $start <= 1 ) ? 1 : $start;
			}

			for ( $i = $start; $i <= $end; $i ++ ) {
				if ( 1 == $i ) {
					$body .= '<li><a href="' . get_permalink() . '" data-page="1">' . $i . '</a></li>';
				} else {
					if ( $current_page == $i ) {
						$body .= '<li class="current"><a data-page="' . $i . '">' . $i . '</a></li>';
					} else {
						$body .= '<li><a href="' . get_pagenum_link( $i ) . '" data-page="' . $i . '">' . $i . '</a></li>';
					}
				}
			}

			if ( $current_page < $total_pages ) {
				$body .= '<li><a href="' . get_pagenum_link( $current_page + 1 ) . '" data-page="' . ( $current_page + 1 ) . '">&raquo;</a></li>';
			}
			if ( $current_page < $total_pages - 1 && $current_page + $range - 1 < $total_pages && $current_page < $total_pages ) {
				$body .= '<li><a href="' . get_pagenum_link( $total_pages ) . '" data-page="' . $total_pages . '">&nbsp;&rsaquo;</a></li>';
			}
			$body .= '</ul>';

			if ( get_site_url() . '/' == get_permalink() ) {
				/** We must use /page/x */
				if ( ! empty( $current_page ) && substr_count( $body, '/page/' . $current_page . '/' ) != 0 ) {
					$body = str_replace( '/page/' . $current_page . '/', '/', $body );
				}
			} else {
				/** We must use /x */
				$body = str_replace( '/' . $current_page . '/page/', '/', $body );
				$body = str_replace( '/page/', '/', $body );
			}
		}

		return $body;
	}

	/**
	 * Return the content generated by a shortcode with the specific arguments.
	 * @param  array $args Array of shortcode arguments.
	 * @return string
	 */
	function latest_selected_content( $args ) {
		global $post, $lps_current_post_embedded_item_ids;

		if ( empty( $lps_current_post_embedded_item_ids ) ) {
			$lps_current_post_embedded_item_ids = array();
		}

		$lps_current_post_embedded_item_ids = apply_filters( 'lps_filter_exclude_previous_content_ids', $lps_current_post_embedded_item_ids );

		// Get the post arguments from shortcode arguments.
		$ids = ( ! empty( $args['id'] ) ) ? explode( ',', $args['id'] ) : array();
		$parent = ( ! empty( $args['parent'] ) ) ? explode( ',', $args['parent'] ) : array();
		$type = ( ! empty( $args['type'] ) ) ? $args['type'] : 'post';
		$chrlimit = ( ! empty( $args['chrlimit'] ) ) ? intval( $args['chrlimit'] ) : 120;

		$extra_display = ( ! empty( $args['display'] ) ) ? explode( ',', $args['display'] ) : array( 'title' );
		$linkurl = ( ! empty( $args['url'] ) && ( 'yes' == $args['url'] || 'yes_blank' == $args['url'] ) ) ? true : false;
		$tile_type = 0;
		if ( $linkurl ) {
			$linktext = ( ! empty( $args['linktext'] ) ) ? $args['linktext'] : '';
		}
		$tile_type = ( ! empty( $args['elements'] ) && ! empty( $this->tile_pattern[$args['elements']] ) ) ? $args['elements'] : 0;
		$tile_pattern = ( ! empty( $this->tile_pattern[ $tile_type ] ) ) ? $this->tile_pattern[ $tile_type ] : 'title';
		$read_more_class = ( ! in_array( $tile_type, array( 3, 11, 14, 19 ) ) ) ? ' class="read-more"' : ' class="read-more-wrap"';
		$show_extra = ( ! empty( $args['show_extra'] ) ) ? explode( ',', $args['show_extra'] ) : array();

		$qargs = array();
		$qargs['numberposts'] = 1;
		$qargs['post_status'] = 'publish';
		if ( ! empty( $args['status'] ) ) {
			$qargs['post_status'] = explode( ',', trim( $args['status'] ) );
			if ( in_array( 'private', $qargs['post_status'] ) ) {
				if ( ! is_user_logged_in() ) {
					if ( ( $pkey = array_search( 'private', $qargs['post_status'] ) ) !== false ) {
						unset( $qargs['post_status'][ $pkey ] );
					}
				}
			}
		}
		if ( empty( $qargs['post_status'] ) ) {
			return;
		}
		$orderby = ( ! empty( $args['orderby'] ) ) ? $args['orderby'] : 'dateD';
		$qargs['order'] = 'DESC';
		$qargs['orderby'] = 'date_publish';

		switch ( $orderby ) {
			case 'dateA' :
				$qargs['order'] = 'ASC';
				$qargs['orderby'] = 'date_publish';
				break;
			case 'menuA' :
				$qargs['order'] = 'ASC';
				$qargs['orderby'] = 'menu_order';
				break;
			case 'menuD' :
				$qargs['order'] = 'DESC';
				$qargs['orderby'] = 'menu_order';
				break;
			case 'titleA' :
				$qargs['order'] = 'ASC';
				$qargs['orderby'] = 'post_title';
				break;
			case 'titleD' :
				$qargs['order'] = 'DESC';
				$qargs['orderby'] = 'post_title';
				break;
			case 'random' :
				$qargs['order'] = 'DESC';
				$qargs['orderby'] = 'rand';
				break;
			default :
				break;
		}

		// Make sure we do not loop in the current page.
		if ( ! empty( $post->ID ) ) {
			$qargs['post__not_in'] = array( $post->ID );
		}

		if ( ! empty( $show_extra ) && in_array( 'exclude_previous_content', $show_extra ) ) {
			// Exclude the previous ID embedded through the plugin shortcodes on this page.
			$qargs['post__not_in'] = array_merge( $qargs['post__not_in'], $lps_current_post_embedded_item_ids );
		}

		if ( ! empty( $args['limit'] ) ) {
			$qargs['numberposts'] = ( ! empty( $args['limit'] ) ) ? intval( $args['limit'] ) : 1;
		}
		if ( ! empty( $args['perpage'] ) ) {
			$qargs['posts_per_page'] = ( ! empty( $args['perpage'] ) ) ? intval( $args['perpage'] ) : 0;
			$paged = get_query_var( 'page' ) ? abs( intval( get_query_var( 'page' ) ) ) : 1;
			$current_page = $paged;
			$qargs['paged'] = $paged;
			$qargs['page'] = $current_page;
		}
		if ( ! empty( $args['offset'] ) ) {
			$qargs['offset'] = ( ! empty( $args['offset'] ) ) ? intval( $args['offset'] ) : 0;
			if ( ! empty( $qargs['paged'] ) ) {
				$qargs['offset'] = abs( $current_page - 1 ) * $args['offset'];
			}
		}

		$force_type = true;
		if ( ! empty( $ids ) && is_array( $ids ) ) {
			foreach ( $ids as $k => $v ) {
				$ids[$k] = intval( $v );
			}
			$qargs['post__in'] = $ids;
			$force_type = false;
		}
		if ( $force_type ) {
			$qargs['post_type'] = $type;
		} else {
			if ( ! empty( $args['type'] ) ) {
				$qargs['post_type'] = $args['type'];
			}
		}
		if ( ! empty( $parent ) ) {
			$qargs['post_parent__in'] = $parent;
		}
		$qargs['tax_query'] = array();
		if ( ! empty( $args['tag'] ) ) {
			array_push(
				$qargs['tax_query'],
				array(
					'taxonomy' => 'post_tag',
					'field' => 'slug',
					'terms' => ( ! empty( $args['tag'] ) ) ? $args['tag'] : 'homenews',
				)
			);
		}
		if ( ! empty( $args['dtag'] ) ) {
			$tag_ids = wp_get_post_tags( $post->ID, array( 'fields' => 'ids' ) );
			if ( ! empty( $tag_ids ) && is_array( $tag_ids ) ) {
				if ( ! empty( $qargs['tax_query'] ) ) {
					array_push(
						$qargs['tax_query'],
						array(
							'relation' => 'AND',
						)
					);
				}
				array_push(
					$qargs['tax_query'],
					array(
						'taxonomy' => 'post_tag',
						'field' => 'term_id',
						'terms' => $tag_ids,
						'operator' => 'IN',
					)
				);
			}
		}
		if ( ! empty( $args['taxonomy'] ) && ! empty( $args['term'] ) ) {
			if ( ! empty( $qargs['tax_query'] ) ) {
				array_push(
					$qargs['tax_query'],
					array(
						'relation' => 'AND',
					)
				);
			}
			array_push(
				$qargs['tax_query'],
				array(
					'taxonomy' => $args['taxonomy'],
					'field' => 'slug',
					'terms' => $args['term'],
				)
			);
		}
		if ( ! empty( $args['exclude_tags'] ) ) {
			if ( ! empty( $qargs['tax_query'] ) ) {
				array_push(
					$qargs['tax_query'],
					array(
						'relation' => 'AND',
					)
				);
			}
			array_push(
				$qargs['tax_query'],
				array(
					'taxonomy' => 'post_tag',
					'field' => 'slug',
					'terms' => explode( ',', $args['exclude_tags'] ),
					'operator' => 'NOT IN',
				)
			);
		}

		$qargs['suppress_filters'] = false;
		$posts = get_posts( $qargs );

		// If the slider extension is enabled and the shortcode is configured to output the slider, let's do that and return.
		if ( ! empty( $posts ) && class_exists( 'Latest_Post_Shortcode_Slider' ) 
			&& ! empty( $args['output'] ) && 'slider' == $args['output'] ) {
			ob_start();
			do_action( 'latest_selected_content_slider', $posts, $args );
			wp_reset_postdata();
			return ob_get_clean();
		}

		$is_lps_ajax = get_query_var( 'lps_ajax' ) ? intval( get_query_var( 'lps_ajax' ) ) : 0;
		$shortcode_id = 'lps-' . md5( serialize( $args ) . microtime() );

		ob_start();
		$forced_end = '';
		if ( ! empty( $qargs['posts_per_page'] ) && ! empty( $args['showpages'] ) ) {
			$counter = new WP_Query( $qargs );
			$found_posts = ( ! empty( $counter->found_posts ) ) ? $counter->found_posts : 0;
			$pagination_html = $this->lps_pagination( intval( $found_posts ), ( ! empty( $qargs['posts_per_page'] ) ) ? $qargs['posts_per_page'] : 1, intval( $args['showpages'] ), $shortcode_id );

			if ( in_array( 'ajax_pagination', $show_extra ) && ! $is_lps_ajax && ! empty( $args ) && is_array( $args ) ) {
				if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
					echo '<div id="' . esc_attr( $shortcode_id ) . '-wrap" data-args="' . esc_js( json_encode( $args ) ) . '">';
				} else {
					echo '<div id="' . esc_attr( $shortcode_id ) . '-wrap" data-args="' . esc_js( json_encode( $args, JSON_UNESCAPED_UNICODE ) ) . '">';
				}
			} else {
				echo '<div id="' . esc_attr( $shortcode_id ) . '-wrap">';
				$forced_end = '</div>';
			}

			if ( empty( $args['pagespos'] ) || ( ! empty( $args['pagespos'] ) && 2 == $args['pagespos'] ) ) {
				echo $pagination_html;
			}
		}
		if ( ! empty( $posts ) ) {

			if ( in_array( 'date', $extra_display ) ) {
				$date_format = get_option( 'date_format' ) . ' \<\i\>' . get_option( 'time_format' ) . '\<\/\i\>';
			}

			$class = ( ! empty( $args['css'] ) ) ? ' ' . $args['css'] : '';
			if ( in_array( 'ajax_pagination', $show_extra ) ) {
				$class .= ' ajax_pagination';
			}

			$use_custom_markup = false;
			if ( '_custom_' === substr( $tile_pattern, 1, 8 ) ) {
				$use_custom_markup = true;
			}

			if ( $use_custom_markup ) {
				$start = apply_filters( 'lps_filter_use_custom_section_markup_start', $tile_pattern, $shortcode_id, $class );
				if ( ! substr_count( $start, esc_attr( $shortcode_id ) ) ) {
					$start = '<div id="' . esc_attr( $shortcode_id ) . '" class="' . trim( esc_attr( $class ) ) . '">' . $start;
					$forced_end .= '</div>';
				}
				echo $start;
			} else {
				echo '<section class="latest-post-selection' . esc_attr( $class ) . '" id="' . esc_attr( $shortcode_id ) . '">';
				if ( ! empty( $args['image'] ) ) {
					$iw = get_option( esc_attr( $args['image'] ) . '_size_w', true );
					$iw = ( 1 == $iw ) ? '100%' : $iw . 'px';
					$ih = get_option( esc_attr( $args['image'] ) . '_size_h', true );
					$ih = ( 1 == $ih ) ? '100%' : $ih . 'px';
					echo '<style>#' . esc_attr( $shortcode_id ) . ' .custom-' . esc_attr( $args['image'] ) .' { width:' . $iw . '; min-height:' . $ih . '; height:auto;}</style>';
				}
			}
			foreach ( $posts as $post ) {
				// Collect the IDs for the current page from the shortcode results.
				array_push( $lps_current_post_embedded_item_ids, $post->ID );

				$tile = $tile_pattern;

				if ( $use_custom_markup ) {
					echo apply_filters( 'lps_filter_use_custom_tile_markup', $tile_pattern, $post );
				} else {
					$a_start = $a_end = '';
					if ( $linkurl ) {
						$link_target = ( 'yes_blank' == $args['url'] ) ? ' target="_blank"' : '';
						$a_start = '<a href="' . get_permalink( $post->ID ) . '"' . $read_more_class . $link_target . '>';
						$a_end = '</a>';
					}
					$tile = str_replace( '[a]', $a_start, $tile );
					$tile = str_replace( '[/a]', $a_end, $tile );
					if ( ! empty( $args['image'] ) ) {
						$img_html = apply_filters( 'post_thumbnail_html', '', $post->ID, 0, $args['image'], array( 'class' => 'custom-' . $args['image'] ) );
						$th_id = get_post_thumbnail_id( intval( $post->ID ) );
						$image = wp_get_attachment_image_src( $th_id, $args['image'] );
						if ( ! empty( $image[0] ) ) {
							$img_html = '<img src="' . esc_url( $image[0] ) . '" />';
							$img_html = apply_filters( 'post_thumbnail_html', $img_html, $post->ID, $th_id, $args['image'], array() );
						}
						if ( ! empty( $img_html ) ) {
							$tile = str_replace( '[image]', $img_html, $tile );
						} else {
							$tile = str_replace( '[image]', '', $tile );
						}
					} else {
						$tile = str_replace( '[image]', '', $tile );
					}

					if ( in_array( 'date', $extra_display ) ) {
						if ( in_array( 'title', $extra_display ) ) {
							if ( ! empty( $args['display'] ) && substr_count( $args['display'], 'date,title' ) ) {
								$tile = str_replace( '[title]', '[date][title]', $tile );
							} else {
								$tile = str_replace( '[title]', '[title][date]', $tile );
							}
						} else {
							$tile = str_replace( '[title]', '[date]', $tile );
						}
					}
					if ( in_array( 'date', $extra_display ) ) {
						$tile = str_replace( '[date]', '<em>' . date_i18n( $date_format, strtotime( $post->post_date ), true ) . '</em>', $tile );
					} else {
						$tile = str_replace( '[date]', '', $tile );
					}

					if ( ! empty( $show_extra ) ) {
						if ( ! empty( $a_end ) ) {
							$tile = str_replace( $a_end, $a_end . '[extra]', $tile );
						} else {
							$tile = str_replace( '[text]', '[text][extra]', $tile );
						}

						if ( in_array( 'tags', $show_extra ) ) {
							$tags = apply_filters( 'the_tags', get_the_term_list( $post->ID, 'post_tag', '<div class="lps-terms tags">', ', ', '</div>' ), '<div class="lps-terms tags">', ', ', '</div>', $post->ID );
							if ( ! empty( $tags ) ) {
								$tags = '<span class="lps-tags-wrap">' . $tags . '</span>';
								$tile = str_replace( '[extra]', '[extra]' . $tags, $tile );
							}
						}

						$taxonomies = array_diff( $show_extra, array( 'tags', 'author', 'ajax_pagination', 'hide_uncategorized_category' ) );
						if ( ! empty( $taxonomies ) ) {
							foreach ( $taxonomies as $tax ) {
								$terms = '';
								$tax_obj = get_taxonomy( $tax );
								if ( ! empty( $tax_obj ) ) {
									$terms_list = get_the_term_list( $post->ID, $tax, '<span class="lps-terms ' . esc_attr( $tax ) . '">', ', ', '</span>' );
									if ( 'category' === $tax && in_array( 'hide_uncategorized_category', $show_extra ) ) {
										if ( substr_count( $terms_list, 'uncategorized' ) ) {
											$terms_list = '';
										}
									}
									if ( ! empty( $terms_list ) ) {
										$terms = '<div class="lps-taxonomy-wrap ' . esc_attr( $tax ) . '"><span class="lps-taxonomy ' . esc_attr( $tax ) . '">' . esc_html( $tax_obj->label ) . ':</span> ' . $terms_list . '</div>';
									}
								}
								if ( ! empty( $terms ) ) {
									$tile = str_replace( '[extra]', '[extra]' . $terms, $tile );
								}
							}
						}

						if ( in_array( 'author', $show_extra ) ) {
							$author =  '<div class="lps-author-wrap"><span class="lps-author">' . esc_html__( 'By', 'lps' ) . '</span> <a href="' . esc_url( get_author_posts_url( $post->post_author ) ) . '" class="lps-author-link">' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) ) . '</a></div>';
							$tile = str_replace( '[extra]', '[extra]' . $author, $tile );
						}

						$tile = str_replace( '[extra]', '', $tile );
					}
					

					if ( in_array( 'title', $extra_display ) ) {
						$tile = str_replace( '[title]', '<h1>' . esc_html( $post->post_title ) . '</h1>', $tile );
					} else {
						$tile = str_replace( '[title]', '', $tile );
					}
					$text = '';
					if ( in_array( 'excerpt', $extra_display ) || in_array( 'content', $extra_display ) || in_array( 'content-small', $extra_display ) || in_array( 'excerpt-small', $extra_display ) ) {
						if ( in_array( 'excerpt', $extra_display ) ) {
							$text = apply_filters( 'the_excerpt', strip_shortcodes( $post->post_excerpt ) );
						} elseif ( in_array( 'excerpt-small', $extra_display ) ) {
							$text = $this->get_short_text( $post->post_excerpt, $chrlimit, true );
						} else if ( in_array( 'content', $extra_display ) ) {
							$text = apply_filters( 'the_content', $post->post_content );
						} elseif ( in_array( 'content-small', $extra_display ) ) {
							$text = $this->get_short_text( $post->post_content, $chrlimit, false );
						}
					}
					$tile = str_replace( '[text]', $text, $tile );
					if ( ! empty( $linktext ) ) {
						$tile = str_replace( '[read_more_text]', $linktext, $tile );
					} else {
						$tile = str_replace( '[read_more_text]', '', $tile );
					}

					echo '<article>' . $tile . '<div class="clear"></div></article>';
				}
			}

			if ( $use_custom_markup ) {
				echo apply_filters( 'lps_filter_use_custom_section_markup_end', $tile_pattern, $shortcode_id, $class );
				if ( ! empty( $forced_end ) ) {
					echo $forced_end;
				}
			} else {
				echo '</section>';
			}
		}
		if ( ! empty( $qargs['posts_per_page'] ) && ! empty( $args['showpages'] ) ) {
			if ( ! empty( $args['pagespos'] ) && ( 1 == $args['pagespos'] || 2 == $args['pagespos'] ) ) {
				echo $pagination_html;
			}
			if ( in_array( 'ajax_pagination', $show_extra ) && ! $is_lps_ajax && ! empty( $args ) && is_array( $args ) ) {
				echo '</div>';
			}
		}
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Plugin action link.
	 * @param  array $links Plugin links.
	 * @return array
	 */
	function plugin_action_links( $links ) {
		$all = array();
		$all[] = '<a href="http://iuliacazan.ro/latest-post-shortcode">Plugin URL</a>';
		$all = array_merge( $all, $links );
		return $all;
	}
}

Latest_Post_Shortcode::get_instance();

/** Allow the text widget to render the Latest Post Shortcode */
add_filter( 'widget_text', 'do_shortcode', 11 );
