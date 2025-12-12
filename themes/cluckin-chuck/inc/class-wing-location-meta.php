<?php
/**
 * Registers canonical wing_location metadata and provides helper accessors
 *
 * @package CluckinChuck
 */

namespace CluckinChuck;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wing_Location_Meta {
	/**
	 * Register all wing_location meta fields with REST visibility
	 */
	public static function register() {
		foreach ( self::get_fields() as $meta_key => $settings ) {
			register_post_meta(
				'wing_location',
				$meta_key,
				array(
					'type'              => $settings['type'],
					'single'            => true,
					'show_in_rest'      => array(
						'schema' => array(
							'type'    => $settings['rest_type'],
							'default' => $settings['default'],
						),
					),
					'sanitize_callback' => $settings['sanitize'],
					'auth_callback'     => array( __CLASS__, 'auth_callback' ),
				)
			);
		}
	}

	/**
	 * Provide sane defaults for each meta field
	 */
	public static function get_defaults() {
		return array_map(
			static function( $field ) {
				return $field['default'];
			},
			self::get_fields()
		);
	}

	/**
	 * Retrieve canonical metadata for a wing_location post
	 */
	public static function get_location_meta( $post_id ) {
		$post_id  = absint( $post_id );
		$defaults = self::get_defaults();
		$data     = array();

		foreach ( self::get_fields() as $meta_key => $settings ) {
			$raw = get_post_meta( $post_id, $meta_key, true );
			if ( '' === $raw && '' !== $defaults[ $meta_key ] ) {
				$data[ $meta_key ] = $defaults[ $meta_key ];
				continue;
			}

			if ( '' === $raw && '' === $defaults[ $meta_key ] ) {
				$data[ $meta_key ] = $defaults[ $meta_key ];
				continue;
			}

			$data[ $meta_key ] = call_user_func( $settings['sanitize'], $raw );
		}

		return array_merge( $defaults, $data );
	}

	/**
	 * Persist canonical metadata for a wing_location post
	 */
	public static function update_location_meta( $post_id, array $input ) {
		$post_id = absint( $post_id );

		foreach ( self::get_fields() as $meta_key => $settings ) {
			if ( array_key_exists( $meta_key, $input ) ) {
				$value = call_user_func( $settings['sanitize'], $input[ $meta_key ] );
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}

	/**
	 * Ensure only editors of the post can mutate meta
	 */
	public static function auth_callback( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	}

	private static function get_fields() {
		return array(
			'wing_address'        => self::field( 'string', '', array( __CLASS__, 'sanitize_text' ) ),
			'wing_latitude'       => self::field( 'number', 0, array( __CLASS__, 'sanitize_latitude' ) ),
			'wing_longitude'      => self::field( 'number', 0, array( __CLASS__, 'sanitize_longitude' ) ),
			'wing_website'        => self::field( 'string', '', array( __CLASS__, 'sanitize_url' ) ),
			'wing_instagram'      => self::field( 'string', '', array( __CLASS__, 'sanitize_url' ) ),
			'wing_average_rating' => self::field( 'number', 0, array( __CLASS__, 'sanitize_rating' ) ),
			'wing_review_count'   => self::field( 'integer', 0, array( __CLASS__, 'sanitize_review_count' ) ),
			'wing_average_ppw'    => self::field( 'number', 0, array( __CLASS__, 'sanitize_ppw' ) ),
			'wing_min_ppw'        => self::field( 'number', 0, array( __CLASS__, 'sanitize_ppw' ) ),
			'wing_max_ppw'        => self::field( 'number', 0, array( __CLASS__, 'sanitize_ppw' ) ),
		);
	}

	private static function field( $type, $default, $sanitize_callback ) {
		return array(
			'type'      => $type,
			'rest_type' => 'boolean' === $type ? 'boolean' : ( 'integer' === $type ? 'integer' : ( 'number' === $type ? 'number' : 'string' ) ),
			'default'   => $default,
			'sanitize'  => $sanitize_callback,
		);
	}

	private static function sanitize_text( $value ) {
		return sanitize_text_field( (string) $value );
	}

	private static function sanitize_url( $value ) {
		return esc_url_raw( (string) $value );
	}

	private static function sanitize_latitude( $value ) {
		$lat = floatval( $value );
		if ( $lat > 90 ) {
			$lat = 90;
		}
		if ( $lat < -90 ) {
			$lat = -90;
		}
		return $lat;
	}

	private static function sanitize_longitude( $value ) {
		$lng = floatval( $value );
		if ( $lng > 180 ) {
			$lng = 180;
		}
		if ( $lng < -180 ) {
			$lng = -180;
		}
		return $lng;
	}

	private static function sanitize_rating( $value ) {
		$rating = floatval( $value );
		if ( $rating < 0 ) {
			return 0;
		}
		if ( $rating > 5 ) {
			return 5;
		}
		return round( $rating, 2 );
	}

	private static function sanitize_review_count( $value ) {
		$count = intval( $value );
		return max( 0, $count );
	}

	private static function sanitize_ppw( $value ) {
		$ppw = floatval( $value );
		return max( 0, round( $ppw, 2 ) );
	}
}
