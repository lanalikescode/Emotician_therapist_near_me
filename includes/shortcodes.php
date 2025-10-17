<?php
// Shortcode for EMDR Therapist Finder

// Register the shortcode [emdr_finder]
function emdr_finder_shortcode($atts) {
    // Enqueue necessary scripts and styles
    wp_enqueue_style('emdr-public-style', plugin_dir_url(__FILE__) . '../assets/css/public.css');
    wp_enqueue_script('emdr-public-script', plugin_dir_url(__FILE__) . '../assets/js/public.js', array('jquery'), null, true);

    // Start output buffering
    ob_start();
    
    // Include the search page template
    include plugin_dir_path(__FILE__) . 'public/templates/search-page.php';
    
    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('emdr_finder', 'emdr_finder_shortcode');
?>