<?php
namespace ContextWP\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Utility functions for ContextWP
 * 
 * @package ContextWP
 * @since 1.0.0
 */
class Utilities {

    /**
     * Get the client IP address
     * 
     * @since 1.0.0
     * @return string
     */
    public static function get_client_ip() {
        $ip_keys = [ 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ];
        
        foreach ( $ip_keys as $key ) {
            if ( array_key_exists( $key, $_SERVER ) === true ) {
                foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                    $ip = trim( $ip );
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Check if rate limit is exceeded for a given key
     * 
     * @since 1.0.0
     * @param string $key The cache key
     * @param int $limit The rate limit
     * @param int $window The time window in seconds
     * @return bool
     */
    public static function is_rate_limited( $key, $limit, $window = 60 ) {
        $requests = wp_cache_get( $key, 'contextwp' ) ?: 0;
        
        if ( $requests >= $limit ) {
            return true;
        }
        
        wp_cache_incr( $key, 1, 'contextwp' );
        wp_cache_set( $key, $requests + 1, 'contextwp', $window );
        
        return false;
    }

    /**
     * Sanitize and validate post ID
     * 
     * @since 1.0.0
     * @param string $id The post ID
     * @return array|false Array with 'type' and 'id' keys, or false if invalid
     */
    public static function parse_post_id( $id ) {
        if ( empty( $id ) ) {
            return false;
        }

        $parts = explode( '-', $id, 2 );
        if ( count( $parts ) !== 2 || ! is_numeric( $parts[1] ) ) {
            return false;
        }

        return [
            'type' => sanitize_text_field( $parts[0] ),
            'id'   => (int) $parts[1],
        ];
    }

    /**
     * Check if a post is publicly accessible
     * 
     * @since 1.0.0
     * @param \WP_Post $post The post object
     * @return bool
     */
    public static function is_public_post( $post ) {
        if ( ! $post || ! is_object( $post ) ) {
            return false;
        }

        return $post->post_status === 'publish';
    }

    /**
     * Check if current user can access a post
     * 
     * @since 1.0.0
     * @param \WP_Post $post The post object
     * @return bool
     */
    public static function can_access_post( $post ) {
        if ( ! $post || ! is_object( $post ) ) {
            return false;
        }

        if ( self::is_public_post( $post ) ) {
            return true;
        }

        if ( ! is_user_logged_in() ) {
            return false;
        }

        $post_type_obj = get_post_type_object( $post->post_type );
        return $post_type_obj && current_user_can( $post_type_obj->cap->read_post, $post->ID );
    }

    /**
     * Format content based on the specified format
     * 
     * @since 1.0.0
     * @param \WP_Post $post The post object
     * @param string $format The output format
     * @return string
     */
    public static function format_content( $post, $format = 'markdown' ) {
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

    /**
     * Get cache key for a specific request
     * 
     * @since 1.0.0
     * @param string $prefix The cache key prefix
     * @param array $params The request parameters
     * @return string
     */
    public static function get_cache_key( $prefix, $params = [] ) {
        return $prefix . '_' . md5( serialize( $params ) );
    }

    /**
     * Log debug information
     * 
     * @since 1.0.0
     * @param mixed $data The data to log
     * @param string $context The context
     */
    public static function log_debug( $data, $context = 'contextwp' ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf( '[%s] %s: %s', $context, date( 'Y-m-d H:i:s' ), print_r( $data, true ) ) );
        }
    }
} 