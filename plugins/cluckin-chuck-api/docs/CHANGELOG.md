# Changelog

## [0.1.1] - 2026-03-30

### Added
- add reject-review, approve-location, reject-location across all surfaces

### Changed
- Add cluckin-chuck-cli and cluckin-chuck-api plugins (v0.1.0)

### Fixed
- add page.html template and index.php fallback, update lockfiles and homeboy configs

## 0.1.0

- Initial release
- REST namespace: `cluckin-chuck/v1`
- `GET /locations` — list wing locations
- `GET /locations/<id>` — get single location details
- `PUT /locations/<id>` — update location metadata
- `POST /locations/geocode` — geocode an address
- `POST /locations/submit` — submit a new wing location (public)
- `GET /reviews/<post_id>` — list reviews for a location
- `POST /reviews/approve` — approve a pending review
- `POST /reviews/recalculate` — recalculate location stats
- `GET /reviews/pending` — list pending submissions
- `POST /reviews/submit` — submit a review for existing location (public)
