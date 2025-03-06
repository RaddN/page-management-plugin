/**
 * Layout Switcher JavaScript
 * Handles the functionality for toggling between table and tree layouts
 */
jQuery(document).ready(function($) {
    // Toggle tree nodes open/close
    $(document).on('click', '.pmp-node-toggle', function() {
        var $node = $(this).closest('.pmp-tree-node');
        var $children = $node.siblings('.pmp-tree-children');
        
        if ($children.length) {
            $children.slideToggle(200);
            $node.toggleClass('collapsed');
            
            // Toggle the arrow icon
            var $icon = $(this).find('.dashicons');
            if ($node.hasClass('collapsed')) {
                $icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
            } else {
                $icon.removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
            }
        }
    });
    
    // Handle layout form submission with AJAX
    $('.pmp-layout-switcher form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: pmpData.ajaxUrl,
            type: 'POST',
            data: formData + '&action=pmp_switch_layout',
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
        location.reload();
    });
});