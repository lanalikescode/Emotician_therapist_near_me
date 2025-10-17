<?php
/**
 * Template for the EMDR Therapist Finder Search Page
 */

get_header(); ?>

<div class="emdr-therapist-finder">
    <h1><?php esc_html_e('Find an EMDR Therapist', 'emdr-therapist-finder'); ?></h1>
    
    <div class="search-bar">
        <input type="text" id="therapist-search" placeholder="<?php esc_attr_e('Find EMDR therapist in...', 'emdr-therapist-finder'); ?>" />
        <button id="search-button"><?php esc_html_e('Search', 'emdr-therapist-finder'); ?></button>
    </div>

    <div id="map" style="height: 400px;"></div>

    <div id="results-list">
        <h2><?php esc_html_e('Results', 'emdr-therapist-finder'); ?></h2>
        <ul id="therapist-results"></ul>
    </div>
</div>

<!-- frontend behavior is handled by assets/js/public.js which uses localized EMDRSettings object -->

<?php get_footer(); ?>