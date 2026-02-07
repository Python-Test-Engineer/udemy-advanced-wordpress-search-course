# FTS Query Builder - WordPress Plugin

A WordPress plugin that provides an advanced search form to generate encoded FTS (Full Text Search) query strings with operators.

## Features

- **Admin Menu Interface**: Accessible from WordPress admin dashboard
- **User Level 4.9 Access**: Menu item available to users with level_4 capability
- **Must Contain** (+): Terms that must appear in search results
- **Must NOT Contain** (-): Terms to exclude from results
- **Wildcard** (*): Match word variations
- **Proper URL Encoding**: Uses `rawurlencode()` to preserve operators
- **Copy to Clipboard**: Easy copying of generated query strings
- **AJAX-powered**: Smooth, no-reload experience
- **Frontend Shortcode**: Display form on any page or post

## Installation

1. Upload the `fts-query-builder` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access via admin menu "FTS Search" (requires user level 4 or higher)
4. Or use the shortcode `[fts_search_form]` in any page or post

## Usage

### Admin Menu
After activation, you'll see "FTS Search" in your WordPress admin menu (with a search icon). Click it to access the query builder interface.

**Minimum User Level:** 4.9 (level_4 capability)

### Frontend Shortcode
```
[fts_search_form]
```

### How It Works

1. **User fills in the form fields:**
   - Basic Query: `wordpress plugin`
   - Must Contain: `tutorial`
   - Must Not Contain: `premium`
   - Wildcard: `develop`

2. **Plugin generates:**
   - Query: `wordpress plugin +tutorial -premium develop*`
   - Encoded: `wordpress%20plugin%20%2Btutorial%20-premium%20develop*`

3. **User gets the encoded query string to append to their URL:**
   ```
   /wp-json/wp/v2/posts?search=wordpress%20plugin%20%2Btutorial%20-premium%20develop*
   ```

## Key Technical Details

### Why `rawurlencode()`?

The plugin uses `rawurlencode()` instead of `urlencode()` because:

- `+` in URLs normally means "space"
- We need `+` as a literal search operator
- `rawurlencode()` converts `+` to `%2B` (preserving the operator)
- `urlencode()` would convert spaces to `+` (breaking our operators)

### Encoding Comparison

| Character | Purpose | Encoded As |
|-----------|---------|------------|
| `+` | Must contain operator | `%2B` |
| `-` | Must not contain operator | `%2D` |
| `*` | Wildcard operator | `%2A` |
| space | Term separator | `%20` |

## File Structure

```
fts-query-builder/
├── fts-query-builder.php   (Main plugin file)
├── assets/
│   ├── style.css            (Styling)
│   └── script.js            (JavaScript/AJAX)
└── README.md               (This file)
```

## WordPress API Usage

Once you have the encoded query, use it with the WordPress REST API:

```php
$encoded_query = 'wordpress%20plugin%20%2Btutorial';
$url = home_url('/wp-json/wp/v2/posts?search=' . $encoded_query);
$response = wp_remote_get($url);
$posts = json_decode(wp_remote_retrieve_body($response));
```

## Support

For issues or questions, please contact the developer.

## License

GPL2
