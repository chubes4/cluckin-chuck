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
				'post_id'           => array( 'type' => 'integer' ),
				'reviewer_name'     => array( 'type' => 'string' ),
				'reviewer_email'    => array( 'type' => 'string', 'format' => 'email' ),
				'rating'            => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 5 ),
				'review_text'       => array( 'type' => 'string' ),
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

	$params = $request->get_params();
	$params = array_merge(
		$params,
		array(
			'post_id'           => absint( $params['post_id'] ?? $params['wing_post_id'] ?? 0 ),
			'reviewer_name'     => sanitize_text_field( $params['reviewer_name'] ?? $params['wing_reviewer_name'] ?? '' ),
			'reviewer_email'    => sanitize_email( $params['reviewer_email'] ?? $params['wing_reviewer_email'] ?? '' ),
			'rating'            => absint( $params['rating'] ?? $params['wing_rating'] ?? 0 ),
			'review_text'       => sanitize_textarea_field( $params['review_text'] ?? $params['wing_review_text'] ?? '' ),
			'sauce_rating'      => absint( $params['sauce_rating'] ?? $params['wing_sauce_rating'] ?? 0 ),
			'crispiness_rating' => absint( $params['crispiness_rating'] ?? $params['wing_crispiness_rating'] ?? 0 ),
			'wing_count'        => absint( $params['wing_count'] ?? 0 ),
			'total_price'       => floatval( $params['total_price'] ?? $params['wing_total_price'] ?? 0 ),
		)
	);
	$post = get_post( $params['post_id'] );
	if ( ! $post || 'wing_location' !== $post->post_type || 'publish' !== $post->post_status ) {
		return new WP_Error( 'not_found', 'Published wing location not found.', array( 'status' => 404 ) );
	}
	if ( empty( $params['reviewer_name'] ) || ! is_email( $params['reviewer_email'] ) || empty( $params['review_text'] ) ) {
		return new WP_Error( 'missing_fields', 'Name, a valid email, and review text are required.', array( 'status' => 400 ) );
	}
	if ( $params['rating'] < 1 || $params['rating'] > 5 ) {
		return new WP_Error( 'invalid_rating', 'Rating must be between 1 and 5.', array( 'status' => 400 ) );
	}

	$photo_ids = cluckin_chuck_api_upload_review_photos( $request, $params['post_id'] );
	if ( is_wp_error( $photo_ids ) ) {
		return $photo_ids;
	}
	$params['photo_ids'] = $photo_ids;

	$result = $ability->execute( $params );

	if ( is_wp_error( $result ) ) {
		foreach ( $photo_ids as $photo_id ) {
			wp_delete_attachment( $photo_id, true );
		}
		$status = in_array( $result->get_error_code(), array( 'not_found', 'missing_fields', 'invalid_rating' ), true ) ? 400 : 500;
		if ( 'not_found' === $result->get_error_code() ) {
			$status = 404;
		}
		return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => $status ) );
	}

	return rest_ensure_response( $result );
}

function cluckin_chuck_api_upload_review_photos( WP_REST_Request $request, $post_id ) {
	$files = $request->get_file_params();
	$photos = $files['wing_photos'] ?? array();

	if ( empty( $photos ) || empty( $photos['name'] ) ) {
		return array();
	}

	$names = is_array( $photos['name'] ) ? $photos['name'] : array( $photos['name'] );
	if ( count( $names ) > 4 ) {
		return new WP_Error( 'too_many_photos', 'You can upload up to 4 photos per review.', array( 'status' => 400 ) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$photo_ids = array();
	foreach ( array_keys( $names ) as $index ) {
		$file = array(
			'name'     => $photos['name'][ $index ],
			'type'     => $photos['type'][ $index ],
			'tmp_name' => $photos['tmp_name'][ $index ],
			'error'    => $photos['error'][ $index ],
			'size'     => $photos['size'][ $index ],
		);

		if ( $file['size'] > 8 * MB_IN_BYTES ) {
			foreach ( $photo_ids as $photo_id ) {
				wp_delete_attachment( $photo_id, true );
			}
			return new WP_Error( 'photo_too_large', 'Each photo must be smaller than 8 MB.', array( 'status' => 400 ) );
		}

		$_FILES['wing_review_photo'] = $file;
		$attachment_id = media_handle_upload( 'wing_review_photo', $post_id );
		unset( $_FILES['wing_review_photo'] );

		if ( is_wp_error( $attachment_id ) ) {
			foreach ( $photo_ids as $photo_id ) {
				wp_delete_attachment( $photo_id, true );
			}
			return new WP_Error( 'photo_upload_failed', $attachment_id->get_error_message(), array( 'status' => 400 ) );
		}

		update_post_meta( $attachment_id, '_wing_photo_status', 'pending' );
		$photo_ids[] = $attachment_id;
	}

	return $photo_ids;
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
