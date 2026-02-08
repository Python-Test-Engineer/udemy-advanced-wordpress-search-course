<?php
/**
 * Plugin Name: ✅ 33 WP Search Form With Reranking
 * Plugin URI: https://example.com
 * Description: Advanced search form that generates encoded FTS query strings and shows reranked results.
 * Version: 1.0.0
 * Author: Craig West
 * Author URI: https://example.com
 * License: GPL2
 * Text Domain: fts-query-builder-reranking
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


class FTS_Query_Builder_Reranking {
    
    public function __construct() {
        // Register shortcode
        add_shortcode('fts_search_form_reranking', array($this, 'render_search_form'));
        
        // Enqueue styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX handler for query generation
        add_action('wp_ajax_generate_fts_query', array($this, 'ajax_generate_query'));
        add_action('wp_ajax_nopriv_generate_fts_query', array($this, 'ajax_generate_query'));
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_menu_page(
            'FTS Query Builder',           // Page title
            '33 SEARCH + RERANK',                   // Menu title
            'manage_options',            
            'fts-query-builder-reranking',            // Menu slug
            array($this, 'render_admin_page'), // Callback function
            'dashicons-search',             // Icon
            4.97                             // Position
        );
    }
    
    /**
     * Enqueue assets for admin page
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin page
        if ($hook !== 'toplevel_page_fts-query-builder-reranking') {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        wp_localize_script('jquery', 'ftsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fts_query_nonce')
        ));
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>FTS Query Builder + Reranking</h1>
            <p>Generate advanced search queries with operators and review the reranked output.</p>
            
            <style>
                .fts-admin-container {
                    max-width: 800px;
                    background: #fff;
                    padding: 30px;
                    margin-top: 20px;
                    border: 1px solid #ccd0d4;
                    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
                }
                .fts-admin-form-group {
                    margin-bottom: 20px;
                }
                .fts-admin-form-group label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 8px;
                    font-size: 14px;
                }
                .fts-admin-form-group input[type="text"] {
                    width: 100%;
                    padding: 10px;
                    font-size: 14px;
                    border: 1px solid #8c8f94;
                    border-radius: 4px;
                }
                .fts-admin-form-group input[type="text"]:focus {
                    border-color: #2271b1;
                    outline: 2px solid transparent;
                    box-shadow: 0 0 0 1px #2271b1;
                }
                .fts-admin-help-text {
                    font-size: 12px;
                    color: #646970;
                    margin-top: 5px;
                    font-style: italic;
                }
                .fts-admin-operator-badge {
                    display: inline-block;
                    padding: 2px 8px;
                    border-radius: 3px;
                    font-family: monospace;
                    font-size: 13px;
                    margin-left: 5px;
                }
                .fts-admin-operator-badge.fts-plus {
                    background: #d4edda;
                    color: #155724;
                }
                .fts-admin-operator-badge.fts-minus {
                    background: #f8d7da;
                    color: #721c24;
                }
                .fts-admin-operator-badge.fts-wildcard {
                    background: #d1ecf1;
                    color: #0c5460;
                }
                .fts-admin-result {
                    margin-top: 30px;
                    padding: 20px;
                    background: #f0f6fc;
                    border-left: 4px solid #2271b1;
                    display: none;
                }
                .fts-admin-result.show {
                    display: block;
                }
                .fts-admin-result h3 {
                    margin-top: 0;
                    font-size: 16px;
                }
                .fts-admin-query-output {
                    background: #fff;
                    padding: 15px;
                    border: 1px solid #8c8f94;
                    border-radius: 4px;
                    font-family: monospace;
                    word-break: break-all;
                    margin: 10px 0;
                    font-size: 13px;
                }
                .fts-admin-example-url {
                    background: #fff3cd;
                    padding: 15px;
                    border-radius: 4px;
                    margin-top: 15px;
                    border-left: 4px solid #856404;
                }
                .fts-admin-info-box {
                    background: #f0f6fc;
                    border-left: 4px solid #2271b1;
                    padding: 15px;
                    margin-top: 20px;
                }
                .fts-admin-info-box h3 {
                    margin-top: 0;
                    font-size: 14px;
                }
            </style>
            
            <div class="fts-admin-container">
                <div class="fts-admin-info-box" style="background: #e8f5e9; border-left-color: #4caf50;">
                    <h3>📝 Quick Start Example - Memory Foam Pillow Search</h3>
                    <p>Click the button below to pre-fill the form with a real product search example:</p>
                    <button type="button" class="button button-secondary" id="fts-load-example" style="margin-top: 10px;">Load Memory Foam Pillow Example</button>
                </div>

                <form id="fts-admin-search-form">
                    <div class="fts-admin-form-group">
                        <label for="fts-admin-basic-query">Basic Search Query</label>
                        <input type="text" id="fts-admin-basic-query" name="basic_query" placeholder="e.g., wordpress plugin">
                        <span class="fts-admin-help-text">Regular search terms</span>
                    </div>

                    <div class="fts-admin-form-group">
                        <label for="fts-admin-must-contain">
                            Must Contain <span class="fts-admin-operator-badge fts-plus">+</span>
                        </label>
                        <input type="text" id="fts-admin-must-contain" name="must_contain" placeholder="e.g., tutorial security">
                        <span class="fts-admin-help-text">Terms that MUST appear (separate with spaces)</span>
                    </div>

                    <div class="fts-admin-form-group">
                        <label for="fts-admin-must-not-contain">
                            Must NOT Contain <span class="fts-admin-operator-badge fts-minus">-</span>
                        </label>
                        <input type="text" id="fts-admin-must-not-contain" name="must_not_contain" placeholder="e.g., premium paid">
                        <span class="fts-admin-help-text">Exclude these terms (separate with spaces)</span>
                    </div>

                    <div class="fts-admin-form-group">
                        <label for="fts-admin-wildcard">
                            Wildcard <span class="fts-admin-operator-badge fts-wildcard">*</span>
                        </label>
                        <input type="text" id="fts-admin-wildcard" name="wildcard" placeholder="e.g., develop custom">
                        <span class="fts-admin-help-text">Match variations (becomes develop* custom*)</span>
                    </div>

                    <div class="fts-admin-form-group">
                        <label for="fts-admin-phrase">
                            Exact Phrase <span class="fts-admin-operator-badge" style="background: #e8eaf6; color: #283593;">"..."</span>
                        </label>
                        <input type="text" id="fts-admin-phrase" name="phrase" placeholder="e.g., wordpress tutorial">
                        <span class="fts-admin-help-text">Match exact phrase (becomes "wordpress tutorial")</span>
                    </div>

                    <div class="fts-admin-form-group">
                        <label for="fts-admin-less-than">
                            Reduce Terms <span class="fts-admin-operator-badge" style="background: #fff9c4; color: #f57f17;">&lt;</span>
                        </label>
                        <input type="text" id="fts-admin-less-than" name="less_than" placeholder="e.g., outdated generic">
                        <span class="fts-admin-help-text">Reduce importance (becomes &lt;outdated &lt;generic)</span>
                    </div>

                    <div class="fts-admin-form-group">
                        <label for="fts-admin-greater-than">
                            Boost Terms <span class="fts-admin-operator-badge" style="background: #ffe0b2; color: #e65100;">&gt;</span>
                        </label>
                        <input type="text" id="fts-admin-greater-than" name="greater_than" placeholder="e.g., featured premium">
                        <span class="fts-admin-help-text">Boost importance (becomes &gt;featured &gt;premium)</span>
                    </div>

                    <div class="fts-admin-form-group">
                        <label for="fts-admin-or-terms">
                            OR Terms <span class="fts-admin-operator-badge" style="background: #f3e5f5; color: #6a1b9a;">|</span>
                        </label>
                        <input type="text" id="fts-admin-or-terms" name="or_terms" placeholder="e.g., tutorial guide">
                        <span class="fts-admin-help-text">Match any of these terms (becomes tutorial|guide)</span>
                    </div>

                    <div class="fts-admin-form-group">
                        <label for="fts-admin-parentheses">
                            Grouping (Parentheses) <span class="fts-admin-operator-badge" style="background: #e0f2f1; color: #00695c;">()</span>
                        </label>
                        <input type="text" id="fts-admin-parentheses" name="parentheses" placeholder="e.g., wordpress AND (plugin OR theme)">
                        <span class="fts-admin-help-text">Group terms with parentheses for complex queries</span>
                    </div>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">Generate Query</button>
                        <button type="button" class="button" id="fts-clear-form" style="margin-left: 10px;">Clear Form</button>
                    </p>
                </form>

                <div class="fts-admin-info-box">
                    <h3>Boolean Operators Guide:</h3>
                    <p>
                        <strong>+ (Must Contain):</strong> <code>+tutorial</code> - Result MUST include "tutorial"<br>
                        <strong>- (Must NOT Contain):</strong> <code>-premium</code> - Result must NOT include "premium"<br>
                        <strong>* (Wildcard):</strong> <code>develop*</code> - Matches develop, developer, development, etc.<br>
                        <strong>"..." (Exact Phrase):</strong> <code>"wordpress plugin"</code> - Exact phrase match<br>
                        <strong>&lt; (Reduce Terms):</strong> <code>&lt;outdated</code> - Reduce term importance<br>
                        <strong>&gt; (Boost Terms):</strong> <code>&gt;featured</code> - Boost term importance<br>
                        <strong>| (OR):</strong> <code>(tutorial|guide)</code> - Match either tutorial OR guide<br>
                        <strong>() (Grouping):</strong> <code>(+wordpress +(plugin|theme))</code> - Group complex queries<br>
                    </p>
                    <p style="margin-top: 10px;">
                        <strong>Example Complex Query:</strong><br>
                        <code>+wordpress +(plugin|theme) -premium "best practices" develop* &gt;featured &lt;outdated</code>
                    </p>
                </div>

                <div class="fts-admin-info-box">
                    <h3>How URL Encoding Works:</h3>
                    <p>
                        <strong>The Problem:</strong> In URLs, <code>+</code> means "space", but we need it as a search operator.<br>
                        <strong>The Solution:</strong> We use <code>%2B</code> for the literal + character.<br><br>
                        <strong>Example:</strong><br>
                        • Search for: <code>+tutorial -premium develop*</code><br>
                        • URL becomes: <code>/wp-json/search/v1/hybrid-search?query=%2Btutorial%20-premium%20develop*</code><br>
                        • Backend receives: <code>+tutorial -premium develop*</code> ✓
                    </p>
                </div>

                <div class="fts-admin-info-box" style="background: #f3e5f5; border-left-color: #9c27b0;">
                    <h3>API Endpoint Information:</h3>
                    <p>
                        <strong>Custom Hybrid Search Endpoint:</strong><br>
                        <code><?php echo get_site_url(); ?>/wp-json/search/v1/hybrid-search?query=</code><br><br>
                        This plugin automatically uses your site's domain with the hybrid search endpoint.<br>
                        The generated URL will work with your current WordPress installation.
                    </p>
                </div>

                <div class="fts-admin-info-box" style="background: #fcf8e3; border-left-color: #f0ad4e;">
                    <h3>Using the Shortcode:</h3>
                    <p>Add <code>[fts_search_form_reranking]</code> to any page or post to display this form on the frontend.</p>
                </div>
            </div>

                <div id="fts-admin-result" class="fts-admin-result">
                    <h3>Encoded Query String:</h3>
                    <div class="fts-admin-query-output" id="fts-admin-encoded-query"></div>
                    <button type="button" class="button" id="fts-admin-copy-btn">Copy to Clipboard</button>
                    
                    <div class="fts-admin-example-url">
                        <strong>Use this in your URL:</strong><br>
                        <code id="fts-admin-example-url"></code>
                    </div>
                </div>

                <div id="fts-admin-rerank-result" class="fts-admin-result" style="border-left-color:#6a1b9a; background:#f5f3ff;">
                    <h3>Reranked Results (Final Set)</h3>
                    <p style="font-size:12px;color:#555;margin-bottom:12px;">This output is fetched from <code>/wp-json/reranker/v1/reranked</code> using the generated query.</p>
                    <div id="fts-admin-rerank-results" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;"></div>
                </div>

                <div id="fts-admin-sql-result" class="fts-admin-result" style="border-left-color:#0ea5e9; background:#ecfeff;">
                    <h3>SQL Used (FTS)</h3>
                    <p style="font-size:12px;color:#555;margin-bottom:12px;">Shows the SQL used for the full-text lookup ("none" for vector-only results).</p>
                    <pre id="fts-admin-sql-output" style="background:#fff;border:1px solid #ddd;padding:12px;overflow:auto;white-space:pre-wrap;"></pre>
                </div>

                <div id="fts-admin-json-result" class="fts-admin-result" style="border-left-color:#10b981; background:#ecfdf5;">
                    <h3>Live JSON Response</h3>
                    <p style="font-size:12px;color:#555;margin-bottom:12px;">Actual response returned by <code>/wp-json/reranker/v1/reranked</code>.</p>
                    <pre id="fts-admin-json-output" style="background:#fff;border:1px solid #ddd;padding:12px;overflow:auto;white-space:pre-wrap;"></pre>
                </div>

                <div class="fts-admin-info-box" style="background: #fff3e0; border-left-color: #ff9800;">
                    <h3>Reranked Output (Final Result Set)</h3>
                    <p>The reranker endpoint combines Fulltext + Vector results, normalizes scores, and returns a final list ordered by the combined score.</p>
                    <p><strong>Endpoint:</strong> <code><?php echo esc_html(get_site_url()); ?>/wp-json/reranker/v1/reranked?query=YOUR_QUERY</code></p>
                    <p><strong>Output Fields:</strong></p>
                    <ul>
                        <li><strong>post_id</strong> - WordPress post ID</li>
                        <li><strong>post_title</strong> - Title of the result</li>
                        <li><strong>excerpt</strong> - Summary text used in cards</li>
                        <li><strong>relevance_score</strong> - Fulltext score (FTS)</li>
                        <li><strong>similarity_score</strong> - Vector similarity score</li>
                        <li><strong>method</strong> - FTS, VECTOR, or FTS+VECTOR</li>
                        <li><strong>position</strong> - Final ordering after reranking</li>
                        <li><strong>sql</strong> - SQL used for the FTS lookup ("none" for vector-only results)</li>
                    </ul>
                    <p><strong>Response Shape (Live):</strong></p>
                    <pre id="fts-admin-response-shape-output" style="background:#fff;border:1px solid #ddd;padding:12px;overflow:auto;white-space:pre-wrap;"></pre>
                </div>

            <script>
            jQuery(document).ready(function($) {
                function renderRerankAdmin(results) {
                    const $container = $('#fts-admin-rerank-results');
                    $container.empty();

                    if (!Array.isArray(results) || results.length === 0) {
                        $container.append('<p style="font-size:13px;color:#6b7280;margin:0;">No reranked results returned.</p>');
                        return;
                    }

                    results.forEach(function(item) {
                        const title = item.post_title || 'Untitled';
                        const excerpt = item.excerpt || item.content || item.post_content || '';
                        const method = item.method || 'UNKNOWN';
                        const relevance = typeof item.relevance_score !== 'undefined' ? Number(item.relevance_score).toFixed(4) : '0.0000';
                        const similarity = typeof item.similarity_score !== 'undefined' ? Number(item.similarity_score).toFixed(4) : '0.0000';
                        const position = item.position || '-';
                        const link = item.url || item.permalink || '';

                        const card = `
                            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;box-shadow:0 4px 10px rgba(17,24,39,0.06);">
                                <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
                                    <h4 style="margin:0;font-size:15px;color:#1f2937;">${title}</h4>
                                    <span style="background:#ede9fe;color:#6d28d9;border-radius:999px;padding:4px 10px;font-size:11px;font-weight:700;">#${position}</span>
                                </div>
                                <div style="display:flex;flex-wrap:wrap;gap:6px;margin:10px 0 6px;">
                                    <span style="background:#f3f4f6;color:#111827;border-radius:999px;padding:4px 10px;font-size:11px;font-weight:600;">${method}</span>
                                </div>
                                <div style="display:flex;flex-wrap:wrap;gap:6px;margin:0 0 12px;">
                                    <span style="background:#f3f4f6;color:#111827;border-radius:999px;padding:4px 10px;font-size:11px;font-weight:600;">FTS: ${relevance}</span>
                                    <span style="background:#f3f4f6;color:#111827;border-radius:999px;padding:4px 10px;font-size:11px;font-weight:600;">Vector: ${similarity}</span>
                                </div>
                                <p style="font-size:13px;color:#374151;line-height:1.5;margin-bottom:12px;">${excerpt}</p>
                                ${link ? `<a href="${link}" target="_blank" rel="noopener" style="font-size:12px;font-weight:600;color:#2563eb;text-decoration:none;">View Result</a>` : ''}
                            </div>
                        `;
                        $container.append(card);
                    });
                }

                function fetchRerankAdmin(query) {
                    const endpoint = '<?php echo esc_js(rest_url('reranker/v1/reranked')); ?>';
                    const url = `${endpoint}?query=${encodeURIComponent(query)}`;
                    return $.ajax({
                        url: url,
                        type: 'GET'
                    });
                }

                function fetchFulltextSql(query) {
                    const endpoint = '<?php echo esc_js(rest_url('search/v1/search')); ?>';
                    const url = `${endpoint}?query=${encodeURIComponent(query)}&limit=3`;
                    return $.ajax({
                        url: url,
                        type: 'GET'
                    });
                }

                function formatSql(sql) {
                    if (!sql || typeof sql !== 'string') {
                        return 'none';
                    }

                    const cleaned = sql.replace(/[\r\n]+/g, ' ').replace(/\s+/g, ' ').trim();
                    if (!cleaned.length) {
                        return 'none';
                    }

                    return cleaned
                        .replace(/\s+(SELECT|FROM|WHERE|ORDER BY|LIMIT|INNER JOIN|LEFT JOIN|RIGHT JOIN|JOIN|GROUP BY)\s+/gi, '\n$1 ');
                }

                // Load example data
                $('#fts-load-example').on('click', function() {
                    $('#fts-admin-basic-query').val('pillow');
                    $('#fts-admin-must-contain').val('memory foam bamboo hypoallergenic');
                    $('#fts-admin-must-not-contain').val('premium expensive luxury');
                    $('#fts-admin-wildcard').val('adjust');
                    $('#fts-admin-phrase').val('machine washable');
                    $('#fts-admin-less-than').val('generic');
                    $('#fts-admin-greater-than').val('cooling');
                    $('#fts-admin-or-terms').val('cooling breathable');
                    $('#fts-admin-parentheses').val('');
                    
                    // Highlight the form
                    $('.fts-admin-container').css('background', '#fff9c4');
                    setTimeout(function() {
                        $('.fts-admin-container').css('background', '#fff');
                    }, 1000);
                    
                    // Scroll to form
                    $('html, body').animate({
                        scrollTop: $('#fts-admin-search-form').offset().top - 50
                    }, 500);
                });

                // Clear form
                $('#fts-clear-form').on('click', function() {
                    $('#fts-admin-search-form')[0].reset();
                    $('#fts-admin-result').removeClass('show');
                    $('#fts-admin-rerank-result').removeClass('show');
                    $('#fts-admin-sql-result').removeClass('show');
                    $('#fts-admin-json-result').removeClass('show');
                    $('#fts-admin-rerank-results').empty();
                    $('#fts-admin-sql-output').text('');
                    $('#fts-admin-json-output').text('');
                    $('#fts-admin-response-shape-output').text('');
                });
                
                $('#fts-admin-search-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    const basicQuery = $('#fts-admin-basic-query').val().trim();
                    const mustContain = $('#fts-admin-must-contain').val().trim();
                    const mustNotContain = $('#fts-admin-must-not-contain').val().trim();
                    const wildcard = $('#fts-admin-wildcard').val().trim();
                    const phrase = $('#fts-admin-phrase').val().trim();
                    const lessThan = $('#fts-admin-less-than').val().trim();
                    const greaterThan = $('#fts-admin-greater-than').val().trim();
                    const orTerms = $('#fts-admin-or-terms').val().trim();
                    const parentheses = $('#fts-admin-parentheses').val().trim();
                    
                    if (!basicQuery && !mustContain && !mustNotContain && !wildcard && !phrase && !lessThan && !greaterThan && !orTerms && !parentheses) {
                        alert('Please enter at least one search term');
                        return;
                    }
                    
                    $('#fts-admin-rerank-result').removeClass('show');
                    $('#fts-admin-sql-result').removeClass('show');
                    $('#fts-admin-json-result').removeClass('show');
                    $('#fts-admin-rerank-results').empty();
                    $('#fts-admin-sql-output').text('');
                    $('#fts-admin-json-output').text('');
                    $('#fts-admin-response-shape-output').text('');

                    $.ajax({
                        url: ftsAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'generate_fts_query',
                            nonce: ftsAjax.nonce,
                            basic_query: basicQuery,
                            must_contain: mustContain,
                            must_not_contain: mustNotContain,
                            wildcard: wildcard,
                            phrase: phrase,
                            less_than: lessThan,
                            greater_than: greaterThan,
                            or_terms: orTerms,
                            parentheses: parentheses
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#fts-admin-encoded-query').text(response.data.encoded);
                                $('#fts-admin-example-url').text(response.data.url);
                                $('#fts-admin-result').addClass('show');

                                const query = response.data.query || basicQuery;
                                if (query) {
                                    fetchRerankAdmin(query)
                                        .done(function(rerankResponse) {
                                            if (rerankResponse && rerankResponse.success) {
                                                renderRerankAdmin(rerankResponse.results || []);
                                                const formattedSql = formatSql(rerankResponse.sql);
                                                if (formattedSql === 'none') {
                                                    fetchFulltextSql(query)
                                                        .done(function(fulltextResponse) {
                                                            const fallbackSql = formatSql(fulltextResponse && fulltextResponse.sql ? fulltextResponse.sql : '');
                                                            $('#fts-admin-sql-output').text(fallbackSql);
                                                        })
                                                        .fail(function() {
                                                            $('#fts-admin-sql-output').text('none');
                                                        });
                                                } else {
                                                    $('#fts-admin-sql-output').text(formattedSql);
                                                }
                                                $('#fts-admin-json-output').text(JSON.stringify(rerankResponse, null, 2));
                                                $('#fts-admin-response-shape-output').text(JSON.stringify(rerankResponse, null, 2));
                                            } else {
                                                renderRerankAdmin([]);
                                                $('#fts-admin-sql-output').text('none');
                                                $('#fts-admin-json-output').text(JSON.stringify(rerankResponse || {}, null, 2));
                                                $('#fts-admin-response-shape-output').text(JSON.stringify(rerankResponse || {}, null, 2));
                                            }
                                            $('#fts-admin-rerank-result').addClass('show');
                                            $('#fts-admin-sql-result').addClass('show');
                                            $('#fts-admin-json-result').addClass('show');
                                        })
                                        .fail(function() {
                                            renderRerankAdmin([]);
                                            $('#fts-admin-sql-output').text('none');
                                            $('#fts-admin-rerank-result').addClass('show');
                                            $('#fts-admin-sql-result').addClass('show');
                                            $('#fts-admin-json-output').text('');
                                            $('#fts-admin-json-result').addClass('show');
                                            $('#fts-admin-response-shape-output').text('');
                                        });
                                }
                                
                                $('html, body').animate({
                                    scrollTop: $('#fts-admin-result').offset().top - 100
                                }, 500);
                            }
                        },
                        error: function() {
                            alert('Error generating query. Please try again.');
                        }
                    });
                });
                
                $('#fts-admin-copy-btn').on('click', function() {
                    const text = $('#fts-admin-example-url').text();
                    const $btn = $(this);
                    const originalText = $btn.text();
                    
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(function() {
                            $btn.text('Copied!');
                            setTimeout(function() {
                                $btn.text(originalText);
                            }, 2000);
                        });
                    } else {
                        const textarea = $('<textarea>');
                        textarea.val(text).css({position: 'fixed', opacity: 0});
                        $('body').append(textarea);
                        textarea[0].select();
                        document.execCommand('copy');
                        textarea.remove();
                        $btn.text('Copied!');
                        setTimeout(function() {
                            $btn.text(originalText);
                        }, 2000);
                    }
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Enqueue CSS and JavaScript
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'fts-query-builder-style',
            plugin_dir_url(__FILE__) . 'assets/style.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'fts-query-builder-script',
            plugin_dir_url(__FILE__) . 'assets/script.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('fts-query-builder-script', 'ftsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fts_query_nonce'),
            'rerankEndpoint' => rest_url('reranker/v1/reranked'),
            'siteUrl' => get_site_url()
        ));
    }
    
    /**
     * Build the FTS query string
     */
    public function build_query($basic, $must_contain, $must_not_contain, $wildcard, $phrase = '', $less_than = '', $greater_than = '', $or_terms = '', $parentheses = '') {
        $parts = array();
        
        // Add basic query
        if (!empty($basic)) {
            $parts[] = trim($basic);
        }
        
        // Add must-contain with + prefix
        if (!empty($must_contain)) {
            $terms = preg_split('/\s+/', trim($must_contain), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($terms as $term) {
                $parts[] = '+' . $term;
            }
        }
        
        // Add must-not-contain with - prefix
        if (!empty($must_not_contain)) {
            $terms = preg_split('/\s+/', trim($must_not_contain), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($terms as $term) {
                $normalized = ltrim($term, '+-');
                if ($normalized !== '') {
                    $parts[] = '-' . $normalized;
                }
            }
        }
        
        // Add wildcard with * suffix
        if (!empty($wildcard)) {
            $terms = preg_split('/\s+/', trim($wildcard), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($terms as $term) {
                $parts[] = $term . '*';
            }
        }
        
        // Add exact phrase with quotes
        if (!empty($phrase)) {
            $parts[] = '"' . trim($phrase) . '"';
        }
        
        // Add reduce terms (< prefix)
        if (!empty($less_than)) {
            $terms = preg_split('/\s+/', trim($less_than), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($terms as $term) {
                $parts[] = '<' . $term;
            }
        }
        
        // Add boost terms (> prefix)
        if (!empty($greater_than)) {
            $terms = preg_split('/\s+/', trim($greater_than), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($terms as $term) {
                $parts[] = '>' . $term;
            }
        }
        
        // Add OR terms with | separator
        if (!empty($or_terms)) {
            $terms = preg_split('/\s+/', trim($or_terms), -1, PREG_SPLIT_NO_EMPTY);
            if (count($terms) > 1) {
                $parts[] = '(' . implode('|', $terms) . ')';
            } else {
                $parts[] = $terms[0];
            }
        }
        
        // Add parentheses grouping (user provides the full expression)
        if (!empty($parentheses)) {
            $parts[] = trim($parentheses);
        }
        
        return implode(' ', $parts);
    }
    
    /**
     * AJAX handler to generate encoded query
     */
    public function ajax_generate_query() {
        check_ajax_referer('fts_query_nonce', 'nonce');
        
        $basic = isset($_POST['basic_query']) ? sanitize_text_field($_POST['basic_query']) : '';
        $must_contain = isset($_POST['must_contain']) ? sanitize_text_field($_POST['must_contain']) : '';
        $must_not_contain = isset($_POST['must_not_contain']) ? sanitize_text_field($_POST['must_not_contain']) : '';
        $wildcard = isset($_POST['wildcard']) ? sanitize_text_field($_POST['wildcard']) : '';
        $phrase = isset($_POST['phrase']) ? sanitize_text_field($_POST['phrase']) : '';
        $less_than = isset($_POST['less_than']) ? sanitize_text_field($_POST['less_than']) : '';
        $greater_than = isset($_POST['greater_than']) ? sanitize_text_field($_POST['greater_than']) : '';
        $or_terms = isset($_POST['or_terms']) ? sanitize_text_field($_POST['or_terms']) : '';
        $parentheses = isset($_POST['parentheses']) ? sanitize_text_field($_POST['parentheses']) : '';
        
        // Build query
        $query = $this->build_query($basic, $must_contain, $must_not_contain, $wildcard, $phrase, $less_than, $greater_than, $or_terms, $parentheses);
        
        // Encode query using rawurlencode for proper + encoding
        $encoded = rawurlencode($query);
        
        // Get current site URL
        $site_url = get_site_url();
        
        // Build the custom hybrid search API URL
        $api_url = $site_url . '/wp-json/search/v1/hybrid-search?query=' . $encoded;
        
        wp_send_json_success(array(
            'query' => $query,
            'encoded' => $encoded,
            'url' => $api_url
        ));
    }
    
    /**
     * Render the search form
     */
    public function render_search_form($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '', // Optional: redirect to search results page
        ), $atts);
        
        ob_start();
        ?>
        <div class="fts-query-builder">
            <form id="fts-search-form" class="fts-form">
                <div class="fts-form-group">
                    <label for="fts-basic-query">Basic Search Query</label>
                    <input type="text" id="fts-basic-query" name="basic_query" placeholder="e.g., wordpress plugin">
                    <span class="fts-help-text">Regular search terms</span>
                </div>

                <div class="fts-form-group">
                    <label for="fts-must-contain">
                        Must Contain <span class="fts-operator-badge fts-plus">+</span>
                    </label>
                    <input type="text" id="fts-must-contain" name="must_contain" placeholder="e.g., tutorial security">
                    <span class="fts-help-text">Terms that MUST appear (separate with spaces)</span>
                </div>

                <div class="fts-form-group">
                    <label for="fts-must-not-contain">
                        Must NOT Contain <span class="fts-operator-badge fts-minus">-</span>
                    </label>
                    <input type="text" id="fts-must-not-contain" name="must_not_contain" placeholder="e.g., premium paid">
                    <span class="fts-help-text">Exclude these terms (separate with spaces)</span>
                </div>

                <div class="fts-form-group">
                    <label for="fts-wildcard">
                        Wildcard <span class="fts-operator-badge fts-wildcard">*</span>
                    </label>
                    <input type="text" id="fts-wildcard" name="wildcard" placeholder="e.g., develop custom">
                    <span class="fts-help-text">Match variations (becomes develop* custom*)</span>
                </div>

                <div class="fts-form-group">
                    <label for="fts-phrase">
                        Exact Phrase <span class="fts-operator-badge" style="background: #e8eaf6; color: #283593;">"..."</span>
                    </label>
                    <input type="text" id="fts-phrase" name="phrase" placeholder="e.g., wordpress tutorial">
                    <span class="fts-help-text">Match exact phrase (becomes "wordpress tutorial")</span>
                </div>

                <div class="fts-form-group">
                    <label for="fts-less-than">
                        Reduce Terms <span class="fts-operator-badge" style="background: #fff9c4; color: #f57f17;">&lt;</span>
                    </label>
                    <input type="text" id="fts-less-than" name="less_than" placeholder="e.g., outdated generic">
                    <span class="fts-help-text">Reduce importance (becomes &lt;outdated &lt;generic)</span>
                </div>

                <div class="fts-form-group">
                    <label for="fts-greater-than">
                        Boost Terms <span class="fts-operator-badge" style="background: #ffe0b2; color: #e65100;">&gt;</span>
                    </label>
                    <input type="text" id="fts-greater-than" name="greater_than" placeholder="e.g., featured premium">
                    <span class="fts-help-text">Boost importance (becomes &gt;featured &gt;premium)</span>
                </div>

                <div class="fts-form-group">
                    <label for="fts-or-terms">
                        OR Terms <span class="fts-operator-badge" style="background: #f3e5f5; color: #6a1b9a;">|</span>
                    </label>
                    <input type="text" id="fts-or-terms" name="or_terms" placeholder="e.g., tutorial guide">
                    <span class="fts-help-text">Match any of these terms (becomes tutorial|guide)</span>
                </div>

                <div class="fts-form-group">
                    <label for="fts-parentheses">
                        Grouping (Parentheses) <span class="fts-operator-badge" style="background: #e0f2f1; color: #00695c;">()</span>
                    </label>
                    <input type="text" id="fts-parentheses" name="parentheses" placeholder="e.g., wordpress AND (plugin OR theme)">
                    <span class="fts-help-text">Group terms with parentheses for complex queries</span>
                </div>

                <button type="submit" class="fts-submit-btn">Generate Query</button>
            </form>

            <div id="fts-result" class="fts-result" style="display: none;">
                <h3>Encoded Query String:</h3>
                <div class="fts-query-output" id="fts-encoded-query"></div>
                <button type="button" class="fts-copy-btn" id="fts-copy-btn">Copy to Clipboard</button>
                
                <div class="fts-example-url">
                    <strong>Use this in your URL:</strong><br>
                    <code id="fts-example-url"></code>
                </div>
            </div>

            <div id="fts-rerank-result" class="fts-result fts-rerank-result" style="display: none;">
                <h3>Reranked Results (Final Set)</h3>
                <p class="fts-rerank-help">Results are fetched from <code class="fts-inline-code">/wp-json/reranker/v1/reranked</code> using your query.</p>
                <div id="fts-rerank-results" class="fts-rerank-grid"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize plugin
new FTS_Query_Builder_Reranking();
