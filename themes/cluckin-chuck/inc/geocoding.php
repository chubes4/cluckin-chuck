<?php
/**
 * Geocoding functionality using OpenStreetMap Nominatim API.
 *
 * @package CluckinChuck
 */

namespace CluckinChuck;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Geocode an address using OpenStreetMap Nominatim API.
 *
 * Server-side only, results cached for 24 hours.
 *
 * @param string $address Address to geocode.
 * @return array|false Array with 'lat' and 'lng' keys, or false on failure.
 */
function geocode_address( $address ) {
	if ( empty( $address ) ) {
		return false;
	}

	$transient_key = 'geocode_' . md5( $address );
	$cached        = get_transient( $transient_key );

	if ( $cached !== false ) {
		return $cached;
	}

	$url = add_query_arg(
		array(
			'q'      => $address,
			'format' => 'json',
			'limit'  => 1,
		),
		'https://nominatim.openstreetmap.org/search'
	);

	$response = wp_remote_get(
		$url,
		array(
			'headers' => array(
				'User-Agent' => 'CluckinChuck/0.1.0 (https://chubes.net)',
			),
			'timeout' => 10,
		)
	);

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data ) || ! is_array( $data ) || ! isset( $data[0]['lat'], $data[0]['lon'] ) ) {
		return false;
	}

	$result = array(
		'lat' => floatval( $data[0]['lat'] ),
		'lng' => floatval( $data[0]['lon'] ),
	);

	set_transient( $transient_key, $result, DAY_IN_SECONDS );

	return $result;
}
