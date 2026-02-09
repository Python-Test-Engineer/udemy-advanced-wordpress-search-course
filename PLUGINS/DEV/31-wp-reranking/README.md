# WP Reranking Plugin

A WordPress plugin that intelligently combines and reranks Full-Text Search (FTS) and Vector Search results into a single, ordered list using a hybrid scoring approach.

## Overview

This plugin implements a **Reciprocal Rank Fusion (RRF)**-inspired reranking algorithm that merges results from two different search methods:

- **Full-Text Search (FTS)**: Keyword-based search using MySQL's FULLTEXT index
- **Vector Search**: Semantic similarity search using vector embeddings

By combining both approaches, the plugin delivers more accurate and relevant search results than either method alone.

## How It Works

### The Reranking Algorithm (Step-by-Step)

When you run a search query, the plugin performs the following steps:

#### 1. **Collect Input Data**

The plugin accepts search results from multiple sources:

- **Direct Payload**: POST JSON data with `fulltext_search` and `vector_search` keys
- **Automatic Fetch**: If no payload is provided, it automatically queries:
  - `/wp-json/search/v1/search` (FTS endpoint)
  - `/wp-json/search/v1/vector-search` (Vector endpoint)

#### 2. **Filter by Query Relevance**

Results are filtered to ensure they contain the search query terms in their title, content, excerpt, categories, or tags. This removes irrelevant matches.

#### 3. **Trim to Top Results**

Each result set is sorted by score and limited to the top N results (default: 6 per method) to focus on the highest-quality matches.

#### 4. **Normalize Scores**

Since FTS relevance scores and vector similarity scores are on different scales, the plugin normalizes them:

```
normalized_relevance = relevance_score / max_relevance
normalized_similarity = similarity_score / max_similarity
```

This ensures both scoring methods contribute fairly to the final ranking.

#### 5. **Merge Results**

Results from both methods are merged by `post_id`. If a post appears in both result sets, its scores are combined.

#### 6. **Calculate Combined Score**

For each unique post, the plugin calculates:

```
combined_score = normalized_relevance + normalized_similarity + title_boost
```

The **title boost** (up to 0.2) is added when query keywords appear in the post title, giving extra weight to title matches.

#### 7. **Sort and Assign Positions**

Results are sorted by `combined_score` in descending order, and each receives a `position` number (1, 2, 3...) indicating its final rank.

#### 8. **Determine Search Method**

Each result is tagged with the method that found it:

- `FTS` - Found only by full-text search
- `VECTOR` - Found only by vector search
- `FTS+VECTOR` - Found by both methods (highest relevance)

---

## REST API Endpoint

### GET Request

```
GET /wp-json/reranker/v1/reranked?query=your-search-term&limit=6
```

**Parameters:**

- `query` (required): The search query string
- `limit` (optional): Maximum number of results to return (default: 6)

### POST Request

```
POST /wp-json/reranker/v1/reranked
Content-Type: application/json

{
  "query": "FOAM products",
  "limit": 6,
  "fulltext_search": {
    "results": [...]
  },
  "vector_search": {
    "results": [...]
  }
}
```

**Payload Options:**

- `fulltext_search` / `vector_search`: Full payload wrappers
- `fts_results` / `vector_results`: Direct result arrays
- `results`: Combined hybrid results (auto-split by method)
- `sql`: Optional SQL query string for debugging

---

## Example Usage

### Input Data

**Full-Text Search Results:**
```json
{
  "fulltext_search": {
    "results": [
      {"post_id": 4339, "relevance_score": 10.40, "post_title": "FOAM Facts"},
      {"post_id": 4352, "relevance_score": 1.73, "post_title": "John Bowie"}
    ]
  }
}
```

**Vector Search Results:**
```json
{
  "vector_search": {
    "results": [
      {"post_id": 4339, "similarity_score": 0.528, "post_title": "FOAM Facts"},
      {"post_id": 4350, "similarity_score": 0.443, "post_title": "rob emmott"}
    ]
  }
}
```

### Output (Reranked)

```json
{
  "success": true,
  "query": "FOAM",
  "method": "reranking",
  "sql": "SELECT ... FROM ... WHERE ...",
  "results": [
    {
      "position": 1,
      "post_id": 4339,
      "post_title": "FOAM Facts",
      "relevance_score": 10.40,
      "similarity_score": 0.528,
      "method": "FTS+VECTOR"
    },
    {
      "position": 2,
      "post_id": 4350,
      "post_title": "rob emmott",
      "relevance_score": 0,
      "similarity_score": 0.443,
      "method": "VECTOR"
    },
    {
      "position": 3,
      "post_id": 4352,
      "post_title": "John Bowie",
      "relevance_score": 1.73,
      "similarity_score": 0,
      "method": "FTS"
    }
  ],
  "count": 3
}
```

---

## Admin Interface

The plugin includes a **Reranker Test Page** in the WordPress admin (under "31 RERANKER" menu):

### Features:

- **Test Query Form**: Enter any search query and limit to see live results
- **Step-by-Step Visualization**: See how the reranking algorithm processes your data at each stage:
  - Input Data
  - Query Filter
  - Trimmed Input Results
  - Max Scores Calculation
  - Merge Results
  - Normalization and Combined Score
  - Sorting
  - Final Limit
  - Final Positions

- **Stylish JSON Output**: Each step displays formatted JSON in a modern dark-themed code block with:
  - Gradient background (slate/dark blue)
  - Rounded corners and subtle shadows
  - Monospace font for readability
  - Step headers with purple accents

- **Final Results Cards**: Visual cards showing:
  - Post title
  - Post ID and position
  - Method badge (FTS, VECTOR, or FTS+VECTOR)
  - Relevance and similarity scores
  - Content excerpt

- **SQL Display**: Shows the FTS SQL query used for the search

---

## Technical Details

### Score Normalization

| Score Type | Range | Normalization |
|------------|-------|---------------|
| `relevance_score` (FTS) | 0 - ∞ | `score / max_relevance` |
| `similarity_score` (Vector) | 0 - 1 | `score / max_similarity` |

### Title Boost Calculation

```php
if (query matches full title) → +0.2
if (query tokens match title) → +0.05 per token (max 0.2)
```

### Result Methods

| Method | Description |
|--------|-------------|
| `FTS` | Result found only by full-text search |
| `VECTOR` | Result found only by vector search |
| `FTS+VECTOR` | Result found by both methods (highest confidence) |
| `UNKNOWN` | No scores detected (rare) |

---

## Installation

1. Upload the plugin files to `/wp-content/plugins/31-wp-reranking/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the test page via **Admin Menu → 31 RERANKER**

### Requirements

- WordPress 5.0+
- PHP 7.4+
- Compatible FTS search endpoint (`search/v1/search`)
- Compatible Vector search endpoint (`search/v1/vector-search`)

---

## Why Reranking Matters

Traditional search uses a single method:

- **FTS only**: Misses semantic meaning (e.g., "car" ≠ "automobile")
- **Vector only**: May miss exact keyword matches

**Hybrid Reranking** combines both:

✅ Exact keyword matching (FTS)  
✅ Semantic understanding (Vector)  
✅ Intelligent score normalization  
✅ Title keyword boosting  
✅ Better overall relevance

---

## Debug Mode

When `WP_DEBUG` is enabled in `wp-config.php`, the plugin logs detailed information to the PHP error log:

- Input payload counts
- Query filter results
- Normalization values
- Final result counts

```php
define('WP_DEBUG', true);
```

---

## Author

**Craig West**

Part of the Udemy Advanced WordPress Search Course
