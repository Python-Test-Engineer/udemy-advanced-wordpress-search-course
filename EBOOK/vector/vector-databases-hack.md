# Building Your Own Vector Database in MySQL with PHP (ELI5)

## What's a Vector Database Anyway?

Imagine you have a magical library where books that are "similar" automatically sit next to each other, even if they have different titles! That's what a vector database does with data.

**A vector** is just a list of numbers that represents the "meaning" of something:

```
"pizza" → [0.9, 0.1, 0.2, 0.8]
"pasta" → [0.8, 0.2, 0.3, 0.7]  ← Similar to pizza!
"car"   → [0.1, 0.8, 0.9, 0.1]  ← Very different!
```

## The Magic Trick: Using LONGTEXT Column

Since MySQL doesn't have a vector field, we'll **hack it** by storing vectors as text in a `LONGTEXT` column!

```
┌─────┬───────────────┬──────────────────────────────────┐
│ id  │     text      │       vector (LONGTEXT)          │
├─────┼───────────────┼──────────────────────────────────┤
│  1  │  "pizza"      │  "0.9,0.1,0.2,0.8"               │
│  2  │  "pasta"      │  "0.8,0.2,0.3,0.7"               │
│  3  │  "car"        │  "0.1,0.8,0.9,0.1"               │
└─────┴───────────────┴──────────────────────────────────┘
```

---

## Step 1: Create the Database Table

```sql
CREATE TABLE vector_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text VARCHAR(500) NOT NULL,
    vector LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_text (text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Step 2: The Math Behind Similarity

To find similar items, we use **Cosine Similarity**. Think of it like measuring the "angle" between two arrows:


![Cosine Similarity](../images/vectors/cosine-similarity.jpg)

**Formula:**
```
Similarity = (A·B) / (|A| × |B|)
```

Where:
- `A·B` = dot product (multiply matching numbers and add them up)
- `|A|` = magnitude (length of the vector)


---


## How It Works (Visual Flow)

```
┌─────────────────────────────────────────────────────────┐
│  1. USER INPUT                                          │
│     "pizza"                                             │
└────────────┬────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────┐
│  2. CONVERT TO VECTOR (Embedding)                       │
│     [0.9, 0.1, 0.2, 0.8, 0.3, ...]                      │
└────────────┬────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────┐
│  3. LOAD ALL VECTORS FROM DATABASE                      │
│     "pizza" → "0.9,0.1,0.2,0.8,0.3,..."                 │
│     "pasta" → "0.8,0.2,0.3,0.7,0.4,..."                 │
│     "car"   → "0.1,0.8,0.9,0.1,0.2,..."                 │
└────────────┬────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────┐
│  4. CALCULATE SIMILARITY (Cosine)                       │
│     pizza vs pizza → 1.00 (100%)                        │
│     pizza vs pasta → 0.95 (95%)                         │
│     pizza vs car   → 0.12 (12%)                         │
└────────────┬────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────┐
│  5. SORT BY SIMILARITY & RETURN TOP RESULTS             │
│     1. pizza (100%)                                     │
│     2. pasta (95%)                                      │
│     3. Italian food (87%)                               │
└─────────────────────────────────────────────────────────┘
```

---

## Limitations & When to Upgrade

### This Approach is Slow When:
- You have 10,000+ vectors (loads all into PHP memory)
- You need sub-second search times
- Vector dimensions are very large (1000+)

###  When to Use Real Vector DB:

- Production apps with millions of vectors
- Real-time search requirements
- Use: Pinecone, Weaviate, Milvus, or pgvector (PostgreSQL extension)

### his Approach Works Great For:

- Small to medium datasets (< 10,000 items)
- Learning and prototyping
- Side projects and MVPs
- When you already have MySQL and don't want extra infrastructure

---

## Summary

You just learned how to:
- ✅ Store vectors as comma-separated text in `LONGTEXT`
- ✅ Calculate cosine similarity in pure PHP
- ✅ Build a working vector search engine
- ✅ Integrate with OpenAI embeddings
- ✅ Optimize with caching and filtering

**The Magic:** MySQL stores the vectors as text, PHP does the math to find similar items. Simple but effective!