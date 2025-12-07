<?php
/**
 * Plugin Name: Wing Map Display
 * Plugin URI: https://chubes.net
 * Description: Interactive Leaflet map block displaying all wing locations with reviews
 * Version: 0.1.1
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wing-map-display
 */

namespace WingMapDisplay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WING_MAP_DISPLAY_VERSION', '0.1.0' );
define( 'WING_MAP_DISPLAY_PATH', plugin_dir_path( __FILE__ ) );
define( 'WING_MAP_DISPLAY_URL', plugin_dir_url( __FILE__ ) );

function get_meta_helper() {
	if ( ! class_exists( '\\CluckinChuck\\Wing_Location_Meta' ) ) {
		return null;
	}
	return '\\CluckinChuck\\Wing_Location_Meta';
}

function register_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type(
		WING_MAP_DISPLAY_PATH . 'build/map-display',
		array(
			'render_callback' => __NAMESPACE__ . '\\render_callback',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block' );

/**
 * Enqueue Leaflet assets and pass wing location data to JavaScript
 */
function render_callback( $attributes, $content ) {
	enqueue_assets();

	$locations = get_wing_locations();

	$script_handle = 'wing-map-display-wing-map-display-view-script';

	wp_add_inline_script(
		$script_handle,
		'window.wingMapData = ' . wp_json_encode( array(
			'locations' => $locations,
		) ),
		'before'
	);

	return '<div id="wing-map" class="wing-location-map"></div>';
}

function enqueue_assets() {
	wp_enqueue_style(
		'leaflet',
		'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
		array(),
		'1.9.4'
	);

	wp_enqueue_script(
		'leaflet',
		'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
		array(),
		'1.9.4',
		true
	);

	wp_script_add_data( 'wing-map-display-wing-map-display-view-script', 'dependencies', array( 'leaflet' ) );
}

function get_wing_locations() {
	$meta_helper = get_meta_helper();

	$query = new \WP_Query(
		array(
			'post_type'      => 'wing_location',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		)
	);

	$locations = array();

	foreach ( $query->posts as $post ) {
		if ( ! $meta_helper ) {
			continue;
		}

		$meta         = $meta_helper::get_location_meta( $post->ID );
		$lat          = floatval( $meta['wing_latitude'] );
		$lng          = floatval( $meta['wing_longitude'] );

		if ( empty( $lat ) || empty( $lng ) ) {
			continue;
		}

		$address      = $meta['wing_address'];
		$avg_rating   = floatval( $meta['wing_average_rating'] );
		$review_count = intval( $meta['wing_review_count'] );

		$locations[] = array(
			'id'          => $post->ID,
			'title'       => get_the_title( $post ),
			'lat'         => $lat,
			'lng'         => $lng,
			'address'     => $address,
			'rating'      => round( $avg_rating ),
			'reviewCount' => $review_count,
			'url'         => get_permalink( $post ),
		);
	}

	return $locations;
}
