# How MySQL Selects Indexes for Full-Text Search (FTS)

## Introduction

Full-Text Search (FTS) in MySQL is a powerful feature for searching text content in database columns. Understanding how MySQL selects and uses indexes for FTS operations is crucial for optimizing search performance.

## Full-Text Index Basics

Before diving into index selection, let's understand what full-text indexes are:

- Full-text indexes are special indexes designed specifically for text searching
- They can only be created on `CHAR`, `VARCHAR`, or `TEXT` columns
- They work with `MATCH() ... AGAINST()` syntax
- Available in MyISAM (all versions) and InnoDB (MySQL 5.6+)

## Index Selection Process for FTS

### 1. MATCH() Clause Requirement

MySQL will **only use a full-text index** when you use the `MATCH() ... AGAINST()` syntax. Regular `WHERE` clauses with `LIKE` will not trigger full-text index usage.

```sql
-- Uses full-text index (if exists)
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('database');

-- Does NOT use full-text index
SELECT * FROM articles 
WHERE title LIKE '%database%';
```

### 2. Exact Column Match

The columns specified in the `MATCH()` clause must **exactly match** the columns in a full-text index.

```sql
-- If you have: FULLTEXT INDEX ft_idx (title, body)

-- ✓ Will use ft_idx
MATCH(title, body) AGAINST('search term')

-- ✓ Will use ft_idx (order doesn't matter)
MATCH(body, title) AGAINST('search term')

-- ✗ Will NOT use ft_idx (missing column)
MATCH(title) AGAINST('search term')

-- ✗ Will NOT use ft_idx (extra column)
MATCH(title, body, author) AGAINST('search term')
```

### 3. Multiple Full-Text Indexes

If you have multiple full-text indexes on a table, MySQL will select the one that matches your `MATCH()` clause:

```sql
-- Table with multiple FTS indexes:
-- FULLTEXT INDEX ft_title (title)
-- FULLTEXT INDEX ft_body (body)
-- FULLTEXT INDEX ft_both (title, body)

-- Uses ft_title
MATCH(title) AGAINST('mysql')

-- Uses ft_body
MATCH(body) AGAINST('mysql')

-- Uses ft_both
MATCH(title, body) AGAINST('mysql')
```

### 4. Query Optimizer Considerations

The MySQL query optimizer makes decisions based on:

#### Cost Estimation
- MySQL estimates the cost of using the full-text index versus a table scan
- For very small tables, a table scan might be chosen instead
- The optimizer considers factors like:
  - Number of matching rows expected
  - Table size
  - Available memory
  - Other indexes that might be used in combination

#### Boolean Mode vs Natural Language Mode
The search mode affects how the index is used:

```sql
-- Natural Language Mode (default)
MATCH(title) AGAINST('mysql database')

-- Boolean Mode (more control)
MATCH(title) AGAINST('+mysql -oracle' IN BOOLEAN MODE)

-- Query Expansion Mode (finds related terms)
MATCH(title) AGAINST('database' WITH QUERY EXPANSION)
```

### 5. Combining FTS with Other Conditions

When you combine full-text search with other WHERE conditions, MySQL must decide how to execute the query:

```sql
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('mysql')
  AND publish_date > '2020-01-01'
  AND category_id = 5;
```

The optimizer might:
1. Use the full-text index to find matching documents
2. Apply other filters afterward
3. Or use a different strategy if other indexes are more selective

You can check the execution plan with `EXPLAIN`:

```sql
EXPLAIN SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('mysql');
```

## Key Selection Rules

Here's a summary of how MySQL selects FTS indexes:

1. **Automatic Selection**: If a `MATCH()` clause exactly matches a full-text index, MySQL will typically use it
2. **One Index Per MATCH()**: Each `MATCH()` clause can only use one full-text index
3. **No Partial Matches**: The index must include all columns in the `MATCH()` clause
4. **Optimizer Override**: You can force index usage with `USE INDEX` or `FORCE INDEX`:

```sql
SELECT * FROM articles USE INDEX (ft_title_body)
WHERE MATCH(title, body) AGAINST('mysql');
```

## Performance Considerations

### Index Selection Tips

1. **Create targeted indexes**: Don't create too many full-text indexes; they consume disk space and slow down writes
2. **Match your queries**: Design indexes based on your most common search patterns
3. **Monitor with EXPLAIN**: Always check query execution plans
4. **Consider index hints**: Use when the optimizer makes poor choices

### Common Pitfalls

```sql
-- Pitfall 1: Column subset won't use combined index
-- Index: FULLTEXT(title, body, tags)
-- Query: MATCH(title, body) AGAINST('text')  -- Won't use the index!

-- Pitfall 2: LIKE instead of MATCH
-- This ignores your full-text index:
WHERE title LIKE '%mysql%'  

-- Use this instead:
WHERE MATCH(title) AGAINST('mysql')
```

## Checking Index Usage

### Using EXPLAIN
```sql
EXPLAIN SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('database' IN BOOLEAN MODE);
```

Look for:
- `type: fulltext` - indicates full-text index is being used
- `key: index_name` - shows which index is selected
- `rows: estimate` - estimated number of rows to examine

### Using SHOW INDEXES
```sql
SHOW INDEXES FROM articles WHERE Index_type = 'FULLTEXT';
```

This shows all full-text indexes on the table.

## Practical Example

```python
import mysql.connector

# Connect to MySQL
conn = mysql.connector.connect(
    host='localhost',
    user='your_user',
    password='your_password',
    database='your_db'
)

cursor = conn.cursor()

# Check what indexes exist
cursor.execute("""
    SHOW INDEXES FROM articles 
    WHERE Index_type = 'FULLTEXT'
""")
indexes = cursor.fetchall()
print("Full-text indexes:", indexes)

# Run a full-text search
cursor.execute("""
    SELECT id, title, MATCH(title, body) AGAINST(%s) AS relevance
    FROM articles
    WHERE MATCH(title, body) AGAINST(%s)
    ORDER BY relevance DESC
    LIMIT 10
""", ('python mysql', 'python mysql'))

results = cursor.fetchall()
for row in results:
    print(f"ID: {row[0]}, Title: {row[1]}, Relevance: {row[2]}")

# Check execution plan
cursor.execute("""
    EXPLAIN SELECT * FROM articles 
    WHERE MATCH(title, body) AGAINST('python')
""")
plan = cursor.fetchall()
print("Execution plan:", plan)

cursor.close()
conn.close()
```

## Summary

MySQL's full-text index selection is relatively straightforward but requires precision:

- The `MATCH()` columns must exactly match an existing full-text index
- MySQL automatically selects the appropriate index when the columns match
- The query optimizer weighs the cost of using the index versus other strategies
- You can verify and influence index selection using `EXPLAIN` and index hints

Understanding these principles helps you design effective full-text indexes and write optimized search queries for your applications.
