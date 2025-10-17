<?php
/**
 * Plugin Name: EMDR Therapist Finder
 * Description: A plugin to find EMDR therapists with a public search page, map, and list.
 * Version: 1.0.0
 * Author: Aslan Madaev
 * License: GPL2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'EMDR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EMDR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files.
require_once EMDR_PLUGIN_DIR . 'includes/class-activator.php';
require_once EMDR_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once EMDR_PLUGIN_DIR . 'includes/class-db.php';
require_once EMDR_PLUGIN_DIR . 'includes/shortcodes.php';
require_once EMDR_PLUGIN_DIR . 'includes/rest/routes.php';
require_once EMDR_PLUGIN_DIR . 'includes/admin/class-admin.php';
require_once EMDR_PLUGIN_DIR . 'includes/public/class-public.php';

// Activation and deactivation hooks.
register_activation_hook( __FILE__, array( 'EMDR_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'EMDR_Deactivator', 'deactivate' ) );

// Initialize the plugin.
function emdr_init() {
    // Initialize public functionality.
    $public = new EMDR_Public();
    $public->init();

    // Initialize admin functionality.
    if ( is_admin() ) {
        $admin = new EMDR_Admin();
        $admin->init();
    }
}
add_action( 'plugins_loaded', 'emdr_init' );
?>