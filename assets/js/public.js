document.addEventListener('DOMContentLoaded', function() {
    // Elements expected in the template
    const searchInput = document.getElementById('therapist-search');
    const searchButton = document.getElementById('search-button');
    const resultsList = document.getElementById('therapist-results');
    const mapContainer = document.getElementById('map');

    // Default to Los Angeles if geolocation not available
    const DEFAULT_LOCATION = { lat: 34.052235, lng: -118.243683 };
    let currentLocation = DEFAULT_LOCATION;
    let map;

    function initMap(center) {
        if (typeof google === 'undefined' || !mapContainer) return;
        map = new google.maps.Map(mapContainer, {
            zoom: 10,
            center: center || DEFAULT_LOCATION,
        });
    }

    // Try to get browser geolocation
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            currentLocation = { lat: position.coords.latitude, lng: position.coords.longitude };
            initMap(currentLocation);
            // set placeholder to the user's location if desired
            searchInput.placeholder = 'Find EMDR therapist in...';
        }, function(err) {
            // Use default LA
            initMap(DEFAULT_LOCATION);
        }, { timeout: 5000 });
    } else {
        initMap(DEFAULT_LOCATION);
    }

    function renderResults(items) {
        resultsList.innerHTML = '';
        if (!items || items.length === 0) {
            resultsList.innerHTML = '<li>No therapists found.</li>';
            return;
        }
        items.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item.name + (item.address ? ' — ' + item.address : '');
            resultsList.appendChild(li);
        });
    }

    function placeMarkers(locations) {
        if (!map || !locations) return;
        locations.forEach(loc => {
            new google.maps.Marker({ position: loc, map: map });
        });
    }

    async function doSearch(query) {
        try {
            const url = (EMDRSettings && EMDRSettings.restUrl ? EMDRSettings.restUrl : '/wp-json/') + 'therapists?query=' + encodeURIComponent(query) + '&lat=' + currentLocation.lat + '&lng=' + currentLocation.lng;
            const res = await fetch(url, { credentials: 'same-origin' });
            const data = await res.json();
            // Expect data.items (array) and data.locations (array of {lat,lng})
            renderResults(data.items || []);
            placeMarkers(data.locations || []);
        } catch (e) {
            console.error('Search error', e);
        }
    }

    // Wire up UI
    searchButton.addEventListener('click', function() {
        const q = searchInput.value || '';
        doSearch(q);
    });
});