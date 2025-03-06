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
require_once PMP_PLUGIN_DIR . 'src/blocks/class-pmp-blocks.php';

// Deactivation hook
function pmp_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pmp_deactivate' );

// Initialize the plugin
function pmp_init() {
    // Load text domain for translations
    load_plugin_textdomain('page-management-plugin', false, basename(dirname(__FILE__)) . '/languages');
}
add_action( 'init', 'pmp_init' );

// Enqueue block editor assets
function pmp_enqueue_block_editor_assets() {
    // Enqueue block editor script
    wp_enqueue_script(
        'pmp-blocks',
        PMP_PLUGIN_URL . 'src/blocks/dynamic-content-block/block.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
        filemtime(PMP_PLUGIN_DIR . 'src/blocks/dynamic-content-block/block.js'),
        true
    );

    // Enqueue editor styles
    wp_enqueue_style(
        'pmp-blocks-editor',
        PMP_PLUGIN_URL . 'src/blocks/dynamic-content-block/editor.css',
        array('wp-edit-blocks'),
        filemtime(PMP_PLUGIN_DIR . 'src/blocks/dynamic-content-block/editor.css')
    );
}
add_action('enqueue_block_editor_assets', 'pmp_enqueue_block_editor_assets');
