/**
 * Wing Submit Block - Frontend Modal and Form Logic
 */

import './frontend.scss';

document.addEventListener('DOMContentLoaded', () => {
	const submitButton = document.querySelector('.wing-submit-button');
	if (!submitButton) return;

	const modal = document.getElementById('wing-submit-modal');
	if (!modal) return;

	const form = document.getElementById('wing-submit-form');
	const closeButton = modal.querySelector('.wing-modal-close');
	const overlay = modal.querySelector('.wing-modal-overlay');
	const addressField = document.getElementById('wing_address');
	const latField = document.getElementById('wing_latitude');
	const lngField = document.getElementById('wing_longitude');
	const locationNameField = document.getElementById('wing_location_name');

	const postIdField = document.getElementById('wing_post_id');
	const isExistingPost = postIdField && postIdField.value;

	if (isExistingPost && locationNameField) {
		locationNameField.closest('.wing-form-field').style.display = 'none';
	}

	submitButton.addEventListener('click', (e) => {
		e.preventDefault();
		modal.classList.add('wing-modal-open');
		document.body.style.overflow = 'hidden';

		const locationDataAttr = submitButton.getAttribute('data-location-info');
		if (locationDataAttr) {
			try {
				const locationData = JSON.parse(locationDataAttr);
				preFillLocationFields(locationData);
			} catch (error) {
				console.error('Error parsing location data:', error);
			}
		}
	});

	const closeModal = () => {
		modal.classList.remove('wing-modal-open');
		document.body.style.overflow = '';
		form.reset();
		clearMessages();
	};

	closeButton.addEventListener('click', closeModal);
	overlay.addEventListener('click', closeModal);

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && modal.classList.contains('wing-modal-open')) {
			closeModal();
		}
	});

	let geocodeTimeout;
	addressField.addEventListener('blur', () => {
		const address = addressField.value.trim();
		if (!address) return;

		clearTimeout(geocodeTimeout);
		geocodeTimeout = setTimeout(() => geocodeAddress(address), 500);
	});

	function geocodeAddress(address) {
		const geocodeIndicator = document.getElementById('geocode-indicator');
		geocodeIndicator.textContent = 'Geocoding...';
		geocodeIndicator.className = 'geocode-indicator geocode-loading';

		fetch(wingSubmitData.ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({
				action: 'wing_geocode',
				address: address,
				nonce: wingSubmitData.nonce
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data.lat && data.data.lng) {
				latField.value = data.data.lat;
				lngField.value = data.data.lng;
				geocodeIndicator.textContent = 'Location found';
				geocodeIndicator.className = 'geocode-indicator geocode-success';
			} else {
				geocodeIndicator.textContent = 'Could not find location';
				geocodeIndicator.className = 'geocode-indicator geocode-error';
			}
		})
		.catch(error => {
			console.error('Geocoding error:', error);
			geocodeIndicator.textContent = 'Geocoding failed';
			geocodeIndicator.className = 'geocode-indicator geocode-error';
		});
	}

	form.addEventListener('submit', (e) => {
		e.preventDefault();

		if (!validateForm()) return;

		const formData = new FormData(form);
		formData.append('action', 'wing_submit');
		formData.append('nonce', wingSubmitData.nonce);

		const submitBtn = form.querySelector('button[type="submit"]');
		const originalText = submitBtn.textContent;
		submitBtn.disabled = true;
		submitBtn.textContent = 'Submitting...';

		fetch(wingSubmitData.ajaxUrl, {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				showMessage('success', data.data.message || 'Thank you! Your submission has been received and is pending review.');
				setTimeout(() => {
					closeModal();
				}, 2000);
			} else {
				showMessage('error', data.data || 'Submission failed. Please try again.');
			}
		})
		.catch(error => {
			console.error('Submission error:', error);
			showMessage('error', 'An error occurred. Please try again.');
		})
		.finally(() => {
			submitBtn.disabled = false;
			submitBtn.textContent = originalText;
		});
	});

	function validateForm() {
		clearMessages();

		const requiredFields = form.querySelectorAll('[required]');
		let isValid = true;

		requiredFields.forEach(field => {
			if (!field.value.trim()) {
				isValid = false;
				field.classList.add('field-error');
			} else {
				field.classList.remove('field-error');
			}
		});

		if (!isValid) {
			showMessage('error', 'Please fill in all required fields.');
		}

		if (addressField.value.trim() && (!latField.value || !lngField.value)) {
			showMessage('error', 'Please wait for address to be geocoded or enter a valid address.');
			isValid = false;
		}

		return isValid;
	}

	function showMessage(type, message) {
		const messageContainer = document.getElementById('wing-form-messages');
		const messageDiv = document.createElement('div');
		messageDiv.className = `wing-form-message wing-form-${type}`;
		messageDiv.textContent = message;
		messageContainer.appendChild(messageDiv);
	}

	function clearMessages() {
		const messageContainer = document.getElementById('wing-form-messages');
		messageContainer.innerHTML = '';
		form.querySelectorAll('.field-error').forEach(field => {
			field.classList.remove('field-error');
		});
	}

	function preFillLocationFields(data) {
		if (data.address) {
			addressField.value = data.address;
			addressField.setAttribute('readonly', true);
			addressField.classList.add('prefilled');
		}
		if (data.latitude) {
			latField.value = data.latitude;
		}
		if (data.longitude) {
			lngField.value = data.longitude;
		}

		if (data.phone) {
			const phoneField = document.getElementById('wing_phone');
			if (phoneField) {
				phoneField.value = data.phone;
				phoneField.setAttribute('readonly', true);
				phoneField.classList.add('prefilled');
			}
		}
		if (data.website) {
			const websiteField = document.getElementById('wing_website');
			if (websiteField) {
				websiteField.value = data.website;
				websiteField.setAttribute('readonly', true);
				websiteField.classList.add('prefilled');
			}
		}

		if (data.hours) {
			const hoursField = document.getElementById('wing_hours');
			if (hoursField) {
				hoursField.value = data.hours;
				hoursField.setAttribute('readonly', true);
				hoursField.classList.add('prefilled');
			}
		}
		if (data.priceRange) {
			const priceField = document.getElementById('wing_price_range');
			if (priceField) {
				priceField.value = data.priceRange;
				priceField.setAttribute('disabled', true);
				priceField.classList.add('prefilled');
			}
		}

		if (data.takeout) {
			const takeoutField = document.querySelector('input[name="wing_takeout"]');
			if (takeoutField) {
				takeoutField.checked = true;
				takeoutField.setAttribute('disabled', true);
			}
		}
		if (data.delivery) {
			const deliveryField = document.querySelector('input[name="wing_delivery"]');
			if (deliveryField) {
				deliveryField.checked = true;
				deliveryField.setAttribute('disabled', true);
			}
		}
		if (data.dineIn) {
			const dineInField = document.querySelector('input[name="wing_dine_in"]');
			if (dineInField) {
				dineInField.checked = true;
				dineInField.setAttribute('disabled', true);
			}
		}

		const geocodeIndicator = document.getElementById('geocode-indicator');
		if (geocodeIndicator && data.address) {
			geocodeIndicator.textContent = 'Location data from existing review';
			geocodeIndicator.className = 'geocode-indicator geocode-success';
		}
	}
});
