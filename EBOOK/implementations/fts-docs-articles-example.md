# MySQL Docs example

This uses a custom table `articles` that is used in the official MySQL docs.

## What is MySQL Full-Text Search?

MySQL Full-Text Search lets you search through text columns **FAST** without using slow `LIKE` queries!

### The Problem with LIKE:

```sql
-- Slow query (scans entire table!) 
SELECT * FROM articles 
WHERE body LIKE '%machine learning%';

Time: 5 seconds for 1 million rows ❌
Cannot use indexes efficiently
```

### The Solution: Full-Text Search

```sql
--
-- Table structure for table `articles`
--

CREATE TABLE IF NOT EXISTS `articles` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `body` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Fast query (uses full-text index!) ⚡
SELECT * FROM articles 
WHERE MATCH(body) AGAINST('machine learning');

Time: 0.05 seconds for 1 million rows ✅
Uses specialized full-text indexes
```

**Speed improvement: 100x faster!** 


## Three Types of Full-Text Search in MySQL

MySQL offers **3 different search modes**, each with unique capabilities:

```
┌─────────────────────────────────────────────────┐
│  1. NATURAL LANGUAGE MODE (Default)             │
│     - Simple, relevance-based search            │
│     - Like Google search                        │
│     - Most commonly used                        │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  2. BOOLEAN MODE                                │
│     - Advanced operators (+, -, *, "")          │
│     - Precise control over search               │
│     - Like advanced Google operators            │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  3. QUERY EXPANSION MODE                        │
│     - Two-pass search                           │
│     - Finds related terms automatically         │
│     - Best for broad exploration                │
└─────────────────────────────────────────────────┘
```

## Type 1: NATURAL LANGUAGE MODE

### What It Does:

Searches like you're talking naturally - just type your query!

**Key Features:**
- ✅ Ranks results by relevance (most relevant first)
- ✅ Ignores words that appear in 50%+ of rows (too common)
- ✅ Automatically handles word variations
- ✅ No special operators needed

### Visual Representation:

```
User Query: "machine learning tutorial"
              ↓
┌─────────────────────────────────────────────────┐
│  MySQL breaks it into words:                    │
│  • machine                                      │
│  • learning                                     │
│  • tutorial                                     │
└─────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────┐
│  Searches documents for these words             │
│  Calculates relevance score for each            │
└─────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────┐
│  RESULTS (Sorted by Score):                     │
│  📄 Doc 1: Score 2.5 ⭐⭐⭐⭐⭐              │
│  📄 Doc 2: Score 1.8 ⭐⭐⭐⭐                 │
│  📄 Doc 3: Score 0.9 ⭐⭐                      │
└─────────────────────────────────────────────────┘
```

### Example Query:

```sql
-- Basic natural language search
SELECT 
    id, 
    title,
    MATCH(title, body) AGAINST('mysql database') AS relevance
FROM articles
WHERE MATCH(title, body) AGAINST('mysql database')
ORDER BY relevance DESC;
```

### Step-by-Step Example:

**Table: articles**
```
ID | Title                              | Body
---+------------------------------------+----------------------------------
1  | MySQL Database Tutorial            | Learn MySQL from scratch...
2  | PostgreSQL vs MySQL                | Comparing databases...
3  | Python Programming Guide           | Python basics for beginners...
4  | MySQL Performance Tips             | Optimize your MySQL database...
5  | Database Design Principles         | Good database design matters...
```

**Query:**
```sql
SELECT title, 
       MATCH(title, body) AGAINST('mysql database') AS score
FROM articles
WHERE MATCH(title, body) AGAINST('mysql database')
ORDER BY score DESC;
```

**Results:**
```
┌─────────────────────────────────┬────────┐
│ Title                           │ Score  │
├─────────────────────────────────┼────────┤
│ MySQL Database Tutorial         │  3.45  │ ⭐⭐⭐⭐⭐
│ MySQL Performance Tips          │  2.10  │ ⭐⭐⭐⭐
│ PostgreSQL vs MySQL             │  1.75  │ ⭐⭐⭐
│ Database Design Principles      │  0.85  │ ⭐⭐
└─────────────────────────────────┴────────┘

(Python Programming Guide not returned - no matches)
```

**Why these scores?**
```
Article 1: "MySQL Database Tutorial"
  • "mysql" in title (high weight) ████████
  • "database" in title (high weight) ████████
  • Both terms in body ██████
  Total: 3.45 ⭐⭐⭐⭐⭐

Article 4: "MySQL Performance Tips"
  • "mysql" in title ████████
  • "database" in body only ████
  Total: 2.10 ⭐⭐⭐⭐

Article 2: "PostgreSQL vs MySQL"
  • "mysql" in title ████████
  • "database" in body (implied) ██
  Total: 1.75 ⭐⭐⭐

Article 5: "Database Design Principles"
  • "database" in title ████
  • No "mysql" mention
  Total: 0.85 ⭐⭐
```

## Type 2: BOOLEAN MODE

### What It Does:

Gives you **precise control** with special operators!

**Key Features:**
- ✅ Use operators: `+` (must have), `-` (must not have), `*` (wildcard)
- ✅ Use quotes `""` for exact phrases
- ✅ Combine multiple conditions
- ✅ No automatic relevance ranking (you control it!)

### Boolean Operators:

```
┌──────────┬─────────────────────────────────────────┐
│ Operator │ Meaning                                 │
├──────────┼─────────────────────────────────────────┤
│    +     │ MUST be present                         │
│    -     │ MUST NOT be present                     │
│   ""     │ Exact phrase match                      │
│    *     │ Wildcard (tech* = technology, technical)│
│    ()    │ Group terms                             │
│    >     │ Increase word importance                │
│    <     │ Decrease word importance                │
│    ~     │ Negation (reduce rank if present)       │
└──────────┴─────────────────────────────────────────┘
```

### Visual Examples:

#### Example 1: Must Include (+)

```
Query: "+mysql +tutorial"
       (MUST have mysql AND MUST have tutorial)

Document Analysis:
  📄 Doc 1: "MySQL Tutorial for Beginners"
     mysql ✓ | tutorial ✓ → MATCH! ✅
  
  📄 Doc 2: "MySQL Database Guide"
     mysql ✓ | tutorial ✗ → NO MATCH ❌
  
  📄 Doc 3: "SQL Tutorial"
     mysql ✗ | tutorial ✓ → NO MATCH ❌
```

#### Example 2: Must Exclude (-)

```
Query: "+database -oracle"
       (MUST have database, MUST NOT have oracle)

Document Analysis:
  📄 Doc 1: "MySQL Database Tutorial"
     database ✓ | oracle ✗ → MATCH! ✅
  
  📄 Doc 2: "Oracle Database Administration"
     database ✓ | oracle ✓ → NO MATCH ❌
  
  📄 Doc 3: "Database Design Principles"
     database ✓ | oracle ✗ → MATCH! ✅
```

#### Example 3: Exact Phrase ("")

```
Query: '"machine learning"'
       (Exact phrase, words must be adjacent)

Document Analysis:
  📄 Doc 1: "machine learning algorithms"
     "machine learning" ✓ → MATCH! ✅
  
  📄 Doc 2: "machine vision and learning"
     "machine learning" ✗ (not adjacent) → NO MATCH ❌
  
  📄 Doc 3: "learning about machine code"
     "machine learning" ✗ (wrong order) → NO MATCH ❌
```

#### Example 4: Wildcard (*)

```
Query: "develop*"
       (Matches: develop, developer, development, developing)

Document Analysis:
  📄 Doc 1: "web development tutorial"
     develop* ✓ (development) → MATCH! ✅
  
  📄 Doc 2: "hire a developer"
     develop* ✓ (developer) → MATCH! ✅
  
  📄 Doc 3: "developing software"
     develop* ✓ (developing) → MATCH! ✅
```

### Complex Boolean Query Example:

```sql
-- Find articles about MySQL or PostgreSQL tutorials,
-- but NOT about Oracle, and must mention "beginner"
SELECT title, body
FROM articles
WHERE MATCH(title, body) 
AGAINST('+beginner +(mysql postgresql) -oracle tutorial*' IN BOOLEAN MODE);
```

**Visual breakdown:**
```
┌────────────────────────────────────── ───────────┐
│  Query Components:                               │
├─────────────────────────────────── ──────────────┤
│  +beginner           MUST have "beginner"        │
│  +(mysql postgresql) MUST have mysql OR postgres │
│  -oracle             MUST NOT have "oracle"      │
│  tutorial*           Optional, matches tutorial* │
└─────────────────────────────────────────────── ──┘

Documents:
  📄 "MySQL Tutorial for Beginners"
     beginner ✓ | mysql ✓ | oracle ✗ | tutorial ✓
     Result: MATCH! ✅⭐⭐⭐⭐⭐
  
  📄 "PostgreSQL Beginner Guide"
     beginner ✓ | postgresql ✓ | oracle ✗ | tutorial ✗
     Result: MATCH! ✅⭐⭐⭐⭐
  
  📄 "Oracle Tutorial for Beginners"
     beginner ✓ | oracle ✓ (rejected!)
     Result: NO MATCH ❌
  
  📄 "MySQL Advanced Topics"
     mysql ✓ | beginner ✗ (missing!)
     Result: NO MATCH ❌
```

### Real-World Boolean Examples:

```sql
-- Example 1: Technical documentation search
SELECT title, body
FROM articles
WHERE MATCH(title, body) 
AGAINST('+"REST API" +authentication -deprecated' IN BOOLEAN MODE);

-- Example 2: Product search
SELECT product_name, description
FROM products
WHERE MATCH(product_name, description) 
AGAINST('+laptop +"16GB RAM" -refurbished' IN BOOLEAN MODE);

-- Example 3: Recipe search
SELECT recipe_name, ingredients
FROM recipes
WHERE MATCH(recipe_name, ingredients) 
AGAINST('+vegetarian +protein -tofu -tempeh' IN BOOLEAN MODE);

-- Example 4: Job search
SELECT title, description
FROM job_postings
WHERE MATCH(title, description) 
AGAINST('+python +(django flask) +remote -junior' IN BOOLEAN MODE);
```

## Type 3: QUERY EXPANSION MODE

### What It Does:

Performs a **two-pass search** to find related content!

**Key Features:**
- ✅ First search finds most relevant documents
- ✅ Extracts common terms from those results
- ✅ Second search uses expanded term list
- ✅ Finds documents you might have missed
- ✅ Great for exploratory searches

### How Query Expansion Works:

```
┌─────────────────────────────────────────────────┐
│  PASS 1: Initial Search                         │
│  Query: "database"                              │
│                                                 │
│  Top Results Found:                             │
│  📄 MySQL Database Administration               │
│  📄 PostgreSQL Performance Tuning               │
│  📄 Database Normalization Guide                │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│  ANALYSIS: Extract Related Terms                │
│                                                 │
│  Common words in top results:                   │
│  • database (original)                          │
│  • mysql                                        │
│  • postgresql                                   │
│  • sql                                          │
│  • query                                        │
│  • table                                        │
│  • index                                        │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│  PASS 2: Expanded Search                        │
│  New Query: "database mysql postgresql sql      │
│              query table index"                 │
│                                                 │
│  Additional Results Found:                      │
│  📄 SQL Query Optimization                      │
│  📄 Index Design Best Practices                 │
│  📄 Table Partitioning Strategies               │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│  FINAL RESULTS: All Combined                    │
│  (Original + Expanded)                          │
└─────────────────────────────────────────────────┘
```

### Example Query:

```sql
-- Query expansion for broad search
SELECT 
    title,
    MATCH(title, body) AGAINST('mysql' WITH QUERY EXPANSION) AS relevance
FROM articles
WHERE MATCH(title, body) AGAINST('mysql' WITH QUERY EXPANSION)
ORDER BY relevance DESC
LIMIT 20;
```

### Step-by-Step Example:

**Original Query:** "authentication"

**Pass 1 - Find Top Matches:**
```
Top 5 Documents Found:
┌───────────────────────────────────────┐
│ 1. User Authentication Methods        │
│ 2. OAuth 2.0 Implementation Guide     │
│ 3. JWT Token Authentication           │
│ 4. Session-Based Authentication       │
│ 5. Two-Factor Authentication Setup    │
└───────────────────────────────────────┘
```

**Analysis - Extract Common Terms:**
```
Words frequently appearing with "authentication":
┌─────────────────────────────────────────┐
│ • authentication (original)             │
│ • oauth                                 │
│ • token                                 │
│ • jwt                                   │
│ • session                               │
│ • login                                 │
│ • password                              │
│ • security                              │
│ • user                                  │
│ • 2fa / two-factor                      │
└─────────────────────────────────────────┘
```

**Pass 2 - Search with Expanded Terms:**
```
Additional Documents Found:
┌───────────────────────────────────────┐
│ 6. Login Security Best Practices      │
│ 7. Password Hashing Algorithms        │
│ 8. User Session Management            │
│ 9. Security Token Generation          │
│ 10. Bearer Token Implementation       │
│ 11. SSO Single Sign-On Guide          │
└───────────────────────────────────────┘
```

### Comparison: Normal vs Query Expansion

```sql
-- WITHOUT Query Expansion
SELECT title FROM articles
WHERE MATCH(title, body) AGAINST('machine learning')
ORDER BY MATCH(title, body) AGAINST('machine learning') DESC
LIMIT 10;

-- Results (5 matches):
--   📄 Machine Learning Basics
--   📄 Introduction to Machine Learning
--   📄 Machine Learning Algorithms
--   📄 Machine Learning with Python
--   📄 Supervised Machine Learning
```

```sql
-- WITH Query Expansion
SELECT title FROM articles
WHERE MATCH(title, body) AGAINST('machine learning' WITH QUERY EXPANSION)
ORDER BY MATCH(title, body) AGAINST('machine learning' WITH QUERY EXPANSION) DESC
LIMIT 10;

-- Results (15+ matches):
--   📄 Machine Learning Basics (original)
--   📄 Introduction to Machine Learning (original)
--   📄 Machine Learning Algorithms (original)
--   📄 Machine Learning with Python (original)
--   📄 Supervised Machine Learning (original)
--   📄 Neural Networks Overview (expanded)
--   📄 Deep Learning Fundamentals (expanded)
--   📄 AI Model Training (expanded)
--   📄 Data Science Workflows (expanded)
--   📄 Classification Algorithms (expanded)
```

### When to Use Query Expansion:

```
✅ GOOD USE CASES:
  • Exploratory research
  • When you're not sure of exact terms
  • Finding related content
  • Broad topic searches
  • Documentation searches
  • Learning about new topics

❌ AVOID WHEN:
  • You need exact matches
  • Precision is critical
  • Results are already good
  • Database is small (<1000 rows)
  • Performance is critical
```

### Visual Comparison of All Three Modes:

```
Query: "database optimization"

┌────────────────────────────────────────────────┐
│  NATURAL LANGUAGE MODE                         │
│  Returns: Documents with both words,           │
│           ranked by relevance                  │
│                                                │
│  Results:                                      │
│  📄 Database Optimization Guide (Score: 3.2)  │
│  📄 MySQL Database Performance (Score: 2.1)    │
│  📄 Query Optimization Tips (Score: 1.8)       │
└────────────────────────────────────────────────┘

┌────────────────────────────────────────────────┐
│  BOOLEAN MODE                                  │
│  Query: +database +optimization                │
│  Returns: Only documents with BOTH words       │
│                                                │
│  Results:                                      │
│  📄 Database Optimization Guide                │
│  📄 Advanced Database Optimization             │
│  (Exact matches only, no scoring)              │
└────────────────────────────────────────────────┘

┌────────────────────────────────────────────────┐
│  QUERY EXPANSION MODE                          │
│  Pass 1: "database optimization"               │
│  Pass 2: + performance, indexing, tuning, etc. │
│  Returns: Original + related documents         │
│                                                │
│  Results:                                      │
│  📄 Database Optimization Guide                │
│  📄 MySQL Database Performance                 │
│  📄 Query Optimization Tips                    │
│  📄 Index Performance Tuning (expanded)        │
│  📄 Database Query Speed (expanded)            │
│  📄 Performance Monitoring (expanded)          │
└────────────────────────────────────────────────┘
```

## Combining Modes with Other SQL Features

### Example 1: Pagination with Full-Text Search

```sql
-- Natural language with pagination
SELECT 
    id,
    title,
    SUBSTRING(body, 1, 200) AS snippet,
    MATCH(title, body) AGAINST('mysql tutorial') AS score
FROM articles
WHERE MATCH(title, body) AGAINST('mysql tutorial')
ORDER BY score DESC
LIMIT 20 OFFSET 40;  -- Page 3 (20 results per page)
```

**Visual:**
```
Results 41-60 of 245 total matches
┌────┬───────────────────────────┬───────┐
│ ID │ Title                     │ Score │
├────┼───────────────────────────┼───────┤
│ 87 │ MySQL Stored Procedures   │  2.34 │
│ 92 │ MySQL Triggers Tutorial   │  2.31 │
│ 103│ MySQL Views Explained     │  2.28 │
│ ... showing 20 results          │
└────┴───────────────────────────┴───────┘
```

### Example 2: Filtering by Date and Category

```sql
-- Boolean search with additional filters
SELECT 
    title,
    author,
    publish_date,
    category
FROM articles
WHERE MATCH(title, body) 
AGAINST('+python +tutorial -beginner' IN BOOLEAN MODE)
AND category IN ('programming', 'data-science')
AND publish_date > DATE_SUB(NOW(), INTERVAL 6 MONTH)
ORDER BY publish_date DESC;
```

**Logic Flow:**
```
┌─────────────────────────────────────────────────┐
│  STEP 1: Full-Text Filter                      │
│  ✓ Must have "python"                           │
│  ✓ Must have "tutorial"                         │
│  ✗ Must NOT have "beginner"                     │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│  STEP 2: Category Filter                       │
│  ✓ Category: "programming" OR "data-science"    │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│  STEP 3: Date Filter                           │
│  ✓ Published within last 6 months               │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│  STEP 4: Sort by Date                          │
│  ↓ Newest first                                 │
└─────────────────────────────────────────────────┘
```

### Example 3: Highlighting Search Terms

```sql
-- Show context around search terms
SELECT 
    id,
    title,
    -- Get snippet with search term highlighted
    SUBSTRING_INDEX(
        SUBSTRING_INDEX(body, 'authentication', 2),
        'authentication', -1
    ) AS context,
    MATCH(title, body) AGAINST('authentication') AS relevance
FROM articles
WHERE MATCH(title, body) AGAINST('authentication')
ORDER BY relevance DESC;
```

## Real-World Use Cases

### Use Case 1: E-commerce Product Search

```sql
-- Natural language for relevance-based product search
SELECT 
    product_id,
    product_name,
    price,
    MATCH(product_name, description) AGAINST('wireless headphones') AS relevance
FROM products
WHERE MATCH(product_name, description) AGAINST('wireless headphones')
AND price BETWEEN 50 AND 200
AND in_stock = 1
ORDER BY relevance DESC, price ASC
LIMIT 50;
```

**Why this works:**
```
┌─────────────────────────────────────────────────┐
│  Natural Language Mode Benefits:                │
│  • "wireless headphones" ranks products with    │
│    both words higher                            │
│  • Automatically handles variations:            │
│    - "Bluetooth headphones" (related term)      │
│    - "wireless earbuds" (similar product)       │
│  • Price and stock filters narrow results       │
└─────────────────────────────────────────────────┘
```

### Use Case 2: Blog Post Search with Filters

```sql
-- Boolean mode for precise filtering
SELECT title, author, publish_date
FROM blog_posts
WHERE MATCH(title, body) 
AGAINST('+javascript +tutorial -jquery -deprecated' IN BOOLEAN MODE)
AND publish_date > DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

**Logic Flow:**
```
┌─────────────────────────────────────────────┐
│  Requirements:                              │
│  ✓ Must have "javascript"                   │
│  ✓ Must have "tutorial"                     │
│  ✗ Must NOT have "jquery"                   │
│  ✗ Must NOT have "deprecated"               │
│  ✓ Published within last year               │
└─────────────────────────────────────────────┘

Results:
  ✅ "Modern JavaScript Tutorial 2024"
  ✅ "JavaScript ES6 Tutorial Guide"
  ❌ "jQuery Tutorial for Beginners" (has jquery)
  ❌ "JavaScript Basics from 2020" (too old)
```

### Use Case 3: Documentation Search

```sql
-- Query expansion for comprehensive results
SELECT 
    doc_title,
    category,
    url,
    MATCH(doc_title, body) AGAINST('authentication') AS score
FROM documentation
WHERE MATCH(doc_title, body) 
AGAINST('authentication' WITH QUERY EXPANSION)
ORDER BY score DESC
LIMIT 50;
```

**Expansion Process:**
```
Original: "authentication"
    ↓
Found docs mention:
  • authentication (original)
  • login
  • security
  • password
  • oauth
  • jwt
    ↓
Searches again with expanded terms
    ↓
Returns comprehensive results:
  📄 "Authentication Methods"
  📄 "OAuth 2.0 Implementation"
  📄 "JWT Token Guide"
  📄 "Login Security Best Practices"
  📄 "Password Hashing"
  📄 "Two-Factor Authentication"
```

## Important Configuration

### Minimum Word Length

```sql
-- Check current minimum word length
SHOW VARIABLES LIKE 'ft_min_word_len';

-- Default: 4 (words must be 4+ characters)
-- Example: "car" won't be indexed, "cars" will be
```

**Visual:**
```
ft_min_word_len = 4

Indexed:
  ✅ "mysql" (5 chars)
  ✅ "database" (8 chars)
  ✅ "tutorial" (8 chars)

NOT Indexed:
  ❌ "sql" (3 chars)
  ❌ "php" (3 chars)
  ❌ "car" (3 chars)
```

**To change (requires restart):**
```sql
-- In my.cnf or my.ini
[mysqld]
ft_min_word_len = 3
innodb_ft_min_token_size = 3

-- Then rebuild indexes
ALTER TABLE articles DROP INDEX ft_idx;
ALTER TABLE articles ADD FULLTEXT INDEX ft_idx (title, body);
```

### Stopwords (Ignored Words)

MySQL ignores common words like: "the", "is", "at", "which", "on", etc.

```
┌─────────────────────────────────────────────┐
│  Default Stopwords (36 words):              │
│  a, about, an, are, as, at, be, by, com,    │
│  for, from, how, in, is, it, of, on, or,    │
│  that, the, this, to, was, what, when,      │
│  where, who, will, with, the, www           │
└─────────────────────────────────────────────┘
```

**Example:**
```
Query: "the best mysql tutorial"
Actually searches: "best mysql tutorial"
(Ignores "the")
```

### 50% Threshold Rule

Words appearing in 50%+ of rows are ignored in **Natural Language Mode**!

```
Table with 100 rows:

Word "database":
  Appears in 60 rows (60%)
  Result: IGNORED ❌
  
Word "mysql":
  Appears in 30 rows (30%)
  Result: USED ✅
```

**Solution:** Use Boolean Mode to bypass this rule!

```sql
-- Natural language (ignores common words)
MATCH(body) AGAINST('database')

-- Boolean mode (includes all words)
MATCH(body) AGAINST('database' IN BOOLEAN MODE)
```

## Performance Tips

### 1. Index Only What You Search

```sql
-- ❌ Bad: Index everything
ALTER TABLE articles 
ADD FULLTEXT INDEX ft_all (title, body, author, tags, comments);

-- ✅ Good: Index only searched columns
ALTER TABLE articles 
ADD FULLTEXT INDEX ft_search (title, body);
```

### 2. Use Smaller Indexes for Faster Searches

```
┌──────────────────────────────────────────────┐
│  Index Size vs Speed:                        │
├──────────────────────────────────────────────┤
│  Title only:          10 MB  ⚡⚡⚡⚡⚡    │
│  Title + Body:        50 MB  ⚡⚡⚡⚡      │
│  Everything:         200 MB  ⚡⚡           │
└──────────────────────────────────────────────┘
```

### 3. Limit Results

```sql
-- Always use LIMIT for better performance
SELECT title, body
FROM articles
WHERE MATCH(title, body) AGAINST('mysql')
LIMIT 100;
```

### 4. Use Covering Indexes

```sql
-- Include frequently selected columns in index
ALTER TABLE articles 
ADD FULLTEXT INDEX ft_idx (title, body);

-- Query only indexed columns (faster!)
SELECT title FROM articles
WHERE MATCH(title, body) AGAINST('mysql');
```

## Full-Text Search vs LIKE

```
┌───────────────────────────────────────────────────┐
│  Feature          │  LIKE          │  Full-Text   │
├───────────────────┼────────────────┼──────────────┤
│  Speed (1M rows)  │  5-10 sec      │  0.05 sec    │
│  Relevance Score  │  No            │  Yes         │
│  Word Boundary    │  No            │  Yes         │
│  Boolean Ops      │  No            │  Yes         │
│  Phrase Search    │  Manual        │  Built-in    │
│  Wildcards        │  % _           │  *           │
│  Index Support    │  Limited       │  Specialized │
│  Memory Usage     │  Low           │  High        │
└───────────────────┴────────────────┴──────────────┘
```

**Example comparison:**

```sql
-- LIKE (slow, no relevance)
SELECT * FROM articles 
WHERE body LIKE '%mysql%' 
AND body LIKE '%tutorial%';
Time: 8 seconds ❌

-- Full-Text (fast, with relevance)
SELECT *, MATCH(body) AGAINST('mysql tutorial') AS score
FROM articles 
WHERE MATCH(body) AGAINST('mysql tutorial')
ORDER BY score DESC;
Time: 0.05 seconds ✅
```

---

## Quick Reference

### Natural Language Mode:
```sql
-- Simple relevance search
MATCH(column) AGAINST('search terms')
```

### Boolean Mode:
```sql
-- Precise control
MATCH(column) AGAINST('+must -not "exact phrase" wild*' IN BOOLEAN MODE)
```

### Query Expansion:
```sql
-- Broad exploration
MATCH(column) AGAINST('term' WITH QUERY EXPANSION)
```

### Common Patterns:

```sql
-- Pattern 1: Basic search with score
SELECT title, MATCH(title) AGAINST('mysql') AS relevance
FROM articles
WHERE MATCH(title) AGAINST('mysql')
ORDER BY relevance DESC;

-- Pattern 2: Multi-column search
SELECT * FROM articles
WHERE MATCH(title, body) AGAINST('database tutorial');

-- Pattern 3: Combined with other conditions
SELECT * FROM articles
WHERE MATCH(title, body) AGAINST('mysql')
AND author = 'John Doe'
AND publish_date > '2024-01-01';

-- Pattern 4: Boolean with required terms
SELECT * FROM articles
WHERE MATCH(title, body) 
AGAINST('+mysql +innodb -myisam' IN BOOLEAN MODE);
```

## Best Practices Summary

```
✅ DO:
  • Create full-text indexes on searched columns
  • Use appropriate mode for your use case
  • Limit results with LIMIT
  • Use Boolean mode for precise searches
  • Test different modes for best results

❌ DON'T:
  • Index columns you don't search
  • Use LIKE for full-text searches
  • Forget about the 50% threshold
  • Over-use query expansion
  • Skip testing with real data
```

## Setup Guide

### Creating a Full-Text Index:

```sql
-- Method 1: During table creation
CREATE TABLE articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    body TEXT,
    author VARCHAR(100),
    publish_date DATE,
    FULLTEXT INDEX ft_idx (title, body)
) ENGINE=InnoDB;

-- Method 2: Add to existing table
ALTER TABLE articles 
ADD FULLTEXT INDEX ft_idx (title, body);

-- Method 3: Create separate index
CREATE FULLTEXT INDEX ft_title_body 
ON articles(title, body);
```

### Checking Existing Indexes:

```sql
-- Show all indexes on a table
SHOW INDEX FROM articles;

-- Check if full-text index exists
SHOW INDEX FROM articles 
WHERE Index_type = 'FULLTEXT';
```

### Dropping a Full-Text Index:

```sql
-- Drop by name
ALTER TABLE articles DROP INDEX ft_idx;

-- Or using DROP INDEX
DROP INDEX ft_idx ON articles;
```

## Troubleshooting

### Problem: No Results Returned

```sql
-- Check 1: Verify index exists
SHOW INDEX FROM articles WHERE Index_type = 'FULLTEXT';

-- Check 2: Test minimum word length
SHOW VARIABLES LIKE 'ft_min_word_len';

-- Check 3: Try Boolean mode (bypasses 50% rule)
SELECT * FROM articles
WHERE MATCH(body) AGAINST('searchterm' IN BOOLEAN MODE);
```

### Problem: Slow Queries

```sql
-- Check 1: Use EXPLAIN
EXPLAIN SELECT * FROM articles
WHERE MATCH(title, body) AGAINST('mysql');

-- Check 2: Add LIMIT
SELECT * FROM articles
WHERE MATCH(title, body) AGAINST('mysql')
LIMIT 100;

-- Check 3: Query only indexed columns
SELECT title FROM articles
WHERE MATCH(title, body) AGAINST('mysql');
```

### Problem: Unexpected Results

```sql
-- Check 1: View relevance scores
SELECT title, 
       MATCH(title, body) AGAINST('mysql') AS score
FROM articles
WHERE MATCH(title, body) AGAINST('mysql')
ORDER BY score DESC;

-- Check 2: Test with Boolean mode for exact control
SELECT title FROM articles
WHERE MATCH(title, body) 
AGAINST('+mysql' IN BOOLEAN MODE);
```

---

**Remember:** Full-Text Search is powerful but requires proper setup and understanding of its modes and limitations. Always test with your actual data to find the best configuration!

<br>