// EMDR Therapist Finder - Public Frontend Script (no map)
console.log('EMDR public.js loaded');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired');
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

    // Elements
    const searchInput = document.getElementById('therapist-search') || document.getElementById('emdr-location-input');
    const searchButton = document.getElementById('search-button') || (document.querySelector('#emdr-location-form button[type="submit"]') || null);
    let resultsList = document.getElementById('therapist-results') || document.getElementById('emdr-results') || null;
    if (!resultsList) {
        const resultsContainer = document.getElementById('results-list');
        if (resultsContainer) {
            const ul = document.createElement('ul');
            ul.id = 'therapist-results';
            ul.className = 'results-list';
            resultsContainer.appendChild(ul);
            resultsList = ul;
        }
    }

    // UI rendering for merged NPI + Place items
    function initialsAvatar(name) {
        const initials = (name || '').split(/\s+/).map(s => s[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
        return initials || 'EM';
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
            li.className = 'emdr-card';
            const photo = item.photo ? `<img class="emdr-card-photo" src="${item.photo}" alt="${item.name}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
<div class="emdr-avatar" style="display:none;">${initialsAvatar(item.name)}</div>` : `<div class="emdr-avatar">${initialsAvatar(item.name)}</div>`;
            const nameLine = `${item.name || ''}${item.credentials ? ', ' + item.credentials : ''}`;
            const credLine = item.credentials ? `<div class="emdr-card-cred">${item.credentials}</div>` : '';
            
            // Parse address for city, state display
            const addrParts = (item.address || '').split(',');
            const city = addrParts.length > 1 ? addrParts[addrParts.length - 2].trim() : '';
            const state = addrParts.length > 0 ? addrParts[addrParts.length - 1].trim() : '';
            const locLine = (city && state) ? `${city}, ${state}` : (item.address || '');

            const phoneHtml = item.phone ? `<div class="emdr-card-phone">${item.phone}</div>` : '';
            const emailHtml = ''; // placeholder for future
            const websiteHtml = item.website ? `<a class="emdr-link" href="${item.website}" target="_blank" rel="noopener">Website</a>` : '';
            const attribution = item.photo ? `<div class="emdr-attrib">Photo from Google</div>` : '';
            
            const endorsedHtml = (item.rating && item.review_count) ? `<div class="emdr-endorsed">${item.review_count} Endorsed</div>` : '';
            const viewBtn = `<button class="emdr-btn-view">View</button>`;
            const emailBtn = `<button class="emdr-btn-email">Email</button>`;
            const descDefault = 'Are you looking for EMDR therapy support? Contact this provider for more information.';
            const descText = item.description || descDefault;

            li.innerHTML = `
                <div class="emdr-card-left">${photo}</div>
                <div class="emdr-card-main">
                    <div class="emdr-card-name-link">${nameLine}</div>
                    ${credLine}
                    ${endorsedHtml}
                    <div class="emdr-card-location"><span class="icon">📍</span> <span class="icon">🚌</span> ${locLine}</div>
                    ${phoneHtml}
                    <div class="emdr-card-desc">${descText}</div>
                    ${attribution}
                </div>
                <div class="emdr-card-right">
                    ${phoneHtml ? `<div class="emdr-card-phone-display">${item.phone}</div>` : ''}
                    ${emailBtn}
                    ${viewBtn}
                </div>
            `;
            resultsList.appendChild(li);
        });
    }

    // Attach Google Places Autocomplete if available
    (function attachAutocomplete(){
        try {
            const input = document.getElementById('emdr-location-input');
            if (input && typeof google !== 'undefined' && google.maps && google.maps.places) {
                const ac = new google.maps.places.Autocomplete(input, { types: ['geocode'] });
                ac.addListener('place_changed', function() {
                    const place = ac.getPlace();
                    const q = (place && place.formatted_address) ? place.formatted_address : (input.value || '');
                    // Trigger search immediately on selection
                    doSearch(q);
                });
            }
        } catch (e) { /* noop */ }
    })();

    // Spinner utility
    let spinnerEl = null;
    function showSpinner() {
        if (!spinnerEl) {
            spinnerEl = document.createElement('div');
            spinnerEl.id = 'emdr-spinner';
            spinnerEl.innerHTML = '<div class="emdr-spinner-circle"></div>';
            document.body.appendChild(spinnerEl);
        }
        spinnerEl.style.display = 'flex';
    }
    function hideSpinner() {
        if (spinnerEl) spinnerEl.style.display = 'none';
    }

    // Fetch
    async function doSearch(query) {
        try {
            showSpinner();
            const url = (EMDRSettings && EMDRSettings.restUrl ? EMDRSettings.restUrl : '/wp-json/') + 'therapists?location=' + encodeURIComponent(query);
            logDiag('Fetching: ' + url);
            const res = await fetch(url, { credentials: 'same-origin' });
            const contentType = res.headers.get('content-type') || '';
            if (!res.ok) {
                const text = await res.text();
                logDiag('HTTP error ' + res.status + ': ' + text.substring(0, 500));
                hideSpinner();
                return;
            }
            if (!contentType.includes('application/json')) {
                const text = await res.text();
                logDiag('Expected JSON but received: ' + text.substring(0, 1000));
                hideSpinner();
                return;
            }
            const data = await res.json();
            renderResults(data.items || []);
            hideSpinner();
        } catch (e) {
            console.error('Search error', e);
            hideSpinner();
        }
    }

    const form = document.getElementById('emdr-location-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const q = (searchInput && searchInput.value) ? searchInput.value : '';
            doSearch(q);
            return false;
        });
    }
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const q = (searchInput && searchInput.value) ? searchInput.value : '';
            doSearch(q);
            return false;
        });
    }
});