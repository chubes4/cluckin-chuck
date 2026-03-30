<?php
/**
 * Plugin Name: Cluckin' Chuck Agent Kit
 * Plugin URI: https://cluckinchuck.saraichinwag.com
 * Description: Chat tools and agent configuration for the Cluckin' Chuck AI assistant. Bridges abilities to Data Machine's chat system.
 * Version: 0.1.2
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cluckin-chuck-agent-kit
 *
 * @package CluckinChuck\AgentKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CLUCKIN_CHUCK_AGENT_KIT_VERSION', '0.1.2' );
define( 'CLUCKIN_CHUCK_AGENT_KIT_PATH', plugin_dir_path( __FILE__ ) );

// Load tool classes.
require_once CLUCKIN_CHUCK_AGENT_KIT_PATH . 'inc/Tools/LocationTools.php';
require_once CLUCKIN_CHUCK_AGENT_KIT_PATH . 'inc/Tools/ReviewTools.php';
require_once CLUCKIN_CHUCK_AGENT_KIT_PATH . 'inc/Tools/SubmitTools.php';

// Boot tool registration.
add_action( 'plugins_loaded', function () {
	new CluckinChuck\AgentKit\Tools\LocationTools();
	new CluckinChuck\AgentKit\Tools\ReviewTools();
	new CluckinChuck\AgentKit\Tools\SubmitTools();
} );

// Configure the frontend chat widget.
add_filter( 'data_machine_frontend_chat_config', function ( $config ) {
	$config['agent_slug']  = 'cluckinchuck';
	$config['visibility']  = 'team';
	$config['description'] = 'Your AI wing advisor. Ask about locations, submit reviews, or find the best wings near you.';
	$config['enabled']     = true;

	return $config;
} );
