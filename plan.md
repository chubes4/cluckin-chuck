# Cluckin Chuck: Implementation Reference & Architecture

This document serves as an **implementation reference** documenting the architectural decisions, design principles, and project structure of Cluckin Chuck. **All phases described here have been successfully implemented.**

## Project Overview

A WordPress block theme paired with three separate plugins for showcasing chicken wing locations across the USA. The site is dedicated to the wing map showcase, featuring interactive Leaflet maps, user review submissions with geocoding, and modern Full Site Editing capabilities.

---

## Architectural Decision

### Chosen Architecture: Block Theme + Three Separate Plugins

The project uses a **three-plugin split for isolated development**, combined with a block theme that owns core data:

```
Theme (cluckin-chuck)          Plugins (separate)
├── wing_location CPT          ├── wing-map-display (map block)
├── Wing_Location_Meta         ├── wing-review (review block + comment conversion)
└── Templates & Presentation   └── wing-submit (form block + geocoding)
```

### Why This Architecture?

#### 1. Single Responsibility Principle
- **Theme** = Data ownership + presentation
  - Custom post type for wing locations
  - Metadata management
  - Templates for displaying locations
  - Block patterns and design system
- **Each Plugin** = One focused responsibility
  - wing-map-display: Render map block
  - wing-review: Display reviews + convert comments to blocks
  - wing-submit: Handle submissions + geocoding
- **Changes to map logic never touch theme files**
- **Design changes never touch plugin logic**

#### 2. KISS Principle (Keep It Simple, Stupid)
- Clear separation between data/functionality and presentation
- Three plugins enable **isolated development** - work on each independently
- Easy to understand data flow
- Clear boundaries prevent complexity

#### 3. Data Source of Truth
- Theme owns the `wing_location` custom post type (single source of truth)
- All wing data and metadata live in theme PHP files
- All plugins check for theme's `CluckinChuck\Wing_Location_Meta` helper
- Theme provides what plugins consume

#### 4. Practical Benefits
- Theme redesigns don't affect plugin functionality
- Plugins can be tested/updated independently
- Block theme provides modern WordPress FSE capabilities
- Easy to add new plugins without modifying existing ones
- Clear upgrade path for future features

---

## Project Structure

```
cluckin-chuck/
├── plan.md (this file - implementation reference)
├── README.md (project overview & quick start)
├── AGENTS.md (development standards & architecture)
├── themes/cluckin-chuck/ (THEME - owns wing_location CPT)
│   ├── inc/
│   │   ├── class-wing-location.php (CPT registration)
│   │   └── class-wing-location-meta.php (metadata management)
│   ├── templates/ (FSE templates)
│   ├── parts/ (header, footer)
│   ├── style.css (theme headers)
│   ├── theme.json (FSE configuration)
│   ├── functions.php (theme setup)
│   ├── build.sh (production build)
│   └── build/ (production output)
├── plugins/
│   ├── wing-map-display/
│   │   ├── wing-map-display.php (main plugin file)
│   │   ├── src/map-display/ (block source)
│   │   ├── build/map-display/ (compiled block)
│   │   ├── package.json (npm config)
│   │   ├── build.sh (production build)
│   │   └── build/ (production output)
│   ├── wing-review/
│   │   ├── wing-review.php (main plugin file)
│   │   ├── src/wing-review/ (block source)
│   │   ├── build/wing-review/ (compiled block)
│   │   ├── package.json (npm config)
│   │   ├── build.sh (production build)
│   │   └── build/ (production output)
│   └── wing-submit/
│       ├── wing-submit.php (main plugin file)
│       ├── src/wing-submit/ (block source)
│       ├── build/wing-submit/ (compiled block)
│       ├── package.json (npm config)
│       ├── build.sh (production build)
│       └── build/ (production output)
```

---

## Theme: Data Owner & Presentation Layer

### Custom Post Type Registration
**File**: `themes/cluckin-chuck/inc/class-wing-location.php`

```php
namespace CluckinChuck;

class Wing_Location {
    public static function register() {
        register_post_type('wing_location', [
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'wings'],
            'supports' => ['title', 'editor', 'thumbnail'],
            // ... additional configuration
        ]);
    }
}
```

**Features**:
- Post type slug: `wings`
- Public archive and REST API enabled
- Supports title, editor (block editor), thumbnail

### Metadata Management
**File**: `themes/cluckin-chuck/inc/class-wing-location-meta.php`

Registers and manages all wing location metadata:

```php
class Wing_Location_Meta {
    // Location & Coordinates
    'wing_address'          // Street address
    'wing_latitude'         // Decimal latitude (-90 to 90)
    'wing_longitude'        // Decimal longitude (-180 to 180)
    
    // Ratings
    'wing_average_rating'   // Overall rating (1-5, stored as float)
    'wing_review_count'     // Integer count
    
    // Contact Information
    'wing_phone'            // Phone number
    'wing_website'          // Website URL
    'wing_email'            // Email address
    
    // Business Details
    'wing_hours'            // Operating hours (textarea)
    'wing_price_range'      // Price range: $, $$, $$$, $$$$
    'wing_takeout'          // Boolean
    'wing_delivery'         // Boolean
    'wing_dine_in'          // Boolean
}
```

**All fields**:
- `show_in_rest: true` (accessible in REST API)
- Sanitization callbacks by field type
- Validation for ranges and formats
- Object type: `post`, subtype: `wing_location`

**Key Methods**:
- `get_location_meta($post_id)` - Retrieve all location metadata
- `update_location_meta($post_id, $meta_data)` - Update metadata
- Handles null values gracefully

---

## Plugin 1: Wing Map Display

### Purpose
Render an interactive Leaflet map showing all wing locations with markers, ratings, and popups.

### Responsibility (Single)
- Register and render the `wing-map/map-display` block
- Query wing_location posts
- Read location data from theme meta (or fallback to review blocks)
- Enqueue Leaflet library and map JavaScript
- Pass location data to frontend JavaScript

### File Structure
```
plugins/wing-map-display/
├── wing-map-display.php (main plugin, 158 lines)
├── src/map-display/
│   ├── block.json
│   ├── edit.js (editor preview)
│   ├── index.js (block registration)
│   ├── editor.scss
│   └── frontend.js (Leaflet initialization)
└── build/map-display/ (compiled assets)
```

### Data Flow
1. `wing-map-display.php` registers block with render_callback
2. render_callback queries wing_location posts
3. For each post:
   - Check for theme metadata (latitude, longitude, address, rating)
   - Fallback to first wing-review block if meta missing
   - Skip location if no coordinates
4. Build location array with ID, title, coordinates, address, rating
5. Enqueue Leaflet library (CDN)
6. Pass location data to frontend JavaScript via `wp_add_inline_script()`
7. Return map container: `<div id="wing-map"></div>`

### Key Functions
```php
// Enqueue Leaflet + pass data
function render_callback($attributes, $content) {
    enqueue_assets();
    $locations = get_wing_locations();
    wp_add_inline_script('wing-map-display-map-display-view-script', 
        'window.wingMapData = ' . wp_json_encode(['locations' => $locations])
    );
    return '<div id="wing-map" class="wing-location-map"></div>';
}

// Get location data
function get_wing_locations() {
    // Query wing_location posts
    // For each: get meta OR fallback to review blocks
    // Return array with: id, title, lat, lng, address, rating, reviewCount, url
}
```

### Leaflet Library
- **CDN**: `https://unpkg.com/leaflet@1.9.4/dist/leaflet.js`
- **Version**: 1.9.4
- **Why**: Open source, no API key, fully customizable
- Enqueued as dependency for map initialization script

### Frontend JavaScript
Initializes map with:
- Default center: USA (39.8283, -98.5795)
- Default zoom: 4
- Custom markers with chicken wing icon
- Auto-fitting bounds to show all locations (padding: 50px)
- Click popups with location details

---

## Plugin 2: Wing Review

### Purpose
Display review blocks and automatically convert approved comments into permanent review blocks.

### Responsibility (Single)
- Register and render the `wing-map/wing-review` block
- Hook into comment approval workflow
- Convert approved comments to review blocks
- Recalculate location aggregate stats
- Display location details (first block only)

### File Structure
```
plugins/wing-review/
├── wing-review.php (main plugin, 290 lines)
├── src/wing-review/
│   ├── block.json
│   ├── edit.js (editor preview)
│   ├── index.js (block registration)
│   └── editor.scss
└── build/wing-review/ (compiled assets)
```

### Review Block Attributes
```php
// Review content
'reviewerName'          // Reviewer's name
'reviewerEmail'         // Reviewer's email
'rating'                // Overall rating (1-5)
'sauceRating'           // Sauce rating (1-5)
'crispinessRating'      // Crispiness rating (1-5)
'reviewText'            // Review body text
'timestamp'             // Comment timestamp

// Location details (fallback, if no theme meta)
'address'               // Street address
'phone'                 // Phone number
'website'               // Website URL
'hours'                 // Operating hours
'priceRange'            // Price range
'takeout'               // Boolean
'delivery'              // Boolean
'dineIn'                // Boolean
```

### Comment-to-Block Conversion Workflow

**Trigger**: Comment approval (wp_set_comment_status to 'approve')

**Process**:
1. Hook fires: `wp_set_comment_status` with comment_id and 'approve' status
2. Verify comment belongs to wing_location post
3. Extract comment metadata: ratings, content
4. Build block array with attributes
5. Serialize block to HTML comment markup
6. Append block to post_content
7. Update post via wp_update_post()
8. Recalculate location stats (average_rating, review_count)
9. Delete original comment (now stored as block)

**Code Example**:
```php
function convert_to_block($comment_id, $status) {
    if ('approve' !== $status) return;
    
    $comment = get_comment($comment_id);
    if (!$comment || 'wing_location' !== get_post_type($comment->comment_post_ID)) {
        return;
    }
    
    // Extract comment data into block attributes
    $block_content = serialize_block([
        'blockName' => 'wing-map/wing-review',
        'attrs' => [
            'reviewerName' => $comment->comment_author,
            'rating' => get_comment_meta($comment_id, 'wing_rating', true),
            'sauceRating' => get_comment_meta($comment_id, 'wing_sauce_rating', true),
            'crispinessRating' => get_comment_meta($comment_id, 'wing_crispiness_rating', true),
            'reviewText' => $comment->comment_content,
            'timestamp' => $comment->comment_date,
        ],
    ]);
    
    // Append to post content
    $post = get_post($comment->comment_post_ID);
    $new_content = $post->post_content . "\n\n" . $block_content;
    wp_update_post(['ID' => $post->ID, 'post_content' => $new_content]);
    
    // Recalculate stats
    recalculate_location_stats($post->ID);
    
    // Delete comment
    wp_delete_comment($comment_id, true);
}
```

### Recalculate Location Stats
```php
function recalculate_location_stats($post_id) {
    // Parse all blocks in post_content
    // Filter for wing-map/wing-review blocks
    // Calculate: average rating, review count
    // Update theme meta: wing_average_rating, wing_review_count
}
```

### Block Display
**First block only**: Displays location details (address, phone, website, hours, price, services)
**All blocks**: Display review ratings, reviewer name, date, review text

---

## Plugin 3: Wing Submit

### Purpose
Provide a form block for users to submit new wing locations or reviews for existing locations, with Nominatim geocoding integration.

### Responsibility (Single)
- Register and render the `wing-submit/wing-submit` block
- Provide submission form for new locations or reviews
- Integrate Nominatim geocoding (server-side)
- Implement rate limiting (1 review per IP per hour)
- Implement honeypot spam prevention
- Handle nonce verification
- Create wing_location posts or comments with metadata

### File Structure
```
plugins/wing-submit/
├── wing-submit.php (main plugin)
├── src/wing-submit/
│   ├── block.json
│   ├── edit.js (editor preview)
│   ├── index.js (block registration)
│   ├── editor.scss
│   └── frontend.js (form submission)
└── build/wing-submit/ (compiled assets)
```

### Data Handling

**Form Input** → **Theme Meta Mapping**:
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

**Fallback Data Retrieval** (if theme meta unavailable):
```php
function get_location_info_for_post($post_id) {
    $meta_helper = get_meta_helper();
    
    if ($meta_helper) {
        // Get from theme meta
        return $meta_helper::get_location_meta($post_id);
    }
    
    // Fallback: get from first wing-review block
    $blocks = parse_blocks(get_post_field('post_content', $post_id));
    $wing_reviews = array_filter($blocks, function($b) {
        return 'wing-map/wing-review' === ($b['blockName'] ?? '');
    });
    
    if (!empty($wing_reviews)) {
        return reset($wing_reviews)['attrs'];
    }
    
    return [];
}
```

### Geocoding Service
- **Service**: Nominatim API (OpenStreetMap)
- **Endpoint**: `https://nominatim.openstreetmap.org/search`
- **Server-side only** - never client-side requests
- **Rate limit**: 1 request/second (enforced by server)
- **User-Agent**: `WingSubmit/0.1.0 (https://chubes.net)`

### Security Features

1. **Nonce Verification**
   - Check nonce on form submission
   - Prevent CSRF attacks

2. **Input Sanitization**
   - `sanitize_text_field()` for text inputs
   - `floatval()` for coordinates
   - `esc_url_raw()` for URLs
   - `sanitize_email()` for emails

3. **Rate Limiting**
   - 1 review per IP per hour
   - Checked before processing submission
   - Prevents spam and API abuse

4. **Honeypot**
   - Hidden form field that legitimate users won't fill
   - If filled, reject submission silently

5. **Output Escaping**
   - `esc_html()` for text content
   - `esc_attr()` for HTML attributes
   - `esc_url()` for URLs

---

## Integration: How Components Work Together

### Data Flow Diagram
```
User Submits Review (wing-submit form)
    ↓
Creates comment with metadata
    ↓
Admin approves comment (WordPress admin)
    ↓
Hook: wp_set_comment_status (wing-review plugin)
    ↓
Converts comment → wing-map/wing-review block
    ↓
Appends block to post_content
    ↓
Recalculates aggregate stats → Theme Meta
    ↓
Deletes original comment
    ↓
Map block reads updated meta (wing-map-display)
    ↓
Renders map with updated location
```

### Key Integration Points

1. **Theme Meta as Central Source**
   - All three plugins check for `CluckinChuck\Wing_Location_Meta`
   - Fallback to wing-review blocks if meta unavailable
   - Single source of truth prevents data conflicts

2. **Comment-to-Block Pipeline**
   - wing-submit creates comments with metadata
   - wing-review converts to blocks on approval
   - Stats update in theme meta
   - wing-map-display reads updated data

3. **Fallback Strategy**
   - If theme disabled: plugins still work with block attributes
   - If review blocks exist: map displays without theme meta
   - Ensures system resilience

---

## Key Architectural Principles Applied

### Single Responsibility
✅ Theme: Data ownership + presentation  
✅ wing-map-display: Render map only  
✅ wing-review: Display reviews + convert comments  
✅ wing-submit: Handle submissions + geocoding  

### KISS (Keep It Simple, Stupid)
✅ Direct data flow: Theme → Plugins → Frontend  
✅ No unnecessary abstractions  
✅ Clear boundaries prevent complexity  

### Single Source of Truth
✅ Theme owns wing_location posts and metadata  
✅ No duplicate data storage  
✅ All plugins reference theme as primary source  

### No Forbidden Fallbacks
✅ No placeholder data  
✅ No legacy compatibility layers  
✅ Fail fast with clear error messages  

---

## Development Workflow

### Setup
```bash
# Install plugin dependencies
cd plugins/wing-map-display && npm install
cd ../wing-review && npm install
cd ../wing-submit && npm install
```

### Watch Mode
```bash
# Each plugin separately
cd plugins/wing-map-display
npm run start  # Rebuilds on file changes
```

### Production Build
```bash
# Build each component
cd plugins/wing-map-display && ./build.sh
cd plugins/wing-review && ./build.sh
cd plugins/wing-submit && ./build.sh
cd themes/cluckin-chuck && ./build.sh
```

---

## Success Criteria - ALL ACHIEVED ✅

✅ Theme successfully registers wing_location CPT  
✅ Theme provides `Wing_Location_Meta` helper class  
✅ Three plugins register blocks independently  
✅ wing-submit handles form submissions + geocoding  
✅ wing-review converts comments to blocks + recalculates stats  
✅ wing-map-display renders interactive map with fallback data  
✅ Theme templates display locations correctly  
✅ Build scripts create valid production packages  
✅ All three plugins can be worked on independently  
✅ Single source of truth maintained (theme data)  
✅ Clear separation of concerns throughout  
✅ Complete documentation for all components  

---

## Future Extensibility

### Plugin Extensions
- Custom taxonomies (wing styles, regions)
- REST API endpoints for external integrations
- Geolocation search functionality
- Additional rating dimensions
- User submission workflow enhancements

### Theme Extensions
- Additional templates
- More block patterns
- Custom block styles
- Color scheme variations
- Typography refinements
- Accessibility improvements

### New Plugins
- Easily add new plugins without modifying existing ones
- All plugins follow same pattern: check for theme meta, use block attributes as fallback
- Clear integration points established
