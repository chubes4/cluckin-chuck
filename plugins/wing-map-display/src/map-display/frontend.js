/**
 * Leaflet map initialization with wing location markers
 */
document.addEventListener('DOMContentLoaded', () => {
	const mapContainer = document.getElementById('wing-map');

	if (!mapContainer) {
		return;
	}

	const map = L.map('wing-map').setView([39.8283, -98.5795], 4);

	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '© OpenStreetMap contributors',
		maxZoom: 19
	}).addTo(map);

	const wingIcon = L.divIcon({
		html: '🍗',
		className: 'wing-emoji-marker',
		iconSize: [32, 32],
		iconAnchor: [16, 32],
		popupAnchor: [0, -32]
	});

	wingMapData.locations.forEach(location => {
		const marker = L.marker([location.lat, location.lng], {
			icon: wingIcon
		}).addTo(map);

		const fullStars = '★'.repeat(location.rating);
		const emptyStars = '☆'.repeat(5 - location.rating);

		marker.bindPopup(`
			<div class="wing-popup">
				<h3>${location.title}</h3>
				<p>${location.address}</p>
				<p class="wing-rating">Rating: ${fullStars}${emptyStars}</p>
				<a href="${location.url}" class="wing-details-link">View Details →</a>
			</div>
		`);
	});

	if (wingMapData.locations.length > 0) {
		const bounds = L.latLngBounds(wingMapData.locations.map(loc => [loc.lat, loc.lng]));
		map.fitBounds(bounds, { padding: [50, 50] });
	}
});
