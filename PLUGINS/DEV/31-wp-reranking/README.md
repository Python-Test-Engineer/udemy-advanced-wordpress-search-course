# WP Reranking

This plugin exposes a REST endpoint that reranks Full‑Text Search (FTS) and Vector Search results into a single, ordered list. It normalizes each score type, combines them, and then sorts the items by the combined score. Each item is annotated with a `position` field to show the final order.

## Reranking Process (Step‑by‑Step)
1. **Collect inputs**
   - Accepts two payloads (`fulltext_search` and `vector_search`) or, when no payload is supplied, fetches the local REST endpoints:
     - `search/v1/search`
     - `search/v1/vector-search`
2. **Normalize scores**
   - Finds the maximum FTS `relevance_score` and the maximum Vector `similarity_score`.
   - Normalizes each item score by dividing by the max of its type.
3. **Combine scores**
   - For each unique `post_id`, it adds:
     - `normalized_relevance + normalized_similarity`
   - This creates a `combined_score` for sorting.
4. **Sort and assign positions**
   - Sorts descending by `combined_score`.
   - Adds `position` starting at 1.

## Example

### Input (FTS + Vector)
```json
{
  "fulltext_search": {
    "results": [
      {"post_id": 4339, "relevance_score": 10.40, "post_title": "FOAM Facts"},
      {"post_id": 4352, "relevance_score": 1.73, "post_title": "John Bowie"}
    ]
  },
  "vector_search": {
    "results": [
      {"post_id": 4339, "similarity_score": 0.528, "post_title": "FOAM Facts"},
      {"post_id": 4350, "similarity_score": 0.443, "post_title": "rob emmott"}
    ]
  }
}
```

### Output (Reranked)
```json
{
  "success": true,
  "query": "FOAM",
  "method": "reranking",
  "results": [
    {
      "position": 1,
      "post_id": 4339,
      "post_title": "FOAM Facts",
      "relevance_score": 10.40,
      "similarity_score": 0.528
    },
    {
      "position": 2,
      "post_id": 4350,
      "post_title": "rob emmott",
      "relevance_score": 0,
      "similarity_score": 0.443
    },
    {
      "position": 3,
      "post_id": 4352,
      "post_title": "John Bowie",
      "relevance_score": 1.73,
      "similarity_score": 0
    }
  ],
  "count": 3
}
```

## Endpoint

```
GET /wp-json/reranker/v1/reranked?query=FOAM
```

You can also POST a JSON payload containing `fulltext_search` and `vector_search` keys to rerank external data.
