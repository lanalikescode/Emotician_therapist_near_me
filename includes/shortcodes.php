<?php
// Shortcode for EMDR Therapist Finder

// Register the shortcode [emdr_finder]
function emdr_finder_shortcode($atts) {
    // Defensive dequeue: ensure public.js is not loaded
    add_action('wp_print_scripts', function() {
        wp_dequeue_script('emdr-public-script');
    }, 100);
    // Enqueue necessary scripts and styles
    wp_enqueue_style('emdr-public-style', plugin_dir_url(__FILE__) . '../assets/css/public.css');
    // Do NOT enqueue custom public.js as UI Kit will handle all UI

    // Start output buffering
    ob_start();
    
    // Include the search page template
    include plugin_dir_path(__FILE__) . 'public/templates/search-page.php';
    
    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('emdr_finder', 'emdr_finder_shortcode');
?>