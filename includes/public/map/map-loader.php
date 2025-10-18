<?php
/**
 * Map Loader for EMDR Therapist Finder
 *
 * This file handles loading the map functionality on the public page.
 */

function emdr_load_map_scripts() {
    // Use saved plugin option for API key if present
    $options = get_option('emdr_options', []);
    $map_api_key = $options['map_api_key'] ?? '';

    if ( ! empty( $map_api_key ) ) {
        // Enqueue Google Maps API (no callback) - other scripts may poll for availability
        wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode($map_api_key) . '&libraries=places', array(), null, true);
        // Enqueue custom map script dependent on Google Maps
        wp_enqueue_script('emdr-map', plugin_dir_url(__FILE__) . 'js/map.js', array('google-maps'), null, true);
    }
}

add_action('wp_enqueue_scripts', 'emdr_load_map_scripts');

function emdr_render_map($latitude, $longitude) {
    ?>
    <div id="emdr-map" style="height: 400px; width: 100%;"></div>
    <script>
        function initMap() {
            var location = {lat: <?php echo esc_js($latitude); ?>, lng: <?php echo esc_js($longitude); ?>};
            var map = new google.maps.Map(document.getElementById('emdr-map'), {
                zoom: 10,
                center: location
            });
            var marker = new google.maps.Marker({
                position: location,
                map: map
            });
        }
        google.maps.event.addDomListener(window, 'load', initMap);
    </script>
    <?php
}
?>