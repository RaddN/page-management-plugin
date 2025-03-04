
<!-- old perfect code -->
<?php
function pmp_admin_page()
{
    // Get pages with 'rpage_category' in post meta
    $args = array(
        'meta_key' => 'rdynamic_meta_data',
        'post_type' => 'page',
        'posts_per_page' => -1
    );
    $args2 = array(
        'meta_key' => 'child_page_of',
        'post_type' => 'page',
        'posts_per_page' => -1
    );
    $child_pages = get_posts($args2);
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
                    // Get the dynamic meta data for the page
                    $meta_data = isset($page->ID) ? get_post_dynamic_meta($page->ID) : [];

                    // Unserialize the meta data if it is not empty
                    if (!empty($meta_data)) {
                        $meta_data_array = maybe_unserialize($meta_data);

                        // Check if 'rpage_category' exists in the unserialized array
                        $category = isset($meta_data_array['rpage_category']) ? $meta_data_array['rpage_category'] : '';

                        // Only add the category if it's not already in the array
                        if (!in_array($category, $categories) && !empty($category)) {
                            $categories[] = $category;
                        }
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
                                <th><?php esc_html_e('Pages', 'page-management-plugin'); ?> </th>
                                <th><?php esc_html_e('Child 1', 'page-management-plugin'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Store template IDs and filtered pages
                            $filtered_pages = [];
                            $template_ids = [];

                            foreach ($pages as $page) {
                                // Get the dynamic meta data for the page
                                $meta_data = isset($page->ID) ? get_post_dynamic_meta($page->ID) : [];

                                // Unserialize the meta data if it is not empty
                                if (!empty($meta_data)) {
                                    $meta_data_array = maybe_unserialize($meta_data);

                                    // Check if 'rpage_category' exists and matches the provided category
                                    if (isset($meta_data_array['rpage_category']) && $meta_data_array['rpage_category'] === $category) {
                                        // Add to filtered pages
                                        $filtered_pages[] = $page;

                                        // Store the template ID
                                        $template_ids[$page->ID] = isset($meta_data_array['rdynamic_template_id']) ? $meta_data_array['rdynamic_template_id'] : '';
                                    }
                                }
                            }
                            ?>

                            <?php if ($filtered_pages) : ?>
                                <?php foreach ($filtered_pages as $page) : ?>
                                    <tr>
                                        <td>
                                            <h3><?php echo esc_html(get_the_title($page->ID)); ?></h3>
                                            <form method="post" action="" style="display: inline;">
                                                <input type="hidden" name="template_title" value="<?php echo esc_attr($category); ?>">
                                                <input type="hidden" name="existing_page" value="<?php echo esc_attr($template_ids[$page->ID]); ?>">
                                                <input type="hidden" name="page_id" value="<?php echo esc_attr($page->ID); ?>">
                                                <input type="submit" name="import_template" value="Edit" style="cursor:pointer; border: none; background: transparent; padding: 0; font-size: 12px; margin-right: 5px;">
                                                <button type="submit" name="delete_page" style="cursor:pointer; border: none; background: transparent; padding: 0; font-size: 12px; margin-right: 5px;" onclick="return confirm('Are you sure you want to delete this page?');">Delete</button>
                                                <a href="<?php echo get_permalink($template_ids[$page->ID]); ?>" target="_blank">template</a>
                                            </form>
                                            <a href="<?php echo get_permalink($page->ID); ?>" target="_blank"><?php esc_html_e('View', 'page-management-plugin'); ?></a>
                                        </td>
                                        <td>
                                            <?php
                                            $path_segments = explode('/', trim(parse_url(get_permalink($page->ID), PHP_URL_PATH), '/'));
                                            foreach ($child_pages as $child_page) {
                                                $child_page_of = get_post_meta($child_page->ID, 'child_page_of', true);
                                                if ($child_page_of === end($path_segments)) {
                                                    $child_page_meta_data = isset($page->ID) ? get_post_dynamic_meta($child_page->ID) : [];

                                            ?>
                                                    <h3><?php echo esc_html(get_the_title($child_page->ID)); ?></h3>
                                                    <form method="post" action="" style="display: inline;">
                                                        <input type="hidden" name="template_title" value="">
                                                        <input type="hidden" name="existing_page" value="<?php echo esc_attr($template_ids[$page->ID]); ?>">
                                                        <input type="hidden" name="page_id" value="<?php echo esc_attr($child_page->ID); ?>">
                                                        <input type="submit" name="import_template" value="Edit" style="cursor:pointer; border: none; background: transparent; padding: 0; font-size: 12px; margin-right: 5px;">
                                                        <button type="submit" name="delete_page" style="cursor:pointer; border: none; background: transparent; padding: 0; font-size: 12px; margin-right: 5px;" onclick="return confirm('Are you sure you want to delete this page?');">Delete</button>
                                                        <a href="<?php echo get_permalink($template_ids[$child_page->ID]); ?>" target="_blank">template</a>
                                                    </form>
                                                    <a href="<?php echo get_permalink($page->ID); ?>" target="_blank"><?php esc_html_e('View', 'page-management-plugin'); ?></a>
                                            <?php }
                                            }
                                            ?>
                                            <button class="button button-primary choose_templates" data-child-page="yes" data-parent-id="<?php echo esc_attr($page->ID); ?>" data-parent-slug="<?php echo end($path_segments); ?>"><?php esc_html_e('Choose Template', 'page-management-plugin'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="2">
                                        <form method="post" action="">
                                            <input type="hidden" name="template_title" value="<?php echo esc_attr($category); ?>">
                                            <input type="hidden" name="existing_page" value="<?php echo esc_attr($template_ids[reset($filtered_pages)->ID] ?? ''); ?>">
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