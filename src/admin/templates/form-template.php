<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['import_template'])) {
        // Handle the import template form submission
        $template_id = intval($_POST['existing_page']);
        $template_post = get_post($template_id);
        $template_content = $template_post->post_content;
    } elseif (isset($_POST['create_page'])) {
        // Handle the create page form submission
        $parent_slug = sanitize_text_field($_POST['parent_slug']);
        $page_name = sanitize_text_field($_POST['page_name']);
        $page_slug = sanitize_text_field($_POST['page_slug']);
        $template_title = sanitize_text_field($_POST['template_title']);

        // Fetch the selected template's content
        $template_id = intval($_POST['existing_page']);
        $template_post = get_post($template_id);
        $template_content = $template_post->post_content;

        // Replace dynamic content placeholders with form data
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'rdynamic_') === 0) {
                $dynamic_name = substr($key, 9); // Remove 'rdynamic_' prefix
                $template_content = preg_replace(
                    '/\{\{\{rdynamic_content type=[\'"][^\'"]+[\'"] name=[\'"]' . preg_quote($dynamic_name, '/') . '[\'"] title=[\'"][^\'"]+[\'"]\}\}\}/',
                    wp_kses_post($value),
                    $template_content
                );
            }
        }

        // Handle loop content
        if (preg_match('/\{\{\{rdynamic_content_loop_start\}\}\}(.*?)\{\{\{rdynamic_content_loop_ends\}\}\}/s', $template_content, $loop_match)) {
            $loop_content = '';
            $loop_template = $loop_match[1];
            $loop_count = intval($_POST['loop_count']);

            for ($i = 1; $i <= $loop_count; $i++) {
                $current_loop_content = $loop_template;
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'rdynamic_' . $i . '_') === 0) {
                        $dynamic_name = substr($key, 10 + strlen($i)); // Remove 'rdynamic_' and loop index prefix
                        $current_loop_content = preg_replace(
                            '/\{\{\{rdynamic_content type=[\'"][^\'"]+[\'"] name=[\'"]' . preg_quote($dynamic_name, '/') . '[\'"] title=[\'"][^\'"]+[\'"]\}\}\}/',
                            wp_kses_post($value),
                            $current_loop_content
                        );
                    }
                }
                $loop_content .= $current_loop_content;
            }

            $template_content = preg_replace(
                '/\{\{\{rdynamic_content_loop_start\}\}\}(.*?)\{\{\{rdynamic_content_loop_ends\}\}\}/s',
                $loop_content,
                $template_content
            );
        }

        // Create the new page
        $new_page_id = wp_insert_post(array(
            'post_title' => $page_name,
            'post_name' => $page_slug,
            'post_content' => $template_content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_parent' => !empty($parent_slug) ? get_page_by_path($parent_slug)->ID : 0,
        ));

        // Store form data in post meta
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'rdynamic_') === 0) {
                update_post_meta($new_page_id, $key, wp_kses_post($value));
            }
        }

        // Set the rpage_category for the new page
        update_post_meta($new_page_id, 'rpage_category', $template_title);
        update_post_meta($new_page_id, 'rdynamic_template_id', $template_id);

        // Redirect to the new page
        wp_redirect(get_permalink($new_page_id));
        exit;
    } elseif (isset($_POST['update_page'])) {
        // Handle the update page form submission
        $page_id = intval($_POST['page_id']);
        $page_name = sanitize_text_field($_POST['page_name']);
        $page_slug = sanitize_text_field($_POST['page_slug']);
        $template_title = sanitize_text_field($_POST['template_title']);

        // Fetch the selected template's content
        $template_id = intval($_POST['existing_page']);
        $template_post = get_post($template_id);
        $template_content = $template_post->post_content;

        // Replace dynamic content placeholders with form data
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'rdynamic_') === 0) {
                $dynamic_name = substr($key, 9); // Remove 'rdynamic_' prefix
                $template_content = preg_replace(
                    '/\{\{\{rdynamic_content type=[\'"][^\'"]+[\'"] name=[\'"]' . preg_quote($dynamic_name, '/') . '[\'"] title=[\'"][^\'"]+[\'"]\}\}\}/',
                    wp_kses_post($value),
                    $template_content
                );
            }
        }

        // Handle loop content
        if (preg_match('/\{\{\{rdynamic_content_loop_start\}\}\}(.*?)\{\{\{rdynamic_content_loop_ends\}\}\}/s', $template_content, $loop_match)) {
            $loop_content = '';
            $loop_template = $loop_match[1];
            $loop_count = intval($_POST['loop_count']);

            for ($i = 1; $i <= $loop_count; $i++) {
                $current_loop_content = $loop_template;
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'rdynamic_' . $i . '_') === 0) {
                        $dynamic_name = substr($key, 10 + strlen($i)); // Remove 'rdynamic_' and loop index prefix
                        $current_loop_content = preg_replace(
                            '/\{\{\{rdynamic_content type=[\'"][^\'"]+[\'"] name=[\'"]' . preg_quote($dynamic_name, '/') . '[\'"] title=[\'"][^\'"]+[\'"]\}\}\}/',
                            wp_kses_post($value),
                            $current_loop_content
                        );
                    }
                }
                $loop_content .= $current_loop_content;
            }

            $template_content = preg_replace(
                '/\{\{\{rdynamic_content_loop_start\}\}\}(.*?)\{\{\{rdynamic_content_loop_ends\}\}\}/s',
                $loop_content,
                $template_content
            );
        }

        // Update the existing page
        wp_update_post(array(
            'ID' => $page_id,
            'post_title' => $page_name,
            'post_name' => $page_slug,
            'post_content' => $template_content,
        ));

        // Update form data in post meta
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'rdynamic_') === 0) {
                update_post_meta($page_id, $key, wp_kses_post($value));
            }
        }

        // Update the rpage_category for the page
        update_post_meta($page_id, 'rpage_category', $template_title);
        update_post_meta($page_id, 'rdynamic_template_id', $template_id);

        // Redirect to the updated page
        wp_redirect(get_permalink($page_id));
        exit;
    } elseif (isset($_POST['delete_page'])) {
        // Handle the delete page form submission
        $page_id = intval($_POST['page_id']);

        // Delete the page
        wp_delete_post($page_id, true);

        // Redirect to the admin page or any other page
        wp_redirect(admin_url('edit.php?post_type=page'));
        exit;
    }
}

if (!isset($_POST['import_template'])): ?>
    <form method="post" action="" id="import_template_form" style="display: none;">
        <h2>Choose Template</h2>
        <input type="hidden" name="child_of_rpages" value="" id="child_of_rpages_input">
        <label for="template_title">Template Title:</label>
        <input type="text" id="template_title" name="template_title" required>

        <label for="existing_page">Select Existing Page:</label>
        <select id="existing_page" name="existing_page" required>
            <option value="">Select a page</option>
            <?php
            // Fetch existing pages and populate the dropdown
            $pages = get_posts(array('post_type' => 'page', 'numberposts' => -1, 'post_status' => array('publish', 'draft')));
            foreach ($pages as $page) {
                echo '<option value="' . esc_attr($page->ID) . '">' . esc_html($page->post_title) . ' (' . esc_html($page->post_status) . ')</option>';
            }
            ?>
        </select>

        <button type="submit" name="import_template">Import</button>
    </form>
<?php endif; ?>
<?php if (isset($_POST['import_template'])): ?>
    <?php
    // Fetch the selected template's content
    $template_id = intval($_POST['existing_page']);
    $template_post = get_post($template_id);
    $template_content = $template_post->post_content;
    $child_of_page = $_POST['child_of_rpages'] ?? '';

    // Match all dynamic content placeholders
    preg_match_all('/\{\{\{rdynamic_content type=[\'"]([^\'"]+)[\'"] name=[\'"]([^\'"]+)[\'"] title=[\'"]([^\'"]+)[\'"]\}\}\}/', $template_content, $matches, PREG_SET_ORDER);

    // Remove loop content from matches
    $matches = array_filter($matches, function($match) use ($template_content) {
        return !preg_match('/\{\{\{rdynamic_content_loop_start\}\}\}.*' . preg_quote($match[0], '/') . '.*\{\{\{rdynamic_content_loop_ends\}\}\}/s', $template_content);
    });

    // Remove duplicates
    $unique_matches = array_map('serialize', $matches); // Serialize to make unique comparison possible
    $unique_matches = array_unique($unique_matches); // Remove duplicates
    $matches = array_map('unserialize', $unique_matches); // Unserialize back to original format

    // Match loop content
    preg_match('/\{\{\{rdynamic_content_loop_start\}\}\}(.*?)\{\{\{rdynamic_content_loop_ends\}\}\}/s', $template_content, $loop_match);
    $unique_matches = array_map('serialize', $loop_match); // Serialize to make unique comparison possible
    $loop_match = array_unique($unique_matches); // Remove duplicates
    $loop_match = array_map('unserialize', $loop_match); // Unserialize back to original format
    $loop_count = isset($_POST['loop_count']) ? intval($_POST['loop_count']) : 1;
    ?>

    <form method="post" action="">
        <h2><?php echo !isset($_POST['page_id']) ? 'Create Page' : 'Update Page'; ?></h2>
        <input type="hidden" name="child_of_rpages" value="<?php echo esc_attr($child_of_page); ?>">
        <label for="parent_slug">Parent Page Slug:</label>
        <input type="text" id="parent_slug" name="parent_slug" value="<?php echo isset($_POST['page_id']) ? esc_attr(get_post_field('post_name', wp_get_post_parent_id($_POST['page_id']))) : ''; ?>">

        <label for="page_name">Page Name:</label>
        <input type="text" id="page_name" name="page_name" required value="<?php echo isset($_POST['page_id']) ? esc_attr(get_the_title($_POST['page_id'])) : ''; ?>">

        <label for="page_slug">Page Slug:</label>
        <input type="text" id="page_slug" name="page_slug" required value="<?php echo isset($_POST['page_id']) ? esc_attr(get_post_field('post_name', $_POST['page_id'])) : ''; ?>">

        <?php foreach ($matches as $match): ?>
            <label for="<?php echo esc_attr($match[2]); ?>"><?php echo esc_html($match[3]); ?>:</label>
            <input type="<?php echo esc_attr($match[1]); ?>" id="<?php echo esc_attr($match[2]); ?>" name="rdynamic_<?php echo esc_attr($match[2]); ?>" required value="<?php echo isset($_POST['page_id']) ? esc_attr(get_post_meta($_POST['page_id'], 'rdynamic_' . $match[2], true)) : ''; ?>">
        <?php endforeach; ?>

        <?php if ($loop_match): ?>
            <h3>Loop Items</h3>
            <input type="hidden" name="loop_count" id="loop_count" value="<?php echo esc_attr($loop_count); ?>">
            <div id="loop_items">
                <?php for ($i = 1; $i <= $loop_count; $i++): ?>
                    <div class="loop_item">
                        <h4>Item <?php echo $i; ?></h4>
                        <?php
                        preg_match_all('/\{\{\{rdynamic_content type=[\'"]([^\'"]+)[\'"] name=[\'"]([^\'"]+)[\'"] title=[\'"]([^\'"]+)[\'"]\}\}\}/', $loop_match[1], $loop_matches, PREG_SET_ORDER);
                        foreach ($loop_matches as $loop_match_item): ?>
                            <label for="rdynamic_<?php echo $i; ?>_<?php echo esc_attr($loop_match_item[2]); ?>"><?php echo esc_html($loop_match_item[3]); ?>:</label>
                            <input type="<?php echo esc_attr($loop_match_item[1]); ?>" id="rdynamic_<?php echo $i; ?>_<?php echo esc_attr($loop_match_item[2]); ?>" name="rdynamic_<?php echo $i; ?>_<?php echo esc_attr($loop_match_item[2]); ?>" required value="<?php echo isset($_POST['page_id']) ? esc_attr(get_post_meta($_POST['page_id'], 'rdynamic_' . $i . '_' . $loop_match_item[2], true)) : ''; ?>">
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div>
            <button type="button" id="add_loop_item">Add Item</button>
            <button type="button" id="remove_loop_item">Remove Item</button>
        <?php endif; ?>

        <input type="hidden" name="template_title" value="<?php echo esc_attr($_POST['template_title']); ?>">
        <input type="hidden" name="existing_page" value="<?php echo esc_attr($_POST['existing_page']); ?>">

        <?php if (isset($_POST['page_id'])): ?>
            <input type="hidden" name="page_id" value="<?php echo esc_attr($_POST['page_id']); ?>">
            <button type="submit" name="update_page">Update Page</button>
        <?php else: ?>
            <button type="submit" name="create_page">Create Page</button>
        <?php endif; ?>
    </form>

    <script>
        document.getElementById('add_loop_item').addEventListener('click', function() {
            var loopCount = parseInt(document.getElementById('loop_count').value);
            loopCount++;
            document.getElementById('loop_count').value = loopCount;

            var loopItems = document.getElementById('loop_items');
            var newItem = document.createElement('div');
            newItem.classList.add('loop_item');
            newItem.innerHTML = '<h4>Item ' + loopCount + '</h4>' + '<?php foreach ($loop_matches as $loop_match_item): ?><label for="rdynamic_' + loopCount + '_<?php echo esc_attr($loop_match_item[2]); ?>"><?php echo esc_html($loop_match_item[3]); ?>:</label><input type="<?php echo esc_attr($loop_match_item[1]); ?>" id="rdynamic_' + loopCount + '_<?php echo esc_attr($loop_match_item[2]); ?>" name="rdynamic_' + loopCount + '_<?php echo esc_attr($loop_match_item[2]); ?>" required><?php endforeach; ?>';
            loopItems.appendChild(newItem);
        });

        document.getElementById('remove_loop_item').addEventListener('click', function() {
            var loopCount = parseInt(document.getElementById('loop_count').value);
            if (loopCount > 1) {
                loopCount--;
                document.getElementById('loop_count').value = loopCount;

                var loopItems = document.getElementById('loop_items');
                loopItems.removeChild(loopItems.lastChild);
            }
        });
    </script>
<?php endif; ?>