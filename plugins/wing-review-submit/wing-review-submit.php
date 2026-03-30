<?php
/**
 * Plugin Name: Wing Review Submit
 * Plugin URI: https://chubes.net
 * Description: Frontend submission form block for new wing locations and reviews
 * Version: 0.1.3
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wing-review-submit
 */

namespace WingReviewSubmit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WING_REVIEW_SUBMIT_VERSION', '0.1.3' );
define( 'WING_REVIEW_SUBMIT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WING_REVIEW_SUBMIT_URL', plugin_dir_url( __FILE__ ) );

require_once WING_REVIEW_SUBMIT_PATH . 'inc/class-submit-abilities.php';

/**
 * Bootstrap abilities registration.
 */
function register_abilities() {
	new Submit_Abilities();
}
add_action( 'init', __NAMESPACE__ . '\\register_abilities' );

/**
 * Get the theme's meta helper class if available.
 */
function get_meta_helper() {
	if ( ! class_exists( '\\CluckinChuck\\Wing_Location_Meta' ) ) {
		return null;
	}

	return '\\CluckinChuck\\Wing_Location_Meta';
}

/**
 * Get the theme's geocoding function if available.
 */
function geocode_address( $address ) {
	if ( ! function_exists( '\\CluckinChuck\\geocode_address' ) ) {
		return false;
	}

	return \CluckinChuck\geocode_address( $address );
}

/**
 * Get location info from post meta.
 */
function get_location_info_for_post( $post_id ) {
	$meta_helper = get_meta_helper();

	if ( ! $meta_helper ) {
		return array();
	}

	$meta = $meta_helper::get_location_meta( $post_id );

	return array(
		'address'   => $meta['wing_address'] ?? '',
		'latitude'  => $meta['wing_latitude'] ?? 0,
		'longitude' => $meta['wing_longitude'] ?? 0,
		'website'   => $meta['wing_website'] ?? '',
		'instagram' => $meta['wing_instagram'] ?? '',
	);
}

/**
 * Register the block.
 */
function register_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type(
		WING_REVIEW_SUBMIT_PATH . 'build/wing-review-submit',
		array(
			'render_callback' => __NAMESPACE__ . '\\render_callback',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block' );

/**
 * REST routes live in cluckin-chuck-api under the cluckin-chuck/v1 namespace.
 */

/**
 * Render the block on the frontend.
 */
function render_callback() {
	$script_handle = 'wing-review-submit-wing-review-submit-view-script';

	wp_add_inline_script(
		$script_handle,
		'window.wingReviewSubmitData = ' . wp_json_encode( array(
			'restUrl'            => rest_url( 'cluckin-chuck/v1' ),
			'nonce'              => wp_create_nonce( 'wp_rest' ),
			'submitReviewPath'   => '/reviews/submit',
			'submitLocationPath' => '/locations/submit',
			'geocodePath'        => '/locations/geocode',
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
	<div class="wing-review-submit-block">
		<button
			class="wing-review-submit-button"
			type="button"
			<?php if ( ! empty( $location_data ) ) : ?>
			data-location-info="<?php echo esc_attr( wp_json_encode( $location_data ) ); ?>"
			<?php endif; ?>
		>
			<?php echo esc_html( $button_text ); ?>
		</button>
	</div>

	<div id="wing-review-submit-modal" class="wing-review-submit-modal">
		<div class="wing-modal-overlay"></div>
		<div class="wing-modal-content">
			<div class="wing-modal-header">
				<h2><?php echo esc_html( $modal_title ); ?></h2>
				<button class="wing-modal-close" type="button" aria-label="Close">&times;</button>
			</div>
			<div class="wing-modal-body">
				<div id="wing-form-messages"></div>
				<form id="wing-review-submit-form">
					<?php if ( ! $is_singular_location ) : ?>
					<div class="wing-form-field">
						<label for="wing_location_name">Location Name <span class="required">*</span></label>
						<input type="text" id="wing_location_name" name="wing_location_name" required>
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
							<label for="wing_website">Website</label>
							<input type="url" id="wing_website" name="wing_website">
						</div>
						<div class="wing-form-field">
							<label for="wing_instagram">Instagram</label>
							<input type="url" id="wing_instagram" name="wing_instagram" placeholder="https://instagram.com/...">
						</div>
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
							<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
							<input type="radio" id="rating_<?php echo $i; ?>" name="wing_rating" value="<?php echo $i; ?>" required>
							<label for="rating_<?php echo $i; ?>">&#9733;</label>
							<?php endfor; ?>
						</div>
					</div>

					<div class="wing-form-row">
						<div class="wing-form-field">
							<label>Sauce Rating</label>
							<div class="wing-rating-input">
								<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
								<input type="radio" id="sauce_rating_<?php echo $i; ?>" name="wing_sauce_rating" value="<?php echo $i; ?>">
								<label for="sauce_rating_<?php echo $i; ?>">&#9733;</label>
								<?php endfor; ?>
							</div>
						</div>
						<div class="wing-form-field">
							<label>Crispiness Rating</label>
							<div class="wing-rating-input">
								<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
								<input type="radio" id="crisp_rating_<?php echo $i; ?>" name="wing_crispiness_rating" value="<?php echo $i; ?>">
								<label for="crisp_rating_<?php echo $i; ?>">&#9733;</label>
								<?php endfor; ?>
							</div>
						</div>
					</div>

					<div class="wing-form-row">
						<div class="wing-form-field">
							<label for="wing_count"># of Wings</label>
							<input type="number" id="wing_count" name="wing_count" min="1" step="1" placeholder="10">
						</div>
						<div class="wing-form-field">
							<label for="wing_total_price">Total Price ($)</label>
							<input type="number" id="wing_total_price" name="wing_total_price" min="0" step="0.01" placeholder="15.00">
						</div>
					</div>

					<div class="wing-form-field">
						<label for="wing_review_text">Your Review <span class="required">*</span></label>
						<textarea id="wing_review_text" name="wing_review_text" required></textarea>
					</div>

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



/**
 * Create a pending comment for review on existing location.
 */
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

	if ( $data['wing_count'] > 0 && $data['total_price'] > 0 ) {
		add_comment_meta( $comment_id, 'wing_count', $data['wing_count'] );
		add_comment_meta( $comment_id, 'wing_total_price', $data['total_price'] );
		add_comment_meta( $comment_id, 'wing_ppw', $data['ppw'] );
	}

	/**
	 * Fires after a wing review comment is submitted and pending moderation.
	 *
	 * @since 0.2.0
	 *
	 * @param int    $comment_id    The new comment ID.
	 * @param array  $data          Sanitized submission data.
	 * @param string $location_name The wing location title.
	 */
	do_action( 'cluckin_chuck_review_submitted', $comment_id, $data, get_the_title( $post_id ) );

	return $comment_id;
}

/**
 * Create a new pending wing location with initial review.
 */
function create_pending_location( $data ) {
	$review_block = sprintf(
		'<!-- wp:wing-review/wing-review %s /-->',
		wp_json_encode( array(
			'reviewerName'     => $data['reviewer_name'],
			'reviewerEmail'    => $data['reviewer_email'],
			'rating'           => $data['rating'],
			'sauceRating'      => $data['sauce_rating'],
			'crispinessRating' => $data['crispiness_rating'],
			'reviewText'       => $data['review_text'],
			'timestamp'        => current_time( 'mysql' ),
			'wingCount'        => $data['wing_count'],
			'totalPrice'       => $data['total_price'],
			'ppw'              => $data['ppw'],
		) )
	);

	$location_details_block = '<!-- wp:wing-location-details/wing-location-details /-->';

	$post_content = $location_details_block . "\n\n" . $review_block;

	$post_id = wp_insert_post( array(
		'post_title'   => $data['location_name'],
		'post_content' => $post_content,
		'post_type'    => 'wing_location',
		'post_status'  => 'pending',
	) );

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$meta_helper = get_meta_helper();
	if ( $meta_helper ) {
		$meta_helper::update_location_meta( $post_id, array(
			'wing_address'        => $data['address'],
			'wing_latitude'       => $data['latitude'],
			'wing_longitude'      => $data['longitude'],
			'wing_website'        => $data['website'],
			'wing_instagram'      => $data['instagram'],
			'wing_average_rating' => floatval( $data['rating'] ),
			'wing_review_count'   => 1,
			'wing_average_ppw'    => $data['ppw'],
			'wing_min_ppw'        => $data['ppw'],
			'wing_max_ppw'        => $data['ppw'],
		) );
	}

	/**
	 * Fires after a new wing location is submitted and pending approval.
	 *
	 * @since 0.2.0
	 *
	 * @param int   $post_id The new pending post ID.
	 * @param array $data    Sanitized submission data.
	 */
	do_action( 'cluckin_chuck_location_submitted', $post_id, $data );

	return $post_id;
}
