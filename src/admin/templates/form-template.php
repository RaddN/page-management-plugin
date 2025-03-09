<?php

/**
 * templates/form-template.php
 *
 * 
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submissions
    PMP_handle_form_submission();
}

function PMP_handle_form_submission()
{
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

function import_template()
{
    $template_id = intval($_POST['existing_page']);
    $template_post = get_post($template_id);
    $template_content = $template_post->post_content;
    // Further processing for importing template...
}

function create_page()
{
    $parent_slug = sanitize_text_field($_POST['parent_slug']);
    $page_name = sanitize_text_field($_POST['page_name']);
    $page_slug = sanitize_text_field($_POST['page_slug']);
    $template_content = fetch_template_content();

    // Replace dynamic content placeholders
    $template_content = replace_dynamic_content($template_content);

    // Get parent page ID if parent slug exists
    $parent_id = 0;
    if (!empty($parent_slug)) {
        $parent_page = get_page_by_path($parent_slug);
        if ($parent_page) {
            $parent_id = $parent_page->ID;
        }
    }

    // Create the new page
    $new_page_id = wp_insert_post(array(
        'post_title' => $page_name,
        'post_name' => $page_slug,
        'post_content' => $template_content,
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_parent' => $parent_id,
    ));

    // Store form data in post meta
    store_post_meta($new_page_id);

    // Redirect to the new page
    wp_redirect(get_permalink($new_page_id));
    exit;
}

function update_page()
{
    $page_id = intval($_POST['page_id']);
    $parent_slug = sanitize_text_field($_POST['parent_slug']);
    $page_name = sanitize_text_field($_POST['page_name']);
    $page_slug = sanitize_text_field($_POST['page_slug']);
    $template_content = fetch_template_content();

    // Replace dynamic content placeholders
    $template_content = replace_dynamic_content($template_content);

    // Get parent page ID if parent slug exists
    $parent_id = 0;
    if (!empty($parent_slug)) {
        $parent_page = get_page_by_path($parent_slug);
        if ($parent_page) {
            $parent_id = $parent_page->ID;
        }
    }

    // Update the existing page
    wp_update_post(array(
        'ID' => $page_id,
        'post_title' => $page_name,
        'post_name' => $page_slug,
        'post_content' => $template_content,
        'post_parent' => $parent_id,
    ));

    // Update form data in post meta
    store_post_meta($page_id);

    // Redirect to the updated page
    wp_redirect(get_permalink($page_id));
    exit;
}

function delete_page()
{
    $page_id = intval($_POST['page_id']);

    // Get all child pages recursively
    $child_pages = get_pages([
        'child_of' => $page_id,
    ]);
    // First delete all child pages to avoid orphaned pages
    foreach ($child_pages as $child) {
        wp_delete_post($child->ID, true);
    }

    wp_delete_post($page_id, true);
    wp_redirect(admin_url('admin.php?page=page-management'));
    exit;
}

function fetch_template_content()
{
    $template_id = intval($_POST['existing_page']);
    $template_post = get_post($template_id);
    return $template_post->post_content;
}

function replace_dynamic_content($template_content)
{
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
    $pattern = '/{{{rdynamic_content_loop_start name=[\'"]?([^\'"]+)[\'"]?}}}(.*?){{{rdynamic_content_loop_ends name=[\'"]?\1[\'"]?}}}/s';

    if (preg_match_all($pattern, $template_content, $loop_matches)) {
        foreach ($loop_matches[0] as $index => $loop_match) {
            $loop_name = $loop_matches[1][$index]; // Extract loop name
            $loop_template = $loop_matches[2][$index];

            // Get loop count, default to 1 if not set
            $loop_count = intval($_POST['loop_count_' . $loop_name] ?? 1);
            $loop_content = '';

            for ($i = 1; $i <= $loop_count; $i++) {
                $current_loop_content = $loop_template;

                foreach ($_POST as $key => $value) {
                    // Check if the key matches the dynamic input for the current loop item
                    if (strpos($key, 'rdynamic_' . $loop_name . '_' . $i . '_') === 0) {
                        $dynamic_name = substr($key, strlen('rdynamic_' . $loop_name . '_' . $i . '_'));

                        // Sanitize the input value
                        $sanitized_value = wp_kses_post($value);

                        // Replace the loop content placeholder with the sanitized value
                        $current_loop_content = preg_replace(
                            '/\{\{\{loop_content type=[\'"][^\'"]+[\'"] name=[\'"]' . preg_quote($dynamic_name, '/') . '[\'"] title=[\'"][^\'"]+[\'"]\}\}\}/',
                            $sanitized_value,
                            $current_loop_content
                        );
                    }
                }

                // Append the processed loop content
                $loop_content .= $current_loop_content;
            }

            // Replace the original loop match in the template content with the generated loop content
            $template_content = str_replace($loop_match, $loop_content, $template_content);
        }
    }

    return $template_content;
}

function store_post_meta($post_id)
{
    $meta_data = array();
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'rdynamic_') === 0) {
            $meta_data[$key] = wp_kses_post($value);
        }
    }
    

    $child_page = isset($_POST['child_of_rpages']) ? sanitize_text_field($_POST['child_of_rpages']) : '';
    $meta_data['rdynamic_template_id'] = intval($_POST['existing_page']);
    if($child_page!=="yes"){
    // Store additional meta
    $meta_data['rpage_category'] = sanitize_text_field($_POST['template_title']);
    }else{
        // Store child page category
        $meta_data['rpage_child_category'] = sanitize_text_field($_POST['template_title']);
        
        // Store parent slug to establish relationship for nested children
        $parent_slug = sanitize_text_field($_POST['parent_slug']);
        if (!empty($parent_slug)) {
            update_post_meta($post_id, 'child_page_of', $parent_slug);
        }
    }

    // Store all meta data in a single meta field
    update_post_meta($post_id, 'rdynamic_meta_data', $meta_data);
}

/**
 * Recursively get all parent slugs for a page
 * 
 * @param int $page_id The page ID
 * @return array Array of parent slugs
 */
function get_post_dynamic_meta($post_id) {
    return pmp_get_dynamic_meta($post_id);
}

// Render form for importing templates
if (!isset($_POST['import_template'])): ?>
    <form method="post" action="" id="import_template_form" style="display: none;">
        <h2>Choose Template</h2>
        <input type="hidden" name="child_page" value="" id="child_of_rpages_input">
        <input type="hidden" name="parent_id" value="" id="parent_id_template_input">
        <input type="hidden" name="parent_slug" value="" id="parent_slug_template_input">
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
    $child_of_page = $_POST['child_page'] ?? '';
    $parent_page_slug = $_POST['parent_slug'] ?? '';

    preg_match_all('/\{\{\{rdynamic_content type=[\'\"]([^\'\"]+)[\'\"] name=[\'\"]([^\'\"]+)[\'\"] title=[\'\"]([^\'\"]+)[\'\"]\}\}\}/', $template_content, $matches, PREG_SET_ORDER);

    $matches = array_filter($matches, function ($match) use ($template_content) {
        return !preg_match('/\{\{\{rdynamic_content_loop_start\}\}\}.*' . preg_quote($match[0], '/') . '.*\{\{\{rdynamic_content_loop_ends\}\}\}/s', $template_content);
    });

    $unique_matches = array_map('serialize', $matches);
    $unique_matches = array_unique($unique_matches);
    $matches = array_map('unserialize', $unique_matches);

    preg_match_all('/{{{rdynamic_content_loop_start name=[\'"]?(.*?)?[\'"]?}}}.*?{{{rdynamic_content_loop_ends name=[\'"]?\1[\'"]?}}}/s', $template_content, $loop_rmatches);

    $loop_count = isset($_POST['loop_count']) ? intval($_POST['loop_count']) : 1;

    $meta_data = isset($_POST['page_id']) ? get_post_dynamic_meta($_POST['page_id']) : [];

    ?>
    <style>
    /* Base styles for the form */
    #rcreate_page {
    margin: 0 auto;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: grid;
    grid-template-columns: repeat(2, 1fr) !important;
    justify-content: space-between;
    column-gap: 100px;
}

/* Form headings */
#rcreate_page h2 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
    grid-column: span 2;
}

#rcreate_page h3 {
    font-size: 20px;
    margin-top: 30px;
    margin-bottom: 10px;
    color: #555;
}

/* Input fields */
#rcreate_page input[type="text"],
#rcreate_page input[type="url"],
#rcreate_page input[type="hidden"],
#rcreate_page input[type="number"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
}

/* Labels */
#rcreate_page label {
    font-size: 14px;
    margin-bottom: 5px;
    display: block;
    color: #666;
}

/* Buttons */
#rcreate_page button {
    background-color: #007cba;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
    margin-right: 20px ;
}

#rcreate_page button:hover {
    background-color: #005a8c;
}
#rcreate_page textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-bottom: 15px;
    box-sizing: border-box; /* Ensures padding is included in width */
}

/* Responsive design */
@media (max-width: 600px) {
    #rcreate_page {
        padding: 15px;
    }

    #rcreate_page h2 {
        font-size: 22px;
    }

    #rcreate_page h3 {
        font-size: 18px;
    }

    #rcreate_page input[type="text"],
    #rcreate_page input[type="url"] {
        font-size: 14px;
    }

    #rcreate_page button {
        width: 100%;
        padding: 12px;
    }
}
</style>
    <form method="post" action="" id="rcreate_page">
        <h2><?php echo !isset($_POST['page_id']) ? 'Create Page' : 'Update Page'; ?></h2>
        <input type="hidden" name="child_of_rpages" value="<?php echo esc_attr($child_of_page); ?>">
        <div class="basic_fields" style="display: flex; gap: 20px;flex-wrap: wrap;">
        <div style="width: 48%;">
        <label for="parent_slug">Parent Page Slug:</label>
        <input type="text" id="parent_slug" name="parent_slug" value="<?php echo isset($_POST['parent_slug'])? $_POST['parent_slug'] : (isset($_POST['page_id']) ? esc_attr(get_post_field('post_name', wp_get_post_parent_id($_POST['page_id']))) : $parent_page_slug ?? ''); ?>">
        </div>
        <div style="width: 48%;">
        <label for="page_name">Page Name:</label>
        <input type="text" id="page_name" name="page_name" required value="<?php echo isset($_POST['page_id']) ? esc_attr(get_the_title($_POST['page_id'])) : ''; ?>">
        </div>
        <label for="page_slug">Page Slug:</label>
        <input type="text" id="page_slug" name="page_slug" required value="<?php echo isset($_POST['page_id']) ? esc_attr(get_post_field('post_name', $_POST['page_id'])) : ''; ?>">

        <?php foreach ($matches as $match): ?>
            <label for="<?php echo esc_attr($match[2]); ?>"><?php echo esc_html($match[3]); ?>:</label>
            <?php if (stripos($match[1], 'text_area') !== false): ?>
                <textarea id="<?php echo esc_attr($match[2]); ?>" name="rdynamic_<?php echo esc_attr($match[2]); ?>" required><?php echo esc_attr($meta_data['rdynamic_' . $match[2]] ?? ''); ?></textarea>
            <?php else: ?>
                <input type="<?php echo esc_attr($match[1]); ?>" id="<?php echo esc_attr($match[2]); ?>" name="rdynamic_<?php echo esc_attr($match[2]); ?>" required value="<?php echo esc_attr($meta_data['rdynamic_' . $match[2]] ?? ''); ?>">
            <?php endif; ?>
        <?php endforeach;
        echo "</div>";

        echo '<div class="all_loops">';
        foreach ($loop_rmatches[0] as $loop_match) {

            // Extract loop name and template
            preg_match('/{{{rdynamic_content_loop_start name=[\'"]?(.*?)?[\'"]?}}}(.*?){{{rdynamic_content_loop_ends name=\1}}}/s', $loop_match, $matches);
            echo '<div class="loop_container">';
            if (isset($matches[1]) && isset($matches[2])) {
                $loop_name = trim($matches[1], "'\"");
                $loop_template = $matches[2];
                $data = $meta_data;
                // determine how many stored items exist
                $unique_steps = [];
                // Loop through the keys and collect unique step titles
                foreach ($data as $key => $value) {
                    if (preg_match('/^rdynamic_'.$loop_name.'_(\d+)/', $key, $matches)) {
                        $unique_steps[$matches[1]] = $value; // Store unique titles indexed by step number
                    }
                }
                $stored_loop_count = count($unique_steps);
                $loop_count = isset($_POST['loop_count_' . $loop_name])
                    ? intval($_POST['loop_count_' . $loop_name])
                    : ($stored_loop_count !== 0 ? $stored_loop_count : 1);

                // Display loop heading
                echo '<h3>' . ucfirst(str_replace('_', ' ', $loop_name)) . ' Loop</h3>';
                echo '<div id="loop_items_' . esc_attr($loop_name) . '">';

                // Generate loop items based on count
                for ($i = 1; $i <= $loop_count; $i++) {
                    echo '<div class="loop_item">';
                    echo '<h4>Item ' . $i . '</h4>';

                    // Match loop content within the loop template
                    preg_match_all('/\{\{\{loop_content type=[\'"]([^\'"]+)[\'"] name=[\'"]([^\'"]+)[\'"] title=[\'"]([^\'"]+)[\'"]\}\}\}/', $loop_template, $loop_content_matches, PREG_SET_ORDER);
                    // Serialize the matches
                    $loop_content_matches = array_map('serialize', $loop_content_matches);
                    // Reverse the array to keep the last unique match
                    $loop_content_matches = array_reverse($loop_content_matches);
                    // Get unique matches
                    $unique_matches = array_unique($loop_content_matches);
                    // Reverse again to maintain original order (with the last unique matches first)
                    $unique_matches = array_reverse($unique_matches);
                    // Unserialize the unique matches
                    $loop_content_matches = array_map('unserialize', $unique_matches);

                    foreach ($loop_content_matches as $match) {
                        echo '<label for="rdynamic_' . esc_attr($loop_name) . '_' . $i . '_' . esc_attr($match[2]) . '">' . esc_html($match[3]) . ':</label>';
                        // Determine the value based on the conditions
                        $value = isset($data['rdynamic_' . esc_attr($loop_name) . '_' . $i . '_' . esc_attr($match[2])])
                            ? $data['rdynamic_' . esc_attr($loop_name) . '_' . $i . '_' . esc_attr($match[2])]
                            : ''; // Default to an empty string if neither exists
                        if (preg_match('/text_area|textarea|textArea|TextArea|text-area/i', $match[1])):
                            echo '<textarea id="rdynamic_' . esc_attr($loop_name) . '_' . $i . '_' . esc_attr($match[2]) . '" name="rdynamic_' . esc_attr($loop_name) . '_' . $i . '_' . esc_attr($match[2]) . '" required>' . esc_attr($value) . '</textarea>';
                        else:
                        echo '<input type="' . esc_attr($match[1]) . '" id="rdynamic_' . esc_attr($loop_name) . '_' . $i . '_' . esc_attr($match[2]) . '" name="rdynamic_' . esc_attr($loop_name) . '_' . $i . '_' . esc_attr($match[2]) . '" value="' . esc_attr($value) . '" required>';
                        endif;
                    }
                    echo '</div>'; // Close loop_item
                    ?>
                    <script>
                        document.querySelectorAll('.add_loop_item').forEach(button => {
                            button.addEventListener('click', function() {
                                let loopName = this.getAttribute('data-loop-name');
                                let loopCountField = document.getElementById('loop_count_' + loopName);
                                let loopCount = parseInt(loopCountField.value);
                                loopCount++;
                                loopCountField.value = loopCount;

                                let loopItems = document.getElementById('loop_items_' + loopName);
                                let newItem = document.createElement('div');
                                newItem.classList.add('loop_item');
                                newItem.innerHTML = `<h4>Item ${loopCount}</h4>
                            <?php foreach ($loop_content_matches as $loop_match): ?>
                                <label for="rdynamic_${loopName}_${loopCount}_<?php echo esc_attr($loop_match[2]); ?>"><?php echo esc_html($loop_match[3]); ?>:</label>
                                <input type="<?php echo esc_attr($loop_match[1]); ?>" id="rdynamic_${loopName}_${loopCount}_<?php echo esc_attr($loop_match[2]); ?>" name="rdynamic_${loopName}_${loopCount}_<?php echo esc_attr($loop_match[2]); ?>" required>
                            <?php endforeach; ?>`;

                                loopItems.appendChild(newItem);
                            });
                        });

                        document.querySelectorAll('.remove_loop_item').forEach(button => {
                            button.addEventListener('click', function() {
                                let loopName = this.getAttribute('data-loop-name');
                                let loopCountField = document.getElementById('loop_count_' + loopName);
                                let loopCount = parseInt(loopCountField.value);

                                if (loopCount > 1) {
                                    loopCount--;
                                    loopCountField.value = loopCount;
                                    let loopItems = document.getElementById('loop_items_' + loopName);
                                    loopItems.removeChild(loopItems.lastElementChild);
                                }
                            });
                        });
                    </script>
                    <?php
                }

                echo '</div>'; // Close loop_items
                echo '<input type="hidden" name="loop_count_' . esc_attr($loop_name) . '" id="loop_count_' . esc_attr($loop_name) . '" value="' . esc_attr($loop_count) . '">';
                echo '<button type="button" class="add_loop_item" data-loop-name="' . esc_attr($loop_name) . '">Add Item</button>';
                echo '<button type="button" class="remove_loop_item" data-loop-name="' . esc_attr($loop_name) . '">Remove Item</button>';
            }
            echo "</div>";
        }
        echo "</div>";
        ?>


        <input type="hidden" name="template_title" value="<?php echo esc_attr($_POST['template_title']); ?>">
        <input type="hidden" name="existing_page" value="<?php echo esc_attr($_POST['existing_page']); ?>">

        <?php if (isset($_POST['page_id'])): ?>
            <input type="hidden" name="page_id" value="<?php echo esc_attr($_POST['page_id']); ?>">
            <button type="submit" name="update_page">Update Page</button>
        <?php else: ?>
            <button type="submit" name="create_page">Create Page</button>
        <?php endif; ?>
    </form>
    
<?php endif; ?>