<?php
/**
 * Uninstall Script
 *
 * This file is automatically executed when the plugin is deleted from WordPress.
 * It cleans up all plugin data including:
 * - Database table removal
 * - Option removal
 * - Transients cleanup
 * - Scheduled events cleanup
 *
 * @package WP_Posts_RAG_Manager
 *
 * ============================================================================
 * UNINSTALL PROCESS:
 * ============================================================================
 * 1. Remove the custom database table
 * 2. Remove all plugin options
 * 3. Remove any transients
 * 4. Clear any scheduled cron events
 * ============================================================================
 */

// ============================================================================
// PREVENT DIRECT ACCESS
// ============================================================================
// This file should only be called by WordPress when uninstalling the plugin.
// If ABSPATH is not defined, exit immediately.
if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// SECURITY CHECK
// ============================================================================
// WordPress should automatically handle this, but we double-check.
// The uninstall process should only run if WordPress is properly loaded.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    define('WP_UNINSTALL_PLUGIN', true);
}

// ============================================================================
// LOGGING
// ============================================================================
// Log the start of uninstallation for debugging purposes.
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('🗑️  WPRAG: Starting uninstall process');
}

// ============================================================================
// INCLUDE REQUIRED FILES
// ============================================================================
// We need to include the activator to access the table name and methods.
require_once dirname(__FILE__) . '/includes/class-posts-rag-activator.php';

// ============================================================================
// REMOVE DATABASE TABLE
// ============================================================================

/**
 * Remove the custom RAG table from the database.
 *
 * This drops the posts_rag table that was created during activation.
 * This is a permanent action and cannot be undone.
 */
function wprag_drop_database_table() {
    // Log table removal.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🗑️  WPRAG: Dropping database table');
    }

    // Get the global WordPress database object.
    global $wpdb;

    // Get the table name.
    $table_name = $wpdb->prefix . 'posts_rag';

    // Build the DROP TABLE SQL.
    // IF EXISTS prevents errors if table doesn't exist.
    $sql = "DROP TABLE IF EXISTS {$table_name}";

    // Execute the query.
    $result = $wpdb->query($sql);

    // Log the result.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        if ($result !== false) {
            error_log('   ✅ Table dropped successfully');
        } else {
            error_log('   ❌ Error dropping table: ' . $wpdb->last_error);
        }
    }
}

// Call the function to drop the table.
wprag_drop_database_table();

// ============================================================================
// REMOVE OPTIONS
// ============================================================================

/**
 * Remove all plugin options from the database.
 *
 * Options are stored in wp_options table.
 * This removes:
 * - OpenAI API key option
 * - Database version option
 */
function wprag_remove_options() {
    // Log option removal.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🗑️  WPRAG: Removing plugin options');
    }

    // The option names to remove.
    $option_names = array(
        'posts_rag_openai_key',    // OpenAI API key.
        'posts_rag_db_version'    // Database version.
    );

    // Loop through each option and delete it.
    foreach ($option_names as $option_name) {
        // Delete the option.
        delete_option($option_name);

        // Also delete any autoloaded versions.
        delete_site_option($option_name);

        // Log each deletion.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   Removed option: ' . $option_name);
        }
    }
}

// Call the function to remove options.
wprag_remove_options();

// ============================================================================
// REMOVE TRANSIENTS
// ============================================================================

/**
 * Remove all plugin transients.
 *
 * Transients are temporary cached data stored in the database.
 * This cleans up any cached data the plugin may have created.
 */
function wprag_remove_transients() {
    // Log transient removal.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🗑️  WPRAG: Removing transients');
    }

    // Get the global WordPress database object.
    global $wpdb;

    // Build SQL to find transients with our prefix.
    // The '_transient_' prefix is used by WordPress for transients.
    $sql = $wpdb->prepare(
        "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
        $wpdb->esc_like('_transient_wprag_') . '%'
    );

    // Execute the deletion.
    $result = $wpdb->query($sql);

    // Also remove network transients if on multisite.
    if (function_exists('is_multisite') && is_multisite()) {
        $sql = $wpdb->prepare(
            "DELETE FROM $wpdb->sitemeta WHERE meta_key LIKE %s",
            $wpdb->esc_like('_site_transient_wprag_') . '%'
        );
        $wpdb->query($sql);
    }

    // Log the result.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('   ✅ Removed ' . $result . ' transient(s)');
    }
}

// Call the function to remove transients.
wprag_remove_transients();

// ============================================================================
// CLEAR SCHEDULED EVENTS
// ============================================================================

/**
 * Clear all scheduled cron events.
 *
 * This removes any scheduled events that were created by the plugin.
 * This prevents orphaned cron jobs from running after uninstallation.
 */
function wprag_clear_scheduled_events() {
    // Log cron cleanup.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🗑️  WPRAG: Clearing scheduled events');
    }

    // Get the timestamp of any scheduled event.
    $timestamp = wp_next_scheduled('wprag_daily_cleanup');

    // If an event is scheduled, unschedule it.
    if ($timestamp) {
        // Unschedule the event.
        wp_unschedule_event($timestamp, 'wprag_daily_cleanup');

        // Log the cleanup.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ✅ Cleared wprag_daily_cleanup event');
        }
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('   ℹ️  No scheduled events found');
        }
    }
}

// Call the function to clear scheduled events.
wprag_clear_scheduled_events();

// ============================================================================
// FINAL CLEANUP
// ============================================================================

/**
 * Perform any additional cleanup tasks.
 *
 * This is where you would add any custom cleanup logic specific to your plugin.
 * For example, if you created custom post types or taxonomy terms.
 */
function wprag_final_cleanup() {
    // Log final cleanup.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🧹 WPRAG: Performing final cleanup');
    }

    // Add any additional cleanup tasks here.
    // Examples:
    // - Remove custom post types (if any were registered)
    // - Remove custom taxonomies
    // - Remove custom capabilities
    // - Remove custom roles

    // For this plugin, we don't have any additional cleanup tasks.
    // This function is here for future extensibility.

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('✅ WPRAG: Uninstall complete - all data removed');
    }
}

// Call the final cleanup function.
wprag_final_cleanup();

// ============================================================================
// END OF UNINSTALL PROCESS
// ============================================================================

/**
 * Return early message (optional).
 *
 * Some plugins return a message to indicate successful uninstallation.
 * However, since this is handled by WordPress automatically, we don't need to.
 *
 * @since 1.7.0
 * @return void
 */
function wprag_uninstall_complete() {
    // This function is intentionally empty.
    // It's here as a placeholder for any post-uninstall operations.
}

// That's the end of the uninstall script.
// All plugin data has been cleaned up.
