<?php
namespace ContextWP\Endpoints;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class List_Contexts {

    public function register_route() {
        register_rest_route( 'mcp/v1', '/list_contexts', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_request' ],
            'permission_callback' => '__return_true', // You may restrict this later
        ]);
    }

    public function handle_request( $request ) {
        $contexts = [];

        // Example: Get latest 5 posts of type 'post'
        $posts = get_posts([
            'post_type'      => 'post',
            'posts_per_page' => 5,
            'post_status'    => 'publish',
        ]);

        foreach ( $posts as $post ) {
            $contexts[] = [
                'id'           => 'post-' . $post->ID,
                'title'        => get_the_title( $post ),
                'description'  => wp_trim_words( $post->post_content, 20 ),
                'last_updated' => get_post_modified_time( 'c', true, $post ),
            ];
        }

        return rest_ensure_response( $contexts );
    }
}
