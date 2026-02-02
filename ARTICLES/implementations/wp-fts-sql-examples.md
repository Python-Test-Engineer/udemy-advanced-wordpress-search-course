# Examples 


**The columns specified in MATCH() must exactly match a FULLTEXT index definition.**

If you have `post_title`, `post_content` in your query ther emust be an FTS index built on these two columns and not two indexes of just one column.

MySQL will select FTS indexes based on matching columns in query with index and has its own algorithms to determine which is best if there is more than one possibility, which is not recommended anyway.

We’ll cover:

1. **Natural Language Mode**  
2. **Boolean Mode**  
3. **Query Expansion Mode**

Each section now includes:

- A scenario  
- Multiple SQL examples  
- Explanation of why each query behaves the way it does  
- Notes on how WordPress developers might use it  

---

# Natural Language Mode  
Natural language mode is MySQL’s “Google‑like” search.  
It ranks results by relevance using TF‑IDF‑style scoring.

---

## Scenario: Searching blog posts about WordPress performance

### Example A — Simple multi‑word search

Create index for all the examples

```sql
CREATE FULLTEXT INDEX idx_posts_fulltext 
ON wp_posts(post_title, post_content);
```

Quereis... 

```sql
SELECT ID, post_title,
       MATCH(post_title, post_content)
       AGAINST ('wordpress performance caching' IN NATURAL LANGUAGE MODE) AS score
FROM wp_posts
WHERE MATCH(post_title, post_content)
      AGAINST ('wordpress performance caching' IN NATURAL LANGUAGE MODE)
ORDER BY score DESC;
```

**What happens:**  
- MySQL breaks the query into words  
- Computes relevance based on frequency  
- Posts heavily discussing caching rank highest  

---

### Example B — Ranking titles higher than content
```sql
SELECT ID, post_title,
       (MATCH(post_title) AGAINST ('performance tuning') * 3 +
        MATCH(post_content) AGAINST ('performance tuning')) AS relevance
FROM wp_posts
ORDER BY relevance DESC;
```

**Why this matters:**  
- Titles often signal intent  
- Weighted scoring improves result quality  

---

### Example C — Searching long‑form content

```sql
SELECT ID, post_title,
       MATCH(post_content)
       AGAINST ('object cache redis persistent' IN NATURAL LANGUAGE MODE) AS score
FROM wp_posts
ORDER BY score DESC;
```

**Use case:**  
Great for documentation sites or long tutorials.

---

### Example D — Natural language with stopwords
```sql
SELECT ID, post_title
FROM wp_posts
WHERE MATCH(post_title, post_content)
      AGAINST ('how to speed up a wordpress site' IN NATURAL LANGUAGE MODE);
```

**Note:**  
Words like *how*, *to*, *a* are ignored unless you customize stopwords.

---

# Boolean Mode  
Boolean mode gives you **precision control** using operators.

Perfect for admin dashboards, advanced search pages, or custom WP search endpoints.

---

## Scenario: Searching a knowledge base with strict rules

---

### Example A — Required + excluded terms
```sql
SELECT ID, post_title
FROM wp_posts
WHERE MATCH(post_title, post_content)
      AGAINST ('+wordpress +cache -plugin' IN BOOLEAN MODE);
```

**Meaning:**  
- Must contain “wordpress”  
- Must contain “cache”  
- Must NOT contain “plugin”  

Useful when users want to avoid plugin‑related results.

---

### Example B — Prefix matching
```sql
SELECT ID, post_title
FROM wp_posts
WHERE MATCH(post_title, post_content)
      AGAINST ('optimiz*' IN BOOLEAN MODE);
```

Matches:

- optimize  
- optimized  
- optimization  
- optimizer  

Great for autocomplete or “search as you type.”

---

### Example C — Exact phrase search
```sql
SELECT ID, post_title
FROM wp_posts
WHERE MATCH(post_title, post_content)
      AGAINST ('"object cache"' IN BOOLEAN MODE);
```

**Use case:**  
When users want precise technical terms.

---

### Example D — Combining phrase + required terms
```sql
SELECT ID, post_title
FROM wp_posts
WHERE MATCH(post_title, post_content)
      AGAINST ('+"object cache" +redis -memcached' IN BOOLEAN MODE);
```

**Meaning:**  
- Must contain the exact phrase “object cache”  
- Must contain “redis”  
- Must NOT contain “memcached”  

---

### **Example E — Boosting relevance with tilde (~)**
```sql
SELECT ID, post_title
FROM wp_posts
WHERE MATCH(post_title, post_content)
      AGAINST ('+cache ~redis' IN BOOLEAN MODE);
```

**Interpretation:**  
- “cache” is required  
- “redis” is optional but increases relevance  

---

# Query Expansion Mode  
Query expansion is MySQL’s “find related topics” mode.

It performs:

1. A first search  
2. Identifies common co‑occurring terms  
3. Expands the query  
4. Runs a second search  

---

## Scenario: A user searches for “SEO” but your content uses synonyms like “search ranking,” “organic traffic,” etc.

---

### **Example A — Basic query expansion**
```sql
SELECT ID, post_title,
       MATCH(post_title, post_content)
       AGAINST ('seo' WITH QUERY EXPANSION) AS score
FROM wp_posts
ORDER BY score DESC;
```

**What happens:**  
MySQL may expand “seo” to include:

- search engine  
- ranking  
- organic  
- traffic  
- optimization  

---

### **Example B — Multi‑word query expansion**
```sql
SELECT ID, post_title,
       MATCH(post_title, post_content)
       AGAINST ('wordpress security' WITH QUERY EXPANSION) AS score
FROM wp_posts
ORDER BY score DESC;
```

**Likely expansions:**  
- malware  
- firewall  
- brute force  
- hardening  
- login protection  

---

### **Example C — Discovering related topics**
```sql
SELECT ID, post_title,
       MATCH(post_title, post_content)
       AGAINST ('speed' WITH QUERY EXPANSION) AS score
FROM wp_posts;
```

**Why this is useful:**  
If your content uses synonyms like:

- performance  
- optimization  
- caching  
- TTFB  

MySQL will find them.

---

### Example D — Query expansion on product reviews
```sql
SELECT ID, post_title
FROM wp_posts
WHERE MATCH(post_title, post_content)
      AGAINST ('hosting' WITH QUERY EXPANSION);
```

**Possible expansions:**  
- uptime  
- bandwidth  
- shared hosting  
- VPS  
- CDN  

This is powerful for e‑commerce or affiliate sites.

---

# Summary Table

|      Mode        |             Best For              |               Examples                 |
|------------------|-----------------------------------|----------------------------------------|
| Natural Language | General search, relevance ranking | “wordpress caching tutorial”           |
| Boolean Mode     | Precision control                 | `+cache -plugin "object cache"`        |
| Query Expansion  | Discovering related topics        | `AGAINST ('seo' WITH QUERY EXPANSION)` |

<br>