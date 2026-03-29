<?php
/**
 * POST /cluckin-chuck/v1/reviews/recalculate
 *
 * Recalculate aggregate stats for a wing location.
 *
 * @package CluckinChuck\API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'cluckin_chuck_api_register_routes', 'cluckin_chuck_api_register_recalculate_route' );

function cluckin_chuck_api_register_recalculate_route() {
	register_rest_route(
		'cluckin-chuck/v1',
		'/reviews/recalculate',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'cluckin_chuck_api_recalculate_handler',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
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

function cluckin_chuck_api_recalculate_handler( WP_REST_Request $request ) {
	$ability = wp_get_ability( 'cluckin-chuck/recalculate-stats' );

	if ( ! $ability ) {
		return new WP_Error( 'ability_missing', 'Recalculate stats ability not available.', array( 'status' => 500 ) );
	}

	$result = $ability->execute( array( 'post_id' => $request->get_param( 'post_id' ) ) );

	if ( is_wp_error( $result ) ) {
		$status = 'not_found' === $result->get_error_code() ? 404 : 500;
		return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => $status ) );
	}

	return rest_ensure_response( $result );
}
