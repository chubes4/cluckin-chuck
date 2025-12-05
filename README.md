# Cluckin Chuck: Wing Map Showcase

A WordPress project combining a custom plugin and block theme to showcase chicken wing locations across the USA with interactive Leaflet maps.

## Project Overview

Cluckin Chuck demonstrates clean WordPress architecture by separating business logic (plugin) from presentation (theme). The project features an interactive map interface for discovering wing locations, complete manual data entry workflow, and modern Full Site Editing capabilities.

## Architecture

This project follows the **Single Responsibility Principle** with clear separation of concerns:

### Wing Map Plugin (`/wing-map/`)
**Responsibility**: All business logic and data management

- Custom post type: `wing_location`
- Metadata management (address, coordinates, ratings, contact info)
- Two Gutenberg blocks: `wing-map/map-display`, `wing-map/wing-review`
- User review submission system with moderation and automatic block conversion
- Leaflet.js map integration with OpenStreetMap
- Nominatim geocoding service integration
- Admin meta box UI for manual data entry

### Cluckin Chuck Theme (`/cluckin-chuck/`)
**Responsibility**: Pure presentation layer

- WordPress block theme with Full Site Editing (FSE)
- Custom templates for wing locations
- Wing sauce orange color scheme
- Responsive layouts
- Template parts (header, footer)
- Block patterns for wing location display

## Directory Structure

```
/cluckin-chuck/
├── README.md (this file)
├── plan.md (implementation reference and architecture documentation)
├── wing-map/ (PLUGIN)
│   ├── wing-map.php
│   ├── src/
│   │   ├── PostTypes/WingLocation.php
│   │   ├── Blocks/MapDisplay.php
│   │   ├── Blocks/WingReview.php
│   │   ├── Blocks/WingSubmit.php
│   │   ├── Comments/ReviewCommentForm.php
│   │   ├── map-display/ (block source)
│   │   ├── wing-review/ (block source)
│   │   └── wing-submit/ (block source)
│   ├── build/
│   │   ├── map-display/ (compiled block)
│   │   ├── wing-review/ (compiled block)
│   │   └── wing-submit/ (compiled block)
│   ├── assets/
│   │   └── wing-marker.svg
│   ├── composer.json
│   ├── package.json
│   ├── build.sh
│   └── build/ (production output)
└── cluckin-chuck/ (THEME)
    ├── style.css
    ├── theme.json
    ├── functions.php
    ├── templates/
    ├── parts/
    ├── patterns/
    ├── build.sh
    └── build/ (production output)
```

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- Node.js and npm (for development)
- Composer (for development)

## Quick Start

### Development Setup

1. **Clone the repository:**
   ```bash
   cd /path/to/wordpress/wp-content/
   # Clone to appropriate directories
   ```

2. **Install plugin dependencies:**
   ```bash
   cd wing-map
   composer install
   npm install
   ```

3. **Build plugin assets:**
   ```bash
   npm run build  # Production build
   npm run start  # Development watch mode
   ```

4. **Activate components:**
   - Go to WordPress Admin → Plugins
   - Activate "Wing Map" plugin
   - Go to Appearance → Themes
   - Activate "Cluckin Chuck" theme

### Production Build

**Build Plugin:**
```bash
cd wing-map
chmod +x build.sh
./build.sh
```
Output: `wing-map/build/wing-map.zip`

**Build Theme:**
```bash
cd cluckin-chuck
chmod +x build.sh
./build.sh
```
Output: `cluckin-chuck/build/cluckin-chuck.zip`

## Features

### Interactive Wing Map
- Leaflet.js integration with OpenStreetMap tiles
- Custom chicken wing marker icons
- Auto-fitting map bounds to show all locations
- Click markers for location details and ratings
- Responsive design (600px desktop, 400px mobile)

### User Review System
- Custom review submission form on wing location posts
- Star ratings for overall quality, sauce, and crispiness
- First review can populate missing location metadata
- Comment moderation workflow (reviews require approval)
- Automatic conversion of approved reviews to permanent blocks
- Security: nonce verification, honeypot spam prevention, rate limiting (1 review/hour per IP)
- Reviews displayed as embedded `wing-map/wing-review` blocks in post content

### Wing Location Management
- Custom post type with full WordPress editor support
- Comprehensive metadata fields:
  - Location & coordinates (geocoded via Nominatim)
  - Ratings (overall, sauce quality, crispiness)
  - Contact information (phone, website, email)
  - Business details (hours, price range, services)
- Admin meta box with AJAX geocoding
- Manual data entry workflow

### WordPress Block Integration
- `wing-map/map-display` Gutenberg block for interactive maps
- `wing-map/wing-review` Gutenberg block for displaying reviews
- Both server-side rendered for optimal performance
- Used in templates via Full Site Editing
- Placeholder previews in editor

### Full Site Editing Theme
- Modern WordPress FSE capabilities
- Custom templates for wing locations
- Wing sauce orange color palette
- System font stack for performance
- Responsive spacing and typography scales

## Usage

### Adding Wing Locations

1. Go to WordPress Admin → Wing Locations → Add New
2. Enter the location title (restaurant name)
3. Add description and featured image
4. In "Wing Location Details" meta box:
   - Enter street address
   - Click "Geocode Address" to auto-fill coordinates
   - Add ratings, contact info, and business details
5. Publish the location

### Displaying the Map

Add the Wing Map block to any page/post:
1. Edit page/post in block editor
2. Click "+" to add block
3. Search for "Wing Map"
4. Insert block

Or use in templates via Full Site Editing:
- Templates include the block by default
- Customize via Appearance → Editor

## Technical Details

### Map Library
- **Leaflet v1.9.4** - Open source, no API keys required
- OpenStreetMap tile provider
- Custom marker icons (32x32px SVG)
- Auto-fitting bounds with 50px padding

### Geocoding Service
- **Nominatim API** (OpenStreetMap)
- Free, open-source geocoding
- Server-side requests only
- 1 request/second rate limit compliance
- User-Agent header: `WingMap/1.0 (https://chubes.net)`

### Build Process
- **Plugin**: npm (wp-scripts) + Composer with production optimization
- **Theme**: File copying with exclusions
- Both create clean directories + ZIP files
- Verification logic confirms successful builds

## Documentation

- **`plan.md`** - Complete implementation reference and architectural documentation
- **`wing-map/README.md`** - Detailed plugin documentation
- **`cluckin-chuck/README.md`** - Theme-specific documentation

## Clean Architecture Principles

### Single Responsibility
- Plugin = data and functionality
- Theme = presentation only
- Each PHP class has one responsibility

### KISS (Keep It Simple, Stupid)
- Direct, centralized solutions
- Clear boundaries between components
- Minimal abstractions

### Single Source of Truth
- All wing location data lives in plugin
- No duplicate data storage
- Theme consumes what plugin provides

### No Forbidden Fallbacks
- No placeholder data
- No legacy support for removed features
- One correct way to do things

## Future Extensibility

### Plugin Extensions (Business Logic)
- Custom taxonomies (wing styles, regions)
- User submission forms
- Public rating/voting system
- REST API endpoints
- Geolocation search
- LLM-assisted metadata filling (Phase 4 - planned)

### Theme Extensions (Presentation)
- Additional templates
- More block patterns
- Custom block styles
- Color scheme variations
- Typography refinements

## Author & Credits

**Author**: Chris Huber
- Website: [chubes.net](https://chubes.net)
- GitHub: [@chubes4](https://github.com/chubes4)
- Founder & Editor: [Extra Chill](https://extrachill.com)
- GitHub Organization: [Extra-Chill](https://github.com/Extra-Chill)

**Built with**:
- WordPress 6.0+
- Leaflet.js v1.9.4
- OpenStreetMap
- Nominatim Geocoding Service

## License

GPL v2 or later

---

Built with love for chicken wing enthusiasts across the USA 🍗
