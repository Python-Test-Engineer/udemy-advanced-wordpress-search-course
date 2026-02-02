# WordPress Full Text Search (FTS) Manager Plugin

## Overview

The FTS Manager plugin provides a comprehensive admin interface for managing MySQL Full Text Search indexes on WordPress custom tables and testing search queries with relevance scoring. This is particularly useful for applications that require advanced search functionality beyond WordPress's default search capabilities.

## Target Use Case

This plugin is designed for WordPress sites with custom product/content tables that need:
- Fast, efficient full-text searching
- Relevance ranking of search results
- Multiple search modes (Natural Language, Boolean, Query Expansion)
- Visual testing and debugging of search queries

## Architecture

### Core Components

#### 1. **Main Plugin Class: `FTS_Manager`**

The plugin follows WordPress best practices with a single class architecture:

```php
class FTS_Manager {
    private $table_name;  // Stores the target table (wp_products)
    
    public function __construct()       // Initialization
    public function add_admin_menu()    // Admin UI registration
    public function render_admin_page() // UI rendering
    
    // AJAX Handlers
    public function ajax_get_indexes()  // Fetch existing indexes
    public function ajax_create_index() // Create new FTS index
    public function ajax_delete_index() // Remove FTS index
    public function ajax_run_query()    // Execute search queries
}
```

#### 2. **Target Table**

The plugin targets the `wp_products` table (where `wp_` is your database prefix). The table is expected to have these columns:
- `product_name`
- `product_short_description`
- `expanded_description`

**To adapt to different tables**: Modify line 23 in the constructor:
```php
$this->table_name = $wpdb->prefix . 'your_table_name';
```

### WordPress Integration Points

#### Hooks Used

```php
// Admin menu registration
add_action('admin_menu', array($this, 'add_admin_menu'));

// AJAX endpoints (all require 'manage_options' capability)
add_action('wp_ajax_fts_create_index', array($this, 'ajax_create_index'));
add_action('wp_ajax_fts_delete_index', array($this, 'ajax_delete_index'));
add_action('wp_ajax_fts_run_query', array($this, 'ajax_run_query'));
add_action('wp_ajax_fts_get_indexes', array($this, 'ajax_get_indexes'));
```

#### Admin Menu Position

- **Menu Title**: "05 FTS MANAGER"
- **Icon**: `dashicons-search`
- **Position**: 3.5 (appears near the top of the admin menu)
- **Capability Required**: `manage_options` (admin-only)

### User Interface

The admin page is divided into two main sections using CSS Grid:

#### Left Panel: Index Management (420px wide)

**Current Indexes Display**
- Lists all existing FULLTEXT indexes
- Shows index name, columns included, and index type
- Provides delete functionality for each index
- Real-time refresh capability

**Create New Index Form**
- Custom index name input
- Checkbox selection for columns to include:
  - `product_name`
  - `product_short_description`
  - `expanded_description`
- Validation and error handling

#### Right Panel: Query Testing (Flexible width)

**Search Configuration**
- **Search Query Input**: Free-text search terms
- **Search Mode Selector**:
  - Natural Language Mode (default relevance ranking)
  - Boolean Mode (advanced operators)
  - Query Expansion Mode (automatic term expansion)
- **Result Limit**: 1-100 results

**Results Display**
- Product information in styled cards
- Visual relevance score bars
- Color-coded relevance indicators
- SQL query display for debugging

## Technical Implementation

### Full Text Search Modes

#### 1. Natural Language Mode
Standard MySQL full-text search with automatic relevance scoring.

```sql
MATCH(columns) AGAINST('search term')
```

**Best for**: General searches, finding most relevant content

#### 2. Boolean Mode
Supports advanced search operators:

| Operator | Function | Example |
|----------|----------|---------|
| `+` | Must include | `+camera` |
| `-` | Must exclude | `-smart` |
| `>` | Increase relevance | `>wireless` |
| `<` | Decrease relevance | `<budget` |
| `*` | Wildcard | `port*` |
| `"phrase"` | Exact phrase | `"noise cancelling"` |
| `()` | Grouping | `+(speaker audio)` |

```sql
MATCH(columns) AGAINST('+camera -smart' IN BOOLEAN MODE)
```

**Best for**: Precise filtering, complex queries, power users

#### 3. Query Expansion Mode
MySQL automatically finds related terms based on the dataset.

```sql
MATCH(columns) AGAINST('camera' WITH QUERY EXPANSION)
```

**Best for**: Broader searches, finding related content

### AJAX Implementation

All database operations use WordPress AJAX with proper nonce validation and capability checks.

#### Request Flow

```
User Action → JavaScript Event Handler → AJAX Request → PHP Handler → Database Query → JSON Response → UI Update
```

#### Example: Creating an Index

**JavaScript** (lines 232-262):
```javascript
$('#create-index-form').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        success: function(response) { /* Update UI */ },
        error: function() { /* Show error */ }
    });
});
```

**PHP Handler** (lines 545-579):
```php
public function ajax_create_index() {
    global $wpdb;
    
    // 1. Validate input
    // 2. Sanitize data
    // 3. Build SQL
    // 4. Execute query
    // 5. Return JSON response
}
```

### Database Operations

#### Retrieving Indexes
```php
$wpdb->get_results("SHOW INDEX FROM {$table_name} WHERE Index_type = 'FULLTEXT'")
```

Groups results by index name to show multi-column indexes.

#### Creating Indexes
```sql
ALTER TABLE wp_products 
ADD FULLTEXT INDEX index_name (column1, column2, column3)
```

#### Deleting Indexes
```sql
ALTER TABLE wp_products 
DROP INDEX index_name
```

#### Search Query Structure
```sql
SELECT 
    product_name, 
    product_short_description, 
    expanded_description,
    MATCH(indexed_columns) AGAINST('query' [MODE]) AS relevance_score
FROM wp_products
WHERE MATCH(indexed_columns) AGAINST('query' [MODE])
ORDER BY relevance_score DESC
LIMIT 10
```

**Key Points**:
- `MATCH` must use the same columns in both SELECT and WHERE
- Relevance score is calculated automatically by MySQL
- Results ordered by relevance (highest first)

## Security Considerations

### Implemented Security Measures

1. **Direct Access Prevention**
   ```php
   if (!defined('ABSPATH')) {
       exit;
   }
   ```

2. **Capability Checks**
   - All admin pages require `manage_options` capability
   - Only administrators can manage indexes

3. **Input Sanitization**
   ```php
   $index_name = sanitize_text_field($_POST['index_name']);
   $columns = array_map('sanitize_text_field', $_POST['columns']);
   ```

4. **Prepared Statements**
   ```php
   $wpdb->prepare("SELECT ... WHERE Key_name = %s", $index_name)
   ```

5. **Output Escaping**
   ```php
   echo esc_html__('Full Text Search Manager', 'fts-manager');
   ```

### Security Recommendations

⚠️ **Important**: Consider adding:
- AJAX nonce verification for all AJAX handlers
- Additional validation for table/column names
- Rate limiting for query execution
- User permission logging for index modifications

## Data Flow Diagram

```
┌─────────────────┐
│   Admin User    │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────┐
│   WordPress Admin Interface     │
│  ┌──────────┐    ┌──────────┐  │
│  │  Index   │    │  Query   │  │
│  │  Manager │    │  Tester  │  │
│  └─────┬────┘    └────┬─────┘  │
└────────┼──────────────┼─────────┘
         │              │
         ▼              ▼
┌─────────────────────────────────┐
│      AJAX Handlers (PHP)        │
│  - ajax_create_index()          │
│  - ajax_delete_index()          │
│  - ajax_get_indexes()           │
│  - ajax_run_query()             │
└────────┬────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│    WordPress Database (MySQL)   │
│  ┌──────────────────────┐       │
│  │   wp_products        │       │
│  │  - product_name      │       │
│  │  - descriptions...   │       │
│  │  - FULLTEXT indexes  │       │
│  └──────────────────────┘       │
└─────────────────────────────────┘
```

## Installation & Usage

### Installation

1. Upload plugin to `/wp-content/plugins/05-wp-fts-manager/`
2. Activate via WordPress admin
3. Access via "05 FTS MANAGER" in admin menu

### Initial Setup

1. **Verify Table Structure**: Ensure `wp_products` table exists
2. **Create First Index**: 
   - Name it (e.g., `ft_product_name`)
   - Select columns to include
   - Click "Create Index"
3. **Test Search**: 
   - Enter a search term
   - Select search mode
   - Review results and relevance scores

### Best Practices

#### Index Creation
- **Single column indexes**: Fast, specific searches
- **Multi-column indexes**: Comprehensive searches, slightly slower
- **Index naming**: Use descriptive names like `ft_product_name` or `ft_all_fields`

#### Search Optimization
- Use Boolean mode for precise filtering
- Natural mode for general searches
- Query expansion for discovery/related content

#### Performance Considerations
- FULLTEXT indexes require MyISAM or InnoDB (MySQL 5.6+)
- Minimum word length: typically 4 characters (MySQL default)
- Stop words (common words like "the", "a") are automatically excluded
- Rebuilding indexes on large tables can be slow

## Debugging

### Error Logging

The plugin includes comprehensive `error_log()` calls for debugging:

```php
error_log('FTS: ajax_create_index called');
error_log('FTS: POST data - ' . print_r($_POST, true));
error_log('FTS: Creating index with SQL - ' . $sql);
```

**To view logs**: Check your PHP error log or WordPress debug.log

### Enable WordPress Debug Mode

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Common Issues

**"No full-text search indexes exist"**
- Solution: Create an index first using the Index Management section

**"Failed to create index"**
- Check table exists and has correct columns
- Verify MySQL version supports FULLTEXT on InnoDB
- Review error log for SQL errors

**No search results**
- Verify index includes relevant columns
- Check search term meets minimum word length (usually 4 chars)
- Review stop words list
- Try Boolean mode with `+` operator

## Extensibility

### Adding Custom Columns

Modify lines 74-76 in `render_admin_page()`:
```php
<label><input type="checkbox" name="columns[]" value="your_column"> your_column</label>
```

And line 690 to include in SELECT:
```php
SELECT your_column, product_name, ...
```

### Supporting Multiple Tables

Create separate instances with different table names:
```php
class FTS_Manager_Products { /* ... */ }
class FTS_Manager_Posts { /* ... */ }

new FTS_Manager_Products();
new FTS_Manager_Posts();
```

### Custom Search Filters

Add to `ajax_run_query()` before executing:
```php
$sql .= " AND category = 'electronics'";
$sql .= " AND price BETWEEN 100 AND 500";
```

## Performance Metrics

The plugin displays several performance indicators:

- **Relevance Score**: MySQL's internal ranking (higher = more relevant)
- **Result Count**: Number of matching products
- **Query Visualization**: Visual bars showing relative relevance
- **SQL Display**: Actual executed query for debugging

## Localization Support

The plugin is translation-ready:
- Text domain: `fts-manager`
- All strings wrapped in `esc_html__()` or `_e()`
- Translation files should go in: `/languages/fts-manager-{locale}.mo`

## Code Quality Notes

### Strengths
✅ Clean separation of concerns  
✅ WordPress coding standards followed  
✅ Comprehensive error logging  
✅ Responsive UI with visual feedback  
✅ Multiple search modes supported  
✅ Real-time results display  

### Areas for Enhancement
⚠️ Add AJAX nonce verification  
⚠️ Implement user capability checks in AJAX handlers  
⚠️ Add unit tests for core functionality  
⚠️ Consider pagination for large result sets  
⚠️ Add export functionality for results  
⚠️ Implement search history/saved queries  

## License

GPL v2 or later

## Support & Contributing

For issues, questions, or contributions, refer to the plugin URI or contact the author.
