<?php
/**
 * GET /cluckin-chuck/v1/locations
 *
 * List wing locations with optional filtering, pagination, and sorting.
 *
 * @package CluckinChuck\API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'cluckin_chuck_api_register_routes', 'cluckin_chuck_api_register_locations_list_route' );

function cluckin_chuck_api_register_locations_list_route() {
	register_rest_route(
		'cluckin-chuck/v1',
		'/locations',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'cluckin_chuck_api_locations_list_handler',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'status'   => array(
					'type'              => 'string',
					'default'           => 'publish',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'per_page' => array(
					'type'              => 'integer',
					'default'           => 20,
					'sanitize_callback' => 'absint',
				),
				'offset'   => array(
					'type'              => 'integer',
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
				'orderby'  => array(
					'type'    => 'string',
					'default' => 'date',
					'enum'    => array( 'date', 'title', 'modified' ),
				),
				'order'    => array(
					'type'    => 'string',
					'default' => 'DESC',
					'enum'    => array( 'ASC', 'DESC' ),
				),
				'search'   => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}

function cluckin_chuck_api_locations_list_handler( WP_REST_Request $request ) {
	$ability = wp_get_ability( 'cluckin-chuck/list-locations' );

	if ( ! $ability ) {
		return new WP_Error( 'ability_missing', 'List locations ability not available.', array( 'status' => 500 ) );
	}

	$result = $ability->execute( array(
		'post_status' => $request->get_param( 'status' ),
		'per_page'    => $request->get_param( 'per_page' ),
		'offset'      => $request->get_param( 'offset' ),
		'orderby'     => $request->get_param( 'orderby' ),
		'order'       => $request->get_param( 'order' ),
		'search'      => $request->get_param( 'search' ) ?? '',
	) );

	if ( is_wp_error( $result ) ) {
		return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 500 ) );
	}

	return rest_ensure_response( $result );
}
