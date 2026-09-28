<?php
/**
 * [parallax_section id=N] shortcode — renders a saved Parallax Section CPT entry anywhere.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PSB_ShortCode' ) ) {
	class PSB_ShortCode {
		public function __construct() {
			add_shortcode( 'parallax_section', [ $this, 'render' ] );
		}

		public function render( $atts ) {
			$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'parallax_section' );
			$pid  = absint( $atts['id'] );
			if ( ! $pid ) {
				return '';
			}

			$post = get_post( $pid );
			if ( ! $post || PSB_PostType::POST_TYPE !== $post->post_type ) {
				return '';
			}
			if ( post_password_required( $post ) ) {
				return get_the_password_form( $post );
			}

			$can_view = false;
			switch ( $post->post_status ) {
				case 'publish':
					$can_view = true;
					break;
				case 'private':
					$can_view = current_user_can( 'read_private_posts' );
					break;
				case 'draft':
				case 'pending':
				case 'future':
					$can_view = current_user_can( 'edit_post', $pid );
					break;
			}
			if ( ! $can_view ) {
				return '';
			}

			// The one section block — the free Parallax Section or a Pro section
			// (skip any whitespace-only "freeform" entries parse_blocks may add).
			foreach ( parse_blocks( $post->post_content ) as $block ) {
				if ( ! empty( $block['blockName'] ) ) {
					return render_block( $block );
				}
			}
			return '';
		}
	}
}
