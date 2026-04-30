<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( !function_exists( 'ps_fs' ) ) {
    // Create a helper function for easy SDK access.
    function ps_fs() {
        global $ps_fs;
        if ( !isset( $ps_fs ) ) {
            // Include Freemius SDK.
            require_once PSB_DIR_PATH . '/vendor/freemius/start.php';
            $ps_fs = fs_dynamic_init( array(
                'id'               => '19833',
                'slug'             => 'parallax-section',
                'type'             => 'plugin',
                'public_key'       => 'pk_b230abfa498765ac9fd6a75cdfde2',
                'is_premium'       => false,
                'premium_suffix'   => 'Pro',
                'has_addons'       => false,
                'has_paid_plans'   => true,
                'is_org_compliant' => true,
                'trial'            => array(
                    'days'               => 7,
                    'is_require_payment' => true,
                ),
                'menu'             => array(
                    'slug'       => 'parallax-section-dashboard',
                    'first-path' => 'tools.php?page=parallax-section-dashboard#/welcome',
                    'support'    => false,
                    'parent'     => array(
                        'slug' => 'tools.php',
                    ),
                ),
                'is_live'          => true,
            ) );
        }
        return $ps_fs;
    }

    // Init Freemius.
    ps_fs();
    // Signal that SDK was initiated.
    do_action( 'ps_fs_loaded' );
}