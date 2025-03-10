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


// on template page update, update all included pages
/**
 * Hook into post update to handle template changes
 */
function pmp_template_update_handler($post_id, $post_after, $post_before) {
    // Only run for pages
    if ($post_after->post_type !== 'page') {
        return;
    }
    
    // Check if the content has changed
    $content_changed = $post_after->post_content !== $post_before->post_content;
    $title_changed = $post_after->post_title !== $post_before->post_title;
    
    // Find all pages that use this template
    $dependent_pages = get_posts(array(
        'post_type' => 'page',
        'meta_query' => array(
            array(
                'key' => 'rdynamic_template_id',
                'value' => $post_id,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1
    ));
    
    if (empty($dependent_pages)) {
        return;
    }
    
    // Get ALL meta data from the template page
    $template_meta = get_post_meta($post_id);
    
    // Loop through each dependent page and update it
    foreach ($dependent_pages as $page) {
        // Update content if it changed
        if ($content_changed) {
            update_dependent_page_content($page->ID, $post_after->post_content);
        }
        
        // Update ALL meta data from template to dependent page
        update_dependent_page_meta($page->ID, $template_meta);
    }
}
add_action('post_updated', 'pmp_template_update_handler', 10, 3);

/**
 * Update a page that depends on the template - content only
 */
function update_dependent_page_content($page_id, $template_content) {
    // Get the stored meta data for this page
    $meta_data = get_post_meta($page_id, 'rdynamic_meta_data', true);
    
    if (empty($meta_data)) {
        return;
    }
    
    // Use the template content and replace dynamic placeholders with stored values
    $updated_content = $template_content;
    
    // Replace single dynamic content placeholders
    foreach ($meta_data as $key => $value) {
        if (strpos($key, 'rdynamic_') === 0) {
            $dynamic_name = substr($key, 9); // Remove 'rdynamic_' prefix
            $updated_content = preg_replace(
                '/\{\{\{rdynamic_content type=[\'"][^\'"]+[\'"] name=[\'"]' . preg_quote($dynamic_name, '/') . '[\'"] title=[\'"][^\'"]+[\'"]\}\}\}/',
                wp_kses_post($value),
                $updated_content
            );
        }
    }
    
    // Handle loop content replacement
    $pattern = '/{{{rdynamic_content_loop_start name=[\'"]?([^\'"]+)[\'"]?}}}(.*?){{{rdynamic_content_loop_ends name=[\'"]?\1[\'"]?}}}/s';
    if (preg_match_all($pattern, $updated_content, $loop_matches)) {
        foreach ($loop_matches[0] as $index => $loop_match) {
            $loop_name = $loop_matches[1][$index]; // Extract loop name
            $loop_template = $loop_matches[2][$index]; // Extract loop template
            
            // Count how many loop items we have for this loop name
            $loop_count = 0;
            foreach ($meta_data as $key => $value) {
                if (preg_match('/^rdynamic_' . preg_quote($loop_name, '/') . '_(\d+)_/', $key, $matches)) {
                    $item_number = intval($matches[1]);
                    if ($item_number > $loop_count) {
                        $loop_count = $item_number;
                    }
                }
            }
            
            // Generate the loop content
            $loop_content = '';
            for ($i = 1; $i <= $loop_count; $i++) {
                $current_loop_content = $loop_template;
                
                // Replace each placeholder in this loop item
                foreach ($meta_data as $key => $value) {
                    if (preg_match('/^rdynamic_' . preg_quote($loop_name, '/') . '_' . $i . '_(.+)$/', $key, $matches)) {
                        $dynamic_name = $matches[1];
                        $current_loop_content = preg_replace(
                            '/\{\{\{loop_content type=[\'"][^\'"]+[\'"] name=[\'"]' . preg_quote($dynamic_name, '/') . '[\'"] title=[\'"][^\'"]+[\'"]\}\}\}/',
                            wp_kses_post($value),
                            $current_loop_content
                        );
                    }
                }
                
                $loop_content .= $current_loop_content;
            }
            
            // Replace the loop placeholder with the generated content
            $updated_content = str_replace($loop_match, $loop_content, $updated_content);
        }
    }
    
    // Update the page with the new content
    wp_update_post(array(
        'ID' => $page_id,
        'post_content' => $updated_content
    ));
}

/**
 * Update all meta data for dependent pages
 */
function update_dependent_page_meta($page_id, $template_meta) {
    // List of meta keys that should NOT be copied from template to dependent pages
    $exclude_meta_keys = array(
        '_edit_lock',                    // Editing locks
        '_edit_last',                    // Last editor
        '_wp_page_template',             // Page template
        '_wp_old_slug'                  // Old slug
    );
    
    // Store any page-specific meta that we want to preserve
    $preserved_meta = array();
    foreach ($exclude_meta_keys as $key) {
        $value = get_post_meta($page_id, $key, true);
        if (!empty($value)) {
            $preserved_meta[$key] = $value;
        }
    }
    
    // Apply all template meta to the dependent page
    foreach ($template_meta as $meta_key => $meta_values) {
        // Skip excluded meta keys
        if (in_array($meta_key, $exclude_meta_keys)) {
            continue;
        }
        
        // Meta comes as array, but we typically want the first value
        $meta_value = reset($meta_values);
        
        // Update the meta value
        update_post_meta($page_id, $meta_key, $meta_value);
    }
    
    // Restore preserved meta values
    foreach ($preserved_meta as $key => $value) {
        update_post_meta($page_id, $key, $value);
    }
    
    // Trigger action to allow additional customizations
    do_action('rdynamic_after_meta_update', $page_id, $template_meta);
}

/**
 * Add meta box to display which pages are using this template
 */
function add_template_info_meta_box() {
    add_meta_box(
        'rdynamic_template_info',
        'Template Information',
        'render_template_info_meta_box',
        'page',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_template_info_meta_box');

/**
 * Render the template info meta box
 */
function render_template_info_meta_box($post) {
    // Check if this is a template page
    $dependent_pages = get_posts(array(
        'post_type' => 'page',
        'meta_query' => array(
            array(
                'key' => 'rdynamic_template_id',
                'value' => $post->ID,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1
    ));
    
    if (!empty($dependent_pages)) {
        echo '<p><strong>This is a template page used by:</strong></p>';
        echo '<ul>';
        foreach ($dependent_pages as $page) {
            echo '<li><a href="' . get_edit_post_link($page->ID) . '">' . esc_html($page->post_title) . '</a></li>';
        }
        echo '</ul>';
        echo '<p><em>Content and style changes made to this page will update all dependent pages.</em></p>';
        
        // Add info about title visibility
        $title_visibility = get_post_meta($post->ID, 'ast-title-bar-display', true) === 'disabled' ? 'Hidden' : 'Visible';
        echo '<p><strong>Title visibility:</strong> ' . $title_visibility . '</p>';
        echo '<p><em>All meta data changes will affect all dependent pages.</em></p>';
    } else {
        // Check if this page uses a template
        $template_id = get_post_meta($post->ID, 'rdynamic_template_id', true);
        if (!empty($template_id)) {
            $template = get_post($template_id);
            if ($template) {
                echo '<p><strong>This page uses template:</strong> <a href="' . get_edit_post_link($template_id) . '">' . esc_html($template->post_title) . '</a></p>';
                
                // Show if title is visible or hidden based on template settings
                $title_visibility = get_post_meta($post->ID, 'ast-title-bar-display', true) === 'disabled' ? 'Hidden' : 'Visible';
                echo '<p><strong>Title visibility:</strong> ' . $title_visibility . ' (inherited from template)</p>';
            }
        }
    }
}