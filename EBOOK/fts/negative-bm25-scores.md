# Understanding BM25 Scores: 

Why Negative Numbers Are Totally Normal


## Opening Intuition: What Is BM25 Trying to Do?

BM25 is a **relevance scoring function**. Its job is simple:

> **Given a query, how relevant is each document?**

But BM25 doesn’t care about absolute numbers.  
It only cares about **relative ranking**.

Think of BM25 like a judge in a talent show:

- It doesn’t matter whether the judge scores contestants 1–10 or -10–0  
- What matters is **who ranks higher**

This is why **negative scores are not a problem** — they’re just part of the math.


## Negative Scores Are Normal

BM25 scores can be **positive, negative, or zero**.  
The only rule that matters:

> **Higher scores = better matches (even if they’re negative)**

This is the part that confuses beginners, so emphasize it early and often.

## Interpreting BM25 Scores

### Positive Scores
These happen when the query terms are **rare** across your collection.

- Rare terms → high IDF → positive BM25  
- Example: Searching for “quantum” in a blog collection

### Negative Scores  
These happen when the query terms are **very common**.

- Common terms → negative IDF → negative BM25  
- Example: Searching for “the” or “programming”

### Zero Scores 
These happen when:

- The document contains **none** of the query terms  
- Or the terms are so common that the math cancels out

## Why Negative Scores Happen (The Math Intuition)

BM25 uses the IDF formula:

```
IDF = ln((N - n + 0.5) / (n + 0.5))
```

Where:

- **N** = total number of documents  
- **n** = number of documents containing the term  

If a term appears in **more than half** of your documents, the fraction becomes < 1, and:

- ln(<1) = negative number  
- → negative IDF  
- → negative BM25  

This is expected and correct.

## Mental Model: 

Think of IDF Like “Uniqueness Points”

- Rare terms earn **bonus points**  
- Common terms earn **penalty points**  
- BM25 adds up all the bonuses and penalties  

If all your query terms are common, you get a **negative total**, but the ranking still works.

## Example Breakdown

| Document | "the" | "programming" | "language" | BM25 |
|---------|-------|----------------|------------|------|
| Doc 1   | Yes   | Yes            | Yes        | -5.2 |
| Doc 2   | Yes   | Yes            | No         | -4.8 |
| Doc 3   | No    | No             | No         | 0.0  |

Ranking:

1. Doc 2 (best)  
2. Doc 1  
3. Doc 3 (worst)

Even though Doc 2 has a negative score, it’s still the **best match**.


## When You’ll See Each Score Type

### Positive Scores
- Rare terms  
- Technical vocabulary  
- Small document sets  

### Negative Scores
- Common words  
- Terms appearing in most documents  
- Broad/general vocabulary  

### Zero Scores
- No matching terms  
- Completely unrelated content  

## Practical Experiments 

### 1. Positive Score Experiment
Documents:  
- “Python is great”  
- “Java is fast”  
- “C++ is powerful”  

Query: **python** → positive score

### 2. Negative Score Experiment
Documents:  
- “Programming in Python”  
- “Programming in Java”  
- “Programming in C++”  

Query: **programming** → negative scores

### 3. Zero Score Experiment

Documents:  
- “Python programming”  
- “Java coding”  
- “C++ development”  

Query: **cooking recipes** → zero

## BM25 vs TF‑IDF 

Students often ask this, so it’s worth adding:

|     Feature     |       TF‑I       |               BM2               |
|-----------------|------------------|---------------------------------|
| Term frequency  | Linear           | Saturates (diminishing returns) |
| Document length | Not handled well | Normalized                      |
| Ranking quality | Good             | Better                          |
| Negative scores | Yes              | Yes                             |
| Search engines  | Rarely           | Very common                     |

BM25 is essentially a **smarter, more realistic TF‑IDF**.

## Why BM25 Still Matters in 2026

Even with vector search, embeddings, and RAG:

- BM25 is still the **best first‑stage retriever**  
- It’s fast, cheap, and interpretable  
- It handles exact keyword matching better than embeddings  
- Hybrid search = BM25 + vectors → best of both worlds  

Your students will encounter BM25 everywhere:  
Elasticsearch, OpenSearch, Meilisearch, Vespa, Solr, and even WordPress plugins.

## Final Takeaways

- Negative BM25 scores are **normal**  
- Zero means **no match**  
- Higher scores always win, even if negative  
- BM25 is ranking-focused, not absolute-value-focused  
- Understanding IDF is the key to understanding everything else  

> **BM25 doesn’t care whether scores are positive or negative — it only cares about which document is the best match.**

<br>
