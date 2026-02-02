# ENDPOINTS

REST API Endpoints
Full-Text Search
Search using MySQL full-text index (keyword matching):

https://immortalityai.co.uk/craig/wp-json/search/v1/search?query=Collinson&limit=3
Vector Search
Search using semantic similarity (requires embeddings):

https://immortalityai.co.uk/craig/wp-json/search/v1/vector-search?query=Collinson&limit=3
Hybrid Search
Combines full-text and vector search results (deduplicated):

https://immortalityai.co.uk/craig/wp-json/search/v1/hybrid-search?query=Collinson&limit=3
Parameters: query (required), limit (optional, default: 3, max: 10 per method)

