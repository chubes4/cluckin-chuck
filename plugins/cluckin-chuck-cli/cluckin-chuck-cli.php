<?php
/**
 * Plugin Name: Cluckin' Chuck CLI
 * Plugin URI: https://cluckinchuck.saraichinwag.com
 * Description: WP-CLI command surface for Cluckin' Chuck. Wraps abilities from wing plugins into a unified `wp cluckinchuck` namespace.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cluckin-chuck-cli
 *
 * @package CluckinChuck\CLI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CLUCKIN_CHUCK_CLI_VERSION', '0.1.0' );
define( 'CLUCKIN_CHUCK_CLI_PATH', plugin_dir_path( __FILE__ ) );

// Only load in WP-CLI context.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

// PSR-4 autoloader for CluckinChuck\CLI namespace.
spl_autoload_register(
	function ( $class_name ) {
		$prefix = 'CluckinChuck\\CLI\\';
		$len    = strlen( $prefix );

		if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
			return;
		}

		$relative = substr( $class_name, $len );
		$file     = CLUCKIN_CHUCK_CLI_PATH . 'inc/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

// Register commands.
require_once CLUCKIN_CHUCK_CLI_PATH . 'inc/bootstrap.php';
