<?php
namespace ContextWP\Endpoints;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Get_Context {

    public function register_route() {
        register_rest_route( 'mcp/v1', '/get_context', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_request' ],
            'permission_callback' => [ $this, 'check_permissions' ],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => [ $this, 'validate_id' ],
                ],
                'format' => [
                    'default'           => 'markdown',
                    'sanitize_callback' => 'sanitize_text_field',
                    'enum'              => [ 'markdown', 'plain', 'html' ],
                ],
            ],
        ]);
    }

    public function check_permissions( $request ) {
        if ( is_user_logged_in() ) {
            return true;
        }

        $id = $request->get_param( 'id' );
        if ( $this->is_public_content( $id ) ) {
            return true;
        }

        return new \WP_Error(
            'rest_forbidden',
            __( 'Sorry, you are not allowed to access this content.', 'contextwp' ),
            [ 'status' => 403 ]
        );
    }

    public function validate_id( $id ) {
        if ( empty( $id ) ) {
            return new \WP_Error( 'invalid_id', 'ID cannot be empty' );
        }

        $supported_prefixes = apply_filters( 'contextwp_supported_id_prefixes', [ 'post', 'page' ] );
        foreach ( $supported_prefixes as $prefix ) {
            if ( strpos( $id, $prefix . '-' ) === 0 ) {
                return true;
            }
        }

        return new \WP_Error(
            'invalid_id_format',
            sprintf(
                'Invalid ID format. Supported: %s',
                implode( ', ', array_map( fn( $p ) => $p . '-{id}', $supported_prefixes ) )
            )
        );
    }

    private function is_public_content( $id ) {
        $parsed = $this->parse_id( $id );
        $post   = $parsed ? get_post( $parsed['id'] ) : null;
        return $post && $post->post_status === 'publish';
    }

    private function parse_id( $id ) {
        $parts = explode( '-', $id, 2 );
        if ( count( $parts ) !== 2 || ! is_numeric( $parts[1] ) ) {
            return false;
        }

        return [
            'type' => $parts[0],
            'id'   => (int) $parts[1],
        ];
    }

    public function handle_request( $request ) {
        $id     = $request->get_param( 'id' );
        $format = $request->get_param( 'format' );
    
        // Early validation - check if ID is empty
        if ( empty( $id ) ) {
            return new \WP_Error( 'invalid_id', 'ID parameter is required', [ 'status' => 400 ] );
        }
    
        $parsed = $this->parse_id( $id );
        if ( ! $parsed ) {
            return new \WP_Error( 'invalid_id_format', 'Invalid ID format', [ 'status' => 400 ] );
        }
    
        // Use get_post with specific post type for better performance
        $post = get_post( $parsed['id'] );
        if ( ! $post ) {
            return new \WP_Error( 'not_found', 'Context not found', [ 'status' => 404 ] );
        }
    
        // Verify post type matches the parsed type
        if ( $post->post_type !== $parsed['type'] ) {
            return new \WP_Error( 'invalid_post_type', 'Post type mismatch', [ 'status' => 400 ] );
        }
    
        if ( ! $this->can_access_post( $post ) ) {
            return new \WP_Error( 'rest_forbidden', 'Access denied', [ 'status' => 403 ] );
        }
    
        // Enhanced cache key with post type and user context
        $user_context = is_user_logged_in() ? get_current_user_id() : 'guest';
        $cache_key = sprintf( 'contextwp_%s_%s_%s_%s', 
            md5( $id . $format ), 
            $post->post_type, 
            $user_context,
            $post->post_modified_gmt
        );
        
        $cached = wp_cache_get( $cache_key, 'contextwp' );
        if ( $cached !== false ) {
            return rest_ensure_response( $cached );
        }
    
        $content = $this->format_content( $post, $format );
    
        // 🔍 Pull ACF fields if available with error handling
        $acf_fields = [];
        if ( function_exists( 'get_fields' ) ) {
            try {
                $acf_fields = get_fields( $post->ID ) ?: [];
            } catch ( \Exception $e ) {
                // Log error but don't fail the request
                error_log( 'ContextWP: ACF fields error for post ' . $post->ID . ': ' . $e->getMessage() );
            }
        }
    
        $response = [
            'id'      => $id,
            'content' => $content,
            'meta'    => [
                'title'     => get_the_title( $post ),
                'type'      => $post->post_type,
                'status'    => $post->post_status,
                'modified'  => $post->post_modified,
                'modified_gmt' => $post->post_modified_gmt,
                'format'    => $format,
                'acf'       => $acf_fields ?: new \stdClass(), // empty object if no fields
                'cache_key' => $cache_key, // for debugging
            ],
        ];
    
        // Cache with shorter TTL for better freshness
        $cache_ttl = apply_filters( 'contextwp_cache_ttl', HOUR_IN_SECONDS, $post, $format );
        wp_cache_set( $cache_key, $response, 'contextwp', $cache_ttl );
        
        return rest_ensure_response( $response );
    }    

    private function can_access_post( $post ) {
        if ( $post->post_status === 'publish' ) {
            return true;
        }

        if ( ! is_user_logged_in() ) {
            return false;
        }

        $post_type_obj = get_post_type_object( $post->post_type );
        return $post_type_obj && current_user_can( $post_type_obj->cap->read_post, $post->ID );
    }

    private function format_content( $post, $format ) {
        $title   = get_the_title( $post );
        $content = apply_filters( 'contextwp_content_before_format', $post->post_content, $post, $format );

        switch ( $format ) {
            case 'html':
                $output = sprintf(
                    '<h2>%s</h2><div>%s</div>',
                    esc_html( $title ),
                    wp_kses_post( $content )
                );
                break;

            case 'plain':
                $output = sprintf(
                    "%s\n\n%s",
                    $title,
                    wp_strip_all_tags( $content )
                );
                break;

            case 'markdown':
            default:
                $output = sprintf(
                    "## %s\n\n%s\n",
                    $title,
                    wp_strip_all_tags( $content )
                );
                break;
        }

        return apply_filters( 'contextwp_formatted_content', $output, $post, $format );
    }
}
