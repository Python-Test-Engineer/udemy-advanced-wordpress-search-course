<?php
/**
 * Posts_RAG_REST_API Class
 *
 * This class handles all REST API functionality for the plugin.
 * It registers REST routes and handles search requests.
 *
 * @package WP_Posts_RAG_Manager
 * @subpackage Includes
 *
 * ============================================================================
 * REST API ENDPOINTS:
 * ============================================================================
 * 1. GET /wp-json/posts-rag/v1/search
 *    - Full-text search using MySQL FULLTEXT index
 *    - Parameters: query (required), limit (optional, default: 3)
 *
 * 2. GET /wp-json/posts-rag/v1/vector-search
 *    - Semantic search using OpenAI embeddings
 *    - Parameters: query (required), limit (optional, default: 3)
 * ============================================================================
 */

// Prevent direct access to this file.
if (!defined('ABSPATH')) {
    exit;
}

// Include the main manager class if not already loaded.
if (!class_exists('Posts_RAG_Manager')) {
    require_once WPRAG_PATH . 'includes/class-posts-rag-manager.php';
}

// ============================================================================
// CLASS DEFINITION
// ============================================================================

/**
 * Posts_RAG_REST_API Class
 *
 * Handles all REST API functionality.
 * Uses singleton pattern to ensure only one instance exists.
 *
 * @since 1.7.0
 * @access public
 */
class Posts_RAG_REST_API {

    // ============================================================================
    // CLASS PROPERTIES
    // ============================================================================

    /**
     * Reference to the main manager class instance.
     *
     * @since 1.7.0
     * @access private
     * @var Posts_RAG_Manager
     */
    private $manager;

    /**
     * The plugin's text domain for translations.
     *
     * @since 1.7.0
     * @access private
     * @var string
     */
    private $text_domain = 'wp-posts-rag-manager';

    /**
     * The REST API namespace.
     *
     * @since 1.7.0
     * @access private
     * @var string
     */
    private $namespace = 'posts-rag/v1';

    // ============================================================================
    // SINGLETON PATTERN
    // ============================================================================

    /**
     * The single instance of the class.
     *
     * @since 1.7.0
     * @access private
     * @var Posts_RAG_REST_API
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since  1.7.0
     * @access public
     * @static
     * @return Posts_RAG_REST_API The singleton instance.
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
    // CONSTRUCTOR & INITIALIZATION
    // ============================================================================

    /**
     * Constructor method.
     *
     * Initializes the REST API class and registers routes.
     *
     * @since 1.7.0
     * @access public
     */
    public function __construct() {
        // Log constructor call.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('📝 Posts_RAG_REST_API: Constructor called');
        }

        // Get the main manager instance.
        $this->manager = Posts_RAG_Manager::get_instance();

        // Register REST API routes.
        // This makes the endpoints available.
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Log successful initialization.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('✅ Posts_RAG_REST_API: Initialized successfully');
        }
    }

    // ============================================================================
    // REST ROUTE REGISTRATION
    // ============================================================================

    /**
     * Register REST API routes.
     *
     * This method registers the REST endpoints that the plugin provides.
     * Each endpoint has a callback function that handles the request.
     *
     * @since  1.7.0
     * @access public
     * @return void
     */
    public function register_rest_routes() {
        // Log registering routes.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🛣️  Posts_RAG_REST_API: Registering REST routes');
        }

        // -------------------------------------------------------------------------
        // Full-Text Search Endpoint
        // -------------------------------------------------------------------------
        // Register GET /wp-json/posts-rag/v1/search
        // This endpoint performs keyword-based full-text search.
        register_rest_route($this->namespace, '/search', array(
            'methods'             => 'GET',  // Only allow GET requests.
            'callback'            => array($this, 'rest_search_posts'),  // Callback function.
            'permission_callback' => '__return_true',  // Allow public access (can be restricted).
            'args'                => array(
                'query' => array(
                    'required'          => true,  // This parameter is required.
                    'type'              => 'string',
                    'description'       => __('Search query string', $this->text_domain),
                    'sanitize_callback' => 'sanitize_text_field',  // Sanitize input.
                ),
                'limit' => array(
                    'required'          => false,
                    'type'              => 'integer',
                    'default'           => 3,
                    'description'       => __('Number of results to return', $this->text_domain),
                    'sanitize_callback' => 'absint',  // Ensure positive integer.
                ),
            ),
        ));

        // Log the full-text search route registration.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Registered: GET /' . $this->namespace . '/search');
        }

        // -------------------------------------------------------------------------
        // Vector Search Endpoint
        // -------------------------------------------------------------------------
        // Register GET /wp-json/posts-rag/v1/vector-search
        // This endpoint performs semantic similarity search using embeddings.
        register_rest_route($this->namespace, '/vector-search', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_vector_search'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'query' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => __('Search query string', $this->text_domain),
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'limit' => array(
                    'required'          => false,
                    'type'              => 'integer',
                    'default'           => 3,
                    'description'       => __('Number of results to return (1-20)', $this->text_domain),
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));

        // Log the vector search route registration.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Registered: GET /' . $this->namespace . '/vector-search');
        }
    }

    // ============================================================================
    // REST CALLBACK METHODS
    // ============================================================================

    /**
     * REST API callback: Full-text search.
     *
     * This handles requests to /wp-json/posts-rag/v1/search
     * It performs a MySQL FULLTEXT search and returns results.
     *
     * @since  1.7.0
     * @access public
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function rest_search_posts($request) {
        // Log the search request.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔎 REST API: Full-text search request received');
            error_log('   Query: ' . $request->get_param('query'));
            error_log('   Limit: ' . $request->get_param('limit'));
        }

        // Get parameters from the request.
        $query = $request->get_param('query');
        $limit = $request->get_param('limit');

        // Validate the query parameter.
        if (empty($query)) {
            // Return an error if query is empty.
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Empty query parameter');
            }
            return new WP_Error(
                'invalid_query',  // Error code.
                __('Query parameter is required', $this->text_domain),  // Error message.
                array('status' => 400)  // HTTP status code.
            );
        }

        // Ensure limit is within valid range (1-20).
        $limit = max(1, min(20, $limit));

        // Check if fulltext index exists.
        $index_exists = $this->manager->check_fulltext_index();

        if (!$index_exists) {
            // Return error if index doesn't exist.
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Full-text index not created');
            }
            return new WP_Error(
                'no_index',
                __('Full-text index not created. Please create it from the admin panel.', $this->text_domain),
                array('status' => 500)
            );
        }

        // Perform the full-text search.
        $results = $this->manager->fulltext_search($query, $limit);

        // Check if we got any results.
        if (empty($results)) {
            // Return empty results.
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ℹ️  No results found');
            }
            return array(
                'success' => true,
                'query'   => $query,
                'method'  => 'fulltext_search',
                'results' => array(),
                'count'   => 0
            );
        }

        // Format the results.
        $formatted_results = array();

        foreach ($results as $row) {
            // Build the formatted result array.
            $formatted_results[] = array(
                'post_id'          => intval($row->post_id),
                'post_title'      => $row->post_title,
                'relevance_score' => floatval($row->relevance_score),
                'categories'      => $row->categories,
                'tags'            => $row->tags,
                'excerpt'         => wp_trim_words($row->post_content, 30)
            );
        }

        // Log the results.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Found ' . count($formatted_results) . ' results');
        }

        // Return the formatted results.
        return array(
            'success' => true,
            'query'   => $query,
            'method'  => 'fulltext_search',
            'results' => $formatted_results,
            'count'   => count($formatted_results)
        );
    }

    /**
     * REST API callback: Vector search.
     *
     * This handles requests to /wp-json/posts-rag/v1/vector-search
     * It performs semantic similarity search using OpenAI embeddings.
     *
     * @since  1.7.0
     * @access public
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function rest_vector_search($request) {
        // Log the search request.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔬 REST API: Vector search request received');
            error_log('   Query: ' . $request->get_param('query'));
            error_log('   Limit: ' . $request->get_param('limit'));
        }

        // Get parameters from the request.
        $query = $request->get_param('query');
        $limit = $request->get_param('limit');

        // Validate the query parameter.
        if (empty($query)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Empty query parameter');
            }
            return new WP_Error(
                'invalid_query',
                __('Query parameter is required', $this->text_domain),
                array('status' => 400)
            );
        }

        // Ensure limit is within valid range (1-20).
        $limit = max(1, min(20, $limit));

        // Perform the vector search.
        $result = $this->manager->vector_search($query, $limit);

        // Check if the search was successful.
        if (!$result['success']) {
            // Return error if something went wrong.
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Vector search failed: ' . $result['message']);
            }
            return new WP_Error(
                'search_failed',
                $result['message'],
                array('status' => 500)
            );
        }

        // Log the results.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Found ' . count($result['results']) . ' results');
        }

        // Return the results.
        return array(
            'success' => true,
            'query'   => $query,
            'method'  => 'vector_search',
            'results' => $result['results'],
            'count'   => count($result['results'])
        );
    }
}

// ============================================================================
// END OF CLASS
// ============================================================================
