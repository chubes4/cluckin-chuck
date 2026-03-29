# Cluckin' Chuck

Chicken wing location reviews — find the best wings near you.

**Live site:** https://cluckinchuck.saraichinwag.com
**Author:** [Chris Huber](https://chubes.net) ([@chubes4](https://github.com/chubes4))

## What Is This

A WordPress monorepo: one block theme + seven plugins that power a chicken wing review site with interactive maps, user submissions, an AI chat agent, a REST API, and WP-CLI tooling.

Visitors discover wing spots on a Leaflet map, read reviews, and submit their own. Chuck — the AI agent — helps with location lookups and review submissions via a floating chat widget, and handles admin operations via Discord.

## Architecture

```
┌───────────────────────────────────────────────────────┐
│                  cluckin-chuck theme                   │
│    Owns: wing_location CPT + Wing_Location_Meta       │
│    Provides: FSE templates, color scheme, layout      │
└──────────────────────┬────────────────────────────────┘
                       │
        All plugins read/write via Wing_Location_Meta
                       │
    ┌──────┬───────────┼───────────┬──────────┐
    ▼      ▼           ▼           ▼          ▼
 location  map       review     review      agent
 details   display   display    submit      kit
 (hero)    (Leaflet) (blocks)   (form)      (AI tools)
                                              │
                              ┌────────────────┤
                              ▼                ▼
                            API              CLI
                         (REST)           (WP-CLI)
```

### Data Flow

1. **Visitor submits** a wing location via `wing-review-submit` form (or Chuck chat)
2. Address is **geocoded** via Nominatim API — coordinates stored automatically
3. A `wing_location` post is created with all metadata via `Wing_Location_Meta`
4. **Location details** renders the hero block (address, hours, services, ratings)
5. **Map display** plots all locations on an interactive Leaflet map
6. **Reviews** stored as comments, converted to blocks on approval, stats recalculated

## Components

### Theme

| Component | Path | Description |
|-----------|------|-------------|
| **cluckin-chuck** | `themes/cluckin-chuck/` | Block theme (FSE). Owns `wing_location` CPT, metadata API, geocoding, templates, wing sauce color palette |

### Wing Plugins

| Plugin | Path | Block | Description |
|--------|------|-------|-------------|
| **wing-location-details** | `plugins/wing-location-details/` | `wing-location-details/wing-location-details` | Hero display: address, website, Instagram, ratings, price per wing |
| **wing-map-display** | `plugins/wing-map-display/` | `wing-map-display/wing-map-display` | Interactive Leaflet map with all wing locations |
| **wing-review** | `plugins/wing-review/` | `wing-review/wing-review` | Review display + comment-to-block conversion on approval |
| **wing-review-submit** | `plugins/wing-review-submit/` | `wing-review-submit/wing-review-submit` | Frontend submission form with Nominatim geocoding, rate limiting, honeypot |

### Platform Plugins

| Plugin | Path | Description |
|--------|------|-------------|
| **cluckin-chuck-agent-kit** | `plugins/cluckin-chuck-agent-kit/` | Chat tools bridging wing abilities to [Data Machine](https://github.com/Extra-Chill/data-machine)'s agent system. Configures the frontend chat widget |
| **cluckin-chuck-api** | `plugins/cluckin-chuck-api/` | Unified REST API under `cluckinchuck/v1` — wraps abilities from wing plugins |
| **cluckin-chuck-cli** | `plugins/cluckin-chuck-cli/` | WP-CLI commands under `wp cluckinchuck` — wraps abilities from wing plugins |

## AI Agent (Chuck)

Chuck is the site's AI assistant, powered by [Data Machine](https://github.com/Extra-Chill/data-machine):

- **Frontend visitors** — floating chat widget helps find wing spots, submit reviews conversationally
- **Admin (Discord)** — site management, content ops, deployment via [kimaki](https://kimaki.xyz)
- **Same identity** in both contexts — what changes is tool access

The agent kit plugin registers chat tools (location search, review submission, stats lookup) and configures the frontend chat widget with the `cluckinchuck` agent slug.

## Directory Structure

```
cluckin-chuck/
├── README.md                          # This file
├── AGENTS.md                          # Development standards + architecture
├── plan.md                            # Implementation reference
├── docs/
│   ├── CHANGELOG.md                   # Version history
│   └── api-reference.md               # REST API documentation
├── themes/
│   └── cluckin-chuck/
│       ├── style.css                  # Theme headers + global styles
│       ├── theme.json                 # FSE config, colors, typography
│       ├── functions.php              # Theme setup
│       ├── inc/
│       │   ├── class-wing-location.php        # CPT registration
│       │   ├── class-wing-location-meta.php   # Metadata API
│       │   └── geocoding.php                  # Nominatim integration
│       ├── templates/                 # FSE templates
│       └── parts/                     # Header, footer
├── plugins/
│   ├── wing-location-details/         # Location hero block
│   ├── wing-map-display/              # Map block (Leaflet)
│   ├── wing-review/                   # Review block + conversion
│   ├── wing-review-submit/            # Submission form block
│   ├── cluckin-chuck-agent-kit/       # AI agent chat tools
│   ├── cluckin-chuck-api/             # REST API surface
│   └── cluckin-chuck-cli/             # WP-CLI surface
└── .github/
    └── copilot-instructions.md
```

## Development

### Requirements

- WordPress 6.9+
- PHP 8.0+
- Node.js and npm (for block development)
- [Data Machine](https://github.com/Extra-Chill/data-machine) (for agent features)

### Build & Deploy

All builds and deployments use [homeboy](https://github.com/Extra-Chill/homeboy):

```bash
homeboy build cluckinchuck     # Test, lint, build all components
homeboy deploy cluckinchuck    # Deploy to production
```

### Plugin Development

```bash
# Watch mode (rebuilds on file changes)
cd plugins/<plugin-name>
npm install
npm run start

# Production build
npm run build
```

### Theme Development

No build step — FSE templates and `theme.json` work directly. The theme does have a `build/location-meta-panel/` for the admin meta box editor script.

## External Services

| Service | Used By | Purpose |
|---------|---------|---------|
| **Nominatim** (OpenStreetMap) | Theme geocoding | Address → coordinates. Server-side only, 1 req/sec, cached 24h |
| **Leaflet.js** v1.9.4 | wing-map-display | Interactive maps. No API key required |
| **Data Machine** | agent-kit | AI agent runtime — memory, pipelines, chat tools |

## License

GPL v2 or later
