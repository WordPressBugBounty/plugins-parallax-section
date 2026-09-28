<?php
/**
 * Enqueues the admin assets for the Parallax Section CPT edit/list screens
 * (the copy-to-clipboard shortcode helper + its styles).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PSB_Enqueue' ) ) {
	class PSB_Enqueue {
		public function __construct() {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueuePromo' ] );
		}

		/**
		 * Shortcode editor sidebar panels (parallax-section post type): the Pro
		 * "Section Block" picker on licensed sites, and the Pro showcase for users
		 * who can manage plugins.
		 */
		public function enqueuePromo() {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! $screen || PSB_PostType::POST_TYPE !== $screen->post_type ) {
				return;
			}
			if ( ! current_user_can( 'edit_posts' ) ) {
				return;
			}
			// Always shown here. Free sites get the upsell (→ pricing); licensed sites
			// get a "your Pro blocks" showcase instead (→ the dashboard's blocks page),
			// since the pricing route doesn't exist for them.
			$is_pro = function_exists( 'psIsPremium' ) && psIsPremium();
			if ( ! apply_filters( 'psb_show_pro_promo', true, $is_pro ) ) {
				return;
			}
			$dashboard = admin_url( 'edit.php?post_type=' . PSB_PostType::POST_TYPE . '&page=parallax-section-dashboard' );

			$asset = $this->asset( 'admin-promo' );
			wp_enqueue_script( 'psb-admin-promo', PSB_DIR_URL . 'build/admin-promo.js', $asset['dependencies'], $asset['version'], true );
			wp_enqueue_style( 'psb-admin-promo', PSB_DIR_URL . 'build/admin-promo.css', [], $asset['version'] );
			wp_set_script_translations( 'psb-admin-promo', 'parallax-section', PSB_DIR_PATH . 'languages' );
			wp_add_inline_script(
				'psb-admin-promo',
				'window.psbPromo = ' . wp_json_encode( [
					'isPro'     => (bool) $is_pro,
					// The showcase / upsell is for people who can buy + install plugins;
					// the Pro "Section Block" picker is for anyone editing the section.
					'showPromo' => current_user_can( 'activate_plugins' ),
					'pricing' => esc_url_raw( $dashboard . '#/pricing' ),
					'blocks'  => esc_url_raw( $dashboard . '#/blocks' ),
				] ) . ';',
				'before'
			);
		}

		public function enqueue( $hook ) {
			global $typenow;
			if ( PSB_PostType::POST_TYPE !== $typenow ) {
				return;
			}

			$asset = $this->asset( 'admin-post' );
			wp_enqueue_script( 'psb-admin-post', PSB_DIR_URL . 'build/admin-post.js', $asset['dependencies'], $asset['version'], true );
			wp_enqueue_style( 'psb-admin-post', PSB_DIR_URL . 'build/admin-post.css', [], $asset['version'] );
			wp_set_script_translations( 'psb-admin-post', 'parallax-section', PSB_DIR_PATH . 'languages' );
		}

		/**
		 * Read a webpack-generated *.asset.php so script dependencies and the
		 * content-hash version stay in sync with the actual bundle.
		 */
		private function asset( $name ) {
			$file = PSB_DIR_PATH . 'build/' . $name . '.asset.php';
			return file_exists( $file ) ? require $file : [ 'dependencies' => [], 'version' => PSB_VERSION ];
		}
	}
}
