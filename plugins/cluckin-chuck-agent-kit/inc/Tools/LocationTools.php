<?php
/**
 * Location chat tools — wraps location abilities for the Data Machine chat system.
 *
 * Tools registered:
 *   - list_locations (public)
 *   - get_location (public)
 *   - update_location (admin)
 *   - geocode_address (public)
 *   - approve_wing_location (admin)
 *   - reject_wing_location (admin)
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
		// Tools are tagged with both modes so they are available whether the
		// caller passes ['cluckin-chuck', 'chat'] (frontend public chat) or
		// just ['chat'] (admin chat surface). DM's tool resolver does a mode
		// intersect, so listing both is the broadest reachable surface.
		//
		// NOTE: the field is 'modes', not 'contexts'. Earlier versions used
		// 'contexts' which DM's resolver silently ignores — meaning none of
		// these tools were reaching the frontend chat. Fixed in 0.2.0.
		$modes = array( 'cluckin-chuck', 'chat' );

		$tools['list_wing_locations'] = array(
			'_callable' => $this->schema_normalized( 'get_list_locations_def' ),
			'modes'     => $modes,
		);

		$tools['get_wing_location'] = array(
			'_callable' => $this->schema_normalized( 'get_get_location_def' ),
			'modes'     => $modes,
		);

		$tools['update_wing_location'] = array(
			'_callable' => $this->schema_normalized( 'get_update_location_def' ),
			'modes'     => $modes,
		);

		$tools['geocode_address'] = array(
			'_callable' => $this->schema_normalized( 'get_geocode_def' ),
			'modes'     => $modes,
		);

		$tools['approve_wing_location'] = array(
			'_callable' => $this->schema_normalized( 'get_approve_location_def' ),
			'modes'     => $modes,
		);

		$tools['reject_wing_location'] = array(
			'_callable' => $this->schema_normalized( 'get_reject_location_def' ),
			'modes'     => $modes,
		);

		return $tools;
	}

	/**
	 * Wrap a def method so its `parameters` field is converted from the
	 * bare-map format to proper JSON Schema before DM serializes it for
	 * the LLM provider.
	 *
	 * @param string $method Method name on $this returning the def.
	 * @return callable
	 */
	private function schema_normalized( string $method ): callable {
		return function () use ( $method ) {
			$def = $this->$method();
			if ( isset( $def['parameters'] ) && is_array( $def['parameters'] ) ) {
				$def['parameters'] = SchemaHelper::to_json_schema( $def['parameters'] );
			}
			return $def;
		};
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

	public function get_approve_location_def(): array {
		return array(
			'class'        => self::class,
			'method'       => 'handle_tool_call',
			'description'  => 'Approve and publish a pending wing location submission. Use when an admin wants to approve a location that was submitted by a public user.',
			'ability'      => 'cluckin-chuck/approve-location',
			'access_level' => 'admin',
			'parameters'   => array(
				'post_id' => array(
					'type'        => 'integer',
					'required'    => true,
					'description' => 'The pending wing location post ID to publish.',
				),
			),
		);
	}

	public function get_reject_location_def(): array {
		return array(
			'class'        => self::class,
			'method'       => 'handle_tool_call',
			'description'  => 'Reject a pending wing location submission by trashing it. Use when an admin wants to decline a submitted location.',
			'ability'      => 'cluckin-chuck/reject-location',
			'access_level' => 'admin',
			'parameters'   => array(
				'post_id' => array(
					'type'        => 'integer',
					'required'    => true,
					'description' => 'The pending wing location post ID to reject.',
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

			case 'cluckin-chuck/approve-location':
			case 'cluckin-chuck/reject-location':
				return array( 'post_id' => intval( $parameters['post_id'] ?? 0 ) );

			default:
				return $parameters;
		}
	}
}
