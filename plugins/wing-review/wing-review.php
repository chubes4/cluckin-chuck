<?php
/**
 * Plugin Name: Wing Review
 * Plugin URI: https://chubes.net
 * Description: Review block for wing locations with comment-to-block conversion on approval
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wing-review
 */

namespace WingReview;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WING_REVIEW_VERSION', '0.1.0' );
define( 'WING_REVIEW_PATH', plugin_dir_path( __FILE__ ) );
define( 'WING_REVIEW_URL', plugin_dir_url( __FILE__ ) );

function register_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type(
		WING_REVIEW_PATH . 'build/wing-review',
		array(
			'render_callback' => __NAMESPACE__ . '\\render_callback',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block' );

/**
 * Hook into comment approval to convert to block
 */
add_action( 'wp_set_comment_status', __NAMESPACE__ . '\\convert_to_block', 10, 2 );

/**
 * Render the wing review block on the frontend
 */
function render_callback( $attributes ) {
	$reviewer_name     = esc_html( $attributes['reviewerName'] ?? '' );
	$rating            = floatval( $attributes['rating'] ?? 0 );
	$sauce_rating      = floatval( $attributes['sauceRating'] ?? 0 );
	$crispiness_rating = floatval( $attributes['crispinessRating'] ?? 0 );
	$review_text       = wp_kses_post( $attributes['reviewText'] ?? '' );
	$timestamp         = esc_html( $attributes['timestamp'] ?? '' );
	$address           = esc_html( $attributes['address'] ?? '' );
	$phone             = esc_html( $attributes['phone'] ?? '' );
	$website           = esc_url( $attributes['website'] ?? '' );
	$hours             = esc_html( $attributes['hours'] ?? '' );
	$price_range       = esc_html( $attributes['priceRange'] ?? '' );
	$takeout           = ! empty( $attributes['takeout'] );
	$delivery          = ! empty( $attributes['delivery'] );
	$dine_in           = ! empty( $attributes['dineIn'] );

	$is_first_block = is_first_review_block();

	$full_stars  = str_repeat( '★', (int) round( $rating ) );
	$empty_stars = str_repeat( '☆', 5 - (int) round( $rating ) );

	ob_start();
	?>
	<div class="wing-review">
		<div class="wing-review-header">
			<span class="wing-review-rating"><?php echo esc_html( $full_stars . $empty_stars ); ?></span>
			<span class="wing-review-meta">
				<?php if ( $reviewer_name ) : ?>
					<span class="wing-reviewer-name"><?php echo $reviewer_name; ?></span>
				<?php endif; ?>
				<?php if ( $timestamp ) : ?>
					<span class="wing-review-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $timestamp ) ) ); ?></span>
				<?php endif; ?>
			</span>
		</div>

		<?php if ( $sauce_rating > 0 || $crispiness_rating > 0 ) : ?>
			<div class="wing-review-sub-ratings">
				<?php if ( $sauce_rating > 0 ) : ?>
					<span class="wing-sub-rating">
						<strong><?php esc_html_e( 'Sauce:', 'wing-review' ); ?></strong>
						<?php echo esc_html( str_repeat( '★', (int) round( $sauce_rating ) ) . str_repeat( '☆', 5 - (int) round( $sauce_rating ) ) ); ?>
					</span>
				<?php endif; ?>
				<?php if ( $crispiness_rating > 0 ) : ?>
					<span class="wing-sub-rating">
						<strong><?php esc_html_e( 'Crispiness:', 'wing-review' ); ?></strong>
						<?php echo esc_html( str_repeat( '★', (int) round( $crispiness_rating ) ) . str_repeat( '☆', 5 - (int) round( $crispiness_rating ) ) ); ?>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $review_text ) : ?>
			<div class="wing-review-text"><?php echo $review_text; ?></div>
		<?php endif; ?>

		<?php if ( $is_first_block && ( $address || $phone || $website || $hours || $price_range ) ) : ?>
			<div class="wing-location-details">
				<?php if ( $address ) : ?>
					<div class="wing-location-address">
						<strong><?php esc_html_e( 'Address:', 'wing-review' ); ?></strong> <?php echo $address; ?>
					</div>
				<?php endif; ?>
				<?php if ( $phone ) : ?>
					<div class="wing-location-phone">
						<strong><?php esc_html_e( 'Phone:', 'wing-review' ); ?></strong>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo $phone; ?></a>
					</div>
				<?php endif; ?>
				<?php if ( $website ) : ?>
					<div class="wing-location-website">
						<strong><?php esc_html_e( 'Website:', 'wing-review' ); ?></strong>
						<a href="<?php echo $website; ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( parse_url( $website, PHP_URL_HOST ) ); ?></a>
					</div>
				<?php endif; ?>
				<?php if ( $hours ) : ?>
					<div class="wing-location-hours">
						<strong><?php esc_html_e( 'Hours:', 'wing-review' ); ?></strong>
						<span><?php echo nl2br( $hours ); ?></span>
					</div>
				<?php endif; ?>
				<?php if ( $price_range ) : ?>
					<div class="wing-location-price">
						<strong><?php esc_html_e( 'Price:', 'wing-review' ); ?></strong> <?php echo $price_range; ?>
					</div>
				<?php endif; ?>
				<?php if ( $takeout || $delivery || $dine_in ) : ?>
					<div class="wing-location-services">
						<strong><?php esc_html_e( 'Services:', 'wing-review' ); ?></strong>
						<?php
						$services = array();
						if ( $takeout ) {
							$services[] = __( 'Takeout', 'wing-review' );
						}
						if ( $delivery ) {
							$services[] = __( 'Delivery', 'wing-review' );
						}
						if ( $dine_in ) {
							$services[] = __( 'Dine-in', 'wing-review' );
						}
						echo esc_html( implode( ', ', $services ) );
						?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Check if current block is the first wing-review block in the post
 */
function is_first_review_block() {
	static $is_first = true;

	if ( $is_first ) {
		$is_first = false;
		return true;
	}

	return false;
}

/**
 * Convert approved comment to wing-review block
 *
 * Triggered when a comment status changes to 'approve'.
 * Reads comment metadata, builds wing-review block, appends to post content,
 * then deletes the original comment.
 */
function convert_to_block( $comment_id, $status ) {
	if ( 'approve' !== $status ) {
		return;
	}

	$comment = get_comment( $comment_id );
	if ( ! $comment || 'wing_location' !== get_post_type( $comment->comment_post_ID ) ) {
		return;
	}

	$rating            = get_comment_meta( $comment_id, 'wing_rating', true );
	$sauce_rating      = get_comment_meta( $comment_id, 'wing_sauce_rating', true );
	$crispiness_rating = get_comment_meta( $comment_id, 'wing_crispiness_rating', true );

	$address   = get_comment_meta( $comment_id, 'wing_address', true );
	$latitude  = get_comment_meta( $comment_id, 'wing_latitude', true );
	$longitude = get_comment_meta( $comment_id, 'wing_longitude', true );
	$phone     = get_comment_meta( $comment_id, 'wing_phone', true );
	$website   = get_comment_meta( $comment_id, 'wing_website', true );
	$hours     = get_comment_meta( $comment_id, 'wing_hours', true );
	$price     = get_comment_meta( $comment_id, 'wing_price_range', true );
	$takeout   = get_comment_meta( $comment_id, 'wing_takeout', true );
	$delivery  = get_comment_meta( $comment_id, 'wing_delivery', true );
	$dine_in   = get_comment_meta( $comment_id, 'wing_dine_in', true );

	$block_content = serialize_block(
		array(
			'blockName' => 'wing-map/wing-review',
			'attrs'     => array(
				'reviewerName'     => $comment->comment_author,
				'reviewerEmail'    => $comment->comment_author_email,
				'rating'           => floatval( $rating ),
				'sauceRating'      => floatval( $sauce_rating ),
				'crispinessRating' => floatval( $crispiness_rating ),
				'reviewText'       => $comment->comment_content,
				'timestamp'        => $comment->comment_date,
				'address'          => $address,
				'latitude'         => floatval( $latitude ),
				'longitude'        => floatval( $longitude ),
				'phone'            => $phone,
				'website'          => $website,
				'hours'            => $hours,
				'priceRange'       => $price,
				'takeout'          => (bool) $takeout,
				'delivery'         => (bool) $delivery,
				'dineIn'           => (bool) $dine_in,
			),
		)
	);

	$post = get_post( $comment->comment_post_ID );
	$new_content = $post->post_content . "\n\n" . $block_content;

	wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_content' => $new_content,
		)
	);

	wp_delete_comment( $comment_id, true );
}
