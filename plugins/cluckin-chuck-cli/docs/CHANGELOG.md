# Changelog

## [0.1.1] - 2026-03-30

### Added
- add CLI submit commands for reviews and locations
- add reject-review, approve-location, reject-location across all surfaces

### Changed
- Add cluckin-chuck-cli and cluckin-chuck-api plugins (v0.1.0)

### Fixed
- add page.html template and index.php fallback, update lockfiles and homeboy configs

## 0.1.0

- Initial release
- `wp cluckinchuck locations list` — list wing locations
- `wp cluckinchuck locations get` — get single location details
- `wp cluckinchuck locations update` — update location metadata
- `wp cluckinchuck locations geocode` — geocode an address
- `wp cluckinchuck reviews list` — list reviews for a location
- `wp cluckinchuck reviews approve` — approve a pending review
- `wp cluckinchuck reviews recalculate` — recalculate location stats
- `wp cluckinchuck reviews pending` — list pending submissions
