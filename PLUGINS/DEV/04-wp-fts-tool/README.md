# WordPress Full-Text Search (FTS) Teaching Plugin

An interactive WordPress plugin that demonstrates and teaches six different Full-Text Search algorithms through a hands-on interface. This plugin serves as both a learning tool and a reference implementation for developers interested in search algorithms.

## Table of Contents

- [Overview](#overview)
- [Plugin Architecture](#plugin-architecture)
- [FTS Methods Explained](#fts-methods-explained)
- [Installation](#installation)
- [Usage](#usage)
- [Developer Guide](#developer-guide)
- [Practical Applications](#practical-applications)

---

## Overview

Full-Text Search (FTS) refers to techniques for searching text content in documents, databases, or other text collections. Unlike simple string matching, FTS methods use sophisticated algorithms to rank and retrieve the most relevant documents based on a user's query.

This plugin demonstrates six different FTS methods with real-time comparison capabilities, making it an excellent teaching tool for understanding search algorithms.

---

## Plugin Architecture

### Core Components

The plugin follows WordPress best practices with a single-class architecture that encapsulates all functionality.

#### Main Class: `FTS_Teaching_Plugin`

**Location**: `04-wp-fts-tool.php`

**Responsibilities**:
- Admin interface management
- AJAX request handling
- Search algorithm implementations
- Document corpus management

### File Structure

```
fts-teaching-plugin/
│
├── 04-wp-fts-tool.php          # Main plugin file (all-in-one)
│   ├── Plugin header
│   ├── Class definition
│   ├── Search methods
│   └── Admin UI (inline HTML/CSS/JS)
│
└── README.md                    # This file
```

### WordPress Hooks & Integration

#### Admin Hooks

```php
add_action('admin_menu', array($this, 'add_admin_menu'));
add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
add_action('wp_ajax_fts_search', array($this, 'handle_search'));
```

**Hook Breakdown**:

1. **`admin_menu`** (Line 23)
   - Registers the plugin's admin page
   - Creates a top-level menu item with dashicons-search icon
   - Menu position: 3.4 (below Dashboard)
   - Required capability: `manage_options`

2. **`admin_enqueue_scripts`** (Line 24)
   - Conditionally loads assets only on the plugin's admin page
   - Prevents unnecessary resource loading on other admin pages
   - Enqueues CSS and JavaScript files
   - Localizes JavaScript with AJAX URL and nonce

3. **`wp_ajax_fts_search`** (Line 25)
   - Handles AJAX search requests from authenticated users
   - Endpoint: `admin-ajax.php?action=fts_search`
   - Returns JSON responses

### Class Structure

#### Properties

```php
private $documents;  // Array of document objects
```

**Document Schema**:
```php
array(
    'id' => int,        // Unique identifier
    'title' => string,  // Document title
    'content' => string // Full document text
)
```

#### Constructor Flow

```php
public function __construct() {
    // 1. Register admin menu
    add_action('admin_menu', array($this, 'add_admin_menu'));
    
    // 2. Register asset enqueuing
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    
    // 3. Register AJAX handler
    add_action('wp_ajax_fts_search', array($this, 'handle_search'));
    
    // 4. Initialize document corpus
    $this->init_documents();
}
```

#### Public Methods

| Method | Purpose | Parameters | Returns |
|--------|---------|------------|---------|
| `add_admin_menu()` | Registers admin page | None | void |
| `enqueue_scripts($hook)` | Loads CSS/JS assets | `$hook` (string) | void |
| `handle_search()` | AJAX request handler | POST: `query`, `method`, `nonce` | JSON |
| `render_admin_page()` | Renders admin UI | None | void (outputs HTML) |

#### Private Methods - Search Algorithms

| Method | Algorithm | Complexity | Lines |
|--------|-----------|------------|-------|
| `search_tf()` | Term Frequency | O(n×m) | 142-168 |
| `search_tfidf()` | TF-IDF | O(n×m) | 170-217 |
| `search_bm25()` | BM25 | O(n×m) | 219-290 |
| `search_natural()` | Positional Search | O(n×m²) | 292-360 |
| `search_boolean()` | Boolean Logic | O(n×m) | 362-431 |
| `search_expansion()` | Query Expansion | O(n×m) | 433-468 |
| `init_documents()` | Corpus initialization | O(1) | 55-108 |

Where:
- `n` = number of documents
- `m` = number of query terms

### AJAX Request Flow

```
User Input (jQuery)
    ↓
$.ajax() call with query + method
    ↓
WordPress admin-ajax.php
    ↓
wp_ajax_fts_search hook
    ↓
handle_search() method
    ↓
check_ajax_referer() - Security validation
    ↓
sanitize_text_field() - Input sanitization
    ↓
Switch/case routing to specific search method
    ↓
Search algorithm execution
    ↓
wp_send_json_success($results)
    ↓
jQuery success callback
    ↓
displayResults() - Render to DOM
```

### Security Measures

1. **Direct Access Prevention**
   ```php
   if (!defined('ABSPATH')) {
       exit;
   }
   ```

2. **Nonce Verification**
   ```php
   check_ajax_referer('fts_search_nonce', 'nonce');
   ```

3. **Input Sanitization**
   ```php
   $query = sanitize_text_field($_POST['query']);
   $method = sanitize_text_field($_POST['method']);
   ```

4. **Capability Checks**
   ```php
   'manage_options'  // Only administrators can access
   ```

### Frontend Architecture (Inline)

#### JavaScript Structure

**Event Handlers**:
- Search button click handler
- Enter key press handler for search input
- AJAX success/error handlers

**Key Functions**:
- `displayResults(results, method)` - Renders search results with rankings

#### CSS Architecture

**Component Classes**:
- `.fts-container` - Main grid layout (70/30 split)
- `.fts-search-panel` - Left column with search form
- `.fts-results-panel` - Right column with results display
- `.fts-result-item` - Individual result card
- `.fts-document` - Document card in corpus view

**Color Scheme**:
- Primary: `#2271b1` (WordPress blue)
- Success: `#46b450` (green)
- Background: `#f5f5f5` (light gray)
- Borders: `#ccc`, `#ddd`

### Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    User Interface (Admin Page)               │
│                                                              │
│  ┌─────────────┐    ┌──────────────┐    ┌───────────────┐  │
│  │ Search Form │───▶│ AJAX Request │───▶│ Results Panel │  │
│  └─────────────┘    └──────────────┘    └───────────────┘  │
│         │                   │                    ▲          │
└─────────┼───────────────────┼────────────────────┼──────────┘
          │                   │                    │
          ▼                   ▼                    │
┌──────────────────┐  ┌──────────────────┐  ┌─────────────┐
│ jQuery Handler   │  │ WordPress AJAX   │  │ JSON        │
│ - Validation     │──▶│ - Nonce Check   │──▶│ Response    │
│ - Serialize Data │  │ - Sanitization  │  │             │
└──────────────────┘  └──────────────────┘  └─────────────┘
                              │
                              ▼
                    ┌──────────────────────┐
                    │ Search Router        │
                    │ (handle_search)      │
                    └──────────────────────┘
                              │
         ┌────────────────────┼────────────────────┐
         ▼                    ▼                    ▼
   ┌──────────┐        ┌──────────┐        ┌──────────┐
   │search_tf │        │search_   │   ...  │search_   │
   │          │        │tfidf     │        │expansion │
   └──────────┘        └──────────┘        └──────────┘
         │                    │                    │
         └────────────────────┼────────────────────┘
                              ▼
                    ┌──────────────────────┐
                    │ Document Corpus      │
                    │ $this->documents[]   │
                    └──────────────────────┘
```

### Document Corpus

The plugin includes 10 pre-defined documents about AI and machine learning topics:

1. **Introduction to Machine Learning** - Comprehensive overview with repeated terms (test TF saturation)
2. **Deep Learning Fundamentals** - Neural networks and modern architectures
3. **Natural Language Processing Overview** - NLP applications and techniques
4. **Computer Vision Applications** - Visual interpretation systems
5. **Reinforcement Learning Basics** - Agent-based learning
6. **Neural Network Architecture** - Network structure and training
7. **Data Preprocessing Techniques** - Data preparation methods
8. **The AI Revolution** - Broad AI impact and ethics
9. **Supervised Learning Methods** - Classification and regression
10. **Unsupervised Learning Techniques** - Clustering and dimensionality reduction

**Design Considerations**:
- Documents vary in length to test normalization
- Some documents have repeated terms (testing saturation)
- Mix of specific and general content (testing IDF)
- Related but distinct topics (testing relevance ranking)

---

## FTS Methods Explained

### 1. Term Frequency (TF)

**Principle**: Counts how often search terms appear in each document.

**Implementation** (Lines 142-168):
```php
private function search_tf($query) {
    $terms = array_map('strtolower', explode(' ', $query));
    $scores = array();
    
    foreach ($this->documents as $doc) {
        $content = strtolower($doc['title'] . ' ' . $doc['content']);
        $score = 0;
        
        foreach ($terms as $term) {
            $score += substr_count($content, $term);
        }
        
        if ($score > 0) {
            $scores[] = array(
                'doc' => $doc,
                'score' => $score,
                'explanation' => "Term frequency: $score occurrences"
            );
        }
    }
    
    usort($scores, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    return $scores;
}
```

**Algorithm Steps**:
1. Tokenize query into terms (lowercase)
2. For each document:
   - Combine title and content
   - Count occurrences of each query term
   - Sum counts for total score
3. Sort by score (descending)

**Characteristics**:
- **Pros**: Simple, fast, intuitive
- **Cons**: Favors long documents, doesn't consider term importance
- **Time Complexity**: O(n×m) where n=docs, m=terms
- **Best For**: Simple keyword counting, quick searches

**Example**: 
```
Query: "machine learning"
Doc A: "machine learning machine learning" → Score: 4
Doc B: "machine learning is useful" → Score: 2
```

### 2. TF-IDF (Term Frequency-Inverse Document Frequency)

**Principle**: Balances term frequency with term rarity across the entire document collection.

**Implementation** (Lines 170-217):
```php
private function search_tfidf($query) {
    // Calculate IDF for each term
    $idf = array();
    $total_docs = count($this->documents);
    
    foreach ($terms as $term) {
        $docs_with_term = 0;
        foreach ($this->documents as $doc) {
            $content = strtolower($doc['title'] . ' ' . $doc['content']);
            if (strpos($content, $term) !== false) {
                $docs_with_term++;
            }
        }
        $idf[$term] = $docs_with_term > 0 ? log($total_docs / $docs_with_term) : 0;
    }
    
    // Calculate TF-IDF scores
    foreach ($this->documents as $doc) {
        foreach ($terms as $term) {
            $tf = substr_count($content, $term);
            $tfidf = $tf * $idf[$term];
            $score += $tfidf;
        }
    }
}
```

**Mathematical Formula**:
```
IDF(term) = log(total_documents / documents_containing_term)
TF-IDF(term, doc) = TF(term, doc) × IDF(term)
Score(doc) = Σ TF-IDF(term, doc) for all query terms
```

**Characteristics**:
- **Pros**: Penalizes common words, rewards rare terms, better relevance
- **Cons**: Doesn't handle term saturation well
- **Time Complexity**: O(n×m) 
- **Best For**: General-purpose search, content discovery

**Example**:
```
Query: "reinforcement learning"
- "reinforcement" appears in 1/10 docs → IDF = log(10/1) = 2.30
- "learning" appears in 8/10 docs → IDF = log(10/8) = 0.22
Result: "reinforcement" weighted much higher
```

### 3. BM25 (Best Match 25)

**Principle**: Advanced probabilistic model with term saturation and document length normalization.

**Implementation** (Lines 219-290):
```php
private function search_bm25($query) {
    $k1 = 1.5;  // Term saturation parameter
    $b = 0.75;  // Length normalization parameter
    
    // Calculate average document length
    $total_length = 0;
    foreach ($this->documents as $doc) {
        $total_length += str_word_count(strtolower($doc['title'] . ' ' . $doc['content']));
    }
    $avg_doc_length = $total_length / count($this->documents);
    
    // BM25 scoring
    foreach ($this->documents as $doc) {
        $doc_length = str_word_count($content);
        $length_norm = 1 - $b + $b * ($doc_length / $avg_doc_length);
        
        foreach ($terms as $term) {
            $tf = substr_count($content, $term);
            $idf = log(($total_docs - $docs_with_term + 0.5) / ($docs_with_term + 0.5));
            $bm25_score = $idf * (($tf * ($k1 + 1)) / ($tf + $k1 * $length_norm));
            $score += $bm25_score;
        }
    }
}
```

**Mathematical Formula**:
```
BM25(doc, term) = IDF(term) × (TF × (k1 + 1)) / (TF + k1 × (1 - b + b × (doc_length / avg_doc_length)))
```

**Parameters**:
- **k1 = 1.5**: Controls term saturation (typical range: 1.2-2.0)
- **b = 0.75**: Controls length normalization (typical range: 0.5-1.0)

**Characteristics**:
- **Pros**: Industry-standard, handles saturation, length-normalized
- **Cons**: More complex, requires parameter tuning
- **Time Complexity**: O(n×m)
- **Best For**: Production search systems, e-commerce

**Saturation Effect**:
```
TF=1  → Score ≈ 0.6
TF=5  → Score ≈ 0.9
TF=20 → Score ≈ 0.98
TF=50 → Score ≈ 0.99  (diminishing returns)
```

### 4. Natural Language (Positional) Search

**Principle**: Considers proximity and positional relationships between query terms.

**Implementation** (Lines 292-360):
```php
private function search_natural($query) {
    foreach ($this->documents as $doc) {
        // Base TF score
        foreach ($terms as $term) {
            $score += substr_count($content, $term);
        }
        
        // Proximity bonus
        $words = str_word_count($content, 1);
        for ($i = 0; $i < count($words) - 1; $i++) {
            if (in_array(strtolower($words[$i]), $terms)) {
                for ($j = $i + 1; $j < min($i + 6, count($words)); $j++) {
                    if (in_array(strtolower($words[$j]), $terms)) {
                        $distance = $j - $i;
                        $proximity_bonus = 10 / $distance;
                        $score += $proximity_bonus;
                    }
                }
            }
        }
    }
}
```

**Proximity Calculation**:
```
Distance = 1 word  → Bonus = 10.0
Distance = 2 words → Bonus = 5.0
Distance = 3 words → Bonus = 3.3
Distance = 4 words → Bonus = 2.5
Distance = 5 words → Bonus = 2.0
```

**Characteristics**:
- **Pros**: Finds phrases, considers context, better for multi-word queries
- **Cons**: More computationally expensive
- **Time Complexity**: O(n×m²)
- **Best For**: Phrase searches, questions, semantic queries

**Example**:
```
Query: "neural network training"
Doc A: "neural network training techniques" → High proximity bonus
Doc B: "neural networks are used for training" → Lower proximity bonus
```

### 5. Boolean Search

**Principle**: Strict logical matching using AND, OR, and NOT operators.

**Implementation** (Lines 362-431):
```php
private function search_boolean($query) {
    // Parse query for operators
    if (stripos($query, ' AND ') !== false) {
        // All terms must be present
        $terms = preg_split('/\s+AND\s+/i', $query);
        foreach ($this->documents as $doc) {
            $all_present = true;
            foreach ($terms as $term) {
                if (strpos($content, trim(strtolower($term))) === false) {
                    $all_present = false;
                    break;
                }
            }
            if ($all_present) {
                $scores[] = array('doc' => $doc, 'score' => 1, ...);
            }
        }
    }
    // Similar logic for OR and NOT
}
```

**Operators**:

| Operator | Logic | Example | Matches |
|----------|-------|---------|---------|
| AND | All terms required | "machine AND learning" | Docs with both terms |
| OR | Any term accepted | "machine OR learning" | Docs with either term |
| NOT | Exclude term | "machine NOT deep" | "machine" but not "deep" |

**Characteristics**:
- **Pros**: Precise control, predictable results
- **Cons**: No ranking, binary results, requires operator knowledge
- **Time Complexity**: O(n×m)
- **Best For**: Legal/medical searches, strict filtering

**Example Queries**:
```
"machine AND learning" → Must have both
"supervised OR unsupervised" → Must have at least one
"learning NOT deep" → "learning" but exclude "deep"
```

### 6. Query Expansion

**Principle**: Expands abbreviations and acronyms to improve recall.

**Implementation** (Lines 433-468):
```php
private function search_expansion($query) {
    $expansions = array(
        'ai' => array('artificial intelligence', 'machine learning'),
        'ml' => array('machine learning'),
        'nlp' => array('natural language processing'),
        'dl' => array('deep learning'),
        'nn' => array('neural network')
    );
    
    $expanded_terms = explode(' ', strtolower($query));
    foreach ($expansions as $abbrev => $full_terms) {
        if (in_array($abbrev, $expanded_terms)) {
            $expanded_terms = array_merge($expanded_terms, $full_terms);
        }
    }
    
    // Score documents with expanded terms
    foreach ($expanded_terms as $term) {
        $score += substr_count($content, $term);
    }
}
```

**Expansion Mappings**:
```php
'ai'  → ['artificial intelligence', 'machine learning']
'ml'  → ['machine learning']
'nlp' → ['natural language processing']
'dl'  → ['deep learning']
'nn'  → ['neural network']
```

**Characteristics**:
- **Pros**: Finds synonyms, improves recall, handles jargon
- **Cons**: May reduce precision, requires maintenance
- **Time Complexity**: O(n×m)
- **Best For**: Technical domains, acronym-heavy fields

**Example**:
```
Query: "AI"
Expanded to: ["ai", "artificial intelligence", "machine learning"]
Matches: Docs with any of these terms
```

---

## Installation

### Manual Installation

1. **Download** the plugin file `04-wp-fts-tool.php`

2. **Upload** to WordPress:
   ```
   wp-content/
   └── plugins/
       └── fts-teaching-plugin/
           └── 04-wp-fts-tool.php
   ```

3. **Activate** the plugin:
   - Go to WordPress Admin → Plugins
   - Find "✅ 04 FTS TOOL"
   - Click "Activate"

### Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- User role: Administrator

---

## Usage

### Accessing the Plugin

1. Log in to WordPress Admin
2. Look for **"04 FTS TOOL"** in the left sidebar menu (with search icon)
3. Click to open the search interface

### Running Searches

1. **Enter a query** in the search box
   - Examples: "machine learning", "neural network", "AI"

2. **Select a search method**:
   - Term Frequency (TF)
   - TF-IDF
   - BM25
   - Natural Language
   - Boolean Search
   - Query Expansion

3. **Click "Search"** or press Enter

4. **View results**:
   - Ranked list with scores
   - Detailed explanations for each ranking
   - Full document content

### Example Queries

**For TF/TF-IDF/BM25**:
```
machine learning
neural network
reinforcement learning
```

**For Natural Language**:
```
neural network training
deep learning applications
```

**For Boolean Search**:
```
machine AND learning
supervised OR unsupervised
learning NOT deep
```

**For Query Expansion**:
```
AI
ML
NLP
```

---

## Developer Guide

### Extending the Plugin

#### Adding New Search Methods

1. **Create a new search method**:
```php
private function search_custom($query) {
    $terms = array_map('strtolower', explode(' ', $query));
    $scores = array();
    
    foreach ($this->documents as $doc) {
        // Your algorithm here
        $score = 0;
        
        if ($score > 0) {
            $scores[] = array(
                'doc' => $doc,
                'score' => $score,
                'explanation' => "Custom: ..."
            );
        }
    }
    
    usort($scores, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    return $scores;
}
```

2. **Add to the switch statement** in `handle_search()`:
```php
case 'custom':
    $results = $this->search_custom($query);
    break;
```

3. **Add to the dropdown** in `render_admin_page()`:
```html
<option value="custom">Custom Method</option>
```

#### Adding Documents

Modify the `init_documents()` method:

```php
private function init_documents() {
    $this->documents = array(
        // Existing documents...
        array(
            'id' => 11,
            'title' => 'Your New Document',
            'content' => 'Your content here...'
        )
    );
}
```

#### Customizing UI

The plugin uses inline CSS (lines 520-689). To customize:

1. Find the `<style>` section in `render_admin_page()`
2. Modify CSS classes as needed
3. Common classes:
   - `.fts-container` - Main layout
   - `.fts-result-item` - Result cards
   - `.fts-document` - Document corpus cards

#### Adding External Assets

To use external CSS/JS files instead of inline:

1. **Create asset files**:
   ```
   assets/
   ├── style.css
   └── script.js
   ```

2. **Update `enqueue_scripts()`**:
   ```php
   wp_enqueue_style('fts-teaching-style', 
       plugin_dir_url(__FILE__) . 'assets/style.css', 
       array(), '1.0.0'
   );
   wp_enqueue_script('fts-teaching-script', 
       plugin_dir_url(__FILE__) . 'assets/script.js', 
       array('jquery'), '1.0.0', true
   );
   ```

3. **Remove inline styles/scripts** from `render_admin_page()`

### Debugging

Enable WordPress debugging in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Add debug logging:

```php
error_log('FTS Query: ' . $query);
error_log('FTS Results: ' . print_r($results, true));
```

### Testing

Test each method with various queries:

```php
// Test TF saturation
"machine learning machine learning machine learning"

// Test IDF weighting
"reinforcement" (rare) vs "learning" (common)

// Test BM25 length normalization
Short vs long documents

// Test proximity
"neural network training" (phrase)

// Test Boolean logic
"machine AND learning NOT deep"

// Test expansion
"AI" → should expand to "artificial intelligence"
```

---

## Practical Applications

These FTS methods are used in various real-world applications:

| Method | Real-World Use Cases |
|--------|---------------------|
| **TF** | Log analysis, simple keyword counting, initial filtering |
| **TF-IDF** | Academic search, email classification, document similarity |
| **BM25** | Google Search, Elasticsearch, production search engines |
| **Natural** | Google phrase search, question answering, semantic search |
| **Boolean** | Legal databases (Westlaw, LexisNexis), medical records (PubMed) |
| **Expansion** | Medical search (ICD codes), technical documentation, acronym-heavy domains |

### Industry Examples

- **Search Engines**: Google, Bing use BM25 variants
- **E-commerce**: Amazon, eBay use natural language + expansion
- **Legal**: Westlaw uses Boolean search
- **Academic**: Google Scholar uses TF-IDF + BM25
- **Enterprise**: Elasticsearch supports all methods

---

## When to Use Each Method

| Method | Best For | Avoid When |
|--------|----------|------------|
| **TF** | Quick prototypes, simple counting | Need relevance ranking |
| **TF-IDF** | General search, content discovery | Phrases are important |
| **BM25** | Production systems, high-quality results | Simplicity is paramount |
| **Natural** | Multi-word queries, Q&A systems | Single keyword searches |
| **Boolean** | Precise filtering, expert users | Novice users, ranking needed |
| **Expansion** | Acronym-heavy domains, synonyms | Precision is critical |

### Comparison Table

| Feature | TF | TF-IDF | BM25 | Natural | Boolean | Expansion |
|---------|----|----|----|----|----|----|
| **Complexity** | Low | Medium | High | High | Medium | Low |
| **Ranking Quality** | Poor | Good | Excellent | Very Good | None | Medium |
| **Speed** | Fast | Fast | Medium | Slow | Fast | Fast |
| **Precision** | Low | Medium | High | High | Very High | Low |
| **Recall** | Medium | Medium | Medium | High | Low | Very High |

---

## Performance Considerations

### Time Complexity Summary

```
TF:        O(n×m)    - n docs, m terms
TF-IDF:    O(n×m)    - Same as TF
BM25:      O(n×m)    - Adds normalization overhead
Natural:   O(n×m²)   - Proximity checking is expensive
Boolean:   O(n×m)    - Simple string matching
Expansion: O(n×m)    - Same as TF with more terms
```

### Optimization Tips

1. **For Large Corpora**:
   - Use BM25 for best quality/speed tradeoff
   - Pre-compute IDF values
   - Cache document lengths

2. **For Real-Time Search**:
   - Use TF for instant results
   - Consider incremental indexing

3. **For Best Quality**:
   - Combine BM25 + Natural Language
   - Add query expansion for recall
   - Use Boolean for filtering

---

## License

GPL v2 or later

---

## Credits

- **WordPress**: Platform
- **Okapi BM25**: Robertson & Zaragoza (2009)
- **TF-IDF**: Salton & McGill (1983)

---

## Support

For issues, questions, or contributions:
- WordPress Support Forums
- GitHub Issues (if applicable)

---

## Changelog

### Version 1.0.0
- Initial release
- Six FTS methods implemented
- Interactive admin interface
- 10-document corpus
- Real-time AJAX search

---

## Further Reading

### Academic Papers
- Robertson & Zaragoza (2009): "The Probabilistic Relevance Framework: BM25 and Beyond"
- Salton & McGill (1983): "Introduction to Modern Information Retrieval"

### Documentation
- Elasticsearch: [BM25 Similarity](https://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules-similarity.html)
- Lucene: [TF-IDF Scoring](https://lucene.apache.org/core/9_0_0/core/org/apache/lucene/search/similarities/TFIDFSimilarity.html)

### Online Resources
- Wikipedia: [Okapi BM25](https://en.wikipedia.org/wiki/Okapi_BM25)
- Stanford NLP: [IR Book](https://nlp.stanford.edu/IR-book/)
