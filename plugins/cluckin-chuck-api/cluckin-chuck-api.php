<?php
/**
 * Plugin Name: Cluckin' Chuck API
 * Plugin URI: https://cluckinchuck.saraichinwag.com
 * Description: Central REST API for Cluckin' Chuck. Unified endpoint namespace wrapping abilities from wing plugins.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cluckin-chuck-api
 *
 * @package CluckinChuck\API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CLUCKIN_CHUCK_API_VERSION', '0.1.0' );

if ( ! defined( 'CLUCKIN_CHUCK_API_PATH' ) ) {
	define( 'CLUCKIN_CHUCK_API_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'CLUCKIN_CHUCK_API_URL' ) ) {
	define( 'CLUCKIN_CHUCK_API_URL', plugin_dir_url( __FILE__ ) );
}

final class CluckinChuck_API_Plugin {

	private static $instance = null;

	private function __construct() {
		$this->load_route_files();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Auto-discover and load all route files from inc/routes/.
	 */
	private function load_route_files() {
		$routes_dir = CLUCKIN_CHUCK_API_PATH . 'inc/routes/';

		if ( ! is_dir( $routes_dir ) ) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $routes_dir, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( 'php' !== $file->getExtension() ) {
				continue;
			}
			require_once $file->getRealPath();
		}
	}

	/**
	 * Fire the route registration action.
	 */
	public function register_routes() {
		do_action( 'cluckin_chuck_api_register_routes' );
	}
}

CluckinChuck_API_Plugin::get_instance();
