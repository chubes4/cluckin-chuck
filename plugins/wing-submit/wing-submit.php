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

add_action( 'wp_ajax_wing_submit', __NAMESPACE__ . '\\ajax_submit_handler' );
add_action( 'wp_ajax_nopriv_wing_submit', __NAMESPACE__ . '\\ajax_submit_handler' );
add_action( 'wp_ajax_wing_geocode', __NAMESPACE__ . '\\ajax_geocode_handler' );
add_action( 'wp_ajax_nopriv_wing_geocode', __NAMESPACE__ . '\\ajax_geocode_handler' );

function render_callback() {
	$script_handle = 'wing-submit-wing-submit-view-script';

	wp_add_inline_script(
		$script_handle,
		'window.wingSubmitData = ' . wp_json_encode( array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wing_submit_nonce' ),
		) ),
		'before'
	);

	$is_singular_location = is_singular( 'wing_location' );
	$post_id              = $is_singular_location ? get_the_ID() : 0;
	$button_text          = $is_singular_location ? 'Submit Review' : 'Submit Wing Location';
	$modal_title          = $is_singular_location ? 'Submit Your Wing Review' : 'Submit New Wing Location';

	$location_data = array();
	if ( $is_singular_location ) {
		$post_content = get_post_field( 'post_content', $post_id );
		$blocks       = parse_blocks( $post_content );

		$wing_reviews = array_filter( $blocks, function( $block ) {
			return 'wing-map/wing-review' === ( $block['blockName'] ?? '' );
		} );

		if ( ! empty( $wing_reviews ) ) {
			$first_review  = reset( $wing_reviews );
			$attrs         = $first_review['attrs'] ?? array();
			$location_data = array(
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

function ajax_geocode_handler() {
	check_ajax_referer( 'wing_submit_nonce', 'nonce' );

	$address = sanitize_text_field( $_POST['address'] ?? '' );

	if ( empty( $address ) ) {
		wp_send_json_error( 'Address is required' );
	}

	$result = geocode_address( $address );

	if ( $result ) {
		wp_send_json_success( $result );
	} else {
		wp_send_json_error( 'Could not geocode address' );
	}
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

function ajax_submit_handler() {
	check_ajax_referer( 'wing_submit_nonce', 'nonce' );

	if ( ! empty( $_POST['wing_website_url'] ) ) {
		wp_send_json_error( 'Spam detected' );
	}

	$ip_hash       = md5( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
	$transient_key = 'wing_submit_' . $ip_hash;

	if ( get_transient( $transient_key ) ) {
		wp_send_json_error( 'Please wait before submitting again' );
	}

	set_transient( $transient_key, true, HOUR_IN_SECONDS );

	$data = sanitize_form_data( $_POST );

	if ( ! validate_form_data( $data ) ) {
		wp_send_json_error( 'Please fill in all required fields' );
	}

	$post_id = intval( $data['post_id'] ?? 0 );

	if ( $post_id > 0 && get_post_type( $post_id ) === 'wing_location' ) {
		$result = create_pending_review_comment( $post_id, $data );
	} else {
		$result = create_pending_location( $data );
	}

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( $result->get_error_message() );
	}

	wp_send_json_success( array(
		'message' => 'Thank you! Your submission has been received and is pending review.',
	) );
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

	add_comment_meta( $comment_id, 'wing_rating', $data['rating'] );
	add_comment_meta( $comment_id, 'wing_sauce_rating', $data['sauce_rating'] );
	add_comment_meta( $comment_id, 'wing_crispiness_rating', $data['crispiness_rating'] );
	add_comment_meta( $comment_id, 'wing_address', $data['address'] );
	add_comment_meta( $comment_id, 'wing_latitude', $data['latitude'] );
	add_comment_meta( $comment_id, 'wing_longitude', $data['longitude'] );
	add_comment_meta( $comment_id, 'wing_phone', $data['phone'] );
	add_comment_meta( $comment_id, 'wing_website', $data['website'] );
	add_comment_meta( $comment_id, 'wing_hours', $data['hours'] );
	add_comment_meta( $comment_id, 'wing_price_range', $data['price_range'] );
	add_comment_meta( $comment_id, 'wing_takeout', $data['takeout'] );
	add_comment_meta( $comment_id, 'wing_delivery', $data['delivery'] );
	add_comment_meta( $comment_id, 'wing_dine_in', $data['dine_in'] );

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
