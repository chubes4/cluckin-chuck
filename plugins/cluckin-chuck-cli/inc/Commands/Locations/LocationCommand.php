<?php
/**
 * Wing Location CLI Commands.
 *
 * Wraps cluckin-chuck/get-location, cluckin-chuck/update-location,
 * cluckin-chuck/list-locations, cluckin-chuck/geocode-address,
 * cluckin-chuck/approve-location, and cluckin-chuck/reject-location abilities.
 *
 * @package CluckinChuck\CLI\Commands\Locations
 */

namespace CluckinChuck\CLI\Commands\Locations;

use WP_CLI;
use WP_CLI\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LocationCommand {

	/**
	 * List wing locations.
	 *
	 * ## OPTIONS
	 *
	 * [--status=<status>]
	 * : Post status filter.
	 * ---
	 * default: publish
	 * ---
	 *
	 * [--search=<search>]
	 * : Search locations by title.
	 *
	 * [--per-page=<per_page>]
	 * : Results per page.
	 * ---
	 * default: 20
	 * ---
	 *
	 * [--offset=<offset>]
	 * : Offset for pagination.
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--orderby=<orderby>]
	 * : Field to order by.
	 * ---
	 * default: date
	 * options:
	 *   - date
	 *   - title
	 *   - modified
	 * ---
	 *
	 * [--order=<order>]
	 * : Sort direction.
	 * ---
	 * default: DESC
	 * options:
	 *   - ASC
	 *   - DESC
	 * ---
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp cluckinchuck locations list
	 *     wp cluckinchuck locations list --search="Rodney" --format=json
	 *     wp cluckinchuck locations list --status=pending
	 *
	 * @subcommand list
	 * @when after_wp_load
	 */
	public function list_( $args, $assoc_args ) {
		$this->ensure_abilities_api();

		$ability = wp_get_ability( 'cluckin-chuck/list-locations' );
		if ( ! $ability ) {
			WP_CLI::error( 'Ability cluckin-chuck/list-locations is not registered. Ensure the cluckin-chuck theme is active.' );
		}

		$result = $ability->execute( array(
			'post_status' => $assoc_args['status'] ?? 'publish',
			'search'      => $assoc_args['search'] ?? '',
			'per_page'    => intval( $assoc_args['per-page'] ?? 20 ),
			'offset'      => intval( $assoc_args['offset'] ?? 0 ),
			'orderby'     => $assoc_args['orderby'] ?? 'date',
			'order'       => $assoc_args['order'] ?? 'DESC',
		) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$format = $assoc_args['format'] ?? 'table';

		if ( empty( $result['locations'] ) ) {
			WP_CLI::log( 'No locations found.' );
			return;
		}

		WP_CLI::log( sprintf( 'Found %d location(s) (showing %d):', $result['total'], count( $result['locations'] ) ) );

		Utils\format_items(
			$format,
			$result['locations'],
			array( 'post_id', 'title', 'status', 'address', 'rating', 'review_count', 'avg_ppw' )
		);
	}

	/**
	 * Get details for a single wing location.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The wing_location post ID.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp cluckinchuck locations get 42
	 *     wp cluckinchuck locations get 42 --format=json
	 *
	 * @when after_wp_load
	 */
	public function get( $args, $assoc_args ) {
		$this->ensure_abilities_api();

		$ability = wp_get_ability( 'cluckin-chuck/get-location' );
		if ( ! $ability ) {
			WP_CLI::error( 'Ability cluckin-chuck/get-location is not registered.' );
		}

		$result = $ability->execute( array( 'post_id' => intval( $args[0] ) ) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$format = $assoc_args['format'] ?? 'table';

		if ( 'json' === $format ) {
			WP_CLI::log( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
			return;
		}

		WP_CLI::log( sprintf( '%s (ID: %d)', $result['title'], $result['post_id'] ) );
		WP_CLI::log( sprintf( 'Status: %s', $result['status'] ) );
		WP_CLI::log( sprintf( 'URL: %s', $result['url'] ) );
		WP_CLI::log( '' );

		$meta_rows = array();
		foreach ( $result['meta'] as $key => $value ) {
			$meta_rows[] = array(
				'field' => $key,
				'value' => is_numeric( $value ) ? $value : (string) $value,
			);
		}

		Utils\format_items( 'table', $meta_rows, array( 'field', 'value' ) );
	}

	/**
	 * Update a wing location's metadata.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The wing_location post ID.
	 *
	 * [--address=<address>]
	 * : Street address.
	 *
	 * [--website=<website>]
	 * : Website URL.
	 *
	 * [--instagram=<instagram>]
	 * : Instagram URL.
	 *
	 * [--latitude=<latitude>]
	 * : Latitude coordinate.
	 *
	 * [--longitude=<longitude>]
	 * : Longitude coordinate.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cluckinchuck locations update 42 --address="123 Wing St, Charleston SC"
	 *     wp cluckinchuck locations update 42 --website="https://example.com"
	 *
	 * @when after_wp_load
	 */
	public function update( $args, $assoc_args ) {
		$this->ensure_abilities_api();

		$ability = wp_get_ability( 'cluckin-chuck/update-location' );
		if ( ! $ability ) {
			WP_CLI::error( 'Ability cluckin-chuck/update-location is not registered.' );
		}

		$meta = array();
		$map  = array(
			'address'   => 'wing_address',
			'website'   => 'wing_website',
			'instagram' => 'wing_instagram',
			'latitude'  => 'wing_latitude',
			'longitude' => 'wing_longitude',
		);

		foreach ( $map as $flag => $meta_key ) {
			if ( isset( $assoc_args[ $flag ] ) ) {
				$meta[ $meta_key ] = $assoc_args[ $flag ];
			}
		}

		if ( empty( $meta ) ) {
			WP_CLI::error( 'Provide at least one field to update (--address, --website, --instagram, --latitude, --longitude).' );
		}

		$result = $ability->execute( array(
			'post_id' => intval( $args[0] ),
			'meta'    => $meta,
		) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( sprintf( 'Updated location %d. Fields: %s', $result['post_id'], implode( ', ', $result['updated'] ) ) );
	}

	/**
	 * Geocode an address to coordinates.
	 *
	 * ## OPTIONS
	 *
	 * <address>
	 * : The street address to geocode.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp cluckinchuck locations geocode "123 King St, Charleston SC"
	 *
	 * @when after_wp_load
	 */
	public function geocode( $args, $assoc_args ) {
		$this->ensure_abilities_api();

		$ability = wp_get_ability( 'cluckin-chuck/geocode-address' );
		if ( ! $ability ) {
			WP_CLI::error( 'Ability cluckin-chuck/geocode-address is not registered.' );
		}

		$result = $ability->execute( array( 'address' => $args[0] ) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$format = $assoc_args['format'] ?? 'table';

		if ( 'json' === $format ) {
			WP_CLI::log( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
			return;
		}

		WP_CLI::success( sprintf( 'Geocoded: lat=%s, lng=%s', $result['lat'], $result['lng'] ) );
	}

	/**
	 * Approve a pending wing location.
	 *
	 * Publishes the pending wing_location post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The pending wing_location post ID to approve.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cluckinchuck locations approve 24
	 *
	 * @when after_wp_load
	 */
	public function approve( $args, $assoc_args ) {
		$this->ensure_abilities_api();

		$ability = wp_get_ability( 'cluckin-chuck/approve-location' );
		if ( ! $ability ) {
			WP_CLI::error( 'Ability cluckin-chuck/approve-location is not registered. Ensure wing-review-submit plugin is active.' );
		}

		$result = $ability->execute( array( 'post_id' => intval( $args[0] ) ) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( sprintf(
			'Published location "%s" (ID: %d). URL: %s',
			$result['title'],
			$result['post_id'],
			$result['url']
		) );
	}

	/**
	 * Reject a pending wing location.
	 *
	 * Trashes the pending wing_location post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The pending wing_location post ID to reject.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cluckinchuck locations reject 24
	 *
	 * @when after_wp_load
	 */
	public function reject( $args, $assoc_args ) {
		$this->ensure_abilities_api();

		$ability = wp_get_ability( 'cluckin-chuck/reject-location' );
		if ( ! $ability ) {
			WP_CLI::error( 'Ability cluckin-chuck/reject-location is not registered. Ensure wing-review-submit plugin is active.' );
		}

		$result = $ability->execute( array( 'post_id' => intval( $args[0] ) ) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( sprintf(
			'Rejected location "%s" (ID: %d, trashed).',
			$result['title'],
			$result['post_id']
		) );
	}

	/**
	 * Ensure the Abilities API is available.
	 */
	private function ensure_abilities_api() {
		if ( ! function_exists( 'wp_get_ability' ) ) {
			WP_CLI::error( 'WordPress Abilities API is not available. Requires WordPress 6.9+.' );
		}
	}
}
