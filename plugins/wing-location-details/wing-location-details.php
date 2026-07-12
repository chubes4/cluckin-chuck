<?php
/**
 * Plugin Name: Wing Location Details
 * Plugin URI: https://chubes.net
 * Description: Hero block displaying wing location details (address, website, Instagram, ratings, PPW)
 * Version: 0.2.0
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

define( 'WING_LOCATION_DETAILS_VERSION', '0.2.0' );
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
	$website        = esc_url( $meta['wing_website'] );
	$instagram      = esc_url( $meta['wing_instagram'] );
	$average_rating = floatval( $meta['wing_average_rating'] );
	$review_count   = intval( $meta['wing_review_count'] );
	$min_ppw        = floatval( $meta['wing_min_ppw'] );
	$max_ppw        = floatval( $meta['wing_max_ppw'] );
	$latitude       = floatval( $meta['wing_latitude'] );
	$longitude      = floatval( $meta['wing_longitude'] );
	$display_mode   = 'compact' === ( $attributes['displayMode'] ?? 'full' ) ? 'compact' : 'full';
	$destination    = ( 0.0 !== $latitude || 0.0 !== $longitude ) ? $latitude . ',' . $longitude : $address;
	$directions_url = $destination ? add_query_arg(
		array(
			'api'         => '1',
			'destination' => $destination,
		),
		'https://www.google.com/maps/dir/'
	) : '';
	$review_blocks   = array_filter(
		parse_blocks( (string) get_post_field( 'post_content', $post_id ) ),
		static function ( $block ) {
			return 'wing-review/wing-review' === ( $block['blockName'] ?? '' );
		}
	);
	$sauce_values    = array();
	$crispy_values   = array();

	foreach ( $review_blocks as $review_block ) {
		$sauce_rating = floatval( $review_block['attrs']['sauceRating'] ?? 0 );
		$crispy_rating = floatval( $review_block['attrs']['crispinessRating'] ?? 0 );
		if ( $sauce_rating > 0 ) {
			$sauce_values[] = $sauce_rating;
		}
		if ( $crispy_rating > 0 ) {
			$crispy_values[] = $crispy_rating;
		}
	}

	$average_sauce = $sauce_values ? array_sum( $sauce_values ) / count( $sauce_values ) : 0;
	$average_crispy = $crispy_values ? array_sum( $crispy_values ) / count( $crispy_values ) : 0;
	$photo_ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_parent'    => $post_id,
			'post_mime_type' => 'image',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);
	$photo_ids = array_values(
		array_filter(
			$photo_ids,
			static function ( $photo_id ) {
				return 'pending' !== get_post_meta( $photo_id, '_wing_photo_status', true );
			}
		)
	);

	$full_stars  = str_repeat( '★', (int) round( $average_rating ) );
	$empty_stars = str_repeat( '☆', 5 - (int) round( $average_rating ) );

	ob_start();
	?>
	<div class="wing-location-details wing-location-details--<?php echo esc_attr( $display_mode ); ?>">
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

			<div class="wing-ppw">
				<?php echo esc_html( get_ppw_display( $min_ppw, $max_ppw ) ); ?>
			</div>
		</div>

		<?php if ( 'full' === $display_mode ) : ?>
			<div class="wing-score-grid">
				<div class="wing-score"><strong><?php echo esc_html( number_format( $average_rating, 1 ) ); ?></strong><span>Overall</span></div>
				<div class="wing-score"><strong><?php echo $average_sauce ? esc_html( number_format( $average_sauce, 1 ) ) : '—'; ?></strong><span>Sauce</span></div>
				<div class="wing-score"><strong><?php echo $average_crispy ? esc_html( number_format( $average_crispy, 1 ) ) : '—'; ?></strong><span>Crispiness</span></div>
				<div class="wing-score"><strong><?php echo esc_html( $review_count ); ?></strong><span><?php echo esc_html( _n( 'Review', 'Reviews', $review_count, 'wing-location-details' ) ); ?></span></div>
			</div>
		<?php endif; ?>

		<div class="wing-location-details-body">
			<?php if ( $address ) : ?>
				<div class="wing-detail-row wing-address">
					<span class="wing-detail-icon" aria-hidden="true">📍</span>
					<span class="wing-detail-value"><?php echo $address; ?></span>
				</div>
			<?php endif; ?>

			<?php if ( $website ) : ?>
				<div class="wing-detail-row wing-website">
					<span class="wing-detail-icon" aria-hidden="true">🌐</span>
					<a href="<?php echo $website; ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( parse_url( $website, PHP_URL_HOST ) ); ?></a>
				</div>
			<?php endif; ?>

			<?php if ( $instagram ) : ?>
				<div class="wing-detail-row wing-instagram">
					<span class="wing-detail-icon" aria-hidden="true">📸</span>
					<a href="<?php echo $instagram; ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( get_instagram_handle( $instagram ) ); ?></a>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( 'full' === $display_mode ) : ?>
			<div class="wing-location-actions">
				<?php if ( $directions_url ) : ?>
					<a class="wing-location-action wing-location-action--primary" href="<?php echo esc_url( $directions_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get Directions', 'wing-location-details' ); ?> ↗</a>
				<?php endif; ?>
				<a class="wing-location-action" href="#wing-review-submit-section"><?php esc_html_e( 'Review This Spot', 'wing-location-details' ); ?></a>
			</div>
		<?php endif; ?>

		<?php if ( 'full' === $display_mode && $photo_ids ) : ?>
			<section class="wing-community-gallery" aria-labelledby="wing-gallery-title">
				<div class="wing-section-heading">
					<h2 id="wing-gallery-title"><?php esc_html_e( 'Community Photos', 'wing-location-details' ); ?></h2>
					<span><?php echo esc_html( sprintf( _n( '%d photo', '%d photos', count( $photo_ids ), 'wing-location-details' ), count( $photo_ids ) ) ); ?></span>
				</div>
				<div class="wing-gallery-grid">
					<?php foreach ( $photo_ids as $photo_id ) : ?>
						<a href="<?php echo esc_url( wp_get_attachment_image_url( $photo_id, 'large' ) ); ?>" target="_blank" rel="noopener">
							<?php echo wp_get_attachment_image( $photo_id, 'medium_large', false, array( 'loading' => 'lazy' ) ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Get PPW display string (range or single value or "No pricing data yet")
 */
function get_ppw_display( $min_ppw, $max_ppw ) {
	if ( $min_ppw <= 0 && $max_ppw <= 0 ) {
		return __( 'No pricing data yet', 'wing-location-details' );
	}

	if ( $min_ppw === $max_ppw || $min_ppw <= 0 ) {
		return '$' . number_format( $max_ppw, 2 ) . '/wing';
	}

	if ( $max_ppw <= 0 ) {
		return '$' . number_format( $min_ppw, 2 ) . '/wing';
	}

	return '$' . number_format( $min_ppw, 2 ) . ' - $' . number_format( $max_ppw, 2 ) . '/wing';
}

/**
 * Extract Instagram handle from URL
 */
function get_instagram_handle( $url ) {
	$path = parse_url( $url, PHP_URL_PATH );
	$handle = trim( $path, '/' );

	if ( empty( $handle ) ) {
		return 'Instagram';
	}

	return '@' . $handle;
}
