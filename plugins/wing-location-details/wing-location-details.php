<?php
/**
 * Plugin Name: Wing Location Details
 * Plugin URI: https://chubes.net
 * Description: Hero block displaying wing location details (address, phone, hours, services, ratings)
 * Version: 0.1.1
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wing-location-details
 */

namespace WingLocationDetails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WING_LOCATION_DETAILS_VERSION', '0.1.0' );
define( 'WING_LOCATION_DETAILS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WING_LOCATION_DETAILS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Get the theme's meta helper class if available
 */
function get_meta_helper() {
	if ( ! class_exists( '\\CluckinChuck\\Wing_Location_Meta' ) ) {
		return null;
	}
	return '\\CluckinChuck\\Wing_Location_Meta';
}

/**
 * Register the block
 */
function register_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type(
		WING_LOCATION_DETAILS_PATH . 'build/wing-location-details',
		array(
			'render_callback' => __NAMESPACE__ . '\\render_callback',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block' );

/**
 * Render the wing location details hero block
 */
function render_callback( $attributes, $content ) {
	$post_id = get_the_ID();

	if ( ! $post_id || 'wing_location' !== get_post_type( $post_id ) ) {
		return '';
	}

	$meta_helper = get_meta_helper();

	if ( ! $meta_helper ) {
		return '<div class="wing-location-details-error">' . esc_html__( 'Location data unavailable.', 'wing-location-details' ) . '</div>';
	}

	$meta = $meta_helper::get_location_meta( $post_id );

	$address        = esc_html( $meta['wing_address'] );
	$phone          = esc_html( $meta['wing_phone'] );
	$website        = esc_url( $meta['wing_website'] );
	$hours          = esc_html( $meta['wing_hours'] );
	$price_range    = esc_html( $meta['wing_price_range'] );
	$takeout        = (bool) $meta['wing_takeout'];
	$delivery       = (bool) $meta['wing_delivery'];
	$dine_in        = (bool) $meta['wing_dine_in'];
	$average_rating = floatval( $meta['wing_average_rating'] );
	$review_count   = intval( $meta['wing_review_count'] );

	$full_stars  = str_repeat( '★', (int) round( $average_rating ) );
	$empty_stars = str_repeat( '☆', 5 - (int) round( $average_rating ) );

	ob_start();
	?>
	<div class="wing-location-details">
		<div class="wing-location-details-header">
			<?php if ( $average_rating > 0 ) : ?>
				<div class="wing-location-rating">
					<span class="wing-stars"><?php echo esc_html( $full_stars . $empty_stars ); ?></span>
					<span class="wing-rating-value"><?php echo esc_html( number_format( $average_rating, 1 ) ); ?></span>
					<?php if ( $review_count > 0 ) : ?>
						<span class="wing-review-count">
							<?php
							printf(
								esc_html( _n( '(%d review)', '(%d reviews)', $review_count, 'wing-location-details' ) ),
								$review_count
							);
							?>
						</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $price_range ) : ?>
				<span class="wing-price-range"><?php echo $price_range; ?></span>
			<?php endif; ?>
		</div>

		<div class="wing-location-details-body">
			<?php if ( $address ) : ?>
				<div class="wing-detail-row wing-address">
					<span class="wing-detail-icon" aria-hidden="true">📍</span>
					<span class="wing-detail-value"><?php echo $address; ?></span>
				</div>
			<?php endif; ?>

			<?php if ( $phone ) : ?>
				<div class="wing-detail-row wing-phone">
					<span class="wing-detail-icon" aria-hidden="true">📞</span>
					<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo $phone; ?></a>
				</div>
			<?php endif; ?>

			<?php if ( $website ) : ?>
				<div class="wing-detail-row wing-website">
					<span class="wing-detail-icon" aria-hidden="true">🌐</span>
					<a href="<?php echo $website; ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( parse_url( $website, PHP_URL_HOST ) ); ?></a>
				</div>
			<?php endif; ?>

			<?php if ( $hours ) : ?>
				<div class="wing-detail-row wing-hours">
					<span class="wing-detail-icon" aria-hidden="true">🕐</span>
					<span class="wing-detail-value"><?php echo nl2br( $hours ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( $takeout || $delivery || $dine_in ) : ?>
				<div class="wing-detail-row wing-services">
					<span class="wing-detail-icon" aria-hidden="true">🍴</span>
					<span class="wing-detail-value">
						<?php
						$services = array();
						if ( $takeout ) {
							$services[] = __( 'Takeout', 'wing-location-details' );
						}
						if ( $delivery ) {
							$services[] = __( 'Delivery', 'wing-location-details' );
						}
						if ( $dine_in ) {
							$services[] = __( 'Dine-in', 'wing-location-details' );
						}
						echo esc_html( implode( ' · ', $services ) );
						?>
					</span>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
