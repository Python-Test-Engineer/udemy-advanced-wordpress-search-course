
Here's a comprehensive markdown plan for building an OpenClaw-style digital personal assistant in WordPress, with heavy emphasis on async work patterns. I'll output this as a document you can save — toggle to **Act mode** when you're ready and I'll write it to a `.md` file.

---

# 🤖 WP Digital Personal Assistant — Architecture Plan

## Vision

Build a WordPress-native AI personal assistant inspired by [OpenClaw.ai](https://openclaw.ai/) that can:
- Accept natural language instructions via a chat interface
- Execute tasks **asynchronously** in the background
- Maintain **persistent memory** across sessions
- Search your site content using your existing RAG/hybrid search infrastructure
- Perform scheduled, recurring, and long-running work autonomously

---

## 1. What You Already Have (Building Blocks)

Your existing plugin suite provides a powerful foundation:

| Plugin | Capability | Reuse for Assistant |
|--------|-----------|-------------------|
| **30 - REST RAG Endpoints** | FTS (natural/boolean/expansion) + vector + hybrid search | **Tool**: "Search site content" |
| **31 - Reranking** | Score normalization, weighted FTS+vector merge | **Tool**: "Find best-match content" |
| **20 - RAG Manager** | Batch OpenAI embeddings, `wp_posts_rag` table | **Memory**: Embed & retrieve assistant knowledge |
| **40 - Search Signals** | Session-aware event logging, query tracking | **Memory**: Track what the user asked/did |
| **32/33 - Search Forms** | Frontend search UIs with AJAX | **UI pattern**: Chat interface scaffold |

---

## 2. Core Architecture

```
┌─────────────────────────────────────────────────────┐
│                   CHAT INTERFACE                     │
│  (WP Admin page / Shortcode / REST-powered SPA)     │
│  User types: "Summarise all posts about foam"        │
└──────────────────────┬──────────────────────────────┘
                       │ REST API (POST /assistant/v1/message)
                       ▼
┌─────────────────────────────────────────────────────┐
│              ORCHESTRATOR (PHP + WP)                 │
│  - Parse intent (via LLM function-calling)           │
│  - Resolve to one or more Tools                      │
│  - For quick tasks → execute inline, return result   │
│  - For slow tasks → enqueue as ASYNC JOB             │
│  - Store conversation in memory table                │
└─────┬──────────┬──────────┬─────────┬───────────────┘
      │          │          │         │
      ▼          ▼          ▼         ▼
   ┌──────┐ ┌────────┐ ┌────────┐ ┌──────────┐
   │Search│ │Content │ │External│ │Scheduled │
   │Tools │ │Tools   │ │APIs    │ │Jobs      │
   └──────┘ └────────┘ └────────┘ └──────────┘
```

---

## 3. The Async Work Engine (★ Core Focus)

This is the heart of the system. WordPress is request/response by nature — making it do reliable async work requires deliberate architecture.

### 3.1 The Job Queue Table

```sql
CREATE TABLE wp_assistant_jobs (
    job_id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    user_id       BIGINT UNSIGNED NOT NULL,
    job_type      VARCHAR(100) NOT NULL,       -- e.g. 'search_and_summarise', 'bulk_tag', 'generate_report'
    job_status    ENUM('pending','running','completed','failed','cancelled') DEFAULT 'pending',
    job_payload   LONGTEXT NOT NULL,           -- JSON: input params, tool config
    job_result    LONGTEXT NULL,               -- JSON: output when complete
    error_message TEXT NULL,
    priority      TINYINT UNSIGNED DEFAULT 5,  -- 1=highest, 10=lowest
    attempts      TINYINT UNSIGNED DEFAULT 0,
    max_attempts  TINYINT UNSIGNED DEFAULT 3,
    scheduled_at  DATETIME NULL,               -- NULL = run ASAP; future = scheduled
    started_at    DATETIME NULL,
    completed_at  DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_priority (job_status, priority, created_at),
    INDEX idx_user_conversation (user_id, conversation_id),
    INDEX idx_scheduled (job_status, scheduled_at)
);
```

### 3.2 Three Async Strategies (Pick Per Situation)

#### Strategy A: WP-Cron Based (Simplest)

```php
// Enqueue a job
function assistant_enqueue_job($conversation_id, $type, $payload, $scheduled_at = null) {
    global $wpdb;
    $wpdb->insert('wp_assistant_jobs', [
        'conversation_id' => $conversation_id,
        'user_id'         => get_current_user_id(),
        'job_type'        => $type,
        'job_payload'     => wp_json_encode($payload),
        'scheduled_at'    => $scheduled_at,
        'created_at'      => current_time('mysql'),
    ]);
    
    // Schedule the processor if not already scheduled
    if (!wp_next_scheduled('assistant_process_jobs')) {
        wp_schedule_single_event(time(), 'assistant_process_jobs');
    }
    
    return $wpdb->insert_id;
}

// The processor hook
add_action('assistant_process_jobs', 'assistant_run_job_processor');

function assistant_run_job_processor() {
    global $wpdb;
    
    // Claim the next pending job (atomic update to prevent race conditions)
    $wpdb->query("
        UPDATE wp_assistant_jobs 
        SET job_status = 'running', started_at = NOW(), attempts = attempts + 1
        WHERE job_status = 'pending' 
          AND (scheduled_at IS NULL OR scheduled_at <= NOW())
          AND attempts < max_attempts
        ORDER BY priority ASC, created_at ASC
        LIMIT 1
    ");
    
    $job = $wpdb->get_row("
        SELECT * FROM wp_assistant_jobs 
        WHERE job_status = 'running' AND started_at IS NOT NULL
        ORDER BY started_at DESC LIMIT 1
    ");
    
    if (!$job) return;
    
    try {
        $result = assistant_execute_job($job);
        $wpdb->update('wp_assistant_jobs', [
            'job_status'   => 'completed',
            'job_result'   => wp_json_encode($result),
            'completed_at' => current_time('mysql'),
        ], ['job_id' => $job->job_id]);
    } catch (Exception $e) {
        $new_status = ($job->attempts >= $job->max_attempts) ? 'failed' : 'pending';
        $wpdb->update('wp_assistant_jobs', [
            'job_status'    => $new_status,
            'error_message' => $e->getMessage(),
        ], ['job_id' => $job->job_id]);
    }
    
    // If more jobs remain, re-schedule immediately
    $remaining = $wpdb->get_var("
        SELECT COUNT(*) FROM wp_assistant_jobs 
        WHERE job_status = 'pending' 
          AND (scheduled_at IS NULL OR scheduled_at <= NOW())
    ");
    
    if ($remaining > 0) {
        wp_schedule_single_event(time(), 'assistant_process_jobs');
    }
}
```

**Pros:** Pure WordPress, no external deps  
**Cons:** WP-Cron only fires on page visits (unless you set up a real cron); not great for time-sensitive work  

**Fix:** Add a real system cron:
```bash
* * * * * curl -s https://yoursite.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

#### Strategy B: Loopback HTTP Fire-and-Forget (Medium)

For tasks that should start *immediately* without blocking the user:

```php
function assistant_fire_async_job($job_id) {
    $url = admin_url('admin-ajax.php');
    
    wp_remote_post($url, [
        'timeout'   => 0.01,  // Fire and forget
        'blocking'  => false,
        'sslverify' => false,
        'body'      => [
            'action' => 'assistant_process_single_job',
            'job_id' => $job_id,
            'nonce'  => wp_create_nonce('assistant_job_' . $job_id),
        ],
    ]);
}

add_action('wp_ajax_assistant_process_single_job', function() {
    // Extend execution time for long tasks
    set_time_limit(300);
    ignore_user_abort(true);
    
    $job_id = intval($_POST['job_id']);
    // ... validate nonce, fetch job, execute, update status
});
```

**Pros:** Starts immediately, doesn't block the user  
**Cons:** Can hit PHP timeout limits; loopback requests can fail in some hosting environments  

#### Strategy C: External Worker via WP-CLI (Most Robust)

A persistent background worker that polls for jobs:

```php
// wp-cli command: wp assistant worker --interval=5
class Assistant_Worker_Command {
    
    /**
     * Run the async job worker.
     *
     * ## OPTIONS
     * [--interval=<seconds>]
     * : Poll interval in seconds. Default 5.
     *
     * [--max-jobs=<count>]
     * : Max jobs to process before exiting. Default 0 (unlimited).
     */
    public function worker($args, $assoc_args) {
        $interval = isset($assoc_args['interval']) ? intval($assoc_args['interval']) : 5;
        $max_jobs = isset($assoc_args['max-jobs']) ? intval($assoc_args['max-jobs']) : 0;
        $processed = 0;
        
        WP_CLI::log("🤖 Assistant worker started (polling every {$interval}s)");
        
        while (true) {
            $job = $this->claim_next_job();
            
            if ($job) {
                WP_CLI::log("⚡ Processing job #{$job->job_id}: {$job->job_type}");
                $this->process_job($job);
                $processed++;
                
                if ($max_jobs > 0 && $processed >= $max_jobs) {
                    WP_CLI::success("Reached max jobs limit ({$max_jobs}). Exiting.");
                    break;
                }
            } else {
                sleep($interval);
            }
        }
    }
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('assistant', 'Assistant_Worker_Command');
}
```

Run as a **systemd service** or **supervisor process**:
```ini
[program:wp-assistant-worker]
command=/usr/local/bin/wp assistant worker --interval=5 --path=/var/www/html
autostart=true
autorestart=true
user=www-data
```

**Pros:** True background processing, no timeout limits, can run multiple workers  
**Cons:** Requires server access, more DevOps setup  

### 3.3 The "Heartbeat" Pattern (OpenClaw-style Proactive Work)

OpenClaw's users describe it "independently assessing how it can help." WordPress can do this:

```php
// Register a recurring cron event
register_activation_hook(__FILE__, function() {
    if (!wp_next_scheduled('assistant_heartbeat')) {
        wp_schedule_event(time(), 'every_fifteen_minutes', 'assistant_heartbeat');
    }
});

add_action('assistant_heartbeat', function() {
    // 1. Check for scheduled/deferred jobs
    assistant_run_job_processor();
    
    // 2. Check for "proactive" triggers
    $triggers = [
        'check_stale_drafts'     => 'Are there drafts older than 7 days? Nudge the user.',
        'content_gap_analysis'   => 'Run a vector search for trending topics not covered.',
        'broken_link_scan'       => 'Scan recent posts for 404 links.',
        'embedding_freshness'    => 'Are there posts without embeddings? Queue batch job.',
    ];
    
    foreach ($triggers as $trigger => $description) {
        $result = call_user_func("assistant_trigger_{$trigger}");
        if ($result) {
            // Store a proactive notification for the user
            assistant_create_notification(0, $trigger, $result);
        }
    }
});

// Custom cron interval
add_filter('cron_schedules', function($schedules) {
    $schedules['every_fifteen_minutes'] = [
        'interval' => 900,
        'display'  => 'Every 15 Minutes',
    ];
    return $schedules;
});
```

### 3.4 Job Status Polling (Frontend ↔ Backend)

The chat UI needs to know when async jobs finish:

```javascript
// Frontend: Poll for job completion
class AssistantJobPoller {
    constructor(jobId) {
        this.jobId = jobId;
        this.pollInterval = 2000; // Start at 2s
        this.maxInterval = 30000; // Back off to 30s
        this.timer = null;
    }
    
    start() {
        this.poll();
    }
    
    async poll() {
        try {
            const response = await fetch(
                `/wp-json/assistant/v1/job/${this.jobId}/status`
            );
            const data = await response.json();
            
            if (data.status === 'completed') {
                this.onComplete(data.result);
                return;
            }
            
            if (data.status === 'failed') {
                this.onError(data.error_message);
                return;
            }
            
            // Exponential backoff
            this.pollInterval = Math.min(
                this.pollInterval * 1.5, 
                this.maxInterval
            );
            this.timer = setTimeout(() => this.poll(), this.pollInterval);
            
        } catch (err) {
            this.onError(err.message);
        }
    }
    
    stop() {
        clearTimeout(this.timer);
    }
}

// Usage in chat:
// User sends "Summarise all posts about foam"
// -> Orchestrator returns { type: 'async', job_id: 42, message: "Working on it..." }
// -> UI shows spinner + starts poller
const poller = new AssistantJobPoller(42);
poller.onComplete = (result) => {
    appendMessage('assistant', result.summary);
};
poller.start();
```

**Alternative: Server-Sent Events (SSE)** for real-time streaming:

```php
// REST endpoint that streams job progress
register_rest_route('assistant/v1', '/job/(?P<id>\d+)/stream', [
    'methods'  => 'GET',
    'callback' => function($request) {
        $job_id = $request['id'];
        
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        set_time_limit(120);
        
        while (true) {
            $job = get_job($job_id);
            
            echo "data: " . wp_json_encode([
                'status' => $job->job_status,
                'progress' => $job->progress ?? null,
            ]) . "\n\n";
            
            ob_flush();
            flush();
            
            if (in_array($job->job_status, ['completed', 'failed'])) {
                echo "data: " . wp_json_encode([
                    'status' => $job->job_status,
                    'result' => json_decode($job->job_result, true),
                ]) . "\n\n";
                break;
            }
            
            sleep(2);
        }
    },
]);
```

---

## 4. Persistent Memory System

### 4.1 Conversation Table

```sql
CREATE TABLE wp_assistant_conversations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    title           VARCHAR(255) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
);

CREATE TABLE wp_assistant_messages (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    role            ENUM('user','assistant','system','tool') NOT NULL,
    content         LONGTEXT NOT NULL,
    tool_calls      LONGTEXT NULL,    -- JSON: function calls made
    job_id          BIGINT UNSIGNED NULL,
    tokens_used     INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation (conversation_id, created_at)
);
```

### 4.2 Long-Term Memory (Facts & Preferences)

```sql
CREATE TABLE wp_assistant_memory (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    memory_type ENUM('fact','preference','instruction','context') NOT NULL,
    content     TEXT NOT NULL,
    embedding   LONGTEXT NULL,         -- For semantic retrieval
    source      VARCHAR(100) NULL,     -- 'conversation:42', 'manual', 'inferred'
    confidence  DECIMAL(3,2) DEFAULT 1.00,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at  DATETIME NULL,
    INDEX idx_user_type (user_id, memory_type)
);
```

Before each LLM call, retrieve relevant memories:
```php
function assistant_get_relevant_context($user_id, $query) {
    // 1. Get user preferences/instructions (always included)
    $prefs = get_memories($user_id, ['preference', 'instruction']);
    
    // 2. Semantic search memories for relevance
    $embedding = get_openai_embedding($query);
    $relevant = vector_search_memories($user_id, $embedding, limit: 5);
    
    // 3. Recent conversation context
    $recent = get_recent_messages($user_id, limit: 10);
    
    return compact('prefs', 'relevant', 'recent');
}
```

---

## 5. Tool / Skill System

### 5.1 Tool Registry

```php
class Assistant_Tool_Registry {
    private static $tools = [];
    
    public static function register($name, $config) {
        self::$tools[$name] = wp_parse_args($config, [
            'name'        => $name,
            'description' => '',
            'parameters'  => [],     // JSON Schema
            'callback'    => null,
            'async'       => false,  // If true, runs as background job
            'timeout'     => 30,     // Max seconds for sync execution
        ]);
    }
    
    public static function get_tools_for_llm() {
        // Format as OpenAI function-calling schema
        return array_map(function($tool) {
            return [
                'type' => 'function',
                'function' => [
                    'name'        => $tool['name'],
                    'description' => $tool['description'],
                    'parameters'  => $tool['parameters'],
                ],
            ];
        }, self::$tools);
    }
    
    public static function execute($name, $args, $context) {
        $tool = self::$tools[$name] ?? null;
        if (!$tool) throw new Exception("Unknown tool: {$name}");
        
        if ($tool['async']) {
            // Enqueue as background job, return job_id
            $job_id = assistant_enqueue_job(
                $context['conversation_id'],
                "tool:{$name}",
                ['tool_args' => $args, 'context' => $context]
            );
            assistant_fire_async_job($job_id);
            return ['async' => true, 'job_id' => $job_id];
        }
        
        return call_user_func($tool['callback'], $args, $context);
    }
}
```

### 5.2 Built-in Tools (Leveraging Your Existing Plugins)

```php
// Search site content (wraps your Plugin 30 + 31)
Assistant_Tool_Registry::register('search_content', [
    'description' => 'Search site content using hybrid FTS + vector search with reranking',
    'parameters'  => [
        'type' => 'object',
        'properties' => [
            'query' => ['type' => 'string', 'description' => 'Search query'],
            'limit' => ['type' => 'integer', 'default' => 5],
        ],
        'required' => ['query'],
    ],
    'async'    => false, // Fast enough to run inline
    'callback' => function($args) {
        $response = wp_remote_get(rest_url('reranker/v1/reranked') . '?' . http_build_query([
            'query' => $args['query'],
            'limit' => $args['limit'] ?? 5,
        ]));
        return json_decode(wp_remote_retrieve_body($response), true);
    },
]);

// Summarise multiple posts (slow → async)
Assistant_Tool_Registry::register('summarise_posts', [
    'description' => 'Search for and summarise multiple posts on a topic',
    'parameters'  => [
        'type' => 'object',
        'properties' => [
            'topic' => ['type' => 'string'],
            'max_posts' => ['type' => 'integer', 'default' => 10],
        ],
        'required' => ['topic'],
    ],
    'async'    => true,  // Runs in background
    'callback' => function($args) {
        // 1. Search for relevant posts
        // 2. For each post, call LLM to summarise
        // 3. Call LLM to synthesise all summaries
        // 4. Return final summary
    },
]);

// Create/update WordPress content (async for safety)
Assistant_Tool_Registry::register('create_draft_post', [
    'description' => 'Create a draft WordPress post',
    'async'       => true,
    'parameters'  => [/* ... */],
    'callback'    => function($args) {
        return wp_insert_post([
            'post_title'   => $args['title'],
            'post_content' => $args['content'],
            'post_status'  => 'draft', // Always draft, never auto-publish
        ]);
    },
]);

// Scheduled reminder
Assistant_Tool_Registry::register('set_reminder', [
    'description' => 'Set a reminder for a future date/time',
    'async'       => true,
    'parameters'  => [/* ... */],
    'callback'    => function($args) {
        return assistant_enqueue_job(
            $args['conversation_id'],
            'reminder',
            ['message' => $args['message']],
            $args['scheduled_at'] // Future datetime
        );
    },
]);
```

### 5.3 Plugin-Based Skill Extension

Allow third-party skills via WordPress hooks:

```php
// In any other plugin:
add_action('assistant_register_tools', function() {
    Assistant_Tool_Registry::register('check_woocommerce_orders', [
        'description' => 'Check recent WooCommerce orders',
        'async'       => false,
        'callback'    => function($args) {
            // ... WooCommerce API calls
        },
    ]);
});
```

---

## 6. The Orchestrator (LLM Integration)

```php
function assistant_handle_message($conversation_id, $user_message) {
    $user_id = get_current_user_id();
    
    // 1. Store user message
    assistant_store_message($conversation_id, 'user', $user_message);
    
    // 2. Build context
    $context = assistant_get_relevant_context($user_id, $user_message);
    $tools = Assistant_Tool_Registry::get_tools_for_llm();
    
    // 3. Build messages array for LLM
    $messages = [
        ['role' => 'system', 'content' => assistant_build_system_prompt($context)],
        ...assistant_get_conversation_history($conversation_id, limit: 20),
    ];
    
    // 4. Call LLM with function-calling
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Content-Type' => 'application/json'],
        'body' => wp_json_encode([
            'model'    => 'gpt-4o',
            'messages' => $messages,
            'tools'    => $tools,
        ]),
        'timeout' => 60,
    ]);
    
    $result = json_decode(wp_remote_retrieve_body($response), true);
    $choice = $result['choices'][0]['message'];
    
    // 5. Handle tool calls
    if (!empty($choice['tool_calls'])) {
        $tool_results = [];
        $async_jobs = [];
        
        foreach ($choice['tool_calls'] as $call) {
            $args = json_decode($call['function']['arguments'], true);
            $tool_result = Assistant_Tool_Registry::execute(
                $call['function']['name'], 
                $args,
                ['conversation_id' => $conversation_id, 'user_id' => $user_id]
            );
            
            if (isset($tool_result['async']) && $tool_result['async']) {
                $async_jobs[] = $tool_result;
                $tool_results[] = [
                    'tool_call_id' => $call['id'],
                    'role' => 'tool',
                    'content' => "Job #{$tool_result['job_id']} queued. Will complete asynchronously.",
                ];
            } else {
                $tool_results[] = [
                    'tool_call_id' => $call['id'],
                    'role' => 'tool',
                    'content' => wp_json_encode($tool_result),
                ];
            }
        }
        
        // 6. If all tools were sync, do a follow-up LLM call with results
        // If some were async, return a "working on it" response
        if (!empty($async_jobs)) {
            $reply = "I'm working on that in the background. I'll update you when it's done.";
            assistant_store_message($conversation_id, 'assistant', $reply, [
                'pending_jobs' => array_column($async_jobs, 'job_id'),
            ]);
            return ['reply' => $reply, 'async_jobs' => $async_jobs];
        }
        
        // Follow-up call with tool results
        // ... (second LLM call to synthesise final answer)
    }
    
    // 7. Store and return the assistant's reply
    assistant_store_message($conversation_id, 'assistant', $choice['content']);
    
    // 8. Extract and store any memories
    assistant_extract_memories($user_id, $conversation_id, $user_message, $choice['content']);
    
    return ['reply' => $choice['content']];
}
```

---

## 7. Async Work Patterns Summary

| Pattern | Mechanism | Best For | WordPress Implementation |
|---------|-----------|----------|------------------------|
| **Fire & Forget** | Non-blocking `wp_remote_post` to self | Tasks < 5 min | Loopback AJAX with `blocking: false` |
| **Job Queue + Cron** | DB queue + `wp_schedule_single_event` | Reliable batch work | Custom table + WP-Cron |
| **Persistent Worker** | WP-CLI long-running process | High-throughput, time-sensitive | `wp assistant worker` via supervisor |
| **Scheduled Tasks** | `wp_schedule_event` / future `scheduled_at` | Reminders, recurring checks | Cron + job table with `scheduled_at` |
| **Heartbeat / Proactive** | Recurring cron + trigger checks | "AI that independently helps" | 15-min cron scanning for opportunities |
| **Polling for Results** | REST endpoint + JS `setInterval` | Chat UI waiting for async job | `/assistant/v1/job/{id}/status` |
| **Server-Sent Events** | SSE stream endpoint | Real-time streaming responses | PHP `text/event-stream` endpoint |
| **Chained Jobs** | Job completion triggers next job | Multi-step workflows | `on_complete` callback in job config |

### Chained/Multi-Step Async Example

```php
// "Research and write a blog post about X" becomes:
// Job 1: Search content → on_complete → 
// Job 2: Summarise findings → on_complete → 
// Job 3: Generate draft post → on_complete → 
// Job 4: Notify user

assistant_enqueue_job($conv_id, 'search_content', [
    'query' => 'foam mattresses',
    'on_complete' => [
        'type' => 'summarise_results',
        'on_complete' => [
            'type' => 'generate_draft',
            'on_complete' => [
                'type' => 'notify_user',
                'message' => 'Your draft post about foam mattresses is ready for review!'
            ]
        ]
    ]
]);
```

---

## 8. Chat Interface Options

| Approach | Effort | UX Quality |
|----------|--------|------------|
| **WP Admin page** (like your signals plugin) | Low | Good for admin users |
| **Shortcode + REST** | Medium | Frontend-facing, any theme |
| **Gutenberg block** | Medium | Modern WP-native |
| **External SPA** (React/Vue) hitting REST API | High | Best UX, most flexible |
| **WhatsApp/Telegram bot** → REST webhook | Medium | OpenClaw-style messaging |

---

## 9. Implementation Phases

### Phase 1: Foundation (MVP)
- [ ] Job queue table + WP-Cron processor
- [ ] Conversation + message tables
- [ ] Basic orchestrator with OpenAI function-calling
- [ ] 2-3 built-in tools (search_content, create_draft, set_reminder)
- [ ] Simple admin chat page

### Phase 2: Async Power
- [ ] Fire-and-forget loopback for immediate jobs
- [ ] Job status polling in chat UI
- [ ] Chained job execution
- [ ] Heartbeat / proactive trigger system

### Phase 3: Memory & Intelligence
- [ ] Long-term memory table with embeddings
- [ ] Semantic memory retrieval before each LLM call
- [ ] Memory extraction from conversations
- [ ] User preference learning

### Phase 4: Extensibility
- [ ] Skill/tool registration API (`assistant_register_tools` hook)
- [ ] WP-CLI worker for production environments
- [ ] SSE streaming for real-time responses
- [ ] External messaging integration (Telegram/WhatsApp webhook)

---

## 10. Key WordPress-Specific Gotchas

1. **PHP execution timeout** (30-60s default) — always offload heavy work to async jobs
2. **WP-Cron is lazy** — needs real system cron for reliability
3. **Memory limits** — `wp_raise_memory_limit('admin')` for big embedding operations
4. **Nonce expiry** — for long-running chat sessions, refresh nonces via AJAX
5. **Multisite** — scope all tables and jobs to `$wpdb->prefix`
6. **Object cache** — clear relevant caches when jobs complete

---

This plan gives you a WordPress-native approach to building something with OpenClaw's spirit — particularly the async job queue, heartbeat proactive system, and persistent memory. Want me to toggle to **Act mode** and write this to a markdown file in your project, or would you like to discuss/refine any section first?
