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
    <div id="emdr-ui-kit-container" style="height: 500px; width: 100%; display: flex;"></div>
</div>


<script>
    (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
        key: "<?php echo esc_attr($map_api_key); ?>",
        v: "weekly"
    });
</script>

<script type="module">
    // Import the Places Library for PlaceDetailsElement and PlaceSearchElement
    (async () => {
        try {
            // Initialize logging first
            const diagnostics = document.getElementById('emdr-diagnostics');
            function logDiag(msg) {
                if (diagnostics) {
                    diagnostics.innerHTML += '<div>' + msg + '</div>';
                    diagnostics.style.display = 'block';
                }
                console.log(msg);
            }

            logDiag('Starting initialization...');

            const {PlaceDetailsElement, PlaceSearchElement} = await google.maps.importLibrary('places');
            logDiag('Places library loaded');

            const container = document.getElementById('emdr-ui-kit-container');
            if (!container) {
                logDiag('Error: Container element not found');
                return;
            }

            // Create PlaceSearchElement and PlaceDetailsElement
            logDiag('Creating UI components...');
            const searchEl = new PlaceSearchElement({
                type: ['health'],  // Filter to health-related businesses
                fields: ['name', 'formatted_address', 'place_id', 'types', 'business_status']
            });
            const detailsEl = new PlaceDetailsElement();

                    logDiag('Components created successfully');

                    // Set initial query and parameters
                    searchEl.query = 'emdr therapy';
                    searchEl.maxResults = 25;
                    searchEl.style.flex = '1';
                    detailsEl.style.flex = '1';

                    // Verify component properties
                    logDiag('SearchElement properties set - Query: ' + searchEl.query + ', MaxResults: ' + searchEl.maxResults);
            } catch (error) {
                    logDiag('Error initializing components: ' + error.message);
                    console.error('Full initialization error:', error);
            }

                    // Listen for search errors and results
                    searchEl.addEventListener('gmpx-search-error', (e) => {
                        logDiag('Search error: ' + (e.detail?.error?.message || JSON.stringify(e.detail)));
                        console.error('Full error details:', e.detail);
                    });

                    searchEl.addEventListener('gmpx-search-results-changed', (e) => {
                        const count = e.detail?.results?.length || 0;
                        logDiag('Search results changed: ' + count + ' results');
                        if (count === 0) {
                            logDiag('No results found for query: ' + searchEl.query);
                        } else {
                            // Log first result for debugging
                            const firstResult = e.detail.results[0];
                            logDiag('First result: ' + firstResult.formattedAddress);
                        }
                        console.log('Full results:', e.detail.results);
                    });

                    searchEl.addEventListener('gmpx-search-status-changed', (e) => {
                        logDiag('Search status: ' + (e.detail?.status || 'unknown'));
                        logDiag('Search request: ' + searchEl.query);
                        console.log('Full status details:', e.detail);
                    });

                    // When a place is selected, show details
                    searchEl.addEventListener('gmpx-place-selection-changed', (e) => {
                        detailsEl.place = e.detail.place;
                    });

                    // Add to container
                    container.appendChild(searchEl);
                    container.appendChild(detailsEl);

                    // Wire up location search bar
                    const form = document.getElementById('emdr-location-form');
                    const input = document.getElementById('emdr-location-input');
                    if (form && input) {
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();
                            const loc = input.value.trim();
                            if (loc.length > 0) {
                                // For diagnostics, allow toggling between 'emdr therapy in [location]' and just '[location]'
                                // Try different query formats for testing
                            if (loc.toLowerCase().includes('debug:')) {
                                // Debug mode - use exact query
                                let query = loc.replace('debug:', '').trim();
                                logDiag('DEBUG: Using raw query: ' + query);
                                searchEl.query = query;
                            } else if (loc.toLowerCase().includes('test:')) {
                                // Test mode - simple location search
                                let query = loc.replace('test:', '').trim();
                                logDiag('TEST: Searching for location: ' + query);
                                searchEl.query = query;
                            } else {
                                // Normal mode - search for EMDR therapy
                                let query = 'emdr therapy in ' + loc;
                                logDiag('SEARCH: Using query: ' + query);
                                searchEl.query = query;
                            }
                                logDiag('Searching for: ' + query);
                            }
                        });
                        input.addEventListener('keydown', function(e) {
                            if (e.key === 'Enter') {
                                form.dispatchEvent(new Event('submit'));
                            }
                        });
                    }
        } catch (err) {
            const diagnostics = document.getElementById('emdr-diagnostics');
            if (diagnostics) {
                diagnostics.textContent = 'Google Places UI Kit failed to load: ' + err;
                diagnostics.style.display = 'block';
            }
            console.error('Google Places UI Kit failed to load:', err);
        }
    })();
</script>
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

<!-- Google Places UI Kit loader is now handled by official Google loader above -->
<script>
// Remove custom JS if present
if (window.EMDRSettings) {
  // Optionally, you can add custom logic here to sync with WP if needed
}
</script>

<?php get_footer(); ?>