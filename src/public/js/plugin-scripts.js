document.addEventListener('DOMContentLoaded', function() {
    const chooseTemplateButton = document.getElementById('choose-template-button');
    const importButton = document.getElementById('import-button');
    const createPageButton = document.getElementById('create-page-button');
    const templateForm = document.getElementById('template-form');
    const pageList = document.getElementById('page-list');
    const templateTitleInput = document.getElementById('template-title');
    const existingPageSelect = document.getElementById('existing-page-select');

    chooseTemplateButton.addEventListener('click', function() {
        templateForm.style.display = 'block';
    });

    importButton.addEventListener('click', function() {
        const templateTitle = templateTitleInput.value;
        const existingPage = existingPageSelect.value;

        if (templateTitle && existingPage) {
            // Logic to handle the import of the existing page
            // This could involve an AJAX request to the server to fetch the page content
            // and populate the form fields for creating a new page
        } else {
            alert('Please fill in all fields.');
        }
    });

    createPageButton.addEventListener('click', function() {
        const parentSlug = document.getElementById('parent-page-slug').value;
        const pageName = document.getElementById('page-name').value;
        const pageSlug = document.getElementById('page-slug').value;

        if (parentSlug && pageName && pageSlug) {
            // Logic to create a new page
            // This could involve an AJAX request to the server to create the page
            // and replace the {{{rdynamic_content}}} placeholders with actual content
        } else {
            alert('Please fill in all fields.');
        }
    });

    // Function to dynamically update the page list based on the selected template
    function updatePageList(templateTitle) {
        // Logic to fetch and display pages associated with the selected template
    }
});


jQuery(document).ready(function($) {
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var tabId = $(this).attr('href');

        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');
        // Add active class to the clicked tab
        $(this).addClass('nav-tab-active');

        // Hide all tab contents
        $('.tab-content').hide();
        // Show the selected tab content
        $(tabId).show();
    });

    $('.choose_templates').on('click', function(e) {
        e.preventDefault();
        $('#import_template_form').show();
        $('#page-management').hide();
    });
});
