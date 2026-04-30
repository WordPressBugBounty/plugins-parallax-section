<?php
/**
 * Plugin Name: Parallax Section - Block
 * Description: Makes background element scrolls slower than foreground content.
 * Version: 2.0.4
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
define( 'PSB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '2.0.4' );
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

		}

		function defaultTitle( $title, $post ) {
			if ( 'page' === $post->post_type && isset( $_GET['title'] ) ) {
				return sanitize_text_field( wp_unslash( $_GET['title'] ) );
			}
			return $title; 
		}

		function defaultContent( $content, $post ) {
			if ( 'page' === $post->post_type && isset( $_GET['content'] ) ) {
				return wp_unslash( $_GET['content'] );
			}
			return $content;
		}

		function enqueueBlockEditorAssets() {
			wp_add_inline_script( 'psb-parallax-editor-script', 'const psbpipecheck = ' . wp_json_encode(psIsPremium()).';', 'before');
		}

		function onInit(){
			register_block_type( __DIR__ . '/build' );
		}
	}
	new PSBPlugin();
}

}

require_once PSB_DIR_PATH . '/includes/Menu.php';



