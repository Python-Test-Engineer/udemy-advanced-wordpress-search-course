# WP REST RAG Endpoints Plugin - Developer Explanation

## Overview

This WordPress plugin creates a modular set of REST API endpoints for RAG (Retrieval-Augmented Generation) search functionality. It provides three types of search: **Full-Text Search (FTS)**, **Vector Search**, and **Hybrid Search**.

## Architecture

### Plugin Structure
The plugin is contained in a single class `WP_REST_RAG_Endpoints` initialized at the bottom of the file.

### Configuration Constants (Lines 14-21)
```php
define('RAG_PLUGIN_NAMESPACE', 'search/v1');           // Base namespace for vector/hybrid endpoints
define('RAG_FTS_NATURAL_NAMESPACE', 'fts-natural/v1');  // Namespace for natural language FTS
define('RAG_FTS_BOOLEAN_NAMESPACE', 'fts-boolean/v1');  // Namespace for boolean FTS
define('RAG_FTS_QUERY_EXPANSION_NAMESPACE', 'fts-query-expansion/v1'); // Query expansion FTS
define('RAG_TABLE_NAME', 'wp_posts_rag');               // Table containing posts with embeddings
```

## REST API Endpoints Registered

### 1. Full-Text Search Endpoints (Lines 56-120)
Three endpoints using MySQL's `MATCH AGAINST` full-text search:

| Mode | Endpoint | Description |
|------|----------|-------------|
| Natural | `/fts-natural/v1/search` | Basic relevance ranking |
| Boolean | `/fts-boolean/v1/search` | Supports `+word -word OR` operators |
| Query Expansion | `/fts-query-expansion/v1/search` | Expands query using related terms |

**Key Method**: `rest_fulltext_search()` (Lines 389-440)
- Validates query parameter (required)
- Enforces limit between 1-20
- Checks if full-text index exists before querying
- Returns results with `relevance_score` from MySQL

### 2. Vector Search Endpoint (Lines 121-143)
Endpoint: `/search/v1/vector-search`

**Key Method**: `vector_search()` (Lines 516-572)
1. Gets OpenAI API key from another plugin or options
2. Generates embedding for query using `text-embedding-3-small`
3. Fetches all posts with embeddings from `wp_posts_rag` table
4. Calculates **cosine similarity** between query embedding and each post embedding
5. Returns top N results sorted by similarity

**Cosine Similarity Calculation** (Lines 475-495):
```php
// Formula: dot_product / (magnitude1 * magnitude2)
$dot_product += $vec1[$i] * $vec2[$i];
$magnitude1 = sqrt(sum($vec1[i]²));
return $dot_product / ($magnitude1 * $magnitude2);
```

### 3. Hybrid Search Endpoint (Lines 144-170)
Endpoint: `/search/v1/hybrid-search`

Combines full-text and vector search results:
1. Calls FTS endpoint (configurable mode: boolean/natural/query_expansion)
2. Calls vector search endpoint
3. Merges results, removing duplicates by `post_id`
4. Full-text results prioritized first, then vector results

## OpenAI Integration

**Method**: `get_openai_embedding()` (Lines 747-774)
```php
POST https://api.openai.com/v1/embeddings
{
  "input": "query text",
  "model": "text-embedding-3-small"
}
```

API key retrieval (Lines 681-690):
- First tries `WP_REST_OpenAI_Key` class from another plugin
- Falls back to `get_option('posts_rag_openai_key')`

## Admin Interface (Lines 203-362)

Added via `add_admin_menu()` with dashboard under "30 ENDPOINTS" menu item.

Features:
- **Full-Text Index Status** - Check if MySQL FTS index exists with button to create it
- **Test Buttons** - AJAX buttons to test each search method with sample query "FOAM"
- **Endpoint URLs** - Displays all REST endpoint URLs for easy copying
- **Sample Outputs** - Collapsible examples showing expected JSON responses

## AJAX Handlers (Lines 693-746)

Each handler:
1. Verifies `manage_options` capability
2. Creates a `WP_REST_Request` object
3. Calls the corresponding REST handler method
4. Returns JSON success/error response

## Sanitization (Lines 181-203)

Two sanitization callbacks:

| Method | Allowed Characters | Use Case |
|--------|-------------------|----------|
| `sanitize_boolean_query()` | Letters, numbers, whitespace, `+-\*\|<>()()` | Boolean operators preserved |
| `sanitize_fulltext_query()` | Letters, numbers, whitespace, quotes | Natural language queries |

## Key Database Operations

### Full-Text Search SQL (Lines 576-594)
```sql
SELECT post_id, post_title, post_content, categories, tags,
       MATCH(post_title, post_content) AGAINST ('query' IN MODE) as relevance_score
FROM wp_posts_rag
WHERE MATCH(post_title, post_content) AGAINST ('query' IN MODE)
ORDER BY relevance_score DESC
LIMIT N
```

### Check/Create Index (Lines 624-661)
- Uses `SHOW INDEX FROM table WHERE Key_name = 'fulltext_search_idx'`
- Creates via `ALTER TABLE wp_posts_rag ADD FULLTEXT INDEX fulltext_search_idx (post_title, post_content)`

## Data Flow Example: Hybrid Search

1. User requests `/search/v1/hybrid-search?query=FOAM&limit=3`
2. Plugin creates internal REST requests to FTS and vector endpoints
3. Full-text search runs MySQL query, returns results with relevance scores
4. Vector search generates OpenAI embedding, calculates cosine similarity
5. Results merged, deduplicated by post_id
6. JSON response returned with both result sets and counts

## Dependencies

- **WordPress REST API** - Uses `register_rest_route()`, `WP_REST_Request`, `WP_REST_Response`
- **MySQL Full-Text Index** - Required on `wp_posts_rag` table
- **OpenAI API** - For generating text embeddings
- **jQuery** - Admin UI JavaScript uses `$` shorthand

## Extensibility Points

1. **Change embeddings model** - Modify `text-embedding-3-small` in `get_openai_embedding()`
2. **Add new search modes** - Add new constant, register route, create handler method
3. **Customize similarity metric** - Replace cosine similarity in `cosine_similarity()`
4. **Different API provider** - Modify `get_openai_embedding()` to call alternative API
