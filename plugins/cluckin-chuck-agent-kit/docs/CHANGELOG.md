# Changelog

## [0.1.1] - 2026-03-30

### Added
- add reject-review, approve-location, reject-location across all surfaces

### Changed
- Set frontend chat visibility to team-only (admin) instead of public
- Use cluckinchuck agent slug instead of chuck
- Add cluckin-chuck-agent-kit: chat tools + agent config (v0.1.0)

### Fixed
- add page.html template and index.php fallback, update lockfiles and homeboy configs

## 0.1.0

- Initial release
- 10 chat tools wrapping all Cluckin' Chuck abilities
- Public tools: list_wing_locations, get_wing_location, geocode_address, list_wing_reviews, submit_wing_review, submit_wing_location
- Admin tools: update_wing_location, approve_wing_review, recalculate_wing_stats, list_pending_submissions
- Frontend chat widget config filter (agent_slug: chuck, visibility: public)
