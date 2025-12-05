# Cluckin Chuck: Wing Map Project - IMPLEMENTATION COMPLETE

## Project Overview

A WordPress block theme paired with a custom plugin for showcasing chicken wing locations across the USA. The site is dedicated to the wing map showcase, featuring interactive Leaflet maps, manual data entry workflow, and a custom post type for wing locations.

**STATUS:** All phases described in this document have been successfully implemented. This document now serves as implementation reference and architectural documentation.

---

**⚠️ ARCHITECTURE UPDATE NOTICE:**

This planning document describes the **original architecture** with custom post meta fields and admin meta boxes. The system has since evolved:

- **Custom post meta fields removed** - All data now stored in wing-review block attributes
- **Admin meta box removed** - Data editable directly in WordPress block editor
- **WingLocationMeta class removed** - Geocoding moved to wing-submit block

**For current architecture details, see:** `CLAUDE.md`

---

## Architectural Decision

**Chosen Architecture: Block Theme + Separate Wing Map Plugin**

### Why This Architecture?

#### 1. Single Responsibility Principle
- **Plugin** (`wing-map`) = All business logic
  - Custom post type for wing locations
  - Map functionality and rendering
  - User submission handling
  - Metadata management
  - Centralized filters and actions
- **Theme** (`cluckin-chuck`) = Pure presentation
  - Layout and styling
  - Templates for displaying wing locations
  - Block patterns for common layouts
  - Site-wide design system

#### 2. KISS Principle (Keep It Simple, Stupid)
- Clear separation between data/functionality and presentation
- Changes to map functionality never touch theme files
- Design changes never touch business logic
- Clear boundaries create simpler mental model

#### 3. Data Source of Truth
- Plugin owns the `wing_location` custom post type (single source of truth)
- All wing data, metadata, and taxonomies live in plugin PHP files
- Theme consumes and displays what plugin provides
- Aligns with "all data must have a single source of truth, located in PHP files on server side"

#### 4. Practical Benefits
- Theme redesigns don't affect wing data or functionality
- Plugin can be tested/updated independently
- Block theme provides modern WordPress FSE capabilities
- Plugin can potentially be used with other themes in future

---

## Project Structure

```
/cluckin-chuck/
├── plan.md (this file - implementation reference)
├── README.md (project overview)
├── wing-map/ (PLUGIN)
│   ├── wing-map.php (main plugin file)
│   ├── composer.json
│   ├── package.json
│   ├── build.sh
│   ├── README.md
│   ├── src/
│   │   ├── PostTypes/
│   │   │   └── WingLocation.php
│   │   ├── Blocks/
│   │   │   └── MapDisplay.php
│   │   └── Meta/
│   │       └── WingLocationMeta.php
│   ├── blocks/
│   │   └── map-display/
│   │       ├── block.json
│   │       ├── index.js
│   │       ├── style.css
│   │       └── editor.css
│   ├── assets/
│   │   ├── frontend.js (Leaflet initialization)
│   │   ├── admin-meta-box.js
│   │   ├── admin-meta-box.css
│   │   └── wing-marker.svg
│   └── build/ (production output)
│       ├── wing-map/ (clean directory)
│       └── wing-map.zip (deployment package)
└── cluckin-chuck/ (THEME)
    ├── style.css (theme headers)
    ├── theme.json (FSE configuration)
    ├── functions.php
    ├── build.sh
    ├── README.md
    ├── templates/
    │   ├── index.html
    │   ├── single-wing_location.html
    │   └── archive-wing_location.html
    ├── parts/
    │   ├── header.html
    │   └── footer.html
    ├── patterns/
    │   └── wing-location-grid.php
    ├── .vscode/
    │   └── tasks.json
    └── build/ (production output)
        ├── cluckin-chuck/ (clean directory)
        └── cluckin-chuck.zip (deployment package)
```

---

## Phase 1: Wing Map Plugin Implementation ✅ COMPLETE

### 1.1 Core Plugin File: `wing-map/wing-map.php` ✅

**Purpose**: Main plugin entry point with headers and initialization

**Implementation Status**: Complete and functional

**Contents**:
- WordPress plugin headers (Name, Description, Version, Author: Chris Huber)
- PHP namespace: `WingMap`
- Security check (no direct access)
- Composer autoloader require
- Hook into `init` action to register post type, meta, and block
- Instantiate core classes

**Key Functions**:
- `wing_map_init()` - Initialize all plugin components
- Load `PostTypes\WingLocation`, `Meta\WingLocationMeta`, `Blocks\MapDisplay`

---

### 1.2 Composer Configuration: `wing-map/composer.json`

**Purpose**: PHP dependency management and autoloading

**Configuration**:
- PHP 8.0+ requirement
- PSR-4 autoloading: `WingMap` namespace → `src/` directory
- Composer scripts for PHPCS validation
- Production-only dependencies for build

---

### 1.3 Package Configuration: `wing-map/package.json`

**Purpose**: Block asset building and development

**Dependencies**:
- `@wordpress/scripts` - Official WordPress build tooling
- Build scripts:
  - `npm run build` - Production build
  - `npm run start` - Development watch mode

---

### 1.4 Custom Post Type: `wing-map/src/PostTypes/WingLocation.php`

**Namespace**: `WingMap\PostTypes`

**Class**: `WingLocation`

**Purpose**: Register `wing_location` custom post type (single responsibility)

**Configuration**:
- Post type slug: `wing_location`
- Public: `true`
- Show in REST: `true` (required for block editor)
- Supports: `title`, `editor`, `thumbnail`
- Custom labels (Wing Location, Wing Locations, etc.)
- Menu icon: `dashicons-location`
- Has archive: `true`
- Rewrite slug: `wings`

**Method**:
- `register()` - Static method to register the post type
- Called via `init` hook

---

### 1.5 Metadata Management: `wing-map/src/Meta/WingLocationMeta.php`

**Namespace**: `WingMap\Meta`

**Class**: `WingLocationMeta`

**Purpose**: Register and manage wing location metadata (single responsibility)

**Core Meta Fields** (required):
- `wing_address` (text) - Street address for geocoding and display
- `wing_latitude` (number) - Decimal latitude (-90 to 90)
- `wing_longitude` (number) - Decimal longitude (-180 to 180)

**Rating Fields**:
- `wing_rating` (number) - Overall rating (1-5 scale, 0.5 increments)
- `wing_sauce_rating` (number) - Sauce quality rating (1-5)
- `wing_crispiness_rating` (number) - Crispiness rating (1-5)

**Contact Information**:
- `wing_phone` (text) - Phone number
- `wing_website` (url) - Website URL
- `wing_email` (email) - Email address

**Business Details**:
- `wing_hours` (textarea) - Operating hours (free text format)
- `wing_price_range` (text) - Price range: $ | $$ | $$$ | $$$$
- `wing_takeout` (boolean) - Offers takeout service
- `wing_delivery` (boolean) - Offers delivery service
- `wing_dine_in` (boolean) - Offers dine-in service

**Configuration**:
- All fields: `show_in_rest: true` (for block editor access)
- Sanitization callbacks for each field type (text, number, url, email, boolean)
- Validation for latitude/longitude ranges
- Object type: `post`
- Object subtype: `wing_location`

**Methods**:
- `register()` - Static method to register all meta fields via `init` hook
- `add_meta_boxes()` - Register custom meta box UI via `add_meta_boxes` hook
- `render_meta_box()` - Render meta box HTML with all fields
- `save_meta_box()` - Handle meta box save with nonce verification
- `geocode_address()` - Server-side geocoding via Nominatim API
- `ajax_geocode_handler()` - AJAX endpoint for geocoding requests

---

### 1.6 Block Registration: `wing-map/src/Blocks/MapDisplay.php`

**Namespace**: `WingMap\Blocks`

**Class**: `MapDisplay`

**Purpose**: Register custom block and handle rendering (single responsibility)

**Block Details**:
- Block name: `wing-map/map-display`
- Server-side rendered block
- Enqueue Leaflet library (CSS/JS)
- Enqueue custom block assets
- Render callback for frontend display

**Methods**:
- `register()` - Static method to register block via `init` hook
- `render_callback()` - Render block on frontend with map container
- `enqueue_assets()` - Enqueue Leaflet library + custom frontend JS/CSS
- `get_wing_locations()` - Query and format wing location data for map

**Leaflet Asset Enqueuing** (`enqueue_assets()`):
```php
// Leaflet CSS (v1.9.4)
wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');

// Leaflet JS (v1.9.4)
wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js');

// Custom wing map initialization
wp_enqueue_script('wing-map-frontend', ..., ['leaflet']);
```

**Rendering Logic** (`render_callback()`):
- Query all published `wing_location` posts via `WP_Query`
- Extract latitude/longitude/address/rating from post meta
- Skip locations missing coordinates (no fallback)
- Localize script with wing location data array
- Return map container div: `<div id="wing-map"></div>`

**Data Structure** (`get_wing_locations()`):
```php
[
    [
        'id' => 123,
        'title' => 'Wing Restaurant Name',
        'lat' => 40.7128,
        'lng' => -74.0060,
        'address' => '123 Main St, City, State',
        'rating' => 4,
        'url' => 'https://site.com/wings/wing-restaurant-name'
    ],
    // ... more locations
]
```

---

### 1.7 Block Definition: `wing-map/blocks/map-display/block.json`

**Purpose**: Block metadata and configuration

**Configuration**:
- `apiVersion`: 3
- `name`: `wing-map/map-display`
- `title`: "Wing Map"
- `category`: `widgets`
- `icon`: `location`
- `description`: "Interactive map of chicken wing locations"
- `attributes`: Map settings (zoom level, default center, etc.)
- `supports`: Alignment, custom className
- `editorScript`: `file:./index.js`
- `style`: `file:./style.css`

---

### 1.8 Block Editor Script: `wing-map/blocks/map-display/index.js`

**Purpose**: Block editor functionality (edit interface)

**Implementation**:
```javascript
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import metadata from './block.json';

registerBlockType(metadata.name, {
    edit: () => {
        const blockProps = useBlockProps();
        return (
            <div {...blockProps}>
                <div className="wing-map-placeholder">
                    🗺️ Wing Location Map
                    <p>Interactive map will display on the frontend</p>
                </div>
            </div>
        );
    },
    save: () => null, // Server-side rendered
});
```

**Editor Display**:
- Shows placeholder with map emoji and description
- Indicates server-side rendering
- No interactive map in editor (Leaflet renders only on frontend)

---

### 1.8.1 Block Frontend Script: `wing-map/assets/frontend.js` ✅

**Purpose**: Leaflet map initialization and marker rendering

**Actual Location**: `/wing-map/assets/frontend.js` (not in blocks directory)

**Implementation**: Complete with auto-fitting map bounds feature

**Features**:
- Leaflet map centered on USA (coordinates: 39.8283, -98.5795)
- Default zoom level 4 (country view)
- OpenStreetMap tiles (free, no API key)
- Custom chicken wing marker icons
- Popup on marker click with:
  - Wing location title
  - Street address
  - Star rating visualization
  - Link to full post page
- **Auto-fitting map bounds**: Automatically adjusts zoom/position to show all markers (lines 51-55)

**Key Implementation Detail**:
```javascript
// Fit map bounds to show all markers if there are locations
if (wingMapData.locations.length > 0) {
    const bounds = L.latLngBounds(wingMapData.locations.map(loc => [loc.lat, loc.lng]));
    map.fitBounds(bounds, { padding: [50, 50] });
}
```

---

### 1.9 Block Styles: `wing-map/blocks/map-display/style.css`

**Purpose**: Frontend styles for Leaflet map and popups

**Styles**:
```css
.wp-block-wing-map-map-display {
    margin: 2rem 0;
}

#wing-map {
    width: 100%;
    height: 600px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Custom popup styling */
.wing-popup {
    text-align: center;
}

.wing-popup h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    color: #d35400; /* Wing sauce orange */
}

.wing-popup p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
}

.wing-popup a {
    display: inline-block;
    margin-top: 0.5rem;
    padding: 0.5rem 1rem;
    background: #d35400;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
}

.wing-popup a:hover {
    background: #e67e22;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #wing-map {
        height: 400px;
    }
}
```

**Design Features**:
- 600px height on desktop, 400px on mobile
- Rounded corners and subtle shadow
- Custom popup styling with wing sauce orange theme
- Hover effect on "View Details" link

---

### 1.10 Build Script: `wing-map/build.sh` ✅

**Purpose**: Create production-ready plugin package

**Implementation Status**: Complete with verification logic

**Process**:
1. Clean previous build: `rm -rf build/wing-map build/wing-map.zip`
2. Build block assets: `npm run build`
3. Install production dependencies: `composer install --no-dev --optimize-autoloader`
4. Create build directory: `mkdir -p build/wing-map`
5. Copy essential files to `build/wing-map/`:
   - `wing-map.php`
   - `src/` directory
   - `blocks/` directory (built assets)
   - `assets/` directory (frontend.js, admin files, SVG marker)
   - `vendor/` directory
   - `composer.json`
6. Create ZIP: `cd build && zip -r wing-map.zip wing-map/`
7. Restore dev dependencies: `composer install`
8. Verification: Script includes success confirmation with file listing

**Build Output**:
- `/build/wing-map/` - Clean production directory
- `/build/wing-map.zip` - Deployment package

**Make executable**: `chmod +x build.sh`

---

### 1.11 Build Exclusions: `wing-map/.buildignore`

**Purpose**: Files to exclude from production build

**Excluded**:
- `node_modules/`
- `vendor/` (reinstalled as production-only)
- `.git/`
- `src/` (compiled to blocks/build/)
- `package-lock.json`
- `composer.lock`
- `.DS_Store`
- `.claude/`
- `CLAUDE.md`
- `README.md` (or include if needed)
- `build.sh`
- `.buildignore`

---

### 1.12 Map Library Integration: Leaflet.js

**Library**: Leaflet v1.9.4 (https://leafletjs.com)

**Why Leaflet**:
- 100% open source, BSD-2-Clause license
- No API keys or billing required
- Fully customizable styling via CSS
- Lightweight (~42KB gzipped)
- Custom marker icons (chicken wing SVG/PNG)
- Extensive plugin ecosystem
- Active community support
- Perfect for open source developers

**Integration Method**:
- CDN delivery via unpkg.com
- Enqueued in `MapDisplay::enqueue_assets()`
- CSS: `https://unpkg.com/leaflet@1.9.4/dist/leaflet.css`
- JS: `https://unpkg.com/leaflet@1.9.4/dist/leaflet.js`

**Map Configuration**:
- Default center: USA coordinates (39.8283, -98.5795)
- Default zoom: 4 (country-wide view)
- Tile provider: OpenStreetMap
- Attribution: © OpenStreetMap contributors
- Max zoom: 19

**Custom Marker Icon**:
- Asset: `wing-map/assets/wing-marker.svg`
- Size: 32x32 pixels
- Anchor: [16, 32] (bottom center)
- Popup anchor: [0, -32] (above marker)
- Color scheme: Orange/red wing sauce theme

**Marker Popup Behavior**:
- Click marker to open popup
- Popup contents:
  - Wing location title (h3)
  - Street address (p)
  - Star rating visualization (★★★★☆)
  - "View Details →" link to full post
- Popup styling: Custom CSS with wing sauce orange theme

---

### 1.13 Geocoding Service: Nominatim (OpenStreetMap)

**Service**: Nominatim API (https://nominatim.openstreetmap.org)

**Why Nominatim**:
- Free, open-source geocoding service
- No API key required
- Powered by OpenStreetMap data
- Global coverage
- Simple REST API
- Perfect for manual entry workflow

**Usage Policy Compliance**:
- Maximum 1 request per second
- Must provide User-Agent header
- Server-side requests only (not client-side)
- Used for manual geocoding (not real-time)

**Implementation** (`WingLocationMeta::geocode_address()`):
```php
public static function geocode_address($address) {
    $encoded_address = urlencode($address);
    $url = "https://nominatim.openstreetmap.org/search?q={$encoded_address}&format=json&limit=1";

    $response = wp_remote_get($url, [
        'headers' => [
            'User-Agent' => 'WingMap/1.0 (https://chubes.net)'
        ]
    ]);

    if (is_wp_error($response)) {
        return null;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body[0])) {
        return [
            'lat' => $body[0]['lat'],
            'lon' => $body[0]['lon']
        ];
    }

    return null;
}
```

**AJAX Integration**:
- WordPress AJAX action: `wp_ajax_wing_map_geocode`
- Admin-only access
- Nonce verification for security
- Returns JSON: `{success: true, lat: X, lng: Y}` or error
- Triggered by "Geocode Address" button in meta box

**Error Handling**:
- Invalid address: Show error message, require manual entry
- API failure: Graceful degradation to manual lat/lng entry
- No fallback coordinates (prevents bad data)

---

### 1.14 Meta Box UI for Manual Data Entry

**Meta Box Registration** (`WingLocationMeta::add_meta_boxes()`):
```php
add_meta_box(
    'wing_location_details',
    'Wing Location Details',
    [self::class, 'render_meta_box'],
    'wing_location',
    'normal',
    'high'
);
```

**Meta Box Layout Design**:
```
┌─ Wing Location Details ─────────────────────────────────┐
│                                                          │
│ Location & Coordinates                                   │
│ ├─ Address: [_________________________________]         │
│ ├─ [Geocode Address] button                             │
│ ├─ Latitude:  [__________] (auto-filled from geocoding) │
│ └─ Longitude: [__________] (auto-filled from geocoding) │
│                                                          │
│ Ratings                                                  │
│ ├─ Overall:     [★★★★☆] 1-5 scale                      │
│ ├─ Sauce:       [★★★★☆] 1-5 scale                      │
│ └─ Crispiness:  [★★★★☆] 1-5 scale                      │
│                                                          │
│ Contact Information                                      │
│ ├─ Phone:   [____________________]                      │
│ ├─ Website: [____________________]                      │
│ └─ Email:   [____________________]                      │
│                                                          │
│ Business Details                                         │
│ ├─ Hours:       [________________________] (textarea)   │
│ ├─ Price Range: [$$] (dropdown: $, $$, $$$, $$$$)      │
│ ├─ Services:    ☑ Takeout  ☑ Delivery  ☑ Dine-in       │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

**Field Organization**:
1. **Location & Coordinates** section
   - Address (text input, required)
   - Geocode button with loading spinner
   - Latitude (number input, readonly when geocoded)
   - Longitude (number input, readonly when geocoded)

2. **Ratings** section
   - Overall rating (number input with star UI)
   - Sauce rating (number input with star UI)
   - Crispiness rating (number input with star UI)
   - JavaScript star selector: click stars to set value

3. **Contact Information** section
   - Phone (text input with tel validation)
   - Website (URL input with validation)
   - Email (email input with validation)

4. **Business Details** section
   - Hours (textarea for flexible formatting)
   - Price range (select dropdown)
   - Services (checkboxes for takeout/delivery/dine-in)

**JavaScript Features** (`wing-map/assets/admin-meta-box.js`):
- Geocode button AJAX handler
- Loading spinner during geocoding
- Success/error messages
- Star rating click-to-select UI
- Real-time field validation
- Latitude/longitude range validation (-90 to 90, -180 to 180)

**Styling** (`wing-map/assets/admin-meta-box.css`):
- Clear visual sections with headings
- Consistent spacing and alignment
- Star rating hover effects
- Button styling (primary for Geocode)
- Success/error message styling
- Responsive layout for smaller screens

**Save Handler** (`WingLocationMeta::save_meta_box()`):
- Verify nonce: `wp_verify_nonce()`
- Check autosave: Skip if autosave
- Check permissions: `current_user_can('edit_post', $post_id)`
- Sanitize all fields by type:
  - Text: `sanitize_text_field()`
  - URL: `esc_url_raw()`
  - Email: `sanitize_email()`
  - Number: `floatval()` or `intval()`
  - Boolean: `(bool)` cast
- Validate ranges for lat/lng
- Update post meta: `update_post_meta()`

---

## Phase 2: Cluckin Chuck Block Theme Implementation ✅ COMPLETE

### 2.1 Theme Headers: `cluckin-chuck/style.css` ✅

**Actual Directory**: `cluckin-chuck` (not `cluckin-chuck-theme`)

**Purpose**: Theme metadata and global styles

**Headers**:
```css
/*
Theme Name: Cluckin Chuck
Theme URI: https://chubes.net
Author: Chris Huber
Author URI: https://chubes.net
Description: A block theme dedicated to showcasing chicken wing locations across the USA
Version: 0.1.0
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: cluckin-chuck
*/
```

**Global Styles**:
- CSS custom properties for colors
- Base typography
- Utility classes

---

### 2.2 FSE Configuration: `cluckin-chuck/theme.json` ✅

**Purpose**: Full Site Editing configuration and design system

**Version**: 2

**Settings**:
- **Color Palette**:
  - Primary: Wing sauce red/orange
  - Secondary: Golden crispy color
  - Background: Clean white/cream
  - Text: Dark brown/black
- **Typography**:
  - Font families (system fonts or Google Fonts)
  - Font sizes (small, medium, large, xlarge)
  - Line heights
- **Spacing**:
  - Spacing scale (20, 30, 40, 50, 60)
- **Layout**:
  - Content width: 1200px
  - Wide width: 1400px

**Template Parts**:
- `header`: Site header
- `footer`: Site footer

**Custom Templates**:
- Referenced templates in `templates/` directory

---

### 2.3 Theme Functions: `cluckin-chuck/functions.php` ✅

**Purpose**: Theme setup and configuration

**Functions**:
- `cluckin_chuck_setup()` - Theme setup (hooked to `after_setup_theme`)
  - Add theme support: `title-tag`, `post-thumbnails`
  - Add theme support: `editor-styles`
  - Add theme support: `wp-block-styles`
- `cluckin_chuck_enqueue_styles()` - Enqueue theme styles (hooked to `wp_enqueue_scripts`)
- Register any theme-specific filters for wing data display

**Notes**:
- Minimal functions - most config in `theme.json`
- No business logic - only presentation concerns

---

### 2.4 Main Template: `cluckin-chuck/templates/index.html` ✅

**Purpose**: Default fallback template for all content

**Structure**:
```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
    <!-- wp:query -->
    <!-- Query loop for posts -->
    <!-- /wp:query -->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

---

### 2.5 Single Wing Location Template: `cluckin-chuck/templates/single-wing_location.html` ✅

**Purpose**: Display individual wing location posts

**Structure**:
```html
<!-- wp:template-part {"slug":"header"} /-->

<main>
    <!-- wp:post-title /-->

    <!-- wp:post-featured-image /-->

    <!-- wp:wing-map/map-display (PLUGIN BLOCK) /-->

    <!-- wp:group (Wing Details) -->
        <!-- Display meta: address, rating -->
    <!-- /wp:group -->

    <!-- wp:post-content /-->
</main>

<!-- wp:template-part {"slug":"footer"} /-->
```

---

### 2.6 Archive Template: `cluckin-chuck/templates/archive-wing_location.html` ✅

**Purpose**: Display wing location archive/listing

**Structure**:
```html
<!-- wp:template-part {"slug":"header"} /-->

<main>
    <!-- wp:query-title /-->

    <!-- wp:query (wing_location query) -->
        <!-- wp:post-template (Grid layout) -->
            <!-- wp:post-title /-->
            <!-- wp:post-featured-image /-->
            <!-- wp:post-excerpt /-->
        <!-- /wp:post-template -->
    <!-- /wp:query -->
</main>

<!-- wp:template-part {"slug":"footer"} /-->
```

---

### 2.7 Header Template Part: `cluckin-chuck/parts/header.html` ✅

**Purpose**: Site header with branding and navigation

**Structure**:
```html
<!-- wp:group (Header container) -->
    <!-- wp:site-title /-->
    <!-- wp:navigation /-->
<!-- /wp:group -->
```

---

### 2.8 Footer Template Part: `cluckin-chuck/parts/footer.html` ✅

**Purpose**: Site footer with copyright and credits

**Structure**:
```html
<!-- wp:group (Footer container) -->
    <!-- wp:paragraph (Copyright) /-->
    <!-- wp:paragraph (Credits) /-->
<!-- /wp:group -->
```

---

### 2.9 Block Pattern: `cluckin-chuck/patterns/wing-location-grid.php` ✅

**Purpose**: Reusable pattern for displaying wing locations in grid

**Registration**:
```php
<?php
register_block_pattern(
    'cluckin-chuck/wing-location-grid',
    array(
        'title' => 'Wing Location Grid',
        'categories' => array('query'),
        'content' => '<!-- Pattern content -->'
    )
);
```

---

### 2.10 Theme Build Script: `cluckin-chuck/build.sh` ✅

**Purpose**: Create production-ready theme package

**Process**:
1. Clean: `rm -rf build/cluckin-chuck build/cluckin-chuck.zip`
2. Create directory: `mkdir -p build/cluckin-chuck`
3. Copy essential files:
   - `style.css`
   - `theme.json`
   - `functions.php`
   - `README.md`
   - `templates/`
   - `parts/`
   - `patterns/`
4. Create ZIP: `cd build && zip -r cluckin-chuck.zip cluckin-chuck/`
5. Verify: Both directory and ZIP exist with success confirmation

---

### 2.11 Theme Build Exclusions: `cluckin-chuck/.buildignore` ✅

**Excluded**:
- `.git/`
- `.DS_Store`
- `.claude/`
- `CLAUDE.md`
- `build.sh`
- `.buildignore`
- `.vscode/`

---

### 2.12 VSCode Tasks: `cluckin-chuck/.vscode/tasks.json` ✅

**Purpose**: Development tasks for theme work

**Tasks**:
- Build theme
- Watch for changes
- Validate theme files

---

## Phase 3: Documentation ✅ COMPLETE

### 3.1 Root README: `README.md` ✅

**Status**: Complete - comprehensive project documentation

**Contents**:
- Project overview
- Architecture explanation (plugin + theme separation)
- Development setup instructions
- Build process for both components
- Installation instructions
- Credits

---

### 3.2 Plugin README: `wing-map/README.md` ✅

**Status**: Complete - detailed plugin documentation

**Contents**:
- Plugin description
- Installation instructions
- Custom post type usage
- Block usage in editor
- Available filters and actions
- Metadata schema
- Development setup

---

### 3.3 Theme README: `cluckin-chuck/README.md` ✅

**Actual Path**: `cluckin-chuck/README.md` (not `cluckin-chuck-theme`)

**Status**: Complete - comprehensive theme documentation

**Contents**:
- Theme description
- Installation instructions
- Template customization
- Block pattern usage
- Theme.json configuration
- Compatibility notes

---

## Phase 4: Future LLM Integration (NOT IMPLEMENTED)

**Status**: Planned for future release - NOT included in current implementation

**Note**: This phase describes potential future functionality that has NOT been implemented.

**Vision**: AI-assisted metadata filling to streamline wing location creation and reduce manual data entry

### 4.1 Use Case

**Current Workflow** (Manual Entry):
1. Admin creates new Wing Location post
2. Admin manually enters:
   - Address
   - Geocodes to get coordinates
   - Phone number (looks up on Google)
   - Website URL (searches and copies)
   - Operating hours (finds on website)
   - Price range (estimates from menu)
   - Rating (based on research)

**Future Workflow** (LLM-Assisted):
1. Admin creates new Wing Location post
2. Admin enters ONLY:
   - Restaurant name
   - Address
3. Admin clicks "Auto-Fill with AI" button
4. LLM searches web and fills in:
   - Phone number
   - Website URL
   - Operating hours
   - Price range estimate
   - Initial rating suggestions (from review aggregation)
5. Admin reviews, edits if needed, and saves

**Time Savings**: Estimated 5-10 minutes per location

---

### 4.2 Implementation Approach (Future)

**New PHP Class**: `wing-map/src/AI/MetadataAssistant.php`

**Single Responsibility**: AI-powered metadata research and suggestion

**LLM Integration Options**:
- Claude API (Anthropic) - Preferred for web search capabilities
- OpenAI GPT-4 - Alternative option
- Other LLM providers with web access

**Methods**:
```php
namespace WingMap\AI;

class MetadataAssistant {
    // Initialize AI service with API key
    public static function init() { }

    // Main entry point for metadata filling
    public static function auto_fill_metadata($restaurant_name, $address) { }

    // Generate prompt for LLM
    private static function build_prompt($restaurant_name, $address) { }

    // Parse LLM response into structured data
    private static function parse_response($response) { }

    // Validate LLM-provided data
    private static function validate_metadata($data) { }
}
```

**AJAX Integration**:
- WordPress AJAX action: `wp_ajax_wing_map_ai_fill`
- Admin-only access
- Nonce verification
- Rate limiting (prevent API abuse)
- Returns JSON with suggested metadata

**Meta Box Enhancement**:
- Add "Auto-Fill with AI" button (prominent, next to Geocode)
- Loading indicator during AI processing
- Display suggestions in editable fields
- Admin can accept/edit/reject suggestions
- Clear indication which fields are AI-suggested

---

### 4.3 LLM Prompt Design (Future)

**Example Prompt Structure**:
```
You are a helpful assistant that researches restaurant information.

Restaurant Name: [name]
Address: [address]

Please search for this restaurant and provide the following information in JSON format:

{
  "phone": "phone number",
  "website": "website URL",
  "hours": "operating hours (e.g., Mon-Fri 11am-10pm, Sat-Sun 12pm-11pm)",
  "price_range": "$ or $$ or $$$ or $$$$",
  "overall_rating": "1-5 based on online reviews",
  "notes": "any important information about the restaurant"
}

If you cannot find reliable information for a field, use null.
Provide only the JSON response, no additional text.
```

---

### 4.4 WordPress Admin Settings (Future)

**Settings Page**: `Settings > Wing Map AI`

**Settings Fields**:
1. **Enable AI Auto-Fill** (checkbox)
   - Enable/disable LLM integration
   - Default: disabled until API key provided

2. **LLM Provider** (radio buttons)
   - Claude (Anthropic)
   - OpenAI
   - Other

3. **API Key** (password field)
   - Secure storage via WordPress options
   - Encrypted at rest
   - Never exposed to client-side

4. **Request Rate Limit** (number input)
   - Max requests per hour
   - Default: 10
   - Prevents API cost overruns

**Settings Class**: `wing-map/src/Settings/AISettings.php`

---

### 4.5 Error Handling & Fallbacks (Future)

**Graceful Degradation**:
- If API key not configured: Hide "Auto-Fill with AI" button
- If API request fails: Show error message, fallback to manual entry
- If LLM returns invalid data: Validate and reject, require manual entry
- If rate limit exceeded: Show message "Daily limit reached, try again tomorrow"

**No Automatic Saves**:
- AI suggestions populate fields but don't auto-save
- Admin must review and click "Update" to save
- Prevents bad data from being saved automatically

---

### 4.6 Why Not Implementing Now?

**Reasons to Delay LLM Integration**:

1. **KISS Principle**: Keep initial release simple and focused
   - Core functionality first (CPT, map, manual entry)
   - Add complexity only after baseline is stable

2. **Data Model Validation**: Manual entry validates the data model
   - Ensures all fields make sense
   - Tests edge cases (missing data, unusual formats)
   - Establishes what "good data" looks like

3. **Cost & Complexity**:
   - LLM API calls have costs
   - Requires API key management
   - Adds dependencies and potential failure points
   - Rate limiting complexity

4. **User Adoption**: Start with familiar workflow
   - Users understand manual entry
   - Build trust before introducing AI automation
   - Gather feedback on which fields are most tedious

5. **Technical Debt Avoidance**:
   - Don't want AI integration to delay initial release
   - Can be added as enhancement without changing core architecture
   - Single Responsibility: AI lives in separate class

**Timeline**: Implement in Phase 4 after Phase 1-3 are stable and deployed

---

### 4.7 Future File Structure for AI Integration

**New Files** (when implementing Phase 4):
```
wing-map/
├── src/
│   ├── AI/
│   │   └── MetadataAssistant.php (LLM integration)
│   └── Settings/
│       └── AISettings.php (Admin settings page)
└── assets/
    └── admin-ai-ui.js (AI auto-fill button handler)
```

**Modified Files**:
- `wing-map/src/Meta/WingLocationMeta.php` - Add "Auto-Fill with AI" button
- `wing-map/assets/admin-meta-box.js` - Add AJAX handler for AI requests
- `wing-map/wing-map.php` - Register AI settings page

---

## Key Architectural Principles Applied

### Single Responsibility
- **Plugin**: One responsibility = wing location data and functionality
- **Theme**: One responsibility = presentation and layout
- Each PHP class has single responsibility (PostType, Meta, Block)

### KISS (Keep It Simple, Stupid)
- Direct, centralized solution: Plugin owns data, theme displays it
- No unnecessary abstractions or layers
- Clear separation prevents complexity

### Single Source of Truth
- All wing location data originates from plugin's custom post type
- No duplicate data storage
- Metadata managed centrally through plugin

### Centralized Filters/Actions
- Plugin provides WordPress filters for wing data transformation
- Theme can hook into these filters without owning business logic
- Plugin provides actions for extending functionality

### Human-Readable Code
- Clear class names: `WingLocation`, `WingLocationMeta`, `MapDisplay`
- Descriptive function names
- Minimal inline comments - code is self-documenting

### No Forbidden Fallbacks
- No placeholder data for undefined wing locations
- No legacy support for old data structures
- No dual-method support - one way to do things

---

## Development Workflow

1. **Initial Setup**:
   - Create all directory structures
   - Write all plugin PHP files
   - Write all theme files
   - Run `composer install` in plugin
   - Run `npm install` in plugin

2. **Block Development**:
   - Run `npm start` in plugin for watch mode
   - Edit block files in `blocks/map-display/`
   - Test in WordPress block editor

3. **Theme Customization**:
   - Edit templates in `templates/`
   - Modify `theme.json` for design system
   - Add patterns in `patterns/`

4. **Production Build**:
   - Run `./build.sh` in plugin directory
   - Run `./build.sh` in theme directory
   - Upload ZIP files to WordPress

---

## Integration Points

### How Plugin and Theme Work Together

1. **Plugin registers `wing_location` post type**
2. **Theme provides templates** for displaying wing locations
3. **Plugin provides `wing-map/map-display` block**
4. **Theme uses block** in single template to show map
5. **Plugin provides metadata** (address, coordinates, rating)
6. **Theme displays metadata** in templates using block editor
7. **Plugin handles all data queries** and processing
8. **Theme consumes processed data** for display

### Clean Boundaries

- Theme NEVER queries custom post type directly
- Theme NEVER registers/modifies metadata
- Plugin NEVER dictates layout/styling
- Plugin provides data, theme presents it

---

## Future Extensibility

### Plugin Extensions (Business Logic)
- Add custom taxonomies: `wing_style`, `region`
- User submission form handling
- Rating/voting system
- Admin interface for managing locations
- REST API endpoints for map data
- Geolocation search

### Theme Extensions (Presentation)
- Additional templates: front-page, home
- More block patterns for various layouts
- Custom block styles for wing-map block
- Color scheme variations in theme.json
- Typography refinements

### Both Remain Independent
- Plugin can work with any block theme
- Theme can display any custom post type
- Clean separation enables flexibility

---

## Success Criteria - ALL ACHIEVED ✅

- ✅ Plugin successfully registers wing_location post type
- ✅ Custom block appears in block editor
- ✅ Map displays wing locations on frontend with auto-fitting bounds
- ✅ Theme templates display wing location content correctly
- ✅ Build scripts create valid production packages with verification
- ✅ No business logic in theme files
- ✅ No presentation logic in plugin files
- ✅ All data has single source of truth (plugin)
- ✅ Code follows KISS principle
- ✅ Single responsibility maintained throughout
- ✅ Complete documentation for all components
