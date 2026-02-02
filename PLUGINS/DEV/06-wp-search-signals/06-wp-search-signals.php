<?php
/**
 * Plugin Name:  ✅ 06 WP SEARCH SIGNALS
 * Description: Record user actions as signals for machine learning.
 * Version: 1.1.0
 * Author: Craig West
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Signals_Plugin {
    const TABLE_SLUG = 'signals';
    const QUERIES_TABLE_SLUG = 'signals_queries';
    const NONCE_ACTION = 'wp_signals_log_event';
    const AJAX_ACTION = 'wp_signals_log_event';
    const AJAX_ACTION_CREATE_QUERY = 'wp_signals_create_query';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_log_event' ) );
        add_action( 'wp_ajax_' . self::AJAX_ACTION_CREATE_QUERY, array( $this, 'handle_create_query' ) );
    }

    public static function activate() {
        global $wpdb;

        $signals_table = $wpdb->prefix . self::TABLE_SLUG;
        $queries_table = $wpdb->prefix . self::QUERIES_TABLE_SLUG;
        $charset_collate = $wpdb->get_charset_collate();

        // Create queries table
        $queries_sql = "CREATE TABLE IF NOT EXISTS {$queries_table} (
            query_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(128) NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            query_text VARCHAR(255) NOT NULL,
            result_ids LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (query_id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        // Create signals table with query_id reference
        $signals_sql = "CREATE TABLE IF NOT EXISTS {$signals_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            query_id BIGINT UNSIGNED NULL,
            session_id VARCHAR(128) NOT NULL,
            guid VARCHAR(36) NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            event_name VARCHAR(120) NOT NULL,
            post_id BIGINT UNSIGNED NULL,
            event_meta_details LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY query_id (query_id),
            KEY event_name (event_name),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY post_id (post_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $queries_sql );
        dbDelta( $signals_sql );
    }

    public function register_admin_menu() {
        add_menu_page(
            'WP Signals',
            '06 WP SIGNALS',
            'manage_options',
            'wp-signals',
            array( $this, 'render_admin_page' ),
            'dashicons-rss',
            3.6
        );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_wp-signals' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'wp-signals-admin',
            plugin_dir_url( __FILE__ ) . 'assets/admin.js',
            array(),
            '1.1.0',
            true
        );

        wp_enqueue_style(
            'wp-signals-admin',
            plugin_dir_url( __FILE__ ) . 'assets/styles.css',
            array(),
            '1.1.0'
        );

        wp_localize_script(
            'wp-signals-admin',
            'wpSignalsData',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
                'hybridSearchUrl' => rest_url( 'search/v1/hybrid-search' ),
                'limit'  => 3,
            )
        );
    }

    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap">
            <h1>WP Signals</h1>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ws_query">Search Query</label></th>
                    <td>
                        <input
                            name="ws_query"
                            id="ws_query"
                            type="text"
                            class="regular-text"
                            value="FOAM products"
                           
                        />
                        <button type="button" class="button button-primary" id="ws_query_run">
                            Run Query
                        </button>
                        <p class="description">
                            Results are pulled from the hybrid search endpoint and limited to 3 entries.
                        </p>
                    </td>
                </tr>
            </table>

            <h2>Search Results</h2>
            <div id="wp-signals-results" class="wp-signals-results">
                <p>Enter a query to fetch results.</p>
            </div>

            <h2>Debug Output</h2>
            <p class="description">Latest event payloads that will be saved:</p>
            <ul id="wp-signals-debug" class="wp-signals-debug"></ul>
        </div>
        <?php
    }

    public function handle_create_query() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'User not logged in.' ), 403 );
        }

        $query_text = '';
        $result_ids = array();

        if ( isset( $_POST['query_text'] ) ) {
            $query_text = sanitize_text_field( wp_unslash( $_POST['query_text'] ) );
        }

        if ( isset( $_POST['result_ids'] ) ) {
            $raw_results = wp_unslash( $_POST['result_ids'] );
            if ( is_array( $raw_results ) ) {
                $result_ids = array_map( 'intval', $raw_results );
            } elseif ( is_string( $raw_results ) ) {
                $decoded = json_decode( $raw_results, true );
                if ( is_array( $decoded ) ) {
                    $result_ids = array_map( 'intval', $decoded );
                }
            }
        }

        if ( empty( $query_text ) ) {
            wp_send_json_error( array( 'message' => 'Missing query text.' ), 400 );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . self::QUERIES_TABLE_SLUG;

        $session_id = '';
        if ( function_exists( 'wp_get_session_token' ) ) {
            $session_id = (string) wp_get_session_token();
        }
        if ( empty( $session_id ) ) {
            if ( PHP_SESSION_ACTIVE !== session_status() ) {
                session_start();
            }
            $session_id = (string) session_id();
        }

        $inserted = $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'user_id'    => get_current_user_id(),
                'query_text' => $query_text,
                'result_ids' => wp_json_encode( $result_ids ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%d', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            wp_send_json_error( array( 'message' => 'Database insert failed.' ), 500 );
        }

        $query_id = $wpdb->insert_id;

        wp_send_json_success( array( 
            'message' => 'Query logged.',
            'query_id' => $query_id
        ) );
    }

    public function handle_log_event() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'User not logged in.' ), 403 );
        }

        $event_name = '';
        $event_meta_details = '';
        $query_id = null;
        $post_id = null;

        if ( isset( $_POST['event_name'] ) ) {
            $event_name = sanitize_text_field( wp_unslash( $_POST['event_name'] ) );
        }

        if ( isset( $_POST['event_meta_details'] ) ) {
            $event_meta_details = wp_unslash( $_POST['event_meta_details'] );
        }

        if ( isset( $_POST['query_id'] ) ) {
            $query_id = intval( $_POST['query_id'] );
        }

        if ( isset( $_POST['post_id'] ) ) {
            $post_id = intval( $_POST['post_id'] );
        }

        if ( empty( $event_name ) ) {
            wp_send_json_error( array( 'message' => 'Missing event name.' ), 400 );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_SLUG;

        $session_id = '';
        if ( function_exists( 'wp_get_session_token' ) ) {
            $session_id = (string) wp_get_session_token();
        }
        if ( empty( $session_id ) ) {
            if ( PHP_SESSION_ACTIVE !== session_status() ) {
                session_start();
            }
            $session_id = (string) session_id();
        }

        $inserted = $wpdb->insert(
            $table_name,
            array(
                'query_id'          => $query_id,
                'session_id'        => $session_id,
                'guid'              => wp_generate_uuid4(),
                'user_id'           => get_current_user_id(),
                'event_name'        => $event_name,
                'post_id'           => $post_id,
                'event_meta_details'=> $event_meta_details,
                'created_at'        => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s' )
        );

        if ( false === $inserted ) {
            wp_send_json_error( array( 'message' => 'Database insert failed.' ), 500 );
        }

        wp_send_json_success( array( 'message' => 'Event logged.' ) );
    }
}

register_activation_hook( __FILE__, array( 'WP_Signals_Plugin', 'activate' ) );
new WP_Signals_Plugin();
