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
define('RAG_TABLE_NAME', 'wp_posts_rag');
define('RAG_OPENAI_KEY_OPTION', 'posts_rag_openai_key');

class WP_REST_RAG_Endpoints {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_test_rag_search', array($this, 'ajax_test_search'));
        add_action('wp_ajax_test_rag_vector_search', array($this, 'ajax_test_vector_search'));
        add_action('wp_ajax_test_rag_hybrid_search', array($this, 'ajax_test_hybrid_search'));
        add_action('wp_ajax_create_rag_fulltext_index', array($this, 'ajax_create_fulltext_index'));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Full-text search endpoint
        register_rest_route(RAG_PLUGIN_NAMESPACE, '/' . RAG_SEARCH_ENDPOINT, array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_search_posts'),
            'permission_callback' => '__return_true',
            'args' => array(
                'query' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Search query string',
                    'sanitize_callback' => 'sanitize_text_field'
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
                    'sanitize_callback' => 'sanitize_text_field'
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
                    'sanitize_callback' => 'sanitize_text_field'
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
     * Admin page content
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        $index_exists = $this->check_fulltext_index();

        ?>
        <div class="wrap">
            <h1>WP REST RAG Endpoints</h1>

            <div id="rag-message" style="display:none;" class="notice">
                <p></p>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2>Full-Text Index</h2>
                <p>The REST full-text search endpoint requires a MySQL full-text index.</p>
                <p>Status: <strong style="color: <?php echo $index_exists ? 'green' : 'red'; ?>;">
                    <?php echo $index_exists ? '✅ Created' : '❌ Not Created'; ?>
                </strong></p>
                <?php if (!$index_exists): ?>
                    <button type="button" id="create-fulltext-index-btn" class="button button-primary">
                        Create Full-Text Index
                    </button>
                <?php else: ?>
                    <p class="description">Full-text index is already available for search.</p>
                <?php endif; ?>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2>Test Full-Text Search Endpoint</h2>
                <p>Test the <code><?php echo RAG_SEARCH_ENDPOINT; ?></code> endpoint with query "FOAM" and limit 3.</p>
                <button type="button" id="test-search-btn" class="button button-primary">Test Full-Text Search</button>
                <div id="search-results" style="margin-top: 15px; display: none;">
                    <h3>Results:</h3>
                    <pre id="search-response" style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto;"></pre>
                </div>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2>Test Vector Search Endpoint</h2>
                <p>Test the <code><?php echo RAG_VECTOR_SEARCH_ENDPOINT; ?></code> endpoint with query "FOAM" and limit 3.</p>
                <button type="button" id="test-vector-search-btn" class="button button-primary">Test Vector Search</button>
                <div id="vector-search-results" style="margin-top: 15px; display: none;">
                    <h3>Results:</h3>
                    <pre id="vector-search-response" style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto;"></pre>
                </div>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2>Test Hybrid Search Endpoint</h2>
                <p>Test the <code><?php echo RAG_HYBRID_SEARCH_ENDPOINT; ?></code> endpoint with query "FOAM" and limit 3 (gets 3 from each method, combines and deduplicates).</p>
                <button type="button" id="test-hybrid-search-btn" class="button button-primary">Test Hybrid Search</button>
                <div id="hybrid-search-results" style="margin-top: 15px; display: none;">
                    <h3>Results:</h3>
                    <pre id="hybrid-search-response" style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; overflow-x: auto;"></pre>
                </div>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2>REST API Endpoints</h2>
                <h3>Full-Text Search</h3>
                <p>Search using MySQL full-text index (keyword matching):</p>
                <code><?php echo esc_url(rest_url(RAG_PLUGIN_NAMESPACE . '/' . RAG_SEARCH_ENDPOINT)); ?>?query=FOAM&limit=3</code>

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

            // Test Full-Text Search
            $('#test-search-btn').on('click', function() {
                var $btn = $(this);
                var $results = $('#search-results');
                var $response = $('#search-response');

                $btn.prop('disabled', true).text('Testing...');
                $results.hide();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_rag_search'
                    },
                    success: function(response) {
                        if (response.success) {
                            $response.text(JSON.stringify(response.data, null, 2));
                            $results.show();
                            showMessage('Full-text search test completed successfully!', 'success');
                        } else {
                            showMessage(response.data || 'Search failed', 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while testing the search.', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test Full-Text Search');
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

                $btn.prop('disabled', true).text('Testing...');
                $results.hide();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_rag_hybrid_search'
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
        });
        </script>
        <?php
    }

    /**
     * REST API endpoint: Search posts
     */
    public function rest_search_posts($request) {
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
        $results = $this->fulltext_search($query, $limit);

        if (empty($results)) {
            return array(
                'success' => true,
                'query' => $query,
                'method' => 'fulltext_search',
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
            'method' => 'fulltext_search',
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

        if (empty($query)) {
            return new WP_Error('invalid_query', 'Query parameter is required', array('status' => 400));
        }

        // Limit between 1 and 10 (per method, so total results will be up to 2x this)
        $limit = max(1, min(10, $limit));

        // Perform both searches
        $fulltext_results = array();
        $vector_results = array();

        // Try full-text search first
        $fulltext_request = new WP_REST_Request('GET', RAG_PLUGIN_NAMESPACE . '/' . RAG_SEARCH_ENDPOINT);
        $fulltext_request->set_param('query', $query);
        $fulltext_request->set_param('limit', $limit);
        $fulltext_response = $this->rest_search_posts($fulltext_request);

        if (!is_wp_error($fulltext_response) && isset($fulltext_response['results'])) {
            $fulltext_results = $fulltext_response['results'];
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
    private function fulltext_search($query, $limit = 3) {
        global $wpdb;

        // Escape the query for use in MATCH AGAINST
        $search_query = $wpdb->esc_like($query);

        $sql = $wpdb->prepare(
            "SELECT
                post_id,
                post_title,
                post_content,
                categories,
                tags,
                MATCH(post_title, post_content)
                AGAINST (%s IN NATURAL LANGUAGE MODE) as relevance_score
            FROM " . RAG_TABLE_NAME . "
            WHERE MATCH(post_title, post_content)
                AGAINST (%s IN NATURAL LANGUAGE MODE)
            ORDER BY relevance_score DESC
            LIMIT %d",
            $query,
            $query,
            $limit
        );

        return $wpdb->get_results($sql);
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
     * AJAX handler for testing full-text search
     */
    public function ajax_test_search() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $query = 'FOAM';
        $limit = 3;

        $request = new WP_REST_Request('GET', RAG_PLUGIN_NAMESPACE . '/' . RAG_SEARCH_ENDPOINT);
        $request->set_param('query', $query);
        $request->set_param('limit', $limit);

        $response = $this->rest_search_posts($request);

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

        $request = new WP_REST_Request('GET', RAG_HYBRID_NAMESPACE . '/' . RAG_HYBRID_SEARCH_ENDPOINT);
        $request->set_param('query', $query);
        $request->set_param('limit', $limit);

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
