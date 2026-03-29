<?php
/**
 * Submission chat tools — wraps submit abilities for the Data Machine chat system.
 *
 * Tools registered:
 *   - submit_wing_review (public)
 *   - submit_wing_location (public)
 *
 * These are the primary tools for the conversational review flow:
 * user describes their wing experience → agent extracts structured data → submits via these tools.
 *
 * @package CluckinChuck\AgentKit\Tools
 */

namespace CluckinChuck\AgentKit\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SubmitTools {

	public function __construct() {
		add_filter( 'datamachine_tools', array( $this, 'register_tools' ) );
	}

	public function register_tools( array $tools ): array {
		$tools['submit_wing_review'] = array(
			'_callable' => array( $this, 'get_submit_review_def' ),
			'contexts'  => array( 'chat' ),
		);

		$tools['submit_wing_location'] = array(
			'_callable' => array( $this, 'get_submit_location_def' ),
			'contexts'  => array( 'chat' ),
		);

		return $tools;
	}

	// ------------------------------------------------------------------
	// Tool definitions
	// ------------------------------------------------------------------

	public function get_submit_review_def(): array {
		return array(
			'class'        => self::class,
			'method'       => 'handle_tool_call',
			'description'  => 'Submit a wing review for an existing location. Creates a pending review awaiting moderation. '
				. 'Use this when a user wants to review a place that already exists in the system. '
				. 'Extract these details from the conversation: their name, email, overall rating (1-5), '
				. 'review text, and optionally sauce rating, crispiness rating, number of wings, and total price. '
				. 'Always confirm the extracted data with the user before submitting. '
				. 'You must first use list_wing_locations to find the location\'s post_id.',
			'ability'      => 'cluckin-chuck/submit-review',
			'access_level' => 'public',
			'parameters'   => array(
				'post_id' => array(
					'type'        => 'integer',
					'required'    => true,
					'description' => 'The wing_location post ID to submit a review for.',
				),
				'reviewer_name' => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'Reviewer\'s display name.',
				),
				'reviewer_email' => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'Reviewer\'s email address.',
				),
				'rating' => array(
					'type'        => 'integer',
					'required'    => true,
					'description' => 'Overall wing rating from 1-5. Interpret conversational cues: "amazing" = 5, "pretty good" = 4, "decent" = 3, "meh" = 2, "terrible" = 1.',
				),
				'review_text' => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'The review text. Can be the user\'s own words, cleaned up for readability.',
				),
				'sauce_rating' => array(
					'type'        => 'integer',
					'required'    => false,
					'description' => 'Sauce quality rating 1-5. Extract from mentions like "great sauce", "sauce was fire", etc.',
				),
				'crispiness_rating' => array(
					'type'        => 'integer',
					'required'    => false,
					'description' => 'Crispiness rating 1-5. Extract from mentions like "super crispy", "soggy", etc.',
				),
				'wing_count' => array(
					'type'        => 'integer',
					'required'    => false,
					'description' => 'Number of wings ordered. Must provide total_price if wing_count is provided.',
				),
				'total_price' => array(
					'type'        => 'number',
					'required'    => false,
					'description' => 'Total price paid in dollars. Must provide wing_count if total_price is provided.',
				),
			),
		);
	}

	public function get_submit_location_def(): array {
		return array(
			'class'        => self::class,
			'method'       => 'handle_tool_call',
			'description'  => 'Submit a brand new wing location with an initial review. Creates a pending location awaiting admin approval. '
				. 'Use this when a user mentions a restaurant that does NOT exist in the system yet. '
				. 'First use list_wing_locations to confirm it doesn\'t already exist, then use geocode_address to get coordinates. '
				. 'Extract location name, address, and review details from the conversation. '
				. 'Always confirm the extracted data with the user before submitting.',
			'ability'      => 'cluckin-chuck/submit-location',
			'access_level' => 'public',
			'parameters'   => array(
				'location_name' => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'Name of the wing restaurant.',
				),
				'address' => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'Street address of the restaurant.',
				),
				'latitude' => array(
					'type'        => 'number',
					'required'    => true,
					'description' => 'Latitude from geocode_address tool.',
				),
				'longitude' => array(
					'type'        => 'number',
					'required'    => true,
					'description' => 'Longitude from geocode_address tool.',
				),
				'reviewer_name' => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'Reviewer\'s display name.',
				),
				'reviewer_email' => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'Reviewer\'s email address.',
				),
				'rating' => array(
					'type'        => 'integer',
					'required'    => true,
					'description' => 'Overall wing rating 1-5.',
				),
				'review_text' => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'The review text.',
				),
				'website' => array(
					'type'        => 'string',
					'required'    => false,
					'description' => 'Restaurant website URL.',
				),
				'instagram' => array(
					'type'        => 'string',
					'required'    => false,
					'description' => 'Restaurant Instagram URL.',
				),
				'sauce_rating' => array(
					'type'        => 'integer',
					'required'    => false,
					'description' => 'Sauce quality rating 1-5.',
				),
				'crispiness_rating' => array(
					'type'        => 'integer',
					'required'    => false,
					'description' => 'Crispiness rating 1-5.',
				),
				'wing_count' => array(
					'type'        => 'integer',
					'required'    => false,
					'description' => 'Number of wings ordered.',
				),
				'total_price' => array(
					'type'        => 'number',
					'required'    => false,
					'description' => 'Total price paid in dollars.',
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

		// Parameters map directly — ability input_schema matches tool parameters.
		return $parameters;
	}
}
