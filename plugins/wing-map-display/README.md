# Wing Map Display Plugin

Interactive Leaflet map block displaying all wing locations with ratings and reviews.

## Plugin Responsibility

**Single Responsibility**: Register and render the `wing-map/map-display` block

This plugin provides an interactive map visualization for wing locations. It queries published wing locations and displays them on an interactive Leaflet map with markers, popups, and auto-fitting bounds.

## Block Details

- **Block Name**: `wing-map/map-display`
- **Block Type**: Server-side rendered
- **Category**: Widgets

## Features

- **Interactive Leaflet Map**
  - Built with Leaflet.js v1.9.4 (CDN)
  - OpenStreetMap tiles
  - Custom chicken wing marker icons
  - Click popups with location details

- **Auto-Fitting Bounds**
  - Automatically adjusts zoom and center
  - Shows all locations in view
  - 50px padding around markers

- **Location Display**
  - Location title
  - Street address
  - Star rating visualization
  - Link to full location post

- **Rating Integration**
  - Shows average rating from theme metadata
  - Displays review count
  - Updates automatically when reviews are approved

- **Data Source**
  - Primary: Theme's `Wing_Location_Meta` class
  - Fallback: Wing-review blocks (if theme unavailable)
  - Handles missing data gracefully (skips locations without coordinates)

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Go to WordPress Admin → Plugins
3. Find "Wing Map Display" and click "Activate"

Or install via zip file:
1. Download `wing-map-display.zip`
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"
5. Click "Activate"

## Usage

### Adding the Map Block

1. Create or edit a page/post in the block editor
2. Click "+" to add a new block
3. Search for "Wing Map"
4. Insert the "Wing Map Display" block
5. The map will automatically display all published wing locations

### Map Behavior

- Map centers on USA (39.8283, -98.5795) with zoom level 4 by default
- When locations are added, the map automatically adjusts to fit all markers
- Click a marker to view location details popup
- Click "View Details" link in popup to navigate to full location post

## Data Integration

### Reading from Theme Metadata

The plugin checks for `CluckinChuck\Wing_Location_Meta`:

```php
$meta_helper = get_meta_helper();
if ($meta_helper) {
    $meta = $meta_helper::get_location_meta($post_id);
    $latitude = $meta['wing_latitude'];
    $longitude = $meta['wing_longitude'];
    $address = $meta['wing_address'];
    $avg_rating = $meta['wing_average_rating'];
    $review_count = $meta['wing_review_count'];
}
```

### Fallback to Wing-Review Blocks

If theme metadata is unavailable, the plugin reads from wing-review blocks:

```php
$blocks = parse_blocks($post->post_content);
$wing_reviews = array_filter($blocks, function($b) {
    return 'wing-map/wing-review' === ($b['blockName'] ?? '');
});
```

Uses the first wing-review block for location coordinates and calculates average rating from all review blocks.

## Development

### Setup

```bash
cd plugins/wing-map-display
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

Output: `build/wing-map-display.zip`

## Library Details

### Leaflet.js v1.9.4

- **CDN**: `https://unpkg.com/leaflet@1.9.4/dist/leaflet.js`
- **CSS**: `https://unpkg.com/leaflet@1.9.4/dist/leaflet.css`
- **Why**: Open source, no API key required, fully customizable

The plugin enqueues Leaflet from CDN on the frontend. The JavaScript initialization:
- Creates map with default USA center
- Adds custom wing marker icons
- Builds location markers from queried data
- Implements auto-fitting bounds
- Adds click handlers for popups

## Architecture

### File Structure

```
plugins/wing-map-display/
├── wing-map-display.php (main plugin file)
├── src/
│   └── map-display/
│       ├── block.json
│       ├── edit.js (editor preview)
│       ├── index.js (block registration)
│       ├── editor.scss
│       └── frontend.js (Leaflet initialization)
├── build/
│   └── map-display/ (compiled assets)
├── package.json
├── build.sh
└── README.md
```

### Main Plugin File

The `wing-map-display.php` file (158 lines):
- Defines plugin constants (version, path, URL)
- Provides `get_meta_helper()` function for accessing theme metadata
- Registers the block with `register_block_type()`
- Implements `render_callback()` for server-side rendering
- Provides `get_wing_locations()` to query and format location data
- Enqueues Leaflet library and dependencies

## Hooks & Filters

The plugin uses standard WordPress block registration hooks:

- `init` - Register block type and enqueue assets

No custom hooks or filters are provided by this plugin (single responsibility).

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Cluckin Chuck theme (for optimal data access, but not required)

## Compatibility

- Works with cluckin-chuck theme (reads theme metadata)
- Compatible with wing-review plugin (reads review blocks as fallback)
- Compatible with wing-submit plugin (displays submitted locations)

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
