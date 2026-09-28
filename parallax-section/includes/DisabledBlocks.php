<?php
/**
 * Stores and serves the list of disabled blocks (toggled off from the dashboard
 * Blocks page). Block registration in plugin.php honours this list.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PSB_DisabledBlocks' ) ) {
	class PSB_DisabledBlocks {
		const OPTION = 'psbDisabledBlocks';
		const NONCE  = 'psb_disabled_blocks';

		public function __construct() {
			add_action( 'wp_ajax_psb_disabled_blocks', [ $this, 'save' ] );
		}

		/** Current disabled block names, always an array. */
		public static function get() {
			$disabled = get_option( self::OPTION, [] );
			return is_array( $disabled ) ? $disabled : [];
		}

		public function save() {
			$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
				wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'parallax-section' ) ], 403 );
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'parallax-section' ) ], 403 );
			}

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded JSON, each element sanitized below.
			$raw  = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : '';
			$data = is_string( $raw ) ? json_decode( $raw, true ) : null;

			if ( is_array( $data ) ) {
				$data = array_values( array_map( 'sanitize_text_field', $data ) );
				update_option( self::OPTION, $data );
				wp_send_json_success( $data );
			}

			wp_send_json_success( self::get() );
		}
	}
}
