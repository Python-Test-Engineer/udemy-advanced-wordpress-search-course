# MySQL Docs example

This uses a custom table `articles` that is used in the official MySQL docs.

[https://dev.mysql.com/doc/refman/8.4/en/fulltext-natural-language.html](https://dev.mysql.com/doc/refman/8.4/en/fulltext-natural-language.html)


<p class="author">My additions are formatted like this.</p>

## What is MySQL Full-Text Search?

MySQL Full-Text Search lets you search through text columns **FAST** without using slow `LIKE` queries!

## Create FTS index

Two ways:

1 After table creation:

```sql
CREATE TABLE IF NOT EXISTS  (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `body` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_

-- Using ALTER TABLE
ALTER TABLE articles 
ADD FULLTEXT INDEX ft_title_body (title, body);

ALTER TABLE articles 
ADD FULLTEXT INDEX ft_title (title);

ALTER TABLE articles 
ADD FULLTEXT INDEX ft_body (body);

-- Or using CREATE INDEX
CREATE FULLTEXT INDEX ft_title_body
ON articles(title, body);

```

2 As part of table creation:

```sql
CREATE TABLE IF NOT EXISTS articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    body TEXT,
    FULLTEXT INDEX ft_title_body (title, body)
);
```

### The Problem with LIKE:

```sql
-- Slow query (scans entire table!) 
SELECT * FROM articles 
WHERE body LIKE '%MySQL Tutorial%';
```
Time: 5 seconds for 1 million rows for example

❌ Cannot use indexes efficiently

### The Solution: Full-Text Search

```sql
--
-- Table structure for table `articles`
--

-- Fast query (uses full-text index!) ⚡
SELECT * FROM articles 
WHERE MATCH(body) AGAINST('MySQL Tutorial');

SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('MySQL Tutorial');

-- Getting relative scores and ordering
SELECT id, title, body, 
MATCH (title,body) AGAINST ('MySQL Tutorial') AS score
FROM articles ORDER BY score DESC;
```

<p class="author">MySQL's MATCH syntax is used for full-text searching and follows the pattern MATCH(column1, column2, ...) AGAINST('search terms' [search_modifier]), where you can search one or more text columns for specific words or phrases. </p>

<p class="author">The critical requirement is that you must have a FULLTEXT index on the exact same column(s) in the same order that you specify in the MATCH claus — for example, if you use MATCH(title, body), you need a FULLTEXT index defined as FULLTEXT(title, body).</p>

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
    body,
    MATCH(title, body) AGAINST('mysql database') AS relevance
FROM articles
WHERE MATCH(title, body) AGAINST('mysql database'  IN NATURAL LANGUAGE MODE) 
--  IN NATURAL LANGUAGE MODE is the default so not required
ORDER BY relevance DESC;
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
│    ()    │ Group terms (mysql or postgres)         │
│    >     │ Increase word importance                │
│    <     │ Decrease word importance                │
│    ~     │ Negation (reduce rank if present)       │
└──────────┴─────────────────────────────────────────┘
```
### Scoring is possilbe

```sql
-- Let's look at output as rows with 'tutorial*' are returned but with 0 score
SELECT id, title, body, MATCH (title,body)  
AGAINST ('+mysql -tutorial*' IN BOOLEAN MODE) AS score
FROM articles ORDER BY score DESC;

```

### Visual Examples:

#### Example 1: Must Include (+)

```
Query: "+mysql +tutorial"
       (MUST have mysql AND MUST have tutorial - "+mysql tutorial" means must have 'mysql' and can or cannot have' tutorial')

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
Query: "+mysql -YourSQL"
       (MUST have database, MUST NOT have oracle)

Document Analysis:

  📄 Doc 1: "MySQL Database YourSQL"
     database ✓ | YourSQL ✗ → MATCH! ✅
  
  📄 Doc 2: "Oracle Database Administration"
     database ✓ | oracle ✓ → NO MATCH ❌
  
  📄 Doc 3: "Database Design Principles"
     MySQL ✓ | oracle ✗ →  NO MATCH ❌
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
  *We will see later that we can specify a distance between terms.*

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
-- Find articles about (MySQL or PostgreSQL) or tutorials,
-- but NOT about Oracle
SELECT title, body
FROM articles
WHERE MATCH(title, body) 
AGAINST(' +(mysql postgresql) -oracle tutorial*' IN BOOLEAN MODE);
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

*We will se an actual example in `PLUGIN05 WP PRODUCTS` but for now we will describe it.*

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

## Visual Comparison of All Three Modes:

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

<br>