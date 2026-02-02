# Vector Distance Metrics: 

**Example vectors used throughout:**  
A = (2, 1)  
B = (6, 4)

This version focuses on how each metric behaves when used inside a WordPress search plugin, a custom MySQL table, or a hybrid search pipeline (e.g., TF‑IDF + embeddings).

---

## **Why WordPress Developers Should Care**

When you add vector search to WordPress — whether via a plugin, a custom table, or an external vector DB — you need to choose a **distance metric**.  
This choice affects:

- how relevant your search results feel  
- how your ranking behaves  
- how well embeddings match user intent  
- how expensive queries are in MySQL or external services  

Different metrics produce very different results, even on the same embeddings.

---

# **The Metrics (with diagrams)**

## **Euclidean Distance**  
Straight‑line distance between A and B.

```
   y
   |
 4 |                B
 3 |
 2 |
 1 |      A
   |
   +---------------------------- x
       2    3    4    5    6
```

**WordPress/MySQL relevance meaning:**  
Good when vector magnitude matters — e.g., embeddings where “stronger meaning” produces longer vectors.

**Typical use:**  
- Custom MySQL vector tables  
- External vector DBs that default to L2  

---

## **Manhattan Distance (L1)**  
Grid‑based movement.

```
   y
   |
 4 |                B
 3 |                |
 2 |                |
 1 |      A -------+
   |
   +---------------------------- x
```

**WordPress/MySQL relevance meaning:**  
More stable than Euclidean in high‑dimensional spaces.  
Useful if your embeddings are sparse (rare in NLP, common in bag‑of‑words vectors).

**Typical use:**  
- Rare for semantic embeddings  
- Useful for custom scoring experiments  

---

## **Cosine Distance**  
Angle between vectors, ignoring length.

```
Origin
  |
  |   A
  |  /
  | /
  |/___________
       \
        \
         B
```

**WordPress/MySQL relevance meaning:**  
The gold standard for semantic search.  
Perfect when using sentence embeddings (e.g., OpenAI, Cohere, local models).

**Typical use:**  
- WordPress semantic search plugins  
- Hybrid search (BM25 + embeddings)  
- Most vector DBs default to cosine for text  

---

## **Dot Product**  
Alignment × magnitude.

```
Origin
  |
  |   A →→
  |  /
  | /
  |/___________ →→ B (longer)
```

**WordPress/MySQL relevance meaning:**  
Good when embedding length encodes confidence or intensity.  
Some embedding models are trained specifically for dot‑product scoring.

**Typical use:**  
- Recommender systems (products, posts, related content)  
- Models where magnitude matters  

---

## **Chebyshev Distance**  
Largest single‑dimension difference.

```
   y
   |
 4 |                B
 3 |
 2 |
 1 |      A
   |
   +---------------------------- x
```

**WordPress/MySQL relevance meaning:**  
Not used for semantic search.  
Useful only in niche cases where you care about the worst‑case deviation.

**Typical use:**  
- Outlier detection  
- Quality‑control pipelines  

---

## **Hamming Distance**  
Counts mismatched positions.

```
A = (2,1)
B = (6,4)

Differences:
- 2 vs 6 → different
- 1 vs 4 → different

Hamming distance = 2
```

**WordPress/MySQL relevance meaning:**  
Not used for embeddings.  
Useful only for binary vectors (e.g., hashed fingerprints).

**Typical use:**  
- Duplicate detection  
- Hash‑based similarity  

---

# **Summary Table (WordPress/MySQL Context)**

| Metric | What It Measures | Best For | WordPress Use Case |
|--------|------------------|----------|---------------------|
| Euclidean | Straight‑line distance | Geometric similarity | Custom vector tables, general semantic search |
| Manhattan | Grid‑based movement | Sparse/high‑dimensional data | Rare; experimental scoring |
| Cosine | Angle between vectors | Semantic similarity | Best choice for WordPress semantic search |
| Dot Product | Alignment × magnitude | Recommenders | Related posts, product suggestions |
| Chebyshev | Largest coordinate difference | Outlier detection | Data validation, anomaly detection |
| Hamming | Count of mismatches | Binary vectors | Duplicate detection, hashing |

---

# **How This Fits Into WordPress Search Architecture**

### **1. Classic WordPress Search (LIKE queries)**  
No vector search. No distance metrics.

### **2. MySQL Full‑Text Search (MATCH AGAINST)**  
Still lexical — no vectors.

### **3. Hybrid Search (BM25 + Embeddings)**  
You combine:  
- BM25 from MySQL  
- Cosine similarity from embeddings  

This is where **cosine distance** shines.

### **4. Full Vector Search in MySQL**  
If you store embeddings in a custom table (e.g., `FLOAT[]` or JSON), you can compute:  
- Euclidean  
- Manhattan  
- Dot product  
- Cosine (with normalization)
