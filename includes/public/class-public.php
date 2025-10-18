<?php
class EMDR_Therapist_Finder_Public {
    public function __construct() {
        add_shortcode('emdr_finder', [$this, 'render_search_page']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts() {
        $plugin_base = plugin_dir_url( dirname(__FILE__, 2) );
        wp_enqueue_style('emdr-public-style', $plugin_base . 'assets/css/public.css');

        // Localize plugin settings for frontend JS
        $options = get_option( 'emdr_options', [] );
        $map_api_key = $options['map_api_key'] ?? '';
        $npi_api_key = $options['npi_api_key'] ?? '';

        // Load Google Maps JS only for Places Autocomplete (no map UI on frontend)
        if ( ! empty( $map_api_key ) ) {
            wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode($map_api_key) . '&libraries=places', [], null, true);
            wp_enqueue_script('emdr-public-script', $plugin_base . 'assets/js/public.js', ['jquery', 'google-maps'], null, true);
        } else {
            wp_enqueue_script('emdr-public-script', $plugin_base . 'assets/js/public.js', ['jquery'], null, true);
        }

        wp_localize_script('emdr-public-script', 'EMDRSettings', [
            'restUrl' => esc_url_raw( rest_url('emdr/v1/') ),
            'mapApiKey' => esc_attr( $map_api_key ),
            'npiApiKey' => esc_attr( $npi_api_key ),
        ]);
    }

    public function render_search_page() {
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/search-page.php';
        return ob_get_clean();
    }

    public function get_therapists($args = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'emdr_therapists';
        $query = "SELECT * FROM $table_name WHERE 1=1";

        if (!empty($args['location'])) {
            $query .= $wpdb->prepare(" AND location LIKE %s", '%' . $wpdb->esc_like($args['location']) . '%');
        }

        if (!empty($args['specialty'])) {
            $query .= $wpdb->prepare(" AND specialty LIKE %s", '%' . $wpdb->esc_like($args['specialty']) . '%');
        }

        return $wpdb->get_results($query);
    }
}
?>