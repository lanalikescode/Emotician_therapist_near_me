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


<script>
    (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
        key: "<?php echo esc_attr($map_api_key); ?>",
        v: "weekly"
    });
</script>

<script type="module">
    // Import the Places Library for PlaceDetailsElement and PlaceSearchElement
    (async () => {
        const {PlaceDetailsElement, PlaceSearchElement} = await google.maps.importLibrary('places');
        // You can now use PlaceDetailsElement and PlaceSearchElement in your page
        // Example: dynamically create and insert a PlaceSearchElement
        const placeList = document.getElementById('emdr-place-list');
        if (placeList) {
            // Optionally, you can replace this with your own logic/UI
            // For now, just log that the library loaded
            console.log('Google Places UI Kit loaded:', {PlaceDetailsElement, PlaceSearchElement});
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