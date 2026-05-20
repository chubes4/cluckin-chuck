<?php
/**
 * Plugin Name: Cluckin' Chuck Agent Kit
 * Plugin URI: https://cluckinchuck.saraichinwag.com
 * Description: Chat tools and agent configuration for the Cluckin' Chuck AI assistant. Bridges abilities to Data Machine's chat system.
 * Version: 0.2.1
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cluckin-chuck-agent-kit
 *
 * @package CluckinChuck\AgentKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CLUCKIN_CHUCK_AGENT_KIT_VERSION', '0.2.1' );
define( 'CLUCKIN_CHUCK_AGENT_KIT_PATH', plugin_dir_path( __FILE__ ) );

// Load tool classes.
require_once CLUCKIN_CHUCK_AGENT_KIT_PATH . 'inc/Tools/LocationTools.php';
require_once CLUCKIN_CHUCK_AGENT_KIT_PATH . 'inc/Tools/ReviewTools.php';
require_once CLUCKIN_CHUCK_AGENT_KIT_PATH . 'inc/Tools/SubmitTools.php';

// Load mode definition.
require_once CLUCKIN_CHUCK_AGENT_KIT_PATH . 'inc/Mode/CluckinChuckMode.php';

// Boot tool registration.
add_action( 'plugins_loaded', function () {
	new CluckinChuck\AgentKit\Tools\LocationTools();
	new CluckinChuck\AgentKit\Tools\ReviewTools();
	new CluckinChuck\AgentKit\Tools\SubmitTools();
} );

// Boot the cluckin-chuck Data Machine mode (registers with AgentModeRegistry +
// hooks the directive filter for the wing-biz system prompt overlay).
\CluckinChuck\AgentKit\Mode\CluckinChuckMode::boot();

// Seed mode_models['cluckin-chuck'] = gpt-5.4-mini on activation. Idempotent
// — only writes when the site has no existing override.
register_activation_hook( __FILE__, array( '\CluckinChuck\AgentKit\Mode\CluckinChuckMode', 'seed_default_model' ) );

// Also seed on version upgrade. Compare the stored version to the live
// constant — if they differ (or no version stored yet), run the seeder
// + agent-config migration and store the new version. This makes the
// 0.1.x → 0.2.0 deploy self-installing on every environment.
add_action( 'init', function () {
	$stored = get_option( 'cluckin_chuck_agent_kit_version', '' );
	if ( CLUCKIN_CHUCK_AGENT_KIT_VERSION === $stored ) {
		return;
	}
	\CluckinChuck\AgentKit\Mode\CluckinChuckMode::seed_default_model();
	\CluckinChuck\AgentKit\Mode\CluckinChuckMode::migrate_agent_config();
	update_option( 'cluckin_chuck_agent_kit_version', CLUCKIN_CHUCK_AGENT_KIT_VERSION );
}, 20 );

// ------------------------------------------------------------------
// Register 'agents' ability category for WP 6.9.x (WP 7.0+ has it natively).
// Must fire on wp_abilities_api_categories_init, which runs before wp_abilities_api_init.
// ------------------------------------------------------------------
$register_agents_category = function () {
	if ( ! function_exists( 'wp_register_ability_category' ) || wp_has_ability_category( 'agents' ) ) {
		return;
	}
	wp_register_ability_category( 'agents', array(
		'label'       => 'Agents',
		'description' => 'Agent management abilities.',
	) );
};

if ( doing_action( 'wp_abilities_api_categories_init' ) ) {
	$register_agents_category();
} elseif ( did_action( 'wp_abilities_api_categories_init' ) ) {
	// Too late — categories can't be registered after the action.
	// Fall back handled below by omitting category from abilities.
} else {
	add_action( 'wp_abilities_api_categories_init', $register_agents_category );
}

// ------------------------------------------------------------------
// Agents API shims for frontend-agent-chat v0.8.0.
//
// FAC v0.8.0 expects `agents/list-accessible-agents`, `agents/can-access-agent`,
// and `agents/chat` abilities from the WP Agents API (WP 7.0+). On WP 6.9.x
// these don't exist, so we register lightweight shims that delegate to
// Data Machine's internal agent system.
// ------------------------------------------------------------------
$register_agents_api_shims = function () {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	// Only register shims if the real Agents API abilities don't exist.
	if ( wp_get_ability( 'agents/list-accessible-agents' ) ) {
		return;
	}

	wp_register_ability(
		'agents/list-accessible-agents',
		array(
			'label'            => 'List Accessible Agents',
			'description'      => 'Shim: lists agents the current user can access.',
			'category'         => 'agents',
			'input_schema'     => array(
				'type'       => 'object',
				'properties' => array(
					'minimum_role' => array( 'type' => 'string', 'default' => 'viewer' ),
				),
			),
			'output_schema'    => array(
				'type'       => 'object',
				'properties' => array(
					'agents' => array( 'type' => 'array' ),
				),
			),
			'execute_callback' => function ( array $input ) {
				global $wpdb;
				$agents = $wpdb->get_results(
					"SELECT * FROM {$wpdb->prefix}datamachine_agents ORDER BY agent_slug ASC",
					ARRAY_A
				);

				$result = array();
				foreach ( $agents as $row ) {
					$result[] = array(
						'slug'        => $row['agent_slug'],
						'agent_slug'  => $row['agent_slug'],
						'label'       => $row['agent_slug'],
						'agent_name'  => $row['agent_slug'],
						'description' => '',
						'meta'        => array(),
					);
				}

				return array( 'agents' => $result );
			},
			'permission_callback' => '__return_true',
			'meta'                => array( 'show_in_rest' => false ),
		)
	);

	wp_register_ability(
		'agents/can-access-agent',
		array(
			'label'            => 'Can Access Agent',
			'description'      => 'Shim: checks if the current user can access an agent.',
			'category'         => 'agents',
			'input_schema'     => array(
				'type'       => 'object',
				'required'   => array( 'agent' ),
				'properties' => array(
					'agent'        => array( 'type' => 'string' ),
					'minimum_role' => array( 'type' => 'string', 'default' => 'viewer' ),
				),
			),
			'output_schema'    => array(
				'type'       => 'object',
				'properties' => array(
					'allowed' => array( 'type' => 'boolean' ),
				),
			),
			'execute_callback' => function ( array $input ) {
				// On WP 6.9 without the full Agents API, allow all
				// authenticated users and the configured agent slug.
				$allowed = is_user_logged_in();
				return array( 'allowed' => $allowed );
			},
			'permission_callback' => '__return_true',
			'meta'                => array( 'show_in_rest' => false ),
		)
	);

	wp_register_ability(
		'agents/chat',
		array(
			'label'            => 'Chat with Agent',
			'description'      => 'Shim: delegates to datamachine/send-message.',
			'category'         => 'agents',
			'input_schema'     => array(
				'type'       => 'object',
				'required'   => array( 'message' ),
				'properties' => array(
					'agent'          => array( 'type' => 'string' ),
					'message'        => array( 'type' => 'string' ),
					'session_id'     => array( 'type' => 'string' ),
					'attachments'    => array( 'type' => 'array' ),
					'client_context' => array( 'type' => 'object' ),
				),
			),
			'output_schema'    => array( 'type' => 'object' ),
			'execute_callback' => function ( array $input ) {
				$send = wp_get_ability( 'datamachine/send-message' );
				if ( ! $send ) {
					return new \WP_Error( 'missing_ability', 'datamachine/send-message is not available.' );
				}

				// Resolve agent slug to agent_id.
				$agent_slug = sanitize_title( $input['agent'] ?? '' );
				$agent_id   = 0;
				if ( '' !== $agent_slug ) {
					global $wpdb;
					$agent_id = (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT id FROM {$wpdb->prefix}datamachine_agents WHERE agent_slug = %s LIMIT 1",
							$agent_slug
						)
					);
				}

				return $send->execute( array(
					'message'        => $input['message'] ?? '',
					'session_id'     => $input['session_id'] ?? null,
					'agent_id'       => $agent_id,
					'attachments'    => $input['attachments'] ?? array(),
					'client_context' => $input['client_context'] ?? array(),
				) );
			},
			'permission_callback' => function () {
				return is_user_logged_in() || current_user_can( 'read' );
			},
			'meta' => array( 'show_in_rest' => false ),
		)
	);
};

// Use the same pattern as Data Machine: if the init action already fired,
// register immediately; otherwise hook into it.
if ( doing_action( 'wp_abilities_api_init' ) ) {
	$register_agents_api_shims();
} elseif ( did_action( 'wp_abilities_api_init' ) ) {
	$register_agents_api_shims();
} else {
	add_action( 'wp_abilities_api_init', $register_agents_api_shims );
}

// Configure the frontend chat widget.
add_filter( 'frontend_agent_chat_config', function ( $config ) {
	$config['agent_slug']  = 'cluckinchuck';
	$config['visibility']  = 'team';
	$config['description'] = 'Your AI wing advisor. Ask about locations, submit reviews, or find the best wings near you.';
	$config['enabled']     = true;

	return $config;
} );

// Inject runtime context into chat input. Two responsibilities:
//
//   1. agent_modes — opt the frontend chat into the cluckin-chuck mode.
//      DM reads client_context.agent_modes in AgentsChatHandler::resolveModes
//      and uses them to (a) pick the per-mode model via mode_models[],
//      (b) inject the cluckin-chuck system directive, and (c) gather
//      tools whose 'modes' includes 'cluckin-chuck'. The 'chat' mode is
//      kept as the execution surface — without it, ToolPolicyResolver
//      treats the call as pipeline mode.
//
//   2. user identity — tell the agent whether the current user is logged
//      in so it can skip asking for name/email during review submission.
add_filter( 'frontend_agent_chat_chat_input', function ( $chat_input ) {
	if ( ! is_array( $chat_input ) ) {
		return $chat_input;
	}

	if ( ! isset( $chat_input['client_context'] ) || ! is_array( $chat_input['client_context'] ) ) {
		$chat_input['client_context'] = array();
	}

	$chat_input['client_context']['agent_modes'] = array(
		\CluckinChuck\AgentKit\Mode\CluckinChuckMode::SLUG,
		'chat',
	);

	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();

		$chat_input['client_context']['user_authenticated'] = true;
		$chat_input['client_context']['user_display_name']  = $user->display_name;
		$chat_input['client_context']['user_role']          = implode( ', ', $user->roles );
	} else {
		$chat_input['client_context']['user_authenticated'] = false;
	}

	return $chat_input;
} );
