# Cluckin Chuck: Wing Map Showcase

A WordPress project combining a block theme and three separate plugins to showcase chicken wing locations across the USA with interactive Leaflet maps.

## Project Overview

Cluckin Chuck demonstrates clean WordPress architecture by separating concerns: the theme owns data and presentation, while three plugins provide isolated functionality. The project features an interactive map interface for discovering wing locations, user review submission with geocoding, and modern Full Site Editing capabilities.

## Architecture

This project follows the **Single Responsibility Principle** with clear separation of concerns:

### Theme (`/themes/cluckin-chuck/`)
**Responsibility**: Data ownership + presentation layer

- Custom post type: `wing_location` (slug: `wings`)
- Metadata management (address, coordinates, ratings, contact info)
- Single source of truth for all location data
- WordPress block theme with Full Site Editing (FSE)
- Custom templates for wing locations
- Wing sauce orange color scheme
- Template parts (header, footer)

### Three Plugins (for isolated development)

1. **wing-map-display** (`/plugins/wing-map-display/`)
   - Single responsibility: Interactive map display
   - Block: `wing-map/map-display`
   - Reads location data from theme metadata (or wing-review blocks as fallback)
   - Leaflet.js map integration with OpenStreetMap

2. **wing-review** (`/plugins/wing-review/`)
   - Single responsibility: Review display + comment-to-block conversion
   - Block: `wing-map/wing-review`
   - Hooks into comment approval workflow
   - Converts approved comments to permanent review blocks
   - Recalculates location aggregate stats

3. **wing-submit** (`/plugins/wing-submit/`)
   - Single responsibility: Submission form + geocoding
   - Block: `wing-submit/wing-submit`
   - User review & location submission form
   - Nominatim geocoding service integration
   - Rate limiting, honeypot, nonce security

## Directory Structure

```
cluckin-chuck/
├── README.md (this file)
├── plan.md (implementation reference)
├── AGENTS.md (development standards)
├── themes/
│   └── cluckin-chuck/
│       ├── style.css
│       ├── theme.json
│       ├── functions.php
│       ├── inc/
│       │   ├── class-wing-location.php (CPT registration)
│       │   └── class-wing-location-meta.php (metadata management)
│       ├── templates/
│       ├── parts/
│       └── build.sh
├── plugins/
│   ├── wing-map-display/
│   │   ├── wing-map-display.php
│   │   ├── src/map-display/
│   │   ├── build/map-display/
│   │   ├── package.json
│   │   └── build.sh
│   ├── wing-review/
│   │   ├── wing-review.php
│   │   ├── src/wing-review/
│   │   ├── build/wing-review/
│   │   ├── package.json
│   │   └── build.sh
│   └── wing-submit/
│       ├── wing-submit.php
│       ├── src/wing-submit/
│       ├── build/wing-submit/
│       ├── package.json
│       └── build.sh
```

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- Node.js and npm (for development)

## Quick Start

### Development Setup

1. **Clone the repository** to your WordPress installation:
   ```bash
   cd /path/to/wordpress/wp-content/
   git clone https://github.com/your-repo/cluckin-chuck.git cluckin-chuck
   cd cluckin-chuck
   ```

2. **Install theme & plugin dependencies:**
   ```bash
   # Theme (no dependencies)
   cd themes/cluckin-chuck

   # Plugin 1: wing-map-display
   cd ../../plugins/wing-map-display
   npm install

   # Plugin 2: wing-review
   cd ../wing-review
   npm install

   # Plugin 3: wing-submit
   cd ../wing-submit
   npm install
   ```

3. **Activate components:**
   - Go to WordPress Admin → Plugins
   - Activate: Wing Map Display, Wing Review, Wing Submit
   - Go to Appearance → Themes
   - Activate: Cluckin Chuck

### Development Build

Each plugin supports watch mode for automatic rebuilds:

```bash
# Plugin development (watch mode)
cd plugins/wing-map-display
npm run start

# Or build once
npm run build
```

### Production Build

```bash
# Build theme
cd themes/cluckin-chuck
chmod +x build.sh
./build.sh
# Output: build/cluckin-chuck.zip

# Build plugin
cd plugins/wing-map-display
chmod +x build.sh
./build.sh
# Output: build/wing-map-display.zip

# Repeat for wing-review and wing-submit
```

## Features

### Interactive Wing Map Block (`wing-map/map-display`)
- Leaflet.js integration with OpenStreetMap tiles
- Custom chicken wing marker icons
- Auto-fitting map bounds to show all locations
- Click markers for location details and ratings
- Responsive design (600px desktop, 400px mobile)
- Provided by: **wing-map-display plugin**

### User Review System (`wing-map/wing-review`)
- Review submission form block for new locations and reviews
- Star ratings for overall quality, sauce, and crispiness
- Comment moderation workflow (reviews require approval)
- Automatic conversion of approved reviews to permanent blocks
- Security: nonce verification, honeypot spam prevention, rate limiting (1 review/hour per IP)
- Location details display (first review block only)
- Provided by: **wing-submit** (form) + **wing-review** (block conversion)

### Wing Location Management
- Custom post type with full WordPress editor support
- Comprehensive metadata fields:
  - Location & coordinates (geocoded via Nominatim)
  - Ratings (overall, sauce quality, crispiness)
  - Contact information (phone, website, email)
  - Business details (hours, price range, services)
- Owned by: **cluckin-chuck theme**

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
4. Use the form block (wing-submit) to submit data with geocoding
5. Publish the location

### Displaying the Map

Add the Wing Map block to any page/post:
1. Edit page/post in block editor
2. Click "+" to add block
3. Search for "Wing Map"
4. Insert block (will display all published wing locations)

Or use in templates via Full Site Editing:
- Single Wing Location template includes the map by default
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
- User-Agent header: `WingSubmit/0.1.0 (https://chubes.net)`

### Build Process
- **Plugins**: npm (wp-scripts) for block compilation
- **Theme**: No build required (FSE uses HTML templates directly)
- Production builds create clean directories + ZIP files
- Verification logic confirms successful builds

## Documentation

- **AGENTS.md** - Development standards and architecture reference
- **plan.md** - Implementation reference and architectural decisions
- **plugins/wing-map-display/README.md** - Map block documentation
- **plugins/wing-review/README.md** - Review block documentation
- **plugins/wing-submit/README.md** - Submission form documentation
- **themes/cluckin-chuck/README.md** - Theme documentation

## Clean Architecture Principles

### Single Responsibility
- Theme = data ownership + presentation
- Each plugin = one focused responsibility
- Each PHP class = one responsibility

### KISS (Keep It Simple, Stupid)
- Direct, centralized solutions
- Clear boundaries between components
- Minimal abstractions

### Single Source of Truth
- Theme owns all wing location data
- No duplicate data storage
- Plugins consume what theme provides

### Three-Plugin Split Benefits
- **Isolated development** - Work on plugins independently
- **Clear responsibilities** - Each plugin has one job
- **Easy testing** - Test each plugin separately
- **Scalable** - Add new plugins without touching existing ones

## Architecture Principles

### Data Flow
1. **Theme** registers CPT and owns all location metadata
2. **wing-submit** plugin collects user submissions and saves to theme meta
3. **wing-review** plugin converts approved comments to review blocks
4. **wing-map-display** plugin queries locations and renders interactive map

### Fallback Strategy
If theme metadata unavailable, plugins fall back to data in wing-review blocks:
- wing-map-display reads coordinates from first review block
- wing-submit reads location details from first review block

This ensures the system works even if theme is disabled, though theme provides primary data source.

## Future Extensibility

### Plugin Extensions
- Custom taxonomies (wing styles, regions)
- REST API endpoints for external integrations
- Geolocation search functionality
- Additional rating dimensions

### Theme Extensions
- Additional templates for different post types
- More block patterns for various layouts
- Custom block styles for plugins
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
