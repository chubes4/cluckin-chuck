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
