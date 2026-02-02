<?php
/**
 * Plugin Name: ✅ 31 WP RERANKING
 * Description: Reranks hybrid search results from FTS and Vector responses.
 * Version: 1.0.0
 * Author: Craig West
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Reranking_Plugin {
    // Admin page slug for the reranker menu.
    private $menu_slug = 'wp-reranking';

    public function __construct() {
        // Register REST endpoints.
        add_action('rest_api_init', array($this, 'register_routes'));
        // Register admin menu.
        add_action('admin_menu', array($this, 'register_admin_menu'));
    }

    public function register_routes() {
        // REST endpoint to rerank incoming search results.
        register_rest_route('reranker/v1', '/reranked', array(
            'methods' => array('GET', 'POST'),
            'callback' => array($this, 'handle_rerank_request'),
            'permission_callback' => '__return_true',
        ));
    }

    public function register_admin_menu() {
        // Admin menu item for the reranker test page.
        add_menu_page(
            'Reranker',
            '31 RERANKER',
            'manage_options',
            $this->menu_slug,
            array($this, 'render_admin_page'),
            'dashicons-filter',
           4.66
        );
    }

    /**
     * Helper logger that writes to PHP error_log when WP_DEBUG is enabled.
     */
    private function log_debug($message, $context = null) {
        if (!defined('WP_DEBUG') || WP_DEBUG !== true) {
            return;
        }

        if ($context !== null) {
            $message .= ' ' . wp_json_encode($context);
        }

        error_log('[WP Reranking] ' . $message);
    }

    public function handle_rerank_request(WP_REST_Request $request) {
        $query = $request->get_param('query');
        $limit = $request->get_param('limit');
        $payload = $request->get_json_params();

        $this->log_debug('Rerank request received.', array(
            'query' => $query,
            'limit' => $limit,
            'has_payload' => !empty($payload)
        ));

        if (!empty($payload)) {
            // Accept fulltext_search/vector_search wrappers directly.
            $fulltext = isset($payload['fulltext_search']) ? $payload['fulltext_search'] : null;
            $vector = isset($payload['vector_search']) ? $payload['vector_search'] : null;

            // Accept raw arrays from alternate naming conventions.
            if (!$fulltext && isset($payload['fts_results']) && is_array($payload['fts_results'])) {
                $fulltext = array('results' => $payload['fts_results']);
            }

            if (!$vector && isset($payload['vector_results']) && is_array($payload['vector_results'])) {
                $vector = array('results' => $payload['vector_results']);
            }

            // Accept hybrid search combined list and split it by method.
            if (!$fulltext && !$vector && isset($payload['results']) && is_array($payload['results'])) {
                $split = $this->split_hybrid_results($payload['results']);
                $fulltext = $split['fulltext'];
                $vector = $split['vector'];
            }

            $query = isset($payload['query']) ? $payload['query'] : $query;

            $this->log_debug('Payload parsing complete.', array(
                'fulltext_found' => !empty($fulltext),
                'vector_found' => !empty($vector)
            ));
        } else {
            $fulltext = null;
            $vector = null;
        }

        $limit = $limit ? absint($limit) : 6;

        if (!$fulltext && !$vector) {
            if (empty($query)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Missing query or payload.',
                ), 400);
            }
            $search_payloads = $this->fetch_search_payloads($query, $limit);

            if (isset($search_payloads['error'])) {
                $this->log_debug('Failed to fetch search payloads.', $search_payloads['error']);
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => $search_payloads['error'],
                ), 500);
            }

            $fulltext = $search_payloads['fulltext'];
            $vector = $search_payloads['vector'];
        }

        $this->log_debug('Input payloads ready.', array(
            'fulltext_count' => isset($fulltext['results']) ? count($fulltext['results']) : 0,
            'vector_count' => isset($vector['results']) ? count($vector['results']) : 0
        ));

        $reranked = $this->rerank_results($fulltext, $vector, false, $limit, 6, $query);

        $this->log_debug('Returning reranked response.', array(
            'result_count' => count($reranked)
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'query' => $query,
            'method' => 'reranking',
            'results' => $reranked,
            'count' => count($reranked),
        ));
    }

    /**
     * Combine and rerank FTS + vector results.
     */
    private function rerank_results($fulltext, $vector, $explain = false, $limit = null, $per_method_limit = 6, $query = '') {
        $items = array();
        $max_relevance = 0;
        $max_similarity = 0;
        $steps = array();
        $limit = $limit ? absint($limit) : null;
        $per_method_limit = $per_method_limit ? absint($per_method_limit) : 6;
        $per_method_limit = max(1, $per_method_limit);
        $query = is_string($query) ? trim($query) : '';

        $this->log_debug('Starting rerank.', array(
            'fulltext_has_results' => is_array($fulltext) && isset($fulltext['results']),
            'vector_has_results' => is_array($vector) && isset($vector['results'])
        ));

        $fulltext_results = (is_array($fulltext) && isset($fulltext['results']) && is_array($fulltext['results']))
            ? $fulltext['results']
            : array();
        $vector_results = (is_array($vector) && isset($vector['results']) && is_array($vector['results']))
            ? $vector['results']
            : array();

        if ($explain) {
            $steps[] = array(
                'step' => 'Input Data',
                'description' => 'Received fulltext and vector search results.',
                'fulltext_results' => $fulltext_results,
                'vector_results' => $vector_results
            );
        }

        if ($query !== '') {
            $fulltext_before_filter = count($fulltext_results);
            $vector_before_filter = count($vector_results);

            $fulltext_results = array_values(array_filter($fulltext_results, function ($item) use ($query) {
                return $this->item_matches_query($item, $query);
            }));

            $vector_results = array_values(array_filter($vector_results, function ($item) use ($query) {
                return $this->item_matches_query($item, $query);
            }));

            $this->log_debug('Query filter applied.', array(
                'query' => $query,
                'fulltext_before' => $fulltext_before_filter,
                'fulltext_after' => count($fulltext_results),
                'vector_before' => $vector_before_filter,
                'vector_after' => count($vector_results)
            ));

            if ($explain) {
                $steps[] = array(
                    'step' => 'Query Filter',
                'description' => 'Removed items that do not include the query text in title or content.',
                    'query' => $query,
                    'fulltext_results' => $fulltext_results,
                    'vector_results' => $vector_results
                );
            }
        }

        $this->log_debug('Rerank input counts.', array(
            'fulltext_count' => count($fulltext_results),
            'vector_count' => count($vector_results),
            'per_method_limit' => $per_method_limit
        ));

        // Enforce top N per method before normalization/merge.
        if (!empty($fulltext_results)) {
            usort($fulltext_results, function ($a, $b) {
                $a_score = isset($a['relevance_score']) ? $a['relevance_score'] : 0;
                $b_score = isset($b['relevance_score']) ? $b['relevance_score'] : 0;
                return $b_score <=> $a_score;
            });
            $fulltext_results = array_slice($fulltext_results, 0, $per_method_limit);
        }

        if (!empty($vector_results)) {
            usort($vector_results, function ($a, $b) {
                $a_score = isset($a['similarity_score']) ? $a['similarity_score'] : 0;
                $b_score = isset($b['similarity_score']) ? $b['similarity_score'] : 0;
                return $b_score <=> $a_score;
            });
            $vector_results = array_slice($vector_results, 0, $per_method_limit);
        }

        $this->log_debug('Rerank trimmed counts.', array(
            'fulltext_count' => count($fulltext_results),
            'vector_count' => count($vector_results)
        ));

        if ($explain) {
            $steps[] = array(
                'step' => 'Trimmed Input Results',
                'description' => 'Sorted by score and trimmed to per-method limit before normalization.',
                'fulltext_results' => $fulltext_results,
                'vector_results' => $vector_results,
                'per_method_limit' => $per_method_limit
            );
        }

        // Capture the max relevance score to normalize later.
        if (!empty($fulltext_results)) {
            foreach ($fulltext_results as $item) {
                if (isset($item['relevance_score']) && $item['relevance_score'] > $max_relevance) {
                    $max_relevance = $item['relevance_score'];
                }
            }
        }

        // Capture the max similarity score to normalize later.
        if (!empty($vector_results)) {
            foreach ($vector_results as $item) {
                if (isset($item['similarity_score']) && $item['similarity_score'] > $max_similarity) {
                    $max_similarity = $item['similarity_score'];
                }
            }
        }

        $max_relevance = $max_relevance > 0 ? $max_relevance : 1;
        $max_similarity = $max_similarity > 0 ? $max_similarity : 1;

        $this->log_debug('Normalization max values computed.', array(
            'max_relevance' => $max_relevance,
            'max_similarity' => $max_similarity
        ));

        if ($explain) {
            $steps[] = array(
                'step' => 'Max Scores Calculation',
                'description' => 'Found the maximum relevance score from fulltext results and maximum similarity score from vector results for normalization.',
                'max_relevance' => $max_relevance,
                'max_similarity' => $max_similarity
            );
        }

        // Seed items from fulltext results.
        if (!empty($fulltext_results)) {
            foreach ($fulltext_results as $item) {
                $post_id = isset($item['post_id']) ? $item['post_id'] : null;
                if (!$post_id) {
                    continue;
                }
                $items[$post_id] = array_merge($item, array(
                    'relevance_score' => isset($item['relevance_score']) ? $item['relevance_score'] : 0,
                    'similarity_score' => 0,
                ));
            }
        }

        // Merge vector scores into the combined list.
        if (!empty($vector_results)) {
            foreach ($vector_results as $item) {
                $post_id = isset($item['post_id']) ? $item['post_id'] : null;
                if (!$post_id) {
                    continue;
                }
                if (!isset($items[$post_id])) {
                    $items[$post_id] = array_merge($item, array(
                        'relevance_score' => 0,
                        'similarity_score' => isset($item['similarity_score']) ? $item['similarity_score'] : 0,
                    ));
                } else {
                    $items[$post_id]['similarity_score'] = isset($item['similarity_score']) ? $item['similarity_score'] : 0;
                }
            }
        }

        if ($explain) {
            $steps[] = array(
                'step' => 'Merge Results',
                'description' => 'Combined fulltext and vector results by post_id, merging scores where both exist.',
                'merged_items' => $items
            );
        }

        // Compute the combined score for ordering.
        foreach ($items as $post_id => $item) {
            $normalized_relevance = isset($item['relevance_score']) ? ($item['relevance_score'] / $max_relevance) : 0;
            $normalized_similarity = isset($item['similarity_score']) ? ($item['similarity_score'] / $max_similarity) : 0;
            $items[$post_id]['combined_score'] = $normalized_relevance + $normalized_similarity;
        }

        if ($explain) {
            $normalized_items = array();
            foreach ($items as $post_id => $item) {
                $normalized_relevance = isset($item['relevance_score']) ? ($item['relevance_score'] / $max_relevance) : 0;
                $normalized_similarity = isset($item['similarity_score']) ? ($item['similarity_score'] / $max_similarity) : 0;
                $normalized_items[$post_id] = $item;
                $normalized_items[$post_id]['normalized_relevance'] = number_format($item['relevance_score'], 4) . ' / ' . number_format($max_relevance, 4) . ' = ' . number_format($normalized_relevance, 4);
                $normalized_items[$post_id]['normalized_similarity'] = number_format($item['similarity_score'], 4) . ' / ' . number_format($max_similarity, 4) . ' = ' . number_format($normalized_similarity, 4);
                $normalized_items[$post_id]['combined_score'] = number_format($normalized_relevance, 4) . ' + ' . number_format($normalized_similarity, 4) . ' = ' . number_format($item['combined_score'], 4);
            }
            $steps[] = array(
                'step' => 'Normalization and Combined Score',
                'description' => 'Normalized relevance and similarity scores by dividing by their respective max values, then computed combined score as sum of normalized relevance + normalized similarity.',
                'normalized_items' => $normalized_items
            );
        }

        $this->log_debug('Rerank normalization summary.', array(
            'max_relevance' => $max_relevance,
            'max_similarity' => $max_similarity,
            'items_count' => count($items)
        ));

        $items = array_values($items);
        usort($items, function ($a, $b) {
            if ($a['combined_score'] === $b['combined_score']) {
                return 0;
            }
            return ($a['combined_score'] > $b['combined_score']) ? -1 : 1;
        });

        if ($explain) {
            $steps[] = array(
                'step' => 'Sorting',
                'description' => 'Sorted results by combined score in descending order.',
                'sorted_items' => $items
            );
        }

        if ($limit) {
            $items = array_slice($items, 0, $limit);

            if ($explain) {
                $steps[] = array(
                    'step' => 'Final Limit',
                    'description' => 'Applied output limit after sorting by combined score.',
                    'limit' => $limit,
                    'limited_results' => $items
                );
            }

            $this->log_debug('Rerank output limited.', array(
                'limit' => $limit,
                'result_count' => count($items)
            ));
        }

        $position = 1;
        foreach ($items as $index => $item) {
            $items[$index]['position'] = $position;
            unset($items[$index]['combined_score']);
            $position++;
        }

        if ($explain) {
            $steps[] = array(
                'step' => 'Final Positions',
                'description' => 'Assigned positions based on sorted order and removed temporary combined_score.',
                'final_results' => $items
            );
        }

        $this->log_debug('Rerank complete.', array('result_count' => count($items)));

        if ($explain) {
            return array('results' => $items, 'steps' => $steps);
        }

        return $items;
    }

    /**
     * Fetch fulltext + vector payloads from the local REST endpoints.
     */
    private function fetch_search_payloads($query, $limit) {
        $limit = max(1, min(10, $limit));

        $fulltext_url = add_query_arg(
            array(
                'query' => $query,
                'limit' => $limit,
            ),
            rest_url('search/v1/search')
        );

        $vector_url = add_query_arg(
            array(
                'query' => $query,
                'limit' => $limit,
            ),
            rest_url('search/v1/vector-search')
        );

        $this->log_debug('Fetching search payloads.', array(
            'fulltext_url' => $fulltext_url,
            'vector_url' => $vector_url
        ));

        $fulltext_response = wp_remote_get($fulltext_url, array('timeout' => 20));
        if (is_wp_error($fulltext_response)) {
            return array('error' => $fulltext_response->get_error_message());
        }

        $vector_response = wp_remote_get($vector_url, array('timeout' => 20));
        if (is_wp_error($vector_response)) {
            return array('error' => $vector_response->get_error_message());
        }

        $fulltext_body = wp_remote_retrieve_body($fulltext_response);
        $vector_body = wp_remote_retrieve_body($vector_response);

        $fulltext_data = json_decode($fulltext_body, true);
        $vector_data = json_decode($vector_body, true);

        if (!is_array($fulltext_data) || !is_array($vector_data)) {
            return array('error' => 'Invalid response from search endpoints.');
        }

        $this->log_debug('Search payloads fetched.', array(
            'fulltext_count' => isset($fulltext_data['results']) ? count($fulltext_data['results']) : 0,
            'vector_count' => isset($vector_data['results']) ? count($vector_data['results']) : 0
        ));

        return array(
            'fulltext' => $fulltext_data,
            'vector' => $vector_data,
        );
    }

    /**
     * Check if a result item contains the query text in key fields.
     */
    private function item_matches_query($item, $query) {
        if ($query === '') {
            return true;
        }

        if (!is_array($item)) {
            return false;
        }

        $title = !empty($item['post_title']) ? mb_strtolower($item['post_title']) : '';
        $content = !empty($item['content']) ? mb_strtolower($item['content']) : '';
        $needle = mb_strtolower($query);

        return ($title !== '' && strpos($title, $needle) !== false)
            || ($content !== '' && strpos($content, $needle) !== false);
    }

    /**
     * Split a hybrid payload list into synthetic fulltext/vector structures.
     */
    private function split_hybrid_results($results) {
        $fulltext_results = array();
        $vector_results = array();

        foreach ($results as $item) {
            $method = isset($item['search_method']) ? $item['search_method'] : null;

            if ($method === 'fulltext') {
                $fulltext_results[] = $item;
                continue;
            }

            if ($method === 'vector') {
                $vector_results[] = $item;
                continue;
            }

            if (isset($item['relevance_score']) && !isset($item['similarity_score'])) {
                $fulltext_results[] = $item;
                continue;
            }

            if (isset($item['similarity_score']) && !isset($item['relevance_score'])) {
                $vector_results[] = $item;
                continue;
            }

            $fulltext_results[] = $item;
            $vector_results[] = $item;
        }

        $this->log_debug('Hybrid results split.', array(
            'fulltext_count' => count($fulltext_results),
            'vector_count' => count($vector_results)
        ));

        return array(
            'fulltext' => array('results' => $fulltext_results),
            'vector' => array('results' => $vector_results),
        );
    }

    public function render_admin_page() {
        $query = isset($_GET['rerank_query']) ? sanitize_text_field($_GET['rerank_query']) : 'Foam products';
        $limit = isset($_GET['rerank_limit']) ? intval($_GET['rerank_limit']) : 6;
        $output = null;
        $error = null;

        if (isset($_GET['rerank_submit'])) {
            $fulltext_url = add_query_arg(
                array(
                    'query' => $query,
                    'limit' => $limit,
                ),
                home_url('/wp-json/search/v1/search')
            );

            $vector_url = add_query_arg(
                array(
                    'query' => $query,
                    'limit' => $limit,
                ),
                home_url('/wp-json/search/v1/vector-search')
            );

            $this->log_debug('Admin test requesting separate search endpoints.', array(
                'fulltext_url' => $fulltext_url,
                'vector_url' => $vector_url
            ));

            $fulltext_response = wp_remote_get($fulltext_url, array('timeout' => 20));
            if (is_wp_error($fulltext_response)) {
                $error = $fulltext_response->get_error_message();
                $this->log_debug('Admin fulltext request failed.', array('error' => $error));
                $fulltext_response = null;
            }

            $vector_response = wp_remote_get($vector_url, array('timeout' => 20));
            if (is_wp_error($vector_response)) {
                $error = $vector_response->get_error_message();
                $this->log_debug('Admin vector request failed.', array('error' => $error));
                $vector_response = null;
            }

            if ($fulltext_response && $vector_response) {
                $fulltext_body = wp_remote_retrieve_body($fulltext_response);
                $vector_body = wp_remote_retrieve_body($vector_response);

                $fulltext_data = json_decode($fulltext_body, true);
                $vector_data = json_decode($vector_body, true);

                $this->log_debug('Admin test responses received.', array(
                    'fulltext_decoded' => is_array($fulltext_data),
                    'vector_decoded' => is_array($vector_data)
                ));

                if (is_array($fulltext_data) && is_array($vector_data)) {
                    $fulltext = $fulltext_data;
                    $vector = $vector_data;

                    $rerank_result = $this->rerank_results($fulltext, $vector, true, $limit, 6, $query);
                    if (is_array($rerank_result) && isset($rerank_result['results'])) {
                        $output = array(
                            'success' => true,
                            'query' => $query,
                            'method' => 'reranking',
                            'results' => $rerank_result['results'],
                            'steps' => $rerank_result['steps'],
                            'count' => isset($fulltext_data['count']) ? $fulltext_data['count'] : null,
                            'fulltext_count' => isset($fulltext_data['count']) ? $fulltext_data['count'] : null,
                            'vector_count' => isset($vector_data['count']) ? $vector_data['count'] : null,
                        );
                    } else {
                        $output = array(
                            'success' => true,
                            'query' => $query,
                            'method' => 'reranking',
                            'results' => $rerank_result,
                            'count' => isset($fulltext_data['count']) ? $fulltext_data['count'] : null,
                        );
                    }
                } else {
                    $error = 'Invalid response from search endpoints.';
                }
            } elseif (!$error) {
                $error = 'Failed to fetch one or more search endpoints.';
            }
        }

        ?>
        <div class="wrap">
            <h1>Reranker Test Page</h1>
            <p>Fetch hybrid search results from <code><?php echo esc_html(home_url('/wp-json/search/v1/hybrid-search')); ?></code> and rerank them.</p>
            <!-- Simple admin-side console log for quick debugging -->
            <script>
                console.log('WP Reranking admin page loaded.');
            </script>
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($this->menu_slug); ?>" />
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="rerank_query">Query</label></th>
                        <td><input type="text" class="regular-text" id="rerank_query" name="rerank_query" value="<?php echo esc_attr($query); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rerank_limit">Limit</label></th>
                        <td><input type="number" id="rerank_limit" name="rerank_limit" value="<?php echo esc_attr($limit); ?>" min="1" max="50" /></td>
                    </tr>
                </table>
                <?php submit_button('Run Rerank', 'primary', 'rerank_submit'); ?>
            </form>

            <?php if ($error): ?>
                <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>

            <?php if ($output): ?>
                <h2>Reranking Calculation Steps</h2>
                <?php if (isset($output['steps'])): ?>
                    <?php foreach ($output['steps'] as $step): ?>
                        <h3><?php echo esc_html($step['step']); ?></h3>
                        <p><?php echo esc_html($step['description']); ?></p>
                        <?php if (isset($step['fulltext_results'])): ?>
                            <h4>Fulltext Results:</h4>
                            <pre><?php echo esc_html(json_encode($step['fulltext_results'], JSON_PRETTY_PRINT)); ?></pre>
                        <?php endif; ?>
                        <?php if (isset($step['vector_results'])): ?>
                            <h4>Vector Results:</h4>
                            <pre><?php echo esc_html(json_encode($step['vector_results'], JSON_PRETTY_PRINT)); ?></pre>
                        <?php endif; ?>
                        <?php if (isset($step['max_relevance'])): ?>
                            <p><strong>Max Relevance:</strong> <?php echo esc_html($step['max_relevance']); ?></p>
                            <p><strong>Max Similarity:</strong> <?php echo esc_html($step['max_similarity']); ?></p>
                        <?php endif; ?>
                        <?php if (isset($step['merged_items'])): ?>
                            <h4>Merged Items:</h4>
                            <pre><?php echo esc_html(json_encode($step['merged_items'], JSON_PRETTY_PRINT)); ?></pre>
                        <?php endif; ?>
                        <?php if (isset($step['normalized_items'])): ?>
                            <h4>Normalized Items with Combined Scores:</h4>
                            <pre><?php echo esc_html(json_encode($step['normalized_items'], JSON_PRETTY_PRINT)); ?></pre>
                        <?php endif; ?>
                        <?php if (isset($step['sorted_items'])): ?>
                            <h4>Sorted Items:</h4>
                            <pre><?php echo esc_html(json_encode($step['sorted_items'], JSON_PRETTY_PRINT)); ?></pre>
                        <?php endif; ?>
                        <?php if (isset($step['final_results'])): ?>
                            <h4>Final Results with Positions:</h4>
                            <pre><?php echo esc_html(json_encode($step['final_results'], JSON_PRETTY_PRINT)); ?></pre>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <h2>Reranked Response</h2>
                    <pre><?php echo esc_html(json_encode($output, JSON_PRETTY_PRINT)); ?></pre>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}

new WP_Reranking_Plugin();
