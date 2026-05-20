<?php
/**
 * Cluckin' Chuck Agent Mode
 *
 * Registers the `cluckin-chuck` execution mode with Data Machine's
 * AgentModeRegistry and contributes the behavioral overlay (system prompt,
 * model preference, tool surface) that defines Chuck as the public-facing
 * wing assistant.
 *
 * A Data Machine "mode" bundles three things:
 *   - model         (provider + model name, via mode_models setting)
 *   - tool policy   (which tools are available — enforced by 'modes' key
 *                    on each tool plus the datamachine_resolved_tools filter)
 *   - directive     (system prompt overlay, via the
 *                    datamachine_agent_mode_{slug} filter)
 *
 * The `cluckin-chuck` mode is the centralized definition of "what does
 * Chuck know and do when a visitor talks to him on the site." It composes
 * with the `chat` execution-surface mode — the frontend chat passes
 * agent_modes = ['cluckin-chuck', 'chat'].
 *
 * Resolution flow:
 *   1. Frontend chat injects client_context.agent_modes = ['cluckin-chuck','chat']
 *   2. AgentsChatHandler reads modes, calls PluginSettings::resolveModelForAgentModes()
 *   3. mode_models['cluckin-chuck'] = gpt-5.4-mini wins over global default
 *   4. AgentModeDirective::get_outputs() runs the
 *      datamachine_agent_mode_cluckin-chuck filter to inject the wing-biz prompt
 *   5. ToolPolicyResolver gathers tools whose 'modes' includes 'cluckin-chuck'
 *
 * @package CluckinChuck\AgentKit\Mode
 * @since 0.2.0
 */

namespace CluckinChuck\AgentKit\Mode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CluckinChuckMode {

	/**
	 * Mode slug used throughout the system.
	 */
	public const SLUG = 'cluckin-chuck';

	/**
	 * Default model for this mode when the site has nothing configured.
	 */
	public const DEFAULT_PROVIDER = 'openai';
	public const DEFAULT_MODEL    = 'gpt-5.4-mini';

	/**
	 * Boot the mode: register with DM's registry and hook the directive filter.
	 */
	public static function boot(): void {
		add_action( 'init', array( self::class, 'register_mode' ), 5 );

		add_filter(
			'datamachine_agent_mode_' . self::SLUG,
			array( self::class, 'directive' ),
			10,
			2
		);
	}

	/**
	 * Register the mode with Data Machine's AgentModeRegistry.
	 *
	 * Runs at init priority 5 so it lands before Data Machine's own core
	 * mode registrations (priority 0 → default 10), keeping the surface
	 * stable while still being discoverable in admin settings UI.
	 */
	public static function register_mode(): void {
		if ( ! class_exists( '\DataMachine\Engine\AI\AgentModeRegistry' ) ) {
			return;
		}

		\DataMachine\Engine\AI\AgentModeRegistry::register(
			self::SLUG,
			100, // Sort order: after core modes (chat=10, pipeline=20, system=30).
			array(
				'label'       => __( "Cluckin' Chuck", 'cluckin-chuck-agent-kit' ),
				'description' => __( 'Public wing-review assistant. Composes with the chat execution surface to power the frontend chat widget.', 'cluckin-chuck-agent-kit' ),
			)
		);
	}

	/**
	 * Contribute the cluckin-chuck mode's system directive.
	 *
	 * Returns the behavioral overlay text that gets injected into the
	 * system prompt whenever this mode is active. Combined with the `chat`
	 * mode directive (which provides generic chat-session guidance), this
	 * is what makes Chuck Chuck.
	 *
	 * Note: identity (name, voice) lives in SOUL.md memory file, loaded by
	 * the memory files directive (priority 20). This mode contributes the
	 * task-specific business logic, not the personality.
	 *
	 * @param string $content Current directive content (default empty).
	 * @param array  $payload Full request payload (agent_id, user_id, etc.).
	 * @return string
	 */
	public static function directive( string $content, array $payload ): string {
		return <<<'MD'
# Cluckin' Chuck Mode — Public Wing Assistant

You are running as the public-facing wing-review assistant on cluckinchuck.saraichinwag.com. Your audience is site visitors — wing enthusiasts looking for spots, submitting reviews, or asking about ratings. Be enthusiastic, casual, and knowledgeable about wing styles (buffalo, Korean BBQ, lemon pepper, dry rub, etc.).

## Available Tools

You have tools for the full wing lifecycle:

- **Discovery:** list_wing_locations, get_wing_location, list_wing_reviews
- **Geocoding:** geocode_address (run BEFORE submitting a new location to convert street address to lat/lng)
- **Submissions:** submit_wing_review, submit_wing_location
- **Moderation (admin only):** approve_wing_review, reject_wing_review, approve_wing_location, reject_wing_location, recalculate_wing_stats, list_pending_submissions, update_wing_location

## Review Submission Flow

When a user wants to submit a review:

1. Ask which restaurant. Use `list_wing_locations` to search.
2. If the restaurant does not exist, offer to submit it as a new location.
   - Ask for street address.
   - Call `geocode_address` to get coordinates.
   - Then call `submit_wing_location` with the geocoded result.
3. Extract structured data from the user's casual description:
   - **rating** (1–5, required)
   - **sauce_rating** (1–5, optional)
   - **crispiness_rating** (1–5, optional)
   - **wing_count** (integer, optional)
   - **total_price** (decimal, optional)
   - **review_text** (the user's words)
4. Interpret conversational cues for ratings:
   - "amazing / incredible / best ever" → 5
   - "pretty good / solid / really good" → 4
   - "decent / okay / fine" → 3
   - "meh / mid / not great" → 2
   - "terrible / awful / never again" → 1
5. **Always confirm extracted data with the user before calling submit_wing_review.**

## Identity Handling

- **Logged-in users:** NEVER ask for name or email. Omit `reviewer_name` and `reviewer_email` — the system auto-fills from their WordPress account. Just focus on the review content.
- **Anonymous users:** Ask for name and email naturally in the conversation flow. These are required for submission.

You can detect login status from `client_context.user_authenticated`.

## Location Lookup Behavior

- Show ratings, review counts, and price per wing when listing locations.
- Offer to show detailed reviews if the user wants more depth.
- If a user asks about a specific spot by name, use `list_wing_locations` with the `search` parameter first to find the `post_id`, then `get_wing_location` for full details.

## Moderation Behavior (Admin Only)

When `client_context.user_role` includes administrator/editor:

- Use `list_pending_submissions` when asked "what needs approval?" or "show me pending reviews."
- Approve with `approve_wing_review` / `approve_wing_location` only when the user explicitly says to approve a specific item.
- Reject with `reject_wing_review` / `reject_wing_location` only when explicitly asked.
- After approving reviews, the system recalculates stats automatically.

## Execution Rules

- Act decisively — execute tools directly for routine lookups. Don't ask permission to search.
- Only confirm task completion after a successful tool result. Never claim success on error.
- If a tool returns validation errors, fix the inputs and retry once.
- Keep responses concise and wing-focused. No meta-commentary.
MD;
	}

	/**
	 * Seed the per-mode model setting in datamachine_settings.
	 *
	 * Runs on plugin activation and on version-upgrade. Idempotent: only
	 * writes if mode_models['cluckin-chuck'] is not already configured, so
	 * a site owner can later override via the DM settings UI without this
	 * code stomping on their choice.
	 *
	 * Cascade for the model used when this mode is active:
	 *   1. agent_config.mode_models['cluckin-chuck']  (per-agent override)
	 *   2. datamachine_settings.mode_models['cluckin-chuck']  ← seeded here
	 *   3. agent_config.default_model
	 *   4. datamachine_settings.default_model  (global default, e.g. nano)
	 *
	 * @return void
	 */
	/**
	 * Migrate the legacy cluckinchuck agent_config to the mode-based shape.
	 *
	 * Pre-0.2.0, the `cluckinchuck` agent row carried the full wing-business
	 * system_prompt + a tool_policy allowlist in its `agent_config` JSON.
	 * The cluckin-chuck mode now owns both of those (directive filter + the
	 * 'modes' key on each tool), so this migration strips them from the
	 * agent row.
	 *
	 * Idempotent: only runs while those fields still exist. Safe to call
	 * on every version upgrade.
	 *
	 * Note: we intentionally do NOT delete the agent. The agent_id is still
	 * the addressable identity for the chat session (transcripts, access
	 * grants, etc.). Only its behavioral payload moves to the mode.
	 *
	 * @return void
	 */
	public static function migrate_agent_config(): void {
		if ( ! class_exists( '\DataMachine\Core\Database\Agents\Agents' ) ) {
			return;
		}

		$repo  = new \DataMachine\Core\Database\Agents\Agents();
		$agent = $repo->get_by_slug( 'cluckinchuck' );

		if ( ! $agent || ! is_array( $agent['agent_config'] ?? null ) ) {
			return;
		}

		$config   = $agent['agent_config'];
		$modified = false;

		if ( isset( $config['system_prompt'] ) ) {
			unset( $config['system_prompt'] );
			$modified = true;
		}

		if ( isset( $config['tool_policy'] ) ) {
			unset( $config['tool_policy'] );
			$modified = true;
		}

		if ( ! $modified ) {
			return;
		}

		$agent_id = (int) ( $agent['agent_id'] ?? 0 );
		if ( $agent_id <= 0 ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'datamachine_agents';
		$wpdb->update(
			$table,
			array( 'agent_config' => wp_json_encode( $config ) ),
			array( 'agent_id' => $agent_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public static function seed_default_model(): void {
		$settings = get_option( 'datamachine_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$mode_models = isset( $settings['mode_models'] ) && is_array( $settings['mode_models'] )
			? $settings['mode_models']
			: array();

		// Don't overwrite an existing user-configured value.
		if ( ! empty( $mode_models[ self::SLUG ]['provider'] ) && ! empty( $mode_models[ self::SLUG ]['model'] ) ) {
			return;
		}

		$mode_models[ self::SLUG ] = array(
			'provider' => self::DEFAULT_PROVIDER,
			'model'    => self::DEFAULT_MODEL,
		);

		$settings['mode_models'] = $mode_models;

		update_option( 'datamachine_settings', $settings );

		// Bust DM's static cache so the new value is visible immediately.
		if ( class_exists( '\DataMachine\Core\PluginSettings' ) ) {
			\DataMachine\Core\PluginSettings::clearCache();
		}
	}
}
