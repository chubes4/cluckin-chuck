# Wing Location Details Plugin

Hero block displaying wing location details including address, phone, hours, services, and ratings.

## Plugin Responsibility

**Single Responsibility**: Register and render the `wing-location-details/wing-location-details` block

This plugin provides a hero block for displaying location information at the top of wing_location posts. All data comes from the theme's metadata system.

## Block Details

- **Block Name**: `wing-location-details/wing-location-details`
- **Block Type**: Server-side rendered
- **Category**: Widgets

## Features

- **Location Information Display**
  - Address with map pin icon
  - Phone number with click-to-call link
  - Website with external link
  - Operating hours with multi-line support
  - Price range indicator

- **Service Availability**
  - Takeout indicator
  - Delivery indicator
  - Dine-in indicator

- **Rating Display**
  - Star visualization (filled/empty stars)
  - Numeric average rating
  - Review count with proper pluralization

- **Data Source**
  - Requires theme's `Wing_Location_Meta` class
  - Reads all data from theme metadata

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Go to WordPress Admin → Plugins
3. Find "Wing Location Details" and click "Activate"

Or install via zip file:
1. Download `wing-location-details.zip`
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"
5. Click "Activate"

## Usage

### Adding to Location Posts

1. Edit a wing_location post in the block editor
2. Add the "Wing Location Details" block
3. Block automatically displays location metadata
4. No attributes to configure - data comes from post meta

### Automatic Insertion

When new locations are submitted via wing-review-submit, this block is automatically added to the post content.

### Displayed Information

The block reads and displays these meta fields:
- `wing_address` - Street address
- `wing_phone` - Phone number (with tel: link)
- `wing_website` - Website URL (with external link)
- `wing_hours` - Operating hours (supports newlines)
- `wing_price_range` - Price indicator ($, $$, $$$, $$$$)
- `wing_takeout` - Takeout available (boolean)
- `wing_delivery` - Delivery available (boolean)
- `wing_dine_in` - Dine-in available (boolean)
- `wing_average_rating` - Average rating (1-5)
- `wing_review_count` - Number of reviews

## Development

### Setup

```bash
cd plugins/wing-location-details
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

Output: `build/wing-location-details.zip`

## Architecture

### File Structure

```
plugins/wing-location-details/
├── wing-location-details.php (main plugin file, 165 lines)
├── src/
│   └── wing-location-details/
│       ├── block.json
│       ├── edit.js (editor preview)
│       ├── index.js (block registration)
│       ├── editor.scss
│       └── style.scss
├── build/
│   └── wing-location-details/ (compiled assets)
├── package.json
├── build.sh
└── README.md
```

### Main Plugin File

The `wing-location-details.php` file:
- Defines plugin constants (version, path, URL)
- Provides `get_meta_helper()` function for accessing theme metadata
- Registers the block with `register_block_type()`
- Implements `render_callback()` for server-side rendering

### Key Functions

```php
// Get theme meta helper
function get_meta_helper() {
    if (!class_exists('\\CluckinChuck\\Wing_Location_Meta')) {
        return null;
    }
    return '\\CluckinChuck\\Wing_Location_Meta';
}

// Render the block
function render_callback($attributes, $content) {
    // Get post ID
    // Verify wing_location post type
    // Get meta via theme helper
    // Render location details HTML
}
```

## Hooks & Filters

The plugin uses standard WordPress hooks:

- `init` - Register block type

No custom hooks or filters provided (single responsibility).

## Data Integration

### Reading from Theme Metadata

```php
$meta_helper = get_meta_helper();
if ($meta_helper) {
    $meta = $meta_helper::get_location_meta($post_id);
}
```

### Error Handling

If theme meta helper is unavailable, displays:
```html
<div class="wing-location-details-error">Location data unavailable.</div>
```

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Cluckin Chuck theme (required for data access)

## Compatibility

- Requires cluckin-chuck theme (for metadata access)
- Compatible with wing-review-submit plugin (auto-inserts block on new locations)
- Compatible with wing-review plugin (displays review count/ratings)
- Compatible with wing-map-display plugin (uses same location data)

## Security

The plugin implements:
- **Output Escaping**: `esc_html()`, `esc_attr()`, `esc_url()` on all output
- **Read-Only**: Block only reads data, no user input or saves
- **Type Checking**: Verifies post type before rendering

## Support

For issues or questions:
- Author: Chris Huber
- Website: https://chubes.net
- GitHub: https://github.com/chubes4

## License

GPL v2 or later

---

**Version**: 0.1.1
**Last Updated**: 2025
