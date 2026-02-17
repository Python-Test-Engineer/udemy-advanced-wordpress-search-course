# Query Processing Overview

We will look at an actual example after this bit of theory.

When MySQL receives a full-text search query, it goes through several stages:

## Process

### Query Parsing

MySQL parses the search string to identify search terms and operators. 

It recognizes special syntax like boolean operators (+, -, >, <, ~, *, ""), stopwords, and phrase searches. 

The parser tokenizes the query string similar to how documents were indexed.

### Stemming

This is where 'run', 'running', 'runs' are stripped down into the core term 'run'.

### Stopword Filtering

MySQL filters out stopwords (common words like "the", "is", "at") based on the configured stopword list. 

These are ignored because they appear too frequently to be useful for relevance ranking.

### Index Lookup

MySQL uses the FULLTEXT index to quickly locate documents containing the search terms. 

The index is typically organized as an inverted index, (IDF), where each word maps to a list of documents (and positions within those documents) where it appears.

### Relevance Calculation

For each matching document, MySQL calculates a relevance score based on:

- Term frequency (how often the word appears in the document)
- Inverse document frequency (how rare the word is across all documents)
- Document length normalization
- Word proximity (for phrase searches - covered later...)

The formula is similar to TF-IDF (Term Frequency-Inverse Document Frequency).

### Result Filtering and Sorting

MySQL filters results based on the WHERE clause conditions (including the MATCH() condition itself) and sorts results by relevance score (or other specified ORDER BY criteria).

This can enable pre-filtering to avoid searching for example all categories if we have specified categories in our search.

### Result Return

The final set of documents with relevancy scores is returned.

## An example

Finally, MySQL returns the matching rows with their relevance scores, applying any LIMIT clauses to restrict the result set size.

PROMPT: 

> "What WordPress plugins help me with SEO and other similar search techniques"

RESPONSE:

### Processing the Query: 

### BM25 Scoring Example

**Search Query:** "What WordPress plugins help me with SEO and other similar search techniques"

**Document Being Scored:** "Yoast SEO is the best WordPress SEO plugin for optimizing search rankings and implementing advanced SEO techniques"

**Corpus:** 10,000 documents total

---

| Search Term | How Many Documents Contain It? | IDF Score | Times in This Document | Contribution to Total |
|-------------|-------------------------------|-----------|----------------------|---------------------|
| **yoast** | 150 (very rare) | 1.82 | 1 | 1.82 × 0.9 × 1 = **1.638** |
| **seo** | 1,500 (moderately rare) | 0.82 | 3 | 0.82 × 1.5 × 3 = **3.690** |
| **best** | 6,000 (very common) | 0.22 | 1 | 0.22 × 0.9 × 1 = **0.198** |
| **wordpress** | 8,500 (very common) | 0.07 | 1 | 0.07 × 0.9 × 1 = **0.063** |
| **plugin** | 2,800 (moderately common) | 0.55 | 1 | 0.55 × 0.9 × 1 = **0.495** |
| **optimizing** | 1,200 (moderately rare) | 0.92 | 1 | 0.92 × 0.9 × 1 = **0.828** |
| **search** | 5,000 (common) | 0.30 | 1 | 0.30 × 0.9 × 1 = **0.270** |
| **rankings** | 900 (moderately rare) | 1.01 | 1 | 1.01 × 0.9 × 1 = **0.909** |
| **implementing** | 400 (rare) | 1.39 | 1 | 1.39 × 0.9 × 1 = **1.251** |
| **advanced** | 3,500 (moderately common) | 0.44 | 1 | 0.44 × 0.9 × 1 = **0.396** |
| **techniques** | 300 (very rare) | 1.52 | 1 | 1.52 × 0.9 × 1 = **1.368** |

### Final Score: 11.11 points

```
1.638 + 3.690 + 0.198 + 0.063 + 0.495 + 0.828 + 0.270 + 0.909 + 1.251 + 0.396 + 1.368 = 11.11
```

---

**Key Observations:**

- **"seo"** contributes most (appears 3 times in document)
- **"yoast"**, **"techniques"**, and **"implementing"** are very rare terms with high impact
- Common words like **"best"** and **"wordpress"** contribute minimally
- Perfect match between query and document = very high score
- Stop words like "is", "the", "for", "and" are typically filtered out before scoring
- **"seo"** dominates the score (appears 3 times AND moderately rare)
- **"techniques"** is very rare, contributing strongly despite appearing once
- **"plugins"** contributes nothing (not in document - notice "plugin" not "plugins")
- **"wordpress"** appears but contributes little due to being extremely common
- This document ranks highly due to multiple mentions of the rare term "seo"

### Final Score: 4.45 points

```
0.005 + 0.000 + 0.000 + 2.036 + 0.000 + 0.000 + 0.091 + 2.319 = 4.45
```

## Why This Score Makes Sense

**What matters most:**

1. **"techniques"** contributes **2.319 points** (52% of total score)

   - Only appears in 300 out of 10,000 documents (rare = valuable)
   
2. **"seo"** contributes **2.036 points** (45% of total score)

   - Appears 3 times in the document
   - Only in 1,500 documents (moderately rare)

3. **"search"** contributes **0.091 points** (2% of total score)

   - Appears in half the documents (common)

**What barely matters:**

4. **"wordpress"** and **"and"** together contribute only **0.006 points** (0.1% of total score)

   - They're in almost every document, so they don't help distinguish good matches

**Missing words don't hurt much:**

- The document doesn't contain "plugins", "help", "other", or "similar"
- But it still ranks highly because it has the important semantic terms

## The Key Insight

**Rare, specific words dominate the ranking.** 

A document mentioning "SEO techniques" will rank far higher than one mentioning "WordPress plugins help" because:

- "techniques" is rare (high value)
- "wordpress" is everywhere (low value)
- "help" is common (low value)

This is why MySQL Full-Text Search automatically finds the most relevant results without you needing to specify which words are important.

<br>