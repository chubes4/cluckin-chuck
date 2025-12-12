# API Reference

This document provides technical details for developers integrating with the Cluckin Chuck wing location system.

## REST API Endpoints

All endpoints require WordPress REST API authentication via nonce in the `X-WP-Nonce` header.

### Submit Wing Location or Review

**Endpoint:** `POST /wp-json/wing-review-submit/v1/submit`

Submits a new wing location or review for an existing location.

#### Request Headers
- `X-WP-Nonce`: Valid WordPress nonce for REST API authentication
- `Content-Type`: `application/json`

#### Request Body (New Location)
```json
{
  "type": "location",
  "locationName": "Wing Palace",
  "address": "123 Main St, Anytown, USA",
  "website": "https://wingpalace.com",
  "instagram": "@wingpalace",
  "rating": 4.5,
  "sauceRating": 4,
  "crispinessRating": 5,
  "reviewText": "Amazing wings!",
  "saucesTried": "Buffalo, BBQ",
  "wingCount": 10,
  "totalPrice": 15.00
}
```

#### Request Body (Review for Existing Location)
```json
{
  "type": "review",
  "postId": 123,
  "reviewerName": "John Doe",
  "reviewerEmail": "john@example.com",
  "rating": 4.5,
  "sauceRating": 4,
  "crispinessRating": 5,
  "reviewText": "Great wings!",
  "saucesTried": "Buffalo, BBQ",
  "wingCount": 10,
  "totalPrice": 15.00
}
```

#### Response (Success)
```json
{
  "success": true,
  "message": "Submission received and pending approval.",
  "data": {
    "postId": 456,
    "status": "pending"
  }
}
```

#### Response (Error)
```json
{
  "success": false,
  "message": "Rate limit exceeded. Please try again later.",
  "code": "rate_limit_exceeded"
}
```

#### Error Codes
- `invalid_nonce`: Authentication failed
- `rate_limit_exceeded`: Too many submissions from this IP
- `honeypot_triggered`: Spam detection triggered
- `invalid_data`: Missing or invalid required fields
- `geocoding_failed`: Address could not be geocoded
- `creation_failed`: Failed to create post or comment

### Geocode Address

**Endpoint:** `POST /wp-json/wing-review-submit/v1/geocode`

Geocodes an address to latitude/longitude coordinates using OpenStreetMap Nominatim.

#### Request Headers
- `X-WP-Nonce`: Valid WordPress nonce for REST API authentication
- `Content-Type`: `application/json`

#### Request Body
```json
{
  "address": "123 Main St, Anytown, USA"
}
```

#### Response (Success)
```json
{
  "success": true,
  "data": {
    "lat": 40.7128,
    "lng": -74.0060
  }
}
```

#### Response (Error)
```json
{
  "success": false,
  "message": "Address not found.",
  "code": "geocoding_failed"
}
```

## Rate Limiting

- **Submissions**: 1 per IP address per hour
- **Geocoding**: 1 request per second (server-side enforcement)

## Authentication

All endpoints require a valid WordPress nonce. Obtain a nonce using:

```javascript
wp.apiFetch.use( wp.apiFetch.createNonceMiddleware( wpApiSettings.nonce ) );
```

Or manually:

```javascript
const nonce = wpApiSettings.nonce; // Available in WordPress admin
fetch('/wp-json/wing-review-submit/v1/submit', {
  method: 'POST',
  headers: {
    'X-WP-Nonce': nonce,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(data)
});
```

## Data Types

### Location Submission Fields
- `locationName`: string (required) - Restaurant name
- `address`: string (required) - Full street address
- `website`: string (optional) - Website URL
- `instagram`: string (optional) - Instagram handle
- `rating`: number (1-5, required) - Overall rating
- `sauceRating`: number (1-5, required) - Sauce quality rating
- `crispinessRating`: number (1-5, required) - Crispiness rating
- `reviewText`: string (required) - Review content
- `saucesTried`: string (optional) - Sauces tried
- `wingCount`: number (optional) - Number of wings ordered
- `totalPrice`: number (optional) - Total price paid

### Review Submission Fields
- `postId`: number (required) - ID of existing wing_location post
- `reviewerName`: string (required) - Reviewer's name
- `reviewerEmail`: string (required) - Reviewer's email
- `rating`: number (1-5, required) - Overall rating
- `sauceRating`: number (1-5, required) - Sauce quality rating
- `crispinessRating`: number (1-5, required) - Crispiness rating
- `reviewText`: string (required) - Review content
- `saucesTried`: string (optional) - Sauces tried
- `wingCount`: number (optional) - Number of wings ordered
- `totalPrice`: number (optional) - Total price paid

## Integration Examples

### JavaScript (WordPress Block Editor)

```javascript
import apiFetch from '@wordpress/api-fetch';

const submitData = {
  type: 'location',
  locationName: 'Wing Palace',
  address: '123 Main St, Anytown, USA',
  rating: 4.5,
  reviewText: 'Amazing wings!'
};

apiFetch({
  path: '/wing-review-submit/v1/submit',
  method: 'POST',
  data: submitData
}).then(response => {
  console.log('Submission successful:', response);
}).catch(error => {
  console.error('Submission failed:', error);
});
```

### PHP (Custom Plugin)

```php
$nonce = wp_create_nonce('wp_rest');
$url = rest_url('wing-review-submit/v1/submit');

$response = wp_remote_post($url, array(
  'headers' => array(
    'X-WP-Nonce' => $nonce,
    'Content-Type' => 'application/json'
  ),
  'body' => wp_json_encode(array(
    'type' => 'location',
    'locationName' => 'Wing Palace',
    'address' => '123 Main St, Anytown, USA',
    'rating' => 4.5,
    'reviewText' => 'Amazing wings!'
  ))
));

if (!is_wp_error($response)) {
  $data = json_decode(wp_remote_retrieve_body($response), true);
  // Handle response
}
```

## Notes

- All submissions require admin approval before appearing on the site
- Geocoding is cached for 24 hours to improve performance
- Rate limiting is enforced server-side to prevent abuse
- All input is sanitized and validated before processing