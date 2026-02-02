<?php
/**
 * Plugin Name: ✅ 20 WP POST RAG MANAGER
 * Description: Manages a custom table for RAG processing of WordPress posts with Full-Text Search and Vector Search. Enhanced FTS index configuration.
 * Version: 1.6
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Posts_RAG_Manager {
    
    private $table_name;
    private $option_name = 'posts_rag_openai_key';
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'posts_rag';
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_save_openai_key', array($this, 'ajax_save_openai_key'));
        add_action('wp_ajax_sync_posts', array($this, 'ajax_sync_posts'));
        add_action('wp_ajax_generate_embeddings', array($this, 'ajax_generate_embeddings'));
        add_action('wp_ajax_create_fulltext_index', array($this, 'ajax_create_fulltext_index'));
        add_action('wp_ajax_delete_fulltext_index', array($this, 'ajax_delete_fulltext_index'));
        add_action('wp_ajax_get_fulltext_index_info', array($this, 'ajax_get_fulltext_index_info'));
        add_action('wp_ajax_test_search', array($this, 'ajax_test_search'));
        add_action('wp_ajax_get_rag_stats', array($this, 'ajax_get_rag_stats'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Get detailed information about existing full-text index
     */
    private function get_fulltext_index_info() {
        global $wpdb;
        
        $index_info = $wpdb->get_results(
            "SHOW INDEX FROM {$this->table_name} WHERE Key_name LIKE 'fulltext%' AND Index_type = 'FULLTEXT'"
        );
        
        if (empty($index_info)) {
            return null;
        }
        
        $index_name = $index_info[0]->Key_name;
        $columns = array();
        
        foreach ($index_info as $info) {
            $columns[] = $info->Column_name;
        }
        
        return array(
            'name' => $index_name,
            'columns' => $columns
        );
    }
    
    /**
     * Check if full-text index exists
     */
    private function check_fulltext_index() {
        return $this->get_fulltext_index_info() !== null;
    }
    
    /**
     * Delete existing full-text index
     */
    public function delete_fulltext_index() {
        global $wpdb;
        
        $index_info = $this->get_fulltext_index_info();
        
        if (!$index_info) {
            return array(
                'success' => false,
                'message' => 'No full-text index exists to delete.'
            );
        }
        
        $index_name = $index_info['name'];
        $sql = "ALTER TABLE {$this->table_name} DROP INDEX " . $wpdb->_escape($index_name);
        
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => 'Failed to delete full-text index: ' . $wpdb->last_error
            );
        }
        
        return array(
            'success' => true,
            'message' => "Full-text index '{$index_name}' deleted successfully."
        );
    }
    
    /**
     * Create full-text index on the table
     */
    public function create_fulltext_index($fields = array('post_title', 'post_content')) {
        global $wpdb;
        
        // Check if index already exists
        if ($this->check_fulltext_index()) {
            return array(
                'success' => false,
                'message' => 'A full-text index already exists. Please delete it first.'
            );
        }
        
        // Validate fields
        $valid_fields = array('post_title', 'post_content', 'categories', 'tags', 'custom_meta_data');
        $fields = array_intersect($fields, $valid_fields);
        
        if (empty($fields)) {
            return array(
                'success' => false,
                'message' => 'No valid fields selected for indexing.'
            );
        }
        
        // Create index name based on selected fields
        $index_name = 'fulltext_idx_' . implode('_', array_map(function($field) {
            return substr($field, 0, 5);
        }, $fields));
        
        // Create the full-text index
        $fields_str = implode(', ', $fields);
        $sql = "ALTER TABLE {$this->table_name} ADD FULLTEXT INDEX {$index_name} ({$fields_str})";
        
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => 'Failed to create full-text index: ' . $wpdb->last_error
            );
        }
        
        return array(
            'success' => true,
            'message' => "Full-text index '{$index_name}' created successfully on: " . implode(', ', $fields),
            'index_name' => $index_name,
            'fields' => $fields
        );
    }
    
    /**
     * AJAX: Get Full-Text Index Info
     */
    public function ajax_get_fulltext_index_info() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $index_info = $this->get_fulltext_index_info();
        
        if ($index_info) {
            wp_send_json_success($index_info);
        } else {
            wp_send_json_error('No index exists');
        }
    }
    
    /**
     * AJAX: Get RAG Stats
     */
    public function ajax_get_rag_stats() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        ob_start();
        $this->display_stats();
        $stats_html = ob_get_clean();
        
        wp_send_json_success($stats_html);
    }
    
    /**
     * AJAX: Delete Full-Text Index
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
    
    /**
     * AJAX: Create Full-Text Index
     */
    public function ajax_create_fulltext_index() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Get selected fields from POST data
        $fields = array();
        if (isset($_POST['index_title']) && $_POST['index_title'] === 'true') {
            $fields[] = 'post_title';
        }
        if (isset($_POST['index_content']) && $_POST['index_content'] === 'true') {
            $fields[] = 'post_content';
        }
        
        if (empty($fields)) {
            wp_send_json_error('Please select at least one field to index.');
            return;
        }
        
        $result = $this->create_fulltext_index($fields);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    // Copy all the REST API and search methods from original file
    // (register_rest_routes, rest_search_posts, rest_vector_search, etc.)
    // For brevity, I'm including the essential parts. You would copy the full methods.
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Full-text search endpoint
        register_rest_route('posts-rag/v1', '/search', array(
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
        register_rest_route('posts-rag/v1', '/vector-search', array(
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
                'excerpt' => wp_trim_words($row->post_content, 30)
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
     * Perform full-text search
     */
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Posts RAG Manager',
            '20 POSTS RAG',
            'manage_options',
            'posts-rag-manager',
            array($this, 'admin_page'),
            'dashicons-search',
            4.1
        );
        
        add_submenu_page(
            'posts-rag-manager',
            'Search Testing',
            'Search Testing',
            'manage_options',
            'posts-rag-search-testing',
            array($this, 'search_testing_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_posts-rag-manager' && $hook !== 'capstone_page_posts-rag-search-testing') {
            return;
        }
        
        wp_enqueue_script('jquery');
    }
    
    /**
     * AJAX: Save OpenAI API Key
     */
    public function ajax_save_openai_key() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        update_option($this->option_name, $api_key);
        
        wp_send_json_success('API Key saved successfully.');
    }
    
    /**
     * AJAX: Sync Posts
     */
    public function ajax_sync_posts() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $synced = $this->sync_posts_to_table();
        wp_send_json_success("Synced {$synced} posts to RAG table.");
    }
    
    /**
     * AJAX: Generate Embeddings
     */
    public function ajax_generate_embeddings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $result = $this->generate_embeddings();
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }
        
        $index_info = $this->get_fulltext_index_info();
        $index_exists = ($index_info !== null);
        ?>
        <div class="wrap" >
            <h1>Posts RAG Manager</h1>
            
            <div id="rag-message" style="display:none;" class="notice">
                <p></p>
            </div>
            
            <div class="card">
                <h2>OpenAI API Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="openai_api_key">OpenAI API Key</label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="openai_api_key" 
                                   id="openai_api_key" 
                                   value="<?php echo esc_attr(get_option($this->option_name)); ?>" 
                                   class="regular-text" 
                                   placeholder="sk-...">
                            <p class="description">Enter your OpenAI API key to enable embeddings generation.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="button" id="save-api-key-btn" class="button button-primary">Save API Key</button>
                </p>
            </div>
             <div class="card" style="margin-top: 20px;">
                <h2>Table Statistics</h2>
                <div id="stats-container">
                    <?php $this->display_stats(); ?>
                </div>
            </div>
            <div class="card" style="margin-top: 20px;">
                <h2>Sync Posts to RAG Table</h2>
                <p>Click the button below to sync all published posts to the RAG table.</p>
                <button type="button" id="sync-posts-btn" class="button button-primary">Sync Posts</button>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Full-Text Search Index</h2>
                
                <div id="index-status" style="margin-bottom: 15px;">
                    <?php if ($index_exists): ?>
                        <p>
                            Status: <strong style="color: green;">✅ Created</strong><br>
                            Index Name: <strong><?php echo esc_html($index_info['name']); ?></strong><br>
                            Indexed Fields: <strong><?php echo esc_html(implode(', ', $index_info['columns'])); ?></strong>
                        </p>
                    <?php else: ?>
                        <p>Status: <strong style="color: red;">❌ Not Created</strong></p>
                    <?php endif; ?>
                </div>
                <?php if (!$index_exists): ?>
                    <div id="index-creation-form">
                        <p><strong>Select fields to include in the full-text search index:</strong></p>
                        <p style="margin-left: 20px;">
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" id="index-title" checked> 
                                <strong>post_title</strong> - Post titles
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" id="index-content" checked> 
                                <strong>post_content</strong> - Post content/body
                            </label>
                        </p>
                        <button type="button" id="create-fulltext-btn" class="button button-primary">
                            Create Full-Text Index
                        </button>
                    </div>
                <?php else: ?>
                    <div id="index-management">
                        <p>
                            <button type="button" id="delete-fulltext-btn" class="button button-secondary">
                                Delete Index
                            </button>
                        </p>
                        <p class="description">Delete the current index to create a new one with different field selections.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Generate Embeddings</h2>
                <p>Generate OpenAI embeddings for post titles and content combined. This will process all posts that don't have embeddings yet.</p>
                <button type="button" id="generate-embeddings-btn" class="button button-primary">Generate Embeddings</button>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>REST API Endpoints</h2>
                
                <h3>Full-Text Search</h3>
                <p>Search using MySQL full-text index (keyword matching):</p>
                <code><?php echo esc_url(rest_url('posts-rag/v1/search')); ?>?query=FOAM&limit=3</code>
                
                <h3 style="margin-top: 15px;">Vector Search</h3>
                <p>Search using semantic similarity (requires embeddings):</p>
                <code><?php echo esc_url(rest_url('posts-rag/v1/vector-search')); ?>?query=FOAM&limit=3</code>
                
                <p class="description" style="margin-top: 10px;">
                    <strong>Parameters:</strong> <strong>query</strong> (required), <strong>limit</strong> (optional, default: 3, max: 20)
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
            
            function refreshStats() {
                console.log('🔄 refreshStats() called');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_rag_stats'
                    },
                    success: function(response) {
                        console.log('✅ Stats response:', response);
                        if (response.success) {
                            $('#stats-container').html(response.data);
                            console.log('✓ Stats updated');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Stats error:', status, error);
                    }
                });
            }
            
            function refreshIndexStatus() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_fulltext_index_info'
                    },
                    success: function(response) {
                        // Reload the page to show updated status
                        location.reload();
                    }
                });
            }
            
            // Save API Key
            $('#save-api-key-btn').on('click', function() {
                var $btn = $(this);
                var apiKey = $('#openai_api_key').val();
                
                $btn.prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'save_openai_key',
                        api_key: apiKey
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data, 'success');
                        } else {
                            showMessage(response.data, 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while saving the API key.', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
            
            // Sync Posts
            $('#sync-posts-btn').on('click', function() {
                var $btn = $(this);
                
                $btn.prop('disabled', true).text('Syncing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sync_posts'
                    },
                    success: function(response) {
                        console.log('✅ Sync response:', response);
                        if (response.success) {
                            showMessage(response.data, 'success');
                            setTimeout(function() { refreshStats(); }, 500);
                        } else {
                            showMessage(response.data, 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while syncing posts.', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Sync Posts');
                    }
                });
            });
            
            // Create Full-Text Index
            $('#create-fulltext-btn').on('click', function() {
                var $btn = $(this);
                
                // Get selected fields
                var indexTitle = $('#index-title').is(':checked');
                var indexContent = $('#index-content').is(':checked');
                
                if (!indexTitle && !indexContent) {
                    showMessage('Please select at least one field to index.', 'error');
                    return;
                }
                
                $btn.prop('disabled', true).text('Creating Index...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'create_fulltext_index',
                        index_title: indexTitle,
                        index_content: indexContent
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            var message = typeof data === 'object' ? data.message : data;
                            showMessage(message, 'success');
                            
                            // Refresh the page to show new index status
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showMessage(response.data, 'error');
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
            $('#delete-fulltext-btn').on('click', function() {
                if (!confirm('Are you sure you want to delete the full-text search index? You can create a new one afterwards.')) {
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('Deleting...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delete_fulltext_index'
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data, 'success');
                            
                            // Refresh the page to show updated status
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showMessage(response.data, 'error');
                            $btn.prop('disabled', false).text('Delete Index');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while deleting the index.', 'error');
                        $btn.prop('disabled', false).text('Delete Index');
                    }
                });
            });
            
            // Generate Embeddings
            $('#generate-embeddings-btn').on('click', function() {
                var $btn = $(this);
                
                $btn.prop('disabled', true).text('Generating Embeddings...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'generate_embeddings'
                    },
                    success: function(response) {
                        console.log('✅ Embeddings response:', response);
                        if (response.success) {
                            showMessage(response.data, 'success');
                            setTimeout(function() { refreshStats(); }, 500);
                        } else {
                            showMessage(response.data, 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while generating embeddings.', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Generate Embeddings');
                    }
                });
            });
        });
        </script>
        
        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .card h2 {
            margin-top: 0;
        }
        #index-creation-form label {
            cursor: pointer;
        }
        #index-creation-form input[type="checkbox"] {
            margin-right: 8px;
        }
        </style>
        <?php
    }
    
    /**
     * Display table statistics
     */
    /**
     * Display table statistics
     */
    private function display_stats() {
        global $wpdb;
        
        $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // Count posts with valid embeddings (not NULL and not empty)
        $embedded_rows = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE last_embedded IS NOT NULL AND embedding IS NOT NULL AND embedding != ''");
        
        $index_info = $this->get_fulltext_index_info();
        
        error_log("📊 Stats - Total: {$total_rows}, Embedded: {$embedded_rows}");
        
        echo '<p><strong>Total Posts in RAG Table:</strong> ' . intval($total_rows) . '</p>';
        echo '<p><strong>Posts with Embeddings:</strong> ' . intval($embedded_rows) . '</p>';
        echo '<p><strong>Full-Text Index:</strong> ' . ($index_info ? '✅ Active (' . $index_info['name'] . ')' : '❌ Not Created') . '</p>';
    }
    
    // Additional methods from original file would go here
    // (sync_posts_to_table, generate_embeddings, rest_vector_search, etc.)
    // For a complete implementation, copy all remaining methods from the original file
    
    /**
     * Sync posts to the table
     */
    private function sync_posts_to_table() {
        global $wpdb;
        
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1
        );
        
        $posts = get_posts($args);
        $synced_count = 0;
        
        foreach ($posts as $post) {
            // Get categories
            $categories = get_the_category($post->ID);
            $cat_names = array();
            foreach ($categories as $cat) {
                $cat_names[] = $cat->name;
            }
            $categories_str = implode(', ', $cat_names);
            
            // Get tags
            $tags = get_the_tags($post->ID);
            $tag_names = array();
            if ($tags) {
                foreach ($tags as $tag) {
                    $tag_names[] = $tag->name;
                }
            }
            $tags_str = implode(', ', $tag_names);
            
            // Get all custom field values as CSV
            $custom_values = array();
            
            // First try ACF fields if available
            if (function_exists('get_field_objects')) {
                $acf_fields = get_field_objects($post->ID);
                if ($acf_fields) {
                    foreach ($acf_fields as $field) {
                        $value = $field['value'];
                        if (is_array($value)) {
                            $value = implode('|', $value);
                        }
                        if (!empty($value)) {
                            $custom_values[] = $value;
                        }
                    }
                }
            }
            
            // Also get regular custom fields (non-ACF)
            $all_meta = get_post_meta($post->ID);
            foreach ($all_meta as $key => $values) {
                // Skip WordPress internal meta keys and ACF internal keys
                if (substr($key, 0, 1) !== '_') {
                    foreach ($values as $value) {
                        $value = maybe_unserialize($value);
                        if (is_array($value)) {
                            $value = implode('|', $value);
                        }
                        if (!empty($value) && is_scalar($value)) {
                            $custom_values[] = $value;
                        }
                    }
                }
            }
            
            // Remove duplicates and create CSV
            $custom_values = array_unique($custom_values);
            $custom_meta_csv = implode(', ', $custom_values);
            
            // Insert or update
            $wpdb->replace(
                $this->table_name,
                array(
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_content' => $post->post_content,
                    'categories' => $categories_str,
                    'tags' => $tags_str,
                    'custom_meta_data' => $custom_meta_csv
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s')
            );
            
            $synced_count++;
        }
        
        return $synced_count;
    }
    /**
     * Generate embeddings for posts using OpenAI API
     */
    public function generate_embeddings() {
        global $wpdb;

        $api_key = get_option($this->option_name);

        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key is not configured. Please add your API key first.'
            );
        }

        // Get posts without embeddings

        // Debug: Show what we're looking for
        $null_last_embedded = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE last_embedded IS NULL");
        $null_embedding = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE embedding IS NULL");
        $empty_embedding = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE embedding = ''");
        
        error_log("🔍 Debug counts:");
        error_log("   - last_embedded IS NULL: {$null_last_embedded}");
        error_log("   - embedding IS NULL: {$null_embedding}");
        error_log("   - embedding = '': {$empty_embedding}");
        $query = "SELECT id, post_id, post_title, post_content FROM {$this->table_name} WHERE embedding IS NULL OR embedding = ''";
        error_log("🔍 Embeddings query: " . $query);
        
        $posts = $wpdb->get_results($query);
        
        error_log("📊 Found " . count($posts) . " posts without embeddings");

        if (empty($posts)) {
            return array(
                'success' => true,
                'message' => 'All posts already have embeddings.'
            );
        }

        $success_count = 0;
        $error_count = 0;
        $errors = array();

        foreach ($posts as $post) {
            error_log("🔄 Processing post ID: {$post->post_id}");
            
            // Create embedding text from title + content (truncated for API limits)
            $embedding_text = $post->post_title . "\n\n" . wp_trim_words($post->post_content, 500);

            $embedding = $this->get_openai_embedding($embedding_text, $api_key);

            if ($embedding !== false) {
                // Store embedding as JSON
                $embedding_json = json_encode($embedding);

                $updated = $wpdb->update(
                    $this->table_name,
                    array(
                        'embedding' => $embedding_json,
                        'last_embedded' => current_time('mysql')
                    ),
                    array('id' => $post->id),
                    array('%s', '%s'),
                    array('%d')
                );

                if ($updated !== false) {
                    $success_count++;
                    error_log("✅ Embedded post ID: {$post->post_id}");
                } else {
                    $error_count++;
                    $errors[] = "Failed to update database for post ID {$post->post_id}";
                    error_log("❌ Failed to update DB for post ID: {$post->post_id} - " . $wpdb->last_error);
                }
            } else {
                $error_count++;
                $errors[] = "Failed to generate embedding for post ID {$post->post_id}";
                error_log("❌ Failed to generate embedding for post ID: {$post->post_id}");
            }

            // Small delay to avoid rate limiting
            usleep(100000); // 0.1 second
        }

        $message = "Generated embeddings for {$success_count} posts.";
        if ($error_count > 0) {
            $message .= " {$error_count} errors occurred.";
        }
        
        error_log("✅ Embedding complete: {$message}");

        return array(
            'success' => $success_count > 0,
            'message' => $message,
            'errors' => $errors
        );
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
    
    /**
     * Search testing page (placeholder - copy from original)
     */
    /**
     * Search Testing Page
     */
    public function search_testing_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }
        
        $site_url = get_site_url();
        $fts_endpoint = rest_url('posts-rag/v1/search');
        $vector_endpoint = rest_url('posts-rag/v1/vector-search');
        ?>
        <div class="wrap">
            <h1>Search Testing</h1>
            
            <div id="search-message" style="display:none; margin: 15px 0;" class="notice">
                <p></p>
            </div>
            
            <!-- Full-Text Search Testing -->
            <div class="card" style="margin-top: 20px;">
                <h2>Full-Text Search (FTS) Testing</h2>
                <p>Test the MySQL full-text search functionality. Requires a full-text index to be created.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="fts-query">Search Query</label>
                        </th>
                        <td>
                            <input type="text" id="fts-query" class="regular-text" placeholder="Enter search term..." value="foam">
                            <p class="description">Enter keywords to search for (e.g., "foam", "materials")</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="fts-limit">Result Limit</label>
                        </th>
                        <td>
                            <input type="number" id="fts-limit" value="3" min="1" max="20" style="width: 80px;">
                            <p class="description">Number of results to return (1-20)</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="button" id="test-fts-btn" class="button button-primary">Test Full-Text Search</button>
                </p>
                
                <div id="fts-results" style="margin-top: 20px; display: none;">
                    <h3>Results:</h3>
                    <div id="fts-results-content" style="background: #f5f5f5; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto;">
                        <pre id="fts-json" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <strong>API Endpoint:</strong><br>
                    <code><?php echo esc_url($fts_endpoint); ?>?query=foam&limit=3</code>
                </div>
            </div>
            
            <!-- Vector Search Testing -->
            <div class="card" style="margin-top: 20px;">
                <h2>Vector Search Testing</h2>
                <p>Test the semantic similarity search using OpenAI embeddings. Requires embeddings to be generated.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="vector-query">Search Query</label>
                        </th>
                        <td>
                            <input type="text" id="vector-query" class="regular-text" placeholder="Enter search phrase..." value="sustainable materials">
                            <p class="description">Enter a phrase or question for semantic search</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="vector-limit">Result Limit</label>
                        </th>
                        <td>
                            <input type="number" id="vector-limit" value="3" min="1" max="20" style="width: 80px;">
                            <p class="description">Number of results to return (1-20)</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="button" id="test-vector-btn" class="button button-primary">Test Vector Search</button>
                </p>
                
                <div id="vector-results" style="margin-top: 20px; display: none;">
                    <h3>Results:</h3>
                    <div id="vector-results-content" style="background: #f5f5f5; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto;">
                        <pre id="vector-json" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <strong>API Endpoint:</strong><br>
                    <code><?php echo esc_url($vector_endpoint); ?>?query=sustainable+materials&limit=3</code>
                </div>
            </div>
            
            <!-- Comparison Testing -->
            <div class="card" style="margin-top: 20px;">
                <h2>Compare Both Methods</h2>
                <p>Run the same query through both FTS and Vector search to compare results.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="compare-query">Search Query</label>
                        </th>
                        <td>
                            <input type="text" id="compare-query" class="regular-text" placeholder="Enter search term..." value="foam">
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="button" id="compare-btn" class="button button-primary">Compare Both Methods</button>
                </p>
            </div>

            <!-- Comparison Results (Full Width) -->
            <div id="compare-results" class="card compare-results" style="margin-top: 20px; display: none;">
                <h2>Comparison Results</h2>
                <div class="compare-results-grid">
                    <div>
                        <h3>Full-Text Search Results</h3>
                        <div class="compare-results-panel">
                            <pre id="compare-fts-json" class="compare-results-json"></pre>
                        </div>
                    </div>
                    <div>
                        <h3>Vector Search Results</h3>
                        <div class="compare-results-panel">
                            <pre id="compare-vector-json" class="compare-results-json"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .card h2 {
            margin-top: 0;
        }
        .compare-results {
            max-width: none;
            width: 100%;
        }
        .compare-results-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }
        .compare-results-panel {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            max-height: 500px;
            overflow-y: auto;
        }
        .compare-results-json {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 12px;
        }
        @media (max-width: 960px) {
            .compare-results-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            
            function showMessage(message, type) {
                var $msg = $('#search-message');
                $msg.removeClass('notice-success notice-error notice-info notice-warning')
                    .addClass('notice-' + type)
                    .find('p').text(message);
                $msg.show();
                
                setTimeout(function() {
                    $msg.fadeOut();
                }, 5000);
            }
            
            // Test Full-Text Search
            $('#test-fts-btn').on('click', function() {
                var $btn = $(this);
                var query = $('#fts-query').val();
                var limit = $('#fts-limit').val();
                
                if (!query) {
                    showMessage('Please enter a search query', 'error');
                    return;
                }
                
                $btn.prop('disabled', true).text('Searching...');
                $('#fts-results').hide();
                
                var url = '<?php echo esc_url($fts_endpoint); ?>?query=' + encodeURIComponent(query) + '&limit=' + limit;
                
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        console.log('FTS Response:', response);
                        $('#fts-json').text(JSON.stringify(response, null, 2));
                        $('#fts-results').show();
                        
                        if (response.success && response.count > 0) {
                            showMessage('Found ' + response.count + ' results', 'success');
                        } else if (response.success) {
                            showMessage('No results found', 'info');
                        } else {
                            showMessage('Search completed with no matches', 'warning');
                        }
                    },
                    error: function(xhr) {
                        console.error('FTS Error:', xhr);
                        var error = xhr.responseJSON || {message: 'Unknown error'};
                        $('#fts-json').text(JSON.stringify(error, null, 2));
                        $('#fts-results').show();
                        showMessage('Error: ' + (error.message || xhr.statusText), 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test Full-Text Search');
                    }
                });
            });
            
            // Test Vector Search
            $('#test-vector-btn').on('click', function() {
                var $btn = $(this);
                var query = $('#vector-query').val();
                var limit = $('#vector-limit').val();
                
                if (!query) {
                    showMessage('Please enter a search query', 'error');
                    return;
                }
                
                $btn.prop('disabled', true).text('Searching...');
                $('#vector-results').hide();
                
                var url = '<?php echo esc_url($vector_endpoint); ?>?query=' + encodeURIComponent(query) + '&limit=' + limit;
                
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        console.log('Vector Response:', response);
                        $('#vector-json').text(JSON.stringify(response, null, 2));
                        $('#vector-results').show();
                        
                        if (response.success && response.count > 0) {
                            showMessage('Found ' + response.count + ' results', 'success');
                        } else if (response.success) {
                            showMessage('No results found', 'info');
                        } else {
                            showMessage('Search completed with no matches', 'warning');
                        }
                    },
                    error: function(xhr) {
                        console.error('Vector Error:', xhr);
                        var error = xhr.responseJSON || {message: 'Unknown error'};
                        $('#vector-json').text(JSON.stringify(error, null, 2));
                        $('#vector-results').show();
                        showMessage('Error: ' + (error.message || xhr.statusText), 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test Vector Search');
                    }
                });
            });
            
            // Compare Both Methods
            $('#compare-btn').on('click', function() {
                var $btn = $(this);
                var query = $('#compare-query').val();
                
                if (!query) {
                    showMessage('Please enter a search query', 'error');
                    return;
                }
                
                $btn.prop('disabled', true).text('Comparing...');
                $('#compare-results').hide();
                
                var ftsUrl = '<?php echo esc_url($fts_endpoint); ?>?query=' + encodeURIComponent(query) + '&limit=3';
                var vectorUrl = '<?php echo esc_url($vector_endpoint); ?>?query=' + encodeURIComponent(query) + '&limit=3';
                
                $.when(
                    $.ajax({url: ftsUrl, type: 'GET'}),
                    $.ajax({url: vectorUrl, type: 'GET'})
                ).done(function(ftsResult, vectorResult) {
                    console.log('FTS:', ftsResult[0]);
                    console.log('Vector:', vectorResult[0]);
                    
                    $('#compare-fts-json').text(JSON.stringify(ftsResult[0], null, 2));
                    $('#compare-vector-json').text(JSON.stringify(vectorResult[0], null, 2));
                    $('#compare-results').show();
                    
                    showMessage('Comparison complete!', 'success');
                }).fail(function(xhr) {
                    showMessage('Error during comparison', 'error');
                    console.error('Comparison error:', xhr);
                }).always(function() {
                    $btn.prop('disabled', false).text('Compare Both Methods');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * REST API endpoint: Vector search (placeholder - copy from original)
     */
    
    /**
     * REST API endpoint: Vector search
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
        
        $api_key = get_option($this->option_name);
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key is not configured.'
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
            FROM {$this->table_name} 
            WHERE embedding IS NOT NULL AND embedding != ''"
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
                    'excerpt' => wp_trim_words($post->post_content, 30)
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
        
        // Get index info to determine which fields are indexed
        $index_info = $this->get_fulltext_index_info();
        
        if (!$index_info) {
            return array();
        }
        
        $indexed_fields = implode(', ', $index_info['columns']);
        
        $sql = $wpdb->prepare(
            "SELECT 
                post_id,
                post_title,
                post_content,
                categories,
                tags,
                MATCH({$indexed_fields}) 
                AGAINST (%s IN NATURAL LANGUAGE MODE) as relevance_score
            FROM {$this->table_name}
            WHERE MATCH({$indexed_fields}) 
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
     * Activation hook - create table
     */
    public function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            post_title text NOT NULL,
            post_content longtext NOT NULL,
            categories text,
            tags text,
            custom_meta_data text,
            embedding longtext,
            last_embedded datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
$posts_rag_manager = new Posts_RAG_Manager();

// Activation hook must be outside the class
register_activation_hook(__FILE__, array($posts_rag_manager, 'activate'));
