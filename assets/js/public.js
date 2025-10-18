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
        return `<div class="emdr-avatar">${initials || 'EM'}</div>`;
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
            const photo = item.photo ? `<img class="emdr-card-photo" src="${item.photo}" alt="${item.name}">` : initialsAvatar(item.name);
            const nameLine = `${item.name || ''}${item.credentials ? ', ' + item.credentials : ''}`;
            const ratingHtml = (item.rating ? `<div class="emdr-rating"><span class="stars" data-rating="${item.rating}"></span> <span class="num">${item.rating.toFixed(1)} (${item.review_count || 0})</span></div>` : '');
            const phoneHtml = item.phone ? `<a class="emdr-link" href="tel:${item.phone.replace(/[^\d\+]/g,'')}">${item.phone}</a>` : '';
            const websiteHtml = item.website ? `<a class="emdr-link" href="${item.website}" target="_blank" rel="noopener">Website</a>` : '';
            const attribution = item.photo ? `<div class="emdr-attrib">Photo from Google ${item.photo_attribution ? ' · ' + item.photo_attribution : ''}</div>` : '';
            const emdrBadge = `<div class="emdr-badge">${item.emdr_verified ? 'EMDR verified' : 'EMDR likely'}</div>`;
            li.innerHTML = `
                <div class="emdr-card-left">${photo}</div>
                <div class="emdr-card-main">
                    <div class="emdr-card-name">${nameLine}</div>
                    ${emdrBadge}
                    <div class="emdr-card-address">${item.address || ''}</div>
                    <div class="emdr-card-actions">
                        ${phoneHtml}
                        ${websiteHtml}
                    </div>
                    ${ratingHtml}
                    ${attribution}
                </div>
            `;
            resultsList.appendChild(li);
        });

        // Render stars from data-rating
        resultsList.querySelectorAll('.stars').forEach(el => {
            const r = parseFloat(el.getAttribute('data-rating') || '0');
            const full = Math.floor(r);
            const half = r - full >= 0.5;
            let stars = '';
            for (let i = 0; i < full; i++) stars += '★';
            if (half) stars += '☆';
            while (stars.length < 5) stars += '☆';
            el.textContent = stars;
        });
    }

    // Fetch
    async function doSearch(query) {
        try {
            const url = (EMDRSettings && EMDRSettings.restUrl ? EMDRSettings.restUrl : '/wp-json/') + 'therapists?location=' + encodeURIComponent(query);
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
            renderResults(data.items || []);
        } catch (e) {
            console.error('Search error', e);
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