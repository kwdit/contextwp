<?php
namespace ContextWP\Endpoints;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * MCP Manifest Endpoint
 * 
 * Returns metadata about this context provider for AI agents.
 * 
 * @package ContextWP
 * @since 1.0.0
 */
class Manifest {

    /**
     * Register the REST API route
     * 
     * @since 1.0.0
     */
    public function register_route() {
        register_rest_route( 'mcp/v1', '/manifest', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_request' ],
            'permission_callback' => [ $this, 'check_permissions' ],
            'args'                => $this->get_args(),
        ] );
    }

    /**
     * Check if the request is allowed
     * 
     * @since 1.0.0
     * @return bool|\WP_Error
     */
    public function check_permissions() {
        // Allow public access but with rate limiting
        if ( $this->is_rate_limited() ) {
            return new \WP_Error(
                'rate_limit_exceeded',
                __( 'Too many requests. Please try again later.', 'contextwp' ),
                [ 'status' => 429 ]
            );
        }
        
        return true;
    }

    /**
     * Get the arguments for the endpoint
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_args() {
        return [
            'format' => [
                'default'           => 'json',
                'sanitize_callback' => 'sanitize_text_field',
                'enum'              => [ 'json', 'yaml' ],
            ],
        ];
    }

    /**
     * Handle the REST API request
     * 
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_request( $request ) {
        try {
            // Check for cached response
            $cache_key = \ContextWP\Helpers\Utilities::get_cache_key( 'contextwp_manifest', $request->get_params() );
            $cached    = wp_cache_get( $cache_key, 'contextwp' );
            
            if ( $cached !== false ) {
                return rest_ensure_response( $cached );
            }

            $manifest = $this->generate_manifest( $request );
            
            // Cache the response for 1 hour
            wp_cache_set( $cache_key, $manifest, 'contextwp', HOUR_IN_SECONDS );
            
            return rest_ensure_response( $manifest );
            
        } catch ( \Exception $e ) {
            \ContextWP\Helpers\Utilities::log_debug( $e->getMessage(), 'manifest_error' );
            return new \WP_Error(
                'manifest_generation_failed',
                __( 'Failed to generate manifest.', 'contextwp' ),
                [ 'status' => 500 ]
            );
        }
    }

    /**
     * Generate the manifest data
     * 
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object
     * @return array
     */
    private function generate_manifest( $request ) {
        $site_name = get_bloginfo( 'name' );
        $site_description = get_bloginfo( 'description' );
        
        // Ensure we have valid data
        if ( empty( $site_name ) ) {
            $site_name = __( 'WordPress Site', 'contextwp' );
        }
        
        if ( empty( $site_description ) ) {
            $site_description = __( 'A WordPress site with ContextWP integration', 'contextwp' );
        }

        $manifest = apply_filters( 'contextwp_manifest', [
            'name'        => $site_name . ' – ContextWP',
            'description' => $site_description,
            'version'     => defined( 'CONTEXTWP_VERSION' ) ? CONTEXTWP_VERSION : '1.0.0',
            'endpoints'   => $this->get_endpoints(),
            'formats'     => [ 'markdown', 'plain', 'html' ],
            'context_types' => apply_filters( 'contextwp_supported_post_types', [ 'post', 'page' ] ),
            'branding'    => $this->get_branding(),
            'capabilities' => $this->get_capabilities(),
            'rate_limits' => $this->get_rate_limits(),
        ] );

        return $manifest;
    }

    /**
     * Get the available endpoints
     * 
     * @since 1.0.0
     * @return array
     */
    private function get_endpoints() {
        return [
            'list_contexts' => [
                'url'    => rest_url( 'mcp/v1/list_contexts' ),
                'method' => 'GET',
                'description' => __( 'List available contexts', 'contextwp' ),
            ],
            'get_context' => [
                'url'    => rest_url( 'mcp/v1/get_context' ),
                'method' => 'GET',
                'description' => __( 'Get specific context content', 'contextwp' ),
            ],
        ];
    }

    /**
     * Get branding information
     * 
     * @since 1.0.0
     * @return array
     */
    private function get_branding() {
        $plugin_url = defined( 'CONTEXTWP_URL' ) ? CONTEXTWP_URL : '';
        $logo_url   = $plugin_url ? $plugin_url . 'admin/assets/logo.png' : '';
        
        return [
            'plugin_url' => apply_filters( 'contextwp_plugin_url', $plugin_url ),
            'logo_url'   => apply_filters( 'contextwp_logo_url', $logo_url ),
            'author'     => apply_filters( 'contextwp_author', __( 'ContextWP Team', 'contextwp' ) ),
        ];
    }

    /**
     * Get capability information
     * 
     * @since 1.0.0
     * @return array
     */
    private function get_capabilities() {
        return [
            'public_access' => true,
            'authentication_required' => false,
            'rate_limited' => true,
            'caching_enabled' => true,
        ];
    }

    /**
     * Get rate limit information
     * 
     * @since 1.0.0
     * @return array
     */
    private function get_rate_limits() {
        return [
            'requests_per_minute' => apply_filters( 'contextwp_rate_limit_per_minute', 60 ),
            'requests_per_hour'   => apply_filters( 'contextwp_rate_limit_per_hour', 1000 ),
        ];
    }

    /**
     * Check if the request is rate limited
     * 
     * @since 1.0.0
     * @return bool
     */
    private function is_rate_limited() {
        $ip = \ContextWP\Helpers\Utilities::get_client_ip();
        $key = 'contextwp_rate_limit_' . md5( $ip );
        $limit_per_minute = apply_filters( 'contextwp_rate_limit_per_minute', 60 );
        
        return \ContextWP\Helpers\Utilities::is_rate_limited( $key, $limit_per_minute, 60 );
    }
}
