<?php
/**
 * Plugin Name: ContextWP – AI Context Provider for WordPress
 * Description: Expose structured post and ACF data via OpenAI's Model Context Protocol (MCP).
 * Version: 0.1.0
 * Author: Your Name
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
