document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('emdr-search-form');
    const resultsContainer = document.getElementById('emdr-results');
    const mapContainer = document.getElementById('emdr-map');

    searchForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const searchQuery = document.getElementById('emdr-search-input').value;

        fetch(`/wp-json/emdr/v1/search?query=${encodeURIComponent(searchQuery)}`)
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
        if (data.providers.length > 0) {
            data.providers.forEach(provider => {
                const providerElement = document.createElement('div');
                providerElement.classList.add('provider');
                providerElement.innerHTML = `
                    <h3>${provider.name}</h3>
                    <p>${provider.address}</p>
                    <p>${provider.phone}</p>
                `;
                resultsContainer.appendChild(providerElement);
            });
        } else {
            resultsContainer.innerHTML = '<p>No therapists found.</p>';
        }
    }

    function loadMap(locations) {
        // Initialize the map and add markers for each location
        if (mapContainer) {
            // Map initialization logic goes here
        }
    }
});