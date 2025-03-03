<?php

function latest_products_table_shortcode_without_filter($atts)
{
    // Get shortcode attributes
    $atts = shortcode_atts(
        array(
            'attributes'     => '', // JSON string for product attributes
            'per_page'       => 5, // Default number of products per page
            'orderby'        => 'date', // Default orderby
            'order'          => 'DESC', // Default order
            'pagination'     => 'true', // Enable or disable pagination
        ),
        $atts,
        'latest_products_table'
    );

    // Get the current page number from the query string
    $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;

    // Create a cache key based on shortcode attributes and current page
    $cache_key = 'latest_products_' . md5(serialize($atts) . $paged);
    $cache_file = plugin_dir_path(__FILE__) . 'cache/' . $cache_key . '.html';

    // Check if cached file exists and is less than 24 hours old
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 86400) {
        return file_get_contents($cache_file);
    }

    // Build tax_query array
    $tax_query = array();

    // Decode the JSON string for attributes
    if (!empty($atts['attributes'])) {
        $decoded_attributes = json_decode($atts['attributes'], true);
        if (is_array($decoded_attributes)) {
            foreach ($decoded_attributes as $attribute => $terms) {
                $terms_array = array_map('trim', explode(',', $terms));
                $tax_query[] = array(
                    'taxonomy' => 'pa_' . sanitize_text_field($attribute),
                    'field'    => 'slug',
                    'terms'    => $terms_array,
                );
            }
        }
    }

    // Query latest products
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => intval($atts['per_page']),
        'orderby'        => sanitize_text_field($atts['orderby']),
        'order'          => strtoupper($atts['order']),
        'paged'          => $paged,
        'tax_query'      => !empty($tax_query) ? $tax_query : array(),
    );

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<p>No products found.</p>';
    }

    // Start building the table
    $output = '<table border="1" cellpadding="5" cellspacing="0" style="width:100%;border-collapse:collapse;" class="featured-conferences-table">';
    $output .= '<thead><tr><th>Date</th><th>Product List</th><th>Venue</th></tr></thead><tbody>';

    while ($query->have_posts()) {
        $query->the_post();

        // Extract Date and Place from the short description
        $short_description = get_the_excerpt();
        $date = $place = 'N/A';

        $lines = explode("\n", $short_description);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'calendar-icon') !== false) {
                $date = strip_tags($line);
            } elseif (strpos($line, 'location-icon-3') !== false) {
                $place = strip_tags($line);
            }
        }

        // Add a row for the current product
        $output .= '<tr>';
        $output .= '<td>' . esc_html($date) . '</td>';
        $output .= '<td><a href="' . get_permalink() . '">' . get_the_title() . '</a></td>';
        $output .= '<td>' . esc_html($place) . '</td>';
        $output .= '</tr>';
    }

    $output .= '</tbody></table>';

    // Pagination logic
    if ($atts['pagination'] === 'true') {
        $total_pages = $query->max_num_pages;
        if ($total_pages > 1) {
            $output .= '<nav class="woocommerce-pagination" aria-label="Product Pagination">';
            $current_page = max(1, $paged);
            $output .= paginate_links(array(
                'total'   => $total_pages,
                'current' => $current_page,
                'format'  => '?paged=%#%',
                'show_all' => false,
                'type'    => 'list',
            ));
            $output .= '</nav>';
        }
    }

    // Reset post data
    wp_reset_postdata();

    // Cache the output to file
    if (!file_exists(plugin_dir_path(__FILE__) . 'cache')) {
        mkdir(plugin_dir_path(__FILE__) . 'cache', 0755, true);
    }
    file_put_contents($cache_file, $output);

    return $output;
}

add_shortcode('latest_products_table_without_filter', 'latest_products_table_shortcode_without_filter');