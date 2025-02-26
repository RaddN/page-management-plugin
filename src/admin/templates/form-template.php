<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submissions
    PMP_handle_form_submission();
}

function PMP_handle_form_submission() {
    if (isset($_POST['import_template'])) {
        import_template();
    } elseif (isset($_POST['create_page'])) {
        create_page();
    } elseif (isset($_POST['update_page'])) {
        update_page();
    } elseif (isset($_POST['delete_page'])) {
        delete_page();
    }
}

function import_template() {
    $template_id = intval($_POST['existing_page']);
    $template_post = get_post($template_id);
    $template_content = $template_post->post_content;
    // Further processing for importing template...
}

function create_page() {
    $parent_slug = sanitize_text_field($_POST['parent_slug']);
    $page_name = sanitize_text_field($_POST['page_name']);
    $page_slug = sanitize_text_field($_POST['page_slug']);
    $template_content = fetch_template_content();

    // Replace dynamic content placeholders
    $template_content = replace_dynamic_content($template_content);

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
    store_post_meta($new_page_id);
    
    // Redirect to the new page
    wp_redirect(get_permalink($new_page_id));
    exit;
}

function update_page() {
    $page_id = intval($_POST['page_id']);
    $page_name = sanitize_text_field($_POST['page_name']);
    $page_slug = sanitize_text_field($_POST['page_slug']);
    $template_content = fetch_template_content();

    // Replace dynamic content placeholders
    $template_content = replace_dynamic_content($template_content);

    // Update the existing page
    wp_update_post(array(
        'ID' => $page_id,
        'post_title' => $page_name,
        'post_name' => $page_slug,
        'post_content' => $template_content,
    ));

    // Update form data in post meta
    store_post_meta($page_id);
    
    // Redirect to the updated page
    wp_redirect(get_permalink($page_id));
    exit;
}

function delete_page() {
    $page_id = intval($_POST['page_id']);
    wp_delete_post($page_id, true);
    wp_redirect(admin_url('edit.php?post_type=page'));
    exit;
}

function fetch_template_content() {
    $template_id = intval($_POST['existing_page']);
    $template_post = get_post($template_id);
    return $template_post->post_content;
}

function replace_dynamic_content($template_content) {
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
    return $template_content . $loop_count;
}

function store_post_meta($post_id) {
    $meta_data = array();
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'rdynamic_') === 0) {
            $meta_data[$key] = wp_kses_post($value);
        }
    }
    // Store additional meta
    $meta_data['rpage_category'] = sanitize_text_field($_POST['template_title']);
    $meta_data['rdynamic_template_id'] = intval($_POST['existing_page']);
    
    // Store all meta data in a single meta field
    update_post_meta($post_id, 'rdynamic_meta_data', $meta_data);
}

function get_post_dynamic_meta($post_id) {
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

// Render form for importing templates
if (!isset($_POST['import_template'])): ?>
    <form method="post" action="" id="import_template_form" style="display: none;">
        <h2>Choose Template</h2>
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
    $template_id = intval($_POST['existing_page']);
    $template_post = get_post($template_id);
    $template_content = $template_post->post_content;
    $child_of_page = $_POST['child_of_rpages'] ?? '';

    preg_match_all('/\{\{\{rdynamic_content type=[\'\"]([^\'\"]+)[\'\"] name=[\'\"]([^\'\"]+)[\'\"] title=[\'\"]([^\'\"]+)[\'\"]\}\}\}/', $template_content, $matches, PREG_SET_ORDER);
    
    $matches = array_filter($matches, function($match) use ($template_content) {
        return !preg_match('/\{\{\{rdynamic_content_loop_start\}\}\}.*' . preg_quote($match[0], '/') . '.*\{\{\{rdynamic_content_loop_ends\}\}\}/s', $template_content);
    });

    $unique_matches = array_map('serialize', $matches);
    $unique_matches = array_unique($unique_matches);
    $matches = array_map('unserialize', $unique_matches);

    preg_match('/\{\{\{rdynamic_content_loop_start\}\}\}(.*?)\{\{\{rdynamic_content_loop_ends\}\}\}/s', $template_content, $loop_match);
    $loop_count = isset($_POST['loop_count']) ? intval($_POST['loop_count']) : 1;
    
    $meta_data = isset($_POST['page_id']) ? get_post_dynamic_meta($_POST['page_id']) : [];
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
            <?php if (stripos($match[1], 'text_area') !== false): ?>
                <textarea id="<?php echo esc_attr($match[2]); ?>" name="rdynamic_<?php echo esc_attr($match[2]); ?>" required><?php echo esc_attr($meta_data['rdynamic_' . $match[2]] ?? ''); ?></textarea>
            <?php else: ?>
                <input type="<?php echo esc_attr($match[1]); ?>" id="<?php echo esc_attr($match[2]); ?>" name="rdynamic_<?php echo esc_attr($match[2]); ?>" required value="<?php echo esc_attr($meta_data['rdynamic_' . $match[2]] ?? ''); ?>">
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($loop_match):
    $meta_data = isset($_POST['page_id']) ? get_post_dynamic_meta($_POST['page_id']) : [];
    $loop_matches = [];
    preg_match_all('/\{\{\{rdynamic_content type=[\'\"]([^\'\"]+)[\'\"] name=[\'\"]([^\'\"]+)[\'\"] title=[\'\"]([^\'\"]+)[\'\"]\}\}\}/', $loop_match[1], $loop_matches_raw, PREG_SET_ORDER);
    foreach ($loop_matches_raw as $match) {
        $loop_matches[] = [
            'type' => $match[1],
            'name' => $match[2],
            'title' => $match[3],
        ];
    }
    $loop_matches = array_map('unserialize', array_unique(array_map('serialize', $loop_matches)));
$stored_loop_count = 1;

// Determine how many stored items exist
foreach ($meta_data as $key => $value) {
    if (preg_match('/rdynamic_(\d+)_/', $key, $matches)) {
        $stored_loop_count = max($stored_loop_count, intval($matches[1]));
    }
}

$loop_count = isset($_POST['loop_count']) ? intval($_POST['loop_count']) : $stored_loop_count;

?>
    <h3>Loop Items</h3>
    <div id="loop_items">
        <?php for ($i = 1; $i <= $loop_count; $i++): ?>
            <div class="loop_item">
                <h4>Item <?php echo $i; ?></h4>
                <?php foreach ($loop_matches as $loop_match_item): ?>
                    <label for="rdynamic_<?php echo $i; ?>_<?php echo esc_attr($loop_match_item['name']); ?>">
                        <?php echo esc_html($loop_match_item['title']); ?>:
                    </label>
                    <input type="<?php echo esc_attr($loop_match_item['type']); ?>" 
                        id="rdynamic_<?php echo $i; ?>_<?php echo esc_attr($loop_match_item['name']); ?>" 
                        name="rdynamic_<?php echo $i; ?>_<?php echo esc_attr($loop_match_item['name']); ?>" 
                        required 
                        value="<?php echo esc_attr($meta_data['rdynamic_' . $i . '_' . $loop_match_item['name']] ?? ''); ?>">
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>
    </div>

    <input type="hidden" name="loop_count" id="loop_count" value="<?php echo esc_attr($loop_count); ?>">
    <button type="button" id="add_loop_item">Add Item</button>
    <button type="button" id="remove_loop_item">Remove Item</button>

    <script>
        document.getElementById('add_loop_item').addEventListener('click', function () {
            var loopCount = parseInt(document.getElementById('loop_count').value);
            loopCount++;
            document.getElementById('loop_count').value = loopCount;

            var loopItems = document.getElementById('loop_items');
            var newItem = document.createElement('div');
            newItem.classList.add('loop_item');

            newItem.innerHTML = `<h4>Item ${loopCount}</h4>
                <?php foreach ($loop_matches as $loop_match_item): ?>
                    <label for="rdynamic_${loopCount}_<?php echo esc_attr($loop_match_item['name']); ?>">
                        <?php echo esc_html($loop_match_item['title']); ?>:
                    </label>
                    <input type="<?php echo esc_attr($loop_match_item['type']); ?>" 
                        id="rdynamic_${loopCount}_<?php echo esc_attr($loop_match_item['name']); ?>" 
                        name="rdynamic_${loopCount}_<?php echo esc_attr($loop_match_item['name']); ?>" 
                        required>
                <?php endforeach; ?>`;

            loopItems.appendChild(newItem);
        });

        document.getElementById('remove_loop_item').addEventListener('click', function () {
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


        <input type="hidden" name="template_title" value="<?php echo esc_attr($_POST['template_title']); ?>">
        <input type="hidden" name="existing_page" value="<?php echo esc_attr($_POST['existing_page']); ?>">
        
        <?php if (isset($_POST['page_id'])): ?>
            <input type="hidden" name="page_id" value="<?php echo esc_attr($_POST['page_id']); ?>">
            <button type="submit" name="update_page">Update Page</button>
        <?php else: ?>
            <button type="submit" name="create_page">Create Page</button>
        <?php endif; ?>
    </form>
    <!-- <script>
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
    </script> -->
<?php endif; ?>
