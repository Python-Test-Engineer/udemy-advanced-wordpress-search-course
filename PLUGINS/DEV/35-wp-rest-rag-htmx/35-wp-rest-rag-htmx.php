<?php
/**
 * Plugin Name: ✅ 35 WP RAG HTMX Interface
 * Description: HTMX-powered admin interface for testing WP REST RAG Endpoints with buttons.
 * Version: 1.0
 * Author: Craig West
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load HTMX from CDN
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'toplevel_page_wp-rest-rag-htmx') {
        return;
    }
    wp_enqueue_script('htmx', 'https://unpkg.com/htmx.org@1.9.12', array(), null, true);
});

// Add admin menu at level 30
add_action('admin_menu', function() {
    add_menu_page(
        'WP RAG HTMX Interface',
        '35 RAG HTMX',
        'manage_options',
        'wp-rest-rag-htmx',
        'wp_rag_htmx_admin_page',
        'dashicons-search',
        30
    );
});

/**
 * Admin page content with HTMX-powered buttons
 */
function wp_rag_htmx_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    // Get the REST API base URL
    $rest_url = rest_url();
    $namespace_natural = 'fts-natural/v1';
    $namespace_boolean = 'fts-boolean/v1';
    $namespace_query_expansion = 'fts-query-expansion/v1';
    $namespace_vector = 'search/v1';
    $namespace_hybrid = 'search/v1';
    ?>
    <div class="wrap">
        <h1>WP RAG HTMX Interface</h1>
        <p>Use the buttons below to test the REST API endpoints via HTMX.</p>

        <!-- Search Query Input -->
        <div class="card" style="margin-top: 20px;">
            <h2>Search Query</h2>
            <label for="search-query">Query:</label>
            <input type="text" id="search-query" value="FOAM" style="min-width: 300px; margin-left: 10px;">
            <label for="result-limit" style="margin-left: 20px;">Limit:</label>
            <input type="number" id="result-limit" value="3" min="1" max="20" style="width: 60px; margin-left: 10px;">
        </div>

        <!-- FTS Natural Language -->
        <div class="card" style="margin-top: 20px;">
            <h2>FTS Natural Language Search</h2>
            <p>Test the natural language full-text search endpoint.</p>
            <button 
                class="button button-primary"
                hx-get="<?php echo esc_url($rest_url . $namespace_natural . '/search'); ?>"
                hx-vals="js:{query: document.getElementById('search-query').value, limit: document.getElementById('result-limit').value}"
                hx-target="#natural-results"
                hx-swap="innerHTML"
                hx-indicator="#natural-spinner">
                Test FTS Natural
            </button>
            <span id="natural-spinner" class="htmx-indicator" style="margin-left: 10px;">
                <span class="spinner is-active" style="float: none; margin-top: 0;"></span> Loading...
            </span>
            <div id="natural-results" style="margin-top: 15px;">
                <p class="description">Click the button to see results.</p>
            </div>
        </div>

        <!-- FTS Boolean -->
        <div class="card" style="margin-top: 20px;">
            <h2>FTS Boolean Search</h2>
            <p>Test the boolean mode full-text search endpoint. Supports operators like +, -, *, "exact phrase".</p>
            <button 
                class="button button-primary"
                hx-get="<?php echo esc_url($rest_url . $namespace_boolean . '/search'); ?>"
                hx-vals="js:{query: document.getElementById('search-query').value, limit: document.getElementById('result-limit').value}"
                hx-target="#boolean-results"
                hx-swap="innerHTML"
                hx-indicator="#boolean-spinner">
                Test FTS Boolean
            </button>
            <span id="boolean-spinner" class="htmx-indicator" style="margin-left: 10px;">
                <span class="spinner is-active" style="float: none; margin-top: 0;"></span> Loading...
            </span>
            <div id="boolean-results" style="margin-top: 15px;">
                <p class="description">Click the button to see results.</p>
            </div>
        </div>

        <!-- FTS Query Expansion -->
        <div class="card" style="margin-top: 20px;">
            <h2>FTS Query Expansion Search</h2>
            <p>Test the query expansion full-text search endpoint. Expands search with related terms.</p>
            <button 
                class="button button-primary"
                hx-get="<?php echo esc_url($rest_url . $namespace_query_expansion . '/search'); ?>"
                hx-vals="js:{query: document.getElementById('search-query').value, limit: document.getElementById('result-limit').value}"
                hx-target="#query-expansion-results"
                hx-swap="innerHTML"
                hx-indicator="#query-expansion-spinner">
                Test FTS Query Expansion
            </button>
            <span id="query-expansion-spinner" class="htmx-indicator" style="margin-left: 10px;">
                <span class="spinner is-active" style="float: none; margin-top: 0;"></span> Loading...
            </span>
            <div id="query-expansion-results" style="margin-top: 15px;">
                <p class="description">Click the button to see results.</p>
            </div>
        </div>

        <!-- Vector Search -->
        <div class="card" style="margin-top: 20px;">
            <h2>Vector Search</h2>
            <p>Test the vector/semantic similarity search endpoint. Requires embeddings to be generated.</p>
            <button 
                class="button button-primary"
                hx-get="<?php echo esc_url($rest_url . $namespace_vector . '/vector-search'); ?>"
                hx-vals="js:{query: document.getElementById('search-query').value, limit: document.getElementById('result-limit').value}"
                hx-target="#vector-results"
                hx-swap="innerHTML"
                hx-indicator="#vector-spinner">
                Test Vector Search
            </button>
            <span id="vector-spinner" class="htmx-indicator" style="margin-left: 10px;">
                <span class="spinner is-active" style="float: none; margin-top: 0;"></span> Loading...
            </span>
            <div id="vector-results" style="margin-top: 15px;">
                <p class="description">Click the button to see results.</p>
            </div>
        </div>

        <!-- Hybrid Search -->
        <div class="card" style="margin-top: 20px;">
            <h2>Hybrid Search</h2>
            <p>Test the hybrid search endpoint that combines full-text and vector search results.</p>
            <label for="hybrid-fts-mode" style="display: inline-block; margin-right: 8px;">FTS Mode:</label>
            <select id="hybrid-fts-mode" style="min-width: 180px; margin-right: 10px;">
                <option value="boolean">Boolean</option>
                <option value="natural">Natural Language</option>
                <option value="query_expansion">Query Expansion</option>
            </select>
            <button 
                class="button button-primary"
                hx-get="<?php echo esc_url($rest_url . $namespace_hybrid . '/hybrid-search'); ?>"
                hx-vals="js:{query: document.getElementById('search-query').value, limit: document.getElementById('result-limit').value, fts_mode: document.getElementById('hybrid-fts-mode').value}"
                hx-target="#hybrid-results"
                hx-swap="innerHTML"
                hx-indicator="#hybrid-spinner">
                Test Hybrid Search
            </button>
            <span id="hybrid-spinner" class="htmx-indicator" style="margin-left: 10px;">
                <span class="spinner is-active" style="float: none; margin-top: 0;"></span> Loading...
            </span>
            <div id="hybrid-results" style="margin-top: 15px;">
                <p class="description">Click the button to see results.</p>
            </div>
        </div>

        <!-- API Endpoints Reference -->
        <div class="card" style="margin-top: 20px;">
            <h2>REST API Endpoints Reference</h2>
            <table class="widefat" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>URL</th>
                        <th>Parameters</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>FTS Natural</td>
                        <td><code><?php echo esc_url($rest_url . $namespace_natural . '/search'); ?></code></td>
                        <td>query (required), limit (optional)</td>
                    </tr>
                    <tr>
                        <td>FTS Boolean</td>
                        <td><code><?php echo esc_url($rest_url . $namespace_boolean . '/search'); ?></code></td>
                        <td>query (required), limit (optional)</td>
                    </tr>
                    <tr>
                        <td>FTS Query Expansion</td>
                        <td><code><?php echo esc_url($rest_url . $namespace_query_expansion . '/search'); ?></code></td>
                        <td>query (required), limit (optional)</td>
                    </tr>
                    <tr>
                        <td>Vector Search</td>
                        <td><code><?php echo esc_url($rest_url . $namespace_vector . '/vector-search'); ?></code></td>
                        <td>query (required), limit (optional)</td>
                    </tr>
                    <tr>
                        <td>Hybrid Search</td>
                        <td><code><?php echo esc_url($rest_url . $namespace_hybrid . '/hybrid-search'); ?></code></td>
                        <td>query (required), limit (optional), fts_mode (optional: boolean|natural|query_expansion)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .htmx-request .spinner {
            visibility: visible;
        }
        .htmx-indicator {
            display: none;
        }
        .htmx-request .htmx-indicator {
            display: inline-block;
        }
        pre.json-result {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }
        .error-result {
            background: #ffeaea;
            border-left: 4px solid #dc3232;
            padding: 15px;
            margin: 10px 0;
        }
        .success-result {
            background: #eafaea;
            border-left: 4px solid #46b450;
            padding: 15px;
            margin: 10px 0;
        }
    </style>

    <script>
        // HTMX event handlers to format JSON responses
        document.body.addEventListener('htmx:afterRequest', function(evt) {
            var target = evt.detail.target;
            var xhr = evt.detail.xhr;
            
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    var formatted = JSON.stringify(data, null, 2);
                    target.innerHTML = '<div class="success-result"><h4>Success (HTTP ' + xhr.status + ')</h4><pre class="json-result">' + escapeHtml(formatted) + '</pre></div>';
                } catch (e) {
                    target.innerHTML = '<div class="success-result"><h4>Success (HTTP ' + xhr.status + ')</h4><pre class="json-result">' + escapeHtml(xhr.responseText) + '</pre></div>';
                }
            } else {
                var errorMsg = xhr.responseText || 'An error occurred';
                try {
                    var data = JSON.parse(xhr.responseText);
                    errorMsg = JSON.stringify(data, null, 2);
                } catch (e) {}
                target.innerHTML = '<div class="error-result"><h4>Error (HTTP ' + xhr.status + ')</h4><pre class="json-result">' + escapeHtml(errorMsg) + '</pre></div>';
            }
        });

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    <?php
}