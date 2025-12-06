# Wing Review Plugin

Review block for displaying wing location reviews with automatic comment-to-block conversion.

## Plugin Responsibility

**Single Responsibility**: Register and render the `wing-map/wing-review` block + convert approved comments to blocks

This plugin provides a review display block and hooks into the comment approval workflow to automatically convert approved comments into permanent review blocks stored in post content.

## Block Details

- **Block Name**: `wing-map/wing-review`
- **Block Type**: Server-side rendered
- **Category**: Widgets

## Features

- **Review Display**
  - Reviewer name and date
  - Overall rating (star visualization)
  - Sub-ratings: Sauce quality, Crispiness
  - Review text with proper escaping
  - Responsive layout

- **Comment-to-Block Conversion**
  - Automatically triggers on comment approval
  - Converts comment metadata to block attributes
  - Appends block to post content
  - Deletes original comment after conversion
  - Preserves comment content and metadata

- **Location Details Display** (first block only)
  - Address, phone, website, hours
  - Price range and services offered
  - Reads from theme metadata or block fallback
  - Only displays on the first review block

- **Aggregate Stats Recalculation**
  - Recalculates average rating from all review blocks
  - Recalculates review count
  - Updates theme metadata automatically
  - Used by wing-map-display for map display

- **Data Source Flexibility**
  - Primary: Theme's `Wing_Location_Meta` class
  - Fallback: Block attributes (for location details)
  - Handles missing data gracefully

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Go to WordPress Admin → Plugins
3. Find "Wing Review" and click "Activate"

Or install via zip file:
1. Download `wing-review.zip`
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"
5. Click "Activate"

## Usage

### Block Attributes

The block stores review data as attributes:

```json
{
  "reviewerName": "John Doe",
  "reviewerEmail": "john@example.com",
  "rating": 4.5,
  "sauceRating": 4,
  "crispinessRating": 5,
  "reviewText": "Great wings!",
  "timestamp": "2025-01-15 14:30:00",
  "address": "123 Main St",
  "phone": "(555) 123-4567",
  "website": "https://example.com",
  "hours": "Mon-Fri 11am-10pm",
  "priceRange": "$$",
  "takeout": true,
  "delivery": true,
  "dineIn": true
}
```

### Comment-to-Review Block Workflow

1. **User submits review** (via wing-submit form)
   - Creates a comment on wing_location post
   - Stores ratings and review text in comment metadata

2. **Admin approves comment** (WordPress Admin → Comments)
   - Changes comment status to "Approved"

3. **Plugin converts to block**
   - Reads comment data and metadata
   - Creates wing-map/wing-review block
   - Appends block to post content
   - Updates post in database

4. **Stats recalculated**
   - Parses all wing-review blocks in post
   - Calculates average rating across all reviews
   - Counts total reviews
   - Updates theme metadata

5. **Comment deleted**
   - Original comment removed from database
   - Data now permanently stored as block

### Displaying Reviews

1. Go to edit a wing_location post
2. View the block editor
3. Review blocks appear in post content
4. First block displays location details
5. All blocks display review content

## Development

### Setup

```bash
cd plugins/wing-review
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

Output: `build/wing-review.zip`

## Architecture

### File Structure

```
plugins/wing-review/
├── wing-review.php (main plugin file, 290 lines)
├── src/
│   └── wing-review/
│       ├── block.json
│       ├── edit.js (editor preview)
│       ├── index.js (block registration)
│       └── editor.scss
├── build/
│   └── wing-review/ (compiled assets)
├── package.json
├── build.sh
└── README.md
```

### Main Plugin File

The `wing-review.php` file:
- Defines plugin constants (version, path, URL)
- Provides `get_meta_helper()` function for accessing theme metadata
- Registers the block with `register_block_type()`
- Implements `render_callback()` for server-side rendering
- Hooks `wp_set_comment_status` for comment approval
- Implements `convert_to_block()` to convert comments
- Implements `is_first_review_block()` to detect first block
- Implements `recalculate_location_stats()` to update metadata

### Key Functions

```php
// Render the block
function render_callback($attributes) {
    // Extract attributes
    // Get location details (first block only)
    // Return HTML with escaping
}

// Hook into comment approval
add_action('wp_set_comment_status', __NAMESPACE__ . '\convert_to_block', 10, 2);

// Convert comment to block
function convert_to_block($comment_id, $status) {
    // Check status is 'approve'
    // Get comment data
    // Build block array
    // Append to post content
    // Recalculate stats
    // Delete comment
}

// Recalculate average rating and review count
function recalculate_location_stats($post_id) {
    // Parse blocks
    // Filter wing-review blocks
    // Calculate average rating
    // Update theme metadata
}
```

## Hooks & Filters

The plugin uses standard WordPress hooks:

- `init` - Register block type
- `wp_set_comment_status` - Hook comment approval for block conversion

No custom hooks or filters provided (single responsibility).

## Data Integration

### Reading from Theme Metadata

```php
$meta_helper = get_meta_helper();
if ($meta_helper && $is_first_block) {
    $meta = $meta_helper::get_location_meta($post_id);
    $address = $meta['wing_address'];
    $phone = $meta['wing_phone'];
    // ... etc
}
```

### Fallback to Block Attributes

If theme unavailable, uses attributes from first review block for location details.

### Updating Theme Metadata

After comment conversion, recalculates and updates:
- `wing_average_rating` - float average of all review ratings
- `wing_review_count` - integer count of reviews

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Cluckin Chuck theme (for optimal data access, but not required)

## Compatibility

- Works with cluckin-chuck theme (reads/updates theme metadata)
- Compatible with wing-map-display plugin (provides review data)
- Compatible with wing-submit plugin (provides comment source)

## Security

The plugin implements:
- **Output Escaping**: `esc_html()`, `esc_attr()`, `esc_url()` on all output
- **Input Sanitization**: Comment data sanitized by WordPress core
- **No Direct Saves**: Block conversion updates via `wp_update_post()`

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
