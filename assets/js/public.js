document.addEventListener('DOMContentLoaded', function() {
    // Elements expected in the template (support old and new IDs)
    const searchInput = document.getElementById('therapist-search') || document.getElementById('emdr-location-input');
    const searchButton = document.getElementById('search-button') || (document.querySelector('#emdr-location-form button[type="submit"]') || null);
    const resultsList = document.getElementById('therapist-results') || document.getElementById('emdr-results') || document.getElementById('results-list') || null;
    const mapContainer = document.getElementById('map') || document.getElementById('emdr-ui-kit-container') || document.getElementById('emdr-map') || null;

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

    // Initialize map when Google Maps is ready or fallback to default
    function onGoogleMapsReady() {
        // Try browser geolocation first
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                currentLocation = { lat: position.coords.latitude, lng: position.coords.longitude };
                initMap(currentLocation);
                if (searchInput) {
                    try { searchInput.placeholder = 'Find EMDR therapist in...'; } catch (e) { logDiag('Unable to set placeholder: ' + e.message); }
                } else {
                    logDiag('searchInput element not found; skipping placeholder set');
                }
            }, function(err) {
                initMap(DEFAULT_LOCATION);
            }, { timeout: 5000 });
        } else {
            initMap(DEFAULT_LOCATION);
        }
    }

    if (typeof google !== 'undefined' && google.maps) {
        onGoogleMapsReady();
    } else {
        // If google maps is loaded asynchronously, wait for it
        // Some installs may not call init callback; try to poll for google.maps for a short while
        var gmPollAttempts = 0;
        var gmPoll = setInterval(function() {
            gmPollAttempts++;
            if (typeof google !== 'undefined' && google.maps) {
                clearInterval(gmPoll);
                onGoogleMapsReady();
            } else if (gmPollAttempts > 20) { // ~10 seconds
                clearInterval(gmPoll);
                initMap(DEFAULT_LOCATION);
            }
        }, 500);
    }

    let markers = [];

    function clearMarkers() {
        markers.forEach(m => m.setMap(null));
        markers = [];
    }

    function renderResults(items) {
        if (!resultsList) {
            logDiag('Results container not found; skipping renderResults');
            return;
        }
        resultsList.innerHTML = '';
        if (!items || items.length === 0) {
            resultsList.innerHTML = '<li>No therapists found.</li>';
            return;
        }
        items.forEach(item => {
            const li = document.createElement('li');
            li.className = 'emdr-result-item';
            const imgHtml = item.photo ? `<img src="${item.photo}" alt="${item.name}" class="emdr-result-photo"/>` : '';
            const phoneHtml = item.phone ? `<div class="emdr-result-phone">${item.phone}</div>` : '';
            const emailHtml = item.email ? `<div class="emdr-result-email">${item.email}</div>` : '';
            li.innerHTML = `
                <div class="emdr-result-left">${imgHtml}</div>
                <div class="emdr-result-main">
                    <div class="emdr-result-name">${item.name}</div>
                    <div class="emdr-result-address">${item.address || ''}</div>
                    ${phoneHtml}
                    ${emailHtml}
                </div>
            `;
            resultsList.appendChild(li);
        });
    }

    function placeMarkers(locations) {
        if (!map || !locations) return;
        clearMarkers();
        locations.forEach(loc => {
            const marker = new google.maps.Marker({ position: loc, map: map });
            markers.push(marker);
        });
    }

    // Diagnostics helper
    const diagnosticsEl = document.getElementById('emdr-diagnostics');
    function logDiag(msg) {
        if (diagnosticsEl) {
            diagnosticsEl.style.display = 'block';
            const d = document.createElement('div');
            d.textContent = msg;
            diagnosticsEl.appendChild(d);
        }
        console.log(msg);
    }

    // Global error handlers to surface runtime problems
    window.addEventListener('error', function(evt) {
        logDiag('Runtime error: ' + (evt.message || evt));
    });
    window.addEventListener('unhandledrejection', function(evt) {
        logDiag('Unhandled promise rejection: ' + (evt.reason && evt.reason.message ? evt.reason.message : JSON.stringify(evt.reason)));
    });

    async function doSearch(query) {
        try {
            // If the query looks like a location string, geocode it to lat/lng using Google Maps Geocoder when available
            let searchLat = currentLocation.lat;
            let searchLng = currentLocation.lng;
            if (query && typeof google !== 'undefined' && google.maps) {
                const geocoder = new google.maps.Geocoder();
                const geocodeResult = await new Promise((resolve) => {
                    geocoder.geocode({ address: query }, function(results, status) {
                        if (status === 'OK' && results[0]) {
                            resolve(results[0].geometry.location);
                        } else {
                            resolve(null);
                        }
                    });
                });
                if (geocodeResult) {
                    searchLat = geocodeResult.lat();
                    searchLng = geocodeResult.lng();
                    // center the map on the geocoded location
                    if (map) map.setCenter({ lat: searchLat, lng: searchLng });
                }
            }

            const url = (EMDRSettings && EMDRSettings.restUrl ? EMDRSettings.restUrl : '/wp-json/') + 'therapists?query=' + encodeURIComponent(query) + '&lat=' + searchLat + '&lng=' + searchLng + '&radius=50000';
            logDiag('Fetching: ' + url);
            const res = await fetch(url, { credentials: 'same-origin' });
            const contentType = res.headers.get('content-type') || '';
            if (!res.ok) {
                const text = await res.text();
                logDiag('HTTP error ' + res.status + ': ' + text.substring(0, 500));
                return;
            }
            if (!contentType.includes('application/json')) {
                const text = await res.text();
                logDiag('Expected JSON but received: ' + text.substring(0, 1000));
                return;
            }
            const data = await res.json();
            // Expect data.items (array) and data.locations (array of {lat,lng})
            renderResults(data.items || []);
            placeMarkers(data.locations || []);
        } catch (e) {
            console.error('Search error', e);
        }
    }

    // Wire up UI
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            e.preventDefault && e.preventDefault();
            const q = (searchInput && searchInput.value) ? searchInput.value : '';
            doSearch(q);
        });
    } else if (document.getElementById('emdr-location-form')) {
        document.getElementById('emdr-location-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('emdr-location-input');
            const q = (input && input.value) ? input.value : '';
            doSearch(q);
        });
    } else {
        logDiag('No search UI found (no button and no form).');
    }
});