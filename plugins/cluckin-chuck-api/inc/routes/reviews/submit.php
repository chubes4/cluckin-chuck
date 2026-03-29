<?php
/**
 * POST /cluckin-chuck/v1/reviews/submit
 * POST /cluckin-chuck/v1/locations/submit
 *
 * Submit a new review or new wing location. Public endpoints (nonce-gated).
 *
 * @package CluckinChuck\API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'cluckin_chuck_api_register_routes', 'cluckin_chuck_api_register_submit_routes' );

function cluckin_chuck_api_register_submit_routes() {
	// Submit a review for an existing location.
	register_rest_route(
		'cluckin-chuck/v1',
		'/reviews/submit',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'cluckin_chuck_api_submit_review_handler',
			'permission_callback' => '__return_true',
			'args'                => array(
				'post_id'           => array( 'type' => 'integer', 'required' => true ),
				'reviewer_name'     => array( 'type' => 'string', 'required' => true ),
				'reviewer_email'    => array( 'type' => 'string', 'required' => true, 'format' => 'email' ),
				'rating'            => array( 'type' => 'integer', 'required' => true, 'minimum' => 1, 'maximum' => 5 ),
				'review_text'       => array( 'type' => 'string', 'required' => true ),
				'sauce_rating'      => array( 'type' => 'integer', 'default' => 0 ),
				'crispiness_rating' => array( 'type' => 'integer', 'default' => 0 ),
				'wing_count'        => array( 'type' => 'integer', 'default' => 0 ),
				'total_price'       => array( 'type' => 'number', 'default' => 0 ),
			),
		)
	);

	// Submit a new wing location.
	register_rest_route(
		'cluckin-chuck/v1',
		'/locations/submit',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'cluckin_chuck_api_submit_location_handler',
			'permission_callback' => '__return_true',
			'args'                => array(
				'location_name'     => array( 'type' => 'string', 'required' => true ),
				'address'           => array( 'type' => 'string', 'required' => true ),
				'latitude'          => array( 'type' => 'number', 'required' => true ),
				'longitude'         => array( 'type' => 'number', 'required' => true ),
				'reviewer_name'     => array( 'type' => 'string', 'required' => true ),
				'reviewer_email'    => array( 'type' => 'string', 'required' => true, 'format' => 'email' ),
				'rating'            => array( 'type' => 'integer', 'required' => true, 'minimum' => 1, 'maximum' => 5 ),
				'review_text'       => array( 'type' => 'string', 'required' => true ),
				'website'           => array( 'type' => 'string', 'default' => '' ),
				'instagram'         => array( 'type' => 'string', 'default' => '' ),
				'sauce_rating'      => array( 'type' => 'integer', 'default' => 0 ),
				'crispiness_rating' => array( 'type' => 'integer', 'default' => 0 ),
				'wing_count'        => array( 'type' => 'integer', 'default' => 0 ),
				'total_price'       => array( 'type' => 'number', 'default' => 0 ),
			),
		)
	);
}

function cluckin_chuck_api_submit_review_handler( WP_REST_Request $request ) {
	$ability = wp_get_ability( 'cluckin-chuck/submit-review' );

	if ( ! $ability ) {
		return new WP_Error( 'ability_missing', 'Submit review ability not available.', array( 'status' => 500 ) );
	}

	$result = $ability->execute( $request->get_params() );

	if ( is_wp_error( $result ) ) {
		$status = in_array( $result->get_error_code(), array( 'not_found', 'missing_fields', 'invalid_rating' ), true ) ? 400 : 500;
		if ( 'not_found' === $result->get_error_code() ) {
			$status = 404;
		}
		return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => $status ) );
	}

	return rest_ensure_response( $result );
}

function cluckin_chuck_api_submit_location_handler( WP_REST_Request $request ) {
	$ability = wp_get_ability( 'cluckin-chuck/submit-location' );

	if ( ! $ability ) {
		return new WP_Error( 'ability_missing', 'Submit location ability not available.', array( 'status' => 500 ) );
	}

	$result = $ability->execute( $request->get_params() );

	if ( is_wp_error( $result ) ) {
		$code   = $result->get_error_code();
		$status = in_array( $code, array( 'missing_field', 'invalid_rating', 'missing_coordinates' ), true ) ? 400 : 500;
		return new WP_Error( $code, $result->get_error_message(), array( 'status' => $status ) );
	}

	return rest_ensure_response( $result );
}
