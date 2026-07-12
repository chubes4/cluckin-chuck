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
require_once get_theme_file_path( 'inc/geocoding.php' );
require_once get_theme_file_path( 'inc/class-wing-abilities.php' );

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

function cluckin_chuck_register_abilities() {
	new CluckinChuck\Wing_Abilities();
}
add_action( 'init', 'cluckin_chuck_register_abilities' );

/**
 * Render quick links for administrators reviewing wing submissions.
 *
 * The block intentionally renders nothing for visitors without moderation
 * access, allowing it to live safely in public-facing templates.
 *
 * @return string
 */
function cluckin_chuck_render_submission_admin_links() {
	if ( ! current_user_can( 'edit_others_posts' ) && ! current_user_can( 'moderate_comments' ) ) {
		return '';
	}

	$location_counts  = wp_count_posts( 'wing_location' );
	$pending_locations = isset( $location_counts->pending ) ? (int) $location_counts->pending : 0;
	$pending_reviews   = (int) get_comments(
		array(
			'post_type' => 'wing_location',
			'status'    => 'hold',
			'count'     => true,
		)
	);

	$location_label = sprintf(
		/* translators: %d: number of pending wing locations. */
		_n( '%d pending location', '%d pending locations', $pending_locations, 'cluckin-chuck' ),
		$pending_locations
	);
	$review_label = sprintf(
		/* translators: %d: number of pending wing reviews. */
		_n( '%d pending review', '%d pending reviews', $pending_reviews, 'cluckin-chuck' ),
		$pending_reviews
	);

	ob_start();
	?>
	<aside class="wing-admin-review-panel" aria-label="Wing submission moderation">
		<p><strong><?php esc_html_e( 'Chuck needs a ruling', 'cluckin-chuck' ); ?></strong><br><?php esc_html_e( 'Review community submissions without hunting through the dashboard.', 'cluckin-chuck' ); ?></p>
		<div class="wing-admin-review-actions">
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wing_location&post_status=pending' ) ); ?>"><?php echo esc_html( $location_label ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'edit-comments.php?comment_status=moderated&post_type=wing_location' ) ); ?>"><?php echo esc_html( $review_label ); ?></a>
		</div>
	</aside>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render the site copyright with the current year.
 *
 * @return string
 */
function cluckin_chuck_render_copyright() {
	return sprintf(
		'<p class="has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--30)">&copy; %s %s</p>',
		esc_html( wp_date( 'Y' ) ),
		esc_html__( 'Cluckin Chuck. All rights reserved.', 'cluckin-chuck' )
	);
}

/**
 * Render a location image with branded artwork as its fallback.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Existing block content.
 * @param WP_Block $block      Block instance carrying the queried post ID.
 * @return string
 */
function cluckin_chuck_render_location_image( $attributes, $content, $block ) {
	$post_id   = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
	$is_linked = ! isset( $attributes['isLink'] ) || (bool) $attributes['isLink'];
	$class     = 'wing-location-image' . ( ! empty( $attributes['isHero'] ) ? ' is-hero' : '' );

	if ( has_post_thumbnail( $post_id ) ) {
		$image = get_the_post_thumbnail(
			$post_id,
			'large',
			array(
				'class'   => 'wing-location-image__media',
				'loading' => 'lazy',
			)
		);
	} else {
		$image = sprintf(
			'<img class="wing-location-image__media wing-location-image__fallback" src="%s" alt="%s" loading="lazy">',
			esc_url( get_theme_file_uri( 'assets/wing-placeholder.svg' ) ),
			esc_attr( sprintf( __( 'Illustration for %s', 'cluckin-chuck' ), get_the_title( $post_id ) ) )
		);
	}

	if ( $is_linked ) {
		$image = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post_id ) ), $image );
	}

	return sprintf( '<figure class="%s">%s</figure>', esc_attr( $class ), $image );
}

/**
 * Render up to three nearby wing locations.
 *
 * @return string
 */
function cluckin_chuck_render_related_locations() {
	$post_id   = get_the_ID();
	$latitude  = (float) get_post_meta( $post_id, 'wing_latitude', true );
	$longitude = (float) get_post_meta( $post_id, 'wing_longitude', true );
	$locations = get_posts(
		array(
			'post_type'      => 'wing_location',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'post__not_in'   => array( $post_id ),
		)
	);

	if ( ! $locations ) {
		return '';
	}

	if ( $latitude && $longitude ) {
		usort(
			$locations,
			static function ( $first, $second ) use ( $latitude, $longitude ) {
				$distance = static function ( $location ) use ( $latitude, $longitude ) {
					$location_lat = (float) get_post_meta( $location->ID, 'wing_latitude', true );
					$location_lng = (float) get_post_meta( $location->ID, 'wing_longitude', true );

					if ( ! $location_lat || ! $location_lng ) {
						return PHP_FLOAT_MAX;
					}

					$lat_delta = deg2rad( $location_lat - $latitude );
					$lng_delta = deg2rad( $location_lng - $longitude );
					$value     = sin( $lat_delta / 2 ) ** 2 + cos( deg2rad( $latitude ) ) * cos( deg2rad( $location_lat ) ) * sin( $lng_delta / 2 ) ** 2;

					return 3959 * 2 * asin( min( 1, sqrt( $value ) ) );
				};

				return $distance( $first ) <=> $distance( $second );
			}
		);
	}

	$locations = array_slice( $locations, 0, 3 );
	ob_start();
	?>
	<section class="wing-related-locations" aria-labelledby="nearby-wing-spots">
		<h2 id="nearby-wing-spots"><?php esc_html_e( 'More Wing Spots Nearby', 'cluckin-chuck' ); ?></h2>
		<div class="wing-related-locations__grid">
			<?php foreach ( $locations as $location ) : ?>
				<article class="wing-related-location-card">
					<a href="<?php echo esc_url( get_permalink( $location ) ); ?>">
						<?php if ( has_post_thumbnail( $location ) ) : ?>
							<?php echo get_the_post_thumbnail( $location, 'medium_large', array( 'class' => 'wing-related-location-card__image', 'loading' => 'lazy' ) ); ?>
						<?php else : ?>
							<img class="wing-related-location-card__image" src="<?php echo esc_url( get_theme_file_uri( 'assets/wing-placeholder.svg' ) ); ?>" alt="" loading="lazy">
						<?php endif; ?>
						<strong><?php echo esc_html( get_the_title( $location ) ); ?></strong>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php

	return (string) ob_get_clean();
}

/**
 * Register the theme's small dynamic utility blocks.
 */
function cluckin_chuck_register_utility_blocks() {
	register_block_type(
		'cluckin-chuck/copyright',
		array(
			'api_version'     => 3,
			'render_callback' => 'cluckin_chuck_render_copyright',
		)
	);

	register_block_type(
		'cluckin-chuck/submission-admin-links',
		array(
			'api_version'     => 3,
			'render_callback' => 'cluckin_chuck_render_submission_admin_links',
		)
	);

	register_block_type(
		'cluckin-chuck/location-image',
		array(
			'api_version'     => 3,
			'attributes'      => array(
				'isHero' => array( 'type' => 'boolean', 'default' => false ),
				'isLink' => array( 'type' => 'boolean', 'default' => true ),
			),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'cluckin_chuck_render_location_image',
		)
	);

	register_block_type(
		'cluckin-chuck/related-locations',
		array(
			'api_version'     => 3,
			'render_callback' => 'cluckin_chuck_render_related_locations',
		)
	);
}
add_action( 'init', 'cluckin_chuck_register_utility_blocks' );

function cluckin_chuck_enqueue_editor_assets() {
	$asset_file = get_theme_file_path( 'build/location-meta-panel/index.asset.php' );

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'cluckin-chuck-location-meta-panel',
		get_theme_file_uri( 'build/location-meta-panel/index.js' ),
		$asset['dependencies'],
		$asset['version'],
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'cluckin_chuck_enqueue_editor_assets' );

function cluckin_chuck_set_geocode_notice( $type, $message ) {
	$notice = array(
		'type'    => sanitize_text_field( $type ),
		'message' => wp_kses_post( $message ),
	);

	set_transient( 'cluckin_chuck_geocode_notice', $notice, MINUTE_IN_SECONDS * 10 );
}

function cluckin_chuck_clear_geocode_notice() {
	delete_transient( 'cluckin_chuck_geocode_notice' );
}

function cluckin_chuck_render_geocode_notice() {
	if ( ! is_admin() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'wing_location' !== $screen->post_type ) {
		return;
	}

	$notice = get_transient( 'cluckin_chuck_geocode_notice' );

	if ( ! $notice || empty( $notice['message'] ) ) {
		return;
	}

	cluckin_chuck_clear_geocode_notice();

	$type_class = 'notice-info';
	if ( 'error' === $notice['type'] ) {
		$type_class = 'notice-error';
	} elseif ( 'success' === $notice['type'] ) {
		$type_class = 'notice-success';
	}

	printf(
		'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
		esc_attr( $type_class ),
		esc_html( wp_strip_all_tags( $notice['message'] ) )
	);
}
add_action( 'admin_notices', 'cluckin_chuck_render_geocode_notice' );

function cluckin_chuck_sync_coordinates_on_save( $post_id, $post, $update ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( 'wing_location' !== $post->post_type ) {
		return;
	}

	if ( ! function_exists( '\\CluckinChuck\\geocode_address' ) ) {
		return;
	}

	$address = sanitize_text_field( get_post_meta( $post_id, 'wing_address', true ) );

	if ( '' === $address ) {
		return;
	}

	$latitude              = get_post_meta( $post_id, 'wing_latitude', true );
	$longitude             = get_post_meta( $post_id, 'wing_longitude', true );
	$last_geocoded_address = get_post_meta( $post_id, '_wing_geocoded_address', true );

	$has_valid_coordinates = '' !== $latitude && '' !== $longitude;
	$needs_geocode         = ! $has_valid_coordinates || $last_geocoded_address !== $address;

	if ( ! $needs_geocode ) {
		return;
	}

	$result = CluckinChuck\geocode_address( $address );

	if ( ! $result ) {
		// If we already have valid coordinates for this exact address (e.g.
		// they were resolved at submission time via the geocode-address
		// ability), a transient Nominatim failure on re-save is harmless —
		// don't surface a misleading "Geocoding failed" notice. Stamp the
		// address as geocoded so we stop retrying on every subsequent save.
		if ( $has_valid_coordinates && $last_geocoded_address !== $address ) {
			update_post_meta( $post_id, '_wing_geocoded_address', $address );
			return;
		}

		cluckin_chuck_set_geocode_notice( 'error', __( 'Geocoding failed for this address. Please verify and save again.', 'cluckin-chuck' ) );
		return;
	}

	cluckin_chuck_clear_geocode_notice();

	if ( class_exists( '\\CluckinChuck\\Wing_Location_Meta' ) ) {
		\CluckinChuck\Wing_Location_Meta::update_location_meta(
			$post_id,
			array(
				'wing_latitude'  => $result['lat'],
				'wing_longitude' => $result['lng'],
			)
		);
	} else {
		update_post_meta( $post_id, 'wing_latitude', $result['lat'] );
		update_post_meta( $post_id, 'wing_longitude', $result['lng'] );
	}

	update_post_meta( $post_id, '_wing_geocoded_address', $address );
}
add_action( 'save_post_wing_location', 'cluckin_chuck_sync_coordinates_on_save', 10, 3 );
