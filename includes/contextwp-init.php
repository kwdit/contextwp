<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load helper classes
require_once CONTEXTWP_DIR . 'includes/helpers/utilities.php';

// Autoload endpoint classes
require_once CONTEXTWP_DIR . 'includes/endpoints/list-contexts.php';
require_once CONTEXTWP_DIR . 'includes/endpoints/get-context.php';
require_once CONTEXTWP_DIR . 'includes/endpoints/manifest.php';

// Register REST API routes
add_action( 'rest_api_init', function () {
    ( new ContextWP\Endpoints\List_Contexts )->register_route();
    ( new ContextWP\Endpoints\Get_Context )->register_route();
    ( new ContextWP\Endpoints\Manifest )->register_route();
});
