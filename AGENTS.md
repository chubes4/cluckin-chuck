# AGENTS.md

WordPress plugin (wing-map) + block theme (cluckin-chuck). Plugin handles business logic; theme handles presentation only.

## Commands
```bash
cd wing-map && npm run build    # Build blocks (src/ → build/)
cd wing-map && npm run start    # Watch mode
cd wing-map && composer phpcs   # PHP linting (WordPress standard)
cd wing-map && composer phpcbf  # Auto-fix PHP
cd wing-map && npm run lint:js  # JS linting
./wing-map/build.sh             # Production ZIP
```

## Code Style
- **PHP**: WordPress coding standards, PSR-4 autoload (`WingMap\` → `src/`), PHP 8.0+ type hints
- **JS**: @wordpress/scripts, vanilla JS (no jQuery), ES6+ modules
- **Naming**: `snake_case` PHP functions/variables, `camelCase` JS, `PascalCase` classes
- **Security**: Always use nonces (`wp_verify_nonce`), sanitize all input (`sanitize_text_field`, `floatval`, etc.), escape output (`esc_html`, `esc_attr`)
- **Blocks**: Source in `src/[block-name]/`, compiled to `build/[block-name]/`, server-side rendered via PHP

## Architecture
- **Plugin** (`wing-map/`): CPT `wing_location`, 3 Gutenberg blocks, AJAX handlers
- **Theme** (`themes/cluckin-chuck/`): FSE block theme, templates only
- **Data**: All review/location data stored in wing-review block attributes (not post meta)
- **Namespace**: `WingMap\{PostTypes,Blocks,Comments}\ClassName`
