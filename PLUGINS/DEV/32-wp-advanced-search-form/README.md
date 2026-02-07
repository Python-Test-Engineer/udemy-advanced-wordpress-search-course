# FTS Query Builder - WordPress Plugin

A WordPress plugin that provides an advanced search form to generate encoded FTS (Full Text Search) query strings with operators.

## Features

- **Admin Menu Interface**: Accessible from WordPress admin dashboard
- **User Level 4.9 Access**: Menu item available to users with level_4 capability
- **Must Contain (+)**: Terms that must appear in search results
- **Must NOT Contain (-)**: Terms to exclude from results
- **Wildcard (*)**: Match word variations
- **Exact Phrase ("")**: Match exact phrases
- **Less Than (<)**: Numeric comparison operator
- **Greater Than (>)**: Numeric comparison operator
- **OR Operator (|)**: Match any of the specified terms
- **Grouping ()**: Complex query grouping with parentheses
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
   - Exact Phrase: `best practices`
   - OR Terms: `guide help`

2. **Plugin generates:**
   - Query: `wordpress plugin +tutorial -premium develop* "best practices" (guide|help)`
   - Encoded: `wordpress%20plugin%20%2Btutorial%20-premium%20develop*%20%22best%20practices%22%20%28guide%7Chelp%29`

3. **User gets the encoded query string to append to their URL:**
   ```
   /wp-json/wp/v2/posts?search=wordpress%20plugin%20%2Btutorial%20-premium%20develop*%20%22best%20practices%22%20%28guide%7Chelp%29
   ```

## Operator Reference

| Operator | Symbol | Example | Description |
|----------|--------|---------|-------------|
| Must Contain | `+` | `+tutorial` | Result MUST include this term |
| Must NOT Contain | `-` | `-premium` | Result must NOT include this term |
| Wildcard | `*` | `develop*` | Matches develop, developer, development, etc. |
| Exact Phrase | `"..."` | `"wordpress plugin"` | Match exact phrase |
| Less Than | `<` | `price<100` | Numeric less than comparison |
| Greater Than | `>` | `price>100` | Numeric greater than comparison |
| OR | `|` | `(tutorial|guide)` | Match either term |
| Grouping | `()` | `(+term1 +term2)` | Group complex queries |

### Complex Query Examples

**Example 1: Advanced Product Search**
```
+wordpress +(plugin|theme) -premium "best practices" develop* price<50
```
- MUST contain "wordpress"
- MUST contain either "plugin" OR "theme"
- Must NOT contain "premium"
- Must contain exact phrase "best practices"
- Match "develop" variations
- Price less than 50

**Example 2: Tutorial Search**
```
+(tutorial|guide) +beginner -(paid|premium) free*
```
- MUST contain either "tutorial" OR "guide"
- MUST contain "beginner"
- Must NOT contain "paid" or "premium"
- Match "free" variations

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

The plugin automatically generates URLs using your site's custom hybrid search endpoint:

```php
// The plugin uses get_site_url() to get the current domain
$site_url = get_site_url();
$encoded_query = 'pillow%20%2Bmemory%20%2Bfoam';
$url = $site_url . '/wp-json/search/v1/hybrid-search?query=' . $encoded_query;

// Example result:
// https://yoursite.com/wp-json/search/v1/hybrid-search?query=pillow%20%2Bmemory%20%2Bfoam
```

### Making API Calls

```php
// Once you have the encoded query, use it with the hybrid search API
$encoded_query = 'pillow%20%2Bmemory%20%2Bfoam%20-premium';
$url = get_site_url() . '/wp-json/search/v1/hybrid-search?query=' . $encoded_query;

$response = wp_remote_get($url);
if (!is_wp_error($response)) {
    $body = wp_remote_retrieve_body($response);
    $results = json_decode($body, true);
    // Process your results
}
```

### Endpoint Format

The plugin uses the custom hybrid search endpoint:
```
/wp-json/search/v1/hybrid-search?query=[encoded_query]
```

This endpoint is designed to work with your Full Text Search implementation and supports all the boolean operators provided by the plugin.

## Support

For issues or questions, please contact the developer.

## License

GPL2
