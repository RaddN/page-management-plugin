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
    // Get pages with 'rpage_category' in post meta
    $args = array(
        'meta_key' => 'rpage_category',
        'post_type' => 'page',
        'posts_per_page' => -1
    );
    include_once plugin_dir_path(__FILE__) . 'templates/form-template.php';
    if (!isset($_POST['import_template'])):
        $pages = get_posts($args);
?>
        <div class="wrap" id="page-management">
            <h1><?php esc_html_e('Page Management System', 'page-management-plugin'); ?></h1>
            <h2 class="nav-tab-wrapper">
                <?php
                $categories = array();
                foreach ($pages as $page) {
                    $category = get_post_meta($page->ID, 'rpage_category', true);
                    if (! in_array($category, $categories)) {
                        $categories[] = $category;
                    }
                }
                foreach ($categories as $index => $category) {
                    $active_class = $index === 0 ? 'nav-tab-active' : '';
                    echo '<a href="#tab-' . esc_attr($index + 1) . '" class="nav-tab ' . esc_attr($active_class) . '">' . esc_html($category) . '</a>';
                }
                ?>
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
                                <th><?php esc_html_e('Pages', 'page-management-plugin'); ?> <button class="button"><?php esc_html_e('Change Template', 'page-management-plugin'); ?></button></th>
                                <th><?php esc_html_e('Actions', 'page-management-plugin'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $filtered_pages = array_filter($pages, function ($page) use ($category) {
                                return get_post_meta($page->ID, 'rpage_category', true) === $category;
                            });
                            ?>
                            <?php if ($filtered_pages) : ?>
                                <?php foreach ($filtered_pages as $page) : ?>
                                    <tr>
                                        <td>
                                            <h3><?php echo esc_html(get_the_title($page->ID)); ?></h3>
                                            <form method="post" action="" style="display: inline;">
                                                <input type="hidden" name="template_title" value="<?php echo esc_attr($category); ?>">
                                                <input type="hidden" name="existing_page" value="<?php echo esc_attr(get_post_meta(reset($filtered_pages)->ID, 'rdynamic_template_id', true)); ?>">
                                                <input type="hidden" name="page_id" value="<?php echo esc_attr($page->ID); ?>">
                                                <input type="submit" name="import_template" value="Edit" style="cursor:pointer; border: none; background: transparent; padding: 0; font-size: 12px; margin-right: 5px;">
                                                <button type="submit" name="delete_page" style="cursor:pointer; border: none; background: transparent; padding: 0; font-size: 12px; margin-right: 5px;" onclick="return confirm('Are you sure you want to delete this page?');">Delete</button>
                                            </form>
                                            <a href="<?php echo get_permalink($page->ID); ?>" target="_blank"><?php esc_html_e('View', 'page-management-plugin'); ?></a>


                                        </td>
                                        <td>child will be here
                    <button class="button button-primary choose_templates" data-child-page = "yes"><?php esc_html_e('Choose Template', 'page-management-plugin'); ?></button>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="2">
                                        <form method="post" action="">
                                            <input type="hidden" name="template_title" value="<?php echo esc_attr($category); ?>">
                                            <input type="hidden" name="existing_page" value="<?php echo esc_attr(get_post_meta(reset($filtered_pages)->ID, 'rdynamic_template_id', true)); ?>">
                                            <button type="submit" name="import_template" class="button button-primary"><?php esc_html_e('Create New Page', 'page-management-plugin'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <tr>
                                    <td colspan="2"><?php esc_html_e('No pages found.', 'page-management-plugin'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
<?php
    endif;
    echo '<p> instead of content use {{{rdynamic_content type="text" name="first_title" title="first Title"}}} in your template page</p>';
}

// Enqueue scripts
add_action('admin_enqueue_scripts', 'pmp_enqueue_scripts');

function pmp_enqueue_scripts()
{
    wp_enqueue_script('pmp-plugin-scripts', plugin_dir_url(__FILE__) . '../public/js/plugin-scripts.js', array('jquery'), null, true);
}
?>