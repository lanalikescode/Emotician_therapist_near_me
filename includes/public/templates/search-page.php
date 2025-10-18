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
        <!-- Diagnostics output (hidden until populated) -->
        <div id="emdr-diagnostics" style="display:none;border:1px solid #f2dede;background:#fff7f7;color:#8a1f1f;padding:10px;margin:10px 0;"></div>
    <div id="results-list" style="margin-top:16px;">
        <h2><?php esc_html_e('Results', 'emdr-therapist-finder'); ?></h2>
        <ul id="therapist-results" class="results-list"></ul>
    </div>
</div>

<script type="module">
    // Minimal module placeholder: real initialization happens in `assets/js/public.js`.
    // This module only logs that the template loaded and exposes a small diagnostic hook.
    const diagnostics = document.getElementById('emdr-diagnostics');
    function logDiag(msg) {
        if (diagnostics) {
            diagnostics.style.display = 'block';
            const d = document.createElement('div');
            d.textContent = msg;
            diagnostics.appendChild(d);
        }
        console.log(msg);
    }

    logDiag('Search page template loaded. Waiting for frontend script to initialize.');
</script>

<?php get_footer(); ?>