<?php
/**
 * Plugin Name:           20A - WP Posts RAG Manager
 * Plugin URI:            https://example.com/wp-posts-rag-manager
 * Description:           Manages a custom table for RAG (Retrieval-Augmented Generation) processing of WordPress posts with Full-Text Search and Vector Search capabilities.
 * Version:               1.7.0
 * Requires at least:    5.6
 * Requires PHP:         7.4
 * Author:                Your Name
 * Author URI:            https://example.com
 * License:               GPL v2 or later
 * License URI:           https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:           wp-posts-rag-manager
 * Domain Path:           /languages
 * Network:               false
 *
 * @package WP_Posts_RAG_Manager
 *
 * This plugin provides:
 * - Custom database table for RAG processing
 * - Full-Text Search (FTS) index management
 * - OpenAI embeddings generation for semantic/vector search
 * - REST API endpoints for both search methods
 * - Admin interface for configuration and testing
 *
 * ============================================================================
 * WORDPRESS CODING STANDARDS FOLLOWED:
 * ============================================================================
 * - Object-oriented PHP with proper class separation
 * - WordPress hooks (actions/filters) properly registered
 * - Nonce verification for all AJAX requests
 * - Capability checks for user permissions
 * - Properesc escaping (_html, esc_url, sanitize_text_field, etc.)
 * - Internationalization support (text domain)
 * - Proper activation/deactivation/uninstall hooks
 * - Version-based database schema management
 * ============================================================================
 */

// ============================================================================
// PREVENT DIRECT ACCESS
// ============================================================================
// This prevents the plugin file from being accessed directly by browsers.
// ABSPATH is defined by WordPress and is the path to the WordPress root.
if (!defined('ABSPATH')) {
    // translators: %s is the plugin name
    exit(__('Direct access to this plugin is not allowed.', 'wp-posts-rag-manager'));
}

// ============================================================================
// PLUGIN DEFINITIONS
// ============================================================================
// Define plugin constants for use throughout the plugin.
// These make it easy to reference plugin paths and URLs consistently.

/**
 * The current version of the plugin.
 * Used for enqueueing assets and database schema version checks.
 *
 * @since 1.7.0
 */
define('WPRAG_VERSION', '1.7.0');

/**
 * The plugin basename.
 * Used for plugin action links and settings.
 *
 * @since 1.7.0
 */
define('WPRAG_BASENAME', plugin_basename(__FILE__));

/**
 * The plugin directory path.
 * Used for including files and loading assets.
 *
 * @since 1.7.0
 */
define('WPRAG_PATH', plugin_dir_path(__FILE__));

/**
 * The plugin directory URL.
 * Used for enqueueing stylesheets and JavaScript files.
 *
 * @since 1.7.0
 */
define('WPRAG_URL', plugin_dir_url(__FILE__));

/**
 * The text domain for internationalization.
 * Used for translating strings throughout the plugin.
 *
 * @since 1.7.0
 */
define('WPRAG_TEXT_DOMAIN', 'wp-posts-rag-manager');

// ============================================================================
// INCLUDE REQUIRED CLASSES
// ============================================================================
// We use a modular approach with separate classes for different responsibilities.
// This makes the code more maintainable and follows WordPress best practices.

/**
 * Include the activator class.
 * Handles plugin activation, deactivation, and database setup.
 *
 * @since 1.7.0
 */
require_once WPRAG_PATH . 'includes/class-posts-rag-activator.php';

/**
 * Include the main plugin manager class.
 * Contains core functionality for RAG processing.
 *
 * @since 1.7.0
 */
require_once WPRAG_PATH . 'includes/class-posts-rag-manager.php';

/**
 * Include the admin interface class.
 * Handles all admin pages and AJAX handlers.
 *
 * @since 1.7.0
 */
require_once WPRAG_PATH . 'includes/class-posts-rag-admin.php';

/**
 * Include the REST API class.
 * Handles REST API endpoints for search functionality.
 *
 * @since 1.7.0
 */
require_once WPRAG_PATH . 'includes/class-posts-rag-rest-api.php';

// ============================================================================
// PLUGIN INITIALIZATION
// ============================================================================
// Initialize the plugin by creating instances of our classes.
// WordPress best practice is to use a singleton pattern or global instance.

/**
 * Main plugin initialization function.
 *
 * This function is called when WordPress loads the plugin.
 * It creates instances of all required classes and sets up hooks.
 *
 * @since  1.7.0
 * @access public
 * @return void
 */
function wprag_initialize_plugin() {
    // Log plugin initialization for debugging purposes.
    // This helps us track when the plugin starts in server logs.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('📦 ' . WPRAG_TEXT_DOMAIN . ': Initializing plugin version ' . WPRAG_VERSION);
    }

    // Create the main plugin instance.
    // This handles core functionality like database operations.
    $plugin_manager = Posts_RAG_Manager::get_instance();

    // Create the admin interface instance.
    // This handles all admin pages and AJAX handlers.
    $plugin_admin = Posts_RAG_Admin::get_instance();

    // Create the REST API instance.
    // This handles REST API endpoints.
    $plugin_rest_api = Posts_RAG_REST_API::get_instance();

    // Log successful initialization.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('✅ ' . WPRAG_TEXT_DOMAIN . ': Plugin initialized successfully');
        error_log('   - Manager class loaded');
        error_log('   - Admin class loaded');
        error_log('   - REST API class loaded');
    }
}
add_action('plugins_loaded', 'wprag_initialize_plugin', 10);

// ============================================================================
// ACTIVATION & DEACTIVATION HOOKS
// ============================================================================
// Register the activation and deactivation hooks.
// These are called when the plugin is activated or deactivated.

// ----------------------------------------------------------------------------
// ACTIVATION HOOK
// ----------------------------------------------------------------------------
// Called when the plugin is activated in WordPress admin.
// Creates database tables and sets default options.
register_activation_hook(__FILE__, array('Posts_RAG_Activator', 'activate'));

// ----------------------------------------------------------------------------
// DEACTIVATION HOOK
// ----------------------------------------------------------------------------
// Called when the plugin is deactivated.
// Cleans up temporary data if needed.
register_deactivation_hook(__FILE__, array('Posts_RAG_Activator', 'deactivate'));

// ----------------------------------------------------------------------------
// UNINSTALL HOOK
// ----------------------------------------------------------------------------
// Called when the plugin is completely deleted from WordPress.
// Should be in a separate file (uninstall.php) - included at the end.

// ============================================================================
// TRANSLATION LOADING
// ============================================================================
// Load the plugin text domain for internationalization.
// This allows the plugin strings to be translated into other languages.

/**
 * Load plugin translations.
 *
 * WordPress uses the text domain to find translation files.
 * Translation files should be in the /languages directory.
 *
 * @since 1.7.0
 */
function wprag_load_textdomain() {
    // Load the text domain for translations.
    // WordPress will look for files like: wp-posts-rag-manager-en_US.mo
    load_plugin_textdomain(
        WPRAG_TEXT_DOMAIN,  // The text domain identifier
        false,              // Deprecated parameter (false)
        dirname(WPRAG_BASENAME) . '/languages'  // Path to translation files
    );

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🌍 ' . WPRAG_TEXT_DOMAIN . ': Text domain loaded');
    }
}
add_action('plugins_loaded', 'wprag_load_textdomain', 5);

// ============================================================================
// GLOBAL ERROR HANDLING
// ============================================================================
// Add a shutdown handler to catch any fatal errors during plugin execution.
// This helps with debugging issues in production.

/**
 * Catch and log fatal errors during plugin execution.
 *
 * @since 1.7.0
 */
function wprag_shutdown_handler() {
    // Get the last error that occurred.
    $error = error_get_last();

    // Check if there was a fatal error.
    if ($error !== null && in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
        // Log the error for debugging.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('💥 ' . WPRAG_TEXT_DOMAIN . ': Fatal error - ' . $error['message']);
            error_log('   File: ' . $error['file'] . ' Line: ' . $error['line']);
        }
    }
}
add_action('shutdown', 'wprag_shutdown_handler');

// ============================================================================
// PLUGIN INFORMATION RETRIEVAL
// ============================================================================
// The following functions provide information about the plugin.
// They can be used by other plugins or themes to interact with this one.

// None required for this plugin - all functionality is accessed through
// the main Posts_RAG_Manager class instance.

// ============================================================================
// END OF MAIN PLUGIN FILE
// ============================================================================
// This is the end of the main plugin bootstrap file.
// All the real functionality is in the included classes in the /includes folder.
