# Changelog

## [0.3.1] - 2026-05-21

### Added
- set fab_label to "Chat with Chuck" and fab_icon to 🍗 via frontend_agent_chat_config filter (requires frontend-agent-chat >= 0.8.1)

### Removed
- obsolete visibility=team config key (frontend-agent-chat ignores it; access is governed by wp_datamachine_agent_access grants)

## [0.3.0] - 2026-05-20

### Added
- feat(agent-kit): introduce cluckin-chuck mode and fix tool registration

### Fixed
- fix(agent-kit): convert wing tool params to proper JSON Schema
- fix(agent-kit): restrict cluckin-chuck mode tool surface to wing allowlist
- register agents category on wp_abilities_api_categories_init
- register agents ability category before shim abilities
- register Agents API shims for frontend-agent-chat v0.8.0
- inject user auth context into frontend chat AI prompt

## [0.2.2] - 2026-05-04

### Fixed
- Wing tool `parameters` were declared in a bare keyed-map form like
  `array( 'post_id' => array( 'type' => 'integer', 'required' => true, ... ) )`.
  Data Machine's `RequestBuilder::normalizeToolSchema()` expects JSON Schema
  (`{ type: 'object', properties: {...}, required: [...] }`) and passes
  anything else through unchanged. Older/looser OpenAI models tolerated the
  malformed schema, but stricter models like `gpt-5.4-mini` reject it with
  a 400 "invalid schema for function" error — which manifested as the
  frontend chat failing on first tool-call attempt.
- Added `SchemaHelper::to_json_schema()` that converts the bare-map form
  to a proper JSON Schema object, stripping per-parameter `required` flags
  into the top-level `required` array. Each Tools class now wraps its def
  methods via `schema_normalized()` so the conversion runs once at tool
  resolution time. Per-def method bodies stay readable; the wire format
  going to the LLM provider is now correct.

## [0.2.1] - 2026-05-04

### Fixed
- The cluckin-chuck mode now restricts the resolved tool surface to the
  wing-assistant allowlist via the `datamachine_resolved_tools` filter.
  Without this, including `'chat'` as the execution surface mode let every
  admin chat tool (GitHub, Reddit, social publishers, pipeline mgmt, etc.)
  through to the public frontend chat — 128 tools instead of the intended
  13. The allowlist lives in `CluckinChuckMode::allowed_tools()` and is
  the canonical source of truth for "what tools does Chuck have access
  to in the public chat surface."

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
