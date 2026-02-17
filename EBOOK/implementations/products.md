# WP_PRODUCTS

We will create a custonm table `wp_products`.

## Table creation
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

## Indexes

```sql
-- 1. CREATE FULL TEXT INDEXES
-- ============================================

-- Index on product_name only
ALTER TABLE wp_products 
ADD FULLTEXT INDEX ft_product_name (product_name);

-- Index on product_short_description only
ALTER TABLE wp_products 
ADD FULLTEXT INDEX ft_short_desc (product_short_description);

-- Index on product_name and product_short_description 
ALTER TABLE wp_products 
ADD FULLTEXT INDEX ft_name_short_desc (product_name, product_short_description);

-- Composite index on multiple columns (recommended for comprehensive search)
ALTER TABLE wp_products 
ADD FULLTEXT INDEX ft_product_search (product_name, product_short_description, expanded_description);
```

```sql 
-- More robust way
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
## Natural Language Mode 

The MySQL documentation states that when you use MATCH ... AGAINST in both the WHERE clause and ORDER BY, you don't need to explicitly add ORDER BY because MySQL will automatically sort by relevance. However, having an explicit ORDER BY is not wrong - it just may be redundant in some cases.


```sql
-- ============================================
-- 2. NATURAL LANGUAGE MODE QUERIES
-- ============================================
-- Default mode - searches for words in natural text
-- Results ranked by relevance automaticall
-- !! There must be an index on the  composite searched columns !!
-- If you MATCH against multiple columns, ensure a FULLTEXT index exists 
-- on all those columns, not just individual columns.


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
SELECT id, product_name, product_short_description, expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('camera video recording') AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('camera video recording')
ORDER BY relevance_score DESC;
```

```sql
-- Search for workspace/office products
SELECT id, product_name, product_short_description, expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('desk workspace office') AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('desk workspace office')
ORDER BY relevance_score DESC;
```

```sql
-- Combine full-text with regular WHERE clause
SELECT id, created_at, product_name, product_short_description, expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('smart home') AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('smart home')
  AND created_at >= '2025-07-01'
ORDER BY relevance_score DESC;
```
## Boolen Mode

```sql
-- ============================================
-- 3. BOOLEAN MODE QUERIES
-- ============================================
-- More control with operators: +required -excluded "phrase" wildcard

-- Search with required terms (+/-operator)
-- Must contain "smart" AND/NOT "LED" 
-- LED is in expanded_description for Smart Indoor Garden Kit
SELECT id, product_name, product_short_description
FROM wp_products
WHERE MATCH(product_name, product_short_description) 
      AGAINST ('+smart +LED' IN BOOLEAN MODE);
```

```sql
-- Phrase search with quotes
-- Exact phrase "noise cancelling"
SELECT id, product_name, product_short_description
FROM wp_products
WHERE MATCH(product_name, product_short_description) 
      AGAINST ('"noise cancelling"' IN BOOLEAN MODE);
```

```sql
-- Wildcard search (* operator)
-- Matches "port", "portable", "portability", etc.
SELECT id, product_name, product_short_description
FROM wp_products
WHERE MATCH(product_name, product_short_description) 
      AGAINST ('port*' IN BOOLEAN MODE);
```

```sql
-- Complex boolean query combining operators
-- Must have "camera" OR "speaker", must have "portable", cannot have "desk"
SELECT id, product_name, product_short_description, expanded_description
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('+(camera speaker) +portable -desk' IN BOOLEAN MODE);
```

```sql
-- Optional terms with relevance boosting (> and < operators)
-- use > or < with shower to see effect
SELECT 
    id, 
    product_name, 
    product_short_description, 
    expanded_description,
    MATCH(product_name, product_short_description, expanded_description) 
        AGAINST ('smart portable >shower' IN BOOLEAN MODE) AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('smart portable >shower' IN BOOLEAN MODE)
ORDER BY relevance_score DESC;
```


## Query Expansion Mode

```sql
-- ============================================
-- 4. QUERY EXPANSION MODE
-- ============================================
-- Automatically expands search with related terms
-- Two-pass search: first finds relevant docs, then adds related terms
-- compare by replacing with AGAINST ('search terms' IN NATURAL LANGUAGE MODE) 
-- Basic query expansion
-- Finds "bluetooth" and related terms like "wireless", "connectivity", etc.
-- Remove QUERT EXPANSION and NATURAL default gives siginificantly less rows
SELECT id, product_name, product_short_description, expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('bluetooth' WITH QUERY EXPANSION) AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('bluetooth' WITH QUERY EXPANSION)
ORDER BY relevance_score DESC;
```

```sql
-- Search for fitness products with expansion
-- Will find related terms to "yoga" like "exercise", "fitness", "workout"
SELECT id, product_name, product_short_description, expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('yoga' WITH QUERY EXPANSION) AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('yoga' WITH QUERY EXPANSION)
ORDER BY relevance_score DESC;
```


```sql
-- Search with minimum relevance threshold
SELECT id, product_name, product_short_description, expanded_description,
       MATCH(product_name, product_short_description, expanded_description) 
       AGAINST ('wireless portable') AS relevance_score
FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description) 
      AGAINST ('wireless portable')
HAVING relevance_score > 2.5
ORDER BY relevance_score DESC;
```

One could create a query that only returns those rows that in total make up 80% of total score to avoid long tail row. This is more involved scripting but doable.

```sql
-- Combined search: Full-text + category filter (if you add categories)
-- This example assumes you might join with a categories table
SELECT id, product_name, product_short_description, expanded_description,
       MATCH(p.product_name, p.product_short_description, p.expanded_description) 
       AGAINST ('+audio +wireless' IN BOOLEAN MODE) AS relevance_score
FROM wp_products p
WHERE MATCH(p.product_name, p.product_short_description, p.expanded_description) 
      AGAINST ('+audio +wireless' IN BOOLEAN MODE)
ORDER BY relevance_score DESC;
```

<br>