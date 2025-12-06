# Wing Submit Plugin

Submission form block for new wing locations and reviews with geocoding.

## Plugin Responsibility

**Single Responsibility**: Register and render the `wing-submit/wing-submit` block

This plugin provides a form block for users to submit new wing locations or reviews for existing locations. It handles geocoding via Nominatim API, implements rate limiting and spam prevention, and saves submissions securely with proper validation.

## Block Details

- **Block Name**: `wing-submit/wing-submit`
- **Block Type**: Server-side rendered
- **Category**: Forms

## Features

- **Submission Form**
  - New location submission (creates wing_location post)
  - Review submission on existing location (creates comment)
  - Star ratings for overall quality, sauce, crispiness
  - Location details: address, phone, website, hours, price range, services

- **Geocoding Integration**
  - Nominatim API (OpenStreetMap)
  - Server-side only (never client-side)
  - Auto-fills latitude/longitude from address
  - Compliant with Nominatim rate limits (1 request/second)

- **Security Features**
  - Nonce verification on form submission
  - Input sanitization by field type
  - Output escaping on display
  - Honeypot spam prevention
  - Rate limiting (1 review per IP per hour)
  - Form validation

- **Data Handling**
  - Maps form input to theme metadata structure
  - Fallback data retrieval from wing-review blocks
  - Preserves location details across submissions
  - Handles new vs. existing location logic

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Go to WordPress Admin → Plugins
3. Find "Wing Submit" and click "Activate"

Or install via zip file:
1. Download `wing-submit.zip`
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"
5. Click "Activate"

## Usage

### Adding the Form Block

1. Create or edit a page/post in the block editor
2. Click "+" to add a new block
3. Search for "Wing Submit"
4. Insert the "Wing Submit" block
5. The form will be displayed to users

### Form Fields

The form includes:

**For New Location**:
- Location name (required)
- Address (required)
- Geocode button (auto-fills coordinates)
- Phone, website, email
- Hours, price range
- Services: takeout, delivery, dine-in

**For Review**:
- Location selection (dropdown)
- Overall rating (1-5)
- Sauce quality rating (1-5)
- Crispiness rating (1-5)
- Review text (textarea)

**Hidden Fields**:
- Honeypot field (spam prevention)

### Form Submission

1. User fills out form
2. JavaScript validates input
3. Form submitted to REST API endpoint
4. Server validates nonce + honeypot
5. Server checks rate limiting
6. Server geocodes address (if provided)
7. Data saved to database:
   - New location: Creates wing_location post
   - Review: Creates comment with metadata
8. Response returned to user

## Development

### Setup

```bash
cd plugins/wing-submit
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

Output: `build/wing-submit.zip`

## Architecture

### File Structure

```
plugins/wing-submit/
├── wing-submit.php (main plugin file)
├── src/
│   └── wing-submit/
│       ├── block.json
│       ├── edit.js (editor preview)
│       ├── index.js (block registration)
│       ├── editor.scss
│       └── frontend.js (form handling)
├── build/
│   └── wing-submit/ (compiled assets)
├── package.json
├── build.sh
└── README.md
```

### Main Plugin File

The `wing-submit.php` file:
- Defines plugin constants (version, path, URL)
- Provides `get_meta_helper()` for theme metadata access
- Provides `maybe_include_theme_meta_helper()` to load theme class if available
- Provides `map_meta_input()` to convert form data to metadata structure
- Provides `get_location_info_for_post()` to retrieve existing location data
- Registers the block with `register_block_type()`
- Implements `render_callback()` for server-side rendering
- Handles REST endpoints for form submission
- Implements geocoding via `geocode_address()`
- Implements rate limiting checks
- Implements nonce + honeypot verification

## Security Implementation

### Nonce Verification

```php
// Check nonce on form submission
wp_verify_nonce($_REQUEST['_wpnonce'], 'wing_submit_nonce');
```

### Input Sanitization

```php
// Sanitize by field type
$address = sanitize_text_field($_POST['address']);
$latitude = floatval($_POST['latitude']);
$website = esc_url_raw($_POST['website']);
$email = sanitize_email($_POST['email']);
$phone = sanitize_text_field($_POST['phone']);
$takeout = (bool) ($_POST['takeout'] ?? false);
```

### Output Escaping

```php
// Escape on display
echo esc_html($location_name);
echo esc_attr($post_id);
echo esc_url($website_url);
```

### Rate Limiting

```php
// Check: 1 review per IP per hour
$user_ip = $_SERVER['REMOTE_ADDR'];
$last_submission = get_transient('wing_submit_' . $user_ip);
if ($last_submission) {
    wp_send_json_error(['message' => 'Please wait before submitting another review']);
}
// Set transient for 1 hour
set_transient('wing_submit_' . $user_ip, time(), HOUR_IN_SECONDS);
```

### Honeypot Spam Prevention

```php
// Hidden field that should be empty
if (!empty($_POST['wing_submit_website_confirm'])) {
    // Reject silently - bot filled hidden field
    wp_send_json_success();
}
```

## Geocoding Service

### Nominatim API Integration

```php
function geocode_address($address) {
    $encoded = urlencode($address);
    $url = "https://nominatim.openstreetmap.org/search?q={$encoded}&format=json&limit=1";
    
    $response = wp_remote_get($url, [
        'headers' => [
            'User-Agent' => 'WingSubmit/0.1.0 (https://chubes.net)'
        ]
    ]);
    
    if (is_wp_error($response)) {
        return null;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!empty($body[0])) {
        return [
            'latitude' => floatval($body[0]['lat']),
            'longitude' => floatval($body[0]['lon'])
        ];
    }
    
    return null;
}
```

### Rate Limit Compliance

- 1 request per second (enforced server-side)
- User-Agent header required: `WingSubmit/0.1.0 (https://chubes.net)`
- Server-side only - never make requests from client

### Error Handling

- Invalid address: Return error, require manual entry
- API failure: Graceful degradation
- No fallback coordinates (prevents bad data)

## Data Integration

### Theme Metadata Mapping

```php
function map_meta_input($data) {
    return [
        'wing_address' => $data['address'],
        'wing_latitude' => $data['latitude'],
        'wing_longitude' => $data['longitude'],
        'wing_phone' => $data['phone'],
        'wing_website' => $data['website'],
        'wing_hours' => $data['hours'],
        'wing_price_range' => $data['price_range'],
        'wing_takeout' => $data['takeout'],
        'wing_delivery' => $data['delivery'],
        'wing_dine_in' => $data['dine_in'],
        'wing_average_rating' => floatval($data['rating']),
        'wing_review_count' => 1,
    ];
}
```

### Reading Location Data

```php
function get_location_info_for_post($post_id) {
    $meta_helper = get_meta_helper();
    
    if ($meta_helper) {
        return $meta_helper::get_location_meta($post_id);
    }
    
    // Fallback: read from first wing-review block
    $blocks = parse_blocks(get_post_field('post_content', $post_id));
    // ... extract and return review data
}
```

## Hooks & Filters

The plugin uses standard WordPress hooks:

- `plugins_loaded` - Load theme meta helper if available
- `init` - Register block type

No custom hooks or filters provided (single responsibility).

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Cluckin Chuck theme (for optimal data access, but not required)

## Compatibility

- Works with cluckin-chuck theme (reads/updates theme metadata)
- Compatible with wing-review plugin (provides comment source)
- Compatible with wing-map-display plugin (displays submitted locations)

## REST API

The plugin registers REST endpoints for form submission (handled in main plugin file). Endpoints are protected with nonce verification and input validation.

## Support

For issues or questions:
- Author: Chris Huber
- Website: https://chubes.net
- GitHub: https://github.com/chubes4

## License

GPL v2 or later

---

**Version**: 0.1.0
**Last Updated**: 2025
