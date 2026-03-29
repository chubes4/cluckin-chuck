<?php
/**
 * Review chat tools — wraps review abilities for the Data Machine chat system.
 *
 * Tools registered:
 *   - list_wing_reviews (public)
 *   - approve_wing_review (admin)
 *   - recalculate_wing_stats (admin)
 *   - list_pending_submissions (admin)
 *
 * @package CluckinChuck\AgentKit\Tools
 */

namespace CluckinChuck\AgentKit\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewTools {

	public function __construct() {
		add_filter( 'datamachine_tools', array( $this, 'register_tools' ) );
	}

	public function register_tools( array $tools ): array {
		$tools['list_wing_reviews'] = array(
			'_callable' => array( $this, 'get_list_reviews_def' ),
			'contexts'  => array( 'chat' ),
		);

		$tools['approve_wing_review'] = array(
			'_callable' => array( $this, 'get_approve_def' ),
			'contexts'  => array( 'chat' ),
		);

		$tools['recalculate_wing_stats'] = array(
			'_callable' => array( $this, 'get_recalculate_def' ),
			'contexts'  => array( 'chat' ),
		);

		$tools['list_pending_submissions'] = array(
			'_callable' => array( $this, 'get_pending_def' ),
			'contexts'  => array( 'chat' ),
		);

		return $tools;
	}

	// ------------------------------------------------------------------
	// Tool definitions
	// ------------------------------------------------------------------

	public function get_list_reviews_def(): array {
		return array(
			'class'        => self::class,
			'method'       => 'handle_tool_call',
			'description'  => 'Get all reviews for a wing location. Returns reviewer names, ratings (overall, sauce, crispiness), price per wing, review text, and timestamps. Use when someone asks about reviews at a specific spot or wants to see what people said.',
			'ability'      => 'cluckin-chuck/list-reviews',
			'access_level' => 'public',
			'parameters'   => array(
				'post_id' => array(
					'type'        => 'integer',
					'required'    => true,
					'description' => 'The wing location post ID to get reviews for.',
				),
			),
		);
	}

	public function get_approve_def(): array {
		return array(
			'class'        => self::class,
			'method'       => 'handle_tool_call',
			'description'  => 'Approve a pending wing review. Converts the pending comment into a published review block, recalculates the location\'s aggregate stats, and removes the original comment. Use when an admin says to approve a specific review.',
			'ability'      => 'cluckin-chuck/approve-review',
			'access_level' => 'admin',
			'parameters'   => array(
				'comment_id' => array(
					'type'        => 'integer',
					'required'    => true,
					'description' => 'The pending review comment ID to approve.',
				),
			),
		);
	}

	public function get_recalculate_def(): array {
		return array(
			'class'        => self::class,
			'method'       => 'handle_tool_call',
			'description'  => 'Recalculate aggregate stats (average rating, review count, price per wing) for a wing location from its review blocks. Use when an admin suspects stats are out of sync or after manual edits.',
			'ability'      => 'cluckin-chuck/recalculate-stats',
			'access_level' => 'admin',
			'parameters'   => array(
				'post_id' => array(
					'type'        => 'integer',
					'required'    => true,
					'description' => 'The wing location post ID to recalculate.',
				),
			),
		);
	}

	public function get_pending_def(): array {
		return array(
			'class'        => self::class,
			'method'       => 'handle_tool_call',
			'description'  => 'List pending submissions awaiting moderation — both pending wing locations and pending review comments. Use when an admin asks "what needs approval?" or "show me pending reviews".',
			'ability'      => 'cluckin-chuck/list-pending',
			'access_level' => 'admin',
			'parameters'   => array(
				'type' => array(
					'type'        => 'string',
					'required'    => false,
					'description' => 'Filter by type: "all", "locations", or "reviews". Default: "all".',
					'enum'        => array( 'all', 'locations', 'reviews' ),
				),
			),
		);
	}

	// ------------------------------------------------------------------
	// Tool handler
	// ------------------------------------------------------------------

	public function handle_tool_call( array $parameters, array $tool_def = array() ): array {
		$ability_slug = $tool_def['ability'] ?? '';

		if ( empty( $ability_slug ) || ! function_exists( 'wp_get_ability' ) ) {
			return array(
				'success'   => false,
				'error'     => 'Ability not available.',
				'tool_name' => $parameters['tool_name'] ?? 'unknown',
			);
		}

		$ability = wp_get_ability( $ability_slug );

		if ( ! $ability ) {
			return array(
				'success'   => false,
				'error'     => sprintf( 'Ability %s is not registered.', $ability_slug ),
				'tool_name' => $parameters['tool_name'] ?? 'unknown',
			);
		}

		$input = $this->map_parameters( $ability_slug, $parameters );

		$result = $ability->execute( $input );

		if ( is_wp_error( $result ) ) {
			return array(
				'success'   => false,
				'error'     => $result->get_error_message(),
				'tool_name' => $parameters['tool_name'] ?? 'unknown',
			);
		}

		return array(
			'success'   => true,
			'data'      => $result,
			'tool_name' => $parameters['tool_name'] ?? 'unknown',
		);
	}

	private function map_parameters( string $ability_slug, array $parameters ): array {
		unset( $parameters['tool_name'], $parameters['tool_call_id'] );

		switch ( $ability_slug ) {
			case 'cluckin-chuck/list-reviews':
				return array( 'post_id' => intval( $parameters['post_id'] ?? 0 ) );

			case 'cluckin-chuck/approve-review':
				return array( 'comment_id' => intval( $parameters['comment_id'] ?? 0 ) );

			case 'cluckin-chuck/recalculate-stats':
				return array( 'post_id' => intval( $parameters['post_id'] ?? 0 ) );

			case 'cluckin-chuck/list-pending':
				return array( 'type' => $parameters['type'] ?? 'all' );

			default:
				return $parameters;
		}
	}
}
