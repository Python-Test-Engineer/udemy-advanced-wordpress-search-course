<?php
/**
 * Posts_RAG_Activator Class
 *
 * This class handles the activation, deactivation, and database setup
 * for the WP Posts RAG Manager plugin.
 *
 * @package WP_Posts_RAG_Manager
 * @subpackage Includes
 *
 * ============================================================================
 * ACTIVATION PROCESS:
 * ============================================================================
 * 1. Create/update the custom database table
 * 2. Set default plugin options
 * 3. Set the database schema version for future upgrades
 * 4. Schedule any required cron events
 * ============================================================================
 *
 * ============================================================================
 * DEACTIVATION PROCESS:
 * ============================================================================
 * 1. Clear any scheduled cron events
 * 2. Flush rewrite rules (if needed)
 * 3. Clear any temporary transients
 * ============================================================================
 *
 * ============================================================================
 * UNINSTALL PROCESS (in uninstall.php):
 * ============================================================================
 * 1. Remove the custom database table
 * 2. Remove all plugin options
 * 3. Remove any custom post types (if registered)
 * 4. Clean up any other plugin data
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
 * Posts_RAG_Activator Class
 *
 * Handles all plugin activation, deactivation, and database operations.
 * Uses a singleton pattern for database version management.
 *
 * @since 1.7.0
 * @access public
 */
class Posts_RAG_Activator {

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
     * The option name for storing the database schema version.
     * Used to track when schema updates are needed.
     *
     * @since 1.7.0
     * @access private
     * @var string
     */
    private $db_version_option = 'posts_rag_db_version';

    /**
     * The current database schema version.
     * Increment this when making changes to the database structure.
     *
     * @since 1.7.0
     * @access private
     * @var string
     */
    private $current_db_version = '1.7.0';

    /**
     * The option name for storing the OpenAI API key.
     *
     * @since 1.7.0
     * @access private
     * @var string
     */
    private $option_name = 'posts_rag_openai_key';

    // ============================================================================
    // CONSTRUCTOR
    // ============================================================================

    /**
     * Constructor method.
     *
     * Initializes the class and sets up the table name based on WordPress prefix.
     * This is called when the class is instantiated.
     *
     * @since 1.7.0
     * @access public
     */
    public function __construct() {
        // Get the global WordPress database object.
        global $wpdb;

        // Set the table name with WordPress prefix (e.g., wp_posts_rag).
        $this->table_name = $wpdb->prefix . 'posts_rag';

        // Log constructor call if debug mode is enabled.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('📝 Posts_RAG_Activator: Constructor called');
            error_log('   Table name: ' . $this->table_name);
            error_log('   DB version option: ' . $this->db_version_option);
        }
    }

    // ============================================================================
    // STATIC METHODS FOR WORDPRESS HOOKS
    // ============================================================================

    /**
     * Main activation method - called when plugin is activated.
     *
     * This is the entry point for the plugin activation process.
     * It creates the database table if it doesn't exist and sets up
     * the initial plugin configuration.
     *
     * @since  1.7.0
     * @access public
     * @static
     * @return void
     */
    public static function activate() {
        // Log the activation start.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🚀 Posts_RAG_Activator: Starting activation process');
        }

        // Create a new instance to handle the activation.
        // Using self::get_instance() ensures we have access to all methods.
        $instance = self::get_instance();

        // Run the activation tasks.
        $instance->create_database_table();
        $instance->set_default_options();
        $instance->set_db_version();
        $instance->schedule_cron_events();

        // Log successful activation.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('✅ Posts_RAG_Activator: Activation completed successfully');
        }

        // Flush WordPress rewrite rules (in case we add custom endpoints).
        // This ensures that any new URL structures are recognized.
        flush_rewrite_rules();
    }

    /**
     * Main deactivation method - called when plugin is deactivated.
     *
     * This is the entry point for the plugin deactivation process.
     * It cleans up temporary data and scheduled events.
     *
     * @since  1.7.0
     * @access public
     * @static
     * @return void
     */
    public static function deactivate() {
        // Log the deactivation start.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔄 Posts_RAG_Activator: Starting deactivation process');
        }

        // Create a new instance to handle the deactivation.
        $instance = self::get_instance();

        // Clear any scheduled cron events.
        $instance->clear_scheduled_events();

        // Clear any transients (temporary cached data).
        $instance->clear_transients();

        // Log successful deactivation.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('✅ Posts_RAG_Activator: Deactivation completed successfully');
        }

        // Flush rewrite rules to remove any custom endpoints.
        flush_rewrite_rules();
    }

    /**
     * Get the singleton instance of this class.
     *
     * This follows the singleton pattern to ensure we only have
     * one instance of this class running at any time.
     *
     * @since  1.7.0
     * @access public
     * @static
     * @return Posts_RAG_Activator The singleton instance.
     */
    public static function get_instance() {
        // Static variable to hold the single instance.
        static $instance = null;

        // If no instance exists, create one.
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    // ============================================================================
    // DATABASE TABLE METHODS
    // ============================================================================

    /**
     * Create or update the custom database table.
     *
     * This method creates the posts_rag table if it doesn't exist.
     * It uses WordPress dbDelta() function for safe table creation
     * and can update existing tables.
     *
     * Database schema:
     * - id: Auto-increment primary key
     * - post_id: WordPress post ID (unique)
     * - post_title: Post title
     * - post_content: Full post content
     * - categories: Comma-separated category names
     * - tags: Comma-separated tag names
     * - custom_meta_data: Custom field values
     * - embedding: JSON-encoded OpenAI embedding vector
     * - last_embedded: Timestamp of last embedding generation
     *
     * @since  1.7.0
     * @access private
     * @return void
     */
    private function create_database_table() {
        // Log the start of table creation.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('📊 Posts_RAG_Activator: Creating database table');
        }

        // Get the global WordPress database object.
        global $wpdb;

        // Get the character set and collation for the database.
        // This ensures proper UTF-8 support.
        $charset_collate = $wpdb->get_charset_collate();

        // Define the SQL to create the table.
        // We use IF NOT EXISTS to prevent errors if table already exists.
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
            PRIMARY KEY  (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";

        // Include WordPress database upgrade functions (dbDelta).
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Execute the SQL and create/update the table.
        // dbDelta() is smart - it compares the existing table structure
        // with what we want and makes only necessary changes.
        $result = dbDelta($sql);

        // Log the result for debugging.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (!empty($result)) {
                foreach ($result as $message) {
                    error_log('   DB Message: ' . $message);
                }
            } else {
                error_log('   DB: Table already exists, no changes needed');
            }
        }
    }

    // ============================================================================
    // OPTIONS METHODS
    // ============================================================================

    /**
     * Set default plugin options.
     *
     * This method sets up any default options the plugin needs.
     * Options are stored in the wp_options table.
     *
     * @since  1.7.0
     * @access private
     * @return void
     */
    private function set_default_options() {
        // Log setting default options.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('⚙️  Posts_RAG_Activator: Setting default options');
        }

        // Check if the OpenAI API key option exists.
        // If not, create it with an empty value.
        if (false === get_option($this->option_name)) {
            // Add the option with an empty default value.
            add_option($this->option_name, '', '', 'no');

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   Added option: ' . $this->option_name);
            }
        }

        // Add any other default options here as needed.
        // Example: add_option('wprag_settings', array(), '', 'no');
    }

    /**
     * Set the database schema version.
     *
     * This stores the current schema version in the database.
     * On future activations, we can compare this to see if
     * schema updates are needed.
     *
     * @since  1.7.0
     * @access private
     * @return void
     */
    private function set_db_version() {
        // Get the existing version (if any).
        $existing_version = get_option($this->db_version_option);

        // Log the version check.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('📋 Posts_RAG_Activator: Checking database version');
            error_log('   Current version in DB: ' . ($existing_version ? $existing_version : 'none'));
            error_log('   Target version: ' . $this->current_db_version);
        }

        // Update the version option.
        // 'no' means this option is not autoloaded (loaded only when needed).
        update_option($this->db_version_option, $this->current_db_version, 'no');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   Version option updated successfully');
        }
    }

    // ============================================================================
    // CRON SCHEDULING METHODS
    // ============================================================================

    /**
     * Schedule any required cron events.
     *
     * This method sets up scheduled tasks that the plugin needs.
     * For example, we might want to periodically regenerate embeddings
     * or clean up old data.
     *
     * @since  1.7.0
     * @access private
     * @return void
     */
    private function schedule_cron_events() {
        // Log cron scheduling.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('⏰ Posts_RAG_Activator: Scheduling cron events');
        }

        // Check if our cron hook is already scheduled.
        // We use 'wp_next_scheduled' to check if an event is pending.
        if (!wp_next_scheduled('wprag_daily_cleanup')) {
            // Schedule a daily event.
            // This will run once per day to perform maintenance tasks.
            wp_schedule_event(
                time(),           // The time when the event should first run.
                'daily',         // The recurrence schedule (daily, hourly, etc.).
                'wprag_daily_cleanup'  // The hook to call when running the event.
            );

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   Scheduled: wprag_daily_cleanup (daily)');
            }
        }

        // Note: In a production plugin, you would also add the action handler:
        // add_action('wprag_daily_cleanup', 'wprag_do_daily_cleanup');
    }

    // ============================================================================
    // CLEANUP METHODS
    // ============================================================================

    /**
     * Clear all scheduled cron events.
     *
     * This removes any scheduled cron events when the plugin is deactivated.
     * This is important to prevent orphaned scheduled events.
     *
     * @since  1.7.0
     * @access private
     * @return void
     */
    private function clear_scheduled_events() {
        // Log clearing scheduled events.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🧹 Posts_RAG_Activator: Clearing scheduled events');
        }

        // Get the timestamp of the next scheduled event.
        $timestamp = wp_next_scheduled('wprag_daily_cleanup');

        // If an event is scheduled, unschedule it.
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wprag_daily_cleanup');

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('   Unscheduled: wprag_daily_cleanup');
            }
        }
    }

    /**
     * Clear any plugin transients.
     *
     * Transients are temporary cached data stored in the database.
     * We clean these up on deactivation to prevent orphaned data.
     *
     * @since  1.7.0
     * @access private
     * @return void
     */
    private function clear_transients() {
        // Log clearing transients.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🧹 Posts_RAG_Activator: Clearing transients');
        }

        // Delete any transients that start with our prefix.
        // This is a wildcard delete - we query all options and delete matching ones.
        global $wpdb;

        // Build SQL to find transients with our prefix.
        // The '_transient_' prefix is used by WordPress for transients.
        $sql = $wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $wpdb->esc_like('_transient_wprag_') . '%'
        );

        // Execute the deletion.
        $result = $wpdb->query($sql);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   Deleted ' . $result . ' transient(s)');
        }
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    /**
     * Get the table name.
     *
     * This public method allows other classes to get the table name.
     *
     * @since  1.7.0
     * @access public
     * @return string The table name with WordPress prefix.
     */
    public function get_table_name() {
        return $this->table_name;
    }

    /**
     * Check if database needs upgrade.
     *
     * This compares the stored database version with the current one.
     * If they're different, it means we need to run upgrade procedures.
     *
     * @since  1.7.0
     * @access public
     * @return bool True if upgrade is needed, false otherwise.
     */
    public function needs_upgrade() {
        // Get the stored database version.
        $stored_version = get_option($this->db_version_option, '0');

        // Compare with current version.
        // If stored version is less than current, we need to upgrade.
        return version_compare($stored_version, $this->current_db_version, '<');
    }

    /**
     * Get the current database schema version.
     *
     * @since  1.7.0
     * @access public
     * @return string The current schema version.
     */
    public function get_db_version() {
        return $this->current_db_version;
    }
}

// ============================================================================
// END OF CLASS
// ============================================================================

/**
 * Alias function for backwards compatibility.
 * This allows other code to call wprag_activate() if needed.
 *
 * @since 1.7.0
 * @deprecated 1.7.0 Use Posts_RAG_Activator::activate() instead.
 */
function wprag_activate() {
    // Log deprecation warning if debug mode is on.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('⚠️  wprag_activate() is deprecated. Use Posts_RAG_Activator::activate() instead.');
    }

    // Call the static method.
    Posts_RAG_Activator::activate();
}

/**
 * Alias function for backwards compatibility.
 * This allows other code to call wprag_deactivate() if needed.
 *
 * @since 1.7.0
 * @deprecated 1.7.0 Use Posts_RAG_Activator::deactivate() instead.
 */
function wprag_deactivate() {
    // Log deprecation warning if debug mode is on.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('⚠️  wprag_deactivate() is deprecated. Use Posts_RAG_Activator::deactivate() instead.');
    }

    // Call the static method.
    Posts_RAG_Activator::deactivate();
}
