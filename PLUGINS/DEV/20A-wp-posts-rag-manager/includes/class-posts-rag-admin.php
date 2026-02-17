<?php
/**
 * Posts_RAG_Admin Class
 *
 * This class handles all admin interface functionality including:
 * - Admin menu pages (main and search testing)
 * - AJAX handlers for all admin actions
 * - Admin scripts and styles enqueueing
 * - HTML output for admin pages
 *
 * @package WP_Posts_RAG_Manager
 * @subpackage Includes
 *
 * ============================================================================
 * ADMIN PAGES:
 * ============================================================================
 * 1. Main Plugin Page (posts-rag-manager)
 *    - OpenAI API key configuration
 *    - Table statistics display
 *    - Posts sync functionality
 *    - Full-text index management
 *    - Embeddings generation
 *    - REST API endpoints display
 *
 * 2. Search Testing Page (posts-rag-search-testing)
 *    - Full-text search testing
 *    - Vector search testing
 *    - Side-by-side comparison
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
 * Posts_RAG_Admin Class
 *
 * Handles all admin interface functionality.
 * Uses singleton pattern to ensure only one instance exists.
 *
 * @since 1.7.0
 * @access public
 */
class Posts_RAG_Admin {

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

    // ============================================================================
    // SINGLETON PATTERN
    // ============================================================================

    /**
     * The single instance of the class.
     *
     * @since 1.7.0
     * @access private
     * @var Posts_RAG_Admin
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @since  1.7.0
     * @access public
     * @static
     * @return Posts_RAG_Admin The singleton instance.
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
     * Initializes the admin class, sets up hooks for admin menu,
     * AJAX handlers, and script/styles enqueueing.
     *
     * @since 1.7.0
     * @access public
     */
    public function __construct() {
        // Log constructor call.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('📝 Posts_RAG_Admin: Constructor called');
        }

        // Get the main manager instance.
        $this->manager = Posts_RAG_Manager::get_instance();

        // Set up admin menu.
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Set up AJAX handlers.
        // These handle asynchronous requests from the admin pages.
        $this->setup_ajax_handlers();

        // Enqueue admin scripts and styles.
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Log successful initialization.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('✅ Posts_RAG_Admin: Initialized successfully');
        }
    }

    /**
     * Set up all AJAX handlers.
     *
     * AJAX allows the admin pages to communicate with the server
     * without reloading the page. Each action has a corresponding
     * handler method in this class.
     *
     * @since  1.7.0
     * @access private
     * @return void
     */
    private function setup_ajax_handlers() {
        // Log setting up AJAX handlers.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔌 Posts_RAG_Admin: Setting up AJAX handlers');
        }

        // Save OpenAI API key.
        // Called when user saves their API key in the settings.
        add_action('wp_ajax_wprag_save_openai_key', array($this, 'ajax_save_openai_key'));

        // Sync posts to RAG table.
        // Called when user clicks the sync button.
        add_action('wp_ajax_wprag_sync_posts', array($this, 'ajax_sync_posts'));

        // Generate embeddings for posts.
        // Called when user clicks the generate embeddings button.
        add_action('wp_ajax_wprag_generate_embeddings', array($this, 'ajax_generate_embeddings'));

        // Create full-text index.
        // Called when user creates a FULLTEXT index.
        add_action('wp_ajax_wprag_create_fulltext_index', array($this, 'ajax_create_fulltext_index'));

        // Delete full-text index.
        // Called when user deletes the FULLTEXT index.
        add_action('wp_ajax_wprag_delete_fulltext_index', array($this, 'ajax_delete_fulltext_index'));

        // Get full-text index info.
        // Called to check if index exists.
        add_action('wp_ajax_wprag_get_fulltext_index_info', array($this, 'ajax_get_fulltext_index_info'));

        // Get RAG statistics.
        // Called to refresh the stats display.
        add_action('wp_ajax_wprag_get_stats', array($this, 'ajax_get_stats'));

        // Log completion.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ AJAX handlers registered');
        }
    }

    // ============================================================================
    // ADMIN MENU METHODS
    // ============================================================================

    /**
     * Add admin menu items.
     *
     * This method registers the plugin's admin menu pages in WordPress.
     * It creates:
     * 1. Main menu page (Posts RAG Manager)
     * 2. Submenu page (Search Testing)
     *
     * @since  1.7.0
     * @access public
     * @return void
     */
    public function add_admin_menu() {
        // Log adding admin menu.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('📋 Posts_RAG_Admin: Adding admin menu');
        }

        // Add the main menu page.
        // This is the primary page for managing the plugin.
        add_menu_page(
            __('Posts RAG Manager', $this->text_domain),  // Page title
            __('20A POSTS RAG', $this->text_domain),     // Menu title
            'manage_options',                               // Capability required
            'posts-rag-manager',                          // Menu slug
            array($this, 'render_main_page'),             // Callback function
            'dashicons-search',                          // Icon (WordPress dashicon)
            4.1                                           // Menu position
        );

        // Add the search testing submenu page (fresh implementation).
        // This allows testing both search methods with a clean slate.
        add_submenu_page(
            'posts-rag-manager',                         // Parent slug
            __('Search Testing (New)', $this->text_domain),    // Page title
            __('Search Testing (New)', $this->text_domain),   // Menu title
            'manage_options',                            // Capability required
            'posts-rag-search-testing-new',              // New menu slug
            array($this, 'render_search_testing_page')   // Callback function
        );

        // Log completion.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Admin menu added');
        }
    }

    // ============================================================================
    // SCRIPT & STYLE ENQUEUEING
    // ============================================================================

    /**
     * Enqueue admin scripts and styles.
     *
     * This method loads JavaScript and CSS files only on the
     * plugin's admin pages for better performance.
     *
     * @since  1.7.0
     * @access public
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_admin_scripts($hook) {
        // Log script enqueueing.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('📜 Posts_RAG_Admin: Checking script enqueue for hook: ' . $hook);
        }

        // Always enqueue on admin pages to ensure the new subpage loads scripts reliably.
        // This avoids issues with mismatched hook/screen IDs.
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $screen_id = $screen ? $screen->id : '';

        // Enqueue jQuery (WordPress includes it by default).
        wp_enqueue_script('jquery');

        // Enqueue our main admin JavaScript file.
        // This contains all the AJAX logic and UI interactions.
        wp_enqueue_script(
            'wprag-admin-main',                          // Handle (unique identifier)
            WPRAG_URL . 'assets/js/admin-main.js',      // Source URL
            array('jquery'),                            // Dependencies
            WPRAG_VERSION,                              // Version number
            true                                        // Load in footer (better performance)
        );

        // Enqueue our search testing JavaScript file (fresh implementation).
        wp_enqueue_script(
            'wprag-admin-search-testing-new',
            WPRAG_URL . 'assets/js/admin-search-testing-new.js',
            array('jquery'),
            WPRAG_VERSION,
            true
        );

        // Enqueue our admin CSS file.
        // This contains all the styling for our admin pages.
        wp_enqueue_style(
            'wprag-admin-styles',                       // Handle
            WPRAG_URL . 'assets/css/admin-styles.css',  // Source URL
            array(),                                    // Dependencies
            WPRAG_VERSION                               // Version number
        );

        // Localize script to pass PHP variables to JavaScript.
        // This allows JavaScript to access AJAX URL and other PHP data.
        wp_localize_script(
            'wprag-admin-main',
            'wprag_ajax',                               // JavaScript object name
            array(
                'ajax_url'     => admin_url('admin-ajax.php'),  // WordPress AJAX URL
                'nonce'        => wp_create_nonce('wprag_nonce'), // Security nonce
                'fts_endpoint' => rest_url('posts-rag/v1/search'),  // FTS endpoint
                'vector_endpoint' => rest_url('posts-rag/v1/vector-search'),  // Vector search endpoint
                'i18n'         => array(
                    // Translation strings for JavaScript.
                    'saving'       => __('Saving...', $this->text_domain),
                    'saved'        => __('Saved!', $this->text_domain),
                    'error'        => __('Error', $this->text_domain),
                    'confirm'      => __('Are you sure?', $this->text_domain),
                    'processing'   => __('Processing...', $this->text_domain),
                    'success'      => __('Success!', $this->text_domain),
                    'failed'       => __('Failed', $this->text_domain),
                )
            )
        );

        // Also localize the search testing script with the endpoints.
        wp_localize_script(
            'wprag-admin-search-testing-new',
            'wprag_search_testing',
            array(
                'ajax_url'        => admin_url('admin-ajax.php'),
                'nonce'           => wp_create_nonce('wprag_nonce'),
                'fts_endpoint'    => rest_url('posts-rag/v1/search'),
                'vector_endpoint' => rest_url('posts-rag/v1/vector-search'),
                'i18n'            => array(
                    'success' => __('Success!', $this->text_domain),
                    'error'   => __('Error', $this->text_domain)
                )
            )
        );

        // Log completion.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Scripts and styles enqueued');
        }
    }

    // ============================================================================
    // AJAX HANDLER METHODS
    // ============================================================================

    /**
     * AJAX handler: Save OpenAI API key.
     *
     * Saves the OpenAI API key to WordPress options.
     * Requires 'manage_options' capability (usually administrator).
     *
     * @since  1.7.0
     * @access public
     * @return void
     */
    public function ajax_save_openai_key() {
        // Log the AJAX call.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔌 AJAX: wprag_save_openai_key called');
        }

        // Verify the nonce for security.
        // This prevents CSRF (Cross-Site Request Forgery) attacks.
        if (!wp_verify_nonce($_POST['nonce'], 'wprag_nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Nonce verification failed');
            }
            wp_send_json_error(__('Security check failed.', $this->text_domain));
        }

        // Check user capabilities.
        // Only administrators should be able to save the API key.
        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ User capability check failed');
            }
            wp_send_json_error(__('Unauthorized access.', $this->text_domain));
        }

        // Get and sanitize the API key from POST data.
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

        // Save the API key to WordPress options.
        update_option($this->manager->get_option_name(), $api_key);

        // Log success.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ API key saved successfully');
        }

        // Send success response.
        wp_send_json_success(__('API Key saved successfully.', $this->text_domain));
    }

    /**
     * AJAX handler: Sync posts to RAG table.
     *
     * Synchronizes published WordPress posts to the custom RAG table.
     *
     * @since  1.7.0
     * @access public
     * @return void
     */
    public function ajax_sync_posts() {
        // Log the AJAX call.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔌 AJAX: wprag_sync_posts called');
        }

        // Verify nonce.
        if (!wp_verify_nonce($_POST['nonce'], 'wprag_nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Nonce verification failed');
            }
            wp_send_json_error(__('Security check failed.', $this->text_domain));
        }

        // Check capabilities.
        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ User capability check failed');
            }
            wp_send_json_error(__('Unauthorized access.', $this->text_domain));
        }

        // Call the sync method from the manager class.
        $synced = $this->manager->sync_posts_to_table();

        // Log result.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Synced ' . $synced . ' posts');
        }

        // Send success response with count.
        wp_send_json_success(sprintf(
            __('Synced %d posts to RAG table.', $this->text_domain),
            $synced
        ));
    }

    /**
     * AJAX handler: Generate embeddings.
     *
     * Generates OpenAI embeddings for posts that don't have them yet.
     *
     * @since  1.7.0
     * @access public
     * @return void
     */
    public function ajax_generate_embeddings() {
        // Log the AJAX call.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔌 AJAX: wprag_generate_embeddings called');
        }

        // Verify nonce.
        if (!wp_verify_nonce($_POST['nonce'], 'wprag_nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Nonce verification failed');
            }
            wp_send_json_error(__('Security check failed.', $this->text_domain));
        }

        // Check capabilities.
        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ User capability check failed');
            }
            wp_send_json_error(__('Unauthorized access.', $this->text_domain));
        }

        // Call the generate embeddings method from the manager class.
        $result = $this->manager->generate_embeddings();

        // Check result and send appropriate response.
        if ($result['success']) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ✅ Embeddings generated: ' . $result['message']);
            }
            wp_send_json_success($result['message']);
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Embedding generation failed: ' . $result['message']);
            }
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX handler: Create full-text index.
     *
     * Creates a MySQL FULLTEXT index on selected fields.
     *
     * @since  1.7.0
     * @access public
     * @return void
     */
    public function ajax_create_fulltext_index() {
        // Log the AJAX call.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔌 AJAX: wprag_create_fulltext_index called');
        }

        // Verify nonce.
        if (!wp_verify_nonce($_POST['nonce'], 'wprag_nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Nonce verification failed');
            }
            wp_send_json_error(__('Security check failed.', $this->text_domain));
        }

        // Check capabilities.
        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ User capability check failed');
            }
            wp_send_json_error(__('Unauthorized access.', $this->text_domain));
        }

        // Get selected fields from POST data.
        $fields = array();

        // Check if post_title should be indexed.
        if (isset($_POST['index_title']) && $_POST['index_title'] === 'true') {
            $fields[] = 'post_title';
        }

        // Check if post_content should be indexed.
        if (isset($_POST['index_content']) && $_POST['index_content'] === 'true') {
            $fields[] = 'post_content';
        }

        // Validate that at least one field is selected.
        if (empty($fields)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ No fields selected for indexing');
            }
            wp_send_json_error(__('Please select at least one field to index.', $this->text_domain));
        }

        // Log selected fields.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   Fields: ' . implode(', ', $fields));
        }

        // Call the create index method from the manager class.
        $result = $this->manager->create_fulltext_index($fields);

        // Check result and send appropriate response.
        if ($result['success']) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ✅ Index created: ' . $result['message']);
            }
            wp_send_json_success($result);
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Index creation failed: ' . $result['message']);
            }
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX handler: Delete full-text index.
     *
     * Deletes the existing FULLTEXT index from the table.
     *
     * @since  1.7.0
     * @access public
     * @return void
     */
    public function ajax_delete_fulltext_index() {
        // Log the AJAX call.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔌 AJAX: wprag_delete_fulltext_index called');
        }

        // Verify nonce.
        if (!wp_verify_nonce($_POST['nonce'], 'wprag_nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Nonce verification failed');
            }
            wp_send_json_error(__('Security check failed.', $this->text_domain));
        }

        // Check capabilities.
        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ User capability check failed');
            }
            wp_send_json_error(__('Unauthorized access.', $this->text_domain));
        }

        // Call the delete index method from the manager class.
        $result = $this->manager->delete_fulltext_index();

        // Check result and send appropriate response.
        if ($result['success']) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ✅ Index deleted: ' . $result['message']);
            }
            wp_send_json_success($result['message']);
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Index deletion failed: ' . $result['message']);
            }
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX handler: Get full-text index info.
     *
     * Returns information about the current FULLTEXT index.
     *
     * @since  1.7.0
     * @access public
     * @return void
     */
    public function ajax_get_fulltext_index_info() {
        // Log the AJAX call.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔌 AJAX: wprag_get_fulltext_index_info called');
        }

        // Verify nonce.
        if (!wp_verify_nonce($_POST['nonce'], 'wprag_nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Nonce verification failed');
            }
            wp_send_json_error(__('Security check failed.', $this->text_domain));
        }

        // Check capabilities.
        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ User capability check failed');
            }
            wp_send_json_error(__('Unauthorized access.', $this->text_domain));
        }

        // Get index info from manager.
        $index_info = $this->manager->get_fulltext_index_info();

        // Check if index exists.
        if ($index_info) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ✅ Index exists: ' . $index_info['name']);
            }
            wp_send_json_success($index_info);
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ℹ️  No index exists');
            }
            wp_send_json_error(__('No index exists', $this->text_domain));
        }
    }

    /**
     * AJAX handler: Get RAG statistics.
     *
     * Returns current statistics about the RAG table.
     *
     * @since  1.7.0
     * @access public
     * @return void
     */
    public function ajax_get_stats() {
        // Log the AJAX call.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔌 AJAX: wprag_get_stats called');
        }

        // Verify nonce.
        if (!wp_verify_nonce($_POST['nonce'], 'wprag_nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ Nonce verification failed');
            }
            wp_send_json_error(__('Security check failed.', $this->text_domain));
        }

        // Check capabilities.
        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   ❌ User capability check failed');
            }
            wp_send_json_error(__('Unauthorized access.', $this->text_domain));
        }

        // Get stats from manager.
        $stats = $this->manager->get_stats();

        // Log stats.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Stats retrieved');
            error_log('      Total posts: ' . $stats['total_posts']);
            error_log('      Embedded: ' . $stats['embedded_posts']);
            error_log('      Index exists: ' . ($stats['index_exists'] ? 'yes' : 'no'));
        }

        // Send success with stats.
        wp_send_json_success($stats);
    }

    // ============================================================================
    // ADMIN PAGE RENDERING METHODS
    // ============================================================================

    /**
     * Render the main admin page.
     *
     * This outputs the HTML for the main plugin admin page.
     * It includes sections for API key, stats, sync, index management,
     * and embeddings generation.
     *
     * @since  1.7.0
     * @access public
     * @return void
     */
    public function render_main_page() {
        // Log page rendering.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('📄 Posts_RAG_Admin: Rendering main admin page');
        }

        // Check user capabilities.
        // This is a second layer of security.
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', $this->text_domain));
        }

        // Get current index info.
        $index_info = $this->manager->get_fulltext_index_info();
        $index_exists = ($index_info !== null);

        // Get current stats.
        $stats = $this->manager->get_stats();

        // Get the REST API endpoints for display.
        $search_endpoint = rest_url('posts-rag/v1/search');
        $vector_endpoint = rest_url('posts-rag/v1/vector-search');

        // Start output buffering to capture HTML.
        ?>
        <div class="wrap wprag-admin-wrap">
            <!-- Page Header -->
            <h1 class="wprag-page-title">
                <?php esc_html_e('Posts RAG Manager', $this->text_domain); ?>
                <span class="wprag-version">v<?php echo esc_html(WPRAG_VERSION); ?></span>
            </h1>

            <!-- Message Container -->
            <div id="wprag-message" class="notice" style="display: none;">
                <p></p>
            </div>

            <!-- API Configuration Card -->
            <div class="wprag-card">
                <h2><?php esc_html_e('OpenAI API Configuration', $this->text_domain); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="openai_api_key"><?php esc_html_e('OpenAI API Key', $this->text_domain); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="openai_api_key" 
                                   id="openai_api_key" 
                                   value="<?php echo esc_attr(get_option($this->manager->get_option_name())); ?>" 
                                   class="regular-text" 
                                   placeholder="sk-...">
                            <p class="description">
                                <?php esc_html_e('Enter your OpenAI API key to enable embeddings generation.', $this->text_domain); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="button" id="wprag-save-api-key-btn" class="button button-primary">
                        <?php esc_html_e('Save API Key', $this->text_domain); ?>
                    </button>
                </p>
            </div>

            <!-- Statistics Card -->
            <div class="wprag-card" style="margin-top: 20px;">
                <h2><?php esc_html_e('Table Statistics', $this->text_domain); ?></h2>
                <div id="wprag-stats-container">
                    <p><strong><?php esc_html_e('Total Posts in RAG Table:', $this->text_domain); ?></strong> 
                        <?php echo esc_html($stats['total_posts']); ?></p>
                    <p><strong><?php esc_html_e('Posts with Embeddings:', $this->text_domain); ?></strong> 
                        <?php echo esc_html($stats['embedded_posts']); ?></p>
                    <p><strong><?php esc_html_e('Full-Text Index:', $this->text_domain); ?></strong> 
                        <?php if ($stats['index_exists']): ?>
                            <span style="color: green;">✅ <?php echo esc_html($stats['index_name']); ?></span>
                        <?php else: ?>
                            <span style="color: red;">❌ <?php esc_html_e('Not Created', $this->text_domain); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Sync Posts Card -->
            <div class="wprag-card" style="margin-top: 20px;">
                <h2><?php esc_html_e('Sync Posts to RAG Table', $this->text_domain); ?></h2>
                <p><?php esc_html_e('Click the button below to sync all published posts to the RAG table.', $this->text_domain); ?></p>
                <button type="button" id="wprag-sync-posts-btn" class="button button-primary">
                    <?php esc_html_e('Sync Posts', $this->text_domain); ?>
                </button>
            </div>

            <!-- Full-Text Index Card -->
            <div class="wprag-card" style="margin-top: 20px;">
                <h2><?php esc_html_e('Full-Text Search Index', $this->text_domain); ?></h2>

                <div id="wprag-index-status" style="margin-bottom: 15px;">
                    <?php if ($index_exists): ?>
                        <p>
                            <strong><?php esc_html_e('Status:', $this->text_domain); ?></strong> 
                            <span style="color: green;">✅ <?php esc_html_e('Created', $this->text_domain); ?></span><br>
                            <strong><?php esc_html_e('Index Name:', $this->text_domain); ?></strong> 
                            <strong><?php echo esc_html($index_info['name']); ?></strong><br>
                            <strong><?php esc_html_e('Indexed Fields:', $this->text_domain); ?></strong> 
                            <strong><?php echo esc_html(implode(', ', $index_info['columns'])); ?></strong>
                        </p>
                    <?php else: ?>
                        <p>
                            <strong><?php esc_html_e('Status:', $this->text_domain); ?></strong> 
                            <span style="color: red;">❌ <?php esc_html_e('Not Created', $this->text_domain); ?></span>
                        </p>
                    <?php endif; ?>
                </div>

                <?php if (!$index_exists): ?>
                    <!-- Index Creation Form -->
                    <div id="wprag-index-creation-form">
                        <p><strong><?php esc_html_e('Select fields to include in the full-text search index:', $this->text_domain); ?></strong></p>
                        <p style="margin-left: 20px;">
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" id="wprag-index-title" checked> 
                                <strong>post_title</strong> - <?php esc_html_e('Post titles', $this->text_domain); ?>
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" id="wprag-index-content" checked> 
                                <strong>post_content</strong> - <?php esc_html_e('Post content/body', $this->text_domain); ?>
                            </label>
                        </p>
                        <button type="button" id="wprag-create-fulltext-btn" class="button button-primary">
                            <?php esc_html_e('Create Full-Text Index', $this->text_domain); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Index Management -->
                    <div id="wprag-index-management">
                        <button type="button" id="wprag-delete-fulltext-btn" class="button button-secondary">
                            <?php esc_html_e('Delete Index', $this->text_domain); ?>
                        </button>
                        <p class="description">
                            <?php esc_html_e('Delete the current index to create a new one with different field selections.', $this->text_domain); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Generate Embeddings Card -->
            <div class="wprag-card" style="margin-top: 20px;">
                <h2><?php esc_html_e('Generate Embeddings', $this->text_domain); ?></h2>
                <p><?php esc_html_e('Generate OpenAI embeddings for post titles and content combined. This will process all posts that don\'t have embeddings yet.', $this->text_domain); ?></p>
                <button type="button" id="wprag-generate-embeddings-btn" class="button button-primary">
                    <?php esc_html_e('Generate Embeddings', $this->text_domain); ?>
                </button>
            </div>

            <!-- REST API Endpoints Card -->
            <div class="wprag-card" style="margin-top: 20px;">
                <h2><?php esc_html_e('REST API Endpoints', $this->text_domain); ?></h2>

                <h3><?php esc_html_e('Full-Text Search', $this->text_domain); ?></h3>
                <p><?php esc_html_e('Search using MySQL full-text index (keyword matching):', $this->text_domain); ?></p>
                <code><?php echo esc_url($search_endpoint); ?>?query=FOAM&limit=3</code>

                <h3 style="margin-top: 15px;"><?php esc_html_e('Vector Search', $this->text_domain); ?></h3>
                <p><?php esc_html_e('Search using semantic similarity (requires embeddings):', $this->text_domain); ?></p>
                <code><?php echo esc_url($vector_endpoint); ?>?query=FOAM&limit=3</code>

                <p class="description" style="margin-top: 10px;">
                    <strong><?php esc_html_e('Parameters:', $this->text_domain); ?></strong> 
                    <strong>query</strong> (<?php esc_html_e('required', $this->text_domain); ?>), 
                    <strong>limit</strong> (<?php esc_html_e('optional, default: 3, max: 20', $this->text_domain); ?>)
                </p>
            </div>
        </div>
        <?php

        // Log page rendered.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Main page rendered');
        }
    }

    /**
     * Render the search testing admin page.
     *
     * This outputs the HTML for the search testing page where
     * users can test both FTS and vector search methods.
     *
     * @since  1.7.0
     * @access public
     * @return void
     */
    public function render_search_testing_page() {
        // Log page rendering.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('📄 Posts_RAG_Admin: Rendering search testing page');
        }

        // Check user capabilities.
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', $this->text_domain));
        }

        // Get REST API endpoints.
        $fts_endpoint = rest_url('posts-rag/v1/search');
        $vector_endpoint = rest_url('posts-rag/v1/vector-search');

        // Output the page HTML.
        ?>
        <div class="wrap wprag-admin-wrap">
            <!-- Page Header -->
            <h1 class="wprag-page-title"><?php esc_html_e('Search Testing', $this->text_domain); ?></h1>

            <!-- Message Container -->
            <div id="wprag-search-message" class="notice" style="display: none; margin: 15px 0;">
                <p></p>
            </div>

            <!-- Full-Text Search Testing Card -->
            <div class="wprag-card" style="margin-top: 20px;">
                <h2><?php esc_html_e('Full-Text Search (FTS) Testing', $this->text_domain); ?></h2>
                <p><?php esc_html_e('Test the MySQL full-text search functionality. Requires a full-text index to be created.', $this->text_domain); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="fts-query"><?php esc_html_e('Search Query', $this->text_domain); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wprag-fts-query" class="regular-text" placeholder="<?php esc_attr_e('Enter search term...', $this->text_domain); ?>" value="foam">
                            <p class="description"><?php esc_html_e('Enter keywords to search for (e.g., "foam", "materials")', $this->text_domain); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="fts-limit"><?php esc_html_e('Result Limit', $this->text_domain); ?></label>
                        </th>
                        <td>
                            <input type="number" id="wprag-fts-limit" value="3" min="1" max="20" style="width: 80px;">
                            <p class="description"><?php esc_html_e('Number of results to return (1-20)', $this->text_domain); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="button" id="wprag-test-fts-btn" class="button button-primary">
                        <?php esc_html_e('Test Full-Text Search', $this->text_domain); ?>
                    </button>
                </p>

                <!-- Results Container -->
                <div id="wprag-fts-results" style="margin-top: 20px; display: none;">
                    <h3><?php esc_html_e('Results:', $this->text_domain); ?></h3>
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto;">
                        <pre id="wprag-fts-json" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                    </div>
                </div>

                <!-- Endpoint Display -->
                <div style="margin-top: 15px;">
                    <strong><?php esc_html_e('API Endpoint:', $this->text_domain); ?></strong><br>
                    <code><?php echo esc_url($fts_endpoint); ?>?query=foam&limit=3</code>
                </div>
            </div>

            <!-- Vector Search Testing Card -->
            <div class="wprag-card" style="margin-top: 20px;">
                <h2><?php esc_html_e('Vector Search Testing', $this->text_domain); ?></h2>
                <p><?php esc_html_e('Test the semantic similarity search using OpenAI embeddings. Requires embeddings to be generated.', $this->text_domain); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="vector-query"><?php esc_html_e('Search Query', $this->text_domain); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wprag-vector-query" class="regular-text" placeholder="<?php esc_attr_e('Enter search phrase...', $this->text_domain); ?>" value="foam">
                            <p class="description"><?php esc_html_e('Enter a phrase or question for semantic search', $this->text_domain); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="vector-limit"><?php esc_html_e('Result Limit', $this->text_domain); ?></label>
                        </th>
                        <td>
                            <input type="number" id="wprag-vector-limit" value="3" min="1" max="20" style="width: 80px;">
                            <p class="description"><?php esc_html_e('Number of results to return (1-20)', $this->text_domain); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="button" id="wprag-test-vector-btn" class="button button-primary">
                        <?php esc_html_e('Test Vector Search', $this->text_domain); ?>
                    </button>
                </p>

                <!-- Results Container -->
                <div id="wprag-vector-results" style="margin-top: 20px; display: none;">
                    <h3><?php esc_html_e('Results:', $this->text_domain); ?></h3>
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto;">
                        <pre id="wprag-vector-json" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                    </div>
                </div>

                <!-- Endpoint Display -->
                <div style="margin-top: 15px;">
                    <strong><?php esc_html_e('API Endpoint:', $this->text_domain); ?></strong><br>
                    <code><?php echo esc_url($vector_endpoint); ?>?query=sustainable+materials&limit=3</code>
                </div>
            </div>

            <!-- Comparison Testing Card -->
            <div class="wprag-card" style="margin-top: 20px;">
                <h2><?php esc_html_e('Compare Both Methods', $this->text_domain); ?></h2>
                <p><?php esc_html_e('Run the same query through both FTS and Vector search to compare results.', $this->text_domain); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="compare-query"><?php esc_html_e('Search Query', $this->text_domain); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wprag-compare-query" class="regular-text" placeholder="<?php esc_attr_e('Enter search term...', $this->text_domain); ?>" value="foam">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="button" id="wprag-compare-btn" class="button button-primary">
                        <?php esc_html_e('Compare Both Methods', $this->text_domain); ?>
                    </button>
                </p>
            </div>

            <!-- Comparison Results -->
            <div id="wprag-compare-results" class="wprag-card" style="margin-top: 20px; display: none;">
                <h2><?php esc_html_e('Comparison Results', $this->text_domain); ?></h2>
                <div class="wprag-compare-grid">
                    <div>
                        <h3><?php esc_html_e('Full-Text Search Results', $this->text_domain); ?></h3>
                        <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto;">
                            <pre id="wprag-compare-fts-json" style="white-space: pre-wrap; word-wrap: break-word; font-size: 12px;"></pre>
                        </div>
                    </div>
                    <div>
                        <h3><?php esc_html_e('Vector Search Results', $this->text_domain); ?></h3>
                        <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto;">
                            <pre id="wprag-compare-vector-json" style="white-space: pre-wrap; word-wrap: break-word; font-size: 12px;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

        // Log page rendered.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Search testing page rendered');
        }
    }
}

// ============================================================================
// END OF CLASS
// ============================================================================
