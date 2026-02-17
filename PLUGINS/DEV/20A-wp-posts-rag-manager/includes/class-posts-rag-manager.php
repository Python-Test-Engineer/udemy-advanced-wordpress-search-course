<?php
/**
 * Posts_RAG_Manager Class
 *
 * This is the core manager class for the WP Posts RAG Manager plugin.
 * It handles all the core functionality including:
 * - Database table management
 * - Full-text search (FTS) index management
 * - OpenAI embeddings generation
 * - Vector search using cosine similarity
 * - Posts synchronization from WordPress to the custom table
 *
 * @package WP_Posts_RAG_Manager
 * @subpackage Includes
 *
 * ============================================================================
 * CORE FUNCTIONALITY:
 * ============================================================================
 * 1. POSTS SYNCHRONIZATION
 *    - Sync published WordPress posts to custom RAG table
 *    - Include post title, content, categories, tags, and custom meta
 *
 * 2. FULL-TEXT SEARCH (FTS)
 *    - Create/drop MySQL FULLTEXT indexes
 *    - Perform keyword-based search using MATCH...AGAINST
 *    - Return results with relevance scores
 *
 * 3. VECTOR SEARCH (EMBEDDINGS)
 *    - Generate OpenAI text-embedding-3-small embeddings
 *    - Store embeddings as JSON in database
 *    - Perform cosine similarity search
 *    - Return semantically similar posts
 * ============================================================================
 */

// Prevent direct access to this file.
if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// CLASS DEFINITION
// ============================================================================

/**
 * Posts_RAG_Manager Class
 *
 * Main manager class for RAG functionality.
 * Uses singleton pattern to ensure only one instance exists.
 *
 * @since 1.7.0
 * @access public
 */
class Posts_RAG_Manager {

    // ============================================================================
    // CLASS PROPERTIES
    // ============================================================================

    /**
     * The name of the database table for RAG processing.
     *
     * @since 1.7.0
     * @access private
     * @var string
     */
    private $table_name;

    /**
     * The option name for storing the OpenAI API key.
     *
     * @since 1.7.0
     * @access private
     * @var string
     */
    private $option_name = 'posts_rag_openai_key';

    /**
     * The OpenAI model to use for embeddings.
     *
     * @since 1.7.0
     * @access private
     * @var string
     */
    private $embedding_model = 'text-embedding-3-small';

    /**
     * Valid fields for full-text indexing.
     *
     * @since 1.7.0
     * @access private
     * @var array
     */
    private $valid_index_fields = array(
        'post_title',
        'post_content',
        'categories',
        'tags',
        'custom_meta_data'
    );

    // ============================================================================
    // SINGLETON PATTERN
    // ============================================================================

    /**
     * The single instance of the class.
     *
     * @since 1.7.0
     * @access private
     * @var Posts_RAG_Manager
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * This method ensures only one instance of the class exists.
     * If no instance exists, it creates one.
     *
     * @since  1.7.0
     * @access public
     * @static
     * @return Posts_RAG_Manager The singleton instance.
     */
    public static function get_instance() {
        // Check if instance already exists.
        if (null === self::$instance) {
            // Create a new instance if none exists.
            self::$instance = new self();
        }

        return self::$instance;
    }

    // ============================================================================
    // CONSTRUCTOR
    // ============================================================================

    /**
     * Constructor method.
     *
     * Initializes the class properties and sets up the table name.
     * This is called when the class is instantiated.
     *
     * @since 1.7.0
     * @access public
     */
    public function __construct() {
        // Get the global WordPress database object.
        global $wpdb;

        // Set the table name with WordPress prefix.
        // For example, if WordPress uses 'wp_', table becomes 'wp_posts_rag'.
        $this->table_name = $wpdb->prefix . 'posts_rag';

        // Log constructor call if debug mode is enabled.
        // This helps track when the class is instantiated.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('📝 Posts_RAG_Manager: Constructor called');
            error_log('   Table name: ' . $this->table_name);
            error_log('   Option name: ' . $this->option_name);
            error_log('   Embedding model: ' . $this->embedding_model);
        }
    }

    // ============================================================================
    // FULLTEXT INDEX METHODS
    // ============================================================================

    /**
     * Get detailed information about existing full-text index.
     *
     * This method queries the database to get information about any
     * FULLTEXT indexes on the table. It returns the index name and
     * the columns included in the index.
     *
     * @since  1.7.0
     * @access public
     * @return array|null Array with index info or null if no index exists.
     */
    public function get_fulltext_index_info() {
        // Log the start of this operation.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔍 Posts_RAG_Manager: Getting fulltext index info');
        }

        // Get the global WordPress database object.
        global $wpdb;

        // Query for FULLTEXT indexes on this table.
        // SHOW INDEX returns all indexes on a table.
        // We filter for FULLTEXT type indexes.
        $index_info = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW INDEX FROM {$this->table_name} WHERE Key_name LIKE %s AND Index_type = 'FULLTEXT'",
                $wpdb->esc_like('fulltext') . '%'
            )
        );

        // If no results, there's no fulltext index.
        if (empty($index_info)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   No fulltext index found');
            }
            return null;
        }

        // Build the index information array.
        // The first result contains the index name.
        $index_name = $index_info[0]->Key_name;
        $columns = array();

        // Collect all columns in the index.
        foreach ($index_info as $info) {
            $columns[] = $info->Column_name;
        }

        // Return the index information.
        $result = array(
            'name' => $index_name,
            'columns' => $columns
        );

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   Found index: ' . $index_name);
            error_log('   Columns: ' . implode(', ', $columns));
        }

        return $result;
    }

    /**
     * Check if full-text index exists.
     *
     * Simple method to check if a FULLTEXT index exists on the table.
     *
     * @since  1.7.0
     * @access public
     * @return bool True if index exists, false otherwise.
     */
    public function check_fulltext_index() {
        // Get index info and check if it's not null.
        return $this->get_fulltext_index_info() !== null;
    }

    /**
     * Create full-text index on the table.
     *
     * This method creates a MySQL FULLTEXT index on the specified fields.
     * FULLTEXT indexes enable efficient keyword searching.
     *
     * @since  1.7.0
     * @access public
     * @param array $fields Array of field names to index. Default: array('post_title', 'post_content').
     * @return array Result array with success status and message.
     */
    public function create_fulltext_index($fields = array('post_title', 'post_content')) {
        // Log the start of index creation.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('📊 Posts_RAG_Manager: Creating fulltext index');
            error_log('   Requested fields: ' . implode(', ', $fields));
        }

        // Get the global WordPress database object.
        global $wpdb;

        // First, check if an index already exists.
        if ($this->check_fulltext_index()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Index already exists');
            }
            return array(
                'success' => false,
                'message' => __('A full-text index already exists. Please delete it first.', 'wp-posts-rag-manager')
            );
        }

        // Validate the requested fields.
        // Only allow indexing of fields in our valid list.
        $fields = array_intersect($fields, $this->valid_index_fields);

        // Check if we have any valid fields to index.
        if (empty($fields)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ No valid fields selected');
            }
            return array(
                'success' => false,
                'message' => __('No valid fields selected for indexing.', 'wp-posts-rag-manager')
            );
        }

        // Create an index name based on the selected fields.
        // This helps identify what fields are indexed.
        // For example: fulltext_idx_post_title_post_content
        $index_name = 'fulltext_idx_' . implode('_', array_map(function($field) {
            return substr($field, 0, 5);  // Take first 5 characters
        }, $fields));

        // Build the SQL to add the FULLTEXT index.
        // ALTER TABLE ADD FULLTEXT INDEX creates a fulltext index.
        $fields_str = implode(', ', $fields);
        $sql = "ALTER TABLE {$this->table_name} ADD FULLTEXT INDEX {$index_name} ({$fields_str})";

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   SQL: ' . $sql);
        }

        // Execute the SQL query.
        $result = $wpdb->query($sql);

        // Check if the query was successful.
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Query failed: ' . $wpdb->last_error);
            }
            return array(
                'success' => false,
                'message' => __('Failed to create full-text index: ', 'wp-posts-rag-manager') . $wpdb->last_error
            );
        }

        // Log successful creation.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Index created successfully');
        }

        // Return success with details.
        return array(
            'success' => true,
            'message' => sprintf(
                /* translators: %1$s is the index name, %2$s is the list of fields */
                __('Full-text index "%1$s" created successfully on: %2$s', 'wp-posts-rag-manager'),
                $index_name,
                implode(', ', $fields)
            ),
            'index_name' => $index_name,
            'fields' => $fields
        );
    }

    /**
     * Delete existing full-text index.
     *
     * This method removes the FULLTEXT index from the table.
     *
     * @since  1.7.0
     * @access public
     * @return array Result array with success status and message.
     */
    public function delete_fulltext_index() {
        // Log the start of index deletion.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🗑️  Posts_RAG_Manager: Deleting fulltext index');
        }

        // Get the global WordPress database object.
        global $wpdb;

        // First get info about the existing index.
        $index_info = $this->get_fulltext_index_info();

        // Check if an index exists.
        if (!$index_info) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ No index exists to delete');
            }
            return array(
                'success' => false,
                'message' => __('No full-text index exists to delete.', 'wp-posts-rag-manager')
            );
        }

        // Get the index name.
        $index_name = $index_info['name'];

        // Build the SQL to drop the index.
        // ALTER TABLE DROP the index.
        $sql = " INDEX removesALTER TABLE {$this->table_name} DROP INDEX " . $wpdb->_escape($index_name);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   SQL: ' . $sql);
        }

        // Execute the query.
        $result = $wpdb->query($sql);

        // Check if the query was successful.
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Query failed: ' . $wpdb->last_error);
            }
            return array(
                'success' => false,
                'message' => __('Failed to delete full-text index: ', 'wp-posts-rag-manager') . $wpdb->last_error
            );
        }

        // Log successful deletion.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Index deleted successfully');
        }

        // Return success.
        return array(
            'success' => true,
            'message' => sprintf(
                /* translators: %s is the index name */
                __('Full-text index "%s" deleted successfully.', 'wp-posts-rag-manager'),
                $index_name
            )
        );
    }

    // ============================================================================
    // FULLTEXT SEARCH METHODS
    // ============================================================================

    /**
     * Perform full-text search on the RAG table.
     *
     * This method uses MySQL's FULLTEXT search capabilities to find
     * posts matching the search query. It returns results with
     * relevance scores.
     *
     * @since  1.7.0
     * @access public
     * @param string $query The search query string.
     * @param int    $limit Maximum number of results to return. Default: 3.
     * @return array Array of result objects.
     */
    public function fulltext_search($query, $limit = 3) {
        // Log the search operation.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔎 Posts_RAG_Manager: Running fulltext search');
            error_log('   Query: ' . $query);
            error_log('   Limit: ' . $limit);
        }

        // Get the global WordPress database object.
        global $wpdb;

        // Get index info to determine which fields are indexed.
        $index_info = $this->get_fulltext_index_info();

        // If no index exists, return empty results.
        if (!$index_info) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ No fulltext index exists');
            }
            return array();
        }

        // Get the indexed fields.
        $indexed_fields = implode(', ', $index_info['columns']);

        // Prepare the SQL query.
        // MATCH(column1, column2) AGAINST('query' IN NATURAL LANGUAGE MODE)
        // performs the fulltext search and returns a relevance score.
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

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   SQL: ' . $sql);
        }

        // Execute the query.
        $results = $wpdb->get_results($sql);

        // Log the number of results.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   Results found: ' . count($results));
        }

        return $results;
    }

    // ============================================================================
    // VECTOR SEARCH (EMBEDDINGS) METHODS
    // ============================================================================

    /**
     * Call OpenAI API to get embedding for text.
     *
     * This method sends the text to OpenAI's embedding API and
     * returns the embedding vector.
     *
     * @since  1.7.0
     * @access private
     * @param string $text    The text to embed.
     * @param string $api_key The OpenAI API key.
     * @return array|false The embedding array or false on failure.
     */
    private function get_openai_embedding($text, $api_key) {
        // Log the embedding generation start.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🤖 Posts_RAG_Manager: Generating OpenAI embedding');
            error_log('   Text length: ' . strlen($text));
            error_log('   Model: ' . $this->embedding_model);
        }

        // The OpenAI embeddings API endpoint.
        $url = 'https://api.openai.com/v1/embeddings';

        // Prepare the request data.
        $data = array(
            'input' => $text,
            'model' => $this->embedding_model
        );

        // Prepare the HTTP request arguments.
        $args = array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body'    => json_encode($data),
            'timeout' => 30  // 30 second timeout
        );

        // Make the API request using WordPress HTTP API.
        $response = wp_remote_post($url, $args);

        // Check for errors.
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ API Error: ' . $response->get_error_message());
            }
            return false;
        }

        // Get the response body.
        $body = wp_remote_retrieve_body($response);

        // Decode the JSON response.
        $result = json_decode($body, true);

        // Check if we got a successful response with embedding data.
        if (isset($result['data'][0]['embedding'])) {
            // Return the embedding vector.
            return $result['data'][0]['embedding'];
        }

        // Log any API errors.
        if (isset($result['error'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ API Error: ' . $result['error']['message']);
            }
        }

        return false;
    }

    /**
     * Calculate cosine similarity between two vectors.
     *
     * Cosine similarity measures how similar two vectors are based on
     * the angle between them. It's commonly used for semantic search.
     *
     * Formula: cos(A, B) = (A · B) / (||A|| × ||B||)
     *
     * @since  1.7.0
     * @access private
     * @param array $vec1 First vector (embedding array).
     * @param array $vec2 Second vector (embedding array).
     * @return float The cosine similarity score between -1 and 1.
     */
    private function cosine_similarity($vec1, $vec2) {
        // Check if vectors have the same dimensions.
        if (count($vec1) !== count($vec2)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('⚠️  Posts_RAG_Manager: Vector dimensions mismatch');
            }
            return 0;
        }

        // Initialize variables for the calculation.
        $dot_product = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        // Calculate dot product and magnitudes.
        for ($i = 0; $i < count($vec1); $i++) {
            // Dot product: sum of products of corresponding elements.
            $dot_product += $vec1[$i] * $vec2[$i];

            // Magnitude: square root of sum of squares.
            $magnitude1 += $vec1[$i] * $vec1[$i];
            $magnitude2 += $vec2[$i] * $vec2[$i];
        }

        // Calculate square roots of magnitudes.
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        // Check for zero magnitudes (avoid division by zero).
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        // Calculate and return cosine similarity.
        return $dot_product / ($magnitude1 * $magnitude2);
    }

    /**
     * Perform vector search using cosine similarity.
     *
     * This method generates an embedding for the search query and
     * compares it with all stored embeddings using cosine similarity.
     *
     * @since  1.7.0
     * @access public
     * @param string $query The search query string.
     * @param int    $limit Maximum number of results to return. Default: 3.
     * @return array Result array with success status, message, and results.
     */
    public function vector_search($query, $limit = 3) {
        // Log the vector search start.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔬 Posts_RAG_Manager: Running vector search');
            error_log('   Query: ' . $query);
            error_log('   Limit: ' . $limit);
        }

        // Get the global WordPress database object.
        global $wpdb;

        // Get the OpenAI API key from options.
        $api_key = get_option($this->option_name);

        // Check if API key is configured.
        if (empty($api_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ OpenAI API key not configured');
            }
            return array(
                'success' => false,
                'message' => __('OpenAI API key is not configured.', 'wp-posts-rag-manager')
            );
        }

        // Generate embedding for the search query.
        $query_embedding = $this->get_openai_embedding($query, $api_key);

        // Check if embedding generation succeeded.
        if ($query_embedding === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Failed to generate query embedding');
            }
            return array(
                'success' => false,
                'message' => __('Failed to generate embedding for query.', 'wp-posts-rag-manager')
            );
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Query embedding generated, dimensions: ' . count($query_embedding));
        }

        // Get all posts with embeddings from the database.
        $posts = $wpdb->get_results(
            "SELECT id, post_id, post_title, post_content, categories, tags, embedding 
            FROM {$this->table_name} 
            WHERE embedding IS NOT NULL AND embedding != ''"
        );

        // Check if we have any posts with embeddings.
        if (empty($posts)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ No posts with embeddings found');
            }
            return array(
                'success' => false,
                'message' => __('No posts with embeddings found. Please generate embeddings first.', 'wp-posts-rag-manager')
            );
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   Found ' . count($posts) . ' posts with embeddings');
        }

        // Calculate cosine similarity for each post.
        $similarities = array();

        foreach ($posts as $post) {
            // Decode the stored embedding JSON.
            $post_embedding = json_decode($post->embedding, true);

            // Skip if embedding is not a valid array.
            if (!is_array($post_embedding)) {
                continue;
            }

            // Calculate similarity.
            $similarity = $this->cosine_similarity($query_embedding, $post_embedding);

            // Add to results array.
            $similarities[] = array(
                'post_id'          => intval($post->post_id),
                'post_title'       => $post->post_title,
                'similarity_score' => $similarity,
                'categories'       => $post->categories,
                'tags'             => $post->tags,
                'excerpt'          => wp_trim_words($post->post_content, 30)
            );
        }

        // Sort results by similarity score (highest first).
        // Using spaceship operator for descending order.
        usort($similarities, function($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });

        // Get the top N results.
        $top_results = array_slice($similarities, 0, $limit);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Returning ' . count($top_results) . ' results');
        }

        return array(
            'success' => true,
            'results' => $top_results
        );
    }

    // ============================================================================
    // POSTS SYNCHRONIZATION METHODS
    // ============================================================================

    /**
     * Sync posts to the RAG table.
     *
     * This method retrieves all published WordPress posts and inserts
     * or updates them in the custom RAG table. It includes post content,
     * categories, tags, and custom meta fields.
     *
     * @since  1.7.0
     * @access public
     * @return int Number of posts synced.
     */
    public function sync_posts_to_table() {
        // Log the sync start.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔄 Posts_RAG_Manager: Starting posts synchronization');
        }

        // Get the global WordPress database object.
        global $wpdb;

        // Prepare WP_Query arguments to get all published posts.
        $args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1  // Get all posts
        );

        // Get the posts using WordPress get_posts function.
        $posts = get_posts($args);

        // Log the number of posts found.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   Found ' . count($posts) . ' published posts');
        }

        // Initialize counter.
        $synced_count = 0;

        // Loop through each post.
        foreach ($posts as $post) {
            // Get categories for this post.
            $categories = get_the_category($post->ID);
            $cat_names = array();
            foreach ($categories as $cat) {
                $cat_names[] = $cat->name;
            }
            $categories_str = implode(', ', $cat_names);

            // Get tags for this post.
            $tags = get_the_tags($post->ID);
            $tag_names = array();
            if ($tags) {
                foreach ($tags as $tag) {
                    $tag_names[] = $tag->name;
                }
            }
            $tags_str = implode(', ', $tag_names);

            // Get all custom meta field values.
            $custom_values = array();

            // First, try to get ACF (Advanced Custom Fields) fields if available.
            if (function_exists('get_field_objects')) {
                $acf_fields = get_field_objects($post->ID);
                if ($acf_fields) {
                    foreach ($acf_fields as $field) {
                        $value = $field['value'];
                        if (is_array($value)) {
                            // Convert arrays to pipe-separated string.
                            $value = implode('|', $value);
                        }
                        if (!empty($value)) {
                            $custom_values[] = $value;
                        }
                    }
                }
            }

            // Also get regular custom fields (non-ACF).
            $all_meta = get_post_meta($post->ID);
            foreach ($all_meta as $key => $values) {
                // Skip WordPress internal meta keys (starting with underscore).
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

            // Remove duplicates and create CSV string.
            $custom_values = array_unique($custom_values);
            $custom_meta_csv = implode(', ', $custom_values);

            // Insert or update the post in our RAG table.
            // wpdb->replace() inserts a new row or updates existing if unique key matches.
            $result = $wpdb->replace(
                $this->table_name,
                array(
                    'post_id'          => $post->ID,
                    'post_title'       => $post->post_title,
                    'post_content'     => $post->post_content,
                    'categories'       => $categories_str,
                    'tags'            => $tags_str,
                    'custom_meta_data' => $custom_meta_csv
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s')
            );

            if ($result !== false) {
                $synced_count++;
            }
        }

        // Log the sync completion.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Synced ' . $synced_count . ' posts');
        }

        return $synced_count;
    }

    /**
     * Generate embeddings for posts using OpenAI API.
     *
     * This method finds all posts without embeddings and generates
     * embeddings for them using OpenAI's API. It creates embeddings
     * from the post title and content combined.
     *
     * @since  1.7.0
     * @access public
     * @return array Result array with success status and message.
     */
    public function generate_embeddings() {
        // Log the embedding generation start.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🎯 Posts_RAG_Manager: Starting embeddings generation');
        }

        // Get the global WordPress database object.
        global $wpdb;

        // Get the OpenAI API key from options.
        $api_key = get_option($this->option_name);

        // Check if API key is configured.
        if (empty($api_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ OpenAI API key not configured');
            }
            return array(
                'success' => false,
                'message' => __('OpenAI API key is not configured. Please add your API key first.', 'wp-posts-rag-manager')
            );
        }

        // Debug: Count posts without embeddings.
        // This helps us understand how many posts need processing.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $null_embedding = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE embedding IS NULL");
            $empty_embedding = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE embedding = ''");
            error_log('   Posts without embeddings (NULL): ' . $null_embedding);
            error_log('   Posts without embeddings (empty): ' . $empty_embedding);
        }

        // Query for posts without embeddings.
        $posts = $wpdb->get_results(
            "SELECT id, post_id, post_title, post_content 
            FROM {$this->table_name} 
            WHERE embedding IS NULL OR embedding = ''"
        );

        // Check if there are any posts to process.
        if (empty($posts)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ✅ All posts already have embeddings');
            }
            return array(
                'success' => true,
                'message' => __('All posts already have embeddings.', 'wp-posts-rag-manager')
            );
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   Found ' . count($posts) . ' posts to embed');
        }

        // Initialize counters.
        $success_count = 0;
        $error_count = 0;
        $errors = array();

        // Process each post.
        foreach ($posts as $post) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   Processing post ID: ' . $post->post_id);
            }

            // Create embedding text from title and content.
            // We truncate content to avoid exceeding API limits.
            $embedding_text = $post->post_title . "\n\n" . wp_trim_words($post->post_content, 500);

            // Get the embedding from OpenAI.
            $embedding = $this->get_openai_embedding($embedding_text, $api_key);

            // Check if embedding was generated successfully.
            if ($embedding !== false) {
                // Encode the embedding as JSON for storage.
                $embedding_json = json_encode($embedding);

                // Update the database record.
                $updated = $wpdb->update(
                    $this->table_name,
                    array(
                        'embedding'      => $embedding_json,
                        'last_embedded' => current_time('mysql')
                    ),
                    array('id' => $post->id),
                    array('%s', '%s'),
                    array('%d')
                );

                // Check if update was successful.
                if ($updated !== false) {
                    $success_count++;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('      ✅ Embedded post ID: ' . $post->post_id);
                    }
                } else {
                    $error_count++;
                    $errors[] = "Failed to update database for post ID {$post->post_id}";
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('      ❌ DB update failed: ' . $wpdb->last_error);
                    }
                }
            } else {
                $error_count++;
                $errors[] = "Failed to generate embedding for post ID {$post->post_id}";
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('      ❌ Embedding generation failed');
                }
            }

            // Small delay to avoid rate limiting.
            // OpenAI has rate limits, so we pause between requests.
            usleep(100000); // 0.1 second delay
        }

        // Build the result message.
        $message = sprintf(
            /* translators: %d is the number of successfully embedded posts */
            __('Generated embeddings for %d posts.', 'wp-posts-rag-manager'),
            $success_count
        );

        if ($error_count > 0) {
            $message .= sprintf(
                /* translators: %d is the number of errors */
                __(' %d errors occurred.', 'wp-posts-rag-manager'),
                $error_count
            );
        }

        // Log the completion.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Embedding complete: ' . $message);
        }

        return array(
            'success' => $success_count > 0,
            'message' => $message,
            'errors'  => $errors
        );
    }

    // ============================================================================
    // STATISTICS METHODS
    // ============================================================================

    /**
     * Get table statistics.
     *
     * This method retrieves various statistics about the RAG table,
     * including total posts, posts with embeddings, and index status.
     *
     * @since  1.7.0
     * @access public
     * @return array Statistics array.
     */
    public function get_stats() {
        // Log getting statistics.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('📊 Posts_RAG_Manager: Getting table statistics');
        }

        // Get the global WordPress database object.
        global $wpdb;

        // Get total row count.
        $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");

        // Get count of posts with valid embeddings.
        // We check for both NOT NULL and non-empty strings.
        $embedded_rows = $wpdb->get_var(
            "SELECT COUNT(*) 
            FROM {$this->table_name} 
            WHERE last_embedded IS NOT NULL 
            AND embedding IS NOT NULL 
            AND embedding != ''"
        );

        // Get fulltext index info.
        $index_info = $this->get_fulltext_index_info();

        // Build the statistics array.
        $stats = array(
            'total_posts'     => intval($total_rows),
            'embedded_posts' => intval($embedded_rows),
            'index_exists'    => ($index_info !== null),
            'index_name'      => $index_info ? $index_info['name'] : null,
            'index_columns'   => $index_info ? $index_info['columns'] : array()
        );

        // Log the statistics.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   Total posts: ' . $stats['total_posts']);
            error_log('   Embedded posts: ' . $stats['embedded_posts']);
            error_log('   Index exists: ' . ($stats['index_exists'] ? 'yes' : 'no'));
        }

        return $stats;
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    /**
     * Get the table name.
     *
     * Public method to allow other classes to access the table name.
     *
     * @since  1.7.0
     * @access public
     * @return string The table name.
     */
    public function get_table_name() {
        return $this->table_name;
    }

    /**
     * Get the option name for API key storage.
     *
     * @since  1.7.0
     * @access public
     * @return string The option name.
     */
    public function get_option_name() {
        return $this->option_name;
    }
}

// ============================================================================
// END OF CLASS
// ============================================================================
