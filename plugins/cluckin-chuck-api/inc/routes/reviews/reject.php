<?php
/**
 * POST /cluckin-chuck/v1/reviews/reject
 *
 * Reject a pending wing review comment.
 *
 * @package CluckinChuck\API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'cluckin_chuck_api_register_routes', 'cluckin_chuck_api_register_review_reject_route' );

function cluckin_chuck_api_register_review_reject_route() {
	register_rest_route(
		'cluckin-chuck/v1',
		'/reviews/reject',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'cluckin_chuck_api_review_reject_handler',
			'permission_callback' => function () {
				return current_user_can( 'moderate_comments' );
			},
			'args'                => array(
				'comment_id' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}

function cluckin_chuck_api_review_reject_handler( WP_REST_Request $request ) {
	$ability = wp_get_ability( 'cluckin-chuck/reject-review' );

	if ( ! $ability ) {
		return new WP_Error( 'ability_missing', 'Reject review ability not available.', array( 'status' => 500 ) );
	}

	$result = $ability->execute( array( 'comment_id' => $request->get_param( 'comment_id' ) ) );

	if ( is_wp_error( $result ) ) {
		$code   = $result->get_error_code();
		$status = 'not_found' === $code ? 404 : ( 'not_a_review' === $code ? 400 : 500 );
		return new WP_Error( $code, $result->get_error_message(), array( 'status' => $status ) );
	}

	return rest_ensure_response( $result );
}
