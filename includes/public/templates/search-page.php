<?php
/**
 * Template for the EMDR Therapist Finder Search Page
 */

get_header(); ?>


<div class="emdr-therapist-finder">
    <h1><?php esc_html_e('Find an EMDR Therapist', 'emdr-therapist-finder'); ?></h1>
    <gmpx-api-loader key="<?php echo esc_attr(EMDRSettings['mapApiKey'] ?? ''); ?>" solution-channel="GMP_QB_locatorplus_v7_c" region="US">
        <gmpx-place-list style="height: 500px; width: 100%; display: flex;">
            <gmpx-place-overview slot="overview"></gmpx-place-overview>
        </gmpx-place-list>
    </gmpx-api-loader>
</div>

<!-- Google Places UI Kit -->
<script type="module" src="https://unpkg.com/@googlemaps/extended-component-library@latest/dist/extended-component-library/extended-component-library.esm.js"></script>
<script>
// Remove custom JS if present
if (window.EMDRSettings) {
  // Optionally, you can add custom logic here to sync with WP if needed
}
</script>

<?php get_footer(); ?>