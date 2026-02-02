# Reranking

## The Principle of Combining FTS and Vector Search

Reranking is the process of **running two different searches**, each good at different things, and then **combining their strengths** to produce better results than either one could alone.

## In WordPress/MySQL terms:

- **FTS** (MATCH AGAINST) is great at keyword matching  
- **Vector search** is great at semantic meaning  
- **Reranking** blends them into one unified relevance score

This is the foundation of modern hybrid search.

## Why Combine Them?

FTS and vector search solve different problems:

- FTS knows *exact words*  
- Vector search knows *what the words mean*  

Reranking lets you use both signals at once.


## The Core Idea (Simple Diagram)

```
User Query
    |
    +------------------------------+
    |                              |
FTS Search                    Vector Search
(keyword match)            (semantic similarity)
    |                              |
    +--------------+---------------+
                   |
             Combine Signals
                   |
             Reranked Results
```

The system doesn’t replace one with the other — it **layers** them.



## How Developers Should Think About It

### **FTS answers:**  
“Does this document contain the words the user typed?”

### **Vector search answers:**  
“Does this document mean what the user meant?”

### **Reranking answers:**  

“How do we blend both signals to produce the most relevant list?”

## Why This Matters in WordPress/MySQL

### 1. FTS alone is brittle  
Misspellings, synonyms, and natural language phrasing break it.

### 2. Vector search alone is too fuzzy  
It may return semantically related content that doesn’t match the user’s intent.

### 3. Reranking gives you the best of both worlds  
- FTS ensures precision  
- Vectors ensure understanding  
- Reranking ensures balance  

This is exactly how modern search engines behave.


## A WordPress‑Centric View

### FTS strengths in WP 
- Fast in MySQL  
- Great for titles, slugs, product names  
- Good for exact intent (“blue shoes”, “ACF tutorial”)  

### Vector strengths in WP
- Understands meaning (“how to speed up my site” ≈ “WordPress performance tips”)  
- Handles long queries  
- Handles conversational queries  

### Reranking strengths in WP
- Perfect for blog search, product search, documentation search  
- Works beautifully with hybrid BM25 + embeddings  
- Gives users “Google‑like” relevance without replacing MySQL  

## Summary Table

- FTS - Keyword precision - Ensures exact matches and strong lexical signals -
- Vector Search - Semantic understanding - Captures meaning, synonyms, intent -
- Reranking - Blends both signals - Produces the most relevant final ranking 

<br>