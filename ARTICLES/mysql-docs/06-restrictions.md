# 14.9.5 Full-Text Restrictions

Rewritten and formatted by AI.

# MySQL Full-Text Search Restrictions

## Overview

Full-text search in MySQL has several important limitations and restrictions you should be aware of before implementing it in your database. This guide summarizes the key restrictions.


## Storage Engine Limitations

### Supported Engines
- **InnoDB** - Fully supported ✓
- **MyISAM** - Fully supported ✓
- **All other engines** - Not supported ✗

### Partitioned Tables
Full-text searches are **not supported** on partitioned tables. If you need partitioning, you cannot use FULLTEXT indexes on those tables.


## Character Set and Language Support

### Supported Character Sets

**Unicode Support:**
- **utf8mb3** - Supported ✓
- **utf8mb4** - Supported ✓
- **ucs2** - Not supported for FULLTEXT indexes ✗
- **utf16** - Not supported for FULLTEXT indexes ✗
- **utf16le** - Not supported for FULLTEXT indexes ✗
- **utf32** - Not supported for FULLTEXT indexes ✗

**Important Exception:**
You can perform `IN BOOLEAN MODE` searches on ucs2 columns even without a FULLTEXT index, though performance will be slower.

### Same Character Set Required

All columns in a single FULLTEXT index must use:
- The same character set
- The same collation

You cannot mix different character sets within one FULLTEXT index.


## Ideographic Language Support

### The Challenge

Languages like Chinese, Japanese, and Korean (CJK) don't use spaces to separate words. This creates problems for the built-in full-text parser, which relies on word delimiters.

**Example:**
```
English:   "machine learning tutorial"  (clear word boundaries)
Chinese:   "机器学习教程"                  (no spaces between concepts)
```

### Solutions

MySQL provides specialized parsers for these languages:

**1. ngram Parser (Character-based)**
- Supports: Chinese, Japanese, Korean
- Works with: InnoDB and MyISAM
- Approach: Breaks text into fixed-length character sequences

**2. MeCab Parser (Word-based)**
- Supports: Japanese only
- Works with: InnoDB and MyISAM
- Approach: Uses dictionary-based word segmentation


## Query Restrictions

### MATCH() Column List Rules

The columns specified in `MATCH()` must exactly match a FULLTEXT index definition.

**Example:**
```sql
-- You have this index:
CREATE FULLTEXT INDEX ft_idx ON articles(title, content);

-- ✓ CORRECT: Matches the index exactly
WHERE MATCH(title, content) AGAINST('search term')

-- ✗ WRONG: Doesn't match any index
WHERE MATCH(title) AGAINST('search term')

-- ✗ WRONG: Doesn't match any index
WHERE MATCH(content, title) AGAINST('search term')  -- Order matters!
```

**Exception for MyISAM:**
MyISAM tables can use `IN BOOLEAN MODE` on non-indexed columns, but performance will be poor:
```sql
-- Works on MyISAM, but slow:
WHERE MATCH(non_indexed_column) AGAINST('term' IN BOOLEAN MODE)
```

### AGAINST() Argument Must Be Constant

The search string in `AGAINST()` must be a constant value at query time.

```sql
-- ✓ CORRECT: String literal
WHERE MATCH(title) AGAINST('machine learning')

-- ✓ CORRECT: Variable/parameter (constant per query execution)
WHERE MATCH(title) AGAINST(?)

-- ✗ WRONG: Cannot use column values
WHERE MATCH(title) AGAINST(description)

-- ✗ WRONG: Cannot use values that vary by row
WHERE MATCH(title) AGAINST(CONCAT(prefix_col, ' ', suffix_col))
```

**Why?** The search term must be the same for all rows being evaluated, not computed differently for each row.

### Rollup Column Restriction

You cannot use a rollup column as an argument to `MATCH()`.

```sql
-- ✗ NOT ALLOWED
SELECT category, MATCH(category) AGAINST('tech') 
FROM articles 
GROUP BY category WITH ROLLUP;
```


## Index Hints Limitation

Index hints (like `USE INDEX`, `FORCE INDEX`, `IGNORE INDEX`) are more limited with FULLTEXT searches compared to regular indexes.

**What this means:**
You have less control over query optimization when using full-text searches. MySQL's query optimizer makes most decisions automatically.


## InnoDB Transaction Behavior

### DML Operations Processed at Commit Time

For InnoDB tables, full-text index updates happen when transactions commit, not immediately.

**How it works:**
```
1. You INSERT a row with text
   ↓
2. Text is tokenized into individual words
   ↓
3. Words are added to FULLTEXT index tables
   ↓
4. BUT this only happens when you COMMIT
```

**Important Implications:**

```sql
BEGIN;
INSERT INTO articles (title, content) VALUES 
    ('New Article', 'This is about machine learning');
-- At this point, the text is NOT yet in the full-text index

-- Other users searching won't find this article yet
SELECT * FROM articles WHERE MATCH(title, content) AGAINST('machine learning');
-- Returns nothing for the new article

COMMIT;
-- NOW the text is tokenized and added to the index
-- NOW other users can find it in searches
```

**Key Point:** Full-text searches only return **committed data**. Uncommitted changes are not searchable, even within the same transaction.


## Wildcard Restrictions

### The '%' Character Is Not Supported

Unlike `LIKE` queries, the `%` wildcard does not work in full-text searches.

```sql
-- ✗ WRONG: '%' doesn't work in full-text search
WHERE MATCH(title) AGAINST('machine%')

-- ✓ CORRECT: Use the asterisk wildcard in BOOLEAN MODE
WHERE MATCH(title) AGAINST('machine*' IN BOOLEAN MODE)
```

**Available Wildcard:**
Only the asterisk (`*`) works as a wildcard in `IN BOOLEAN MODE`:

```sql
-- Finds: machine, machines, machinery, etc.
WHERE MATCH(content) AGAINST('machin*' IN BOOLEAN MODE)
```


## Quick Reference: Common Mistakes

```
┌─────────────────────────────────────────────────────────────────┐
│                    Common Mistakes to Avoid                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│ ✗ Using with partitioned tables                                 │
│ ✗ Mixing character sets in one FULLTEXT index                   │
│ ✗ MATCH() columns not matching an index exactly                 │
│ ✗ Using column values in AGAINST()                              │
│ ✗ Using '%' as a wildcard                                       │
│ ✗ Expecting uncommitted data to be searchable (InnoDB)          │
│ ✗ Using ucs2/utf16/utf32 without understanding the limitations  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```


## Best Practices

### 1. Choose the Right Storage Engine

- Use **InnoDB** for most applications (transactions, better concurrency)
- Use **MyISAM** only if you need boolean searches on non-indexed columns

### 2. Plan Your Character Sets

- Use **utf8mb4** for full Unicode support
- Ensure all columns in a FULLTEXT index use the same character set
- For CJK languages, use ngram or MeCab parsers

### 3. Design Indexes Carefully

- Create FULLTEXT indexes that match your query patterns
- Remember that `MATCH()` must exactly match an index definition
- Consider creating multiple indexes for different search scenarios

### 4. Understand Transaction Behavior (InnoDB)

- Remember that new data isn't searchable until committed
- Plan for this in real-time applications
- Consider the delay between INSERT and searchability

### 5. Use Correct Wildcards

- Use `*` (asterisk) in BOOLEAN MODE, not `%`
- Only the asterisk wildcard is supported


## Summary Checklist

Before implementing full-text search, verify:

- [ ] You're using InnoDB or MyISAM
- [ ] Table is not partitioned
- [ ] All FULLTEXT index columns use the same character set
- [ ] Your MATCH() columns exactly match a FULLTEXT index
- [ ] Your AGAINST() arguments are constants, not column references
- [ ] You're not using '%' as a wildcard
- [ ] You understand the transaction commit behavior (InnoDB)
- [ ] You've considered ngram/MeCab parsers for CJK languages


## Related Documentation

- **Section 26.6**: Restrictions and Limitations on Partitioning
- **Section 10.9.4**: Index Hints
- **Section 14.9.8**: ngram Full-Text Parser
- **Section 14.9.9**: MeCab Full-Text Parser Plugin

<br>