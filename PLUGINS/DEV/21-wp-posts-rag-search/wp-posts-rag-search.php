<?php
/**
 * Plugin Name: ✅ 21 POSTS RAG SEARCH
 * Plugin URI: https://mydigitalagent.co.uk
 * Description: AI-powered search assistant using Full Text Search and Vector Search APIs with OpenAI
 * Version: 1.0.0
 * Author: Craig West
 * Author URI: https://mydigitalagent.co.uk
 * License: GPL v2 or later
 * Text Domain: rag-search-assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

class RAG_Search_Assistant {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_rag_search_query', array($this, 'handle_search_query'));
    }
    
    /**
     * Get the REST API base URL
     */
    private function get_api_base_url() {
        // Use rest_url() which automatically gets the correct URL
        return rest_url('posts-rag/v1/');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            '21 POSTS RAG SEARCH',
            '21 POSTS SEARCH',
            'edit_posts',
            'rag-search-assistant',
            array($this, 'render_admin_page'),
            'dashicons-search',
            4.2
        );
    }
    
    public function render_admin_page() {
        ?>
        <style>
        .rag-search-wrap {
            max-width: 1200px;
            margin: 20px 0;
        }
        .rag-search-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .rag-search-input-section {
            margin-bottom: 30px;
        }
        .rag-search-input-section label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 16px;
        }
        .rag-query-input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 4px;
        }
        .rag-query-input:focus {
            outline: none;
            border-color: #0073aa;
        }
        .rag-limit-input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 4px;
        }
        .rag-limit-input:focus {
            outline: none;
            border-color: #0073aa;
        }
        .rag-input-row {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }
        .rag-input-group {
            display: flex;
            flex-direction: column;
        }
        .rag-input-group.query {
            flex: 1;
        }
        .rag-input-group.limit {
            min-width: 150px;
        }
        #rag-search-btn {
            padding: 12px 30px;
            font-size: 16px;
            align-self: flex-end;
        }
        .rag-loading {
            text-align: center;
            padding: 20px;
            font-size: 16px;
            color: #666;
        }
        .rag-answer-box {
            background: #f0f6fc;
            border-left: 4px solid #0073aa;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .rag-answer-box h2 {
            margin-top: 0;
            color: #0073aa;
        }
        .rag-answer {
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            white-space: pre-wrap;
        }
        .rag-search-results h3 {
            margin-top: 30px;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
        }
        .rag-results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .rag-result-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            transition: box-shadow 0.3s ease;
        }
        .rag-result-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .rag-result-card h4 {
            margin-top: 0;
            margin-bottom: 12px;
            color: #0073aa;
            font-size: 18px;
        }
        .rag-result-meta {
            font-size: 13px;
            color: #666;
            margin-bottom: 12px;
            line-height: 1.8;
        }
        .rag-score-badge {
            display: inline-block;
            background: #0073aa;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 4px;
        }
        .rag-similarity-badge {
            display: inline-block;
            background: #46b450;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 4px;
        }
        .rag-result-excerpt {
            font-size: 14px;
            line-height: 1.6;
            color: #444;
        }
        .rag-context-section {
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .rag-context-section h2 {
            margin-top: 0;
            color: #333;
        }
        .rag-context {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
        }
        .rag-context pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            margin: 0;
            font-family: "Courier New", monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .rag-metadata {
            background: #fff9e6;
            border: 1px solid #f0e68c;
            border-radius: 6px;
            padding: 20px;
        }
        .rag-metadata h3 {
            margin-top: 0;
            color: #856404;
        }
        .rag-meta-info p {
            margin: 8px 0;
            font-size: 14px;
        }
        .rag-meta-info strong {
            color: #333;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        </style>
        
        <div class="wrap rag-search-wrap">
            <h1>RAG Search Assistant</h1>
            
            <div class="rag-search-container">
                <div class="rag-search-input-section">
                    <div class="rag-input-row">
                        <div class="rag-input-group query">
                            <label for="rag-query">Ask a question:</label>
                            <input type="text" id="rag-query" class="rag-query-input" value="What foam products do you have">
                        </div>
                        <div class="rag-input-group limit">
                            <label for="rag-limit">Results per API:</label>
                            <input type="number" id="rag-limit" class="rag-limit-input" value="2" min="1" max="20">
                        </div>
                        <button id="rag-search-btn" class="button button-primary">Search</button>
                    </div>
                </div>
                
                <div id="rag-loading" class="rag-loading" style="display: none;">
                    <span class="spinner is-active"></span> Searching...
                </div>
                
                <div id="rag-results" class="rag-results"></div>
                
                <div id="rag-context-section" class="rag-context-section" style="display: none;">
                    <h2>Retrieved Context</h2>
                    <div id="rag-context" class="rag-context"></div>
                </div>
                
                <div id="rag-metadata" class="rag-metadata" style="display: none;">
                    <h3>Search Metadata</h3>
                    <div id="rag-metadata-content"></div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('RAG Search initialized');
            
            // Handle Enter key in query input
            $('#rag-query').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#rag-search-btn').click();
                }
            });
            
            // Handle Enter key in limit input
            $('#rag-limit').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#rag-search-btn').click();
                }
            });
            
            $('#rag-search-btn').on('click', function() {
                console.log('Search button clicked');
                
                var query = $('#rag-query').val().trim();
                var limit = parseInt($('#rag-limit').val()) || 2;
                
                // Validate limit
                if (limit < 1) limit = 1;
                if (limit > 20) limit = 20;
                $('#rag-limit').val(limit);
                
                console.log('Query:', query);
                console.log('Limit:', limit);
                
                if (!query) {
                    alert('Please enter a search query');
                    return;
                }
                
                // Show loading, hide previous results
                $('#rag-loading').show();
                $('#rag-results').html('');
                $('#rag-context-section').hide();
                $('#rag-metadata').hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'rag_search_query',
                        query: query,
                        limit: limit
                    },
                    success: function(response) {
                        console.log('AJAX Response:', response);
                        $('#rag-loading').hide();
                        
                        if (response.success) {
                            displayResults(response.data);
                        } else {
                            var errorMessage = response.data && response.data.message ? 
                                response.data.message : 'An error occurred';
                            $('#rag-results').html('<div class="error">' + errorMessage + '</div>');
                            
                            // Show debug info if available
                            if (response.data && response.data.debug) {
                                console.log('Debug info:', response.data.debug);
                                displayDebugInfo(response.data.debug);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.log('XHR:', xhr);
                        $('#rag-loading').hide();
                        $('#rag-results').html('<div class="error">Network error: ' + error + '</div>');
                    }
                });
            });
            
            function displayResults(data) {
                var html = '';
                
                // Display answer
                if (data.answer) {
                    html += '<div class="rag-answer-box">';
                    html += '<h2>Answer</h2>';
                    html += '<div class="rag-answer">' + escapeHtml(data.answer) + '</div>';
                    html += '</div>';
                }
                
                // Display FTS results
                if (data.fts_results && data.fts_results.length > 0) {
                    html += '<div class="rag-search-results">';
                    html += '<h3>Full Text Search Results (Sorted by Score - Highest First)</h3>';
                    html += '<div class="rag-results-grid">';
                    
                    data.fts_results.forEach(function(result) {
                        html += '<div class="rag-result-card">';
                        html += '<h4>' + escapeHtml(result.post_title) + '</h4>';
                        html += '<div class="rag-result-meta">';
                        html += '<strong>ID:</strong> ' + result.post_id + '<br>';
                        html += '<strong>Categories:</strong> ' + escapeHtml(result.categories) + '<br>';
                        html += '<strong>Tags:</strong> ' + escapeHtml(result.tags) + '<br>';
                        if (result.score !== undefined) {
                            html += '<span class="rag-score-badge">Score: ' + result.score.toFixed(2) + '</span>';
                        } else {
                            html += '<span class="rag-score-badge">Score: N/A</span>';
                        }
                        html += '</div>';
                        html += '<div class="rag-result-excerpt">' + escapeHtml(result.excerpt) + '</div>';
                        html += '</div>';
                    });
                    
                    html += '</div></div>';
                }
                
                // Display Vector Search results
                if (data.vector_results && data.vector_results.length > 0) {
                    html += '<div class="rag-search-results">';
                    html += '<h3>Vector Search Results (Sorted by Similarity - Highest First)</h3>';
                    html += '<div class="rag-results-grid">';
                    
                    data.vector_results.forEach(function(result) {
                        html += '<div class="rag-result-card">';
                        html += '<h4>' + escapeHtml(result.post_title) + '</h4>';
                        html += '<div class="rag-result-meta">';
                        html += '<strong>ID:</strong> ' + result.post_id + '<br>';
                        html += '<strong>Categories:</strong> ' + escapeHtml(result.categories) + '<br>';
                        html += '<strong>Tags:</strong> ' + escapeHtml(result.tags) + '<br>';
                        if (result.similarity !== undefined) {
                            html += '<span class="rag-similarity-badge">Similarity: ' + result.similarity.toFixed(4) + '</span>';
                        } else {
                            html += '<span class="rag-similarity-badge">Similarity: N/A</span>';
                        }
                        html += '</div>';
                        html += '<div class="rag-result-excerpt">' + escapeHtml(result.excerpt) + '</div>';
                        html += '</div>';
                    });
                    
                    html += '</div></div>';
                }
                
                $('#rag-results').html(html);
                
                // Display context
                if (data.context) {
                    $('#rag-context').html('<pre>' + escapeHtml(data.context) + '</pre>');
                    $('#rag-context-section').show();
                }
                
                // Display metadata
                if (data.debug) {
                    displayDebugInfo(data.debug);
                }
            }
            
            function displayDebugInfo(debug) {
                var metaHtml = '<div class="rag-meta-info">';
                metaHtml += '<p><strong>Query:</strong> ' + escapeHtml(debug.query) + '</p>';
                metaHtml += '<p><strong>Limit:</strong> ' + debug.limit + '</p>';
                
                if (debug.fts_url) {
                    metaHtml += '<p><strong>FTS URL:</strong> ' + escapeHtml(debug.fts_url) + '</p>';
                }
                if (debug.fts_status_code) {
                    metaHtml += '<p><strong>FTS Status:</strong> ' + debug.fts_status_code + '</p>';
                }
                if (debug.fts_results_count !== undefined) {
                    metaHtml += '<p><strong>FTS Results:</strong> ' + debug.fts_results_count + '</p>';
                }
                
                if (debug.vector_url) {
                    metaHtml += '<p><strong>Vector URL:</strong> ' + escapeHtml(debug.vector_url) + '</p>';
                }
                if (debug.vector_status_code) {
                    metaHtml += '<p><strong>Vector Status:</strong> ' + debug.vector_status_code + '</p>';
                }
                if (debug.vector_results_count !== undefined) {
                    metaHtml += '<p><strong>Vector Results:</strong> ' + debug.vector_results_count + '</p>';
                }
                
                if (debug.fts_error) {
                    metaHtml += '<p><strong>FTS Error:</strong> <span style="color: red;">' + 
                        escapeHtml(debug.fts_error) + '</span></p>';
                }
                if (debug.vector_error) {
                    metaHtml += '<p><strong>Vector Error:</strong> <span style="color: red;">' + 
                        escapeHtml(debug.vector_error) + '</span></p>';
                }
                
                metaHtml += '</div>';
                $('#rag-metadata-content').html(metaHtml);
                $('#rag-metadata').show();
            }
            
            function escapeHtml(text) {
                if (typeof text !== 'string') return text;
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
        </script>
        <?php
    }
    
    public function handle_search_query() {
        $debug_info = array();
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 2;
        
        // Validate limit
        if ($limit < 1) $limit = 1;
        if ($limit > 20) $limit = 20;
        
        $debug_info['query'] = $query;
        $debug_info['limit'] = $limit;
        
        if (empty($query)) {
            wp_send_json_error(array(
                'message' => 'Query cannot be empty',
                'debug' => $debug_info
            ));
            return;
        }
        
        // Fetch from both APIs
        $fts_results = $this->fetch_fulltext_search($query, $limit, $debug_info);
        $vector_results = $this->fetch_vector_search($query, $limit, $debug_info);
        
        // Validate API responses
        if (!$fts_results || !isset($fts_results['results']) || !is_array($fts_results['results'])) {
            wp_send_json_error(array(
                'message' => 'Failed to fetch full text search results. API may be unavailable.',
                'debug' => $debug_info
            ));
            return;
        }
        
        if (!$vector_results || !isset($vector_results['results']) || !is_array($vector_results['results'])) {
            wp_send_json_error(array(
                'message' => 'Failed to fetch vector search results. API may be unavailable.',
                'debug' => $debug_info
            ));
            return;
        }
        
        // Sort results by score (highest first)
        $fts_results['results'] = $this->sort_by_score($fts_results['results']);
        // Limit to user's requested number after sorting (take top N best results)
        $fts_results['results'] = array_slice($fts_results['results'], 0, $limit);
        
        $vector_results['results'] = $this->sort_by_similarity($vector_results['results']);
        // Limit to user's requested number after sorting (take top N best results)
        $vector_results['results'] = array_slice($vector_results['results'], 0, $limit);
        
        // Extract post IDs with validation
        $fts_ids = array();
        foreach ($fts_results['results'] as $item) {
            if (isset($item['post_id'])) {
                $fts_ids[] = $item['post_id'];
            }
        }
        
        $vector_ids = array();
        foreach ($vector_results['results'] as $item) {
            if (isset($item['post_id'])) {
                $vector_ids[] = $item['post_id'];
            }
        }
        
        // Build context from all found posts
        $context = $this->build_context($fts_results['results'], $vector_results['results']);
        
        // Generate answer using OpenAI API
        $answer = $this->generate_answer($query, $context);
        
        wp_send_json_success(array(
            'query' => $query,
            'fts_ids' => $fts_ids,
            'vector_ids' => $vector_ids,
            'context' => $context,
            'answer' => $answer,
            'fts_results' => $fts_results['results'],
            'vector_results' => $vector_results['results'],
            'debug' => $debug_info
        ));
    }
    
    private function fetch_fulltext_search($query, $limit, &$debug_info) {
        // Always fetch MORE results (10) to ensure we get the best matches, then we'll filter to the limit later
        // The API might have its own ranking, so we fetch extra to ensure we don't miss good results
        $fetch_limit = max(10, $limit);
        $url = $this->get_api_base_url() . 'search?query=' . urlencode($query) . '&limit=' . $fetch_limit;
        
        $debug_info['fts_url'] = $url;
        
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            $debug_info['fts_error'] = $response->get_error_message();
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        $debug_info['fts_status_code'] = $status_code;
        $debug_info['fts_response_length'] = strlen($body);
        
        if ($status_code !== 200) {
            $debug_info['fts_error'] = 'Non-200 status code: ' . $status_code;
            $debug_info['fts_response_body'] = substr($body, 0, 500);
            return false;
        }
        
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $debug_info['fts_json_error'] = json_last_error_msg();
            $debug_info['fts_response_preview'] = substr($body, 0, 200);
            return false;
        }
        
        $debug_info['fts_success'] = true;
        $debug_info['fts_results_count'] = isset($decoded['results']) ? count($decoded['results']) : 0;
        
        // Store API scores before recalculation for debugging
        if (isset($decoded['results']) && is_array($decoded['results'])) {
            $api_scores = array();
            foreach ($decoded['results'] as $r) {
                if (isset($r['post_id'])) {
                    $api_scores[$r['post_id']] = array(
                        'title' => isset($r['post_title']) ? $r['post_title'] : 'N/A',
                        'api_score' => isset($r['score']) ? $r['score'] : 'N/A'
                    );
                }
            }
            $debug_info['fts_api_scores'] = $api_scores;
        }
        
        // Calculate scores if not provided by API
        if (isset($decoded['results']) && is_array($decoded['results'])) {
            $decoded['results'] = $this->calculate_fts_scores($decoded['results'], $query);
            
            // Store recalculated scores for debugging
            $recalc_scores = array();
            foreach ($decoded['results'] as $r) {
                if (isset($r['post_id'])) {
                    $recalc_scores[$r['post_id']] = array(
                        'title' => isset($r['post_title']) ? $r['post_title'] : 'N/A',
                        'new_score' => isset($r['score']) ? $r['score'] : 'N/A'
                    );
                }
            }
            $debug_info['fts_recalc_scores'] = $recalc_scores;
        }
        
        return $decoded;
    }
    
    private function fetch_vector_search($query, $limit, &$debug_info) {
        // Always fetch MORE results (10) to ensure we get the best matches, then we'll filter to the limit later
        $fetch_limit = max(10, $limit);
        $url = $this->get_api_base_url() . 'vector-search?query=' . urlencode($query) . '&limit=' . $fetch_limit;
        
        $debug_info['vector_url'] = $url;
        
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            $debug_info['vector_error'] = $response->get_error_message();
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        $debug_info['vector_status_code'] = $status_code;
        $debug_info['vector_response_length'] = strlen($body);
        
        if ($status_code !== 200) {
            $debug_info['vector_error'] = 'Non-200 status code: ' . $status_code;
            $debug_info['vector_response_body'] = substr($body, 0, 500);
            return false;
        }
        
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $debug_info['vector_json_error'] = json_last_error_msg();
            $debug_info['vector_response_preview'] = substr($body, 0, 200);
            return false;
        }
        
        $debug_info['vector_success'] = true;
        $debug_info['vector_results_count'] = isset($decoded['results']) ? count($decoded['results']) : 0;
        
        // Calculate similarity scores if not provided by API
        if (isset($decoded['results']) && is_array($decoded['results'])) {
            $decoded['results'] = $this->calculate_vector_scores($decoded['results'], $query);
        }
        
        return $decoded;
    }
    
    /**
     * Calculate relevance scores for Full Text Search results
     */
    private function calculate_fts_scores($results, $query) {
        if (empty($results)) {
            return $results;
        }
        
        // Normalize and tokenize query
        $query_terms = array_map('strtolower', array_filter(explode(' ', preg_replace('/[^\w\s]/', ' ', $query))));
        $query_lower = strtolower($query);
        
        foreach ($results as &$result) {
            // Always recalculate scores to ensure consistent ranking
            $score = 0;
            
            // Get text fields
            $title = isset($result['post_title']) ? strtolower($result['post_title']) : '';
            $excerpt = isset($result['excerpt']) ? strtolower($result['excerpt']) : '';
            $categories = isset($result['categories']) ? strtolower($result['categories']) : '';
            $tags = isset($result['tags']) ? strtolower($result['tags']) : '';
            
            foreach ($query_terms as $term) {
                if (strlen($term) < 2) continue; // Skip very short terms
                
                // Title matches (highest weight)
                $title_matches = substr_count($title, $term);
                $score += $title_matches * 10;
                
                // Categories matches
                $category_matches = substr_count($categories, $term);
                $score += $category_matches * 5;
                
                // Tags matches
                $tag_matches = substr_count($tags, $term);
                $score += $tag_matches * 5;
                
                // Excerpt/content matches (lower weight)
                $excerpt_matches = substr_count($excerpt, $term);
                $score += $excerpt_matches * 1;
            }
            
            // Exact title match bonus (OUTSIDE the loop so it's only added once)
            if (strpos($title, $query_lower) !== false) {
                $score += 20;
            }
            
            // Normalize score (0-100 range)
            $result['score'] = min(100, $score);
        }
        
        return $results;
    }
    
    /**
     * Calculate similarity scores for Vector Search results
     */
    private function calculate_vector_scores($results, $query) {
        if (empty($results)) {
            return $results;
        }
        
        // Normalize and tokenize query
        $query_terms = array_map('strtolower', array_filter(explode(' ', preg_replace('/[^\w\s]/', ' ', $query))));
        $query_length = count($query_terms);
        
        foreach ($results as &$result) {
            // Always recalculate similarity to ensure consistent ranking
            $score = 0;
            $matches = 0;
            
            // Get text fields
            $title = isset($result['post_title']) ? strtolower($result['post_title']) : '';
            $excerpt = isset($result['excerpt']) ? strtolower($result['excerpt']) : '';
            $categories = isset($result['categories']) ? strtolower($result['categories']) : '';
            $tags = isset($result['tags']) ? strtolower($result['tags']) : '';
            
            // Combine all text
            $all_text = $title . ' ' . $excerpt . ' ' . $categories . ' ' . $tags;
            $all_terms = array_unique(array_map('strtolower', array_filter(explode(' ', preg_replace('/[^\w\s]/', ' ', $all_text)))));
            
            // Calculate term overlap
            foreach ($query_terms as $term) {
                if (strlen($term) < 2) continue;
                
                foreach ($all_terms as $doc_term) {
                    // Exact match
                    if ($term === $doc_term) {
                        $matches++;
                        $score += 1.0;
                        break;
                    }
                    // Partial match (term is substring or vice versa)
                    if (strlen($term) > 3 && (strpos($doc_term, $term) !== false || strpos($term, $doc_term) !== false)) {
                        $matches += 0.5;
                        $score += 0.5;
                        break;
                    }
                }
            }
            
            // Calculate similarity as ratio of matched terms (0-1 scale)
            if ($query_length > 0) {
                $result['similarity'] = min(1.0, $matches / $query_length);
            } else {
                $result['similarity'] = 0.0;
            }
            
            // Boost for title matches
            foreach ($query_terms as $term) {
                if (strlen($term) > 2 && strpos($title, $term) !== false) {
                    $result['similarity'] = min(1.0, $result['similarity'] + 0.1);
                }
            }
        }
        
        return $results;
    }
    
    private function sort_by_score($results) {
        if (empty($results)) {
            return $results;
        }
        
        usort($results, function($a, $b) {
            $score_a = isset($a['score']) ? floatval($a['score']) : 0;
            $score_b = isset($b['score']) ? floatval($b['score']) : 0;
            
            // Sort descending (highest score first)
            if ($score_a == $score_b) {
                return 0;
            }
            return ($score_a > $score_b) ? -1 : 1;
        });
        
        return $results;
    }
    
    private function sort_by_similarity($results) {
        if (empty($results)) {
            return $results;
        }
        
        usort($results, function($a, $b) {
            $sim_a = isset($a['similarity']) ? floatval($a['similarity']) : 0;
            $sim_b = isset($b['similarity']) ? floatval($b['similarity']) : 0;
            
            // Sort descending (highest similarity first)
            if ($sim_a == $sim_b) {
                return 0;
            }
            return ($sim_a > $sim_b) ? -1 : 1;
        });
        
        return $results;
    }
    
    private function build_context($fts_results, $vector_results) {
        $context_parts = array();
        $seen_ids = array();
        
        // Prioritize FTS results
        foreach ($fts_results as $result) {
            if (!in_array($result['post_id'], $seen_ids)) {
                $context_parts[] = "Title: {$result['post_title']}\n" .
                                   "Categories: {$result['categories']}\n" .
                                   "Tags: {$result['tags']}\n" .
                                   "Content: {$result['excerpt']}\n";
                $seen_ids[] = $result['post_id'];
            }
        }
        
        // Add vector results
        foreach ($vector_results as $result) {
            if (!in_array($result['post_id'], $seen_ids)) {
                $context_parts[] = "Title: {$result['post_title']}\n" .
                                   "Categories: {$result['categories']}\n" .
                                   "Tags: {$result['tags']}\n" .
                                   "Content: {$result['excerpt']}\n";
                $seen_ids[] = $result['post_id'];
            }
        }
        
        return implode("\n---\n\n", $context_parts);
    }
    
    private function generate_answer($query, $context) {
        // Check if context is empty
        if (empty(trim($context))) {
            return "My RAG does not have the answer.";
        }
        
        // Use the OpenAI key already stored in the database
        $api_key = get_option('posts_rag_openai_key', '');
        
        if (empty($api_key)) {
            return "Please configure your OpenAI API key in the 20 CAPSTONE plugin settings.";
        }
        
        // Call OpenAI API
        $api_response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode(array(
                'model' => 'gpt-4o-mini',
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => "You are a helpful assistant that answers questions based on the provided context. If the context doesn't contain enough information to answer the question, respond with 'My RAG does not have the answer.'"
                    ),
                    array(
                        'role' => 'user',
                        'content' => "Context:\n{$context}\n\nQuestion: {$query}\n\nPlease provide a helpful answer based on the context above."
                    )
                ),
                'max_tokens' => 500,
                'temperature' => 0.7
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($api_response)) {
            return "Error generating answer: " . $api_response->get_error_message();
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($api_response), true);
        
        if (isset($response_body['choices'][0]['message']['content'])) {
            return $response_body['choices'][0]['message']['content'];
        }
        
        if (isset($response_body['error'])) {
            return "API Error: " . $response_body['error']['message'];
        }
        
        return "My RAG does not have the answer.";
    }
}

// Initialize the plugin
new RAG_Search_Assistant();
