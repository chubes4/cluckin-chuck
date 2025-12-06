<?php
/**
 * Block theme setup, stylesheet enqueue, and wing_location CPT registration
 *
 * @package CluckinChuck
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_theme_file_path( 'inc/class-wing-location.php' );
require_once get_theme_file_path( 'inc/class-wing-location-meta.php' );

function cluckin_chuck_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'custom-line-height' );
	add_theme_support( 'custom-spacing' );
	add_theme_support( 'custom-units' );
}
add_action( 'after_setup_theme', 'cluckin_chuck_setup' );

function cluckin_chuck_enqueue_styles() {
	wp_enqueue_style(
		'cluckin-chuck-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'cluckin_chuck_enqueue_styles' );

function cluckin_chuck_register_cpt() {
	CluckinChuck\Wing_Location::register();
	CluckinChuck\Wing_Location_Meta::register();
}
add_action( 'init', 'cluckin_chuck_register_cpt' );

function cluckin_chuck_register_location_details_block() {
	register_block_type( 'cluckin-chuck/location-details', array(
		'render_callback' => 'cluckin_chuck_render_location_details',
	) );
}
add_action( 'init', 'cluckin_chuck_register_location_details_block' );

function cluckin_chuck_render_location_details() {
	if ( ! is_singular( 'wing_location' ) ) {
		return '';
	}

	$post_id = get_the_ID();
	$meta    = CluckinChuck\Wing_Location_Meta::get_location_meta( $post_id );

	$address        = esc_html( $meta['wing_address'] );
	$phone          = esc_html( $meta['wing_phone'] );
	$website        = esc_url( $meta['wing_website'] );
	$hours          = esc_html( $meta['wing_hours'] );
	$price_range    = esc_html( $meta['wing_price_range'] );
	$average_rating = floatval( $meta['wing_average_rating'] );
	$review_count   = intval( $meta['wing_review_count'] );
	$takeout        = (bool) $meta['wing_takeout'];
	$delivery       = (bool) $meta['wing_delivery'];
	$dine_in        = (bool) $meta['wing_dine_in'];

	if ( empty( $address ) && empty( $phone ) && empty( $website ) ) {
		return '';
	}

	$full_stars  = str_repeat( '★', (int) round( $average_rating ) );
	$empty_stars = str_repeat( '☆', 5 - (int) round( $average_rating ) );

	ob_start();
	?>
	<div class="wing-location-details-block">
		<?php if ( $address ) : ?>
			<p><strong>Address:</strong> <?php echo $address; ?></p>
		<?php endif; ?>

		<?php if ( $average_rating > 0 ) : ?>
			<p><strong>Rating:</strong> <?php echo esc_html( $full_stars . $empty_stars ); ?>
				<?php if ( $review_count > 0 ) : ?>
					<span class="review-count">(<?php echo esc_html( $review_count ); ?> review<?php echo $review_count !== 1 ? 's' : ''; ?>)</span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<?php if ( $phone ) : ?>
			<p><strong>Phone:</strong> <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo $phone; ?></a></p>
		<?php endif; ?>

		<?php if ( $website ) : ?>
			<p><strong>Website:</strong> <a href="<?php echo $website; ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wp_parse_url( $website, PHP_URL_HOST ) ); ?></a></p>
		<?php endif; ?>

		<?php if ( $hours ) : ?>
			<p><strong>Hours:</strong><br><?php echo nl2br( $hours ); ?></p>
		<?php endif; ?>

		<?php if ( $price_range ) : ?>
			<p><strong>Price Range:</strong> <?php echo $price_range; ?></p>
		<?php endif; ?>

		<?php if ( $takeout || $delivery || $dine_in ) : ?>
			<p><strong>Services:</strong>
				<?php
				$services = array();
				if ( $takeout ) {
					$services[] = 'Takeout';
				}
				if ( $delivery ) {
					$services[] = 'Delivery';
				}
				if ( $dine_in ) {
					$services[] = 'Dine-in';
				}
				echo esc_html( implode( ', ', $services ) );
				?>
			</p>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
