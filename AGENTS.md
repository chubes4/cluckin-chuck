# AGENTS.md

Monorepo containing a WordPress block theme and 3 block plugins for chicken wing location reviews.

## Architecture

- **Theme** (`themes/cluckin-chuck/`): FSE block theme, owns `wing_location` CPT
- **Plugin** (`plugins/wing-map-display/`): Leaflet map block showing all locations
- **Plugin** (`plugins/wing-review/`): Review block with comment-to-block conversion on approval
- **Plugin** (`plugins/wing-submit/`): Submission form block with geocoding
- **Data**: All review/location data stored in wing-review block attributes (not post meta)

## Commands

```bash
# Theme
cd themes/cluckin-chuck && ./build.sh    # Production ZIP

# Plugins (each plugin)
cd plugins/wing-map-display && npm run build   # Build block
cd plugins/wing-map-display && npm run start   # Watch mode

cd plugins/wing-review && npm run build
cd plugins/wing-review && npm run start

cd plugins/wing-submit && npm run build
cd plugins/wing-submit && npm run start
```

## Code Style

- **PHP**: WordPress coding standards, PHP 8.0+ type hints
- **JS**: @wordpress/scripts, vanilla JS (no jQuery), ES6+ modules
- **Naming**: `snake_case` PHP functions/variables, `camelCase` JS, `PascalCase` classes
- **Security**: Always use nonces (`wp_verify_nonce`), sanitize all input (`sanitize_text_field`, `floatval`, etc.), escape output (`esc_html`, `esc_attr`)
- **Blocks**: Source in `src/[block-name]/`, compiled to `build/[block-name]/`, server-side rendered via PHP

## Namespaces

- **Theme**: `CluckinChuck\`
- **wing-map-display**: `WingMapDisplay\`
- **wing-review**: `WingReview\`
- **wing-submit**: `WingSubmit\`
