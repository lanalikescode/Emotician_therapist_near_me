<?php
class EMDR_Therapist_Finder_Public {
    public function __construct() {
        add_shortcode('emdr_finder', [$this, 'render_search_page']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('emdr-public-style', plugin_dir_url(__FILE__) . '../../assets/css/public.css');
        wp_enqueue_script('emdr-public-script', plugin_dir_url(__FILE__) . '../../assets/js/public.js', ['jquery'], null, true);
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