<?php
/**
 * Plugin Name: Page Management Plugin
 * Description: A plugin to manage and create multiple pages based on templates with dynamic content.
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'PMP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PMP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once PMP_PLUGIN_DIR . 'src/includes/class-page-manager.php';
require_once PMP_PLUGIN_DIR . 'src/includes/class-template-handler.php';
require_once PMP_PLUGIN_DIR . 'src/admin/admin-page.php';

// Activation hook
function pmp_activate() {
    // Code to run on plugin activation
}
register_activation_hook( __FILE__, 'pmp_activate' );

// Deactivation hook
function pmp_deactivate() {
    // Code to run on plugin deactivation
}
register_deactivation_hook( __FILE__, 'pmp_deactivate' );

// Initialize the plugin
function pmp_init() {
    // Code to initialize the plugin
}
add_action( 'init', 'pmp_init' );
?>