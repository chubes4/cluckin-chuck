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
 * Tries the address as-given first. If that fails, strips common
 * suite/unit/apartment fragments (which Nominatim doesn't index) and
 * retries once. Successful results cached for 24 hours; failures are
 * NOT cached so a corrected address can be re-attempted immediately.
 *
 * @param string $address Address to geocode.
 * @return array|false Array with 'lat' and 'lng' keys, or false on failure.
 */
function geocode_address( $address ) {
	$address = trim( (string) $address );

	if ( '' === $address ) {
		return false;
	}

	$result = geocode_address_request( $address );

	if ( false !== $result ) {
		return $result;
	}

	$cleaned = strip_address_subunit( $address );

	if ( $cleaned !== $address && '' !== $cleaned ) {
		$result = geocode_address_request( $cleaned );

		if ( false !== $result ) {
			// Cache under the original key too so future identical lookups skip the retry.
			set_transient( 'geocode_' . md5( $address ), $result, DAY_IN_SECONDS );
			return $result;
		}
	}

	return false;
}

/**
 * Perform a single Nominatim request with transient caching.
 *
 * @param string $address Address to geocode.
 * @return array|false
 */
function geocode_address_request( $address ) {
	$transient_key = 'geocode_' . md5( $address );
	$cached        = get_transient( $transient_key );

	if ( false !== $cached ) {
		return $cached;
	}

	$url = add_query_arg(
		array(
			'q'            => $address,
			'format'       => 'json',
			'limit'        => 1,
			'countrycodes' => 'us',
			'addressdetails' => 0,
		),
		'https://nominatim.openstreetmap.org/search'
	);

	$response = wp_remote_get(
		$url,
		array(
			'headers' => array(
				'User-Agent'      => 'CluckinChuck/0.1.0 (https://chubes.net)',
				'Accept-Language' => 'en',
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

/**
 * Strip suite/unit/apartment fragments from a US address.
 *
 * Nominatim's address indexing rarely includes sub-units, so phrases like
 * "Suite 301", "Ste. 4B", "Unit 12", "Apt 5", or "#200" cause empty results.
 * Removes the fragment while preserving the surrounding street/city/zip.
 *
 * @param string $address Original address.
 * @return string Cleaned address (may equal input if nothing was stripped).
 */
function strip_address_subunit( $address ) {
	$patterns = array(
		// "Suite 301", "Ste. 4B", "Ste 200" — including a leading comma if present.
		'/,?\s*\b(?:suite|ste\.?)\s*[\w-]+/i',
		// "Unit 12", "Apt 5", "Apartment 3A", "Building 2".
		'/,?\s*\b(?:unit|apt\.?|apartment|bldg\.?|building)\s*[\w-]+/i',
		// "#200" style suffixes.
		'/,?\s*#\s*[\w-]+/',
	);

	$cleaned = preg_replace( $patterns, '', $address );
	$cleaned = preg_replace( '/\s{2,}/', ' ', (string) $cleaned );
	$cleaned = preg_replace( '/\s*,\s*,+/', ',', (string) $cleaned );

	return trim( (string) $cleaned, " \t\n\r\0\x0B," );
}
