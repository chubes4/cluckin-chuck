# Cluckin Chuck WordPress Block Theme

A modern WordPress block theme dedicated to showcasing chicken wing locations across the USA. Built with Full Site Editing (FSE) for maximum flexibility and ease of use.

## Theme Responsibilities

This theme **owns core project data** and provides the presentation layer:

### Data Ownership
- **Custom Post Type**: `wing_location` (slug: `wings`)
- **Metadata Management**: All location data via `Wing_Location_Meta` class
- **Single Source of Truth**: Primary data source for all plugins

### Presentation Layer
- Full Site Editing (FSE) templates
- Wing sauce color scheme
- Responsive layouts and typography
- Template parts (header, footer)

## Integration with Plugins

The four separate plugins consume data from this theme:

- **wing-location-details** - Displays location hero with address, phone, hours, services
- **wing-map-display** - Reads location coordinates and displays interactive map
- **wing-review** - Displays reviews and converts approved comments to blocks
- **wing-review-submit** - Creates submissions and saves to theme metadata

All plugins require `CluckinChuck\Wing_Location_Meta` for data access.

## Features

- **Full Site Editing (FSE)** - Customize every aspect of your site using the WordPress block editor
- **Wing Sauce Color Scheme** - Custom color palette featuring wing sauce orange and golden crispy colors
- **Responsive Design** - Mobile-friendly layouts using WordPress block patterns
- **Custom Templates** - Specialized templates for wing location posts and archives
- **Clean Architecture** - Pure presentation layer with no business logic

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- **Four plugins** (for full functionality):
  - wing-location-details
  - wing-map-display
  - wing-review
  - wing-review-submit

## Installation

### From ZIP File

1. Download `cluckin-chuck.zip`
2. Go to WordPress Admin → Appearance → Themes
3. Click "Add New" → "Upload Theme"
4. Choose the ZIP file and click "Install Now"
5. Click "Activate"

### Manual Installation

1. Upload the theme folder to `/wp-content/themes/`
2. Go to WordPress Admin → Appearance → Themes
3. Find "Cluckin Chuck" and click "Activate"

**Note**: The theme directory is named `cluckin-chuck` (located at `/wp-content/themes/cluckin-chuck/`)

## Templates

The theme includes the following templates:

- **index.html** - Default template for all content
- **single-wing_location.html** - Individual wing location display
- **archive-wing_location.html** - Grid view of all wing locations

## Template Parts

- **header.html** - Site header with logo and navigation
- **footer.html** - Site footer with copyright and credits

## Customization

### Color Palette

The theme provides a custom color palette in `theme.json`:

- **Wing Sauce Orange** (#d35400) - Primary brand color
- **Wing Sauce Light** (#e67e22) - Hover states and accents
- **Golden Crispy** (#f39c12) - Secondary color
- **Cream** (#fdf6e3) - Background color
- **Dark Brown** (#2c1810) - Text color

### Typography

The theme uses system fonts for optimal performance:
- Font sizes: Small (0.875rem), Medium (1rem), Large (1.5rem), X-Large (2.5rem)
- Line height: 1.6 for body text, 1.2 for headings

### Spacing

Consistent spacing scale defined in `theme.json`:
- 20 (0.5rem) - X-Small
- 30 (1rem) - Small
- 40 (1.5rem) - Medium
- 50 (2rem) - Large
- 60 (3rem) - X-Large

### Layout

- Content width: 1200px
- Wide width: 1400px

## Theme Structure

```
cluckin-chuck/
├── style.css           (theme headers and global styles)
├── theme.json          (FSE configuration)
├── functions.php       (theme setup)
├── README.md           (this file)
├── build.sh            (production build script)
├── inc/
│   ├── class-wing-location.php (CPT registration)
│   └── class-wing-location-meta.php (metadata management)
├── templates/
│   ├── index.html
│   ├── single-wing_location.html
│   └── archive-wing_location.html
└── parts/
    ├── header.html
    └── footer.html
```

## Theme Metadata API

The theme provides the `Wing_Location_Meta` helper class for metadata management:

```php
namespace CluckinChuck;

class Wing_Location_Meta {
    // Get all metadata for a wing location
    public static function get_location_meta($post_id);
    
    // Update metadata for a wing location
    public static function update_location_meta($post_id, $meta_data);
}
```

**Available metadata keys**:
- `wing_address` - Street address
- `wing_latitude` - Decimal latitude
- `wing_longitude` - Decimal longitude
- `wing_phone` - Phone number
- `wing_website` - Website URL
- `wing_email` - Email address
- `wing_hours` - Operating hours
- `wing_price_range` - Price range ($, $$, $$$, $$$$)
- `wing_takeout` - Boolean (takeout available)
- `wing_delivery` - Boolean (delivery available)
- `wing_dine_in` - Boolean (dine-in available)
- `wing_average_rating` - Average rating (1-5 float)
- `wing_review_count` - Count of reviews

## Plugin Integration

The theme works seamlessly with four separate plugins:

### wing-location-details
- Displays location hero block with address, phone, hours, services
- Reads all data from theme metadata
- Shows aggregate rating and review count

### wing-map-display
- Displays an interactive Leaflet map
- Reads location coordinates from theme metadata
- Shows all published wing locations with markers

### wing-review
- Displays user reviews as blocks
- Converts approved comments to review blocks
- Recalculates aggregate ratings

### wing-review-submit
- Provides a form block for user submissions
- Creates wing_location posts and reviews
- Geocodes addresses via Nominatim API
- Saves data to theme metadata

## Development

### Project Location

This theme is located in the project directory structure at `/themes/cluckin-chuck/` within the development repository. When deployed to WordPress, it should be placed in `/wp-content/themes/cluckin-chuck/`.

### Building for Production

Run the build script to create a production-ready ZIP file:

```bash
chmod +x build.sh
./build.sh
```

This creates:
- `build/cluckin-chuck/` - Clean production directory
- `build/cluckin-chuck.zip` - Production ZIP file ready for WordPress deployment

### Editing Templates

All templates are HTML files using WordPress block markup. Edit them in:
- WordPress Admin → Appearance → Editor
- Or directly in the theme files using a code editor

## Support

For issues, questions, or contributions:
- Author: Chris Huber
- Website: https://chubes.net
- GitHub: https://github.com/chubes4

## License

This theme is licensed under GPL v2 or later.

## Credits

Built with love for chicken wing enthusiasts across the USA 🍗

---

**Version:** 0.1.1  
**Last Updated:** 2025

---

## Additional Documentation

- **Root README.md** - Complete project overview and setup instructions
- **AGENTS.md** - Development standards and architecture principles
- **plan.md** - Implementation reference and architectural decisions
- **plugins/wing-location-details/README.md** - Location details block documentation
- **plugins/wing-map-display/README.md** - Map block documentation
- **plugins/wing-review/README.md** - Review block documentation
- **plugins/wing-review-submit/README.md** - Submission form documentation

