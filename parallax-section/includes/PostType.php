<?php
/**
 * Registers the "Parallax Section" custom post type used as a shortcode generator.
 *
 * Each post is a single, locked section block — `psb/parallax`, or on Pro sites
 * any Pro section chosen in the "Section Block" panel. Saving the post yields a
 * reusable shortcode ([parallax_section id=N]) that renders the block anywhere —
 * pages, widgets, classic editor, page builders, etc. Mirrors the multi-block
 * shortcode-generator architecture used by Video Player Block.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PSB_PostType' ) ) {
	class PSB_PostType {
		const POST_TYPE = 'parallax-section';

		public function __construct() {
			add_action( 'init', [ $this, 'registerPostType' ] );
			add_filter( 'block_editor_settings_all', [ $this, 'lockTemplate' ], 10, 2 );
		}

		public function registerPostType() {
			register_post_type( self::POST_TYPE, [
				'label'               => __( 'Parallax Section', 'parallax-section' ),
				'labels'              => [
					'name'          => __( 'Parallax Sections', 'parallax-section' ),
					'singular_name' => __( 'Parallax Section', 'parallax-section' ),
					'add_new_item'  => __( 'Add New Parallax Section', 'parallax-section' ),
					'edit_item'     => __( 'Edit Parallax Section', 'parallax-section' ),
					'new_item'      => __( 'New Parallax Section', 'parallax-section' ),
					'all_items'     => __( 'All Parallax Sections', 'parallax-section' ),
					'menu_name'     => __( 'Parallax Section', 'parallax-section' ),
				],
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'menu_icon'           => 'dashicons-images-alt2',
				'menu_position'       => 25,
				'supports'            => [ 'title', 'editor', 'custom-fields' ],
				'template'            => [ [ 'psb/parallax' ] ],
				'template_lock'       => 'all',
			] );
		}

		/**
		 * Keep this CPT to exactly one section block.
		 *
		 * Free: 'all' — the single Parallax Section can't be removed, moved or
		 * surrounded by other blocks.
		 * Pro: 'insert' — still no extra blocks, but the "Section Block" picker
		 * (src/admin/promo/SectionPicker.js) may swap the one block for any Pro
		 * section. 'all' would make WordPress flag a swapped block as "doesn't
		 * match the template"; 'insert' skips that check.
		 */
		public function lockTemplate( $settings, $context ) {
			if ( ! empty( $context->post ) && self::POST_TYPE === $context->post->post_type ) {
				$settings['templateLock'] = ( function_exists( 'psIsPremium' ) && psIsPremium() ) ? 'insert' : 'all';
			}
			return $settings;
		}
	}
}
