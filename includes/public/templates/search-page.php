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
    <div id="emdr-ui-kit-container" style="height: 500px; width: 100%; display: flex;"></div>
</div>


<script>
    (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
        key: "<?php echo esc_attr($map_api_key); ?>",
        v: "weekly"
    });
</script>

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