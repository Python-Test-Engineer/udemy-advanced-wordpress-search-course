/**
 * Plugin Name: ✅ 32 FTS Query Builder
 * Plugin URI: https://example.com
 * Description: Advanced search form that generates encoded FTS query strings with +, -, and * operators
 * Version: 1.0.0
 * Author: Craig West
 * Author URI: https://example.com
 * License: GPL2
 * Text Domain: fts-query-builder
 */


    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_menu_page(
            'FTS Query Builder',           // Page title
            '32 BOOLEAN BUILDER',                   // Menu title
            'manage_options',
            'fts-query-builder',            // Menu slug
            array($this, 'render_admin_page'), // Callback function
            'dashicons-search',             // Icon
            4.98                              // Position
        );
    }