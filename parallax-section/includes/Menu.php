<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

class Menu {
	public $isUserPremium;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
		// Freemius (fresh Pro install, license not yet activated) empties this CPT's
		// submenu at priority 999999999, which makes WordPress deny the dashboard —
		// the very page that holds license activation. Put it back afterwards.
		add_action( 'admin_menu', [ $this, 'restoreDashboardMenu' ], PHP_INT_MAX );
		add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
		$this->isUserPremium = psIsPremium();
	}

	public function adminMenu() {
		// Always nest the dashboard as a "Help & Demos" submenu under the
		// Parallax Section CPT menu, for all users.
		add_submenu_page(
			'edit.php?post_type=' . PSB_PostType::POST_TYPE,
			__('Help & Demos - bPlugins', 'parallax-section'),
			__('Help & Demos', 'parallax-section'),
			'manage_options',
			'parallax-section-dashboard',
			[$this, 'renderDashboardPage']
		);
	}


	public function restoreDashboardMenu() {
		global $submenu, $menu;
		$has = function ( $parent ) use ( &$submenu ) {
			foreach ( isset( $submenu[ $parent ] ) ? $submenu[ $parent ] : [] as $item ) {
				if ( isset( $item[2] ) && 'parallax-section-dashboard' === $item[2] ) {
					return true;
				}
			}
			return false;
		};

		// Keeps the page reachable at edit.php?post_type=parallax-section&page=parallax-section-dashboard.
		$cpt = 'edit.php?post_type=' . PSB_PostType::POST_TYPE;
		if ( ! $has( $cpt ) ) {
			$this->adminMenu();
		}

		// Freemius also swaps the top-level item to its own page slug, which hides
		// the CPT submenu — so show "Help & Demos" under that item as well.
		foreach ( (array) $menu as $item ) {
			$slug = isset( $item[2] ) ? $item[2] : '';
			if ( 'parallax-section' === $slug && ! $has( $slug ) ) {
				add_submenu_page( $slug, __( 'Help & Demos - bPlugins', 'parallax-section' ), __( 'Help & Demos', 'parallax-section' ), 'manage_options', 'parallax-section-dashboard', [ $this, 'renderDashboardPage' ] );
			}
		}
	}

	public function renderDashboardPage(){ ?>
		<div
			id='psbDashboard'
			data-info='<?php echo esc_attr( wp_json_encode( [
				'version' => PSB_VERSION,
				'isPremium' => psIsPremium(),
				'hasPro' => PARALLAX_HAS_PRO,
				'licenseActiveNonce' => wp_create_nonce('bplLicenseActive'),
				'nonce' => wp_create_nonce( 'psbCreatePage' ),
				'adminUrl' => admin_url(),
				'disabledBlocks' => class_exists( 'PSB_DisabledBlocks' ) ? PSB_DisabledBlocks::get() : [],
				'disabledBlocksNonce' => wp_create_nonce( 'psb_disabled_blocks' ),
			] ) ); ?>'
		></div>
	<?php }

	function adminEnqueueScripts( $hook ) {
		if( false !== strpos( $hook, 'parallax-section' ) ){
			// Build hash as version so browsers never keep a stale dashboard after an update.
			$asset = file_exists( PSB_DIR_PATH . 'build/admin-dashboard.asset.php' ) ? include PSB_DIR_PATH . 'build/admin-dashboard.asset.php' : [];
			$ver   = isset( $asset['version'] ) ? $asset['version'] : PSB_VERSION;
			wp_enqueue_style( 'psb-admin-dashboard', PSB_DIR_URL . 'build/admin-dashboard.css', [], $ver );
			wp_enqueue_script( 'psb-admin-dashboard', PSB_DIR_URL . 'build/admin-dashboard.js', [ 'react', 'react-dom', 'wp-util' ], $ver, true );
			wp_set_script_translations( 'psb-admin-dashboard', 'parallax-section', PSB_DIR_PATH . 'languages' );
		}
	}
}
new Menu();
