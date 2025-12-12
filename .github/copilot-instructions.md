# Cluckin Chuck: Wing Map Showcase - AI Coding Guidelines

## Architecture Overview

**Monorepo Layout: Block Theme + Four Separate Plugins for Isolated Development**

```
cluckin-chuck/
├── themes/cluckin-chuck/          (THEME - owns wing_location CPT)
├── plugins/
│   ├── wing-location-details/     (PLUGIN - location details hero block)
│   ├── wing-map-display/          (PLUGIN - map display block)
│   ├── wing-review/               (PLUGIN - review display + comment conversion)
│   └── wing-review-submit/        (PLUGIN - submission form + geocoding)
├── AGENTS.md                      (development standards)
├── README.md                      (project overview)
└── plan.md                        (implementation reference)
```

### Theme Ownership
- **Theme owns `wing_location` custom post type** (CPT registration in `inc/class-wing-location.php`)
- **Theme owns `Wing_Location_Meta` class** (metadata management in `inc/class-wing-location-meta.php`)
- Theme provides the **single source of truth** for location data
- All plugins interact with theme's metadata via `CluckinChuck\Wing_Location_Meta` helper class

### Four-Plugin Split
Separate plugins enable **isolated development** - each plugin can be worked on independently:

1. **wing-location-details** - Hero block displaying location details
   - Single responsibility: Display location details (address, phone, hours, services, ratings)
   - Block name: `wing-location-details/wing-location-details`
   - Reads data from theme meta
   - Namespace: `WingLocationDetails\`

2. **wing-map-display** - Renders interactive Leaflet map from wing locations
   - Single responsibility: Display map block
   - Block name: `wing-map-display/wing-map-display`
   - Reads data from theme meta
   - Namespace: `WingMapDisplay\`

3. **wing-review** - Display reviews + convert approved comments to blocks
   - Single responsibility: Review display + comment-to-block conversion
   - Block name: `wing-review/wing-review`
   - Hooks into comment approval workflow
   - Recalculates aggregate stats
   - Namespace: `WingReview\`

4. **wing-review-submit** - Form block for new locations/reviews + geocoding
   - Single responsibility: Handle submissions + geocoding
   - Block name: `wing-review-submit/wing-review-submit`
   - Nominatim API integration (server-side)
   - Rate limiting, honeypot, nonce security
   - Namespace: `WingReviewSubmit\`

## Essential Development Workflows

### Plugin Development Setup
```bash
cd plugins/<plugin-name>
npm install
npm run start  # Watch mode (rebuilds on file changes)
npm run build  # Production build
```

### Theme Development
No build required - template parts and `theme.json` work directly.

### Production Builds
```bash
# Plugin build
cd plugins/<plugin-name>
./build.sh
# Output: build/<plugin-name>.zip

# Theme build
cd themes/cluckin-chuck
./build.sh
# Output: build/cluckin-chuck.zip
```

## Code Standards

### Namespaces
- Theme: `CluckinChuck\`
- Plugins: `WingLocationDetails\`, `WingMapDisplay\`, `WingReview\`, `WingReviewSubmit\`

### Naming Conventions
- **PHP**: `snake_case` for functions/variables, `PascalCase` for classes
- **JavaScript**: `camelCase` for variables/functions, `PascalCase` for classes
- **Files**: Match namespace structure, one class per file

### Imports & Dependencies
- Use **ES modules** for JavaScript (no CommonJS or default namespace imports)
- Prefer **destructured imports** from `@wordpress/*` packages
- Example: `import { registerBlockType } from '@wordpress/blocks';`

### JavaScript
- **Vanilla JavaScript only** - no jQuery, no third-party frameworks
- No inline scripts - enqueue all assets via `wp_enqueue_script()/wp_enqueue_style()`
- Build blocks with `@wordpress/scripts`
- Block sources in `src/<block>/`, compiled to `build/<block>/`

### CSS/SCSS
- Use **SCSS per block** in `src/<block>/editor.scss` and `src/<block>/style.scss`
- No inline styles - all styles in dedicated CSS files
- Respect theme `theme.json` tokens and design system
- Theme-wide variables available in root CSS

### PHP Code Standards
- Follow **WordPress Coding Standards**
- PHP lint before committing: `php -l path/to/file.php`
- Single responsibility principle - one responsibility per class/file
- Clear, human-readable code reduces need for comments

## Security Standards

### Every Form & AJAX Handler Must Have:
1. **Nonce verification** - `wp_verify_nonce()` for all form submissions
2. **Input sanitization** - `sanitize_text_field()`, `floatval()`, `esc_url_raw()`, etc.
3. **Output escaping** - `esc_html()`, `esc_attr()`, `esc_url()` when rendering
4. **Capability checks** - `current_user_can()` for admin-only actions

### Wing Review Submit Plugin Specifics:
- Rate limiting: 1 review per IP per hour
- Honeypot spam prevention
- Nonce on submission form
- Sanitize all inputs before processing

### Error Handling
- **Fail fast** - don't hide broken functionality with fallbacks
- **Return human-readable errors** - use `wp_send_json_error()` with clear messages
- No silent failures or placeholder fallbacks
- Log issues for debugging

## Data Architecture

### Single Source of Truth
Theme owns all location data via `Wing_Location_Meta`:

```php
// Meta keys (all stored on wing_location posts)
'wing_address'          // Street address
'wing_latitude'         // Decimal latitude
'wing_longitude'        // Decimal longitude
'wing_website'          // Website URL
'wing_instagram'        // Instagram URL
'wing_average_rating'   // Float (1-5)
'wing_review_count'     // Integer
'wing_average_ppw'      // Average price per wing
'wing_min_ppw'          // Minimum price per wing
'wing_max_ppw'          // Maximum price per wing
```

### Plugin Data Access
All plugins require the theme's meta helper:
```php
$meta_helper = get_meta_helper();  // Returns CluckinChuck\Wing_Location_Meta or null
if ( $meta_helper ) {
    $meta = $meta_helper::get_location_meta( $post_id );
}
```

## Block Architecture

### Block Structure
```
plugins/<plugin>/
├── src/<block>/
│   ├── block.json       (metadata)
│   ├── edit.js          (editor interface)
│   ├── index.js         (registration)
│   ├── editor.scss      (editor styles)
│   └── style.scss       (frontend styles)
├── build/<block>/       (compiled output)
└── <plugin>.php         (register_block_type call)
```

### Server-Side Rendering
All four blocks use server-side rendering:
- PHP render callbacks in plugin files
- `register_block_type()` with render_callback
- Saves return `null` in JS (no client-side saving)
- Block markup generated by PHP on frontend

### Block Attributes
Stored in block HTML comments, accessible in render callback:
```php
function render_callback( $attributes, $content ) {
    $rating = floatval( $attributes['rating'] ?? 0 );
    // Render block HTML
}
```

## Integration Points

### How Plugins Work with Theme
1. **Wing Review Submit** submits form data
   - Creates new wing_location post (if new location)
   - Stores submission as comment with metadata
   - Calls geocoding service
   - Saves coordinates to theme meta

2. **Wing Review** displays reviews
   - Reads approval status changes
   - Converts approved comments to `wing-review/wing-review` blocks
   - Reads location data from theme meta
   - Recalculates aggregate stats

3. **Wing Map Display** renders map
   - Queries wing_location posts
   - Reads coordinates from theme meta
   - Renders interactive Leaflet map

4. **Wing Location Details** displays location info
   - Reads location data from theme meta
   - Renders hero block with address, website, Instagram, ratings, price per wing

## APIs & External Services

### REST API
- Use WordPress REST API exclusively for data operations
- No admin-ajax.php endpoints
- Plugins register REST routes via `register_rest_route()`

### Nominatim Geocoding (wing-review-submit)
- **Service**: OpenStreetMap Nominatim API
- **Endpoint**: `https://nominatim.openstreetmap.org/search`
- **Rate limit**: 1 request/second (enforced server-side)
- **User-Agent**: `WingReviewSubmit/0.1.0 (https://chubes.net)`
- **Server-side only** - never client-side requests

## Key Resources

- WordPress REST API: https://developer.wordpress.org/rest-api/
- WordPress Block Development: https://developer.wordpress.org/block-editor/
- WordPress Coding Standards: https://developer.wordpress.org/coding-standards/
- Leaflet.js: https://leafletjs.com/
- Nominatim API: https://nominatim.org/release-docs/latest/api/Overview/

## Testing Standards

### No PHPUnit Suite
Manual testing approach:

1. **Wing Submit REST API**
   - Test form submission for new locations
   - Test form submission for reviews on existing location
   - Test geocoding functionality
   - Test rate limiting
   - Test honeypot spam prevention
   - Test nonce verification

2. **Comment-to-Review Block Conversion**
   - Submit review (creates comment)
   - Approve comment in WordPress admin
   - Verify comment converted to `wing-review/wing-review` block
   - Verify comment deleted after conversion
   - Verify aggregate stats recalculated

3. **Map Display**
   - Verify map renders with all locations
   - Verify markers show correct coordinates
   - Test with location metadata present

## Critical "Don't Do" Rules

- Do NOT leave inline comments about removed or modified code
- Do NOT use !important in .css files (except editor styles in WordPress)
- Do NOT change AI models in the code
- Do NOT use any of the following fallback types: Placeholder fallbacks, Legacy fallbacks, Fallbacks that prevent code failure, Fallbacks that provide dual support, BACKWARD COMPATIBILITY IS BLOAT
- Do NOT use inline scripts or styles