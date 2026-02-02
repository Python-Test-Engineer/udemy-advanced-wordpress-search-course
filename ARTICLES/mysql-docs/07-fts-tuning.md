# MySQL Full-Text Search (FTS) Fine-Tuning Guide

## Introduction

Full-Text Search (FTS) in MySQL allows you to efficiently search text content in your database. While the default settings work well for most applications, MySQL provides several configuration options to optimize search behavior for your specific needs.

**Important Note:** MySQL's FTS is already well-tuned out of the box. Only modify these settings if you have a specific reason and understand the implications. Most changes require rebuilding your FULLTEXT indexes and restarting the server.

## 1. Word Length Configuration

### What It Does
Controls which words get indexed based on their length. By default, very short words (like "a", "in") and extremely long words are excluded from the index.

### Configuration Variables

**For InnoDB tables:**
- `innodb_ft_min_token_size` - Minimum word length (default: 3)
- `innodb_ft_max_token_size` - Maximum word length (default: 84)

**For MyISAM tables:**
- `ft_min_word_len` - Minimum word length (default: 4)
- `ft_max_word_len` - Maximum word length (default: 84)

### Example Use Case
If you need to search for two-letter words like "AI" or "UK", you'd need to lower the minimum token size.

### How to Configure

Add to your MySQL configuration file (e.g., `/etc/my.cnf` or `my.ini` on Windows):

```ini
[mysqld]
innodb_ft_min_token_size=2
ft_min_word_len=2
```

**Important:** After changing these settings:
1. Restart the MySQL server
2. Rebuild all FULLTEXT indexes (see Rebuilding Indexes section)

### PHP Example - Rebuilding Index After Configuration Change

```php
<?php
// Connect to MySQL
$mysqli = new mysqli("localhost", "root", "password", "mydb");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Rebuild the FULLTEXT index
// Step 1: Drop the existing index
$sql = "ALTER TABLE articles DROP INDEX content_idx";
if ($mysqli->query($sql)) {
    echo "Index dropped successfully.\n";
} else {
    echo "Error dropping index: " . $mysqli->error . "\n";
}

// Step 2: Recreate the index
$sql = "ALTER TABLE articles ADD FULLTEXT INDEX content_idx (content)";
if ($mysqli->query($sql)) {
    echo "Index recreated successfully.\n";
} else {
    echo "Error creating index: " . $mysqli->error . "\n";
}

$mysqli->close();
?>
```

### PHP Example - Searching with New Configuration

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "mydb");

// Now you can search for 2-letter words like "AI"
$search_term = "AI technology";
$sql = "SELECT * FROM articles 
        WHERE MATCH(title, content) AGAINST(? IN NATURAL LANGUAGE MODE)";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $search_term);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "Title: " . htmlspecialchars($row['title']) . "<br>";
    echo "Content: " . htmlspecialchars($row['content']) . "<br><br>";
}

$stmt->close();
$mysqli->close();
?>
```

## 2. Stopword Configuration

### What It Does
Stopwords are common words (like "the", "and", "is") that are excluded from the search index because they appear so frequently they're not useful for searching.

### Configuration Variables

**For InnoDB:**
- `innodb_ft_enable_stopword` - Enable/disable stopword filtering (default: ON)
- `innodb_ft_server_stopword_table` - Custom server-level stopword table
- `innodb_ft_user_stopword_table` - Custom session-level stopword table

**For MyISAM:**
- `ft_stopword_file` - Path to custom stopword file

### Example Use Case
If you're building a search engine for legal documents where words like "the" and "and" might be significant, you might want to disable stopwords or create a custom list.

### PHP Example - Creating Custom Stopword Table (InnoDB)

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "mydb");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Step 1: Create a custom stopword table
$sql = "CREATE TABLE IF NOT EXISTS custom_stopwords (
    value VARCHAR(30)
) ENGINE = INNODB";

if ($mysqli->query($sql)) {
    echo "Stopword table created successfully.\n";
} else {
    echo "Error creating table: " . $mysqli->error . "\n";
}

// Step 2: Add your custom stopwords
$stopwords = ['the', 'and', 'or', 'but', 'is', 'are', 'was', 'were'];

$stmt = $mysqli->prepare("INSERT INTO custom_stopwords (value) VALUES (?)");
foreach ($stopwords as $word) {
    $stmt->bind_param("s", $word);
    $stmt->execute();
}
$stmt->close();

echo "Custom stopwords added.\n";

// Step 3: Configure MySQL to use the custom stopword table
// (This requires appropriate privileges and a server restart)
$sql = "SET GLOBAL innodb_ft_user_stopword_table = 'mydb/custom_stopwords'";
if ($mysqli->query($sql)) {
    echo "Stopword table configured.\n";
} else {
    echo "Error setting stopword table: " . $mysqli->error . "\n";
}

$mysqli->close();
?>
```

### PHP Example - Disabling Stopwords Temporarily

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "mydb");

// Disable stopwords for the current session
$mysqli->query("SET SESSION innodb_ft_enable_stopword = OFF");

// Now create a FULLTEXT index without stopword filtering
$mysqli->query("CREATE TABLE temp_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT,
    FULLTEXT(content)
) ENGINE=InnoDB");

// After creating the index, you can re-enable stopwords
$mysqli->query("SET SESSION innodb_ft_enable_stopword = ON");

$mysqli->close();
?>
```

**Note:** After changing stopword settings, rebuild your FULLTEXT indexes.

## 3. Natural Language Search Threshold (MyISAM Only)

### What It Does
By default, MyISAM ignores words that appear in more than 50% of rows. This prevents common words from dominating search results.

### Why This Exists
If a word appears in more than half your documents, it's not useful for distinguishing between documents.

### Example
If you have 1000 articles and the word "MySQL" appears in 600 of them, natural language search will ignore "MySQL" in queries.

### PHP Example - Understanding the 50% Threshold

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "mydb");

// Check how many rows contain a specific word
$word = "mysql";
$sql = "SELECT COUNT(*) as count FROM articles WHERE content LIKE ?";
$stmt = $mysqli->prepare($sql);
$like_term = "%$word%";
$stmt->bind_param("s", $like_term);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$word_count = $row['count'];

// Get total row count
$total_result = $mysqli->query("SELECT COUNT(*) as total FROM articles");
$total_row = $total_result->fetch_assoc();
$total_count = $total_row['total'];

$percentage = ($word_count / $total_count) * 100;

echo "Word '$word' appears in $word_count out of $total_count articles ($percentage%).\n";

if ($percentage > 50) {
    echo "This word will be ignored in natural language search!\n";
    echo "Use IN BOOLEAN MODE instead.\n";
}

$stmt->close();
$mysqli->close();
?>
```

### PHP Example - Using Boolean Mode to Bypass Threshold

**Option 1: Boolean Mode Search** (Recommended)
Boolean mode doesn't apply the 50% threshold:

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "mydb");

$search_term = "mysql";

// Boolean search ignores the 50% threshold
$sql = "SELECT *, MATCH(content) AGAINST(? IN BOOLEAN MODE) AS relevance 
        FROM articles 
        WHERE MATCH(content) AGAINST(? IN BOOLEAN MODE)
        ORDER BY relevance DESC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ss", $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Search Results for: " . htmlspecialchars($search_term) . "</h2>";

while ($row = $result->fetch_assoc()) {
    echo "<div>";
    echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
    echo "<p>" . htmlspecialchars(substr($row['content'], 0, 200)) . "...</p>";
    echo "<small>Relevance: " . $row['relevance'] . "</small>";
    echo "</div><hr>";
}

$stmt->close();
$mysqli->close();
?>
```

## 4. Boolean Search Operators (MyISAM Only)

### What It Does
Allows you to customize the operators used in Boolean full-text searches.

### Default Operators
- `+` - Word must be present (AND)
- `-` - Word must not be present (NOT)
- `>` - Increase word's relevance
- `<` - Decrease word's relevance
- `()` - Group words into subexpressions
- `~` - Negate word's contribution to relevance
- `*` - Wildcard (must be at end of word)
- `""` - Phrase search (exact match)

### Configuration Variable
`ft_boolean_syntax` - Can be changed at runtime (no restart needed)

### PHP Example - Viewing Current Boolean Syntax

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "mydb");

// View current boolean syntax
$result = $mysqli->query("SHOW VARIABLES LIKE 'ft_boolean_syntax'");
$row = $result->fetch_assoc();

echo "Current boolean syntax: " . $row['Value'] . "\n";

$mysqli->close();
?>
```

### PHP Example - Advanced Boolean Searches

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "mydb");

// Example 1: Must include "mysql", must exclude "oracle"
$search1 = "+mysql -oracle";
echo "<h3>Search: $search1</h3>";
$sql = "SELECT * FROM articles 
        WHERE MATCH(content) AGAINST(? IN BOOLEAN MODE)";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $search1);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    echo "- " . htmlspecialchars($row['title']) . "<br>";
}
$stmt->close();

echo "<br>";

// Example 2: Phrase search
$search2 = '"full text search"';
echo "<h3>Search: $search2</h3>";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $search2);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    echo "- " . htmlspecialchars($row['title']) . "<br>";
}
$stmt->close();

echo "<br>";

// Example 3: Wildcard search
$search3 = "data*"; // matches data, database, databases, etc.
echo "<h3>Search: $search3</h3>";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $search3);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    echo "- " . htmlspecialchars($row['title']) . "<br>";
}
$stmt->close();

echo "<br>";

// Example 4: Complex boolean query
$search4 = "+(mysql database) -oracle";
echo "<h3>Search: $search4</h3>";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $search4);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    echo "- " . htmlspecialchars($row['title']) . "<br>";
}
$stmt->close();

$mysqli->close();
?>
```

## 5. Character Set Modifications

### What It Does
Defines which characters are considered "word characters" versus delimiters. By default, hyphens and most punctuation split words.

### Example Problem
By default, "full-text" is treated as two words: "full" and "text". If you want to treat hyphens as part of words, you need to modify the character set configuration.

### Three Methods to Modify

#### Method 1: Modify MySQL Source Code (Advanced)
Edit `storage/innobase/handler/ha_innodb.cc` (InnoDB) or `storage/myisam/ftdefs.h` (MyISAM) and recompile MySQL.

#### Method 2: Modify Character Set XML Files (No Recompilation)
Edit the character set definition files to include additional characters as "letters".

#### Method 3: Add Custom Collation (Recommended)
Create a new collation for full-text indexing with custom character definitions.

### PHP Example - Working with Hyphenated Words

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "mydb");

// Problem: Searching for "full-text" might not work as expected
$search = "full-text";
$sql = "SELECT * FROM articles 
        WHERE MATCH(content) AGAINST(? IN BOOLEAN MODE)";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Results for 'full-text' (may be treated as two words):</h3>";
while ($row = $result->fetch_assoc()) {
    echo "- " . htmlspecialchars($row['title']) . "<br>";
}

// Workaround: Search for both variations
$search2 = '+full +text';
echo "<h3>Workaround - searching for both words:</h3>";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $search2);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "- " . htmlspecialchars($row['title']) . "<br>";
}

$stmt->close();
$mysqli->close();
?>
```

## 6. Optimizing InnoDB Full-Text Indexes

### What It Does
Removes deleted Document IDs and consolidates multiple entries for the same word, improving search performance.

### When to Optimize
- After bulk DELETE operations
- After many INSERT/UPDATE operations
- Periodically for maintenance

### PHP Example - Optimizing Full-Text Index

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "mydb");

// Enable full-text only optimization
$mysqli->query("SET GLOBAL innodb_optimize_fulltext_only = ON");

// Run optimization
$result = $mysqli->query("OPTIMIZE TABLE articles");
$row = $result->fetch_assoc();

echo "Optimization result: " . $row['Msg_text'] . "\n";

// Disable full-text only optimization (return to normal)
$mysqli->query("SET GLOBAL innodb_optimize_fulltext_only = OFF");

$mysqli->close();
?>
```

### PHP Example - Staged Optimization for Large Tables

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "mydb");

// For very large tables, optimize in stages
// This optimizes 2000 words at a time (default)
$mysqli->query("SET GLOBAL innodb_optimize_fulltext_only = ON");

// You can adjust how many words to optimize per run
$mysqli->query("SET GLOBAL innodb_ft_num_word_optimize = 500");

// Run OPTIMIZE multiple times to process the full index
for ($i = 1; $i <= 5; $i++) {
    echo "Optimization run $i...\n";
    $mysqli->query("OPTIMIZE TABLE articles");
    sleep(1); // Brief pause between runs
}

// Reset to defaults
$mysqli->query("SET GLOBAL innodb_ft_num_word_optimize = 2000");
$mysqli->query("SET GLOBAL innodb_optimize_fulltext_only = OFF");

$mysqli->close();
?>
``

## 7. Rebuilding Full-Text Indexes

### When to Rebuild
You must rebuild FULLTEXT indexes after changing:
- `innodb_ft_min_token_size` or `ft_min_word_len`
- `innodb_ft_max_token_size` or `ft_max_word_len`
- `innodb_ft_server_stopword_table` or `innodb_ft_user_stopword_table`
- `innodb_ft_enable_stopword` or `ft_stopword_file`
- `ngram_token_size`

### PHP Example - Complete Rebuild Process

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "mydb");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$table = "articles";
$column = "content";
$index_name = "content_idx";

echo "Starting index rebuild process...\n";

// Step 1: Drop the existing FULLTEXT index
echo "Dropping existing index...\n";
$sql = "ALTER TABLE $table DROP INDEX $index_name";
if ($mysqli->query($sql)) {
    echo "✓ Index dropped successfully.\n";
} else {
    echo "✗ Error dropping index: " . $mysqli->error . "\n";
    exit;
}

// Step 2: Recreate the FULLTEXT index
echo "Creating new index...\n";
$sql = "ALTER TABLE $table ADD FULLTEXT INDEX $index_name ($column)";
if ($mysqli->query($sql)) {
    echo "✓ Index created successfully.\n";
} else {
    echo "✗ Error creating index: " . $mysqli->error . "\n";
    exit;
}

echo "\nIndex rebuild complete!\n";

$mysqli->close();
?>
```

### PHP Example - Rebuilding Multiple Indexes

```php
<?php
$mysqli = new mysqli("localhost", "root", "password", "mydb");

// Define all tables and indexes that need rebuilding
$indexes = [
    ['table' => 'articles', 'index' => 'content_idx', 'columns' => 'title, content'],
    ['table' => 'blog_posts', 'index' => 'post_idx', 'columns' => 'body'],
    ['table' => 'products', 'index' => 'description_idx', 'columns' => 'description']
];

foreach ($indexes as $idx) {
    echo "Processing {$idx['table']}...\n";
    
    // Drop index
    $sql = "ALTER TABLE {$idx['table']} DROP INDEX {$idx['index']}";
    if ($mysqli->query($sql)) {
        echo "  ✓ Dropped {$idx['index']}\n";
    } else {
        echo "  ✗ Error: " . $mysqli->error . "\n";
        continue;
    }
    
    // Recreate index
    $sql = "ALTER TABLE {$idx['table']} ADD FULLTEXT INDEX {$idx['index']} ({$idx['columns']})";
    if ($mysqli->query($sql)) {
        echo "  ✓ Created {$idx['index']}\n";
    } else {
        echo "  ✗ Error: " . $mysqli->error . "\n";
    }
    
    echo "\n";
}

echo "All indexes rebuilt!\n";

$mysqli->close();
?>
```

## 8. Practical Full-Text Search Examples

### PHP Example - Building a Simple Search Function

```php
<?php
class FullTextSearch {
    private $mysqli;
    
    public function __construct($host, $user, $pass, $db) {
        $this->mysqli = new mysqli($host, $user, $pass, $db);
        if ($this->mysqli->connect_error) {
            die("Connection failed: " . $this->mysqli->connect_error);
        }
    }
    
    /**
     * Natural language search
     */
    public function naturalSearch($query, $table, $columns) {
        $sql = "SELECT *, 
                MATCH($columns) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance 
                FROM $table 
                WHERE MATCH($columns) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevance DESC";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("ss", $query, $query);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Boolean search with operators
     */
    public function booleanSearch($query, $table, $columns) {
        $sql = "SELECT *, 
                MATCH($columns) AGAINST(? IN BOOLEAN MODE) AS relevance 
                FROM $table 
                WHERE MATCH($columns) AGAINST(? IN BOOLEAN MODE)
                ORDER BY relevance DESC";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("ss", $query, $query);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Search with query expansion (finds related terms)
     */
    public function queryExpansion($query, $table, $columns) {
        $sql = "SELECT *, 
                MATCH($columns) AGAINST(? WITH QUERY EXPANSION) AS relevance 
                FROM $table 
                WHERE MATCH($columns) AGAINST(? WITH QUERY EXPANSION)
                ORDER BY relevance DESC";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("ss", $query, $query);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get search relevance score without filtering
     */
    public function getRelevanceScores($query, $table, $columns, $limit = 10) {
        $sql = "SELECT *, 
                MATCH($columns) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance 
                FROM $table 
                ORDER BY relevance DESC 
                LIMIT ?";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("si", $query, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function close() {
        $this->mysqli->close();
    }
}

// Usage example
$search = new FullTextSearch("localhost", "root", "password", "mydb");

// Natural language search
$results = $search->naturalSearch("mysql database optimization", "articles", "title, content");
echo "<h2>Natural Language Results:</h2>";
foreach ($results as $row) {
    echo "<div>";
    echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
    echo "<p>Relevance: " . $row['relevance'] . "</p>";
    echo "</div>";
}

// Boolean search
$results = $search->booleanSearch("+mysql +performance -oracle", "articles", "title, content");
echo "<h2>Boolean Search Results:</h2>";
foreach ($results as $row) {
    echo "<div>";
    echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
    echo "<p>Relevance: " . $row['relevance'] . "</p>";
    echo "</div>";
}

$search->close();
?>
```

### PHP Example - Search with Pagination

```php
<?php
function paginatedSearch($mysqli, $query, $page = 1, $per_page = 10) {
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM articles 
                  WHERE MATCH(title, content) AGAINST(? IN BOOLEAN MODE)";
    $stmt = $mysqli->prepare($count_sql);
    $stmt->bind_param("s", $query);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Get paginated results
    $sql = "SELECT *, 
            MATCH(title, content) AGAINST(? IN BOOLEAN MODE) AS relevance 
            FROM articles 
            WHERE MATCH(title, content) AGAINST(? IN BOOLEAN MODE)
            ORDER BY relevance DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssii", $query, $query, $per_page, $offset);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return [
        'results' => $results,
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($total / $per_page)
    ];
}

// Usage
$mysqli = new mysqli("localhost", "root", "password", "mydb");
$search_query = "+mysql +performance";
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$data = paginatedSearch($mysqli, $search_query, $page, 10);

echo "<h2>Search Results (Page {$data['page']} of {$data['total_pages']})</h2>";
echo "<p>Found {$data['total']} results</p>";

foreach ($data['results'] as $row) {
    echo "<div>";
    echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
    echo "<p>" . htmlspecialchars(substr($row['content'], 0, 200)) . "...</p>";
    echo "<small>Relevance: " . $row['relevance'] . "</small>";
    echo "</div><hr>";
}

// Pagination links
echo "<div>";
for ($i = 1; $i <= $data['total_pages']; $i++) {
    if ($i == $page) {
        echo "<strong>$i</strong> ";
    } else {
        echo "<a href='?page=$i'>$i</a> ";
    }
}
echo "</div>";

$mysqli->close();
?>
``

## 9. Common Issues and Solutions

### Issue 1: Search Returns No Results for Short Words

**Problem:** Searching for "AI" or "UK" returns nothing.

**Solution:** Lower the minimum token size (requires server restart and index rebuild).

```php
// After configuration change, rebuild index
$mysqli->query("ALTER TABLE articles DROP INDEX content_idx");
$mysqli->query("ALTER TABLE articles ADD FULLTEXT INDEX content_idx (content)");
```

### Issue 2: Common Words Are Ignored

**Problem:** Searching for "the best database" ignores "the" and "best".

**Solution:** Use boolean mode or create custom stopword list.

```php
// Use boolean mode
$query = "+the +best +database";
$sql = "SELECT * FROM articles 
        WHERE MATCH(content) AGAINST(? IN BOOLEAN MODE)";
```

### Issue 3: 50% Threshold Blocks Results (MyISAM)

**Problem:** Word appears in too many documents and is ignored.

**Solution:** Always use IN BOOLEAN MODE for common terms.

```php
// Boolean mode bypasses the 50% threshold
$sql = "SELECT * FROM articles 
        WHERE MATCH(content) AGAINST(? IN BOOLEAN MODE)";
```

### Issue 4: Slow Search Performance

**Problem:** Full-text searches are slow on large tables.

**Solution:** Optimize the full-text index regularly.

```php
$mysqli->query("SET GLOBAL innodb_optimize_fulltext_only = ON");
$mysqli->query("OPTIMIZE TABLE articles");
$mysqli->query("SET GLOBAL innodb_optimize_fulltext_only = OFF");
```

## 10. Best Practices

### 1. Always Use Prepared Statements
```php
// ✓ GOOD - Protected against SQL injection
$stmt = $mysqli->prepare("SELECT * FROM articles 
                          WHERE MATCH(content) AGAINST(? IN BOOLEAN MODE)");
$stmt->bind_param("s", $user_input);
$stmt->execute();

// ✗ BAD - Vulnerable to SQL injection
$sql = "SELECT * FROM articles 
        WHERE MATCH(content) AGAINST('$user_input' IN BOOLEAN MODE)";
```

### 2. Sanitize Boolean Operators from User Input
```php
function sanitizeBooleanQuery($query) {
    // Remove potentially problematic characters
    // while keeping valid boolean operators
    $query = preg_replace('/[^\w\s+\-"*()]/', '', $query);
    return trim($query);
}

$user_query = sanitizeBooleanQuery($_GET['q']);
```

### 3. Add Minimum Query Length
```php
function search($query) {
    // Require at least 2 characters
    if (strlen($query) < 2) {
        return ['error' => 'Query too short. Minimum 2 characters.'];
    }
    
    // Perform search...
}
```

### 4. Cache Search Results
```php
function cachedSearch($mysqli, $query, $cache_time = 300) {
    $cache_key = 'search_' . md5($query);
    $cache_file = "/tmp/$cache_key";
    
    // Check if cache exists and is fresh
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
        return unserialize(file_get_contents($cache_file));
    }
    
    // Perform actual search
    $stmt = $mysqli->prepare("SELECT * FROM articles 
                              WHERE MATCH(content) AGAINST(? IN BOOLEAN MODE)");
    $stmt->bind_param("s", $query);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Cache results
    file_put_contents($cache_file, serialize($results));
    
    return $results;
}
```

### 5. Log Slow Queries
```php
function logSlowQuery($query, $execution_time) {
    if ($execution_time > 1.0) { // Log queries taking more than 1 second
        $log_entry = date('Y-m-d H:i:s') . " - Slow query ($execution_time s): $query\n";
        file_put_contents('/var/log/mysql_slow_searches.log', $log_entry, FILE_APPEND);
    }
}

$start = microtime(true);
// ... perform search ...
$execution_time = microtime(true) - $start;
logSlowQuery($search_query, $execution_time);
```

## 11. Summary Checklist

Before making changes to FTS configuration:

- [ ] Understand the default behavior and why you need to change it
- [ ] Back up your database
- [ ] Test configuration changes on a development server first
- [ ] Plan for server downtime if restart is required
- [ ] Budget time for index rebuilding (can take hours on large tables)
- [ ] Document all configuration changes
- [ ] Monitor search performance after changes

Configuration changes that require restart and rebuild:
- [ ] Word length settings (`innodb_ft_min_token_size`, etc.)
- [ ] Stopword tables
- [ ] Token size for ngram parser

Configuration changes that don't require restart:
- [ ] Boolean syntax operators (MyISAM only)
- [ ] Session-level stopword settings


## Resources

- MySQL 8.4 Full-Text Search Documentation: https://dev.mysql.com/doc/refman/8.4/en/fulltext-search.html
- MySQL 8.4 Fine-Tuning Guide: https://dev.mysql.com/doc/refman/8.4/en/fulltext-fine-tuning.html
- InnoDB Full-Text Indexes: https://dev.mysql.com/doc/refman/8.4/en/innodb-fulltext-index.html


**Remember:** Full-text search is powerful, but it's not a replacement for specialized search engines like Elasticsearch or Solr for very large datasets or complex search requirements. Use FTS for moderate-sized applications where you want search functionality without additional infrastructure.

<br>