/**
 * Leaflet map initialization with wing location markers
 */
/* global L, wingMapData */
document.addEventListener( 'DOMContentLoaded', () => {
	const mapContainer = document.getElementById( 'wing-map' );

	if ( ! mapContainer ) {
		return;
	}

	const map = L.map( 'wing-map' ).setView( [ 39.8283, -98.5795 ], 4 );

	L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '© OpenStreetMap contributors',
		maxZoom: 19,
	} ).addTo( map );

	const wingIcon = L.divIcon( {
		html: '🍗',
		className: 'wing-emoji-marker',
		iconSize: [ 32, 32 ],
		iconAnchor: [ 16, 32 ],
		popupAnchor: [ 0, -32 ],
	} );

	const escapeHtml = ( value ) => {
		const element = document.createElement( 'div' );
		element.textContent = String( value ?? '' );
		return element.innerHTML;
	};

	const formatPrice = ( { minPpw, maxPpw } ) => {
		const minimum = Number( minPpw );
		const maximum = Number( maxPpw );

		if ( minimum > 0 && maximum > 0 && minimum !== maximum ) {
			return `$${ minimum.toFixed( 2 ) }–$${ maximum.toFixed(
				2
			) } / wing`;
		}

		const price = maximum > 0 ? maximum : minimum;
		return price > 0 ? `$${ price.toFixed( 2 ) } / wing` : '';
	};

	const buildPopup = ( location ) => {
		const rating = Number( location.rating );
		const roundedRating = Math.round( rating );
		const reviewCount = Number( location.reviewCount );
		const reviewLabel = `${ reviewCount } ${
			reviewCount === 1 ? 'review' : 'reviews'
		}`;
		const stars = `${ '★'.repeat( roundedRating ) }${ '☆'.repeat(
			5 - roundedRating
		) }`;
		const price = formatPrice( location );
		const directionsUrl = `https://www.google.com/maps/dir/?api=1&destination=${ encodeURIComponent(
			`${ location.lat },${ location.lng }`
		) }`;
		const image = location.imageUrl
			? `<img class="wing-popup__image" src="${ escapeHtml(
					location.imageUrl
			  ) }" alt="" loading="lazy">`
			: '';
		const ratingMarkup =
			rating > 0
				? `<div class="wing-popup__rating" aria-label="${ rating.toFixed(
						1
				  ) } out of 5 stars, ${ reviewLabel }">
				<span class="wing-popup__stars" aria-hidden="true">${ stars }</span>
				<strong>${ rating.toFixed( 1 ) }</strong>
				<span>(${ escapeHtml( reviewLabel ) })</span>
			</div>`
				: '<div class="wing-popup__unrated">No reviews yet</div>';

		return `
			<article class="wing-popup">
				${ image }
				<div class="wing-popup__body">
					<h3>${ escapeHtml( location.title ) }</h3>
					${ ratingMarkup }
					${
						price
							? `<div class="wing-popup__price">${ escapeHtml(
									price
							  ) }</div>`
							: ''
					}
					<p class="wing-popup__address">📍 ${ escapeHtml( location.address ) }</p>
					<div class="wing-popup__actions">
						<a href="${ escapeHtml(
							location.url
						) }" class="wing-popup__primary">View details</a>
						<a href="${ directionsUrl }" target="_blank" rel="noopener noreferrer">Directions ↗</a>
					</div>
				</div>
			</article>
		`;
	};

	wingMapData.locations.forEach( ( location ) => {
		const marker = L.marker( [ location.lat, location.lng ], {
			icon: wingIcon,
		} ).addTo( map );

		marker.bindPopup( buildPopup( location ), {
			maxWidth: 320,
			minWidth: 260,
		} );
	} );

	if ( wingMapData.locations.length > 0 ) {
		const bounds = L.latLngBounds(
			wingMapData.locations.map( ( loc ) => [ loc.lat, loc.lng ] )
		);
		map.fitBounds( bounds, { padding: [ 50, 50 ] } );
	}
} );
