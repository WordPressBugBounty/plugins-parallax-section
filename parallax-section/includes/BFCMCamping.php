<?php

namespace PSB\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( __NAMESPACE__ . '\PSB_PromoBanner' ) ) {

    class PSB_PromoBanner {
        const environment     = 'production'; // 'development' or 'production'
        const ENVIRONMENT     = self::environment;
        const DISMISSED_KEY   = 'psb_promo_banner_dismissed_permanent';
        const EXPIRATION_DATE = '2026-12-05 23:59:59';

        /** Target landing URL */
        const COUPON_URL      = 'https://bplugins.com/products/parallax-section/pricing/?utm_source=admin&utm_medium=dashboard&utm_campaign=bfcm2026';

        private static $instance = null;
        private static $styles_rendered = false;
        private static $scripts_rendered = false;
        private static $banner_rendered = false;

        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct() {
            if ( $this->is_development() ) {
                add_action( 'init', function() {
                    if ( get_current_user_id() && isset( $_GET['reset_promo'] ) ) {
                        delete_user_meta( get_current_user_id(), self::DISMISSED_KEY );
                    }
                } );
            }
            add_action( 'admin_notices',                     [ $this, 'render_admin_notices' ] );
            add_action( 'admin_head',                        [ $this, 'enqueue_styles' ] );
            add_action( 'admin_footer',                      [ $this, 'enqueue_scripts' ] );
            add_action( 'wp_ajax_psb_dismiss_promo_banner',  [ $this, 'ajax_dismiss' ] );
        }

        /**
         * Static helper to render directly on dashboard page
         */
        public static function render_banner(): void {
            self::instance()->render();
        }

        /**
         * Render banner via admin_notices on "All Parallax Sections" and "Account" pages.
         * The "Help & Demo" dashboard page is handled directly by Menu.php::renderDashboardPage().
         */
        public function render_admin_notices(): void {
            if ( isset( $_GET['page'] ) && 'parallax-section-dashboard' === $_GET['page'] ) {
                return;
            }
            $this->render();
        }

        /* ------------------------------------------------------------------ */
        /*  Screen, Expiration, Dismissal & Pro Helpers                       */
        /* ------------------------------------------------------------------ */

        /**
         * Check if the current admin screen is one of the target Parallax Section screens:
         * - All Parallax Sections (edit.php?post_type=parallax-section)
         * - Help & Demo (parallax-section-dashboard)
         * - Account (parallax-section-account)
         * Excludes: "Add New" and post editors.
         */
        private function is_target_screen(): bool {
            global $pagenow;
            // 1. Explicitly exclude post creation and post editing
            if ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) {
                return false;
            }
            if ( function_exists( 'get_current_screen' ) ) {
                $screen = get_current_screen();
                if ( $screen && 'post' === $screen->base ) {
                    return false;
                }
            }

            // 2. Check for "All Parallax Sections" list table
            if ( isset( $_GET['post_type'] ) && 'parallax-section' === $_GET['post_type'] && ! isset( $_GET['page'] ) ) {
                return true;
            }

            // 3. Check for "Help & Demo"
            if ( isset( $_GET['page'] ) && 'parallax-section-dashboard' === $_GET['page'] ) {
                return true;
            }

            // 4. Check for Freemius "Account"
            if ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'account' ) !== false ) {
                if ( ( isset( $_GET['post_type'] ) && 'parallax-section' === $_GET['post_type'] ) || strpos( $_GET['page'], 'parallax-section' ) !== false ) {
                    return true;
                }
            }

            // 5. Fallback via $screen object
            if ( function_exists( 'get_current_screen' ) ) {
                $screen = get_current_screen();
                if ( $screen ) {
                    if ( 'edit' === $screen->base && 'parallax-section' === $screen->post_type ) {
                        return true;
                    }
                    $id = $screen->id ?? '';
                    if ( strpos( $id, 'parallax-section-dashboard' ) !== false || strpos( $id, 'parallax-section-account' ) !== false ) {
                        return true;
                    }
                }
            }

            return false;
        }

        /**
         * Check if current date is past December 5, 2026
         */
        private function is_expired(): bool {
            $expiry_timestamp = strtotime( self::EXPIRATION_DATE );
            $current_time     = time();
            return ( $current_time > $expiry_timestamp );
        }

        /**
         * Check if current environment is development
         */
        public static function is_development(): bool {
            $env = strtolower( trim( (string) self::environment ) );
            return in_array( $env, [ 'development', 'developmet', 'dev' ], true );
        }

        /**
         * Check if current user has permanently dismissed the banner
         */
        private function is_dismissed(): bool {
            // In development, never consider it permanently dismissed
            if ( $this->is_development() ) {
                return false;
            }

            if ( isset( $_GET['reset_promo'] ) ) {
                delete_user_meta( get_current_user_id(), self::DISMISSED_KEY );
                return false;
            }
            return (bool) get_user_meta( get_current_user_id(), self::DISMISSED_KEY, true );
        }

        /**
         * Determine if the banner should be omitted
         */
        private function should_skip(): bool {
            // Must be one of the target screens (All Parallax Sections, Help & Demo, Account)
            if ( ! $this->is_target_screen() ) {
                return true;
            }

            // In development environment, reload will always make it reappear (never skip)
            if ( $this->is_development() ) {
                return false;
            }

            // Production checks:
            // 1. Automatically remove after Dec 5, 2026
            if ( $this->is_expired() ) {
                return true;
            }

            // 2. Never display again once dismissed by the user
            if ( $this->is_dismissed() ) {
                return true;
            }

            // 3. Hide for Pro / Premium users
            if ( function_exists( 'psIsPremium' ) && psIsPremium() ) {
                return true;
            }

            return false;
        }

        /* ------------------------------------------------------------------ */
        /*  CSS Styles                                                        */
        /* ------------------------------------------------------------------ */

        public function enqueue_styles(): void {
            if ( self::$styles_rendered || $this->should_skip() ) { return; }
            self::$styles_rendered = true;
            ?>
            <style id="psb-campaign-card-css">
            #psb-campaign-card {
                --bpl-primary:        #831e91ff;
                --bpl-primary-hover:  #913eaaff;
                --bpl-secondary:      #878c99ff;
                --bpl-dark:           #070127;
                --bpl-text:           #1b2e4b;
                --bpl-muted:          #475569;

                background: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                border-left: 1px solid #cbd5e1 !important;
                border-radius: 8px !important;
                box-shadow: 0 3px 12px rgba(7, 1, 39, 0.05) !important;
                display: flex !important;
                position: relative !important;
                top: auto !important;
                left: auto !important;
                width: auto !important;
                height: auto !important;
                opacity: 1 !important;
                visibility: visible !important;
                z-index: 99 !important;
                align-items: stretch;
                min-height: 84px;
                overflow: hidden;
                margin: 16px 20px 16px 0 !important;
                padding: 0 !important;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                box-sizing: border-box;
            }

            /* Left Brand Ribbon Block */
            #psb-campaign-card .psb-side-brand {
                width: 140px;
                min-width: 140px;
                background: linear-gradient(135deg, var(--bpl-primary) 0%, var(--bpl-dark) 100%);
                color: #ffffff;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 12px 10px;
                gap: 6px;
                flex-shrink: 0;
            }
            #psb-campaign-card .psb-side-brand img {
                width: 38px;
                height: 38px;
                object-fit: contain;
                filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.35));
            }
            #psb-campaign-card .psb-side-brand .psb-side-title {
                font-size: 10.5px;
                font-weight: 800;
                letter-spacing: .7px;
                text-transform: uppercase;
                color: #ffffff;
                text-align: center;
                line-height: 1.2;
            }

            /* Main Content Zone */
            #psb-campaign-card .psb-main-content {
                flex: 1;
                padding: 14px 22px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                flex-wrap: wrap;
                padding-right: 38px;
            }

            /* Left Text Stack */
            #psb-campaign-card .psb-copy-group {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            #psb-campaign-card .psb-title-line {
                font-size: 14px;
                font-weight: 700;
                color: var(--bpl-dark);
                display: flex;
                align-items: center;
                gap: 8px;
                margin: 0;
                line-height: 1.3;
            }
            #psb-campaign-card .psb-offer-tag {
                background: #ffedd5;
                color: #c2410c;
                border: 1px solid #fed7aa;
                font-size: 11px;
                font-weight: 700;
                padding: 2px 8px;
                border-radius: 12px;
                letter-spacing: .2px;
            }

            /* Feature Checklist */
            #psb-campaign-card .psb-features-row {
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 12px;
                color: var(--bpl-muted);
                flex-wrap: wrap;
            }
            #psb-campaign-card .psb-features-row span {
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            #psb-campaign-card .psb-features-row .check-icon {
                color: #10b981;
                font-weight: 800;
            }

            /* Action Button */
            #psb-campaign-card .psb-action-link {
                background: var(--bpl-primary);
                color: #ffffff !important;
                padding: 9px 20px;
                border-radius: 5px;
                font-size: 12.5px;
                font-weight: 600;
                text-decoration: none !important;
                box-shadow: 0 2px 8px rgba(139, 21, 143, 0.32);
                transition: background-color .15s ease, transform .15s ease, box-shadow .15s ease;
                white-space: nowrap;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            #psb-campaign-card .psb-action-link:hover {
                background: var(--bpl-primary-hover);
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(193, 112, 224, 0.7);
            }

            /* Close Button (×) */
            #psb-campaign-card .psb-close-trigger {
                position: absolute;
                top: 8px;
                right: 8px;
                background: none;
                border: none;
                cursor: pointer;
                color: #94a3b8;
                font-size: 20px;
                line-height: 1;
                padding: 6px;
                transition: color .15s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10;
            }
            #psb-campaign-card .psb-close-trigger:hover {
                color: var(--bpl-dark);
            }

            /* Responsive */
            @media (max-width: 960px) {
                #psb-campaign-card .psb-side-brand {
                    width: 110px;
                    min-width: 110px;
                }
            }
            @media (max-width: 782px) {
                #psb-campaign-card {
                    flex-direction: column;
                    align-items: stretch;
                }
                #psb-campaign-card .psb-side-brand {
                    width: 100%;
                    flex-direction: row;
                    justify-content: flex-start;
                    padding: 8px 16px;
                }
                #psb-campaign-card .psb-main-content {
                    flex-direction: column;
                    align-items: flex-start;
                    padding: 14px 16px;
                }
            }
            </style>
            <?php
        }

        /* ------------------------------------------------------------------ */
        /*  Dismiss Script (AJAX + Animation)                                 */
        /* ------------------------------------------------------------------ */

        public function enqueue_scripts(): void {
            if ( self::$scripts_rendered || $this->should_skip() ) { return; }
            self::$scripts_rendered = true;
            $dismiss_nonce = wp_create_nonce( 'psb_dismiss_promo' );
            $ajax_url      = admin_url( 'admin-ajax.php' );
            ?>
            <script id="psb-campaign-card-js">
            (function () {
                var btn = document.getElementById('psb-card-close-btn');
                if (!btn) return;
                btn.addEventListener('click', function(e) {
                    // Only respond to trusted user clicks, ignore extension/synthetic triggers
                    if (e && !e.isTrusted) return;
                    e.preventDefault();
                    var card = document.getElementById('psb-campaign-card');
                    if (card) {
                        card.style.transition = 'opacity .25s ease, transform .25s ease';
                        card.style.opacity    = '0';
                        card.style.transform  = 'translateY(-6px)';
                        setTimeout(function() { card.remove(); }, 260);
                    }
                    var fd = new FormData();
                    fd.append('action', 'psb_dismiss_promo_banner');
                    fd.append('nonce',  '<?php echo esc_js( $dismiss_nonce ); ?>');
                    fetch('<?php echo esc_url( $ajax_url ); ?>', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: fd
                    });
                });
            })();
            </script>
            <?php
        }

        /* ------------------------------------------------------------------ */
        /*  Render Single Banner                                              */
        /* ------------------------------------------------------------------ */

        public function render(): void {
            if ( self::$banner_rendered || $this->should_skip() ) { return; }
            self::$banner_rendered = true;

            $this->enqueue_styles();

            $coupon_url = esc_url( self::COUPON_URL );
            $icon_url   = esc_url( 'https://ps.w.org/parallax-section/assets/icon-128x128.png' );
            ?>
            <div id="psb-campaign-card">

                <!-- Left Brand Ribbon Block -->
                <div class="psb-side-brand">
                    <img src="<?php echo $icon_url; ?>" alt="Parallax Section" />
                    <span class="psb-side-title">Parallax Section</span>
                </div>

                <!-- Main Content Zone -->
                <div class="psb-main-content">
                    <div class="psb-copy-group">
                        <div class="psb-title-line">
                            <span><?php esc_html_e( 'Upgrade to Parallax Section Pro', 'parallax-section' ); ?></span>
                            <span class="psb-offer-tag"><?php esc_html_e( 'Save up to 45%', 'parallax-section' ); ?></span>
                        </div>
                        <div class="psb-features-row">
                            <span><strong class="check-icon">✓</strong> <?php esc_html_e( '5 Pro Parallax Section effects', 'parallax-section' ); ?></span>
                            <span><strong class="check-icon">✓</strong> <?php esc_html_e( 'One-click style presets for every Pro block', 'parallax-section' ); ?></span>
                            <span><strong class="check-icon">✓</strong> <?php esc_html_e( 'All Pro Blocks', 'parallax-section' ); ?></span>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <a href="<?php echo $coupon_url; ?>" target="_blank" rel="noopener noreferrer" class="psb-action-link">
                        <?php esc_html_e( 'Unlock PRO Features →', 'parallax-section' ); ?>
                    </a>
                </div>

                <!-- Dismiss Button -->
                <button type="button"
                        id="psb-card-close-btn"
                        class="psb-close-trigger"
                        title="<?php esc_attr_e( 'Close', 'parallax-section' ); ?>"
                        aria-label="<?php esc_attr_e( 'Close', 'parallax-section' ); ?>">
                    &times;
                </button>

            </div>
            <?php
            $this->enqueue_scripts();
        }

        /* ------------------------------------------------------------------ */
        /*  AJAX Handler: Permanent Dismissal                                 */
        /* ------------------------------------------------------------------ */

        public function ajax_dismiss(): void {
            check_ajax_referer( 'psb_dismiss_promo', 'nonce' );
            // In production, permanently dismiss for this user across all pages
            if ( ! $this->is_development() ) {
                update_user_meta( get_current_user_id(), self::DISMISSED_KEY, 1 );
            }
            wp_send_json_success();
        }
    }

    PSB_PromoBanner::instance();
}
