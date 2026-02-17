<?php
/**
 * Index File
 *
 * This is a security measure. If someone tries to access the includes directory
 * directly, this file will prevent it and exit gracefully.
 *
 * This is a common WordPress best practice to prevent direct access to
 * plugin files.
 *
 * @package WP_Posts_RAG_Manager
 */

// Prevent direct access to this file.
if (!defined('ABSPATH')) {
    // translators: This is a security message shown when someone tries to access this file directly.
    exit(__('Direct access to this file is not allowed.', 'wp-posts-rag-manager'));
}

// ============================================================================
// SECURITY NOTE:
// ============================================================================
// This file intentionally contains minimal code.
// Its sole purpose is to prevent directory browsing.
// ============================================================================

/**
 * Optional: You can add a comment here to explain what this directory contains.
 * For example: This directory contains include files for the WP Posts RAG Manager plugin.
 */

// That's it! Nothing else to see here.
