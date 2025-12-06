<?php
/**
 * Plugin Name: Wing Submit
 * Plugin URI: https://chubes.net
 * Description: Submission form block for new wing locations and reviews with geocoding
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wing-submit
 */

namespace WingSubmit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WING_SUBMIT_VERSION', '0.1.0' );
define( 'WING_SUBMIT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WING_SUBMIT_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', __NAMESPACE__ . '\maybe_include_theme_meta_helper', 5 );

function maybe_include_theme_meta_helper() {
	if ( class_exists( '\\CluckinChuck\\Wing_Location_Meta' ) ) {
		return;
	}

	if ( ! function_exists( 'get_theme_file_path' ) ) {
		return;
	}

	$meta_path = get_theme_file_path( 'inc/class-wing-location-meta.php' );

	if ( $meta_path && file_exists( $meta_path ) ) {
		require_once $meta_path;
	}
}

function get_meta_helper() {
	if ( ! class_exists( '\\CluckinChuck\\Wing_Location_Meta' ) ) {
		return null;
	}

	return '\\CluckinChuck\\Wing_Location_Meta';
}

function map_meta_input( $data ) {
	return array(
		'wing_address'        => $data['address'] ?? '',
		'wing_latitude'       => $data['latitude'] ?? 0,
		'wing_longitude'      => $data['longitude'] ?? 0,
		'wing_phone'          => $data['phone'] ?? '',
		'wing_website'        => $data['website'] ?? '',
		'wing_hours'          => $data['hours'] ?? '',
		'wing_price_range'    => $data['price_range'] ?? '',
		'wing_takeout'        => $data['takeout'] ?? false,
		'wing_delivery'       => $data['delivery'] ?? false,
		'wing_dine_in'        => $data['dine_in'] ?? false,
		'wing_average_rating' => floatval( $data['rating'] ?? 0 ),
		'wing_review_count'   => 1,
	);
}

function get_location_info_for_post( $post_id ) {
	$meta_helper = get_meta_helper();

	if ( $meta_helper ) {
		$meta = $meta_helper::get_location_meta( $post_id );

		return array(
			'address'    => $meta['wing_address'] ?? '',
			'latitude'   => $meta['wing_latitude'] ?? 0,
			'longitude'  => $meta['wing_longitude'] ?? 0,
			'phone'      => $meta['wing_phone'] ?? '',
			'website'    => $meta['wing_website'] ?? '',
			'hours'      => $meta['wing_hours'] ?? '',
			'priceRange' => $meta['wing_price_range'] ?? '',
			'takeout'    => $meta['wing_takeout'] ?? false,
			'delivery'   => $meta['wing_delivery'] ?? false,
			'dineIn'     => $meta['wing_dine_in'] ?? false,
		);
	}

	$post_content = get_post_field( 'post_content', $post_id );
	$blocks       = parse_blocks( $post_content );

	$wing_reviews = array_filter( $blocks, function( $block ) {
		return 'wing-map/wing-review' === ( $block['blockName'] ?? '' );
	} );

	if ( empty( $wing_reviews ) ) {
		return array();
	}

	$first_review = reset( $wing_reviews );
	$attrs        = $first_review['attrs'] ?? array();

	return array(
		'address'    => $attrs['address'] ?? '',
		'latitude'   => $attrs['latitude'] ?? 0,
		'longitude'  => $attrs['longitude'] ?? 0,
		'phone'      => $attrs['phone'] ?? '',
		'website'    => $attrs['website'] ?? '',
		'hours'      => $attrs['hours'] ?? '',
		'priceRange' => $attrs['priceRange'] ?? '',
		'takeout'    => $attrs['takeout'] ?? false,
		'delivery'   => $attrs['delivery'] ?? false,
		'dineIn'     => $attrs['dineIn'] ?? false,
	);
}

function register_block() {



	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type(
		WING_SUBMIT_PATH . 'build/wing-submit',
		array(
			'render_callback' => __NAMESPACE__ . '\\render_callback',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block' );
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_rest_routes' );

function register_rest_routes() {
	register_rest_route(
		'wing-submit/v1',
		'/submit',
		array(
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\\rest_submit_handler',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'wing-submit/v1',
		'/geocode',
		array(
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\\rest_geocode_handler',
			'permission_callback' => '__return_true',
		)
	);
}

function render_callback() {
	$script_handle = 'wing-submit-wing-submit-view-script';

	wp_add_inline_script(
		$script_handle,
		'window.wingSubmitData = ' . wp_json_encode( array(
			'restUrl' => rest_url( 'wing-submit/v1' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		) ),
		'before'
	);

	$is_singular_location = is_singular( 'wing_location' );
	$post_id              = $is_singular_location ? get_the_ID() : 0;
	$button_text          = $is_singular_location ? 'Submit Review' : 'Submit Wing Location';
	$modal_title          = $is_singular_location ? 'Submit Your Wing Review' : 'Submit New Wing Location';

	$location_data = array();
	if ( $is_singular_location ) {
		$location_data = get_location_info_for_post( $post_id );
	}

	ob_start();
	?>
	<div class="wing-submit-block">
		<button
			class="wing-submit-button"
			type="button"
			<?php if ( ! empty( $location_data ) ) : ?>
			data-location-info="<?php echo esc_attr( wp_json_encode( $location_data ) ); ?>"
			<?php endif; ?>
		>
			<?php echo esc_html( $button_text ); ?>
		</button>
	</div>

	<div id="wing-submit-modal" class="wing-submit-modal">
		<div class="wing-modal-overlay"></div>
		<div class="wing-modal-content">
			<div class="wing-modal-header">
				<h2><?php echo esc_html( $modal_title ); ?></h2>
				<button class="wing-modal-close" type="button" aria-label="Close">&times;</button>
			</div>
			<div class="wing-modal-body">
				<div id="wing-form-messages"></div>
				<form id="wing-submit-form">
					<?php if ( ! $is_singular_location ) : ?>
					<div class="wing-form-field">
						<label for="wing_location_name">Location Name <span class="required">*</span></label>
						<input type="text" id="wing_location_name" name="wing_location_name" required>
					</div>
					<?php endif; ?>

					<div class="wing-form-row">
						<div class="wing-form-field">
							<label for="wing_reviewer_name">Your Name <span class="required">*</span></label>
							<input type="text" id="wing_reviewer_name" name="wing_reviewer_name" required>
						</div>
						<div class="wing-form-field">
							<label for="wing_reviewer_email">Your Email <span class="required">*</span></label>
							<input type="email" id="wing_reviewer_email" name="wing_reviewer_email" required>
						</div>
					</div>

					<div class="wing-form-field">
						<label>Overall Rating <span class="required">*</span></label>
						<div class="wing-rating-input">
							<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<input type="radio" id="rating_<?php echo $i; ?>" name="wing_rating" value="<?php echo $i; ?>" required>
							<label for="rating_<?php echo $i; ?>">&#9733;</label>
							<?php endfor; ?>
						</div>
					</div>

					<div class="wing-form-row">
						<div class="wing-form-field">
							<label>Sauce Rating</label>
							<div class="wing-rating-input">
								<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
								<input type="radio" id="sauce_rating_<?php echo $i; ?>" name="wing_sauce_rating" value="<?php echo $i; ?>">
								<label for="sauce_rating_<?php echo $i; ?>">&#9733;</label>
								<?php endfor; ?>
							</div>
						</div>
						<div class="wing-form-field">
							<label>Crispiness Rating</label>
							<div class="wing-rating-input">
								<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
								<input type="radio" id="crisp_rating_<?php echo $i; ?>" name="wing_crispiness_rating" value="<?php echo $i; ?>">
								<label for="crisp_rating_<?php echo $i; ?>">&#9733;</label>
								<?php endfor; ?>
							</div>
						</div>
					</div>

					<div class="wing-form-field">
						<label for="wing_review_text">Your Review <span class="required">*</span></label>
						<textarea id="wing_review_text" name="wing_review_text" required></textarea>
					</div>

					<div class="wing-form-field">
						<label for="wing_address">Address <span class="required">*</span></label>
						<input type="text" id="wing_address" name="wing_address" required>
						<div id="geocode-indicator" class="geocode-indicator"></div>
						<input type="hidden" id="wing_latitude" name="wing_latitude">
						<input type="hidden" id="wing_longitude" name="wing_longitude">
					</div>

					<div class="wing-form-row">
						<div class="wing-form-field">
							<label for="wing_phone">Phone</label>
							<input type="text" id="wing_phone" name="wing_phone">
						</div>
						<div class="wing-form-field">
							<label for="wing_website">Website</label>
							<input type="url" id="wing_website" name="wing_website">
						</div>
					</div>

					<div class="wing-form-field">
						<label for="wing_hours">Hours</label>
						<textarea id="wing_hours" name="wing_hours" rows="3"></textarea>
					</div>

					<div class="wing-form-field">
						<label for="wing_price_range">Price Range</label>
						<select id="wing_price_range" name="wing_price_range">
							<option value="">Select...</option>
							<option value="$">$ - Budget</option>
							<option value="$$">$$ - Moderate</option>
							<option value="$$$">$$$ - Upscale</option>
							<option value="$$$$">$$$$ - Premium</option>
						</select>
					</div>

					<div class="wing-form-field">
						<label>Services Available</label>
						<div class="wing-checkbox-group">
							<label><input type="checkbox" name="wing_takeout" value="1"> Takeout</label>
							<label><input type="checkbox" name="wing_delivery" value="1"> Delivery</label>
							<label><input type="checkbox" name="wing_dine_in" value="1"> Dine-in</label>
						</div>
					</div>

					<div class="wing-honeypot">
						<input type="text" name="wing_website_url" tabindex="-1" autocomplete="off">
					</div>

					<input type="hidden" id="wing_post_id" name="wing_post_id" value="<?php echo esc_attr( $post_id ); ?>">

					<div class="wing-form-submit">
						<button type="submit">Submit</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

function rest_geocode_handler( \WP_REST_Request $request ) {
	$address = sanitize_text_field( $request->get_param( 'address' ) ?? '' );

	if ( empty( $address ) ) {
		return new \WP_REST_Response( array( 'message' => 'Address is required' ), 400 );
	}

	$result = geocode_address( $address );

	if ( $result ) {
		return new \WP_REST_Response( $result, 200 );
	}

	return new \WP_REST_Response( array( 'message' => 'Could not geocode address' ), 400 );
}

function geocode_address( $address ) {
	$url = add_query_arg(
		array(
			'q'      => rawurlencode( $address ),
			'format' => 'json',
			'limit'  => 1,
		),
		'https://nominatim.openstreetmap.org/search'
	);

	$response = wp_remote_get(
		$url,
		array(
			'headers' => array(
				'User-Agent' => 'WingSubmit/1.0 (https://chubes.net)',
			),
			'timeout' => 10,
		)
	);

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( ! empty( $data[0]['lat'] ) && ! empty( $data[0]['lon'] ) ) {
		return array(
			'lat' => floatval( $data[0]['lat'] ),
			'lng' => floatval( $data[0]['lon'] ),
		);
	}

	return false;
}

function rest_submit_handler( \WP_REST_Request $request ) {
	$params = $request->get_params();

	if ( ! empty( $params['wing_website_url'] ) ) {
		return new \WP_REST_Response( array( 'message' => 'Spam detected' ), 400 );
	}

	$ip_hash       = md5( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
	$transient_key = 'wing_submit_' . $ip_hash;

	if ( get_transient( $transient_key ) ) {
		return new \WP_REST_Response( array( 'message' => 'Please wait before submitting again' ), 429 );
	}

	set_transient( $transient_key, true, HOUR_IN_SECONDS );

	$data = sanitize_form_data( $params );

	if ( ! validate_form_data( $data ) ) {
		return new \WP_REST_Response( array( 'message' => 'Please fill in all required fields' ), 400 );
	}

	$post_id = intval( $data['post_id'] ?? 0 );

	if ( $post_id > 0 && get_post_type( $post_id ) === 'wing_location' ) {
		$result = create_pending_review_comment( $post_id, $data );
	} else {
		$result = create_pending_location( $data );
	}

	if ( is_wp_error( $result ) ) {
		return new \WP_REST_Response( array( 'message' => $result->get_error_message() ), 500 );
	}

	return new \WP_REST_Response(
		array( 'message' => 'Thank you! Your submission has been received and is pending review.' ),
		200
	);
}

function sanitize_form_data( $post_data ) {
	return array(
		'post_id'           => intval( $post_data['wing_post_id'] ?? 0 ),
		'location_name'     => sanitize_text_field( $post_data['wing_location_name'] ?? '' ),
		'reviewer_name'     => sanitize_text_field( $post_data['wing_reviewer_name'] ?? '' ),
		'reviewer_email'    => sanitize_email( $post_data['wing_reviewer_email'] ?? '' ),
		'rating'            => intval( $post_data['wing_rating'] ?? 0 ),
		'sauce_rating'      => intval( $post_data['wing_sauce_rating'] ?? 0 ),
		'crispiness_rating' => intval( $post_data['wing_crispiness_rating'] ?? 0 ),
		'review_text'       => sanitize_textarea_field( $post_data['wing_review_text'] ?? '' ),
		'address'           => sanitize_text_field( $post_data['wing_address'] ?? '' ),
		'latitude'          => floatval( $post_data['wing_latitude'] ?? 0 ),
		'longitude'         => floatval( $post_data['wing_longitude'] ?? 0 ),
		'phone'             => sanitize_text_field( $post_data['wing_phone'] ?? '' ),
		'website'           => esc_url_raw( $post_data['wing_website'] ?? '' ),
		'hours'             => sanitize_textarea_field( $post_data['wing_hours'] ?? '' ),
		'price_range'       => sanitize_text_field( $post_data['wing_price_range'] ?? '' ),
		'takeout'           => ! empty( $post_data['wing_takeout'] ),
		'delivery'          => ! empty( $post_data['wing_delivery'] ),
		'dine_in'           => ! empty( $post_data['wing_dine_in'] ),
	);
}

function validate_form_data( $data ) {
	if ( empty( $data['reviewer_name'] ) ) return false;
	if ( empty( $data['reviewer_email'] ) ) return false;
	if ( empty( $data['rating'] ) || $data['rating'] < 1 || $data['rating'] > 5 ) return false;
	if ( empty( $data['review_text'] ) ) return false;
	if ( empty( $data['address'] ) ) return false;
	if ( empty( $data['latitude'] ) || empty( $data['longitude'] ) ) return false;
	if ( empty( $data['post_id'] ) && empty( $data['location_name'] ) ) return false;

	return true;
}

function create_pending_review_comment( $post_id, $data ) {
	$comment_data = array(
		'comment_post_ID'      => $post_id,
		'comment_author'       => $data['reviewer_name'],
		'comment_author_email' => $data['reviewer_email'],
		'comment_content'      => $data['review_text'],
		'comment_type'         => 'comment',
		'comment_approved'     => 0,
	);

	$comment_id = wp_insert_comment( $comment_data );

	if ( ! $comment_id ) {
		return new \WP_Error( 'comment_failed', 'Failed to create review' );
	}

	$meta_helper = get_meta_helper();
	if ( $meta_helper ) {
		$meta_helper::update_location_meta( $post_id, map_meta_input( $data ) );
	}

	add_comment_meta( $comment_id, 'wing_rating', $data['rating'] );
	add_comment_meta( $comment_id, 'wing_sauce_rating', $data['sauce_rating'] );
	add_comment_meta( $comment_id, 'wing_crispiness_rating', $data['crispiness_rating'] );

	send_admin_email( 'review', $data, get_the_title( $post_id ) );

	return $comment_id;
}

function create_pending_location( $data ) {
	$block_content = sprintf(
		'<!-- wp:wing-map/wing-review %s /-->',
		wp_json_encode( array(
			'reviewerName'     => $data['reviewer_name'],
			'reviewerEmail'    => $data['reviewer_email'],
			'rating'           => $data['rating'],
			'sauceRating'      => $data['sauce_rating'],
			'crispinessRating' => $data['crispiness_rating'],
			'reviewText'       => $data['review_text'],
			'timestamp'        => current_time( 'mysql' ),
			'address'          => $data['address'],
			'latitude'         => $data['latitude'],
			'longitude'        => $data['longitude'],
			'phone'            => $data['phone'],
			'website'          => $data['website'],
			'hours'            => $data['hours'],
			'priceRange'       => $data['price_range'],
			'takeout'          => $data['takeout'],
			'delivery'         => $data['delivery'],
			'dineIn'           => $data['dine_in'],
		) )
	);

	$post_id = wp_insert_post( array(
		'post_title'   => $data['location_name'],
		'post_content' => $block_content,
		'post_type'    => 'wing_location',
		'post_status'  => 'pending',
	) );

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$meta_helper = get_meta_helper();
	if ( $meta_helper ) {
		$meta_helper::update_location_meta( $post_id, map_meta_input( $data ) );
	}

	send_admin_email( 'location', $data, $data['location_name'] );

	return $post_id;
}

function send_admin_email( $type, $data, $location_name ) {
	$admin_email = get_option( 'admin_email' );

	if ( $type === 'location' ) {
		$subject  = 'New Wing Location Pending Review - ' . $location_name;
		$message  = "A new wing location has been submitted and is pending review.\n\n";
		$message .= "Location: {$location_name}\n";
		$message .= "Submitted by: {$data['reviewer_name']} ({$data['reviewer_email']})\n";
		$message .= "Address: {$data['address']}\n";
		$message .= "Overall Rating: {$data['rating']}/5\n\n";
		$message .= 'Review Posts: ' . admin_url( 'edit.php?post_type=wing_location&post_status=pending' );
	} else {
		$subject  = 'New Wing Review Submitted - ' . $location_name;
		$message  = "A new review has been submitted for {$location_name}.\n\n";
		$message .= "Reviewer: {$data['reviewer_name']} ({$data['reviewer_email']})\n";
		$message .= "Overall Rating: {$data['rating']}/5\n\n";
		$message .= 'Moderate Comments: ' . admin_url( 'edit-comments.php?comment_status=moderated' );
	}

	wp_mail( $admin_email, $subject, $message );
}
