<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'ps_fs' ) ) {
  function ps_fs() {
      global $ps_fs;

      if ( ! isset( $ps_fs ) ) {
          require_once PSB_DIR_PATH . '/vendor/freemius-lite/start.php';

          $ps_fs = fs_lite_dynamic_init( array(
              'id'                  => '19833',
              'slug'                => 'parallax-section',
              '__FILE__'            =>  PSB_DIR_PATH .'plugin.php',
              'type'                => 'plugin',
              'public_key'          => 'pk_b230abfa498765ac9fd6a75cdfde2',
              'is_premium'          => false,
              'premium_suffix'      => 'Pro',
              'has_premium_version' => true,
              'has_addons'          => false,
              'has_paid_plans'      => true,
              'is_org_compliant'    => true,
              'trial'               => array(
                  'days'               => 7,
                  'is_require_payment' => true,
              ),
              'menu'                => array(
                  'slug'           => 'parallax-section-dashboard',
                  'first-path'     => 'tools.php?page=parallax-section-dashboard#/welcome',
                  'support'        => false,
                  'parent'         => array(
                      'slug' => 'tools.php',
                  ),
              ),
          ) );
      }

      return $ps_fs;
  }

  // Init Freemius.
  ps_fs();
  do_action( 'ps_fs_loaded' );
}