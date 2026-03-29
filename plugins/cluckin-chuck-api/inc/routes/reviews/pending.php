<?php
/**
 * GET /cluckin-chuck/v1/reviews/pending
 *
 * List pending submissions awaiting moderation.
 *
 * @package CluckinChuck\API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'cluckin_chuck_api_register_routes', 'cluckin_chuck_api_register_reviews_pending_route' );

function cluckin_chuck_api_register_reviews_pending_route() {
	register_rest_route(
		'cluckin-chuck/v1',
		'/reviews/pending',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'cluckin_chuck_api_reviews_pending_handler',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'type' => array(
					'type'    => 'string',
					'default' => 'all',
					'enum'    => array( 'all', 'locations', 'reviews' ),
				),
			),
		)
	);
}

function cluckin_chuck_api_reviews_pending_handler( WP_REST_Request $request ) {
	$ability = wp_get_ability( 'cluckin-chuck/list-pending' );

	if ( ! $ability ) {
		return new WP_Error( 'ability_missing', 'List pending ability not available.', array( 'status' => 500 ) );
	}

	$result = $ability->execute( array( 'type' => $request->get_param( 'type' ) ) );

	if ( is_wp_error( $result ) ) {
		return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 500 ) );
	}

	return rest_ensure_response( $result );
}
