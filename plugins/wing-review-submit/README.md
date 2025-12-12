# Wing Review Submit Plugin

Frontend submission form block for new wing locations and reviews with geocoding integration.

## Plugin Responsibility

**Single Responsibility**: Register and render the `wing-review-submit/wing-review-submit` block + handle form submissions + geocoding

This plugin provides a modal form for users to submit new wing locations or reviews on existing locations. Submissions create pending content for admin approval.

## Block Details

- **Block Name**: `wing-review-submit/wing-review-submit`
- **Block Type**: Server-side rendered with frontend JavaScript
- **Category**: Widgets

## Features

- **Submission Form**
  - Modal dialog with form fields
  - Context-aware: shows different fields for new locations vs reviews
  - Star rating inputs for overall, sauce, and crispiness ratings
  - Location details fields (address, website, Instagram)
  - Wing count and total price for price per wing calculation

- **Geocoding Integration**
  - Server-side Nominatim API integration via theme
  - Address validation before submission
  - Coordinates stored for map display

- **Security**
  - Nonce verification on all REST endpoints
  - Honeypot spam prevention
  - Rate limiting (1 submission per IP per hour)
  - Input sanitization and output escaping

- **Admin Notifications**
  - Email notification on new submissions
  - Links to moderation screens

- **Data Source**
  - Requires theme's `Wing_Location_Meta` class
  - Requires theme's `geocode_address()` function

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Go to WordPress Admin → Plugins
3. Find "Wing Review Submit" and click "Activate"

Or install via zip file:
1. Download `wing-review-submit.zip`
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"
5. Click "Activate"

## Usage

### Placing the Block

1. Edit any page or post in the block editor
2. Add the "Wing Review Submit" block
3. The block displays a submission button
4. On frontend, button opens the modal form

### Submission Workflows

**New Location Submission** (from any page):
1. User clicks "Submit Wing Location" button
2. Modal shows all fields: location name, address, contact info, review
3. Address is geocoded for map coordinates
4. Creates pending `wing_location` post with initial review block
5. Admin approves via Posts → Wing Locations → Pending

**Review Submission** (from single wing_location page):
1. User clicks "Submit Review" button
2. Modal shows review fields only (name, email, ratings, text)
3. Creates pending comment on the location
4. Admin approves via Comments → Pending
5. wing-review plugin converts approved comment to block

### REST API Endpoints

**POST** `/wp-json/wing-review-submit/v1/submit`
- Handles form submissions
- Creates pending posts or comments
- Requires nonce in `X-WP-Nonce` header

**POST** `/wp-json/wing-review-submit/v1/geocode`
- Geocodes address via Nominatim
- Returns latitude/longitude coordinates
- Requires nonce in `X-WP-Nonce` header

## Development

### Setup

```bash
cd plugins/wing-review-submit
npm install
```

### Watch Mode

```bash
npm run start
```

Automatically rebuilds JavaScript/CSS when files change.

### Production Build

```bash
npm run build
```

Compiles assets for production.

### Linting

```bash
npm run lint:js
npm run format:js
```

## Production Build

Create a production-ready ZIP file:

```bash
chmod +x build.sh
./build.sh
```

Output: `build/wing-review-submit.zip`

## Architecture

### File Structure

```
plugins/wing-review-submit/
├── wing-review-submit.php (main plugin file, 514 lines)
├── src/
│   └── wing-review-submit/
│       ├── block.json
│       ├── edit.js (editor preview)
│       ├── index.js (block registration)
│       ├── frontend.js (modal/form handling)
│       ├── editor.scss
│       └── frontend.scss
├── build/
│   └── wing-review-submit/ (compiled assets)
├── package.json
├── build.sh
└── README.md
```

### Main Plugin File

The `wing-review-submit.php` file:
- Defines plugin constants (version, path, URL)
- Provides `get_meta_helper()` function for accessing theme metadata
- Provides `geocode_address()` wrapper for theme geocoding
- Registers the block with `register_block_type()`
- Registers REST API routes for submit and geocode
- Implements `render_callback()` for server-side rendering
- Implements `rest_submit_handler()` for form submissions
- Implements `rest_geocode_handler()` for address geocoding
- Implements `create_pending_location()` for new locations
- Implements `create_pending_review_comment()` for reviews
- Implements `send_admin_email()` for notifications

### Key Functions

```php
// Render the block
function render_callback() {
    // Output button and modal HTML
    // Pass REST config to frontend script
}

// Handle form submission
function rest_submit_handler($request) {
    // Validate honeypot
    // Check rate limit
    // Sanitize input
    // Create pending content
    // Send admin email
}

// Handle geocoding request
function rest_geocode_handler($request) {
    // Validate address
    // Call theme geocode function
    // Return coordinates
}

// Create new pending location
function create_pending_location($data) {
    // Create wing_location post with pending status
    // Add wing-location-details block
    // Add initial wing-review block
    // Store metadata via theme helper
}

// Create pending review comment
function create_pending_review_comment($post_id, $data) {
    // Create comment with pending status
    // Store rating metadata
    // wing-review plugin handles conversion on approval
}
```

## Hooks & Filters

The plugin uses standard WordPress hooks:

- `init` - Register block type
- `rest_api_init` - Register REST routes

No custom hooks or filters provided (single responsibility).

## Data Integration

### Reading from Theme Metadata

```php
$meta_helper = get_meta_helper();
if ($meta_helper) {
    $meta = $meta_helper::get_location_meta($post_id);
}
```

### Writing to Theme Metadata

For new locations, stores all location data:
- `wing_address`, `wing_latitude`, `wing_longitude`
- `wing_website`, `wing_instagram`
- `wing_average_rating`, `wing_review_count`
- `wing_average_ppw`, `wing_min_ppw`, `wing_max_ppw`

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Cluckin Chuck theme (required for data access and geocoding)

## Compatibility

- Requires cluckin-chuck theme (for metadata and geocoding)
- Works with wing-review plugin (converts approved comments to blocks)
- Works with wing-map-display plugin (geocoded locations appear on map)
- Works with wing-location-details plugin (displays location info)

## Security

The plugin implements:
- **Nonce Verification**: All REST endpoints require valid nonce
- **Rate Limiting**: 1 submission per IP per hour via transients
- **Honeypot**: Hidden field catches bots
- **Input Sanitization**: `sanitize_text_field()`, `sanitize_email()`, `esc_url_raw()`
- **Output Escaping**: `esc_html()`, `esc_attr()`, `esc_url()` on all output

## Support

For issues or questions:
- Author: Chris Huber
- Website: https://chubes.net
- GitHub: https://github.com/chubes4

## License

GPL v2 or later

---

**Version**: 0.1.2
**Last Updated**: 2025
