<?php
/**
 * Wing Review CLI Commands.
 *
 * Wraps cluckin-chuck/list-reviews, cluckin-chuck/approve-review,
 * cluckin-chuck/reject-review, cluckin-chuck/recalculate-stats,
 * and cluckin-chuck/list-pending abilities.
 *
 * @package CluckinChuck\CLI\Commands\Reviews
 */

namespace CluckinChuck\CLI\Commands\Reviews;

use WP_CLI;
use WP_CLI\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewCommand {

	/**
	 * List reviews for a wing location.
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
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp cluckinchuck reviews list 42
	 *     wp cluckinchuck reviews list 42 --format=json
	 *
	 * @subcommand list
	 * @when after_wp_load
	 */
	public function list_( $args, $assoc_args ) {
		$this->ensure_abilities_api();

		$ability = wp_get_ability( 'cluckin-chuck/list-reviews' );
		if ( ! $ability ) {
			WP_CLI::error( 'Ability cluckin-chuck/list-reviews is not registered. Ensure wing-review plugin is active.' );
		}

		$result = $ability->execute( array( 'post_id' => intval( $args[0] ) ) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$format = $assoc_args['format'] ?? 'table';

		if ( empty( $result['reviews'] ) ) {
			WP_CLI::log( 'No reviews found for this location.' );
			return;
		}

		WP_CLI::log( sprintf( '%d review(s) found:', $result['count'] ) );

		Utils\format_items(
			$format,
			$result['reviews'],
			array( 'reviewer_name', 'rating', 'sauce_rating', 'crispiness_rating', 'ppw', 'timestamp' )
		);
	}

	/**
	 * Approve a pending wing review.
	 *
	 * Converts the pending comment to a review block, recalculates location
	 * stats, and deletes the original comment.
	 *
	 * ## OPTIONS
	 *
	 * <comment_id>
	 * : The pending review comment ID to approve.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cluckinchuck reviews approve 15
	 *
	 * @when after_wp_load
	 */
	public function approve( $args, $assoc_args ) {
		$this->ensure_abilities_api();

		$ability = wp_get_ability( 'cluckin-chuck/approve-review' );
		if ( ! $ability ) {
			WP_CLI::error( 'Ability cluckin-chuck/approve-review is not registered. Ensure wing-review plugin is active.' );
		}

		$result = $ability->execute( array( 'comment_id' => intval( $args[0] ) ) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( sprintf(
			'Approved review by %s (rating: %s) on location %d. Total reviews: %d.',
			$result['reviewer'],
			$result['rating'],
			$result['post_id'],
			$result['review_count']
		) );
	}

	/**
	 * Reject a pending wing review.
	 *
	 * Trashes the pending review comment.
	 *
	 * ## OPTIONS
	 *
	 * <comment_id>
	 * : The pending review comment ID to reject.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cluckinchuck reviews reject 15
	 *
	 * @when after_wp_load
	 */
	public function reject( $args, $assoc_args ) {
		$this->ensure_abilities_api();

		$ability = wp_get_ability( 'cluckin-chuck/reject-review' );
		if ( ! $ability ) {
			WP_CLI::error( 'Ability cluckin-chuck/reject-review is not registered. Ensure wing-review plugin is active.' );
		}

		$result = $ability->execute( array( 'comment_id' => intval( $args[0] ) ) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( sprintf(
			'Rejected review by %s on location %d (comment %d trashed).',
			$result['reviewer'],
			$result['post_id'],
			$result['comment_id']
		) );
	}

	/**
	 * Recalculate aggregate stats for a wing location.
	 *
	 * Re-parses all review blocks and updates rating, review count, and PPW stats.
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
	 *     wp cluckinchuck reviews recalculate 42
	 *
	 * @subcommand recalculate
	 * @when after_wp_load
	 */
	public function recalculate( $args, $assoc_args ) {
		$this->ensure_abilities_api();

		$ability = wp_get_ability( 'cluckin-chuck/recalculate-stats' );
		if ( ! $ability ) {
			WP_CLI::error( 'Ability cluckin-chuck/recalculate-stats is not registered. Ensure wing-review plugin is active.' );
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

		WP_CLI::success( sprintf(
			'Recalculated stats for location %d: %d reviews, avg rating %s, PPW $%s-$%s (avg $%s).',
			$result['post_id'],
			$result['review_count'],
			$result['average_rating'],
			$result['min_ppw'],
			$result['max_ppw'],
			$result['average_ppw']
		) );
	}

	/**
	 * List pending submissions awaiting moderation.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Filter by type.
	 * ---
	 * default: all
	 * options:
	 *   - all
	 *   - locations
	 *   - reviews
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
	 *     wp cluckinchuck reviews pending
	 *     wp cluckinchuck reviews pending --type=reviews
	 *     wp cluckinchuck reviews pending --format=json
	 *
	 * @when after_wp_load
	 */
	public function pending( $args, $assoc_args ) {
		$this->ensure_abilities_api();

		$ability = wp_get_ability( 'cluckin-chuck/list-pending' );
		if ( ! $ability ) {
			WP_CLI::error( 'Ability cluckin-chuck/list-pending is not registered. Ensure wing-review-submit plugin is active.' );
		}

		$result = $ability->execute( array( 'type' => $assoc_args['type'] ?? 'all' ) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$format = $assoc_args['format'] ?? 'table';
		$type   = $assoc_args['type'] ?? 'all';

		if ( ( 'all' === $type || 'locations' === $type ) && ! empty( $result['pending_locations'] ) ) {
			WP_CLI::log( sprintf( '--- Pending Locations (%d) ---', $result['total_locations'] ) );
			Utils\format_items( $format, $result['pending_locations'], array( 'post_id', 'title', 'date', 'address', 'rating' ) );
		} elseif ( 'all' === $type || 'locations' === $type ) {
			WP_CLI::log( 'No pending locations.' );
		}

		if ( ( 'all' === $type || 'reviews' === $type ) && ! empty( $result['pending_reviews'] ) ) {
			WP_CLI::log( sprintf( '--- Pending Reviews (%d) ---', $result['total_reviews'] ) );
			Utils\format_items( $format, $result['pending_reviews'], array( 'comment_id', 'location_name', 'reviewer', 'rating', 'date' ) );
		} elseif ( 'all' === $type || 'reviews' === $type ) {
			WP_CLI::log( 'No pending reviews.' );
		}
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
