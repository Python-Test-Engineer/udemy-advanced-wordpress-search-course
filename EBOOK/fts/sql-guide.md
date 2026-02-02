# MySQL Full Text Search - Complete Guide

## Introduction

Full Text Search (FTS) in MySQL provides powerful text searching capabilities beyond simple LIKE queries. It's particularly useful for searching product catalogs, blog posts, and other content-heavy tables.

## Setting Up Full Text Search

### Creating a FULLTEXT Index

Before using FTS, you must create a FULLTEXT index on the columns you want to search.

```sql
-- For a products table
CREATE FULLTEXT INDEX idx_product_search 
ON products(product_name, description);

-- For WordPress posts table
CREATE FULLTEXT INDEX idx_post_search 
ON wp_posts(post_title, post_content);

-- Add to existing table
ALTER TABLE products 
ADD FULLTEXT INDEX idx_product_search(product_name, description);
```

**Important Notes:**
- FULLTEXT indexes only work with InnoDB (MySQL 5.6+) and MyISAM engines
- Can be created on CHAR, VARCHAR, or TEXT columns
- Multiple columns can be included in a single FULLTEXT index

## The Three Search Modes

MySQL offers three full text search modes, each with different behaviors and use cases.

---

## 1. Natural Language Mode (Default)

Natural language mode ranks results by relevance and filters out words that appear in more than 50% of rows.

### Basic Syntax

```sql
SELECT column_list
FROM table_name
WHERE MATCH(column1, column2, ...) AGAINST('search terms' IN NATURAL LANGUAGE MODE);
```

### Products Table Examples

```sql
-- Basic product search
SELECT product_id, product_name, description
FROM products
WHERE MATCH(product_name, description) 
AGAINST('wireless headphones');

-- With relevance score
SELECT 
    product_id,
    product_name,
    MATCH(product_name, description) AGAINST('wireless headphones') AS relevance
FROM products
WHERE MATCH(product_name, description) AGAINST('wireless headphones')
ORDER BY relevance DESC;

-- Search with additional filters
SELECT product_id, product_name, price
FROM products
WHERE MATCH(product_name, description) AGAINST('laptop gaming')
    AND price BETWEEN 500 AND 2000
    AND in_stock = 1
ORDER BY MATCH(product_name, description) AGAINST('laptop gaming') DESC;
```

### WordPress Posts Examples

```sql
-- Search posts
SELECT ID, post_title, post_date
FROM wp_posts
WHERE MATCH(post_title, post_content) 
AGAINST('wordpress plugins')
    AND post_status = 'publish'
    AND post_type = 'post';

-- Search with relevance scoring
SELECT 
    ID,
    post_title,
    post_date,
    MATCH(post_title, post_content) AGAINST('SEO optimization') AS score
FROM wp_posts
WHERE MATCH(post_title, post_content) AGAINST('SEO optimization')
    AND post_status = 'publish'
ORDER BY score DESC
LIMIT 10;
```

### Natural Language Mode Characteristics

- **Relevance Ranking**: Results automatically ranked by relevance
- **50% Threshold**: Words appearing in >50% of rows are ignored (stopwords)
- **Minimum Word Length**: Default is 4 characters (configurable via `ft_min_word_len`)
- **Case Insensitive**: Searches are not case-sensitive
- **No Operators**: Cannot use boolean operators like +, -, *

---

## 2. Boolean Mode

Boolean mode provides fine-grained control over search queries using operators. No 50% threshold applies.

### Basic Syntax

```sql
SELECT column_list
FROM table_name
WHERE MATCH(column1, column2, ...) AGAINST('search terms' IN BOOLEAN MODE);
```

### Boolean Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `+` | Word must be present | `+required` |
| `-` | Word must not be present | `-excluded` |
| `*` | Wildcard (truncation) | `wire*` matches wireless, wired |
| `""` | Exact phrase | `"exact phrase"` |
| `()` | Group expressions | `+(apple orange) -banana` |
| `>` | Increase word importance | `>important` |
| `<` | Decrease word importance | `<less important` |
| `~` | Negate word ranking | `~minor` |

### Products Table Examples

```sql
-- Product MUST contain "wireless" and MUST contain "headphones"
SELECT product_name, description
FROM products
WHERE MATCH(product_name, description) 
AGAINST('+wireless +headphones' IN BOOLEAN MODE);

-- Product MUST contain "laptop" but NOT "gaming"
SELECT product_name, price
FROM products
WHERE MATCH(product_name, description) 
AGAINST('+laptop -gaming' IN BOOLEAN MODE);

-- Exact phrase search
SELECT product_name
FROM products
WHERE MATCH(product_name, description) 
AGAINST('"noise cancelling headphones"' IN BOOLEAN MODE);

-- Wildcard search - finds "bluetooth", "blue", etc.
SELECT product_name
FROM products
WHERE MATCH(product_name, description) 
AGAINST('blue*' IN BOOLEAN MODE);

-- Complex query: Must have "phone", should have "android" or "samsung", exclude "refurbished"
SELECT product_name, price
FROM products
WHERE MATCH(product_name, description) 
AGAINST('+phone +(android samsung) -refurbished' IN BOOLEAN MODE);

-- Prioritize certain terms
SELECT 
    product_name,
    MATCH(product_name, description) 
    AGAINST('>premium <budget wireless' IN BOOLEAN MODE) AS relevance
FROM products
WHERE MATCH(product_name, description) 
AGAINST('>premium <budget wireless' IN BOOLEAN MODE)
ORDER BY relevance DESC;
```

### WordPress Posts Examples

```sql
-- Posts that MUST contain "wordpress" and "tutorial"
SELECT post_title, post_date
FROM wp_posts
WHERE MATCH(post_title, post_content) 
AGAINST('+wordpress +tutorial' IN BOOLEAN MODE)
    AND post_status = 'publish';

-- Posts about "CSS" but not "JavaScript"
SELECT post_title
FROM wp_posts
WHERE MATCH(post_title, post_content) 
AGAINST('+CSS -JavaScript' IN BOOLEAN MODE)
    AND post_status = 'publish';

-- Exact title search
SELECT ID, post_title
FROM wp_posts
WHERE MATCH(post_title, post_content) 
AGAINST('"How to Install WordPress"' IN BOOLEAN MODE);

-- Find all posts about "develop*" (developer, development, developing)
SELECT post_title
FROM wp_posts
WHERE MATCH(post_title, post_content) 
AGAINST('develop*' IN BOOLEAN MODE)
    AND post_status = 'publish'
LIMIT 20;

-- Complex search: Must have "plugin", prefer "free", exclude "deprecated"
SELECT 
    post_title,
    post_date,
    MATCH(post_title, post_content) 
    AGAINST('+plugin >free -deprecated' IN BOOLEAN MODE) AS score
FROM wp_posts
WHERE MATCH(post_title, post_content) 
AGAINST('+plugin >free -deprecated' IN BOOLEAN MODE)
    AND post_status = 'publish'
ORDER BY score DESC;
```

### Boolean Mode Characteristics

- **No 50% Threshold**: All words are considered, regardless of frequency
- **Explicit Operators**: Full control with +, -, *, etc.
- **No Automatic Ranking**: Results not automatically sorted by relevance (unless you ORDER BY MATCH score)
- **Minimum Length Still Applies**: Words must meet minimum length requirement
- **Can Search Without Index**: Boolean mode works even without FULLTEXT index (but much slower)

---

## 3. Query Expansion Mode

Query expansion performs a two-pass search: first finds relevant documents, then searches again using terms from those documents to find more results.

### Basic Syntax

```sql
SELECT column_list
FROM table_name
WHERE MATCH(column1, column2, ...) 
AGAINST('search terms' WITH QUERY EXPANSION);
```

### How Query Expansion Works

1. **First Pass**: Performs normal natural language search
2. **Extract Terms**: Analyzes top results to find related terms
3. **Second Pass**: Searches again with original + expanded terms
4. **Return Combined Results**: Merges results from both passes

### Products Table Examples

```sql
-- Basic query expansion
-- If searching for "laptop", might also find "notebook", "computer", "PC"
SELECT product_name, description
FROM products
WHERE MATCH(product_name, description) 
AGAINST('laptop' WITH QUERY EXPANSION);

-- Query expansion with scoring
SELECT 
    product_name,
    MATCH(product_name, description) 
    AGAINST('smartphone' WITH QUERY EXPANSION) AS relevance
FROM products
WHERE MATCH(product_name, description) 
AGAINST('smartphone' WITH QUERY EXPANSION)
ORDER BY relevance DESC
LIMIT 20;

-- Practical example: Finding similar products
-- User searches "wireless earbuds", expansion might include "bluetooth", "TWS", "headphones"
SELECT product_id, product_name, price
FROM products
WHERE MATCH(product_name, description) 
AGAINST('wireless earbuds' WITH QUERY EXPANSION)
    AND in_stock = 1
ORDER BY MATCH(product_name, description) 
    AGAINST('wireless earbuds' WITH QUERY EXPANSION) DESC
LIMIT 10;
```

### WordPress Posts Examples

```sql
-- Search posts with query expansion
-- "SEO" might expand to include "search engine", "ranking", "optimization"
SELECT post_title, post_date
FROM wp_posts
WHERE MATCH(post_title, post_content) 
AGAINST('SEO' WITH QUERY EXPANSION)
    AND post_status = 'publish'
ORDER BY post_date DESC;

-- Find related articles
SELECT 
    ID,
    post_title,
    MATCH(post_title, post_content) 
    AGAINST('machine learning' WITH QUERY EXPANSION) AS relevance
FROM wp_posts
WHERE MATCH(post_title, post_content) 
AGAINST('machine learning' WITH QUERY EXPANSION)
    AND post_status = 'publish'
ORDER BY relevance DESC
LIMIT 15;

-- Related posts for a "You might also like" feature
SELECT p2.ID, p2.post_title
FROM wp_posts p1
CROSS JOIN wp_posts p2
WHERE p1.ID = 123  -- Current post ID
    AND p2.ID != p1.ID
    AND MATCH(p2.post_title, p2.post_content) 
        AGAINST(p1.post_title WITH QUERY EXPANSION)
    AND p2.post_status = 'publish'
LIMIT 5;
```

### Query Expansion Characteristics

- **Automatic Term Discovery**: Finds related terms automatically
- **Broader Results**: Returns more results than natural language mode
- **Potentially Lower Precision**: May include less relevant results
- **Performance Impact**: Slower than other modes (two-pass search)
- **Best for Short Queries**: Works well with 1-3 word searches
- **Blind Expansion**: Uses terms even if not semantically related

---

## Comparison Table

| Feature | Natural Language | Boolean Mode | Query Expansion |
|---------|-----------------|--------------|-----------------|
| Default mode | ✓ | ✗ | ✗ |
| Relevance ranking | Automatic | Manual (ORDER BY) | Automatic |
| 50% threshold | Yes | No | Yes |
| Operators (+, -, *) | No | Yes | No |
| Search speed | Fast | Fast | Slower (2-pass) |
| Result precision | High | Highest | Lower |
| Result breadth | Medium | Narrow/Broad | Broadest |
| Best for | General search | Precise search | Discovery/Related content |

---

## Practical Implementation Examples

### Product Search Page with Filters

```sql
-- Complete product search with multiple filters
SELECT 
    p.product_id,
    p.product_name,
    p.description,
    p.price,
    p.category,
    c.category_name,
    MATCH(p.product_name, p.description) 
        AGAINST('wireless speaker' IN BOOLEAN MODE) AS relevance
FROM products p
LEFT JOIN categories c ON p.category = c.category_id
WHERE 
    MATCH(p.product_name, p.description) 
        AGAINST('+wireless +speaker -refurbished' IN BOOLEAN MODE)
    AND p.price BETWEEN 50 AND 300
    AND p.in_stock = 1
    AND p.active = 1
ORDER BY relevance DESC, p.rating DESC
LIMIT 20 OFFSET 0;
```

### WordPress Search Results Page

```sql
-- Complete WordPress search with post meta
SELECT 
    p.ID,
    p.post_title,
    p.post_excerpt,
    p.post_date,
    p.post_modified,
    u.display_name as author,
    MATCH(p.post_title, p.post_content) 
        AGAINST('wordpress security') AS relevance
FROM wp_posts p
INNER JOIN wp_users u ON p.post_author = u.ID
WHERE 
    MATCH(p.post_title, p.post_content) 
        AGAINST('wordpress security')
    AND p.post_status = 'publish'
    AND p.post_type IN ('post', 'page')
    AND p.post_date <= NOW()
ORDER BY relevance DESC, p.post_date DESC
LIMIT 10;
```

### Search Suggestions (Autocomplete)

```sql
-- Product name autocomplete using wildcard
SELECT DISTINCT product_name
FROM products
WHERE MATCH(product_name) 
    AGAINST('head*' IN BOOLEAN MODE)
    AND active = 1
LIMIT 10;
```

### Related Products

```sql
-- Find similar products to product ID 42
SELECT 
    p2.product_id,
    p2.product_name,
    p2.price,
    MATCH(p2.product_name, p2.description) 
        AGAINST((SELECT CONCAT(product_name, ' ', description) 
                 FROM products WHERE product_id = 42)
                WITH QUERY EXPANSION) AS similarity
FROM products p2
WHERE p2.product_id != 42
    AND MATCH(p2.product_name, p2.description) 
        AGAINST((SELECT CONCAT(product_name, ' ', description) 
                 FROM products WHERE product_id = 42)
                WITH QUERY EXPANSION)
ORDER BY similarity DESC
LIMIT 5;
```

---

## Performance Tips

### 1. Index Strategy
```sql
-- Index only columns you'll search together
CREATE FULLTEXT INDEX idx_product_name ON products(product_name);
CREATE FULLTEXT INDEX idx_product_full ON products(product_name, description);
```

### 2. Use MATCH in WHERE and ORDER BY
```sql
-- Efficient: Reuse MATCH calculation
SELECT product_name,
       MATCH(product_name, description) AGAINST('laptop') AS score
FROM products
WHERE MATCH(product_name, description) AGAINST('laptop')
ORDER BY score DESC;
```

### 3. Limit Results
```sql
-- Always use LIMIT for better performance
SELECT product_name
FROM products
WHERE MATCH(product_name, description) AGAINST('phone' IN BOOLEAN MODE)
LIMIT 50;
```

### 4. Configure Minimum Word Length
```sql
-- In my.cnf or my.ini
-- [mysqld]
-- ft_min_word_len = 3
-- ft_max_word_len = 84

-- After changing, rebuild indexes:
-- REPAIR TABLE products QUICK;
```

---

## Common Pitfalls and Solutions

### Problem: No Results Found

**Cause**: Word too short (< 4 characters by default)
```sql
-- Won't work with default settings
WHERE MATCH(product_name) AGAINST('USB' IN BOOLEAN MODE)

-- Solution: Lower ft_min_word_len or use wildcards
WHERE MATCH(product_name) AGAINST('USB*' IN BOOLEAN MODE)
```

### Problem: Common Words Ignored

**Cause**: Word appears in >50% of rows (Natural Language Mode)
```sql
-- "the" might be ignored
WHERE MATCH(post_content) AGAINST('the best laptop')

-- Solution: Use Boolean Mode
WHERE MATCH(post_content) AGAINST('+best +laptop' IN BOOLEAN MODE)
```

### Problem: Slow Queries

**Cause**: Not using FULLTEXT index properly
```sql
-- Slow: MATCH columns don't match index
CREATE FULLTEXT INDEX idx ON products(product_name);
WHERE MATCH(description) AGAINST('laptop');  -- Won't use index!

-- Fast: Match index definition
WHERE MATCH(product_name) AGAINST('laptop');
```

### Problem: Special Characters Ignored

**Cause**: MySQL treats special chars as word separators
```sql
-- "C++" becomes "C"
WHERE MATCH(post_content) AGAINST('C++')

-- Solution: Use exact phrase in Boolean Mode
WHERE MATCH(post_content) AGAINST('"C++"' IN BOOLEAN MODE)
```

---

## Configuration Variables

Key MySQL variables affecting Full Text Search:

```sql
-- View current settings
SHOW VARIABLES LIKE 'ft%';

-- Key variables:
-- ft_min_word_len: Minimum word length (default: 4)
-- ft_max_word_len: Maximum word length (default: 84)
-- ft_stopword_file: Path to stopwords file
-- ft_boolean_syntax: Boolean operators (default: + -><()~*:""&|)
-- innodb_ft_min_token_size: InnoDB minimum word length (default: 3)
```

---

## Summary

**Use Natural Language Mode when:**
- You want automatic relevance ranking
- Searching general content (blog posts, articles)
- Users enter natural search queries
- Simplicity is important

**Use Boolean Mode when:**
- You need precise control over search terms
- Implementing advanced search features
- Handling technical content with abbreviations
- Users are power users who understand operators

**Use Query Expansion when:**
- Finding related content ("You might also like")
- User search terms are very specific but you want broad results
- Building discovery features
- Improving recall at the cost of precision

For most e-commerce products and WordPress searches, **Boolean Mode** offers the best balance of control and functionality, while **Natural Language Mode** is perfect for simple search boxes.

<br>