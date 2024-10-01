<?php

/**
 * Plugin Name: Layouts for Divi
 * Plugin URI: https://www.techeshta.com/product/layouts-for-divi/
 * Description: Beautifully designed, Free templates, Hand-crafted for popular Divi page builder.
 * Version: 1.1.1
 * Author: Techeshta
 * Author URI: https://www.techeshta.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain: layouts-for-divi
 * Domain Path: /languages/
 */
/*
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Define variables
 */
define('LFD_FILE', __FILE__);
define('LFD_DIR', plugin_dir_path(LFD_FILE));
define('LFD_URL', plugins_url('/', LFD_FILE));
define('LFD_TEXTDOMAIN', 'layouts-for-divi');

/**
 * Main Plugin Layouts_For_Divi class.
 */
class Layouts_For_Divi {

    /**
     * Layouts_For_Divi constructor.
     *
     * The main plugin actions registered for WordPress
     */
    public function __construct() {
        add_action('init', array($this, 'lfd_check_dependencies'));
        $this->hooks();
        $this->lfd_include_files();
    }

    /**
     * Initialize
     */
    public function hooks() {
        add_action('plugins_loaded', array($this, 'lfd_load_language_files'));
        add_action('admin_enqueue_scripts', array($this, 'lfd_admin_scripts',));
    }

    /**
     * Load files
     */
    public function lfd_include_files() {
        include_once( LFD_DIR . 'includes/class-layout-importer.php' );
        include_once( LFD_DIR . 'includes/api/class-layouts-remote.php' );
    }

    /**
     * @return Loads plugin textdomain
     */
    public function lfd_load_language_files() {
        load_plugin_textdomain('layouts-for-divi', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Check plugin dependencies
     * Check if Divi plugin is installed
     */
    public function lfd_check_dependencies() {

        if (!defined('ET_BUILDER_VERSION')) {
            add_action('admin_notices', array($this, 'lfd_layouts_widget_fail_load'));
            return;
        } else {
            add_action('admin_menu', array($this, 'lfd_menu'));
        }
        $divi_version_required = '2.21.2';
        if (!version_compare(ET_BUILDER_VERSION, $divi_version_required, '>=')) {
            add_action('admin_notices', array($this, 'lfd_layouts_divi_update_notice'));
            return;
        }
    }

    /**
     * This notice will appear if Divi Builder is not installed or activated or both
     */
    public function lfd_layouts_widget_fail_load() {

        $screen = get_current_screen();
        if (isset($screen->parent_file) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id) {
            return;
        }

        $plugin = 'divi-builder/divi-builder.php';
        $file_path = 'divi-builder/divi-builder.php';
        $installed_plugins = get_plugins();

        if (isset($installed_plugins[$file_path])) { // check if plugin is installed
            if (!current_user_can('activate_plugins')) {
                return;
            }
            $activation_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin);

            $message = '<p><strong>' . esc_html__('Layouts for Divi', 'layouts-for-divi') . '</strong>' . esc_html__(' plugin not working because you need to activate the Divi builder plugin.', 'layouts-for-divi') . '</p>';
            $message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s</a>', $activation_url, esc_html__('Activate Divi Now', 'layouts-for-divi')) . '</p>';
        } else {
            if (!current_user_can('install_plugins')) {
                return;
            }

            $buy_now_url = esc_url('https://www.elegantthemes.com');

            $message = '<p><strong>' . esc_html__('Layouts for Divi', 'layouts-for-divi') . '</strong>' . esc_html__(' plugin not working because you need to install the Divi Builder plugin', 'layouts-for-divi') . '</p>';
            $message .= '<p>' . sprintf('<a href="%s" class="button-primary" target="_blank">%s</a>', $buy_now_url, esc_html__('Get Divi', 'layouts-for-divi')) . '</p>';
        }

        echo '<div class="error"><p>' . wp_kses_post($message) . '</p></div>';
    }

    /**
     * Display admin notice for Divi Builder update if Divi Builder version is old
     */
    public function lfd_layouts_divi_update_notice() {
        if (!current_user_can('update_plugins')) {
            return;
        }

        $file_path = 'divi-builder/divi-builder.php';

        $upgrade_link = esc_url('https://www.elegantthemes.com');
        $message = '<p><strong>' . esc_html__('Layouts for Divi', 'layouts-for-divi') . '</strong>' . esc_html__(' plugin not working because you are using an old version of Divi Builder.', 'layouts-for-divi') . '</p>';
        $message .= '<p>' . sprintf('<a href="%s" class="button-primary" target="_blank">%s</a>', $upgrade_link, esc_html__('Get Latest Divi', 'layouts-for-divi')) . '</p>';
        echo '<div class="error">' . wp_kses_post($message) . '</div>';
    }

    /**
     *
     * @return Enqueue admin panel required css/js
     */
    public function lfd_admin_scripts() {
        $screen = get_current_screen();

        wp_register_style('lfd-admin-stylesheets', LFD_URL . 'assets/css/admin.css', array(), 1.0, false);
        wp_register_style('lfd-toastify-stylesheets', LFD_URL . 'assets/css/toastify.css', array(), 1.0, false);
        wp_register_script('lfd-admin-script', LFD_URL . 'assets/js/admin.js', array('jquery'), '1.0.0', true);
        wp_register_script('lfd-toastify-script', LFD_URL . 'assets/js/toastify.js', array('jquery'), '1.0.0', true);
        wp_localize_script('lfd-admin-script', 'js_object', array(
            'lfd_loading' => esc_html__('Importing...', 'layouts-for-divi'),
            'lfd_tem_msg' => esc_html__('Template is successfully imported!.', 'layouts-for-divi'),
            'lfd_msg' => esc_html__('Your page is successfully imported!', 'layouts-for-divi'),
            'lfd_crt_page' => esc_html__('Please Enter Page Name.', 'layouts-for-divi'),
            'lfd_sync' => esc_html__('Syncing...', 'layouts-for-divi'),
            'lfd_sync_suc' => esc_html__('Templates library refreshed', 'layouts-for-divi'),
            'lfd_sync_fai' => esc_html__('Error in library Syncing', 'layouts-for-divi'),
            'lfd_error' => esc_html__('Something went wrong. Please try again.', 'layouts-for-divi'),
            'LFD_URL' => LFD_URL,
            'nonce' => wp_create_nonce('ajax-nonce'),
        ));

        if ((isset($_GET['page']) && ( $_GET['page'] == 'lfd_layouts' || $_GET['page'] == 'lfd_started'))) {
            wp_enqueue_style('lfd-admin-stylesheets');
            wp_enqueue_style('lfd-toastify-stylesheets');
            wp_enqueue_script('lfd-toastify-script');
            wp_enqueue_script('lfd-admin-script');
            wp_enqueue_script('lfd-admin-live-script');
            add_thickbox();
        }
    }

    /**
     *
     * add menu at admin panel
     */
    public function lfd_menu() {
        add_menu_page(esc_html__('Layouts', 'layouts-for-divi'), esc_html__('Layouts', 'layouts-for-divi'), 'administrator', 'lfd_layouts', 'lfd_layouts_function', LFD_URL . 'assets/images/layouts-for-divi.png');

        /**
         *
         * @global type $wp_version
         * @return html Display setting options
         */
        function lfd_layouts_function() {
            include_once( 'includes/layouts.php' );
        }

    }

}

/*
 * Starts our plugin class, easy!
 */
new Layouts_For_Divi();
