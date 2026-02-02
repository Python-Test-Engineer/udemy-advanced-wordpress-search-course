# BM25 Search Algorithm for WordPress

A comprehensive guide to understanding and implementing the BM25 (Best Match 25) relevance ranking algorithm in WordPress.

## What is BM25?

BM25 is a probabilistic information retrieval algorithm that ranks documents based on how relevant they are to a search query. Unlike simple keyword matching, BM25 considers:

- **Term Frequency (TF)**: How often a search term appears in a document
- **Inverse Document Frequency (IDF)**: How rare/common a term is across all documents
- **Document Length**: Normalizes scores so longer documents aren't unfairly favored

Think of it as Google's ranking algorithm, but simpler and proven effective for search applications.

## The Core Formula

```
BM25(q, d) = Σ IDF(qi) × (f(qi, d) × (k1 + 1)) / (f(qi, d) + k1 × (1 - b + b × |d| / avgdl))
```

Where:
- `q` = query terms
- `d` = document being scored
- `f(qi, d)` = frequency of term qi in document d
- `k1` = term frequency saturation parameter (typically 1.2-2.0)
- `b` = length normalization parameter (typically 0.5-0.8)
- `|d|` = length of document d
- `avgdl` = average document length across all documents

## Plugin Architecture

This plugin implements BM25 with two main classes:

### 1. BM25_Algorithm Class

The pure algorithm implementation - handles all calculations.

```php
class BM25_Algorithm {
    private $k1;        // Term frequency saturation (default: 1.5)
    private $b;         // Document length normalization (default: 0.75)
    private $documents; // Array of text documents to search
    private $avgdl;     // Average document length
    private $N;         // Total number of documents
}
```

**Key Methods:**

- `score($query, $document)` - Calculates BM25 score for one document
- `search($query)` - Searches all documents and returns ranked results
- `calculateIDF($term)` - Computes inverse document frequency
- `termFrequency($term, $document)` - Counts term occurrences

### 2. BM25_Calculation_Plugin Class

The WordPress integration layer - handles admin UI, database, and AJAX.

```php
class BM25_Calculation_Plugin {
    private $index_definitions; // MySQL FULLTEXT index configurations
}
```

**Key Features:**

- Admin page with interactive search testing
- FULLTEXT index management for `wp_products` table
- AJAX-powered real-time calculations
- Visual result rankings with detailed scoring breakdowns

## Understanding the Parameters

### k1 (Term Frequency Saturation)

Controls how much additional occurrences of a term matter.

- **Low k1 (0.5-1.0)**: Term frequency saturates quickly
  - Good for: Short documents, query-focused searches
  - Effect: First few occurrences matter most
  
- **High k1 (2.0-3.0)**: Term frequency keeps mattering
  - Good for: Long documents, content-heavy searches
  - Effect: More occurrences = significantly higher scores

**Default: 1.5** (balanced approach)

```php
// Example: "smart" appears 3 times vs 1 time
$numerator = $tf * ($k1 + 1);
// tf=3, k1=1.5: numerator = 3 × 2.5 = 7.5
// tf=1, k1=1.5: numerator = 1 × 2.5 = 2.5
```

### b (Document Length Normalization)

Controls how much document length affects scoring.

- **b = 0**: Length doesn't matter at all
  - Effect: Longer documents unfairly favored
  
- **b = 1**: Full length normalization
  - Effect: Longer documents heavily penalized
  
- **b = 0.75** (default): Balanced normalization
  - Effect: Moderate penalty for longer documents

```php
$denominator = $tf + $k1 * (1 - $b + $b * ($docLength / $avgdl));
// Short doc (50 words, avgdl=100): penalty applied
// Long doc (200 words, avgdl=100): stronger penalty
```

## FULLTEXT Index Management

The plugin creates and manages MySQL FULLTEXT indexes for optimized searching.

### Available Index Combinations

```php
// Single field indexes
ft_product_name                    // Just product names
ft_product_short_description       // Just short descriptions
ft_expanded_description            // Just expanded descriptions

// Two-field combinations
ft_prod_prod    // product_name + product_short_description
ft_prod_expa    // product_name + expanded_description
ft_prod_expa_2  // product_short_description + expanded_description

// All fields
ft_all_fields   // All three columns together
```

### Creating Indexes

The plugin provides a UI to create indexes, but you can also do it manually:

```sql
-- Example: Create index on product name and short description
ALTER TABLE wp_products 
ADD FULLTEXT INDEX ft_prod_prod (product_name, product_short_description);
```

### Why Multiple Indexes?

Different search scenarios benefit from different field combinations:

- **Product name only**: Fast, precise product lookups
- **Name + short description**: Best for quick product searches
- **All fields**: Comprehensive but slower, finds everything

## The Scoring Process

### Step 1: Term Frequency (TF)

Count how many times each query term appears in the document.

```php
private function termFrequency($term, $document) {
    $tokens = $this->tokenize($document);
    return count(array_filter($tokens, fn($word) => $word === strtolower($term)));
}
```

Example:
- Document: "Smart LED light bulb with smart controls"
- Query term: "smart"
- TF = 2

### Step 2: Document Frequency (DF)

Count how many documents contain the term.

```php
private function documentFrequency($term) {
    $count = 0;
    foreach ($this->documents as $doc) {
        if ($this->termFrequency($term, $doc) > 0) $count++;
    }
    return $count;
}
```

### Step 3: Inverse Document Frequency (IDF)

Calculate how rare/important the term is.

```php
private function calculateIDF($term) {
    $n = $this->documentFrequency($term);
    return log(($this->N - $n + 0.5) / ($n + 0.5));
}
```

**IDF Logic:**
- Rare terms (low DF) = high IDF = more valuable
- Common terms (high DF) = low IDF = less valuable

Example with 100 documents:
- Term appears in 5 docs: IDF = log((100-5+0.5)/(5+0.5)) = 2.94
- Term appears in 50 docs: IDF = log((100-50+0.5)/(50+0.5)) = 0.69
- Term appears in 95 docs: IDF = log((100-95+0.5)/(95+0.5)) = -1.62 (negative!)

### Step 4: Combine Everything

```php
public function score($query, $document, $docIndex = 0) {
    $queryTerms = array_unique($this->tokenize($query));
    $docLength = str_word_count($document);
    $score = 0.0;
    
    foreach ($queryTerms as $term) {
        $tf = $this->termFrequency($term, $document);
        $idf = $this->calculateIDF($term);
        
        $numerator = $tf * ($this->k1 + 1);
        $denominator = $tf + $this->k1 * (1 - $this->b + $this->b * ($docLength / $this->avgdl));
        
        $termScore = $idf * ($numerator / $denominator);
        $score += $termScore;
    }
    
    return compact('score', 'details');
}
```

## Real-World Example

Let's search for "smart led" across three products:

### Documents
1. "Smart LED bulb" (3 words)
2. "LED light fixture" (3 words)
3. "Smart home automation system with LED controls" (7 words)

### Calculation

**Query: "smart led"**
- Average document length: (3+3+7)/3 = 4.33 words
- k1 = 1.5, b = 0.75

**Document 1: "Smart LED bulb"**

Term "smart":
- TF = 1
- DF = 2 (appears in docs 1 and 3)
- IDF = log((3-2+0.5)/(2+0.5)) = -0.405
- Numerator = 1 × 2.5 = 2.5
- Denominator = 1 + 1.5 × (0.25 + 0.75 × (3/4.33)) = 2.03
- Term score = -0.405 × (2.5/2.03) = -0.499

Term "led":
- TF = 1
- DF = 3 (appears in all docs)
- IDF = log((3-3+0.5)/(3+0.5)) = -0.847
- Term score = -0.847 × (2.5/2.03) = -1.044

**Total score: -1.543**

**Document 2: "LED light fixture"**
- Only contains "led", not "smart"
- Term score for "led": -1.044
- **Total score: -1.044**

**Document 3: "Smart home automation..."**
- Longer document (7 words) gets penalized more
- Both terms present but diluted
- **Total score: -1.891** (penalized for length)

### Results Ranking
1. Document 2: -1.044 (best score)
2. Document 1: -1.543
3. Document 3: -1.891

Note: Negative scores occur when terms appear in most/all documents (high DF). In a larger corpus, scores are typically positive.

## WordPress Integration Points

### Database Schema

Expected table structure:

```sql
CREATE TABLE wp_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(255),
    product_short_description TEXT,
    expanded_description TEXT,
    -- FULLTEXT indexes added dynamically via plugin UI
);
```

### AJAX Handler

```php
public function ajax_calculate() {
    check_ajax_referer('bm25_calc_nonce', 'nonce');
    
    $query = sanitize_text_field($_POST['query']);
    $documents = array_map('sanitize_text_field', $_POST['documents']);
    $k1 = floatval($_POST['k1']);
    $b = floatval($_POST['b']);
    
    $bm25 = new BM25_Algorithm($documents, $k1, $b);
    $results = $bm25->search($query);
    
    wp_send_json_success([
        'results' => $results,
        'total_docs' => count($documents),
        'avgdl' => $bm25->getAvgdl()
    ]);
}
```

### Admin Page Features

1. **Index Management**
   - View existing FULLTEXT indexes
   - Create new indexes from predefined combinations
   - Delete outdated indexes

2. **Search Testing**
   - Live query testing with real product data
   - Adjustable k1 and b parameters (sliders)
   - Visual result rankings (🥇🥈🥉)
   - Detailed scoring breakdown per term

3. **Document Source**
   - Auto-loads from `wp_products` table (max 100 rows)
   - Falls back to manual text entry
   - Combines all indexed fields for comprehensive search

## Performance Considerations

### When to Use BM25 vs MySQL FULLTEXT

**Use BM25 (this plugin) when:**
- You need precise relevance ranking
- Testing/tuning search parameters
- Educational purposes
- Small to medium datasets (<10,000 docs)

**Use MySQL FULLTEXT when:**
- You need fast search at scale
- Basic relevance ranking is sufficient
- Production systems with large datasets

**Best approach:** Use this plugin to understand and tune parameters, then implement similar logic with MySQL FULLTEXT for production.

### Optimization Tips

```php
// Cache average document length (doesn't change often)
$this->avgdl = get_transient('bm25_avgdl') ?: $this->calculateAndCacheAvgdl();

// Use WordPress object cache for document frequency
$cache_key = 'bm25_df_' . md5($term);
$df = wp_cache_get($cache_key);
if (false === $df) {
    $df = $this->documentFrequency($term);
    wp_cache_set($cache_key, $df, '', 3600);
}
```

## Common Use Cases

### Product Search
```php
// Search product names and descriptions
$products = $wpdb->get_results("SELECT * FROM wp_products LIMIT 100");
$documents = array_map(function($p) {
    return implode(' ', [$p->product_name, $p->product_short_description]);
}, $products);

$bm25 = new BM25_Algorithm($documents, 1.5, 0.75);
$results = $bm25->search('wireless bluetooth speaker');
```

### Content Search
```php
// Search post titles and content
$posts = get_posts(['numberposts' => 100]);
$documents = array_map(function($p) {
    return $p->post_title . ' ' . $p->post_content;
}, $posts);

$bm25 = new BM25_Algorithm($documents, 1.2, 0.8);
$results = $bm25->search('wordpress development tips');
```

## Extending the Plugin

### Add Custom Fields

```php
private function initialize_index_definitions() {
    $fields = [
        'product_name',
        'product_short_description',
        'expanded_description',
        'sku',              // Add SKU field
        'category_name'     // Add category field
    ];
    // ... rest of index definitions
}
```

### Add Field Weighting

```php
public function score_with_weights($query, $fields_data, $weights) {
    $total_score = 0;
    
    foreach ($fields_data as $field => $content) {
        $weight = $weights[$field] ?? 1.0;
        $score_data = $this->score($query, $content);
        $total_score += $score_data['score'] * $weight;
    }
    
    return $total_score;
}

// Usage
$weights = [
    'product_name' => 2.0,              // Title is 2x important
    'product_short_description' => 1.5,
    'expanded_description' => 1.0
];
```

### Integration with WooCommerce

```php
// Hook into WooCommerce product search
add_filter('woocommerce_product_query', function($query) {
    if (is_search() && $query->is_main_query()) {
        // Get search term
        $search_term = get_query_var('s');
        
        // Run BM25 on products
        $product_ids = $this->search_products_with_bm25($search_term);
        
        // Override query
        $query->set('post__in', $product_ids);
        $query->set('orderby', 'post__in');
    }
});
```

## Testing and Debugging

### Enable Detailed Logging

```php
public function score($query, $document, $docIndex = 0) {
    // ... existing code ...
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("BM25 Score for Doc {$docIndex}: " . json_encode([
            'query' => $query,
            'score' => $score,
            'details' => $details
        ]));
    }
    
    return compact('score', 'details');
}
```

### Unit Testing Example

```php
function test_bm25_basic_scoring() {
    $docs = [
        'The quick brown fox',
        'The lazy dog',
        'Quick brown animals'
    ];
    
    $bm25 = new BM25_Algorithm($docs);
    $results = $bm25->search('quick brown');
    
    // First result should be doc 1 (contains both terms)
    assertEquals(1, $results[0]['index']);
    assertTrue($results[0]['score'] > 0);
}
```

## Troubleshooting

### Issue: All scores are negative

**Cause:** Query terms appear in most/all documents (high document frequency)

**Solution:** 
- Increase corpus size
- Use more specific search terms
- Add field weighting to boost important fields

### Issue: Unexpected ranking order

**Cause:** Default parameters (k1=1.5, b=0.75) don't fit your content

**Solution:**
- Adjust k1 for your document lengths
- Adjust b based on length variation
- Use the admin UI to test different values

### Issue: Slow performance

**Cause:** Calculating TF/DF for every query is expensive

**Solution:**
- Cache document frequencies
- Pre-process and tokenize documents
- Use MySQL FULLTEXT for production
- Limit document corpus size

## Resources

- [Original BM25 Paper](https://en.wikipedia.org/wiki/Okapi_BM25)
- [Understanding TF-IDF and BM25](https://kmwllc.com/index.php/2020/03/20/understanding-tf-idf-and-bm-25/)
- [Elasticsearch BM25 Implementation](https://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules-similarity.html)

## License

GPL v2 or later (matches WordPress)

---

**Author's Note:** This plugin is designed for learning and testing. For production e-commerce sites with thousands of products, consider Elasticsearch, Algolia, or properly optimized MySQL FULLTEXT search with BM25-inspired scoring.
