<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get all pages created by the plugin
$pages = get_posts(array(
    'post_type' => 'page',
    'meta_key' => 'rpage_category',
    'meta_value' => 'your_template_title', // Replace with the actual template title if needed
    'numberposts' => -1
));

// Loop through each page and delete it
foreach ($pages as $page) {
    wp_delete_post($page->ID, true);
}

// Optionally, delete any options or settings related to the plugin
delete_option('your_plugin_option_name'); // Replace with actual option names as needed
?>