/**
 * Wing Review Submit Block - Frontend Modal and Form Logic
 */

/* global wingReviewSubmitData */

import './frontend.scss';

document.addEventListener( 'DOMContentLoaded', () => {
	const submitButton = document.querySelector( '.wing-review-submit-button' );
	if ( ! submitButton ) {
		return;
	}

	const modal = document.getElementById( 'wing-review-submit-modal' );
	const form = document.getElementById( 'wing-review-submit-form' );
	if ( ! modal || ! form ) {
		return;
	}

	const closeButton = modal.querySelector( '.wing-modal-close' );
	const overlay = modal.querySelector( '.wing-modal-overlay' );
	const addressField = document.getElementById( 'wing_address' );
	const latField = document.getElementById( 'wing_latitude' );
	const lngField = document.getElementById( 'wing_longitude' );
	const postIdField = document.getElementById( 'wing_post_id' );
	const isExistingPost = Boolean( postIdField && parseInt( postIdField.value, 10 ) > 0 );

	submitButton.addEventListener( 'click', ( event ) => {
		event.preventDefault();
		modal.classList.add( 'wing-modal-open' );
		document.body.style.overflow = 'hidden';
	} );

	const closeModal = () => {
		modal.classList.remove( 'wing-modal-open' );
		document.body.style.overflow = '';
		form.reset();
		clearMessages();

		const geocodeIndicator = document.getElementById( 'geocode-indicator' );
		if ( geocodeIndicator ) {
			geocodeIndicator.textContent = '';
			geocodeIndicator.className = 'geocode-indicator';
		}
	};

	closeButton.addEventListener( 'click', closeModal );
	overlay.addEventListener( 'click', closeModal );

	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key && modal.classList.contains( 'wing-modal-open' ) ) {
			closeModal();
		}
	} );

	if ( addressField ) {
		let geocodeTimeout;
		addressField.addEventListener( 'blur', () => {
			const address = addressField.value.trim();
			if ( ! address ) {
				return;
			}

			clearTimeout( geocodeTimeout );
			geocodeTimeout = setTimeout( () => geocodeAddress( address ), 500 );
		} );
	}

	function geocodeAddress( address ) {
		const geocodeIndicator = document.getElementById( 'geocode-indicator' );
		geocodeIndicator.textContent = 'Geocoding...';
		geocodeIndicator.className = 'geocode-indicator geocode-loading';

		fetch( `${ wingReviewSubmitData.restUrl }/geocode`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': wingReviewSubmitData.nonce,
			},
			body: JSON.stringify( { address } ),
		} )
			.then( ( response ) => response.json().then( ( data ) => ( { ok: response.ok, data } ) ) )
			.then( ( { ok, data } ) => {
				if ( ok && data.lat && data.lng ) {
					latField.value = data.lat;
					lngField.value = data.lng;
					geocodeIndicator.textContent = 'Location found';
					geocodeIndicator.className = 'geocode-indicator geocode-success';
				} else {
					geocodeIndicator.textContent = data.message || 'Could not find location';
					geocodeIndicator.className = 'geocode-indicator geocode-error';
				}
			} )
			.catch( ( error ) => {
				console.error( 'Geocoding error:', error );
				geocodeIndicator.textContent = 'Geocoding failed';
				geocodeIndicator.className = 'geocode-indicator geocode-error';
			} );
	}

	form.addEventListener( 'submit', ( event ) => {
		event.preventDefault();

		if ( ! validateForm() ) {
			return;
		}

		const formData = new FormData( form );
		const payload = {};
		formData.forEach( ( value, key ) => {
			payload[ key ] = value;
		} );

		const submitBtn = form.querySelector( 'button[type="submit"]' );
		const originalText = submitBtn.textContent;
		submitBtn.disabled = true;
		submitBtn.textContent = 'Submitting...';

		fetch( `${ wingReviewSubmitData.restUrl }/submit`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': wingReviewSubmitData.nonce,
			},
			body: JSON.stringify( payload ),
		} )
			.then( ( response ) => response.json().then( ( data ) => ( { ok: response.ok, data } ) ) )
			.then( ( { ok, data } ) => {
				if ( ok ) {
					showMessage(
						'success',
						data.message || 'Thank you! Your submission has been received and is pending review.'
					);
					setTimeout( () => {
						closeModal();
					}, 2000 );
				} else {
					showMessage( 'error', data.message || 'Submission failed. Please try again.' );
				}
			} )
			.catch( ( error ) => {
				console.error( 'Submission error:', error );
				showMessage( 'error', 'An error occurred. Please try again.' );
			} )
			.finally( () => {
				submitBtn.disabled = false;
				submitBtn.textContent = originalText;
			} );
	} );

	function validateForm() {
		clearMessages();

		const requiredFields = form.querySelectorAll( '[required]' );
		let isValid = true;

		requiredFields.forEach( ( field ) => {
			if ( field.type === 'radio' ) {
				const radioGroup = form.querySelectorAll( `[name="${ field.name }"]` );
				const isChecked = Array.from( radioGroup ).some( ( radio ) => radio.checked );
				if ( ! isChecked ) {
					isValid = false;
				}
			} else if ( ! field.value.trim() ) {
				isValid = false;
				field.classList.add( 'field-error' );
			} else {
				field.classList.remove( 'field-error' );
			}
		} );

		if ( ! isValid ) {
			showMessage( 'error', 'Please fill in all required fields.' );
			return false;
		}

		if ( ! isExistingPost && addressField ) {
			if ( addressField.value.trim() && ( ! latField.value || ! lngField.value ) ) {
				showMessage( 'error', 'Please wait for address to be geocoded or enter a valid address.' );
				return false;
			}
		}

		return true;
	}

	function showMessage( type, message ) {
		const messageContainer = document.getElementById( 'wing-form-messages' );
		const messageDiv = document.createElement( 'div' );
		messageDiv.className = `wing-form-message wing-form-${ type }`;
		messageDiv.textContent = message;
		messageContainer.appendChild( messageDiv );
	}

	function clearMessages() {
		const messageContainer = document.getElementById( 'wing-form-messages' );
		messageContainer.innerHTML = '';
		form.querySelectorAll( '.field-error' ).forEach( ( field ) => {
			field.classList.remove( 'field-error' );
		} );
	}
} );
