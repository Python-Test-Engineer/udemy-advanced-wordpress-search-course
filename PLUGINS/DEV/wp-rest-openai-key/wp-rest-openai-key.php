<?php
/**
 * Plugin Name: ✅ WP GET OPENAI KEY
 * Description: Exposes the OpenAI API key stored in posts_rag_openai_key via a REST endpoint and admin demo page.
 * Version: 1.0.0
 * Author: Craig West
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_REST_OpenAI_Key {
    const OPTION_KEY = 'posts_rag_openai_key';
    const REST_NAMESPACE = 'openai-api-key/v1';
    const REST_ROUTE = '/get-key';

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('admin_menu', array($this, 'register_admin_page'));
    }

    public function register_rest_routes() {
        register_rest_route(self::REST_NAMESPACE, self::REST_ROUTE, array(
            'methods' => 'GET',
            'callback' => array($this, 'get_openai_key_response'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }

    public function getKey() {
        return get_option(self::OPTION_KEY);
    }

    public function get_openai_key_response() {
        $api_key = $this->getKey();

        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'OpenAI API key is not configured.', array('status' => 400));
        }

        return array(
            'openai-api-key' => $api_key
        );
    }

    public function register_admin_page() {
        add_menu_page(
            'OpenAI API Key',
            'OPENAI KEY',
            'manage_options',
            'wp-rest-openai-key',
            array($this, 'render_admin_page'),
            'dashicons-admin-network',
            3.55
        );
    }

    public function render_admin_page() {
        $api_key = $this->getKey();
        $masked_key = $api_key ? substr_replace($api_key, str_repeat('*', 20), 15, 10) : '';
        $endpoint = rest_url(self::REST_NAMESPACE . self::REST_ROUTE);
        ?>
        <div class="wrap wp-rest-openai-key-page" style="font-size:1.5rem;">
            <style>
                .wp-rest-openai-key-page {
                    --oak-orange: #f97316;
                    --oak-orange-dark: #ea580c;
                    --oak-orange-soft: #fff4e6;
                    --oak-border: #fed7aa;
                    color: #1f2937;
                }
             
                .wp-rest-openai-key-page .oak-hero {
                    background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
                    border: 1px solid var(--oak-border);
                    padding: 24px;
                    border-radius: 16px;
                    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
                    margin-bottom: 24px;
                }
                .wp-rest-openai-key-page .oak-hero h1 {
                    margin: 0 0 8px;
                    font-size: 28px;
                    color: var(--oak-orange-dark);
                }
                .wp-rest-openai-key-page .oak-hero p {
                    margin: 0;
                    font-size: 15px;
                    color: #475569;
                }
                .wp-rest-openai-key-page .oak-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                    gap: 20px;
                }
                .wp-rest-openai-key-page .oak-card {
                    background: #ffffff;
                    border: 1px solid #e2e8f0;
                    border-radius: 14px;
                    padding: 20px;
                    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
                }
                .wp-rest-openai-key-page .oak-card h2 {
                    margin-top: 0;
                    font-size: 18px;
                    color: #0f172a;
                }
                .wp-rest-openai-key-page .oak-card code {
                    background: var(--oak-orange-soft);
                    padding: 4px 8px;
                    border-radius: 8px;
                    color: #9a3412;
                    display: inline-block;
                }
                .wp-rest-openai-key-page .oak-card pre {
                    background: #0f172a;
                    color: #e2e8f0;
                    padding: 16px;
                    border-radius: 12px;
                    margin-top: 12px;
                    overflow-x: auto;
                }
                .wp-rest-openai-key-page .oak-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 6px 12px;
                    border-radius: 999px;
                    background: var(--oak-orange);
                    color: #fff;
                    font-size: 12px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                }
            </style>

            <div class="oak-hero">
                <span class="oak-pill">OpenAI Key Demo</span>
                <h1>OpenAI API Key Demo</h1>
                <p>This page demonstrates the class getter and REST endpoint.</p>
            </div>

            <div class="oak-grid">
                <div class="oak-card">
                    <h2>Class Getter</h2>
                    <p><strong>Method:</strong> <code>$instClass = new WP_REST_OpenAI_Key();</code></p>
                    <p><strong>Call:</strong> <code>$api_key = $instClass->getKey();</code></p>
                    <p><strong>Result:</strong> <code><?php echo esc_html($api_key ? $masked_key : 'No key configured'); ?></code></p>
                </div>

                <div class="oak-card">
                    <h2>REST Endpoint</h2>
                    <p><strong>Endpoint:</strong> <code><?php echo esc_url($endpoint); ?></code></p>
                    <p><strong>Sample JSON:</strong></p>
                    <pre>{
    "openai-api-key": "<?php echo esc_html($api_key ? $masked_key : 'sk-proj-xxxxxxxxxxxxxxxx'); ?>"
}</pre>
                </div>
            </div>
        </div>
        <?php
    }
}

new WP_REST_OpenAI_Key();
