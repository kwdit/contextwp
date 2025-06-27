<?php
/**
 * Plugin Name: ContextWP
 * Description: MCP-compatible plugin for exposing context endpoints to AI agents.
 * Version: 1.0.0
 * Author: KWD IT
 * Author URI: https://kwd-it.co.uk
 * Text Domain: contextwp
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'CONTEXTWP_VERSION', '0.1.0' );
define( 'CONTEXTWP_DIR', plugin_dir_path( __FILE__ ) );
define( 'CONTEXTWP_URL', plugin_dir_url( __FILE__ ) );

// Load plugin
require_once CONTEXTWP_DIR . 'includes/contextwp-init.php';
