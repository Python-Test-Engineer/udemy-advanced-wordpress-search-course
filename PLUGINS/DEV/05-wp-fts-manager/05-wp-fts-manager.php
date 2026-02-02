<?php
/**
 * Plugin Name: ✅ 05 FTS MANAGER
 * Plugin URI: https://example.com/fts-manager
 * Description: Manage MySQL Full Text Search indexes and test queries with relevance rankings
 * Version: 1.0.1
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: fts-manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FTS_Manager {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'products';
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_fts_create_index', array($this, 'ajax_create_index'));
        add_action('wp_ajax_fts_delete_index', array($this, 'ajax_delete_index'));
        add_action('wp_ajax_fts_run_query', array($this, 'ajax_run_query'));
        add_action('wp_ajax_fts_get_indexes', array($this, 'ajax_get_indexes'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Full Text Search', 'fts-manager'),
            '05 FTS MANAGER',
            'manage_options',
            'fts-manager',
            array($this, 'render_admin_page'),
            'dashicons-search',
            3.5
        );
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap fts-manager-wrap">
            <h1><?php echo esc_html__('Full Text Search Manager', 'fts-manager'); ?></h1>
            
            <div class="fts-container">
                <!-- Index Management Section -->
                <div class="fts-card">
                    <h2><?php echo esc_html__('Index Management', 'fts-manager'); ?></h2>
                    
                    <div class="fts-current-indexes">
                        <h3><?php echo esc_html__('Current Indexes', 'fts-manager'); ?></h3>
                        <button class="button" id="refresh-indexes"><?php echo esc_html__('Refresh index list', 'fts-manager'); ?></button>
                        <div id="indexes-list"></div>
                    </div>
                    
                    <div class="fts-create-index">
                        <h3><?php echo esc_html__('Create New Index', 'fts-manager'); ?></h3>
                        <form id="create-index-form">
                            <table class="form-table">
                                <tr>
                                    <td colspan="2">
                                        <label for="index-name"><?php echo esc_html__('Index Name', 'fts-manager'); ?></label><br>
                                        <input type="text" id="index-name" name="index_name" size="45" placeholder="ft_product_search" required>
                                        <p class="description"><?php echo esc_html__('Enter a unique name for the index (e.g., ft_product_name)', 'fts-manager'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <label><?php echo esc_html__('Columns', 'fts-manager'); ?></label><br>
                                        <label><input type="checkbox" name="columns[]" value="product_name"> product_name</label><br>
                                        <label><input type="checkbox" name="columns[]" value="product_short_description"> product_short_description</label><br>
                                        <label><input type="checkbox" name="columns[]" value="expanded_description"> expanded_description</label>
                                        <p class="description"><?php echo esc_html__('Select one or more columns to include in the index', 'fts-manager'); ?></p>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <button type="submit" class="button button-primary"><?php echo esc_html__('Create Index', 'fts-manager'); ?></button>
                            </p>
                        </form>
                    </div>
                </div>
                
                <!-- Query Testing Section -->
                <div class="fts-card">
                    <h2><?php echo esc_html__('Test Search Queries', 'fts-manager'); ?></h2>
                    
                    <form id="query-test-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="search-query"><?php echo esc_html__('Search Query', 'fts-manager'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="search-query" name="search_query" class="large-text" value="Smart" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="search-mode"><?php echo esc_html__('Search Mode', 'fts-manager'); ?></label>
                                </th>
                                <td>
                                    <select id="search-mode" name="search_mode">
                                        <option value="natural"><?php echo esc_html__('Natural Language', 'fts-manager'); ?></option>
                                        <option value="boolean"><?php echo esc_html__('Boolean Mode', 'fts-manager'); ?></option>
                                        <option value="expansion"><?php echo esc_html__('Query Expansion', 'fts-manager'); ?></option>
                                    </select>
                                    <p class="description">
                                        <strong>Natural:</strong> Standard search with relevance ranking<br>
                                        <strong>Boolean:</strong> Use operators: +required -excluded "phrase" *wildcard<br>
                                        +wireless -smart<br>
                                        >budget means optional but boost relevance<br>
                                        <strong>Examples:</strong><br>

+camera 4K
+(speaker audio) -bluetooth
+"noise cancelling"
port*
+audio >wireless -smart
+desk +(standing adjustable)
+camera -smart -security -home
+"action camera" +waterproof
>premium <br>
                                        <strong>Expansion:</strong> Automatically finds related terms
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="result-limit"><?php echo esc_html__('Result Limit', 'fts-manager'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="result-limit" name="limit" value="10" min="1" max="100">
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php echo esc_html__('Run Query', 'fts-manager'); ?></button>
                        </p>
                    </form>
                    
                    <div id="query-results"></div>
                </div>
            </div>
            
            <div id="fts-message"></div>
        </div>
        
        <style>
            .fts-manager-wrap {
                margin: 20px 20px 20px 0;
            }
            .fts-container {
                display: grid;
                grid-template-columns: 420px 1fr;
                gap: 20px;
                margin-top: 20px;
            }
            .fts-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                padding: 20px;
            }
            .fts-card h2 {
                margin-top: 0;
                border-bottom: 1px solid #ddd;
                padding-bottom: 10px;
            }
            .fts-card h3 {
                margin-top: 20px;
                margin-bottom: 10px;
            }
            #indexes-list {
                margin-top: 15px;
            }
            .index-item {
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 3px;
                padding: 12px;
                margin-bottom: 10px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .index-item-name {
                font-weight: 600;
                color: #2271b1;
            }
            .index-item-columns {
                font-size: 12px;
                color: #666;
                margin-top: 4px;
            }
            .results-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            .results-table th,
            .results-table td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
            }
            .results-table th {
                background: #f0f0f1;
                font-weight: 600;
            }
            .results-table tr:hover {
                background: #f9f9f9;
            }
            .relevance-score {
                font-weight: 600;
                color: #2271b1;
            }
            .relevance-bar {
                height: 8px;
                background: #e0e0e0;
                border-radius: 4px;
                overflow: hidden;
                margin-top: 4px;
            }
            .relevance-bar-fill {
                height: 100%;
                background: linear-gradient(90deg, #2271b1, #72aee6);
                transition: width 0.3s ease;
            }
            #fts-message {
                position: fixed;
                top: 32px;
                right: 20px;
                max-width: 400px;
                z-index: 9999;
            }
            .fts-notice {
                padding: 12px;
                border-left: 4px solid;
                background: #fff;
                box-shadow: 0 1px 4px rgba(0,0,0,0.15);
                margin-bottom: 10px;
            }
            .fts-notice.success {
                border-left-color: #00a32a;
            }
            .fts-notice.error {
                border-left-color: #d63638;
            }
            .fts-notice.info {
                border-left-color: #2271b1;
            }
            .no-results {
                padding: 20px;
                text-align: center;
                color: #666;
                background: #f9f9f9;
                border-radius: 3px;
                margin-top: 20px;
            }
            @media (max-width: 1280px) {
                .fts-container {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            
            // Debug logging
            console.log('FTS Manager JavaScript loaded');
            console.log('ajaxurl:', ajaxurl);
            
            function showMessage(message, type) {
                type = type || 'success';
                console.log('Message:', message, 'Type:', type);
                const messageDiv = $('<div class="fts-notice ' + type + '">' + message + '</div>');
                $('#fts-message').append(messageDiv);
                setTimeout(function() {
                    messageDiv.fadeOut(function() {
                        $(this).remove();
                    });
                }, 4000);
            }
            
            function loadIndexes() {
                console.log('Loading indexes...');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fts_get_indexes'
                    },
                    success: function(response) {
                        console.log('Get indexes response:', response);
                        if (response.success) {
                            displayIndexes(response.data);
                        } else {
                            showMessage(response.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to load indexes:', error);
                        showMessage('Failed to load indexes: ' + error, 'error');
                    }
                });
            }
            
            function displayIndexes(indexes) {
                console.log('Displaying indexes:', indexes);
                const container = $('#indexes-list');
                
                container.empty();
                
                if (indexes.length === 0) {
                    container.html('<p class="no-results"><strong>⚠️ No full-text indexes found.</strong><br>Create one below to enable search functionality.</p>');
                    container.html('<p class="no-results"><strong>⚠️ No full-text indexes found.</strong><br>Create one below to enable search functionality.</p>');
                    // Show warning in query section
                    $('#query-results').html('<p class="no-results" style="background: #fff3cd; border-left: 4px solid #ffc107;"><strong>⚠️ No FTS Index Available</strong><br>Please create a full-text search index in the Index Management section before running queries.</p>');
                    return;
                }
                
                // Clear the warning if indexes exist
                if ($('#query-results p.no-results').text().includes('No FTS Index')) {
                    $('#query-results').empty();
                }
                
                indexes.forEach(function(index) {
                    const columns = index.columns.join(', ');
                    const indexHtml = '<div class="index-item">' +
                        '<div>' +
                        '<div class="index-item-name">' + index.name + '</div>' +
                        '<div class="index-item-columns">Columns: ' + columns + '</div>' +
                        '</div>' +
                        '<button class="button button-small delete-index" data-index="' + index.name + '">Delete</button>' +
                        '</div>';
                    container.append(indexHtml);
                });
            }
            
            // Load indexes on page load
            loadIndexes();
            
            // Refresh indexes
            $('#refresh-indexes').on('click', function() {
                console.log('Refresh button clicked');
                loadIndexes();
                showMessage('Indexes refreshed', 'info');
            });
            
            // Create index
            $('#create-index-form').on('submit', function(e) {
                e.preventDefault();
                console.log('Create index form submitted');
                
                const indexName = $('#index-name').val();
                const columns = [];
                $('input[name="columns[]"]:checked').each(function() {
                    columns.push($(this).val());
                });
                
                console.log('Index name:', indexName, 'Columns:', columns);
                
                if (columns.length === 0) {
                    showMessage('Please select at least one column', 'error');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fts_create_index',
                        index_name: indexName,
                        columns: columns
                    },
                    success: function(response) {
                        console.log('Create index response:', response);
                        if (response.success) {
                            showMessage('Index created successfully', 'success');
                            $('#create-index-form')[0].reset();
                            loadIndexes();
                        } else {
                            showMessage(response.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to create index:', error);
                        showMessage('Failed to create index: ' + error, 'error');
                    }
                });
            });
            
            // Delete index
            $(document).on('click', '.delete-index', function() {
                const indexName = $(this).data('index');
                console.log('Delete index clicked:', indexName);
                
                if (!confirm('Are you sure you want to delete the index "' + indexName + '"?')) {
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fts_delete_index',
                        index_name: indexName
                    },
                    success: function(response) {
                        console.log('Delete index response:', response);
                        if (response.success) {
                            showMessage('Index deleted successfully', 'success');
                            loadIndexes();
                        } else {
                            showMessage(response.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to delete index:', error);
                        showMessage('Failed to delete index: ' + error, 'error');
                    }
                });
            });
            
            // Run query
            $('#query-test-form').on('submit', function(e) {
                e.preventDefault();
                console.log('Query form submitted');
                
                const searchQuery = $('#search-query').val();
                const searchMode = $('#search-mode').val();
                const limit = $('#result-limit').val();
                
                console.log('Search params - Query:', searchQuery, 'Mode:', searchMode, 'Limit:', limit);
                
                
                $('#query-results').html('<p>Searching...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'fts_run_query',
                        search_query: searchQuery,
                        search_mode: searchMode,
                        limit: limit
                    },
                    success: function(response) {
                        console.log('Query response:', response);
                        if (response.success) {
                            displayResults(response.data);
                        } else {
                            $('#query-results').html('<p class="no-results">' + response.data + '</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Query error:', error);
                        console.error('XHR:', xhr);
                        console.error('Status:', status);
                        $('#query-results').html('<p class="no-results">Search failed: ' + error + '</p>');
                    }
                });
            });
            
            function displayResults(data) {
                console.log('Displaying results:', data);
                const container = $('#query-results');
                
                if (!data.results || data.results.length === 0) {
                    container.html('<p class="no-results">No results found</p>');
                    return;
                }
                
                let html = '<h3>Search Results (' + data.results.length + ' found)</h3>';
                
                // Display SQL query if available
                if (data.sql) {
                    html += '<div style="background: #f5f5f5; border: 1px solid #ddd; padding: 10px; margin-bottom: 15px; border-radius: 3px; font-family: monospace; font-size: 12px; overflow-x: auto;">';
                    html += '<strong>SQL Query:</strong><br>';
                    html += data.sql;
                    html += '</div>';
                }
                
                html += '<table class="results-table">';
                html += '<thead><tr><th>Rank</th><th>Product Name</th><th>Short Description</th><th>Expanded Description</th><th>Relevance</th></tr></thead>';
                html += '<tbody>';
                
                const maxScore = parseFloat(data.max_score) || 1;
                
                data.results.forEach(function(result, index) {
                    const score = parseFloat(result.relevance_score) || 0;
                    const percentage = maxScore > 0 ? (score / maxScore * 100) : 0;
                    
                    html += '<tr>';
                    html += '<td>' + (index + 1) + '</td>';
                    html += '<td><strong>' + result.product_name + '</strong></td>';
                    html += '<td>' + result.product_short_description + '</td>';
                    html += '<td>' + (result.expanded_description || '') + '</td>';
                    html += '<td>';
                    html += '<span class="relevance-score">' + score.toFixed(4) + '</span>';
                    html += '<div class="relevance-bar"><div class="relevance-bar-fill" style="width: ' + percentage + '%"></div></div>';
                    html += '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                container.html(html);
            }
        });
        </script>
        <?php
    }
    
    public function ajax_get_indexes() {
        global $wpdb;
        
        error_log('FTS: ajax_get_indexes called');
        
        $sql = "SHOW INDEX FROM {$this->table_name} WHERE Index_type = 'FULLTEXT'";
        $results = $wpdb->get_results($sql);
        
        error_log('FTS: Found ' . count($results) . ' index entries');
        
        $indexes = array();
        foreach ($results as $row) {
            if (!isset($indexes[$row->Key_name])) {
                $indexes[$row->Key_name] = array(
                    'name' => $row->Key_name,
                    'columns' => array()
                );
            }
            $indexes[$row->Key_name]['columns'][] = $row->Column_name;
        }
        
        wp_send_json_success(array_values($indexes));
    }
    
    public function ajax_create_index() {
        global $wpdb;
        
        error_log('FTS: ajax_create_index called');
        error_log('FTS: POST data - ' . print_r($_POST, true));
        
        if (!isset($_POST['index_name']) || !isset($_POST['columns'])) {
            wp_send_json_error('Invalid parameters');
            return;
        }
        
        $index_name = sanitize_text_field($_POST['index_name']);
        $columns = array_map('sanitize_text_field', $_POST['columns']);
        
        if (empty($index_name) || empty($columns)) {
            wp_send_json_error('Invalid parameters');
            return;
        }
        
        $columns_str = implode(', ', $columns);
        $sql = "ALTER TABLE {$this->table_name} ADD FULLTEXT INDEX {$index_name} ({$columns_str})";
        
        error_log('FTS: Creating index with SQL - ' . $sql);
        
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            error_log('FTS: Failed to create index - ' . $wpdb->last_error);
            wp_send_json_error('Failed to create index: ' . $wpdb->last_error);
            return;
        }
        
        error_log('FTS: Index created successfully');
        wp_send_json_success('Index created successfully');
    }
    
    public function ajax_delete_index() {
        global $wpdb;
        
        error_log('FTS: ajax_delete_index called');
        
        if (!isset($_POST['index_name'])) {
            wp_send_json_error('Invalid index name');
            return;
        }
        
        $index_name = sanitize_text_field($_POST['index_name']);
        
        if (empty($index_name)) {
            wp_send_json_error('Invalid index name');
            return;
        }
        
        $sql = "ALTER TABLE {$this->table_name} DROP INDEX {$index_name}";
        
        error_log('FTS: Deleting index with SQL - ' . $sql);
        
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            error_log('FTS: Failed to delete index - ' . $wpdb->last_error);
            wp_send_json_error('Failed to delete index: ' . $wpdb->last_error);
            return;
        }
        
        error_log('FTS: Index deleted successfully');
        wp_send_json_success('Index deleted successfully');
    }
    
    public function ajax_run_query() {
        global $wpdb;
        
        error_log('FTS: ajax_run_query called');
        error_log('FTS: POST data - ' . print_r($_POST, true));
        
        if (!isset($_POST['search_query']) || !isset($_POST['search_mode']) || !isset($_POST['limit'])) {
            error_log('FTS: Missing required parameters');
            wp_send_json_error('Invalid parameters - missing required fields');
            return;
        }
        
        $search_query = sanitize_text_field($_POST['search_query']);
        $search_mode = sanitize_text_field($_POST['search_mode']);
        $limit = intval($_POST['limit']);
        
        error_log("FTS: Query params - search: $search_query, mode: $search_mode, limit: $limit");
        
        if (empty($search_query)) {
            error_log('FTS: Empty search query');
            wp_send_json_error('Search query is required');
            return;
        }

        // Get all FULLTEXT indexes for this table
        $all_indexes = $wpdb->get_results(
            "SHOW INDEX FROM {$this->table_name} WHERE Index_type = 'FULLTEXT'"
        );
        
        error_log('FTS: All indexes - ' . print_r($all_indexes, true));
        
        if (empty($all_indexes)) {
            error_log('FTS: No FULLTEXT indexes found');
            wp_send_json_error('No full-text search indexes exist. Please create one first.');
            return;
        }
        
        // Get the first FULLTEXT index and its columns (MySQL will automatically choose the best one)
        $index_name = $all_indexes[0]->Key_name;
        $index_info = $wpdb->get_results($wpdb->prepare(
            "SHOW INDEX FROM {$this->table_name} WHERE Key_name = %s",
            $index_name
        ));
        
        error_log('FTS: Index info - ' . print_r($index_info, true));
        
        if (empty($index_info)) {
            error_log('FTS: Index not found - ' . $index_name);
            wp_send_json_error('Index not found: ' . $index_name);
            return;
        }
        
        $columns = array();
        foreach ($index_info as $info) {
            $columns[] = $info->Column_name;
        }
        $columns_str = implode(', ', $columns);
        
        error_log('FTS: Using columns - ' . $columns_str);
        
        // Build MATCH AGAINST clause based on mode
        $against_clause = '';
        switch ($search_mode) {
            case 'boolean':
                $against_clause = "AGAINST (%s IN BOOLEAN MODE)";
                break;
            case 'expansion':
                $against_clause = "AGAINST (%s WITH QUERY EXPANSION)";
                break;
            case 'natural':
            default:
                $against_clause = "AGAINST (%s)";
                break;
        }
        
        $sql = $wpdb->prepare(
            "SELECT product_name, product_short_description, expanded_description,
                    MATCH({$columns_str}) {$against_clause} AS relevance_score
             FROM {$this->table_name}
             WHERE MATCH({$columns_str}) {$against_clause}
             ORDER BY relevance_score DESC
             LIMIT %d",
            $search_query, $search_query, $limit
        );
        
        error_log('FTS: Executing SQL - ' . $sql);
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        error_log('FTS: Found ' . count($results) . ' results');
        
        if ($wpdb->last_error) {
            error_log('FTS: Query error - ' . $wpdb->last_error);
            wp_send_json_error('Database error: ' . $wpdb->last_error);
            return;
        }
        
        if (empty($results)) {
            error_log('FTS: No results found');
            wp_send_json_error('No results found for your search');
            return;
        }
        
        // Calculate max score for visualization and ensure scores are floats
        $max_score = 0;
        foreach ($results as &$result) {
            $result['relevance_score'] = floatval($result['relevance_score']);
            if ($result['relevance_score'] > $max_score) {
                $max_score = $result['relevance_score'];
            }
        }
        
        error_log('FTS: Returning results with max score - ' . $max_score);
        
        wp_send_json_success(array(
            'results' => $results,
            'max_score' => $max_score,
            'count' => count($results),
            'sql' => $sql
        ));
    }
}

// Initialize the plugin
new FTS_Manager();