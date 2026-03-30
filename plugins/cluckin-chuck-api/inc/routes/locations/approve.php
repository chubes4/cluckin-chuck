<?php
/**
 * POST /cluckin-chuck/v1/locations/approve
 *
 * Publish a pending wing location.
 *
 * @package CluckinChuck\API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'cluckin_chuck_api_register_routes', 'cluckin_chuck_api_register_location_approve_route' );

function cluckin_chuck_api_register_location_approve_route() {
	register_rest_route(
		'cluckin-chuck/v1',
		'/locations/approve',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'cluckin_chuck_api_location_approve_handler',
			'permission_callback' => function () {
				return current_user_can( 'publish_posts' );
			},
			'args'                => array(
				'post_id' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}

function cluckin_chuck_api_location_approve_handler( WP_REST_Request $request ) {
	$ability = wp_get_ability( 'cluckin-chuck/approve-location' );

	if ( ! $ability ) {
		return new WP_Error( 'ability_missing', 'Approve location ability not available.', array( 'status' => 500 ) );
	}

	$result = $ability->execute( array( 'post_id' => $request->get_param( 'post_id' ) ) );

	if ( is_wp_error( $result ) ) {
		$code   = $result->get_error_code();
		$status = 'not_found' === $code ? 404 : ( 'not_pending' === $code ? 400 : 500 );
		return new WP_Error( $code, $result->get_error_message(), array( 'status' => $status ) );
	}

	return rest_ensure_response( $result );
}
