document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('emdr-search-form');
    const resultsContainer = document.getElementById('emdr-results');
    const mapContainer = document.getElementById('emdr-map');

    searchForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const searchTerm = document.getElementById('emdr-search-input').value;

        fetch(`/wp-json/emdr/v1/search?query=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                displayResults(data);
                loadMap(data.locations);
            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });
    });

    function displayResults(data) {
        resultsContainer.innerHTML = '';
        if (data.results.length > 0) {
            data.results.forEach(therapist => {
                const therapistElement = document.createElement('div');
                therapistElement.classList.add('therapist-item');
                therapistElement.innerHTML = `
                    <h3>${therapist.name}</h3>
                    <p>${therapist.address}</p>
                    <p>${therapist.phone}</p>
                `;
                resultsContainer.appendChild(therapistElement);
            });
        } else {
            resultsContainer.innerHTML = '<p>No therapists found.</p>';
        }
    }

    function loadMap(locations) {
        // Initialize the map and add markers for each location
        const map = new google.maps.Map(mapContainer, {
            zoom: 10,
            center: locations[0] || { lat: 0, lng: 0 }
        });

        locations.forEach(location => {
            new google.maps.Marker({
                position: location,
                map: map
            });
        });
    }
});