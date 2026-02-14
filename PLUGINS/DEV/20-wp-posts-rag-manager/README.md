# Posts RAG Manager Plugin - Detailed Explanation

This document provides a comprehensive explanation of how the Posts RAG (Retrieval-Augmented Generation) Manager plugin works, designed for students learning WordPress plugin development and RAG concepts.

## Overview

The Posts RAG Manager plugin creates a custom database table to store WordPress posts with additional metadata, and provides two powerful search methods:

1. **Full-Text Search (FTS)** - Traditional keyword matching using MySQL's FULLTEXT index
2. **Vector Search** - Semantic similarity search using OpenAI embeddings and cosine similarity

## Database Table Structure

When the plugin is activated, it creates a table called `wp_posts_rag` with the following columns:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Unique ID (auto-increment) |
| post_id | bigint(20) | Links to WordPress posts |
| post_title | text | Post title |
| post_content | longtext | Full post content |
| categories | text | Comma-separated categories |
| tags | text | Comma-separated tags |
| custom_meta_data | text | Custom field values |
| embedding | longtext | OpenAI embedding vector |
| last_embedded | datetime | When embedding was generated |

## Why This Table?

1. **Performance** - FTS indexes work best on dedicated tables
2. **Embeddings** - Vector data can be large and slow down regular queries
3. **RAG Pipeline** - We can process data without affecting main WordPress tables

## Main Class Structure

```php
class Posts_RAG_Manager {
    private $table_name;      // Custom table name
    private $option_name;     // Option for storing OpenAI API key
    
    public function __construct() {
        // Hooks are registered here
    }
}
```

## Core Components

### 1. Constructor and Hooks

The constructor registers all WordPress hooks:

- `admin_menu` - Adds admin menu pages
- `wp_ajax_*` - AJAX handlers for admin interactions
- `rest_api_init` - Registers REST API routes
- `admin_enqueue_scripts` - Loads admin scripts

### 2. Database Table Creation

On activation, the plugin creates the table:

```sql
CREATE TABLE wp_posts_rag (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id bigint(20) UNSIGNED NOT NULL,
    post_title text NOT NULL,
    post_content longtext NOT NULL,
    categories text,
    tags text,
    custom_meta_data text,
    embedding longtext,
    last_embedded datetime DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY post_id (post_id)
);
```

### 3. Post Sync Process

The `sync_posts_to_table()` method:
- Gets all published posts from WordPress
- Extracts categories and tags
- Collects custom field values (including ACF)
- Inserts/updates records in the RAG table

## Full-Text Search (FTS)

### What is FTS?

MySQL's FULLTEXT search tokenizes text into words and indexes them for fast, relevance-ranked searching.

### Creating the Index

```php
public function create_fulltext_index($fields = array('post_title', 'post_content')) {
    global $wpdb;
    
    // Check if already exists
    if ($this->check_fulltext_index()) {
        return array('success' => false, 'message' => 'Already exists');
    }
    
    // Create the index
    $fields_str = implode(', ', $fields);
    $sql = "ALTER TABLE {$this->table_name} ADD FULLTEXT INDEX {$index_name} ({$fields_str})";
    $result = $wpdb->query($sql);
    
    return $result !== false;
}
```

This creates a MySQL FULLTEXT index that enables fast searching with relevance ranking.

### Performing FTS Search

```php
private function fulltext_search($query, $limit = 3) {
    global $wpdb;
    
    $sql = $wpdb->prepare(
        "SELECT post_id, post_title, post_content, categories, tags,
                MATCH(columns) AGAINST (%s IN NATURAL LANGUAGE MODE) as relevance_score
         FROM {$this->table_name}
         WHERE MATCH(columns) AGAINST (%s IN NATURAL LANGUAGE MODE)
         ORDER BY relevance_score DESC
         LIMIT %d",
        $query, $query, $limit
    );
    
    return $wpdb->get_results($sql);
}
```

**Key SQL features:**
- `MATCH(...) AGAINST(...)` - The FTS function
- `IN NATURAL LANGUAGE MODE` - Relevance-ranked results
- `ORDER BY relevance_score` - Most relevant first

## Vector Search

### What is Vector Search?

Vector search uses embeddings - numerical representations of text that capture meaning. Similar concepts have similar numbers.

### Understanding Embeddings

OpenAI converts text into arrays of 1536 numbers:

```php
private function get_openai_embedding($text, $api_key) {
    $url = 'https://api.openai.com/v1/embeddings';
    
    $response = wp_remote_post($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'input' => $text,
            'model' => 'text-embedding-3-small'
        )),
        'timeout' => 30
    ));
    
    $result = json_decode(wp_remote_retrieve_body($response), true);
    return $result['data'][0]['embedding'];
}
```

### Cosine Similarity Formula

```php
private function cosine_similarity($vec1, $vec2) {
    $dot_product = 0;
    $magnitude1 = 0;
    $magnitude2 = 0;
    
    for ($i = 0; $i < count($vec1); $i++) {
        $dot_product += $vec1[$i] * $vec2[$i];
        $magnitude1 += $vec1[$i] * $vec1[$i];
        $magnitude2 += $vec2[$i] * $vec2[$i];
    }
    
    $magnitude1 = sqrt($magnitude1);
    $magnitude2 = sqrt($magnitude2);
    
    if ($magnitude1 == 0 || $magnitude2 == 0) {
        return 0;
    }
    
    return $dot_product / ($magnitude1 * $magnitude2);
}
```

**Mathematical breakdown:**

```
cos(θ) = (A · B) / (|A| × |B|)
```

- **A · B** = Dot product (sum of element-wise multiplication)
- **|A|, |B|** = Magnitude of each vector

**Result range:** -1 to 1
- 1.0 = Identical meaning
- 0.0 = No relationship
- -1.0 = Opposite meaning

### Vector Search Process

```php
private function vector_search($query, $limit = 3) {
    $api_key = get_option($this->option_name);
    
    // Convert query to embedding
    $query_embedding = $this->get_openai_embedding($query, $api_key);
    
    // Get all posts with embeddings
    $posts = $wpdb->get_results(
        "SELECT id, post_id, post_title, embedding FROM {$this->table_name}
         WHERE embedding IS NOT NULL AND embedding != ''"
    );
    
    // Calculate similarity for each post
    $similarities = array();
    foreach ($posts as $post) {
        $post_embedding = json_decode($post->embedding, true);
        $similarity = $this->cosine_similarity($query_embedding, $post_embedding);
        $similarities[] = array(
            'post_id' => $post->post_id,
            'post_title' => $post->post_title,
            'similarity_score' => $similarity
        );
    }
    
    // Sort by similarity (highest first)
    usort($similarities, function($a, $b) {
        return $b['similarity_score'] <=> $a['similarity_score'];
    });
    
    return array_slice($similarities, 0, $limit);
}
```

## REST API Endpoints

The plugin registers two REST API endpoints:

### 1. Full-Text Search Endpoint

**URL:** `https://yoursite.com/wp-json/posts-rag/v1/search?query=foam&limit=5`

```php
register_rest_route('posts-rag/v1', '/search', array(
    'methods' => 'GET',
    'callback' => array($this, 'rest_search_posts'),
    'permission_callback' => '__return_true',
    'args' => array(
        'query' => array('required' => true, 'type' => 'string'),
        'limit' => array('required' => false, 'type' => 'integer', 'default' => 3)
    )
));
```

### 2. Vector Search Endpoint

**URL:** `https://yoursite.com/wp-json/posts-rag/v1/vector-search?query=sleep&limit=5`

```php
register_rest_route('posts-rag/v1', '/vector-search', array(
    'methods' => 'GET',
    'callback' => array($this, 'rest_vector_search'),
    'permission_callback' => '__return_true',
    'args' => array(...)
));
```

## AJAX Handlers

AJAX allows the admin page to communicate with the server without refreshing:

```php
// PHP Handler
public function ajax_sync_posts() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    
    $synced = $this->sync_posts_to_table();
    wp_send_json_success("Synced {$synced} posts.");
}

// JavaScript counterpart
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: { action: 'sync_posts' },
    success: function(response) {
        if (response.success) {
            alert(response.data);
        }
    }
});
```

**WordPress AJAX Flow:**
```
Browser → WordPress → Handler Function → JSON Response → UI Update
```

## Admin Interface

The plugin adds:

1. **Main Menu:** Posts RAG Manager
2. **Submenu:** Search Testing

### Admin Page Sections

1. **OpenAI API Configuration** - Enter API key
2. **Table Statistics** - View sync and embedding status
3. **Sync Posts** - Copy WordPress posts to RAG table
4. **Full-Text Index** - Create/delete FTS index
5. **Generate Embeddings** - Create vector representations
6. **REST API Endpoints** - View available API URLs

## Complete Workflow

```
1. User Activates Plugin
   ↓
2. Table 'wp_posts_rag' is created
   
3. Admin visits plugin page
   ↓
4. Enters OpenAI API key → Saved via AJAX
   
5. Clicks "Sync Posts"
   ↓
6. Posts copied from wp_posts to wp_posts_rag
   
7. Creates FTS Index
   ↓
8. MySQL FULLTEXT index created
   
9. Generates Embeddings
   ↓
10. OpenAI API called for each post
    ↓
11. Embeddings stored in database
    
12. External requests to REST endpoints
    ↓
13. Search results returned (FTS or Vector)
```

## FTS vs Vector Search Comparison

| Aspect | Full-Text Search | Vector Search |
|--------|-----------------|---------------|
| **Method** | Keyword matching | Semantic similarity |
| **Data** | Original text | Numerical vectors |
| **API** | MySQL | OpenAI API |
| **Speed** | Very fast (indexed) | Slower (calculations) |
| **Example** | "foam mattress" | "comfortable bedding" |
| **Matches** | Contains "foam" or "mattress" | Similar to "soft bed" |

## Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| "Full-text index not created" | No FTS index | Create one in admin page |
| "No posts with embeddings found" | Embeddings not generated | Click "Generate Embeddings" |
| API errors | Invalid/missing API key | Enter valid OpenAI key |

## Learning Exercises

### Exercise 1: Add Boolean Mode Search

```php
register_rest_route('posts-rag/v1', '/search-boolean', array(
    'methods' => 'GET',
    'callback' => array($this, 'rest_search_posts_boolean'),
    // ...
));
```

### Exercise 2: Add Hybrid Search

```php
public function rest_hybrid_search($request) {
    $fts_results = $this->rest_search_posts($request);
    $vector_results = $this->rest_vector_search($request);
    return $this->combine_results($fts_results, $vector_results);
}
```

### Exercise 3: Add Completion Percentage

```php
private function display_stats() {
    global $wpdb;
    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    $embedded = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE embedding IS NOT NULL");
    echo '<p><strong>Completion:</strong> ' . round($embedded / $total * 100, 1) . '%</p>';
}
```

## Summary

The Posts RAG Manager plugin demonstrates:

1. **Custom Database Tables** - Using `$wpdb` for database operations
2. **WordPress Hooks** - `add_action()` for extensibility
3. **REST API** - `register_rest_route()` for external access
4. **AJAX** - `wp_ajax_*` hooks for dynamic admin interfaces
5. **Vector Embeddings** - Using OpenAI API for semantic search
6. **Cosine Similarity** - Mathematical approach to measuring similarity

This plugin can serve as a foundation for building RAG applications, hybrid search systems, and intelligent document retrieval systems.

---

**Happy Learning!**
