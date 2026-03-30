# Changelog

## [0.1.3] - 2026-03-30

### Changed
- Remove all legacy REST routes, handlers, and deprecated functions
- Migrate REST endpoints to cluckin-chuck-api unified namespace
- Add submission abilities + action hooks for admin notifications
- Add homeboy component configs for version management and deployment
- Bump version to 0.1.2
- Bump version to 0.1.1

### Fixed
- auto-approve submissions from privileged users
- add page.html template and index.php fallback, update lockfiles and homeboy configs

## 0.1.2

- Initial tracked release
- Submit form block for wing reviews and locations
- Pending review comment creation with metadata
- Pending location post creation with block content
- Admin email notifications on submission
- Three abilities: submit-review, submit-location, list-pending
