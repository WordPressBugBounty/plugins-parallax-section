<?php
/**
 * Plugin Name: Parallax Section Block – Add Parallax Scrolling Effects to Sections
 * Description: Makes background element scrolls slower than foreground content.
 * Version: 2.1.1
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: parallax-section
   */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'ps_fs' ) ) {
	// register_activation_hook(__FILE__, function () {
	// 'swiper-slider/swiper-slider.php' ---> ai line er prothom ta slug r porer ta php file er name
	// 	if (is_plugin_active('parallax-section/plugin.php')) {
	// 	  deactivate_plugins('parallax-section/plugin.php');
	// 	}
	// 	if (is_plugin_active('parallax-section-pro/plugin.php')) {
	// 	  deactivate_plugins('parallax-section-pro/plugin.php');
	// 	}
	//   });
	
	ps_fs()->set_basename( false, __FILE__ );
	
} else {
define( 'PSB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '2.1.1' );
define( 'PSB_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'PSB_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'PARALLAX_HAS_PRO', file_exists( dirname(__FILE__) . '/vendor/freemius/start.php' ) );

if ( PARALLAX_HAS_PRO ) {
	require_once PSB_DIR_PATH . 'includes/fs.php';
}else{
	require_once PSB_DIR_PATH . 'includes/fs-lite.php';
}

if (PARALLAX_HAS_PRO) {
	require_once PSB_DIR_PATH . 'includes/LicenseActivation.php';
}
	
function psIsPremium(){
	return PARALLAX_HAS_PRO ? ps_fs()->can_use_premium_code() : false;
}

	// ... Your plugin's main file logic ...
require_once PSB_DIR_PATH . 'includes/GetCSS.php';

if( !class_exists( 'PSBPlugin' ) ){
	class PSBPlugin{
		function __construct(){
			add_action( 'init', [ $this, 'onInit' ] );
			add_action('enqueue_block_editor_assets', [$this, "enqueueBlockEditorAssets"]);
			add_filter( 'default_title', [$this, 'defaultTitle'], 10, 2 );
			add_filter( 'default_content', [$this, 'defaultContent'], 10, 2 );
			add_filter( 'block_categories_all', [ $this, 'registerBlockCategory' ], 10, 2 );
			add_action( 'wp_enqueue_scripts', [ $this, 'pricingUrlForAdmins' ] );

		}

		/**
		 * Register the "Parallax Sections" block category so every psb/* block
		 * groups under it in the editor inserter. Each block points at this via
		 * its block.json `"category": "parallax-sections"`.
		 */
		function registerBlockCategory( $categories, $context ) {
			foreach ( $categories as $cat ) {
				if ( isset( $cat['slug'] ) && 'parallax-sections' === $cat['slug'] ) {
					return $categories; // already registered
				}
			}
			return array_merge(
				[
					[
						'slug'  => 'parallax-sections',
						'title' => __( 'Parallax Sections', 'parallax-section' ),
						'icon'  => null,
					],
				],
				$categories
			);
		}

		// "Create page" link from the dashboard (post-new.php?post_type=page&title=…&content=…&nonce=…).
		function isCreatePageRequest( $post ) {
			if ( 'page' !== $post->post_type || ! isset( $_GET['nonce'] ) ) {
				return false;
			}
			return (bool) wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'psbCreatePage' );
		}

		function defaultTitle( $title, $post ) {
			if ( $this->isCreatePageRequest( $post ) && isset( $_GET['title'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified in isCreatePageRequest()
				return sanitize_text_field( wp_unslash( $_GET['title'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			return $title;
		}

		function defaultContent( $content, $post ) {
			if ( $this->isCreatePageRequest( $post ) && isset( $_GET['content'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified in isCreatePageRequest()
				return wp_kses_post( wp_unslash( $_GET['content'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			return $content;
		}

		// Pro-lock modal "Upgrade now": admins go to the dashboard pricing page,
		// everyone else to the public one (the default in ViewUpdateModal.js).
		function pricingUrlForAdmins() {
			if ( current_user_can( 'manage_options' ) ) {
				$url = admin_url( 'edit.php?post_type=parallax-section&page=parallax-section-dashboard#/pricing' );
				wp_add_inline_script( 'psb-parallax-view-script', 'window.psbPricingUrl = ' . wp_json_encode( $url ) . ';', 'before' );
			}
		}

		function enqueueBlockEditorAssets() {
			wp_add_inline_script( 'psb-parallax-editor-script', 'const psbpipecheck = ' . wp_json_encode(psIsPremium()).';', 'before');
		}

		function onInit(){
			$disabled = class_exists( 'PSB_DisabledBlocks' ) ? PSB_DisabledBlocks::get() : [];

			// Core block — always available (marked "required" in the dashboard).
			register_block_type( __DIR__ . '/build' );

			// Pro-only blocks: registered (and therefore available in the
			// inserter) only for licensed users and only when not toggled off
			// from the dashboard Blocks page. Each slug maps 1:1 to its
			// build/blocks/<slug> directory and its psb/<slug> block name.
			//
			// To add a new Pro block: append its build/blocks/<slug> directory
			// name to this array. Do NOT write a new if-block.
			$psb_pro_blocks = [
				'tilt-card', 'parallax-testimonials', 'parallax-cta', 'parallax-cube', 'parallax-skills',
			];

			// Required child blocks: parent-restricted sub-blocks that another block
			// depends on. They are ALWAYS registered for Pro users (never toggleable
			// from the dashboard Blocks page) so disabling them can't break their
			// parent. None at the moment.
			$psb_required_children = [];

			if ( psIsPremium() ) {
				foreach ( $psb_pro_blocks as $slug ) {
					$is_required_child = in_array( $slug, $psb_required_children, true );
					if ( $is_required_child || ! in_array( 'psb/' . $slug, $disabled, true ) ) {
						register_block_type( __DIR__ . '/build/blocks/' . $slug );
					}
				}
			}
		}
	}
	new PSBPlugin();
}

// "Help & Demos" link beside Deactivate on the Plugins screen → the plugin dashboard.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
	if ( current_user_can( 'manage_options' ) ) {
		$links[] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=parallax-section&page=parallax-section-dashboard#/welcome' ) ) . '" style="color:#FF7A00;font-weight:bold;">' . esc_html__( 'Help & Demos', 'parallax-section' ) . '</a>';
	}
	return $links;
} );

// Shortcode generator: a locked single-block CPT that outputs a reusable
// [parallax_section id=N] shortcode (mirrors the Video Player Block architecture).
require_once PSB_DIR_PATH . 'includes/PostType.php';
require_once PSB_DIR_PATH . 'includes/ShortCode.php';
require_once PSB_DIR_PATH . 'includes/CustomColumn.php';
require_once PSB_DIR_PATH . 'includes/Enqueue.php';
require_once PSB_DIR_PATH . 'includes/DisabledBlocks.php';

new PSB_PostType();
new PSB_ShortCode();
new PSB_CustomColumn();
new PSB_Enqueue();
new PSB_DisabledBlocks();

}

require_once PSB_DIR_PATH . '/includes/Menu.php';



