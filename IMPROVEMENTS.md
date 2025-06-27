# ContextWP Manifest Endpoint Improvements

## Overview
The `manifest.php` file has been significantly improved to enhance security, performance, maintainability, and user experience.

## Key Improvements

### 1. **Security Enhancements**
- **Rate Limiting**: Implemented IP-based rate limiting to prevent abuse
- **Input Validation**: Added proper argument validation and sanitization
- **Error Handling**: Comprehensive error handling with proper HTTP status codes
- **Permission Checks**: Structured permission checking with rate limit enforcement

### 2. **Performance Optimizations**
- **Caching**: Added response caching with 1-hour expiration
- **Efficient Data Processing**: Optimized manifest generation with fallback values
- **Reduced Database Queries**: Cached responses reduce server load

### 3. **Code Organization**
- **Utilities Class**: Created reusable helper functions in `includes/helpers/utilities.php`
- **Method Separation**: Broke down large methods into smaller, focused functions
- **Consistent Documentation**: Added comprehensive PHPDoc blocks

### 4. **Enhanced Functionality**
- **Dynamic Branding**: Removed hardcoded URLs, now uses WordPress constants
- **Extensible Design**: Added multiple filter hooks for customization
- **Better Error Messages**: User-friendly error messages with proper internationalization
- **Additional Metadata**: Added capabilities and rate limit information to manifest

### 5. **Maintainability**
- **Consistent Naming**: Follows WordPress coding standards
- **Modular Structure**: Separated concerns into logical methods
- **Filter Hooks**: Multiple customization points for developers
- **Debug Logging**: Added debug logging for troubleshooting

## New Features

### Rate Limiting
```php
// Configurable rate limits via filters
add_filter( 'contextwp_rate_limit_per_minute', function() { return 30; });
add_filter( 'contextwp_rate_limit_per_hour', function() { return 500; });
```

### Caching
```php
// Cache key generation with request parameters
$cache_key = \ContextWP\Helpers\Utilities::get_cache_key( 'contextwp_manifest', $params );
```

### Customization Hooks
```php
// Customize manifest data
add_filter( 'contextwp_manifest', function( $manifest ) {
    $manifest['custom_field'] = 'custom_value';
    return $manifest;
});

// Customize branding
add_filter( 'contextwp_plugin_url', function( $url ) {
    return 'https://mycustomdomain.com/plugin';
});
```

## File Structure Changes

### New Files
- `includes/helpers/utilities.php` - Reusable utility functions
- `IMPROVEMENTS.md` - This documentation file

### Modified Files
- `includes/endpoints/manifest.php` - Completely refactored
- `includes/contextwp-init.php` - Added manifest endpoint registration

## Usage Examples

### Basic Manifest Request
```bash
GET /wp-json/mcp/v1/manifest
```

### Response Format
```json
{
    "name": "My Site – ContextWP",
    "description": "A WordPress site with ContextWP integration",
    "version": "1.0.0",
    "endpoints": {
        "list_contexts": {
            "url": "https://example.com/wp-json/mcp/v1/list_contexts",
            "method": "GET",
            "description": "List available contexts"
        },
        "get_context": {
            "url": "https://example.com/wp-json/mcp/v1/get_context",
            "method": "GET",
            "description": "Get specific context content"
        }
    },
    "formats": ["markdown", "plain", "html"],
    "context_types": ["post", "page"],
    "branding": {
        "plugin_url": "https://example.com/wp-content/plugins/contextwp/",
        "logo_url": "https://example.com/wp-content/plugins/contextwp/admin/assets/logo.png",
        "author": "ContextWP Team"
    },
    "capabilities": {
        "public_access": true,
        "authentication_required": false,
        "rate_limited": true,
        "caching_enabled": true
    },
    "rate_limits": {
        "requests_per_minute": 60,
        "requests_per_hour": 1000
    }
}
```

## Error Handling

The endpoint now returns proper HTTP status codes:

- `200` - Success
- `429` - Rate limit exceeded
- `500` - Server error

## Testing

To test the improvements:

1. **Rate Limiting**: Make multiple rapid requests to see rate limiting in action
2. **Caching**: Make the same request twice to verify caching works
3. **Error Handling**: Test with invalid parameters
4. **Customization**: Use filters to modify the manifest output

## Backward Compatibility

All existing functionality is preserved. The endpoint remains public and returns the same core data structure, with additional fields for enhanced functionality. 