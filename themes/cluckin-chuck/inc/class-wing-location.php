<?php
/**
 * Registers wing_location custom post type with slug 'wings'
 *
 * @package CluckinChuck
 */

namespace CluckinChuck;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wing_Location {
	public static function register() {
		$labels = array(
			'name'                  => __( 'Wing Locations', 'cluckin-chuck' ),
			'singular_name'         => __( 'Wing Location', 'cluckin-chuck' ),
			'menu_name'             => __( 'Wing Locations', 'cluckin-chuck' ),
			'add_new'               => __( 'Add New', 'cluckin-chuck' ),
			'add_new_item'          => __( 'Add New Wing Location', 'cluckin-chuck' ),
			'edit_item'             => __( 'Edit Wing Location', 'cluckin-chuck' ),
			'new_item'              => __( 'New Wing Location', 'cluckin-chuck' ),
			'view_item'             => __( 'View Wing Location', 'cluckin-chuck' ),
			'view_items'            => __( 'View Wing Locations', 'cluckin-chuck' ),
			'search_items'          => __( 'Search Wing Locations', 'cluckin-chuck' ),
			'not_found'             => __( 'No wing locations found', 'cluckin-chuck' ),
			'not_found_in_trash'    => __( 'No wing locations found in trash', 'cluckin-chuck' ),
			'all_items'             => __( 'All Wing Locations', 'cluckin-chuck' ),
			'archives'              => __( 'Wing Location Archives', 'cluckin-chuck' ),
			'attributes'            => __( 'Wing Location Attributes', 'cluckin-chuck' ),
			'insert_into_item'      => __( 'Insert into wing location', 'cluckin-chuck' ),
			'uploaded_to_this_item' => __( 'Uploaded to this wing location', 'cluckin-chuck' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => 'wings' ),
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-location',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'comments' ),
			'description'         => __( 'Chicken wing restaurant locations', 'cluckin-chuck' ),
		);

		register_post_type( 'wing_location', $args );
	}
}
