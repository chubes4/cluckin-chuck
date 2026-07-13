# Changelog

## [0.7.0] - 2026-07-13

### Added
- discover restaurant addresses before asking

## [0.6.1] - 2026-07-12

### Fixed
- simplify fallback wing artwork

## [0.6.0] - 2026-07-12

### Added
- connect wing discovery across site

## [0.5.2] - 2026-07-12

### Fixed
- replace utility shortcodes with blocks

## [0.5.1] - 2026-07-12

### Fixed
- use current year in footer

## [0.5.0] - 2026-07-12

### Added
- add review photos and wing galleries

## [0.4.0] - 2026-07-12

### Added
- connect wing discovery and submissions

## [0.3.0] - 2026-07-12

### Added
- link wing locations in header

## [0.2.0] - 2026-07-12

### Added
- enrich wing location browsing

### Changed
- Remove all legacy REST routes, handlers, and deprecated functions
- Migrate REST endpoints to cluckin-chuck-api unified namespace
- Add Abilities API foundation: category + 4 core location abilities
- Add homeboy component configs for version management and deployment
- docus updated
- Implement theme-owned metadata system and align all plugins to use Wing_Location_Meta
- Initial commit: cluckin-chuck monorepo with theme and 3 block plugins

### Fixed
- suppress false-positive 'Geocoding failed' notice on publish
- geocoder retries without suite/unit fragments; update-location auto-re-geocodes
- enable blockGap in spacing settings so WordPress outputs gap CSS
- add default blockGap spacing to theme.json
- add page.html template and index.php fallback, update lockfiles and homeboy configs

## 0.1.3

- Geocoder now retries with suite/unit/apartment fragments stripped when Nominatim returns no results (fixes "Suite 301"-style addresses geocoding to wrong coordinates).
- Geocoder requests now scope to US results and send `Accept-Language: en`.
- `cluckin-chuck/update-location` ability now auto-re-geocodes when `wing_address` changes and explicit coordinates are not provided. Returns new `geocoded` boolean in the response.

## 0.1.2

- Previous release.
