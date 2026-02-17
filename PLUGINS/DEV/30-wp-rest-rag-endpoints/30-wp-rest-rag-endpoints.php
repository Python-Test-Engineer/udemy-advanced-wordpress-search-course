<?php
/**
 * Plugin Name: ✅ 30 WP ENDPOINTS
 * Description: Recreates the REST API endpoints from the Posts RAG Manager plugin in a modular way.
 * Version: 1.0
 * Author: Craig West
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Configuration constants - change these to customize namespace and endpoint names
define('RAG_PLUGIN_NAMESPACE', 'search/v1');
define('RAG_SEARCH_ENDPOINT', 'search');
define('RAG_VECTOR_SEARCH_ENDPOINT', 'vector-search');
define('RAG_HYBRID_SEARCH_ENDPOINT', 'hybrid-search');
define('RAG_HYBRID_NAMESPACE', 'search/v1');
define('RAG_FTS_NATURAL_NAMESPACE', 'fts-natural/v1');
define('RAG_FTS_BOOLEAN_NAMESPACE', 'fts-boolean/v1');
define('RAG_FTS_QUERY_EXPANSION_NAMESPACE', 'fts-query-expansion/v1');
define('RAG_TABLE_NAME', 'wp_posts_rag');
define('RAG_OPENAI_KEY_OPTION', 'posts_rag_openai_key');

class WP_REST_RAG_Endpoints {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_test_rag_search_natural', array($this, 'ajax_test_search_natural'));
        add_action('wp_ajax_test_rag_search_boolean', array($this, 'ajax_test_search_boolean'));
        add_action('wp_ajax_test_rag_search_query_expansion', array($this, 'ajax_test_search_query_expansion'));
        add_action('wp_ajax_test_rag_vector_search', array($this, 'ajax_test_vector_search'));
        add_action('wp_ajax_test_rag_hybrid_search', array($this, 'ajax_test_hybrid_search'));
        add_action('wp_ajax_create_rag_fulltext_index', array($this, 'ajax_create_fulltext_index'));
        add_action('wp_ajax_delete_rag_fulltext_index', array($this, 'ajax_delete_fulltext_index'));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Full-text search endpoint - Natural Language Mode
        register_rest_route(RAG_FTS_NATURAL_NAMESPACE, '/' . RAG_SEARCH_ENDPOINT, array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_search_posts_natural'),
            'permission_callback' => '__return_true',
            'args' => array(
                'query' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Search query string',
                    'sanitize_callback' => array($this, 'sanitize_fulltext_query')
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 3,
                    'description' => 'Number of results to return',
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Full-text search endpoint - Boolean Mode
        register_rest_route(RAG_FTS_BOOLEAN_NAMESPACE, '/' . RAG_SEARCH_ENDPOINT, array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_search_posts_boolean'),
            'permission_callback' => '__return_true',
            'args' => array(
                'query' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Search query string',
                    'sanitize_callback' => array($this, 'sanitize_boolean_query')
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 3,
                    'description' => 'Number of results to return',
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Full-text search endpoint - Query Expansion Mode
        register_rest_route(RAG_FTS_QUERY_EXPANSION_NAMESPACE, '/' . RAG_SEARCH_ENDPOINT, array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_search_posts_query_expansion'),
            'permission_callback' => '__return_true',
            'args' => array(
                'query' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Search query string',
                    'sanitize_callback' => array($this, 'sanitize_fulltext_query')
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 3,
                    'description' => 'Number of results to return',
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Vector search endpoint
        register_rest_route(RAG_PLUGIN_NAMESPACE, '/' . RAG_VECTOR_SEARCH_ENDPOINT, array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_vector_search'),
            'permission_callback' => '__return_true',
            'args' => array(
                'query' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Search query string',
                    'sanitize_callback' => array($this, 'sanitize_boolean_query')
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 3,
                    'description' => 'Number of results to return (1-20)',
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Hybrid search endpoint
        register_rest_route(RAG_HYBRID_NAMESPACE, '/' . RAG_HYBRID_SEARCH_ENDPOINT, array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_hybrid_search'),
            'permission_callback' => '__return_true',
            'args' => array(
                'query' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Search query string',
                    'sanitize_callback' => array($this, 'sanitize_boolean_query')
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 3,
                    'description' => 'Number of results per search method (1-10)',
                    'sanitize_callback' => 'absint'
                )
            )
        ));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'WP REST RAG Endpoints',
            '30 ENDPOINTS',
            'manage_options',
            'wp-rest-rag-endpoints',
            array($this, 'admin_page'),
            'dashicons-search',
            4.5
        );
    }

    /**
     * Sanitize boolean full-text queries while preserving operators.
     */
    public function sanitize_boolean_query($value) {
        $value = is_string($value) ? wp_unslash($value) : '';
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $allowed_pattern = '/[^\p{L}\p{N}\s\+\-\*\"\(\)\|<>]/u';
        $value = preg_replace($allowed_pattern, ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    /**
     * Sanitize general full-text queries (natural language, query expansion).
     */
    public function sanitize_fulltext_query($value) {
        $value = is_string($value) ? wp_unslash($value) : '';
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $allowed_pattern = '/[^\p{L}\p{N}\s\"\']+/u';
        $value = preg_replace($allowed_pattern, ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    /**
     * Admin page content
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        $index_exists = $this->check_fulltext_index();
        $all_indexes = $this->get_all_indexes();

        ?>
        <style>
            .rag-card {
                width: 95%;
                font-size: 1.25em;
                margin-top: 20px;
                background: #fff;
                border: 1px solid #c3c4c7;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .rag-card h3{ margin-bottom: -10px; }

            .rag-card h2 {
                margin-top: 0;
            }
            .rag-card code {
                font-size: 1.5rem;
                line-height: 1.4;
            }
            .rag-indexes-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }
            .rag-indexes-table th,
            .rag-indexes-table td {
                border: 1px solid #c3c4c7;
                padding: 8px 12px;
                text-align: left;
            }
            .rag-indexes-table th {
                background: #f5f5f5;
                font-weight: 600;
            }
            .rag-indexes-table .index-type-fulltext {
                color: #0073aa;
                font-weight: 600;
            }
            .rag-index-buttons {
                display: flex;
                gap: 10px;
                margin-top: 15px;
            }
        </style>
        <div class="wrap">
            <h1>WP REST RAG Endpoints</h1>

            <div id="rag-message" style="display:none;" class="notice">
                <p></p>
            </div>

            <div class="rag-card">
                <h2>Full-Text Index</h2>
                <p>The REST full-text search endpoint requires a MySQL full-text index on the <code><?php echo RAG_TABLE_NAME; ?></code> table.</p>
                
                <?php if (!empty($all_indexes)): ?>
                    <h3>Current Indexes</h3>
                    <table class="rag-indexes-table">
                        <thead>
                            <tr>
                                <th>Index Name</th>
                                <th>Columns</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grouped_indexes = array();
                            foreach ($all_indexes as $index) {
                                if (!isset($grouped_indexes[$index->Key_name])) {
                                    $grouped_indexes[$index->Key_name] = array(
                                        'columns' => array(),
                                        'type' => $index->Index_type
                                    );
                                }
                                $grouped_indexes[$index->Key_name]['columns'][] = $index->Column_name;
                            }
                            foreach ($grouped_indexes as $index_name => $index_info): 
                                $is_fulltext = (strpos($index_name, 'fulltext') !== false);
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($index_name); ?></strong>
                                        <?php if ($index_name === 'fulltext_search_idx'): ?>
                                            <span style="color: #0073aa;">(FTS)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html(implode(', ', $index_info['columns'])); ?></td>
                                    <td class="<?php echo $is_fulltext ? 'index-type-fulltext' : ''; ?>">
                                        <?php echo esc_html($index_info['type']); ?>
                                        <?php if ($is_fulltext): ?>
                                            <br><small>(Full-Text)</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No indexes found on this table.</p>
                <?php endif; ?>

                <p style="margin-top: 15px;">Status: <strong style="color: <?php echo $index_exists ? 'green' : 'red'; ?>;">
                    <?php echo $index_exists ? '✅ Created' : '❌ Not Created'; ?>
                </strong></p>

                <div class="rag-index-buttons">
                    <?php if (!$index_exists): ?>
                        <button type="button" id="create-fulltext-index-btn" class="button button-primary">
                            Create Full-Text Index
                        </button>
                    <?php else: ?>
                        <button type="button" id="delete-fulltext-index-btn" class="button button-secondary">
                            Delete Full-Text Index
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rag-card">
                <h2>Test FTS Natural Language Endpoint</h2>
                <p>Test the <code><?php echo RAG_SEARCH_ENDPOINT; ?></code> endpoint (natural language mode) with query "FOAM" and limit 3.</p>
                <button type="button" id="test-search-natural-btn" class="button button-primary">Test FTS Natural</button>
                <div id="search-natural-results" style="margin-top: 15px; display: none;">
                    <h3>Results:</h3>
                    <pre id="search-natural-response" style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto;"></pre>
                </div>
                <details style="margin-top: 12px;">
                    <summary><strong>Sample Output (Natural Language Mode)</strong></summary>
                    <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto;">{
  "success": true,
  "query": "FOAM",
  "method": "fulltext_search_natural",
  "sql": "SELECT ... AGAINST ('FOAM' IN NATURAL LANGUAGE MODE) ...",
  "results": [
    {
      "post_id": 101,
      "post_title": "Memory Foam Mattress Guide",
      "relevance_score": 4.5231,
      "categories": "Mattresses",
      "tags": "foam, sleep",
      "content": "..."
    }
  ],
  "count": 1
}</pre>
                </details>
            </div>

            <div class="rag-card">
                <h2>Test FTS Boolean Endpoint</h2>
                <p>Test the <code><?php echo RAG_SEARCH_ENDPOINT; ?></code> endpoint (boolean mode) with query "FOAM" and limit 3.</p>
                <button type="button" id="test-search-boolean-btn" class="button button-primary">Test FTS Boolean</button>
                <div id="search-boolean-results" style="margin-top: 15px; display: none;">
                    <h3>Results:</h3>
                    <pre id="search-boolean-response" style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto;"></pre>
                </div>
                <details style="margin-top: 12px;">
                    <summary><strong>Sample Output (Boolean Mode)</strong></summary>
                    <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto;">{
  "success": true,
  "query": "+FOAM -latex",
  "method": "fulltext_search_boolean",
  "sql": "SELECT ... AGAINST ('+FOAM -latex' IN BOOLEAN MODE) ...",
  "results": [
    {
      "post_id": 102,
      "post_title": "Foam Pillow Essentials",
      "relevance_score": 6.1189,
      "categories": "Pillows",
      "tags": "foam, comfort",
      "content": "..."
    }
  ],
  "count": 1
}</pre>
                </details>
            </div>

            <div class="rag-card">
                <h2>Test FTS Query Expansion Endpoint</h2>
                <p>Test the <code><?php echo RAG_SEARCH_ENDPOINT; ?></code> endpoint (query expansion) with query "FOAM" and limit 3.</p>
                <button type="button" id="test-search-query-expansion-btn" class="button button-primary">Test FTS Query Expansion</button>
                <div id="search-query-expansion-results" style="margin-top: 15px; display: none;">
                    <h3>Results:</h3>
                    <pre id="search-query-expansion-response" style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto;"></pre>
                </div>
                <details style="margin-top: 12px;">
                    <summary><strong>Sample Output (Query Expansion)</strong></summary>
                    <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto;">{
  "success": true,
  "query": "FOAM",
  "method": "fulltext_search_query_expansion",
  "sql": "SELECT ... AGAINST ('FOAM' IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION) ...",
  "results": [
    {
      "post_id": 103,
      "post_title": "High-Density Foam",
      "relevance_score": 5.9024,
      "categories": "Materials",
      "tags": "foam, density",
      "content": "..."
    }
  ],
  "count": 1
}</pre>
                </details>
            </div>

            <div class="rag-card">
                <h2>Test Vector Search Endpoint</h2>
                <p>Test the <code><?php echo RAG_VECTOR_SEARCH_ENDPOINT; ?></code> endpoint with query "FOAM" and limit 3.</p>
                <button type="button" id="test-vector-search-btn" class="button button-primary">Test Vector Search</button>
                <div id="vector-search-results" style="margin-top: 15px; display: none;">
                    <h3>Results:</h3>
                    <pre id="vector-search-response" style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto;"></pre>
                </div>
            </div>

            <div class="rag-card">
                <h2>Test Hybrid Search Endpoint</h2>
                <p>Test the <code><?php echo RAG_HYBRID_SEARCH_ENDPOINT; ?></code> endpoint with query "FOAM" and limit 3 (gets 3 from each method, combines and deduplicates).</p>
                <label for="hybrid-fts-mode" style="display: inline-block; margin-right: 8px;">FTS Mode:</label>
                <select id="hybrid-fts-mode" style="min-width: 220px;">
                    <option value="boolean">Boolean</option>
                    <option value="natural">Natural Language</option>
                    <option value="query_expansion">Query Expansion</option>
                </select>
                <button type="button" id="test-hybrid-search-btn" class="button button-primary">Test Hybrid Search</button>
                <div id="hybrid-search-results" style="margin-top: 15px; display: none;">
                    <h3>Results:</h3>
                    <pre id="hybrid-search-response" style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto;"></pre>
                </div>
            </div>

            <div class="rag-card">
                <h2>REST API Endpoints</h2>
                <h3>FTS Natural Language</h3>
                <p>Search using MySQL full-text index (natural language mode):</p>
                <code><?php echo esc_url(rest_url(RAG_FTS_NATURAL_NAMESPACE . '/' . RAG_SEARCH_ENDPOINT)); ?>?query=FOAM&limit=3</code>

                <h3 style="margin-top: 15px;">FTS Boolean</h3>
                <p>Search using MySQL full-text index (boolean mode):</p>
                <code><?php echo esc_url(rest_url(RAG_FTS_BOOLEAN_NAMESPACE . '/' . RAG_SEARCH_ENDPOINT)); ?>?query=FOAM&limit=3</code>

                <h3 style="margin-top: 15px;">FTS Query Expansion</h3>
                <p>Search using MySQL full-text index (query expansion mode):</p>
                <code><?php echo esc_url(rest_url(RAG_FTS_QUERY_EXPANSION_NAMESPACE . '/' . RAG_SEARCH_ENDPOINT)); ?>?query=FOAM&limit=3</code>

                <h3 style="margin-top: 15px;">Vector Search</h3>
                <p>Search using semantic similarity (requires embeddings):</p>
                <code><?php echo esc_url(rest_url(RAG_PLUGIN_NAMESPACE . '/' . RAG_VECTOR_SEARCH_ENDPOINT)); ?>?query=FOAM&limit=3</code>

                <h3 style="margin-top: 15px;">Hybrid Search</h3>
                <p>Combines full-text and vector search results (deduplicated):</p>
                <code><?php echo esc_url(rest_url(RAG_HYBRID_NAMESPACE . '/' . RAG_HYBRID_SEARCH_ENDPOINT)); ?>?query=FOAM&limit=3</code>

                <p class="description" style="margin-top: 10px;">
                    <strong>Parameters:</strong> <strong>query</strong> (required), <strong>limit</strong> (optional, default: 3, max: 10 per method)
                </p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {

            function showMessage(message, type) {
                var $msg = $('#rag-message');
                $msg.removeClass('notice-success notice-error notice-info')
                    .addClass('notice-' + type)
                    .find('p').text(message);
                $msg.show();

                setTimeout(function() {
                    $msg.fadeOut();
                }, 5000);
            }

            // Test FTS Natural Language Search
            $('#test-search-natural-btn').on('click', function() {
                var $btn = $(this);
                var $results = $('#search-natural-results');
                var $response = $('#search-natural-response');

                $btn.prop('disabled', true).text('Testing...');
                $results.hide();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_rag_search_natural'
                    },
                    success: function(response) {
                        if (response.success) {
                            $response.text(JSON.stringify(response.data, null, 2));
                            $results.show();
                            showMessage('FTS natural language test completed successfully!', 'success');
                        } else {
                            showMessage(response.data || 'Search failed', 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while testing the search.', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test FTS Natural');
                    }
                });
            });

            // Test FTS Boolean Search
            $('#test-search-boolean-btn').on('click', function() {
                var $btn = $(this);
                var $results = $('#search-boolean-results');
                var $response = $('#search-boolean-response');

                $btn.prop('disabled', true).text('Testing...');
                $results.hide();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_rag_search_boolean'
                    },
                    success: function(response) {
                        if (response.success) {
                            $response.text(JSON.stringify(response.data, null, 2));
                            $results.show();
                            showMessage('FTS boolean test completed successfully!', 'success');
                        } else {
                            showMessage(response.data || 'Search failed', 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while testing the search.', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test FTS Boolean');
                    }
                });
            });

            // Test FTS Query Expansion Search
            $('#test-search-query-expansion-btn').on('click', function() {
                var $btn = $(this);
                var $results = $('#search-query-expansion-results');
                var $response = $('#search-query-expansion-response');

                $btn.prop('disabled', true).text('Testing...');
                $results.hide();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_rag_search_query_expansion'
                    },
                    success: function(response) {
                        if (response.success) {
                            $response.text(JSON.stringify(response.data, null, 2));
                            $results.show();
                            showMessage('FTS query expansion test completed successfully!', 'success');
                        } else {
                            showMessage(response.data || 'Search failed', 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while testing the search.', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test FTS Query Expansion');
                    }
                });
            });

            // Test Vector Search
            $('#test-vector-search-btn').on('click', function() {
                var $btn = $(this);
                var $results = $('#vector-search-results');
                var $response = $('#vector-search-response');

                $btn.prop('disabled', true).text('Testing...');
                $results.hide();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_rag_vector_search'
                    },
                    success: function(response) {
                        if (response.success) {
                            $response.text(JSON.stringify(response.data, null, 2));
                            $results.show();
                            showMessage('Vector search test completed successfully!', 'success');
                        } else {
                            showMessage(response.data || 'Search failed', 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while testing the vector search.', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test Vector Search');
                    }
                });
            });

            // Test Hybrid Search
            $('#test-hybrid-search-btn').on('click', function() {
                var $btn = $(this);
                var $results = $('#hybrid-search-results');
                var $response = $('#hybrid-search-response');
                var ftsMode = $('#hybrid-fts-mode').val();

                $btn.prop('disabled', true).text('Testing...');
                $results.hide();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_rag_hybrid_search',
                        fts_mode: ftsMode
                    },
                    success: function(response) {
                        if (response.success) {
                            $response.text(JSON.stringify(response.data, null, 2));
                            $results.show();
                            showMessage('Hybrid search test completed successfully!', 'success');
                        } else {
                            showMessage(response.data || 'Search failed', 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while testing the hybrid search.', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test Hybrid Search');
                    }
                });
            });

            // Create Full-Text Index
            $('#create-fulltext-index-btn').on('click', function() {
                var $btn = $(this);

                $btn.prop('disabled', true).text('Creating Index...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'create_rag_fulltext_index'
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data, 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showMessage(response.data || 'Failed to create index.', 'error');
                            $btn.prop('disabled', false).text('Create Full-Text Index');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while creating the index.', 'error');
                        $btn.prop('disabled', false).text('Create Full-Text Index');
                    }
                });
            });

            // Delete Full-Text Index
            $('#delete-fulltext-index-btn').on('click', function() {
                var $btn = $(this);

                if (!confirm('Are you sure you want to delete the full-text index? This will disable full-text search functionality.')) {
                    return;
                }

                $btn.prop('disabled', true).text('Deleting Index...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delete_rag_fulltext_index'
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data, 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showMessage(response.data || 'Failed to delete index.', 'error');
                            $btn.prop('disabled', false).text('Delete Full-Text Index');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while deleting the index.', 'error');
                        $btn.prop('disabled', false).text('Delete Full-Text Index');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * REST API endpoint: Search posts (natural language mode)
     */
    public function rest_search_posts_natural($request) {
        return $this->rest_fulltext_search($request, 'natural', 'fulltext_search_natural');
    }

    /**
     * REST API endpoint: Search posts (boolean mode)
     */
    public function rest_search_posts_boolean($request) {
        return $this->rest_fulltext_search($request, 'boolean', 'fulltext_search_boolean');
    }

    /**
     * REST API endpoint: Search posts (query expansion mode)
     */
    public function rest_search_posts_query_expansion($request) {
        return $this->rest_fulltext_search($request, 'query_expansion', 'fulltext_search_query_expansion');
    }

    /**
     * Shared full-text REST handler for multiple modes.
     */
    private function rest_fulltext_search($request, $mode, $method_label) {
        global $wpdb;

        $query = $request->get_param('query');
        $limit = $request->get_param('limit');

        if (empty($query)) {
            return new WP_Error('invalid_query', 'Query parameter is required', array('status' => 400));
        }

        // Limit between 1 and 20
        $limit = max(1, min(20, $limit));

        // Check if fulltext index exists
        $index_exists = $this->check_fulltext_index();

        if (!$index_exists) {
            return new WP_Error('no_index', 'Full-text index not created. Please create it from the admin panel.', array('status' => 500));
        }

        // Perform full-text search
        $search_data = $this->fulltext_search($query, $limit, $mode);
        $results = $search_data['results'];
        $sql = $this->normalize_sql_output($search_data['sql']);

        if (empty($results)) {
            return array(
                'success' => true,
                'query' => $query,
                'method' => $method_label,
                'sql' => $sql,
                'results' => array(),
                'count' => 0
            );
        }

        // Format results
        $formatted_results = array();
        foreach ($results as $row) {
            $formatted_results[] = array(
                'post_id' => intval($row->post_id),
                'post_title' => $row->post_title,
                'relevance_score' => floatval($row->relevance_score),
                'categories' => $row->categories,
                'tags' => $row->tags,
                'content' => $row->post_content
            );
        }

        return array(
            'success' => true,
            'query' => $query,
            'method' => $method_label,
            'sql' => $sql,
            'results' => $formatted_results,
            'count' => count($formatted_results)
        );
    }

    /**
     * REST API endpoint: Vector search using cosine similarity
     */
    public function rest_vector_search($request) {
        $query = $request->get_param('query');
        $limit = $request->get_param('limit');

        if (empty($query)) {
            return new WP_Error('invalid_query', 'Query parameter is required', array('status' => 400));
        }

        // Limit between 1 and 20
        $limit = max(1, min(20, $limit));

        // Perform vector search
        $result = $this->vector_search($query, $limit);

        if (!$result['success']) {
            return new WP_Error('search_failed', $result['message'], array('status' => 500));
        }

        return array(
            'success' => true,
            'query' => $query,
            'method' => 'vector_search',
            'sql' => 'none',
            'results' => $result['results'],
            'count' => count($result['results'])
        );
    }

    /**
     * REST API endpoint: Hybrid search combining full-text and vector search
     */
    public function rest_hybrid_search($request) {
        $query = $request->get_param('query');
        $limit = $request->get_param('limit');
        $fts_mode = $request->get_param('fts_mode');

        if (empty($query)) {
            return new WP_Error('invalid_query', 'Query parameter is required', array('status' => 400));
        }

        // Limit between 1 and 10 (per method, so total results will be up to 2x this)
        $limit = max(1, min(10, $limit));

        $valid_fts_modes = array('boolean', 'natural', 'query_expansion');
        $fts_mode = in_array($fts_mode, $valid_fts_modes, true) ? $fts_mode : 'boolean';

        $fts_routes = array(
            'boolean' => RAG_FTS_BOOLEAN_NAMESPACE,
            'natural' => RAG_FTS_NATURAL_NAMESPACE,
            'query_expansion' => RAG_FTS_QUERY_EXPANSION_NAMESPACE
        );

        $fts_labels = array(
            'boolean' => 'fulltext_search_boolean',
            'natural' => 'fulltext_search_natural',
            'query_expansion' => 'fulltext_search_query_expansion'
        );

        // Perform both searches
        $fulltext_results = array();
        $vector_results = array();
        $fulltext_sql = 'none';

        // Try full-text search first
        $fulltext_request = new WP_REST_Request('GET', $fts_routes[$fts_mode] . '/' . RAG_SEARCH_ENDPOINT);
        $fulltext_request->set_param('query', $query);
        $fulltext_request->set_param('limit', $limit);
        $fulltext_response = $this->rest_fulltext_search($fulltext_request, $fts_mode, $fts_labels[$fts_mode]);

        if (!is_wp_error($fulltext_response) && isset($fulltext_response['results'])) {
            $fulltext_results = $fulltext_response['results'];
            if (!empty($fulltext_response['sql'])) {
                $fulltext_sql = $this->normalize_sql_output($fulltext_response['sql']);
            }
        }

        // Try vector search
        $vector_request = new WP_REST_Request('GET', RAG_PLUGIN_NAMESPACE . '/' . RAG_VECTOR_SEARCH_ENDPOINT);
        $vector_request->set_param('query', $query);
        $vector_request->set_param('limit', $limit);
        $vector_response = $this->rest_vector_search($vector_request);

        if (!is_wp_error($vector_response) && isset($vector_response['results'])) {
            $vector_results = $vector_response['results'];
        }

        // Combine results, removing duplicates based on post_id
        $combined_results = array();
        $seen_post_ids = array();

        // Add full-text results first
        foreach ($fulltext_results as $result) {
            $post_id = $result['post_id'];
            if (!in_array($post_id, $seen_post_ids)) {
                $combined_results[] = array_merge($result, array('search_method' => 'fulltext'));
                $seen_post_ids[] = $post_id;
            }
        }

        // Add vector results
        foreach ($vector_results as $result) {
            $post_id = $result['post_id'];
            if (!in_array($post_id, $seen_post_ids)) {
                $combined_results[] = array_merge($result, array('search_method' => 'vector'));
                $seen_post_ids[] = $post_id;
            }
        }

        // If no results from either method, return empty
        if (empty($combined_results)) {
            return array(
                'success' => true,
                'query' => $query,
                'method' => 'hybrid_search',
                'fts_mode' => $fts_mode,
                'sql' => $fulltext_sql,
                'results' => array(),
                'count' => 0,
                'fulltext_count' => count($fulltext_results),
                'vector_count' => count($vector_results)
            );
        }

        return array(
            'success' => true,
            'query' => $query,
            'method' => 'hybrid_search',
            'fts_mode' => $fts_mode,
            'sql' => $fulltext_sql,
            'results' => $combined_results,
            'count' => count($combined_results),
            'fulltext_count' => count($fulltext_results),
            'vector_count' => count($vector_results)
        );
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    private function cosine_similarity($vec1, $vec2) {
        if (count($vec1) !== count($vec2)) {
            return 0;
        }

        $dot_product = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($vec1); $i++) {
            $dot_product += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] * $vec1[$i];
            $magnitude2 += $vec2[$i] * $vec2[$i];
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        return $dot_product / ($magnitude1 * $magnitude2);
    }
    #region VECTOR 
    /**
     * Perform vector search using cosine similarity
     */
    private function vector_search($query, $limit = 3) {
        global $wpdb;

        $api_key = $this->get_openai_api_key();

        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key is not configured or accessible.'
            );
        }

        // Generate embedding for the query
        $query_embedding = $this->get_openai_embedding($query, $api_key);

        if ($query_embedding === false) {
            return array(
                'success' => false,
                'message' => 'Failed to generate embedding for query.'
            );
        }

        // Get all posts with embeddings
        $posts = $wpdb->get_results(
            "SELECT id, post_id, post_title, post_content, categories, tags, embedding
            FROM " . RAG_TABLE_NAME . "
            WHERE embedding IS NOT NULL"
        );

        if (empty($posts)) {
            return array(
                'success' => false,
                'message' => 'No posts with embeddings found. Please generate embeddings first.'
            );
        }

        // Calculate cosine similarity for each post
        $similarities = array();

        foreach ($posts as $post) {
            $post_embedding = json_decode($post->embedding, true);

            if (is_array($post_embedding)) {
                $similarity = $this->cosine_similarity($query_embedding, $post_embedding);

                $similarities[] = array(
                    'post_id' => intval($post->post_id),
                    'post_title' => $post->post_title,
                    'similarity_score' => $similarity,
                    'categories' => $post->categories,
                    'tags' => $post->tags,
                    'content' => $post->post_content
                );
            }
        }

        // Sort by similarity score (highest first)
        usort($similarities, function($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });

        // Return top N results
        $top_results = array_slice($similarities, 0, $limit);

        return array(
            'success' => true,
            'results' => $top_results
        );
    }

    /**
     * Perform full-text search on the RAG table
     */
    private function fulltext_search($query, $limit = 3, $mode = 'boolean') {
        global $wpdb;

        $mode = strtolower($mode);
        $mode_sql = 'IN BOOLEAN MODE';

        if ($mode === 'natural') {
            $mode_sql = 'IN NATURAL LANGUAGE MODE';
        } elseif ($mode === 'query_expansion') {
            $mode_sql = 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION';
        }

        $sql = $wpdb->prepare(
            "SELECT
                post_id,
                post_title,
                post_content,
                categories,
                tags,
                MATCH(post_title, post_content)
                AGAINST (%s {$mode_sql}) as relevance_score
            FROM " . RAG_TABLE_NAME . "
            WHERE MATCH(post_title, post_content)
                AGAINST (%s {$mode_sql})
            ORDER BY relevance_score DESC
            LIMIT %d",
            $query,
            $query,
            $limit
        );

        return array(
            'results' => $wpdb->get_results($sql),
            'sql' => $sql
        );
    }

    /**
     * Normalize SQL output for JSON responses by removing line breaks.
     */
    private function normalize_sql_output($sql) {
        if (!is_string($sql)) {
            return $sql;
        }

        return trim(preg_replace('/\s*\r?\n\s*/', ' ', $sql));
    }

    /**
     * Check if full-text index exists
     */
    private function check_fulltext_index() {
        global $wpdb;

        $index_check = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW INDEX FROM " . RAG_TABLE_NAME . " WHERE Key_name = %s",
                'fulltext_search_idx'
            )
        );

        return !empty($index_check);
    }

    /**
     * Get all indexes on the RAG table
     */
    private function get_all_indexes() {
        global $wpdb;

        $indexes = $wpdb->get_results(
            "SHOW INDEX FROM " . RAG_TABLE_NAME
        );

        return $indexes;
    }
    /**
     * Delete full-text index on the RAG table
     */
    private function delete_fulltext_index() {
        global $wpdb;

        if (!$this->check_fulltext_index()) {
            return array(
                'success' => false,
                'message' => 'Full-text index does not exist.'
            );
        }

        $sql = "ALTER TABLE " . RAG_TABLE_NAME . " DROP INDEX fulltext_search_idx";
        $result = $wpdb->query($sql);

        if ($result === false) {
            return array(
                'success' => false,
                'message' => 'Failed to delete full-text index: ' . $wpdb->last_error
            );
        }

        return array(
            'success' => true,
            'message' => 'Full-text index deleted successfully.'
        );
    }

    /**
     * Create full-text index on the RAG table
     */
    private function create_fulltext_index() {
        global $wpdb;

        if ($this->check_fulltext_index()) {
            return array(
                'success' => false,
                'message' => 'Full-text index already exists.'
            );
        }

        $fields = array('post_title', 'post_content');
        $fields_str = implode(', ', $fields);

        $sql = "ALTER TABLE " . RAG_TABLE_NAME . " ADD FULLTEXT INDEX fulltext_search_idx ({$fields_str})";
        $result = $wpdb->query($sql);

        if ($result === false) {
            return array(
                'success' => false,
                'message' => 'Failed to create full-text index: ' . $wpdb->last_error
            );
        }

        return array(
            'success' => true,
            'message' => 'Full-text index created successfully.'
        );
    }

    /**
     * Get OpenAI API key from the dedicated plugin
     */
    private function get_openai_api_key() {
        // Check if the OpenAI key plugin is available
        if (class_exists('WP_REST_OpenAI_Key')) {
            $openai_key_plugin = new WP_REST_OpenAI_Key();
            return $openai_key_plugin->getKey();
        }

        // Fallback to direct option access if plugin not available
        return get_option(RAG_OPENAI_KEY_OPTION);
    }

    /**
     * AJAX handler for testing FTS natural language search
     */
    public function ajax_test_search_natural() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $query = 'FOAM';
        $limit = 3;

        $request = new WP_REST_Request('GET', RAG_FTS_NATURAL_NAMESPACE . '/' . RAG_SEARCH_ENDPOINT);
        $request->set_param('query', $query);
        $request->set_param('limit', $limit);

        $response = $this->rest_search_posts_natural($request);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        } else {
            wp_send_json_success($response);
        }
    }

    /**
     * AJAX handler for testing FTS boolean search
     */
    public function ajax_test_search_boolean() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $query = 'FOAM';
        $limit = 3;

        $request = new WP_REST_Request('GET', RAG_FTS_BOOLEAN_NAMESPACE . '/' . RAG_SEARCH_ENDPOINT);
        $request->set_param('query', $query);
        $request->set_param('limit', $limit);

        $response = $this->rest_search_posts_boolean($request);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        } else {
            wp_send_json_success($response);
        }
    }

    /**
     * AJAX handler for testing FTS query expansion search
     */
    public function ajax_test_search_query_expansion() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $query = 'FOAM';
        $limit = 3;

        $request = new WP_REST_Request('GET', RAG_FTS_QUERY_EXPANSION_NAMESPACE . '/' . RAG_SEARCH_ENDPOINT);
        $request->set_param('query', $query);
        $request->set_param('limit', $limit);

        $response = $this->rest_search_posts_query_expansion($request);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        } else {
            wp_send_json_success($response);
        }
    }

    /**
     * AJAX handler for testing vector search
     */
    public function ajax_test_vector_search() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $query = 'FOAM';
        $limit = 3;

        $request = new WP_REST_Request('GET', RAG_PLUGIN_NAMESPACE . '/' . RAG_VECTOR_SEARCH_ENDPOINT);
        $request->set_param('query', $query);
        $request->set_param('limit', $limit);

        $response = $this->rest_vector_search($request);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        } else {
            wp_send_json_success($response);
        }
    }

    /**
     * AJAX handler for testing hybrid search
     */
    public function ajax_test_hybrid_search() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $query = 'FOAM';
        $limit = 3;
        $fts_mode = isset($_POST['fts_mode']) ? sanitize_text_field(wp_unslash($_POST['fts_mode'])) : 'boolean';

        $request = new WP_REST_Request('GET', RAG_HYBRID_NAMESPACE . '/' . RAG_HYBRID_SEARCH_ENDPOINT);
        $request->set_param('query', $query);
        $request->set_param('limit', $limit);
        $request->set_param('fts_mode', $fts_mode);

        $response = $this->rest_hybrid_search($request);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        } else {
            wp_send_json_success($response);
        }
    }

    /**
     * AJAX handler for creating full-text index
     */
    public function ajax_create_fulltext_index() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $result = $this->create_fulltext_index();

        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX handler for deleting full-text index
     */
    public function ajax_delete_fulltext_index() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $result = $this->delete_fulltext_index();

        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    #region model
    /**
     * Call OpenAI API to get embedding for text
     */
    private function get_openai_embedding($text, $api_key) {
        $url = 'https://api.openai.com/v1/embeddings';

        $data = array(
            'input' => $text,
            'model' => 'text-embedding-3-small'
        );

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode($data),
            'timeout' => 30
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('OpenAI API Error: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['data'][0]['embedding'])) {
            return $result['data'][0]['embedding'];
        }

        if (isset($result['error'])) {
            error_log('OpenAI API Error: ' . $result['error']['message']);
        }

        return false;
    }
}

// Initialize the plugin
new WP_REST_RAG_Endpoints();
