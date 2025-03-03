<?php

/**
 * Page Management Plugin Admin Page
 *
 * This file sets up the admin page for the plugin and includes the necessary hooks
 * to display the plugin interface in the WordPress admin dashboard.
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add admin menu
add_action('admin_menu', 'pmp_add_admin_menu');

function pmp_add_admin_menu()
{
    add_menu_page(
        'Page Management',
        'Page Management',
        'manage_options',
        'page-management',
        'pmp_admin_page',
        'dashicons-admin-page',
        6
    );
}

// Admin page callback
function pmp_admin_page()
{
    // Fetch pages
    $args = array('meta_key' => 'rdynamic_meta_data', 'post_type' => 'page', 'posts_per_page' => -1);
    $args2 = array('meta_key' => 'child_page_of', 'post_type' => 'page', 'posts_per_page' => -1);
    
    $pages = get_posts($args);
    $child_pages = get_posts($args2);
    $categories = pmp_get_categories($pages);
    
    include_once plugin_dir_path(__FILE__) . 'templates/form-template.php';

    ?>
    <div class="wrap" id="page-management">
        <h1><?php esc_html_e('Page Management System', 'page-management-plugin'); ?></h1>
        <h2 class="nav-tab-wrapper">
            <?php pmp_render_category_tabs($categories); ?>
            <?php if (!empty($categories)) : ?>
                    <button class="button button-primary choose_templates"><?php esc_html_e('+', 'page-management-plugin'); ?></button>
                <?php else : ?>
                    <button class="button button-primary choose_templates"><?php esc_html_e('Choose Template', 'page-management-plugin'); ?></button>
                <?php endif; ?>
        </h2>
        
        <?php foreach ($categories as $index => $category) : ?>
            <div id="tab-<?php echo esc_attr($index + 1); ?>" class="tab-content" style="<?php echo $index === 0 ? 'display:block;' : 'display:none;'; ?>">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Pages', 'page-management-plugin'); ?></th>
                            <th><?php esc_html_e('Child Pages', 'page-management-plugin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php pmp_render_filtered_pages($pages, $child_pages, $category); ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
    <p><?php esc_html_e('Instead of content, use {{{rdynamic_content type="text" name="first_title" title="first Title"}}} in your template page', 'page-management-plugin'); ?></p>
    <?php
}

// Get unique categories from pages
function pmp_get_categories($pages) {
    $categories = [];
    foreach ($pages as $page) {
        $meta_data = get_post_dynamic_meta($page->ID);
        $meta_data_array = maybe_unserialize($meta_data);
        $category = $meta_data_array['rpage_category'] ?? '';
        if (!empty($category) && !in_array($category, $categories)) {
            $categories[] = $category;
        }
    }
    return $categories;
}

function get_post_dynamic_meta($post_id)
{
    // Retrieve the stored meta data
    $meta_data = get_post_meta($post_id, 'rdynamic_meta_data', true);

    // Unserialize the data
    $meta_data = maybe_unserialize($meta_data);

    // If no meta data found, return an empty array
    if (empty($meta_data)) {
        return array();
    }

    // Return the meta data as an associative array
    return $meta_data;
}

// Render tabs for categories
function pmp_render_category_tabs($categories) {
    foreach ($categories as $index => $category) {
        $active_class = $index === 0 ? 'nav-tab-active' : '';
        echo '<a href="#tab-' . esc_attr($index + 1) . '" class="nav-tab ' . esc_attr($active_class) . '">' . esc_html($category) . '</a>';
    }
}

// Render filtered pages
function pmp_render_filtered_pages($pages, $child_pages, $category) {
    $filtered_pages = [];
    foreach ($pages as $page) {
        $meta_data = get_post_dynamic_meta($page->ID);
        $meta_data_array = maybe_unserialize($meta_data);
        if (isset($meta_data_array['rpage_category']) && $meta_data_array['rpage_category'] === $category) {
            $filtered_pages[] = $page;
        }
    }

    if ($filtered_pages) {
        foreach ($filtered_pages as $page) {
            pmp_render_page_row($page, $child_pages);
        }
        echo '<tr><td colspan="2"><form method="post" action=""><input type="hidden" name="template_title" value="' . esc_attr($category) . '"><button type="submit" name="import_template" class="button button-primary">' . esc_html__('Create New Page', 'page-management-plugin') . '</button></form></td></tr>';
    } else {
        echo '<tr><td colspan="2">' . esc_html__('No pages found.', 'page-management-plugin') . '</td></tr>';
    }
}

// Render a row for a page
function pmp_render_page_row($page, $child_pages) {
    ?>
    <tr>
        <td>
            <h3><?php echo esc_html(get_the_title($page->ID)); ?></h3>
            <form method="post" action="" style="display: inline;">
                <input type="hidden" name="page_id" value="<?php echo esc_attr($page->ID); ?>">
                <input type="submit" name="import_template" value="Edit" style="cursor:pointer; border: none; background: transparent; padding: 0; font-size: 12px; margin-right: 5px;">
                <button type="submit" name="delete_page" style="cursor:pointer; border: none; background: transparent; padding: 0; font-size: 12px; margin-right: 5px;" onclick="return confirm('Are you sure you want to delete this page?');">Delete</button>
                <a href="<?php echo get_permalink($page->ID); ?>" target="_blank"><?php esc_html_e('View', 'page-management-plugin'); ?></a>
            </form>
        </td>
        <td>
            <?php pmp_render_child_pages($page, $child_pages); ?>
        </td>
    </tr>
    <?php
}

// Render child pages for a given parent page
function pmp_render_child_pages($page, $child_pages) {
    $path_segments = explode('/', trim(parse_url(get_permalink($page->ID), PHP_URL_PATH), '/'));
    foreach ($child_pages as $child_page) {
        if (get_post_meta($child_page->ID, 'child_page_of', true) === end($path_segments)) {
            ?>
            <h3><?php echo esc_html(get_the_title($child_page->ID)); ?></h3>
            <form method="post" action="" style="display: inline;">
                <input type="hidden" name="page_id" value="<?php echo esc_attr($child_page->ID); ?>">
                <input type="submit" name="import_template" value="Edit" class="button button-small">
                <button type="submit" name="delete_page" class="button button-small" onclick="return confirm('Are you sure you want to delete this page?');">Delete</button>
                <a href="<?php echo get_permalink($template_ids[$page->ID]); ?>" target="_blank">template</a>
                <a href="<?php echo get_permalink($child_page->ID); ?>" target="_blank"><?php esc_html_e('View', 'page-management-plugin'); ?></a>
            </form>
            <?php
        }
    }
}

// Enqueue scripts
add_action('admin_enqueue_scripts', 'pmp_enqueue_scripts');

function pmp_enqueue_scripts()
{
    wp_enqueue_script('pmp-plugin-scripts', plugin_dir_url(__FILE__) . '../public/js/plugin-scripts.js', array('jquery'), null, true);
}
?>






