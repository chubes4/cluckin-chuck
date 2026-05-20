# Changelog

## [0.2.0] - 2026-05-04

### Added
- New `cluckin-chuck` Data Machine agent mode. Centralizes Chuck's behavioral
  contract — model, tool surface, and system directive — into one mode
  definition (`inc/Mode/CluckinChuckMode.php`) instead of being scattered
  across `agent_config`. Composes with the `chat` execution mode.
- Frontend chat now opts into `agent_modes = ['cluckin-chuck', 'chat']` via
  `client_context`, so DM resolves the correct model + system prompt + tools.
- Activation/upgrade hook seeds `datamachine_settings.mode_models['cluckin-chuck']`
  to `{ provider: openai, model: gpt-5.4-mini }`. Idempotent — won't overwrite
  a value set by the site owner.

### Fixed
- Wing chat tools (list/get/submit/approve/etc.) used the wrong registry
  field name (`'contexts'` instead of `'modes'`), which Data Machine's tool
  resolver silently ignores. Result: none of the wing tools were actually
  reaching the frontend chat — only the global `local_search` tool was
  available. All thirteen wing tools now correctly declare
  `'modes' => ['cluckin-chuck', 'chat']`.

### Changed
- The system prompt / business logic that previously lived in
  `agent_config.system_prompt` is migrating to the mode directive. The mode
  filter (`datamachine_agent_mode_cluckin-chuck`) is the new home for "what
  Chuck knows and does." This keeps the DB row minimal and the source of
  truth in version control.

## [0.1.2] - 2026-03-30

### Added
- auto-fill reviewer identity from logged-in user account

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
