<?php
/**
 * PUT /cluckin-chuck/v1/locations/<id>
 *
 * Update a wing location's metadata.
 *
 * @package CluckinChuck\API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'cluckin_chuck_api_register_routes', 'cluckin_chuck_api_register_location_update_route' );

function cluckin_chuck_api_register_location_update_route() {
	register_rest_route(
		'cluckin-chuck/v1',
		'/locations/(?P<id>\d+)',
		array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => 'cluckin_chuck_api_location_update_handler',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'id'   => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'meta' => array(
					'type'        => 'object',
					'required'    => true,
					'description' => 'Partial meta object — only provided keys are updated.',
				),
			),
		)
	);
}

function cluckin_chuck_api_location_update_handler( WP_REST_Request $request ) {
	$ability = wp_get_ability( 'cluckin-chuck/update-location' );

	if ( ! $ability ) {
		return new WP_Error( 'ability_missing', 'Update location ability not available.', array( 'status' => 500 ) );
	}

	$result = $ability->execute( array(
		'post_id' => $request->get_param( 'id' ),
		'meta'    => $request->get_param( 'meta' ),
	) );

	if ( is_wp_error( $result ) ) {
		$status = 'not_found' === $result->get_error_code() ? 404 : 400;
		return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => $status ) );
	}

	return rest_ensure_response( $result );
}
