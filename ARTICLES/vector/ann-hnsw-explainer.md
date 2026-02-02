# ANN and HNSW  

## What is Approximate Nearest Neighbor (ANN)?
ANN is a fast method for finding vectors that are *close enough* to a query point — without scanning the entire database. It’s widely used in search engines, recommendation systems, and vector databases.

Instead of checking every vector, ANN algorithms:
- Divide the space into **partitions** (like clusters or cells)
- Search only within the partition where the query lands
- Return the closest match *within that partition*

This makes ANN fast — but not always accurate.


When you run vector search, you’re essentially asking:

> “Which stored vectors are closest to my query vector?”

If you have **1,000 vectors**, brute‑force search is fine.  
If you have **100,000+ vectors**, brute‑force becomes painfully slow.  
If you have **millions**, it’s impossible to do real‑time search without optimization.

That’s where **ANN** comes in.

## What ANN Does  

ANN algorithms **don’t search every vector**.  
Instead, they use clever indexing structures to find vectors that are *very close* to the true nearest neighbours — but **much faster**.

You trade **tiny accuracy loss** for **massive speed gains**.

## ANN in Practice  
Most vector databases use ANN under the hood:

```
| Vector DB    | ANN Algorithm |
|--------------|---------------|
| **FAISS**    | HNSW, IVF, PQ |
| **Pinecone** | HNSW          |
| **Weaviate** | HNSW          |
| **Milvus**   | HNSW, IVF     |
| **Qdrant**   | HNSW          |
```

If you use any of these with WordPress, you’re already using ANN — even if you don’t know it.


### The Border Problem

When a query lands **near the edge of a partition**, ANN might:
- Search only inside its assigned partition (e.g. Partition A)
- Return a vector that’s farther away
- **Miss closer vectors** in a neighboring partition (e.g. Partition B)


### Why It Matters
This border issue can cause:
- Lower recall (missing relevant results)
- Poor recommendations or search matches
- Confusion in edge cases (e.g. similar products or documents)


### How to Fix It
Advanced techniques like:
- **Multi-probe search** (check nearby partitions)
- **Reranking** (re-evaluate top candidates)
- **HNSW graphs** (link across partitions)

…can reduce border errors and improve accuracy.

![border](../images/vectors/ann-nearest-neighbour-slide.png)

##  Hierarchical Navigable Small World (HNSW)

### *The most popular ANN algorithm — and the one you’ll encounter everywhere*

![hnsw](../images/vectors/hnsw.png)

HNSW is currently the **gold standard** for fast, accurate vector search.

It’s used because it’s:

- extremely fast  
- extremely accurate  
- scalable  
- memory‑efficient  
- easy to tune  



## The Core Idea 

Imagine your vectors are cities on a map.

HNSW builds **multiple layers of roads**:

### Layer 0 (bottom layer)
- Dense local roads  
- Every vector is connected to many neighbours  
- Great for fine‑grained accuracy  
- Slow if used alone  

### Layer 1, 2, 3… (upper layers)
- Sparse highways  
- Only a few long‑distance connections  
- Great for fast navigation  

### How search works  
1. Start at the **top layer** (few nodes → fast).  
2. Move toward the query vector using long‑distance links.  
3. Drop down a layer.  
4. Refine the search using more local connections.  
5. Repeat until you reach the bottom layer.  
6. Return the nearest neighbours.

This structure gives you:

- **logarithmic search time**  
- **near‑exact accuracy**  
- **massive speed improvements**  



## Why HNSW Matters for WordPress Developers

### 1. It makes semantic search fast enough for real‑time use*
Even with **hundreds of thousands** of posts or products.

### 2. It’s the default in most vector DBs  
If you use Pinecone, Qdrant, Weaviate, or Milvus — you’re using HNSW.

### 3. It’s ideal for WordPress workloads
WordPress content is:

- short  
- numerous  
- frequently updated  
- semantically rich  

HNSW handles this beautifully.

### 4. It works well with PHP APIs
You can:

- embed content in PHP  
- send vectors to a vector DB  
- query using HNSW  
- return results to WordPress  

All without touching low‑level ANN code.


## When To Use ANN/HNSW

Use ANN/HNSW when:

- You have **10k+ embeddings**  
- You want **instant search results**  
- You’re building **semantic search**  
- You’re integrating with **Pinecone, Qdrant, Weaviate, Milvus, or FAISS**  
- You want **BM25 + embeddings hybrid search**  
- You’re building **AI‑powered WooCommerce search**  

Do **not** use brute‑force search unless your dataset is tiny.


## Ultra‑Short Summary

### ANN

Fast, approximate vector search.  
Trades tiny accuracy loss for huge speed gains.

### HNSW

The best ANN algorithm.  
Uses multi‑layer graphs to find nearest neighbours quickly and accurately.  
Default in most vector databases.

<br>