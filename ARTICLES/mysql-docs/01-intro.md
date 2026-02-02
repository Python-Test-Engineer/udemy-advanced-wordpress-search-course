# 14.9 Full-Text Search Functions

Rewritten and formatted by AI

## Table of Contents
1. [Introduction](#introduction)
2. [What is Full-Text Search?](#what-is-full-text-search)
3. [Basic Syntax](#basic-syntax)
4. [Requirements and Setup](#requirements-and-setup)
5. [The Three Search Modes](#the-three-search-modes)
6. [Practical Examples](#practical-examples)
7. [Performance Tips](#performance-tips)
8. [Important Restrictions](#important-restrictions)


## Introduction

Full-text search is a powerful MySQL feature that allows you to search through large amounts of text data efficiently. Unlike simple `LIKE` queries that scan every row, full-text search uses specialized indexes to find relevant matches quickly—even in tables with millions of rows.

**When should you use full-text search?**
- Searching blog posts, articles, or product descriptions
- Building search functionality for websites
- Finding relevant documents in large databases
- Implementing features like autocomplete or "related content"


## What is Full-Text Search?

Think of full-text search like the search feature in your word processor or web browser. Instead of looking for exact character-by-character matches, it understands words and can find relevant results even when the exact phrase doesn't appear.

### Traditional Search vs Full-Text Search

```
Traditional LIKE Search:
┌───────────────────────────────────────┐
│ WHERE description LIKE '%database%'   │
│                                       │
│ ❌ Scans every single row             │
│ ❌ Slow on large tables              │
│ ❌ No relevance ranking              │
└───────────────────────────────────────┘

Full-Text Search:
┌──────────────────────────────────────────┐
│ WHERE MATCH(description)                 │
│       AGAINST('database')                │
│                                          │
│ ✓ Uses specialized index                 │
│ ✓ Fast even on millions of rows          │
│ ✓ Returns results ranked by relevance    │
└──────────────────────────────────────────┘
```


## Basic Syntax

The core syntax for full-text search uses two functions working together:

```sql
MATCH(column1, column2, ...) AGAINST('search terms' [mode])
```

### Breaking Down the Syntax

```
┌────────────────────────────────────────────────────────────────────────────────┐
│                    Full Query Structure                                        │
├────────────────────────────────────────────────────────────────────────────────┤
│                                                                                │
│  SELECT * FROM articles                                                        │
│  WHERE MATCH(title, content)                                                   │
│        AGAINST('machine learning' IN NATURAL LANGUAGE MODE)                    │
│         │      │                │                │                             │
│         │      │                │                └─ Search mode (optional)
│         │      │                └─────────────── Search terms
│         │      └──────────────────────────────── Columns to search
│         └─────────────────────────────────────── MATCH function
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

**Important components:**

- **MATCH()**: Specifies which columns to search (must match your FULLTEXT index)
- **AGAINST()**: Contains your search terms and optional search mode
- **Search terms**: The text you're looking for (must be a constant string, not a column name)
- **Mode**: Determines how the search operates (we'll cover three modes below)


## Requirements and Setup

Before you can use full-text search, you need to create a special type of index called a FULLTEXT index.

### Supported Storage Engines

```
┌─────────────────────────────────────────┐
│         InnoDB (Recommended)            │
│  • Modern and actively developed        │
│  • Supports transactions                │
│  • Better for most use cases            │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│              MyISAM                     │
│  • Older storage engine                 │
│  • No transaction support               │
│  • Still supported but not recommended  │
└─────────────────────────────────────────┘
```

### Supported Column Types

Full-text indexes work only with text columns:
- **CHAR** - Fixed-length strings
- **VARCHAR** - Variable-length strings (most common)
- **TEXT** - Large text fields

### Creating a FULLTEXT Index

**Option 1: During table creation**
```sql
CREATE TABLE articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    content TEXT,
    author VARCHAR(100),
    FULLTEXT INDEX ft_title_content (title, content)
);
```

**Option 2: Add to existing table**
```sql
-- Using ALTER TABLE
ALTER TABLE articles 
ADD FULLTEXT INDEX ft_title_content (title, content);

-- Or using CREATE INDEX
CREATE FULLTEXT INDEX ft_title_content 
ON articles(title, content);
```

### Performance Tip: Loading Data

```
Best Practice for Large Datasets:
┌─────────────────────────────────────────────────┐
│                                                 │
│  1. Create table WITHOUT FULLTEXT index         │
│  2. Load all your data (INSERT statements)      │
│  3. THEN create the FULLTEXT index              │
│                                                 │
│  Why? Creating the index after data is loaded   │
│  is MUCH faster than inserting into an indexed  │
│  table row by row.                              │
│                                                 │
└─────────────────────────────────────────────────┘
```


## The Three Search Modes

MySQL offers three different ways to perform full-text searches, each with its own strengths.

### Overview Comparison

```
┌────────────────────────────────────────────────────────────────┐
│                    Search Mode Comparison                       │
├──────────────────┬─────────────────────────────────────────────┤
│                  │                                             │
│  Natural         │  Simple, human-friendly searching           │
│  Language        │  Best for: Basic search boxes               │
│  (Default)       │  Example: "machine learning"                │
│                  │                                             │
├──────────────────┼─────────────────────────────────────────────┤
│                  │                                             │
│  Boolean         │  Advanced control with operators            │
│  Mode            │  Best for: Power users, complex queries     │
│                  │  Example: "+machine +learning -neural"      │
│                  │                                             │
├──────────────────┼─────────────────────────────────────────────┤
│                  │                                             │
│  Query           │  Finds related content automatically        │
│  Expansion       │  Best for: "More like this" features        │
│                  │  Example: Expands "SQL" to include "MySQL"  │
│                  │                                             │
└──────────────────┴─────────────────────────────────────────────┘
```

---

### Mode 1: Natural Language Search (Default)

This is the simplest and most common mode. It treats your search string as plain text, just like you'd type into Google.

**How it works:**
```
User searches for: "machine learning"
                    ↓
MySQL finds documents containing these words
                    ↓
Results ranked by relevance
                    ↓
Most relevant documents appear first
```

**Syntax:**
```sql
-- These two queries are identical:
SELECT * FROM articles 
WHERE MATCH(title, content) AGAINST('machine learning');

SELECT * FROM articles 
WHERE MATCH(title, content) AGAINST('machine learning' IN NATURAL LANGUAGE MODE);
```

**Features:**
- Double quotes create phrase searches: `"machine learning"` finds that exact phrase
- Stopwords (common words like "the", "a", "is") are automatically ignored
- Results are automatically ranked by relevance
- Simple and user-friendly

**Example Use Case:**
```sql
-- Find articles about Python programming
SELECT title, content, 
       MATCH(title, content) AGAINST('Python programming') AS relevance
FROM articles
WHERE MATCH(title, content) AGAINST('Python programming')
ORDER BY relevance DESC
LIMIT 10;
```

---

### Mode 2: Boolean Search

Boolean mode gives you precise control using special operators. It's like using advanced search filters.

**Syntax:**
```sql
SELECT * FROM articles 
WHERE MATCH(title, content) 
AGAINST('+machine +learning -neural' IN BOOLEAN MODE);
```

**Boolean Operators:**

```
┌──────────────────────────────────────────────────────────────┐
│                   Boolean Search Operators                    │
├──────────┬───────────────────────────────────────────────────┤
│ Operator │ Meaning                         │ Example         │
├──────────┼─────────────────────────────────┼─────────────────┤
│    +     │ Word MUST be present            │ +machine        │
│    -     │ Word must NOT be present        │ -neural         │
│    >     │ Increase word importance        │ >learning       │
│    <     │ Decrease word importance        │ <basic          │
│    *     │ Wildcard (word starts with)     │ data*           │
│   "..."  │ Exact phrase                    │ "deep learning" │
│    ()    │ Group words together            │ +(machine AI)   │
└──────────┴─────────────────────────────────┴─────────────────┘
```

**Practical Examples:**

```sql
-- Must contain "machine" AND "learning", but NOT "neural"
AGAINST('+machine +learning -neural' IN BOOLEAN MODE)

-- Must contain "Python" and at least one of "Django" or "Flask"
AGAINST('+Python +(Django Flask)' IN BOOLEAN MODE)

-- Find anything starting with "data" (database, dataset, etc.)
AGAINST('data*' IN BOOLEAN MODE)

-- Exact phrase with importance boost
AGAINST('"machine learning" >Python' IN BOOLEAN MODE)
```

**When to use Boolean mode:**
- Advanced search interfaces
- When users need precise control
- Filtering out unwanted content
- Combining multiple search criteria

---

### Mode 3: Query Expansion

Query expansion is like having MySQL help you find related content. It performs the search twice, using results from the first search to improve the second.

**How it works:**
```
Step 1: Search for "SQL"
        ↓
        Finds top matches
        ↓
Step 2: Extracts common words from those matches
        Example: "MySQL", "database", "query", "SELECT"
        ↓
Step 3: Searches again with expanded terms
        ↓
        Returns results from second search
```

**Syntax:**
```sql
-- Both of these work:
SELECT * FROM articles 
WHERE MATCH(title, content) 
AGAINST('SQL' WITH QUERY EXPANSION);

SELECT * FROM articles 
WHERE MATCH(title, content) 
AGAINST('SQL' IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION);
```

**Visual Example:**

```
Original Search: "database"
        ↓
First Pass Results:
├─ "Understanding MySQL Databases"
├─ "PostgreSQL vs MySQL Comparison"  
└─ "SQL Query Optimization"
        ↓
Expansion Terms Extracted:
- MySQL
- PostgreSQL
- SQL
- query
- optimization
        ↓
Second Pass (Final Results):
├─ Original results PLUS
├─ "Advanced SQL Techniques"
├─ "MySQL Performance Tuning"
└─ "Database Design Principles"
```

**When to use Query Expansion:**
- "More like this" features
- When users might not know the best search terms
- Finding related content
- Exploratory searches

**⚠️ Warning:** Query expansion can sometimes return less relevant results if your initial search matches poor-quality documents. Use with caution.

---

## Practical Examples

Let's see full-text search in action with a realistic scenario.

### Setup: Creating a Blog Database

```sql
-- Create a blog articles table
CREATE TABLE blog_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    content TEXT,
    author VARCHAR(100),
    published_date DATE,
    FULLTEXT INDEX ft_search (title, content)
) ENGINE=InnoDB;

-- Insert sample data
INSERT INTO blog_posts (title, content, author, published_date) VALUES
('Introduction to Machine Learning', 
 'Machine learning is a subset of artificial intelligence...', 
 'John Doe', '2024-01-15'),
('Python Data Science Tutorial', 
 'Learn how to use Python for data analysis and visualization...', 
 'Jane Smith', '2024-02-20'),
('Deep Neural Networks Explained', 
 'Neural networks are inspired by biological neurons...', 
 'Bob Johnson', '2024-03-10');
```

### Example 1: Simple Search

```sql
-- Find all posts about "machine learning"
SELECT title, author,
       MATCH(title, content) AGAINST('machine learning') AS relevance_score
FROM blog_posts
WHERE MATCH(title, content) AGAINST('machine learning')
ORDER BY relevance_score DESC;
```

**Output:**
```
title                              | author    | relevance_score
-----------------------------------+-----------+----------------
Introduction to Machine Learning   | John Doe  | 1.847
Deep Neural Networks Explained     | Bob J.    | 0.234
```

### Example 2: Boolean Search with Multiple Criteria

```sql
-- Find posts about Python but NOT about neural networks
SELECT title, author
FROM blog_posts
WHERE MATCH(title, content) 
AGAINST('+Python -neural' IN BOOLEAN MODE);
```

### Example 3: Using in SELECT Clause for Ranking

```sql
-- Get relevance scores without filtering
SELECT title,
       MATCH(title, content) AGAINST('Python data') AS score
FROM blog_posts
ORDER BY score DESC
LIMIT 5;
```

### Example 4: Combining with Other WHERE Conditions

```sql
-- Recent posts about Python, ranked by relevance
SELECT title, published_date,
       MATCH(title, content) AGAINST('Python') AS relevance
FROM blog_posts
WHERE MATCH(title, content) AGAINST('Python')
  AND published_date >= '2024-01-01'
ORDER BY relevance DESC;
```



## Performance Tips

### Tip 1: Index Creation Timing

```
❌ SLOW: Insert → Insert → Insert → Create Index
✓ FAST: Create Index → Insert → Insert → Insert (small tables)
✓ FASTEST: Insert ALL → Then Create Index (large tables)
```

For large datasets (thousands of rows or more), load your data first, then create the FULLTEXT index.

### Tip 2: Column Selection

```sql
-- ❌ Less efficient: Searching columns you don't need
MATCH(title, content, tags, author) AGAINST('search term')

-- ✓ More efficient: Only search relevant columns
MATCH(title, content) AGAINST('search term')
```

Only include columns in your FULLTEXT index that users will actually search.

### Tip 3: Relevance Scoring

```sql
-- Use the relevance score to sort results
SELECT title,
       MATCH(title, content) AGAINST('keyword') AS relevance
FROM articles
WHERE MATCH(title, content) AGAINST('keyword')
ORDER BY relevance DESC
LIMIT 20;
```

MySQL calculates relevance scores automatically—use them to show the best matches first!



## Important Restrictions

### Search String Must Be Constant

```sql
-- ✓ CORRECT: String literal
WHERE MATCH(title) AGAINST('machine learning')

-- ❌ WRONG: Cannot use column values
WHERE MATCH(title) AGAINST(other_column)

-- ❌ WRONG: Cannot use variables in this way
WHERE MATCH(title) AGAINST(CONCAT(var1, ' ', var2))
```

The search string must be a constant value, not computed from table data.

### Limitations with GROUP BY and ROLLUP

MySQL has specific restrictions when combining MATCH() with `GROUP BY ... WITH ROLLUP`:

```sql
-- ❌ NOT ALLOWED: MATCH() in SELECT with ROLLUP
SELECT MATCH(title) AGAINST('text') FROM articles 
GROUP BY category WITH ROLLUP;

-- ❌ NOT ALLOWED: MATCH() in HAVING with ROLLUP
SELECT category FROM articles 
GROUP BY category WITH ROLLUP 
HAVING MATCH(title) AGAINST('text');

-- ❌ NOT ALLOWED: MATCH() in ORDER BY with ROLLUP
SELECT category FROM articles 
GROUP BY category WITH ROLLUP 
ORDER BY MATCH(title) AGAINST('text');

-- ✓ ALLOWED: MATCH() in WHERE clause with ROLLUP
SELECT category FROM articles 
WHERE MATCH(title) AGAINST('text')
GROUP BY category WITH ROLLUP;
```

**Simple rule:** With ROLLUP, only use MATCH() in the WHERE clause.

### Storage Engine Requirements

- **Must use InnoDB or MyISAM** - Other storage engines don't support FULLTEXT indexes
- **Column types:** Only CHAR, VARCHAR, and TEXT columns can be indexed

### Stopwords

Common words (called "stopwords") are ignored in searches:
- Examples: "the", "a", "an", "is", "at", "which"
- These are filtered out automatically to improve performance
- You can customize the stopword list if needed



## International Language Support

MySQL provides specialized parsers for different languages:

### ngram Parser (Chinese, Japanese, Korean)

```sql
-- Create index with ngram parser
CREATE FULLTEXT INDEX ft_content ON articles(content) 
WITH PARSER ngram;
```

Useful for CJK languages that don't use spaces between words.

### MeCab Parser (Japanese)

```sql
-- Create index with MeCab parser
CREATE FULLTEXT INDEX ft_content ON articles(content) 
WITH PARSER mecab;
```

Provides more accurate parsing for Japanese text.



## Debugging and Utilities

### myisam_ftdump Utility

For MyISAM tables, you can use the `myisam_ftdump` utility to inspect full-text indexes:

```bash
myisam_ftdump table_name index_number
```

This is helpful for debugging and understanding how your full-text index stores data.



## Summary: Quick Reference Card

```
┌──────────────────────────────────────────────────────────────┐
│              MySQL Full-Text Search Cheat Sheet              │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│ CREATE INDEX:                                                │
│   CREATE FULLTEXT INDEX name ON table(col1, col2);           │
│                                                              │
│ NATURAL LANGUAGE (default):                                  │
│   WHERE MATCH(cols) AGAINST('search terms')                  │
│                                                              │
│ BOOLEAN MODE:                                                │
│   WHERE MATCH(cols) AGAINST('+word -exclude' IN BOOLEAN MODE)│
│                                                              │
│ QUERY EXPANSION:                                             │
│   WHERE MATCH(cols) AGAINST('term' WITH QUERY EXPANSION)     │
│                                                              │
│ OPERATORS (Boolean mode):                                    │
│   +   must have        -   must not have                     │
│   >   boost            <   reduce importance                 │
│   *   wildcard         ""  exact phrase                      │
│                                                              │
│ REQUIREMENTS:                                                │
│   • InnoDB or MyISAM engine                                  │
│   • CHAR, VARCHAR, or TEXT columns only                      │
│   • Search string must be constant (not a column)            │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

<br>