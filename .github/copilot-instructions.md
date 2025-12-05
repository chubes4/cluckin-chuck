# Cluckin Chuck: Wing Map Showcase - AI Coding Guidelines

## Architecture Overview

**Two-Component WordPress System with Strict Separation of Concerns:**

1. **Wing Map Plugin** (`/wing-map/`) - Business logic only
   - Custom post type: `wing_location` (slug: `wings`)
   - All location/review data stored in wing-review block attributes (no post meta)
   - Three Gutenberg blocks: `wing-map/map-display`, `wing-map/wing-review`, `wing-map/wing-submit`
   - User review system with comment-to-block conversion
   - Leaflet.js integration with OpenStreetMap

2. **Cluckin Chuck Theme** (`/cluckin-chuck/`) - Presentation only
   - WordPress block theme (FSE) with wing sauce orange color scheme
   - Three templates: index, single-wing_location, archive-wing_location
   - Template parts: header, footer

**Critical Rule**: Plugin NEVER manages presentation. Theme NEVER queries data or registers post types.

## Essential Development Workflows

### Plugin Development Setup
```bash
cd wing-map
composer install  # Install PHP dependencies
npm install       # Install build tools
npm run start     # Watch mode for block development
npm run build     # Production build of blocks
```

### Block Development Pattern
- **Source files**: NOT in repository (maintain externally)
- **Build command**: `npm run build` compiles to flat `blocks/` directory
- **Output**: `blocks/index.js` + `blocks/index.asset.php` (flat structure)
- **Registration**: Server-side rendered via PHP render callbacks

### Theme Development
- Edit `.html` files in `templates/` or `parts/` directly
- Customize colors/spacing in `theme.json`
- No build step required for theme changes

## PHP Architecture Patterns

### Namespace Structure
```php
namespace WingMap;
├── PostTypes\WingLocation      # CPT registration only
├── Blocks\MapDisplay           # Map block registration/rendering
├── Blocks\WingReview           # Review block registration/rendering + editable in block editor
├── Blocks\WingSubmit           # Submission form block + AJAX handlers + geocoding
└── Comments\ReviewCommentForm  # Comment-to-block conversion
```

### Class Registration Pattern
```php
class WingLocation {
    public static function register() {
        // Register CPT, meta fields, blocks, etc.
    }
}
```

### Security Patterns
- **Nonces**: All forms/AJAX verify `wp_verify_nonce()`
- **Capabilities**: Check `current_user_can('edit_post', $post_id)`
- **Sanitization**: Use type-specific sanitizers (`sanitize_text_field()`, `floatval()`, etc.)
- **Honeypot**: Hidden `wing_website_url` field in review forms

## User Review System Flow

1. **Form Submission**: Custom comment form on `wing_location` posts
2. **Data Storage**: Review saved as WordPress comment with metadata
3. **Moderation**: Admin approves in WordPress Admin → Comments
4. **Conversion**: `wp_set_comment_status` hook triggers block creation
5. **Block Injection**: `wing-map/wing-review` block appended to post content
6. **Cleanup**: Original comment permanently deleted

## Key Integration Points

### Geocoding (Nominatim API)
- **Endpoint**: `https://nominatim.openstreetmap.org/search`
- **Rate Limit**: 1 request/second (server-side enforcement)
- **User-Agent**: `WingMap/1.0 (https://chubes.net)`
- **AJAX Actions**: `wp_ajax_wing_geocode` and `wp_ajax_nopriv_wing_geocode`
- **Location**: Wing-submit block only (WingSubmit::ajax_geocode_handler)

### Leaflet Map Integration
- **Version**: 1.9.4 (CDN: unpkg.com)
- **Default Center**: [39.8283, -98.5795] (USA center), zoom 4
- **Auto-fit**: Bounds with 50px padding when locations exist
- **Marker Icon**: `wing-marker.svg` (32x32px, anchor [16,32])

### Build Scripts
- **Plugin**: `./build.sh` → `build/wing-map/` + `build/wing-map.zip`
- **Theme**: `./build.sh` → `build/cluckin-chuck/` + `build/cluckin-chuck.zip`

## Common Patterns & Conventions

### Wing Review Block Attributes (17 total)
All data stored in block attributes embedded in post content, NOT in custom post meta:

Reviewer Info: `reviewerName`, `reviewerEmail`, `timestamp`
Ratings: `rating` (required), `sauceRating`, `crispinessRating`
Review Content: `reviewText` (required)
Location Data (first review only): `address`, `latitude`, `longitude`, `phone`, `website`, `hours`, `priceRange`, `takeout`, `delivery`, `dineIn`

### Block Behavior
- **Map Block**: Queries all published `wing_location` posts, extracts coordinates from first wing-review block
- **Review Block**: Displays from block attributes (all 17 fields editable in block editor)
- **Submit Block**: Submission form with auto-geocoding, creates wing-review blocks

### Theme Colors (theme.json)
- Wing Sauce Orange: `#d35400`
- Golden Crispy: `#f39c12`
- Dark Brown: `#2c1810`
- Cream: `#fdf6e3`

### File Structure Rules
- **Plugin Assets**: `assets/` (wing-marker.svg)
- **Compiled Blocks**: `build/[block-name]/` with index.js, index.asset.php, block.json per block
- **Block Source**: `src/[block-name]/` with block.json, index.js, edit.js, editor.scss
- **Theme Templates**: `templates/` + `parts/` (.html files for FSE)

## Critical "Don't Do" Rules

- **Never** put business logic in theme files
- **Never** query `wing_location` posts in theme PHP
- **Never** commit block source files (only compiled output)
- **Never** modify `vendor/` directory manually
- **Never** bypass WordPress security functions
- **Never** hardcode coordinates without geocoding validation

## Testing Checklist

1. Insert wing-submit block → Submit new location → Verify pending post created with review block
2. Insert map block → Verify markers appear with popups
3. Submit review via wing-submit → Verify comment saved
4. Approve comment → Verify conversion to wing-review block in post content
5. Edit wing-review block in block editor → Verify all 17 attributes are editable
6. Check responsive breakpoints (map height: 600px desktop, 400px mobile)

## Reference Files

- `wing-map.php` - Plugin initialization and hook registration
- `src/PostTypes/WingLocation.php` - CPT registration pattern
- `src/Blocks/WingSubmit.php` - Submission form + AJAX handlers + geocoding
- `src/Blocks/WingReview.php` - Review block rendering (location data only on first block)
- `src/Comments/ReviewCommentForm.php` - Comment-to-block conversion
- `cluckin-chuck/theme.json` - Theme configuration and color palette
- `src/map-display/frontend.js` - Leaflet map initialization
- `CLAUDE.md` - Comprehensive implementation details</content>
<parameter name="filePath">/Users/chubes/Developer/cluckin-chuck/.github/copilot-instructions.md