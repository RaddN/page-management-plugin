<?php

class PageManager {
    public function __construct() {
        add_action('admin_post_import_page', [$this, 'import_page']);
        add_action('admin_post_create_page', [$this, 'create_page']);
    }

    public function import_page() {
        if (!isset($_POST['template_title']) || !isset($_POST['existing_page'])) {
            return;
        }

        $template_title = sanitize_text_field($_POST['template_title']);
        $existing_page = sanitize_text_field($_POST['existing_page']);

        // Load the existing page content
        $page_content = get_post($existing_page)->post_content;

        // Replace dynamic content placeholders
        $processed_content = $this->replace_dynamic_content($page_content);

        // Store the form data in post meta
        $post_id = wp_insert_post([
            'post_title' => $template_title,
            'post_content' => $processed_content,
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);

        if ($post_id) {
            update_post_meta($post_id, 'rpage_category', $template_title);
        }

        wp_redirect(admin_url('admin.php?page=page-management-plugin&imported=1'));
        exit;
    }

    public function create_page() {
        if (!isset($_POST['parent_slug']) || !isset($_POST['page_name']) || !isset($_POST['page_slug'])) {
            return;
        }

        $parent_slug = sanitize_text_field($_POST['parent_slug']);
        $page_name = sanitize_text_field($_POST['page_name']);
        $page_slug = sanitize_title($page_slug);

        // Create the new page
        $post_id = wp_insert_post([
            'post_title' => $page_name,
            'post_name' => $page_slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_parent' => url_to_postid($parent_slug),
        ]);

        if ($post_id) {
            // Store the form data in post meta
            update_post_meta($post_id, 'rpage_category', $_POST['template_title']);
        }

        wp_redirect(admin_url('admin.php?page=page-management-plugin&created=1'));
        exit;
    }

    private function replace_dynamic_content($content) {
        // Replace {{{rdynamic_content}}} placeholders with actual values
        return preg_replace_callback('/{{{rdynamic_content type="([^"]+)" name="([^"]+)" title="([^"]+)"}}}/', function($matches) {
            // Here you would retrieve the actual content based on type and name
            return $this->get_dynamic_content($matches[2]);
        }, $content);
    }

    private function get_dynamic_content($name) {
        // Logic to retrieve dynamic content based on the name
        // This is a placeholder; implement your logic here
        return 'Dynamic content for ' . $name;
    }
}