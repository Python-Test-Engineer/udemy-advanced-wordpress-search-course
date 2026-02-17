# ✅ 20A WP Posts RAG Manager (Refactored)

This plugin manages a **RAG (Retrieval-Augmented Generation)** data layer for WordPress posts. It creates a dedicated database table, syncs WordPress posts into it, supports **Full‑Text Search (FTS)** using MySQL indexes, and provides **Vector Search** using OpenAI embeddings.

The plugin is refactored into clean, modular classes following WordPress best practices, and includes **verbose comments** and **heavy JavaScript console logging** for education/debugging purposes.

---

## ✅ What This Plugin Does

### Core Features

1. **Custom RAG Table**
   - Creates a table (`wp_posts_rag`) to store post data + embeddings.
   - Stores post title, content, categories, tags, custom meta.

2. **Full‑Text Search (FTS)**
   - Builds/drops a MySQL `FULLTEXT` index on selected fields.
   - Searches with `MATCH ... AGAINST` in natural language mode.

3. **Vector Search (Semantic Search)**
   - Uses OpenAI Embeddings (`text-embedding-3-small`).
   - Stores JSON embeddings per post.
   - Searches using cosine similarity between vectors.

4. **Admin Interface**
   - Sync posts into the RAG table.
   - Generate embeddings.
   - Create/delete FULLTEXT index.
   - Test both search methods (new Search Testing page).

5. **REST API Endpoints**
   - `/wp-json/posts-rag/v1/search`
   - `/wp-json/posts-rag/v1/vector-search`

---

## ✅ Folder Structure

```
20A-wp-posts-rag-manager/
├── wp-posts-rag-manager.php          # Plugin bootstrap
├── README.md                         # This file
├── index.php                         # Security file
├── uninstall.php                     # Cleanup on uninstall
├── languages/                        # Translations
├── includes/
│   ├── class-posts-rag-activator.php # Activation/deactivation logic
│   ├── class-posts-rag-manager.php   # Core logic (table, search, embeddings)
│   ├── class-posts-rag-admin.php     # Admin menus + AJAX handlers
│   └── class-posts-rag-rest-api.php  # REST endpoints
└── assets/
    ├── css/
    │   └── admin-styles.css          # Admin styling
    └── js/
        ├── admin-main.js             # Main admin interactivity
        └── admin-search-testing-new.js # New search testing page script
```

---

## ✅ Code Flow Overview

### 1. `wp-posts-rag-manager.php`
**Bootstrap File**
- Defines plugin constants.
- Loads all class files.
- Registers activation/deactivation hooks.
- Initializes plugin classes.

---

### 2. `class-posts-rag-activator.php`
Handles activation and cleanup.

**On Activation:**
- Creates database table.
- Sets default options.
- Stores DB version.
- Schedules optional cron cleanup.

**On Deactivation:**
- Unschedules cron jobs.
- Clears transients.

---

### 3. `class-posts-rag-manager.php`
Core logic for RAG functionality.

**Key Responsibilities:**
- Sync WP posts into custom table.
- Generate OpenAI embeddings.
- Store embeddings in JSON.
- Create/delete FULLTEXT indexes.
- Full‑text and vector search.
- Calculate cosine similarity.

---

### 4. `class-posts-rag-admin.php`
Admin UI and AJAX handlers.

**Admin Pages:**
- Main Page (API key, sync, index, embeddings)
- Search Testing (New)

**AJAX Endpoints:**
- Save API key
- Sync posts
- Generate embeddings
- Create/delete index
- Get stats

**Important Note:**
- The new search page uses a **fresh slug** and new JS file (`admin-search-testing-new.js`).
- Scripts are always enqueued to avoid WP hook mismatches.

---

### 5. `class-posts-rag-rest-api.php`
Exposes REST API endpoints.

**Endpoints:**

| Endpoint | Purpose |
|---------|---------|
| `/posts-rag/v1/search` | Full‑text search |
| `/posts-rag/v1/vector-search` | Vector search |

Each endpoint validates input and returns formatted results.

---

## ✅ Admin Pages (UI)

### ✅ Main Admin Page

Includes:
- OpenAI API key input
- Stats display
- Sync posts button
- Create/delete full-text index
- Generate embeddings button
- REST API endpoint samples

---

### ✅ Search Testing (New)

Provides:
- Full‑text search testing
- Vector search testing
- Comparison view

**JavaScript:** `admin-search-testing-new.js`
- Heavy `console.log()` for debugging.
- Uses REST endpoints directly.

---

## ✅ JavaScript Behavior

All JS files are **very verbose** by request. Examples:

```js
console.log('🚀 WPRAG Search Testing NEW: Script loaded');
console.log('🖱️ NEW FTS button clicked');
console.log('📡 NEW Request URL:', url);
```

---

## ✅ Database Table Schema

```sql
CREATE TABLE wp_posts_rag (
  id BIGINT UNSIGNED AUTO_INCREMENT,
  post_id BIGINT UNSIGNED NOT NULL,
  post_title TEXT NOT NULL,
  post_content LONGTEXT NOT NULL,
  categories TEXT,
  tags TEXT,
  custom_meta_data TEXT,
  embedding LONGTEXT,
  last_embedded DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY post_id (post_id)
);
```

---

## ✅ Requirements

✅ WordPress 5.6+  
✅ PHP 7.4+  
✅ OpenAI API Key (for embeddings)

---

## ✅ REST API Usage Examples

**Full‑Text Search:**
```
/wp-json/posts-rag/v1/search?query=foam&limit=3
```

**Vector Search:**
```
/wp-json/posts-rag/v1/vector-search?query=sustainable+materials&limit=3
```

---

## ✅ Uninstall Behavior

When the plugin is deleted:
- The custom RAG table is dropped.
- All plugin options are removed.
- Transients and cron events are cleared.

---

## ✅ Notes

- This plugin is intended for **educational/demo purposes**.
- It includes verbose logging to help trace program flow.
- In production, you may want to reduce console logging.

---

### ✅ Author
Refactor created by **Cline (AI assistant)** for Udemy Advanced WordPress Search Course.
