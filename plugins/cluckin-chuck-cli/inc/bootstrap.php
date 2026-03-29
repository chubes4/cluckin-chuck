<?php
/**
 * Register WP-CLI commands for Cluckin' Chuck.
 *
 * @package CluckinChuck\CLI
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

// Location commands.
WP_CLI::add_command( 'cluckinchuck locations', CluckinChuck\CLI\Commands\Locations\LocationCommand::class );

// Review commands.
WP_CLI::add_command( 'cluckinchuck reviews', CluckinChuck\CLI\Commands\Reviews\ReviewCommand::class );
