<?php
/**
 * Template for the EMDR Therapist Finder Search Page
 */

get_header(); ?>



<?php
$options = get_option('emdr_options', []);
$map_api_key = $options['map_api_key'] ?? '';
?>

<div class="emdr-therapist-finder">
        <h1><?php esc_html_e('Find an EMDR Therapist', 'emdr-therapist-finder'); ?></h1>
        <form id="emdr-location-form" style="margin-bottom:20px;">
                <input type="text" id="emdr-location-input" placeholder="Enter your city or location" style="padding:8px;width:250px;max-width:90%;" required />
                <button type="submit" style="padding:8px 16px;">Search</button>
        </form>
        <gmpx-api-loader key="<?php echo esc_attr($map_api_key); ?>" solution-channel="GMP_QB_locatorplus_v7_c" region="US">
                <gmpx-place-list id="emdr-place-list" max-results="25" style="height: 500px; width: 100%; display: flex;">
                        <gmpx-place-overview slot="overview"></gmpx-place-overview>
                </gmpx-place-list>
        </gmpx-api-loader>
</div>

<script type="module" src="https://unpkg.com/@googlemaps/extended-component-library@latest/dist/extended-component-library/extended-component-library.esm.js"></script>
<script>
// Remove custom JS if present
// Custom search: always search for 'emdr therapy in [location]'
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('emdr-location-form');
    const input = document.getElementById('emdr-location-input');
    const placeList = document.getElementById('emdr-place-list');
    if (form && input && placeList) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const loc = input.value.trim();
            if (loc.length > 0) {
                placeList.setAttribute('query', 'emdr therapy in ' + loc);
            }
        });
        // Optionally, auto-search user's city if available
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                // Use reverse geocoding if you want to auto-fill city
            });
        }
    }
});
</script>

<!-- Google Places UI Kit -->
<script type="module" src="https://unpkg.com/@googlemaps/extended-component-library@latest/dist/extended-component-library/extended-component-library.esm.js"></script>
<script>
// Remove custom JS if present
if (window.EMDRSettings) {
  // Optionally, you can add custom logic here to sync with WP if needed
}
</script>

<?php get_footer(); ?>