# Changelog

## [0.3.1] - 2026-07-12

### Fixed
- generate slugs when publishing locations

## [0.3.0] - 2026-07-12

### Added
- enrich wing location browsing

## [0.2.0] - 2026-05-25

### Added
- attach chat-uploaded images to wing locations as featured images (#11, #13)

## [0.1.7] - 2026-05-25

### Fixed
- auto-publish-aware admin emails + submitter confirmation (#8, #9)

## [0.1.6] - 2026-05-25

### Fixed
- stop exposing reviewer identity to the chat agent (#7)
- suppress false-positive 'Geocoding failed' notice on publish

## [0.1.5] - 2026-03-30

### Added
- auto-fill reviewer identity from logged-in user account

## [0.1.4] - 2026-03-30

### Added
- add reject-review, approve-location, reject-location across all surfaces

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
