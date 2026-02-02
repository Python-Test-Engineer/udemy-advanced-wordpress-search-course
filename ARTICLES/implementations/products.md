# WP_PRODUCTS

This is based on the custom wp_products table with associated queries.

```sql
-- ============================================
-- FULL TEXT SEARCH SETUP FOR wp_products
-- ============================================

-- Create the wp_products table with auto-increment ID and GUID product_id
CREATE TABLE IF NOT EXISTS wp_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id CHAR(36) NOT NULL UNIQUE,
    product_name VARCHAR(255) NOT NULL,
    product_short_description VARCHAR(500),
    expanded_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_id (product_id),
    INDEX idx_product_name (product_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

```

```sql
-- 1. CREATE FULL TEXT INDEXES
-- ============================================

-- Index on product_name only
ALTER TABLE wp_products 
ADD FULLTEXT INDEX ft_product_name (product_name);

-- Index on product_short_description only
ALTER TABLE wp_products 
ADD FULLTEXT INDEX ft_short_desc (product_short_description);

-- Index on product_short_description only
ALTER TABLE wp_products 
ADD FULLTEXT INDEX ft_expanded_description (expanded_description);
-- Composite index on multiple columns (recommended for comprehensive search)
ALTER TABLE wp_products 
ADD FULLTEXT INDEX ft_product_search (product_name, product_short_description, expanded_description);
```

```sql 
-- More ropbust way
-- Stored procedure to check and create full-text indexes if they don't exist
DELIMITER $$

DROP PROCEDURE IF EXISTS ensure_wp_products_fulltext_indexes$$

CREATE PROCEDURE ensure_wp_products_fulltext_indexes()
BEGIN
    -- ft_product_name
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'wp_products'
          AND index_name = 'ft_product_name'
    ) THEN
        ALTER TABLE wp_products
        ADD FULLTEXT INDEX ft_product_name (product_name);
    END IF;

    -- ft_short_desc
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'wp_products'
          AND index_name = 'ft_short_desc'
    ) THEN
        ALTER TABLE wp_products
        ADD FULLTEXT INDEX ft_short_desc (product_short_description);
    END IF;

    -- ft_expanded_description
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'wp_products'
          AND index_name = 'ft_expanded_description'
    ) THEN
        ALTER TABLE wp_products
        ADD FULLTEXT INDEX ft_expanded_description (expanded_description);
    END IF;

    -- ft_product_search (composite)
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'wp_products'
          AND index_name = 'ft_product_search'
    ) THEN
        ALTER TABLE wp_products
        ADD FULLTEXT INDEX ft_product_search (
            product_name,
            product_short_description,
            expanded_description
        );
    END IF;
END$$

DELIMITER ;

CALL ensure_wp_products_fulltext_indexes();

-- Optional: clean up
DROP PROCEDURE ensure_wp_products_fulltext_indexes;
```

```sql
-- ============================================
-- 2. NATURAL LANGUAGE MODE QUERIES
-- ============================================
-- Default mode - searches for words in natural text
-- Results ranked by relevance automaticall
-- !! There must be an index on the  composite searched columns !!
-- If you MATCH against multiple columns, ensure a FULLTEXT index exists on all those columns, not just individual columns.


-- Basic natural language search
SELECT id, product_name, product_short_description, expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('wireless audio') AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('wireless audio')
ORDER BY relevance_score DESC;
```

```sql
-- Search for camera products
SELECT id, product_name, product_short_description,expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('camera video recording') AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('camera video recording')
ORDER BY relevance_score DESC;
```

```sql
-- Search for workspace/office products
SELECT id, product_name, product_short_description,expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('desk workspace office') AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('desk workspace office')
ORDER BY relevance_score DESC;
```

```sql
-- Combine full-text with regular WHERE clause
SELECT id, product_name, product_short_description,expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('smart home') AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('smart home')
  AND created_at >= '2026-01-01'
ORDER BY relevance_score DESC;
```

```sql
-- ============================================
-- 3. BOOLEAN MODE QUERIES
-- ============================================
-- More control with operators: +required -excluded "phrase" *wildcard

-- Search with required terms (+ operator)
-- Must contain "smart" AND "LED"
-- LED is in expanded_description for Smart Indoor Garden Kit
SELECT id, product_name, product_short_description,expanded_description
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('+smart +LED' IN BOOLEAN MODE);
```

```sql
-- Search with excluded terms (- operator)
-- Contains "speaker" and optionally "bluetooth"
SELECT id, product_name, product_short_description,expanded_description
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('+speaker bluetooth' IN BOOLEAN MODE);
```

```sql
-- Phrase search with quotes
-- Exact phrase "noise cancelling"
SELECT product_name, product_short_description
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('"noise cancelling"' IN BOOLEAN MODE);
```

```sql
-- Wildcard search (* operator)
-- Matches "port", "portable", "portability", etc.
SELECT id, product_name, product_short_description,expanded_description
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('port*' IN BOOLEAN MODE);
```

```sql
-- Complex boolean query combining operators
-- Must have "camera" OR "speaker", must have "portable", cannot have "desk"
SELECT id, product_name, product_short_description,expanded_description
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('+(camera speaker) +portable -desk' IN BOOLEAN MODE);
```

```sql
-- Optional terms with relevance boosting (> and < operators)
-- "wireless" is more important, "audio" is less important
SELECT id, product_name, product_short_description,expanded_description
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('>wireless <audio' IN BOOLEAN MODE);
```

```sql
-- Search for products with specific features
-- Must contain "waterproof" or "water-resistant" or "splash-resistant"
SELECT id, product_name, product_short_description,expanded_description
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('+water* +(proof resistant)' IN BOOLEAN MODE);
```

```sql
-- ============================================
-- 4. QUERY EXPANSION MODE
-- ============================================
-- Automatically expands search with related terms
-- Two-pass search: first finds relevant docs, then adds related terms

-- Basic query expansion
-- Finds "bluetooth" and related terms like "wireless", "connectivity", etc.
SELECT id, product_name, product_short_description,expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('bluetooth' WITH QUERY EXPANSION) AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('bluetooth' WITH QUERY EXPANSION)
ORDER BY relevance_score DESC;
```

```sql
-- Search for audio products with expansion
-- Will find "audio", "sound", "speaker", "music", etc.
SELECT id, product_name, product_short_description,expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('audio' WITH QUERY EXPANSION) AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('audio' WITH QUERY EXPANSION)
ORDER BY relevance_score DESC;
```

```sql
-- Search for fitness products with expansion
-- Will find related terms to "yoga" like "exercise", "fitness", "workout"
SELECT id, product_name, product_short_description,expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('yoga' WITH QUERY EXPANSION) AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('yoga' WITH QUERY EXPANSION)
ORDER BY relevance_score DESC;
```

```sql
-- ============================================
-- 5. PRACTICAL WORDPRESS INTEGRATION EXAMPLES
-- ============================================

-- Search with pagination (for WordPress product listings)
SELECT id, product_name, product_short_description,expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('smart home' WITH QUERY EXPANSION) AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('smart home' WITH QUERY EXPANSION)
ORDER BY relevance_score DESC
LIMIT 10 OFFSET 0;
```

```sql
-- Search with minimum relevance threshold
SELECT id, product_name, product_short_description,expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('wireless portable') AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('wireless portable')
HAVING relevance_score > 0.5
ORDER BY relevance_score DESC;
```

```sql
-- Combined search: Full-text + category filter (if you add categories)
-- This example assumes you might join with a categories table
SELECT id, product_name, product_short_description,expanded_description,
       MATCH(p.product_name, p.product_short_description, p.expanded_description) 
       AGAINST ('+audio +wireless' IN BOOLEAN MODE) AS relevance_score
FROM wp_products p
WHERE MATCH(p.product_name, p.product_short_description, p.expanded_description) 
      AGAINST ('+audio +wireless' IN BOOLEAN MODE)
ORDER BY relevance_score DESC;
```


### PERFORMANCE TIPS & NOTES

1. Minimum word length: By default, MySQL ignores words shorter than 4 characters
   - Configure with ft_min_word_len in my.cnf
   - Rebuild indexes after changing: REPAIR TABLE wp_products QUICK;

2. Stopwords: Common words (the, and, or, etc.) are ignored
   - Configure custom stopword list if needed

3. Boolean operators:
   + : Must contain this word
   - : Must NOT contain this word
   > : Increase relevance
   < : Decrease relevance
   * : Wildcard (must be at end of word)
   " : Exact phrase match
   () : Grouping

4. Query Expansion:
   - Useful for semantic search
   - Can return more results than expected
   - Slower than natural/boolean mode
   - Best for short queries (1-2 words)

5. Performance:
   - Full-text indexes are fast for searching
   - Can slow down INSERT/UPDATE operations
   - Consider using separate search table for high-volume sites

6. WordPress Integration:
   - Use prepared statements in wpdb
   - Example: $wpdb->prepare() with %s for search terms
   - Sanitize user input before search queries


```sql
-- Check full-text index status
SHOW INDEX FROM wp_products WHERE Index_type = 'FULLTEXT';

-- Analyze table performance
ANALYZE TABLE wp_products;
```

<br>