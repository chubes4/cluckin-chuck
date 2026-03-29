<?php
/**
 * Cluckin' Chuck Abilities API — core location abilities.
 *
 * Registers the ability category and four foundational abilities:
 *   - cluckin-chuck/get-location
 *   - cluckin-chuck/update-location
 *   - cluckin-chuck/list-locations
 *   - cluckin-chuck/geocode-address
 *
 * @package CluckinChuck
 * @since 0.2.0
 */

namespace CluckinChuck;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wing_Abilities {

	/**
	 * Prevent duplicate registration.
	 *
	 * @var bool
	 */
	private static bool $registered = false;

	/**
	 * Boot abilities registration.
	 */
	public function __construct() {
		if ( ! class_exists( 'WP_Ability' ) ) {
			return;
		}

		if ( self::$registered ) {
			return;
		}

		self::$registered = true;

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		$this->register_abilities();
	}

	/**
	 * Register the cluckin-chuck ability category.
	 */
	public function register_category(): void {
		wp_register_ability_category(
			'cluckin-chuck',
			array(
				'label'       => __( 'Cluckin\' Chuck', 'cluckin-chuck' ),
				'description' => __( 'Wing location and review management abilities.', 'cluckin-chuck' ),
			)
		);
	}

	/**
	 * Register all core location abilities.
	 */
	private function register_abilities(): void {
		$register = function () {
			$this->register_get_location();
			$this->register_update_location();
			$this->register_list_locations();
			$this->register_geocode_address();
		};

		if ( doing_action( 'wp_abilities_api_init' ) ) {
			$register();
		} elseif ( ! did_action( 'wp_abilities_api_init' ) ) {
			add_action( 'wp_abilities_api_init', $register );
		}
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/get-location
	// ------------------------------------------------------------------

	private function register_get_location(): void {
		wp_register_ability(
			'cluckin-chuck/get-location',
			array(
				'label'               => __( 'Get Location', 'cluckin-chuck' ),
				'description'         => __( 'Retrieve a wing location\'s details including address, coordinates, ratings, and pricing.', 'cluckin-chuck' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The wing_location post ID.', 'cluckin-chuck' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'  => array( 'type' => 'integer' ),
						'title'    => array( 'type' => 'string' ),
						'status'   => array( 'type' => 'string' ),
						'url'      => array( 'type' => 'string' ),
						'meta'     => array( 'type' => 'object' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_location' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' ) || ( defined( 'WP_CLI' ) && WP_CLI );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'readonly' => true ),
				),
			)
		);
	}

	/**
	 * Execute: get-location.
	 *
	 * @param array $input { post_id: int }.
	 * @return array|\WP_Error
	 */
	public function execute_get_location( array $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post || 'wing_location' !== $post->post_type ) {
			return new \WP_Error(
				'not_found',
				__( 'Wing location not found.', 'cluckin-chuck' ),
				array( 'status' => 404 )
			);
		}

		$meta = Wing_Location_Meta::get_location_meta( $post_id );

		return array(
			'post_id' => $post_id,
			'title'   => $post->post_title,
			'status'  => $post->post_status,
			'url'     => get_permalink( $post_id ),
			'meta'    => $meta,
		);
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/update-location
	// ------------------------------------------------------------------

	private function register_update_location(): void {
		wp_register_ability(
			'cluckin-chuck/update-location',
			array(
				'label'               => __( 'Update Location', 'cluckin-chuck' ),
				'description'         => __( 'Update a wing location\'s metadata (address, website, Instagram, coordinates).', 'cluckin-chuck' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'meta' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The wing_location post ID.', 'cluckin-chuck' ),
						),
						'meta'    => array(
							'type'        => 'object',
							'description' => __( 'Partial meta object — only provided keys are updated.', 'cluckin-chuck' ),
							'properties'  => array(
								'wing_address'   => array( 'type' => 'string' ),
								'wing_latitude'  => array( 'type' => 'number' ),
								'wing_longitude' => array( 'type' => 'number' ),
								'wing_website'   => array( 'type' => 'string' ),
								'wing_instagram' => array( 'type' => 'string' ),
							),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'post_id' => array( 'type' => 'integer' ),
						'updated' => array( 'type' => 'array' ),
						'meta'    => array( 'type' => 'object' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_update_location' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' ) || ( defined( 'WP_CLI' ) && WP_CLI );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'idempotent' => true ),
				),
			)
		);
	}

	/**
	 * Execute: update-location.
	 *
	 * @param array $input { post_id: int, meta: array }.
	 * @return array|\WP_Error
	 */
	public function execute_update_location( array $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post || 'wing_location' !== $post->post_type ) {
			return new \WP_Error(
				'not_found',
				__( 'Wing location not found.', 'cluckin-chuck' ),
				array( 'status' => 404 )
			);
		}

		$meta_input = $input['meta'] ?? array();

		if ( empty( $meta_input ) ) {
			return new \WP_Error(
				'empty_meta',
				__( 'No meta fields provided to update.', 'cluckin-chuck' ),
				array( 'status' => 400 )
			);
		}

		// Only allow editable meta keys (not computed stats).
		$editable_keys = array( 'wing_address', 'wing_latitude', 'wing_longitude', 'wing_website', 'wing_instagram' );
		$filtered      = array_intersect_key( $meta_input, array_flip( $editable_keys ) );

		if ( empty( $filtered ) ) {
			return new \WP_Error(
				'invalid_meta',
				__( 'No valid editable meta keys provided.', 'cluckin-chuck' ),
				array( 'status' => 400 )
			);
		}

		Wing_Location_Meta::update_location_meta( $post_id, $filtered );

		return array(
			'success' => true,
			'post_id' => $post_id,
			'updated' => array_keys( $filtered ),
			'meta'    => Wing_Location_Meta::get_location_meta( $post_id ),
		);
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/list-locations
	// ------------------------------------------------------------------

	private function register_list_locations(): void {
		wp_register_ability(
			'cluckin-chuck/list-locations',
			array(
				'label'               => __( 'List Locations', 'cluckin-chuck' ),
				'description'         => __( 'List wing locations with optional filtering, pagination, and sorting.', 'cluckin-chuck' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_status' => array(
							'type'        => 'string',
							'default'     => 'publish',
							'description' => __( 'Post status filter.', 'cluckin-chuck' ),
						),
						'per_page'    => array(
							'type'        => 'integer',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
							'description' => __( 'Results per page.', 'cluckin-chuck' ),
						),
						'offset'      => array(
							'type'    => 'integer',
							'default' => 0,
						),
						'orderby'     => array(
							'type'    => 'string',
							'default' => 'date',
							'enum'    => array( 'date', 'title', 'modified', 'meta_value_num' ),
						),
						'order'       => array(
							'type'    => 'string',
							'default' => 'DESC',
							'enum'    => array( 'ASC', 'DESC' ),
						),
						'search'      => array(
							'type'        => 'string',
							'description' => __( 'Search locations by title.', 'cluckin-chuck' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'locations' => array( 'type' => 'array' ),
						'total'     => array( 'type' => 'integer' ),
						'per_page'  => array( 'type' => 'integer' ),
						'offset'    => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_locations' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' ) || ( defined( 'WP_CLI' ) && WP_CLI );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'readonly' => true ),
				),
			)
		);
	}

	/**
	 * Execute: list-locations.
	 *
	 * @param array $input Query parameters.
	 * @return array
	 */
	public function execute_list_locations( array $input ): array {
		$post_status = sanitize_text_field( $input['post_status'] ?? 'publish' );
		$per_page    = min( max( absint( $input['per_page'] ?? 20 ), 1 ), 100 );
		$offset      = max( absint( $input['offset'] ?? 0 ), 0 );
		$orderby     = sanitize_text_field( $input['orderby'] ?? 'date' );
		$order       = strtoupper( sanitize_text_field( $input['order'] ?? 'DESC' ) );
		$search      = sanitize_text_field( $input['search'] ?? '' );

		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		$args = array(
			'post_type'      => 'wing_location',
			'post_status'    => $post_status,
			'posts_per_page' => $per_page,
			'offset'         => $offset,
			'orderby'        => $orderby,
			'order'          => $order,
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query     = new \WP_Query( $args );
		$locations = array();

		foreach ( $query->posts as $post ) {
			$meta = Wing_Location_Meta::get_location_meta( $post->ID );

			$locations[] = array(
				'post_id'      => $post->ID,
				'title'        => $post->post_title,
				'status'       => $post->post_status,
				'url'          => get_permalink( $post->ID ),
				'date'         => $post->post_date,
				'address'      => $meta['wing_address'],
				'lat'          => $meta['wing_latitude'],
				'lng'          => $meta['wing_longitude'],
				'rating'       => $meta['wing_average_rating'],
				'review_count' => $meta['wing_review_count'],
				'avg_ppw'      => $meta['wing_average_ppw'],
			);
		}

		return array(
			'locations' => $locations,
			'total'     => $query->found_posts,
			'per_page'  => $per_page,
			'offset'    => $offset,
		);
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/geocode-address
	// ------------------------------------------------------------------

	private function register_geocode_address(): void {
		wp_register_ability(
			'cluckin-chuck/geocode-address',
			array(
				'label'               => __( 'Geocode Address', 'cluckin-chuck' ),
				'description'         => __( 'Convert a street address to latitude/longitude coordinates via OpenStreetMap.', 'cluckin-chuck' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'address' ),
					'properties' => array(
						'address' => array(
							'type'        => 'string',
							'description' => __( 'The street address to geocode.', 'cluckin-chuck' ),
							'minLength'   => 1,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'lat' => array( 'type' => 'number' ),
						'lng' => array( 'type' => 'number' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_geocode_address' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' ) || ( defined( 'WP_CLI' ) && WP_CLI );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'readonly' => true ),
				),
			)
		);
	}

	/**
	 * Execute: geocode-address.
	 *
	 * @param array $input { address: string }.
	 * @return array|\WP_Error
	 */
	public function execute_geocode_address( array $input ) {
		$address = sanitize_text_field( $input['address'] ?? '' );

		if ( empty( $address ) ) {
			return new \WP_Error(
				'missing_address',
				__( 'Address is required.', 'cluckin-chuck' ),
				array( 'status' => 400 )
			);
		}

		$result = geocode_address( $address );

		if ( ! $result ) {
			return new \WP_Error(
				'geocode_failed',
				__( 'Could not geocode the provided address.', 'cluckin-chuck' ),
				array( 'status' => 400 )
			);
		}

		return $result;
	}
}
