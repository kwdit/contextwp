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
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    public function handle_request( $request ) {
        $id = $request->get_param( 'id' );

        // Example: expecting IDs like "post-123"
        if ( strpos( $id, 'post-' ) !== 0 ) {
            return new \WP_Error( 'invalid_id_format', 'Invalid ID format', [ 'status' => 400 ] );
        }

        $post_id = intval( substr( $id, 5 ) );
        $post    = get_post( $post_id );

        if ( ! $post || $post->post_status !== 'publish' ) {
            return new \WP_Error( 'not_found', 'Context not found', [ 'status' => 404 ] );
        }

        $content = sprintf(
            "## %s\n\n%s\n",
            get_the_title( $post ),
            wp_strip_all_tags( $post->post_content )
        );

        return rest_ensure_response([
            'id'      => $id,
            'content' => $content,
        ]);
    }
}
