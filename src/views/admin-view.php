<?php
function render_admin_view() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Page Management System', 'page-management-plugin'); ?></h1>
        
        <button id="choose-template-button" class="button button-primary">
            <?php esc_html_e('Choose Template', 'page-management-plugin'); ?>
        </button>

        <div id="template-selection" style="display:none;">
            <form id="template-form">
                <label for="template-title"><?php esc_html_e('Template Title', 'page-management-plugin'); ?></label>
                <input type="text" id="template-title" name="template_title" required>

                <label for="existing-page"><?php esc_html_e('Select Existing Page', 'page-management-plugin'); ?></label>
                <select id="existing-page" name="existing_page" required>
                    <!-- Options will be populated dynamically -->
                </select>

                <button type="button" id="import-button" class="button button-secondary">
                    <?php esc_html_e('Import', 'page-management-plugin'); ?>
                </button>
            </form>
        </div>

        <div id="page-management">
            <h2><?php esc_html_e('Pages', 'page-management-plugin'); ?></h2>
            <div id="template-tabs">
                <!-- Tabs for each template title will be generated here -->
            </div>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Pages', 'page-management-plugin'); ?></th>
                        <th><?php esc_html_e('Choose Template', 'page-management-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Rows for pages and template buttons will be populated dynamically -->
                </tbody>
            </table>
            <button id="add-new-page-button" class="button button-primary">
                <?php esc_html_e('Add New Page', 'page-management-plugin'); ?>
            </button>
        </div>
    </div>
    <?php
}
add_action('admin_menu', function() {
    add_menu_page('Page Management', 'Page Management', 'manage_options', 'page-management', 'render_admin_view');
});
?>