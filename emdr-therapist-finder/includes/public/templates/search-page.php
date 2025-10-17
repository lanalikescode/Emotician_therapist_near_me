<?php
/**
 * Template for the EMDR Therapist Finder Search Page
 */

get_header(); ?>

<div class="emdr-therapist-finder">
    <h1><?php esc_html_e('Find an EMDR Therapist', 'emdr-therapist-finder'); ?></h1>
    
    <div class="search-bar">
        <input type="text" id="therapist-search" placeholder="<?php esc_attr_e('Search for therapists...', 'emdr-therapist-finder'); ?>" />
        <button id="search-button"><?php esc_html_e('Search', 'emdr-therapist-finder'); ?></button>
    </div>

    <div id="map" style="height: 400px;"></div>

    <div id="results-list">
        <h2><?php esc_html_e('Results', 'emdr-therapist-finder'); ?></h2>
        <ul id="therapist-results"></ul>
    </div>
</div>

<script>
    document.getElementById('search-button').addEventListener('click', function() {
        const query = document.getElementById('therapist-search').value;
        fetch(`<?php echo esc_url(rest_url('emdr/v1/therapists?search=')); ?>${query}`)
            .then(response => response.json())
            .then(data => {
                const resultsList = document.getElementById('therapist-results');
                resultsList.innerHTML = '';
                data.forEach(therapist => {
                    const li = document.createElement('li');
                    li.textContent = therapist.name; // Assuming therapist object has a 'name' property
                    resultsList.appendChild(li);
                });
            })
            .catch(error => console.error('Error fetching therapists:', error));
    });
</script>

<?php get_footer(); ?>