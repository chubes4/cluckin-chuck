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

function cluckin_chuck_register_rest_routes() {
	register_rest_route(
		'cluckin-chuck/v1',
		'/geocode',
		array(
			'methods'             => 'POST',
			'callback'            => 'cluckin_chuck_rest_geocode_handler',
			'permission_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'rest_api_init', 'cluckin_chuck_register_rest_routes' );

function cluckin_chuck_rest_geocode_handler( WP_REST_Request $request ) {
	$address = sanitize_text_field( $request->get_param( 'address' ) ?? '' );

	if ( empty( $address ) ) {
		return new WP_REST_Response( array( 'message' => 'Address is required' ), 400 );
	}

	$result = CluckinChuck\geocode_address( $address );

	if ( $result ) {
		return new WP_REST_Response( $result, 200 );
	}

	return new WP_REST_Response( array( 'message' => 'Could not geocode address' ), 400 );
}

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

	$needs_geocode = '' === $latitude || '' === $longitude || $last_geocoded_address !== $address;

	if ( ! $needs_geocode ) {
		return;
	}

	$result = CluckinChuck\geocode_address( $address );

	if ( ! $result ) {
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
