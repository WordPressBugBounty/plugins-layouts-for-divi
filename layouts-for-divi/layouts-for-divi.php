<?php
/**
 * Plugin Name: Layouts for Divi
 * Plugin URI: https://www.techeshta.com/product/layouts-for-divi/
 * Description: 30+ Free Layouts for Divi Templates. One-click import. No coding skills required.
 * Version: 2.0
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * Tested up to: 7.1
 * Author: Techeshta
 * Author URI: https://www.techeshta.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: layouts-for-divi
 * Domain Path: /languages/
 *
 * @package Layouts_For_Divi
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Plugin constants.
 */
define( 'LFD_FILE', __FILE__ );
define( 'LFD_DIR', plugin_dir_path( LFD_FILE ) );
define( 'LFD_URL', plugins_url( '/', LFD_FILE ) );
define( 'LFD_TEXTDOMAIN', 'layouts-for-divi' );

/**
 * Main plugin class.
 *
 * Registers the "Layouts" admin screen, its assets, and the Divi dependency
 * notices. The remote API client and the importer live in includes/.
 */
class Layouts_For_Divi {

	/**
	 * Plugin version, used for asset cache-busting. Keep in sync with the
	 * "Version" header above and readme.txt's "Stable tag".
	 *
	 * @var string
	 */
	const VERSION = '2.0';

	/**
	 * Minimum Divi Builder version this plugin supports.
	 *
	 * @var string
	 */
	const MINIMUM_DIVI_VERSION = '2.21.2';

	/**
	 * Nonce action shared by the plugin's AJAX requests.
	 *
	 * Plugin-specific on purpose: the sibling Layouts for Elementor plugin
	 * uses the generic 'ajax-nonce' action.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'lfd-ajax-nonce';

	/**
	 * Layouts_For_Divi constructor.
	 *
	 * Registers the main plugin actions with WordPress.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'lfd_check_dependencies' ) );
		$this->hooks();
		$this->lfd_include_files();
	}

	/**
	 * Register hooks that do not depend on Divi being active.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'lfd_admin_scripts' ) );
	}

	/**
	 * Load the remote API client and the importer.
	 *
	 * @return void
	 */
	public function lfd_include_files() {
		require_once LFD_DIR . 'includes/api/class-layouts-remote.php';
		require_once LFD_DIR . 'includes/class-layout-importer.php';
	}

	/**
	 * Check that the Divi Builder (theme or plugin) is active and recent enough.
	 *
	 * Adds the admin menu when it is, otherwise shows an admin notice.
	 *
	 * @return void
	 */
	public function lfd_check_dependencies() {

		if ( ! defined( 'ET_BUILDER_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'lfd_layouts_widget_fail_load' ) );
			return;
		}

		if ( ! version_compare( ET_BUILDER_VERSION, self::MINIMUM_DIVI_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'lfd_layouts_divi_update_notice' ) );
			return;
		}

		add_action( 'admin_menu', array( $this, 'lfd_menu' ) );
	}

	/**
	 * Admin notice shown when the Divi Builder is not installed and/or not active.
	 *
	 * @return void
	 */
	public function lfd_layouts_widget_fail_load() {

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin            = 'divi-builder/divi-builder.php';
		$installed_plugins = get_plugins();

		if ( isset( $installed_plugins[ $plugin ] ) ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			$activation_url = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $plugin ) . '&plugin_status=all&paged=1' ), 'activate-plugin_' . $plugin );

			$message  = '<p><strong>' . esc_html__( 'Layouts for Divi', 'layouts-for-divi' ) . '</strong>' . esc_html__( ' plugin not working because you need to activate the Divi builder plugin.', 'layouts-for-divi' ) . '</p>';
			$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', esc_url( $activation_url ), esc_html__( 'Activate Divi Now', 'layouts-for-divi' ) ) . '</p>';
		} else {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}

			$message  = '<p><strong>' . esc_html__( 'Layouts for Divi', 'layouts-for-divi' ) . '</strong>' . esc_html__( ' plugin not working because you need to install the Divi Builder plugin', 'layouts-for-divi' ) . '</p>';
			$message .= '<p>' . sprintf( '<a href="%s" class="button-primary" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( 'https://www.elegantthemes.com' ), esc_html__( 'Get Divi', 'layouts-for-divi' ) ) . '</p>';
		}

		echo '<div class="notice notice-error">' . wp_kses_post( $message ) . '</div>';
	}

	/**
	 * Admin notice shown when the active Divi Builder is older than MINIMUM_DIVI_VERSION.
	 *
	 * @return void
	 */
	public function lfd_layouts_divi_update_notice() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$message  = '<p><strong>' . esc_html__( 'Layouts for Divi', 'layouts-for-divi' ) . '</strong>' . esc_html__( ' plugin not working because you are using an old version of Divi Builder.', 'layouts-for-divi' ) . '</p>';
		$message .= '<p>' . sprintf( '<a href="%s" class="button-primary" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( 'https://www.elegantthemes.com' ), esc_html__( 'Get Latest Divi', 'layouts-for-divi' ) ) . '</p>';
		echo '<div class="notice notice-error">' . wp_kses_post( $message ) . '</div>';
	}

	/**
	 * Register admin CSS/JS, and enqueue them on the plugin's own screen only.
	 *
	 * @return void
	 */
	public function lfd_admin_scripts() {
		wp_register_style( 'lfd-admin-stylesheets', LFD_URL . 'assets/css/admin.css', array(), self::VERSION, 'all' );
		wp_register_style( 'lfd-toastify-stylesheets', LFD_URL . 'assets/css/toastify.css', array(), self::VERSION, 'all' );
		wp_register_script( 'lfd-admin-script', LFD_URL . 'assets/js/admin.js', array( 'jquery' ), self::VERSION, true );
		wp_register_script( 'lfd-toastify-script', LFD_URL . 'assets/js/toastify.js', array( 'jquery' ), self::VERSION, true );
		wp_localize_script(
			'lfd-admin-script',
			'lfd_js_object',
			array(
				'lfd_loading'  => esc_html__( 'Importing...', 'layouts-for-divi' ),
				'lfd_tem_msg'  => esc_html__( 'Template is successfully imported!.', 'layouts-for-divi' ),
				'lfd_msg'      => esc_html__( 'Your page is successfully imported!', 'layouts-for-divi' ),
				'lfd_crt_page' => esc_html__( 'Please Enter Page Name.', 'layouts-for-divi' ),
				'lfd_sync'     => esc_html__( 'Syncing...', 'layouts-for-divi' ),
				'lfd_sync_suc' => esc_html__( 'Templates library refreshed', 'layouts-for-divi' ),
				'lfd_sync_fai' => esc_html__( 'Error in library Syncing', 'layouts-for-divi' ),
				'lfd_error'    => esc_html__( 'Something went wrong. Please try again.', 'layouts-for-divi' ),
				'LFD_URL'      => LFD_URL,
				'nonce'        => wp_create_nonce( self::NONCE_ACTION ),
			)
		);

		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only used to decide whether to enqueue assets.
		if ( 'lfd_layouts' === $page ) {
			wp_enqueue_style( 'lfd-admin-stylesheets' );
			wp_enqueue_style( 'lfd-toastify-stylesheets' );
			wp_enqueue_script( 'lfd-toastify-script' );
			wp_enqueue_script( 'lfd-admin-script' );
			add_thickbox();
		}
	}

	/**
	 * Add the "Layouts" top-level admin menu.
	 *
	 * @return void
	 */
	public function lfd_menu() {
		add_menu_page(
			esc_html__( 'Layouts', 'layouts-for-divi' ),
			esc_html__( 'Layouts', 'layouts-for-divi' ),
			'manage_options',
			'lfd_layouts',
			array( $this, 'lfd_layouts_page' ),
			LFD_URL . 'assets/images/layouts-for-divi.png'
		);
	}

	/**
	 * Render the "Layouts" admin page.
	 *
	 * @return void
	 */
	public function lfd_layouts_page() {
		include_once LFD_DIR . 'includes/layouts.php';
	}
}

/*
 * Start the plugin.
 */
new Layouts_For_Divi();
