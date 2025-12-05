# Cluckin Chuck WordPress Block Theme

A modern WordPress block theme dedicated to showcasing chicken wing locations across the USA. Built with Full Site Editing (FSE) for maximum flexibility and ease of use.

## Overview

Cluckin Chuck is a clean, minimal block theme designed to work seamlessly with the Wing Map plugin. It provides beautiful templates for displaying wing location posts, archives, and all the presentation layer needed for a wing-focused website.

## Features

- **Full Site Editing (FSE)** - Customize every aspect of your site using the WordPress block editor
- **Wing Sauce Color Scheme** - Custom color palette featuring wing sauce orange and golden crispy colors
- **Responsive Design** - Mobile-friendly layouts using WordPress block patterns
- **Custom Templates** - Specialized templates for wing location posts and archives
- **Clean Architecture** - Pure presentation layer with no business logic

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- **Wing Map Plugin** (for full functionality with wing location custom post type)

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
├── templates/
│   ├── index.html
│   ├── single-wing_location.html
│   └── archive-wing_location.html
└── parts/
    ├── header.html
    └── footer.html
```

## Development

### Project Location

This theme is located in the project directory structure at `/cluckin-chuck/cluckin-chuck/` within the development repository. When deployed to WordPress, it should be placed in `/wp-content/themes/cluckin-chuck/`.

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

## Integration with Wing Map Plugin

This theme is designed to work with the Wing Map plugin, which provides:
- `wing_location` custom post type
- `wing-map/map-display` block for interactive maps
- `wing-map/wing-review` block for displaying user-submitted reviews
- Wing location metadata (address, coordinates, ratings)
- User review submission and moderation system

The theme displays reviews embedded in wing location posts as `wing-map/wing-review` blocks, which are automatically generated from approved user comments.

**Note:** The theme will work without the plugin, but wing location functionality requires the plugin to be active.

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

**Version:** 0.1.0
**Last Updated:** 2025

---

## Additional Documentation

- **Root README.md** - Complete project overview and architecture
- **plan.md** - Detailed implementation reference and architectural documentation
- **wing-map/README.md** - Wing Map plugin documentation
