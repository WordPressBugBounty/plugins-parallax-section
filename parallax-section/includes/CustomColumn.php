<?php
/**
 * Adds a copy-to-clipboard "Shortcode" column to the Parallax Section CPT list table.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PSB_CustomColumn' ) ) {
	class PSB_CustomColumn {
		public function __construct() {
			$pt = PSB_PostType::POST_TYPE;
			add_filter( "manage_{$pt}_posts_columns", [ $this, 'columns' ] );
			add_action( "manage_{$pt}_posts_custom_column", [ $this, 'renderColumn' ], 10, 2 );
		}

		public function columns( $columns ) {
			unset( $columns['date'] );
			$columns['shortcode'] = __( 'Shortcode', 'parallax-section' );
			$columns['date']      = __( 'Date', 'parallax-section' );
			return $columns;
		}

		public function renderColumn( $column_name, $post_id ) {
			if ( 'shortcode' !== $column_name ) {
				return;
			}

			$shortcode = sprintf( '[parallax_section id=%d]', $post_id );
			printf(
				'<div class="bPlAdminShortcode" id="bPlAdminShortcode-%1$s">
					<input value="%2$s" onclick="copyBPlAdminShortcode(\'%3$s\')" readonly>
					<span class="tooltip">%4$s</span>
				</div>',
				esc_attr( $post_id ),
				esc_attr( $shortcode ),
				esc_js( $post_id ),
				esc_html__( 'Copy To Clipboard', 'parallax-section' )
			);
		}
	}
}
