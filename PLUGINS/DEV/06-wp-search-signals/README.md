# WP Search Signals Plugin

## Overview

WP Search Signals is a WordPress plugin designed to track and record user interactions with search results for machine learning purposes. It captures user behavior patterns—such as searches, result views, hovers, and clicks—to create training data that can be used to improve search relevance and ranking algorithms.

## What It Does

### User Interaction Tracking

The plugin records three types of user signals:

1. **Search Events** (`event_search`)
   - Triggered when a user performs a search query
   - Captures the search query text, returned results, and result count
   - Associates all returned post IDs with the query

2. **Hover Events** (`event_hover`)
   - Triggered when a user hovers over a search result card
   - Records which specific result caught the user's attention
   - Only logged once per result to avoid duplicate data

3. **Click Events** (`event_click`)
   - Triggered when a user explicitly clicks "Record Click" on a result
   - Indicates strong user interest in a particular result
   - Useful for identifying highly relevant content

### Machine Learning Application

This data creates a feedback loop for search improvement:

- **Implicit Signals**: Hover events show which results attract attention
- **Explicit Signals**: Click events demonstrate clear user intent and relevance
- **Query Context**: Each signal is linked to the original search query
- **Session Tracking**: User sessions are maintained to understand behavior patterns

The collected data can be used to:
- Train ranking models based on actual user preferences
- Identify which search results users find most relevant
- Detect patterns in user behavior for specific query types
- A/B test search algorithm improvements

## Technical Architecture

### Database Schema

The plugin creates two custom database tables:

#### 1. Queries Table (`wp_signals_queries`)

Stores each unique search query:

```sql
query_id         BIGINT      Auto-incrementing primary key
session_id       VARCHAR     User session identifier
user_id          BIGINT      WordPress user ID
query_text       VARCHAR     The actual search query
result_ids       LONGTEXT    JSON array of returned post IDs
created_at       DATETIME    Timestamp of the query
```

#### 2. Signals Table (`wp_signals`)

Stores individual user interaction events:

```sql
id                  BIGINT      Auto-incrementing primary key
query_id            BIGINT      Foreign key to queries table
session_id          VARCHAR     User session identifier
guid                VARCHAR     Unique event identifier
user_id             BIGINT      WordPress user ID
event_name          VARCHAR     Type of event (search/hover/click)
post_id             BIGINT      Specific post involved (for hover/click)
event_meta_details  LONGTEXT    JSON metadata about the event
created_at          DATETIME    Timestamp of the event
```

### PHP Backend (`06-wp-search-signals.php`)

#### Core Class: `WP_Signals_Plugin`

**Initialization & Hooks**:
- Registers admin menu page
- Enqueues JavaScript and CSS assets
- Sets up AJAX endpoints for event logging

**Key Methods**:

1. **`activate()`** (Static)
   - Creates database tables on plugin activation
   - Uses `dbDelta()` for safe schema management
   - Establishes indexes for query performance

2. **`register_admin_menu()`**
   - Adds admin interface at position 3.6
   - Requires `manage_options` capability

3. **`enqueue_admin_assets()`**
   - Loads admin.js and styles.css only on plugin page
   - Passes configuration to JavaScript via `wp_localize_script()`
   - Includes AJAX URL, security nonce, and search endpoint

4. **`handle_create_query()`**
   - AJAX handler for creating new query records
   - Validates user authentication and nonce
   - Sanitizes input and stores query with result IDs
   - Returns the new `query_id` to JavaScript

5. **`handle_log_event()`**
   - AJAX handler for logging individual signals
   - Associates events with queries via `query_id`
   - Generates unique GUID for each event
   - Stores event metadata as JSON

**Security Features**:
- Nonce verification on all AJAX requests
- User authentication checks
- Input sanitization with `sanitize_text_field()`
- Capability checks for admin access

### JavaScript Frontend (`admin.js`)

#### Architecture Pattern

Uses an IIFE (Immediately Invoked Function Expression) to avoid global namespace pollution.

#### Key Components

1. **DOM Management**
   - Query input field (`#ws_query`)
   - Run query button (`#ws_query_run`)
   - Results container (`#wp-signals-results`)
   - Debug output container (`#wp-signals-debug`)

2. **Query Workflow**

```javascript
User enters query → fetchResults() → API call to hybrid search endpoint
                                  ↓
                          createQuery() creates database record
                                  ↓
                          Returns query_id for session
                                  ↓
                          Renders result cards
                                  ↓
                          Logs search event with query_id
```

3. **Event Tracking Functions**

**`createQuery(queryText, resultIds)`**:
- Makes AJAX call to create query record in database
- Stores returned `query_id` in `currentQueryId` variable
- This ID links all subsequent events to this search session

**`sendEvent(eventName, payload, options)`**:
- Sends event data to PHP backend
- Automatically includes current `query_id`
- Logs to debug container for transparency
- Non-blocking (doesn't wait for response)

**`createResultCard(item)`**:
- Dynamically generates HTML for each search result
- Attaches hover event listener (fires once per card)
- Adds click button for explicit signal recording
- Extracts post ID from various possible field names

4. **State Management**

```javascript
let currentQueryId = null;  // Tracks active query session
```

All events in a session reference this ID, creating a complete interaction history.

5. **Search Integration**

- Calls REST API endpoint: `/wp-json/search/v1/hybrid-search`
- Passes query and limit parameters
- Handles multiple response formats (array or object with nested results)
- Limits display to first 3 results

#### Event Flow Example

```
1. User searches "FOAM products"
2. createQuery() → Returns query_id: 42
3. currentQueryId = 42
4. Render 3 result cards
5. Log event_search with query_id: 42
6. User hovers over result #123
7. Log event_hover with query_id: 42, post_id: 123
8. User clicks "Record Click" on result #456
9. Log event_click with query_id: 42, post_id: 456
```

All these events are now linked together for analysis.

## Data Collection Strategy

### Query-Centric Model

The two-table design creates a hierarchical relationship:

```
Query (1) → Many Events (*)
```

Benefits:
- Efficiently groups all interactions for a single search
- Enables analysis of entire user journeys
- Reduces data duplication (query text stored once)
- Makes it easy to calculate metrics like click-through rate per query

### Session Tracking

Sessions are tracked using WordPress session tokens or PHP session IDs:

```php
$session_id = wp_get_session_token() ?? session_id();
```

This allows analysis of:
- Multi-query sessions
- User behavior patterns over time
- Cross-query learning patterns

## Use Cases for Machine Learning

### 1. Learning to Rank (LTR)

Train models using features like:
- Position of clicked results in the original ranking
- Time to first click after search
- Number of hovers before click
- Query-document relevance based on interaction

### 2. Query Understanding

Analyze which results users engage with to:
- Identify user intent behind ambiguous queries
- Cluster similar queries based on interaction patterns
- Suggest query refinements

### 3. A/B Testing

Compare search algorithms by:
- Measuring engagement rates per algorithm variant
- Tracking which variant produces more clicked results
- Calculating metrics like Mean Reciprocal Rank (MRR)

### 4. Relevance Feedback

Use implicit feedback to:
- Boost frequently clicked results for specific queries
- Demote results with high impressions but no engagement
- Personalize results based on user history

## Installation & Usage

1. Upload plugin files to `/wp-content/plugins/wp-search-signals/`
2. Activate the plugin through WordPress admin
3. Navigate to "06 WP SIGNALS" in the admin menu
4. Enter search queries to generate training data
5. Export data from database tables for ML model training

## Data Export for Analysis

Access the collected data via SQL:

```sql
-- Get all queries with their events
SELECT 
    q.query_text,
    q.result_ids,
    s.event_name,
    s.post_id,
    s.event_meta_details,
    s.created_at
FROM wp_signals_queries q
LEFT JOIN wp_signals s ON q.query_id = s.query_id
ORDER BY q.created_at DESC, s.created_at ASC;

-- Calculate click-through rate per query
SELECT 
    query_text,
    COUNT(DISTINCT CASE WHEN event_name = 'event_click' THEN query_id END) as clicks,
    COUNT(DISTINCT query_id) as total_queries,
    (COUNT(DISTINCT CASE WHEN event_name = 'event_click' THEN query_id END) * 100.0 / 
     COUNT(DISTINCT query_id)) as ctr
FROM wp_signals_queries q
LEFT JOIN wp_signals s USING(query_id)
GROUP BY query_text;
```

## Developer Notes

### Extending the Plugin

To add new event types:

1. Create a new event name constant
2. Add JavaScript event listener in `admin.js`
3. Call `sendEvent()` with appropriate payload
4. No backend changes needed—events are stored generically

### Security Considerations

- All AJAX requests use WordPress nonces
- User authentication required for all operations
- Input sanitization on all user-provided data
- SQL injection protection via `$wpdb->insert()` prepared statements

### Performance Optimization

The plugin includes database indexes on:
- `query_id` for fast joins
- `event_name` for filtering by event type
- `user_id` and `session_id` for user-specific queries
- `created_at` for time-based analysis

## Version History

- **1.1.0**: Added query-centric model with separate queries table
- **1.0.0**: Initial release with basic event logging

## License

This plugin is provided as-is for machine learning research and search improvement purposes.
