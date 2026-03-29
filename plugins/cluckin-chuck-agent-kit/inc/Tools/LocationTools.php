<?php
/**
 * Location chat tools — wraps location abilities for the Data Machine chat system.
 *
 * Tools registered:
 *   - list_locations (public)
 *   - get_location (public)
 *   - update_location (admin)
 *   - geocode_address (public)
 *
 * @package CluckinChuck\AgentKit\Tools
 */

namespace CluckinChuck\AgentKit\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LocationTools {

	public function __construct() {
		add_filter( 'datamachine_tools', array( $this, 'register_tools' ) );
	}

	public function register_tools( array $tools ): array {
		$tools['list_wing_locations'] = array(
			'_callable' => array( $this, 'get_list_locations_def' ),
			'contexts'  => array( 'chat' ),
		);

		$tools['get_wing_location'] = array(
			'_callable' => array( $this, 'get_get_location_def' ),
			'contexts'  => array( 'chat' ),
		);

		$tools['update_wing_location'] = array(
			'_callable' => array( $this, 'get_update_location_def' ),
			'contexts'  => array( 'chat' ),
		);

		$tools['geocode_address'] = array(
			'_callable' => array( $this, 'get_geocode_def' ),
			'contexts'  => array( 'chat' ),
		);

		return $tools;
	}

	// ------------------------------------------------------------------
	// Tool definitions
	// ------------------------------------------------------------------

	public function get_list_locations_def(): array {
		return array(
			'class'        => self::class,
			'method'       => 'handle_tool_call',
			'description'  => 'Search for chicken wing locations. Use when a user asks about wing spots, wants to find restaurants, or asks what locations are available. Returns names, addresses, ratings, review counts, and price per wing.',
			'ability'      => 'cluckin-chuck/list-locations',
			'access_level' => 'public',
			'parameters'   => array(
				'search' => array(
					'type'        => 'string',
					'required'    => false,
					'description' => 'Search by restaurant name or keyword.',
				),
				'status' => array(
					'type'        => 'string',
					'required'    => false,
					'description' => 'Post status filter. Default: publish.',
					'enum'        => array( 'publish', 'pending', 'draft' ),
				),
				'per_page' => array(
					'type'        => 'integer',
					'required'    => false,
					'description' => 'Number of results to return. Default: 20.',
				),
			),
		);
	}

	public function get_get_location_def(): array {
		return array(
			'class'        => self::class,
			'method'       => 'handle_tool_call',
			'description'  => 'Get detailed information about a specific wing location including address, website, Instagram, ratings, review count, and price per wing stats. Use when the user mentions a specific restaurant by name or ID.',
			'ability'      => 'cluckin-chuck/get-location',
			'access_level' => 'public',
			'parameters'   => array(
				'post_id' => array(
					'type'        => 'integer',
					'required'    => true,
					'description' => 'The wing location post ID.',
				),
			),
		);
	}

	public function get_update_location_def(): array {
		return array(
			'class'        => self::class,
			'method'       => 'handle_tool_call',
			'description'  => 'Update a wing location\'s editable details: address, website URL, Instagram URL, or coordinates. Only use when an admin explicitly asks to change location information.',
			'ability'      => 'cluckin-chuck/update-location',
			'access_level' => 'admin',
			'parameters'   => array(
				'post_id' => array(
					'type'        => 'integer',
					'required'    => true,
					'description' => 'The wing location post ID to update.',
				),
				'meta' => array(
					'type'        => 'object',
					'required'    => true,
					'description' => 'Fields to update. Keys: wing_address, wing_website, wing_instagram, wing_latitude, wing_longitude. Only include fields being changed.',
				),
			),
		);
	}

	public function get_geocode_def(): array {
		return array(
			'class'        => self::class,
			'method'       => 'handle_tool_call',
			'description'  => 'Convert a street address to latitude/longitude coordinates. Use this before submitting a new wing location to get the coordinates from the address the user provides.',
			'ability'      => 'cluckin-chuck/geocode-address',
			'access_level' => 'public',
			'parameters'   => array(
				'address' => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'The street address to geocode (e.g. "123 King St, Charleston SC").',
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

		// Map tool parameters to ability input.
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

	/**
	 * Map tool parameters to ability input format.
	 */
	private function map_parameters( string $ability_slug, array $parameters ): array {
		// Remove tool infrastructure keys.
		unset( $parameters['tool_name'], $parameters['tool_call_id'] );

		switch ( $ability_slug ) {
			case 'cluckin-chuck/list-locations':
				return array(
					'post_status' => $parameters['status'] ?? 'publish',
					'search'      => $parameters['search'] ?? '',
					'per_page'    => $parameters['per_page'] ?? 20,
				);

			case 'cluckin-chuck/get-location':
				return array( 'post_id' => intval( $parameters['post_id'] ?? 0 ) );

			case 'cluckin-chuck/update-location':
				return array(
					'post_id' => intval( $parameters['post_id'] ?? 0 ),
					'meta'    => $parameters['meta'] ?? array(),
				);

			case 'cluckin-chuck/geocode-address':
				return array( 'address' => $parameters['address'] ?? '' );

			default:
				return $parameters;
		}
	}
}
