This `03-wp-bm25-calc` implements the BM25 (Best Match 25) ranking algorithm for search relevance. Here's how BM25 values are calculated:

## Core Components

**Parameters:**
- `k1` (default 1.5): Controls term frequency saturation - how quickly additional occurrences of a term stop adding relevance
- `b` (default 0.75): Controls document length normalization - how much shorter/longer documents affect scoring
- `N`: Total number of documents in the collection
- `avgdl`: Average document length across all documents

## Calculation Steps

### 1. Document Preprocessing (lines 22-32)

```
avgdl = total_words_in_all_docs / number_of_docs
```

Each document is tokenized into lowercase words using `str_word_count()`.

### 2. Term Frequency (TF) (lines 34-37)

For each query term in a document, count how many times it appears:

```
tf = count of term occurrences in document
```

### 3. Document Frequency (DF) (lines 39-45)

Count how many documents contain the term:

```
df = number of documents containing the term
```

### 4. Inverse Document Frequency (IDF) (lines 47-50)

Calculate how rare/common a term is across all documents:

```
IDF = log((N - df + 0.5) / (df + 0.5))
```

- Rare terms (low df) get higher IDF scores
- Common terms (high df) get lower IDF scores

### 5. BM25 Score Per Term (lines 58-66)

For each query term in the document:

```
numerator = tf × (k1 + 1)
denominator = tf + k1 × (1 - b + b × (doc_length / avgdl))
term_score = IDF × (numerator / denominator)
```

The denominator applies length normalization:
- Longer documents (doc_length > avgdl) have their scores reduced
- Shorter documents (doc_length < avgdl) have their scores boosted
- The `b` parameter controls how strongly this applies

### 6. Final Document Score (line 65)

```
total_score = sum of all term_scores for each unique query term
```

## Example Flow

For the query "Electric Milk Frother" against a document:

1. Tokenize query → ["electric", "milk", "frother"]
2. For each term:
   - Calculate tf (how many times in this doc)
   - Calculate df (how many docs contain it)
   - Calculate IDF (rarity score)
   - Apply BM25 formula with length normalization
   - Get term_score
3. Sum all term_scores → final document score
4. Rank all documents by score (line 80)

Documents with higher scores are more relevant to the query. The algorithm balances term frequency (repetition matters but saturates), document frequency (rare terms are more distinctive), and document length (to prevent bias toward longer documents).