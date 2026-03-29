<?php
/**
 * POST /cluckin-chuck/v1/locations/geocode
 *
 * Geocode a street address to lat/lng coordinates.
 *
 * @package CluckinChuck\API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'cluckin_chuck_api_register_routes', 'cluckin_chuck_api_register_geocode_route' );

function cluckin_chuck_api_register_geocode_route() {
	register_rest_route(
		'cluckin-chuck/v1',
		'/locations/geocode',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'cluckin_chuck_api_geocode_handler',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'address' => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}

function cluckin_chuck_api_geocode_handler( WP_REST_Request $request ) {
	$ability = wp_get_ability( 'cluckin-chuck/geocode-address' );

	if ( ! $ability ) {
		return new WP_Error( 'ability_missing', 'Geocode ability not available.', array( 'status' => 500 ) );
	}

	$result = $ability->execute( array( 'address' => $request->get_param( 'address' ) ) );

	if ( is_wp_error( $result ) ) {
		return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 400 ) );
	}

	return rest_ensure_response( $result );
}
