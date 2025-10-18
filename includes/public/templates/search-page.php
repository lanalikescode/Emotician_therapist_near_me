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
    <div id="emdr-diagnostics" style="color:#b00;background:#fff3f3;border:1px solid #fbb;padding:8px;margin-bottom:10px;display:none;"></div>
    <gmpx-api-loader key="<?php echo esc_attr($map_api_key); ?>" solution-channel="GMP_QB_locatorplus_v7_c" region="US">
        <gmpx-place-list id="emdr-place-list" max-results="25" query="emdr therapy" style="height: 500px; width: 100%; display: flex;">
            <gmpx-place-overview slot="overview"></gmpx-place-overview>
        </gmpx-place-list>
    </gmpx-api-loader>
</div>

<script type="module" src="https://cdn.jsdelivr.net/npm/@googlemaps/extended-component-library/dist/extended-component-library/extended-component-library.esm.js"></script>
<script>
// Remove custom JS if present
// Custom search: always search for 'emdr therapy in [location]'
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('emdr-location-form');
    const input = document.getElementById('emdr-location-input');
    const placeList = document.getElementById('emdr-place-list');
    const diagnostics = document.getElementById('emdr-diagnostics');
    if (form && input && placeList) {
        // Search on submit or Enter
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const loc = input.value.trim();
            if (loc.length > 0) {
                placeList.setAttribute('query', 'emdr therapy in ' + loc);
                diagnostics.style.display = 'none';
            } else {
                diagnostics.textContent = 'Please enter a location.';
                diagnostics.style.display = 'block';
            }
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                form.dispatchEvent(new Event('submit'));
            }
        });
    }
    // Diagnostics for UI Kit errors
    window.addEventListener('error', function(e) {
        diagnostics.textContent = 'Error: ' + (e.message || 'Unknown error');
        diagnostics.style.display = 'block';
    });
    // Optionally, auto-search user's city if available
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            // Use reverse geocoding if you want to auto-fill city
        });
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