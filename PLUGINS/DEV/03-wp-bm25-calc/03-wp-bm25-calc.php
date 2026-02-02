<?php
/**
 * Plugin Name:✅ 03 BM25 CALC
 * Description: BM25 algorithm with comprehensive FTS index management for all field combinations
 * Version: 1.1.0
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class BM25_Algorithm {
    private $k1, $b, $documents, $avgdl, $N;
    
    public function __construct($documents, $k1 = 1.5, $b = 0.75) {
        $this->documents = $documents;
        $this->k1 = $k1;
        $this->b = $b;
        $this->N = count($documents);
        $this->avgdl = $this->calculateAverageDocumentLength();
    }
    
    private function calculateAverageDocumentLength() {
        $totalLength = 0;
        foreach ($this->documents as $doc) {
            $totalLength += str_word_count($doc);
        }
        return $this->N > 0 ? $totalLength / $this->N : 0;
    }
    
    private function tokenize($text) {
        return array_map('strtolower', str_word_count($text, 1));
    }
    
    private function termFrequency($term, $document) {
        $tokens = $this->tokenize($document);
        return count(array_filter($tokens, fn($word) => $word === strtolower($term)));
    }
    
    private function documentFrequency($term) {
        $count = 0;
        foreach ($this->documents as $doc) {
            if ($this->termFrequency($term, $doc) > 0) $count++;
        }
        return $count;
    }
    
    private function calculateIDF($term) {
        $n = $this->documentFrequency($term);
        return log(($this->N - $n + 0.5) / ($n + 0.5));
    }
    
    public function score($query, $document, $docIndex = 0) {
        $queryTerms = array_unique($this->tokenize($query));
        $docLength = str_word_count($document);
        $score = 0.0;
        $details = [];
        
        foreach ($queryTerms as $term) {
            $tf = $this->termFrequency($term, $document);
            $idf = $this->calculateIDF($term);
            $df = $this->documentFrequency($term);
            $numerator = $tf * ($this->k1 + 1);
            $denominator = $tf + $this->k1 * (1 - $this->b + $this->b * ($docLength / $this->avgdl));
            $termScore = $idf * ($numerator / $denominator);
            $score += $termScore;
            $details[] = compact('term', 'tf', 'df', 'idf', 'numerator', 'denominator') + ['term_score' => $termScore, 'doc_length' => $docLength];
        }
        
        return compact('score', 'details');
    }
    
    public function search($query) {
        $results = [];
        foreach ($this->documents as $index => $doc) {
            $scoreData = $this->score($query, $doc, $index + 1);
            if ($scoreData['score'] > 0) {
                $results[] = ['index' => $index + 1, 'document' => $doc] + $scoreData;
            }
        }
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
        return $results;
    }
    
    public function getAvgdl() { return $this->avgdl; }
    public function getN() { return $this->N; }
}

class BM25_Calculation_Plugin {
    private static $instance = null;
    private $index_definitions = [];
    
    public static function get_instance() {
        return self::$instance ?? (self::$instance = new self());
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_bm25_calculate', [$this, 'ajax_calculate']);
        $this->initialize_index_definitions();
    }
    
    private function initialize_index_definitions() {
        $fields = ['product_name', 'product_short_description', 'expanded_description'];
        
        // Single fields
        foreach ($fields as $field) {
            $this->index_definitions[] = [
                'name' => "ft_{$field}",
                'columns' => [$field],
                'label' => ucwords(str_replace('_', ' ', $field))
            ];
        }
        
        // Two field combinations
        $this->index_definitions[] = ['name' => 'ft_prod_prod', 'columns' => ['product_name', 'product_short_description'], 'label' => 'Name + Short Desc'];
        $this->index_definitions[] = ['name' => 'ft_prod_expa', 'columns' => ['product_name', 'expanded_description'], 'label' => 'Name + Expanded'];
        $this->index_definitions[] = ['name' => 'ft_prod_expa_2', 'columns' => ['product_short_description', 'expanded_description'], 'label' => 'Short + Expanded'];
        
        // All three
        $this->index_definitions[] = ['name' => 'ft_all_fields', 'columns' => $fields, 'label' => 'All Fields'];
    }
    
    private function get_existing_indexes($table_name) {
        global $wpdb;
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table_name} WHERE Index_type = 'FULLTEXT'", ARRAY_A);
        $grouped = [];
        foreach ($indexes as $index) {
            $key_name = $index['Key_name'];
            if (!isset($grouped[$key_name])) {
                $grouped[$key_name] = ['name' => $key_name, 'columns' => []];
            }
            $grouped[$key_name]['columns'][] = $index['Column_name'];
        }
        return array_values($grouped);
    }
    
    private function index_exists($table_name, $index_name) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = %s AND index_name = %s",
            $table_name, $index_name
        )) > 0;
    }
    
    public function add_admin_menu() {
        add_menu_page('BM25 Calculation', '03 BM25 CALC', 'manage_options', 'bm25-calculation', [$this, 'render_admin_page'], 'dashicons-search', 3.3);
    }
    
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_bm25-calculation' !== $hook) return;
        wp_enqueue_script('bm25-calc-admin', plugin_dir_url(__FILE__) . 'assets/admin-script.js', ['jquery'], '1.1.0', true);
        wp_localize_script('bm25-calc-admin', 'bm25Ajax', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('bm25_calc_nonce')]);
    }
    
    public function ajax_calculate() {
        check_ajax_referer('bm25_calc_nonce', 'nonce');
        $query = sanitize_text_field($_POST['query']);
        $k1 = floatval($_POST['k1']);
        $b = floatval($_POST['b']);
        $limit = intval($_POST['limit']);
        $documents = array_map('sanitize_text_field', $_POST['documents']);
        $bm25 = new BM25_Algorithm($documents, $k1, $b);
        $results = $bm25->search($query);
        if ($limit > 0) $results = array_slice($results, 0, $limit);
        wp_send_json_success(['results' => $results, 'avgdl' => $bm25->getAvgdl(), 'total_docs' => $bm25->getN()]);
    }
    
    public function render_admin_page() {
        global $wpdb;
        $table_name = 'wp_products';
        $message = '';
        $message_type = 'success';
        
        if (isset($_POST['fts_action']) && check_admin_referer('bm25_fts_action')) {
            $action = $_POST['fts_action'];
            
            if ($action === 'delete_all') {
                $existing = $this->get_existing_indexes($table_name);
                $deleted = 0;
                foreach ($existing as $index) {
                    $wpdb->query("ALTER TABLE {$table_name} DROP INDEX {$index['name']}");
                    $deleted++;
                }
                $message = "Deleted {$deleted} index(es).";
            } elseif ($action === 'delete_single' && isset($_POST['index_name'])) {
                $index_name = sanitize_text_field($_POST['index_name']);
                $result = $wpdb->query("ALTER TABLE {$table_name} DROP INDEX {$index_name}");
                $message = $result !== false ? "Deleted: {$index_name}" : "Failed to delete: {$index_name}";
                $message_type = $result !== false ? 'success' : 'error';
            } elseif ($action === 'create' && isset($_POST['index_key'])) {
                $index_key = intval($_POST['index_key']);
                if (isset($this->index_definitions[$index_key])) {
                    $def = $this->index_definitions[$index_key];
                    if ($this->index_exists($table_name, $def['name'])) {
                        $message = "Index {$def['name']} already exists!";
                        $message_type = 'warning';
                    } else {
                        $sql = "ALTER TABLE {$table_name} ADD FULLTEXT INDEX {$def['name']} (" . implode(', ', $def['columns']) . ")";
                        $result = $wpdb->query($sql);
                        $message = $result !== false ? "Created: {$def['name']}" : "Failed: {$wpdb->last_error}";
                        $message_type = $result !== false ? 'success' : 'error';
                    }
                }
            }
        }
        
        $existing_indexes = $this->get_existing_indexes($table_name);
        
        // Load products from database
        $products = $wpdb->get_results(
            "SELECT id, product_name, product_short_description, expanded_description
             FROM {$table_name}
             WHERE product_name IS NOT NULL AND product_name != ''
             LIMIT 100",
            ARRAY_A
        );
        $product_names = array_map(function($p) { return $p['product_name']; }, $products);
        $documents_text = implode("\n", $product_names);
        
        ?>
        <div class="wrap">
            <h1>🔍 BM25 Search Ranking Calculator</h1>
            
            <?php if ($message): ?>
            <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="index-mgmt">
                <h2>📊 Full-Text Search Index Management</h2>
                
                <div class="current-idx">
                    <h3>Current Full-Text Search Indexes</h3>
                    <?php if (empty($existing_indexes)): ?>
                        <p class="no-idx"><em>No full-text search indexes exist.</em></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width:30%">Index Name</th>
                                    <th style="width:50%">Columns</th>
                                    <th style="width:20%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($existing_indexes as $index): ?>
                                <tr>
                                    <td><code><?php echo esc_html($index['name']); ?></code></td>
                                    <td><?php echo esc_html(implode(', ', $index['columns'])); ?></td>
                                    <td>
                                        <form method="post" style="display:inline">
                                            <?php wp_nonce_field('bm25_fts_action'); ?>
                                            <input type="hidden" name="fts_action" value="delete_single">
                                            <input type="hidden" name="index_name" value="<?php echo esc_attr($index['name']); ?>">
                                            <button type="submit" class="button button-small btn-del" onclick="return confirm('Delete this index?')">🗑️ Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <form method="post" style="margin-top:15px">
                            <?php wp_nonce_field('bm25_fts_action'); ?>
                            <input type="hidden" name="fts_action" value="delete_all">
                            <button type="submit" class="button button-secondary" onclick="return confirm('Delete ALL indexes?')">🗑️ Delete All</button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <div class="add-idx">
                    <h3>➕ Create New Full-Text Search Index</h3>
                    <form method="post">
                        <?php wp_nonce_field('bm25_fts_action'); ?>
                        <input type="hidden" name="fts_action" value="create">
                        <table class="form-table">
                            <tr>
                                <th><label for="index_key">Configuration:</label></th>
                                <td>
                                    <select name="index_key" id="index_key" class="regular-text" required>
                                        <option value="">-- Select fields --</option>
                                        <optgroup label="Single Field">
                                            <?php for ($i = 0; $i < 3; $i++): ?>
                                                <option value="<?php echo $i; ?>"><?php echo esc_html($this->index_definitions[$i]['label']); ?></option>
                                            <?php endfor; ?>
                                        </optgroup>
                                        <optgroup label="Two Fields">
                                            <?php for ($i = 3; $i < 6; $i++): ?>
                                                <option value="<?php echo $i; ?>"><?php echo esc_html($this->index_definitions[$i]['label']); ?></option>
                                            <?php endfor; ?>
                                        </optgroup>
                                        <optgroup label="All Fields">
                                            <option value="6"><?php echo esc_html($this->index_definitions[6]['label']); ?></option>
                                        </optgroup>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p class="submit"><button type="submit" class="button button-primary">➕ Create Index</button></p>
                    </form>
                </div>
                
                <div class="ref-box">
                    <h4>ℹ️ Index Reference</h4>
                    <table class="wp-list-table widefat">
                        <thead><tr><th>Type</th><th>Fields</th><th>Use Case</th></tr></thead>
                        <tbody>
                            <tr><td>Single</td><td>product_name</td><td>Fast product name searches</td></tr>
                            <tr><td>Single</td><td>product_short_description</td><td>Search short descriptions</td></tr>
                            <tr><td>Single</td><td>expanded_description</td><td>Deep description searches</td></tr>
                            <tr><td>Two</td><td>name + short_description</td><td>Title & summary search</td></tr>
                            <tr><td>Two</td><td>name + expanded_description</td><td>Title & detail search</td></tr>
                            <tr><td>Two</td><td>short + expanded</td><td>All descriptions</td></tr>
                            <tr><td>All</td><td>All three</td><td>Comprehensive search</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="bm25-grid">
                <div class="bm25-box">
                    <h2>Search Configuration</h2>
                    <div class="fg">
                        <label for="bm25-query">Query:</label>
                        <input type="text" id="bm25-query" class="widefat" value="smart led">
                    </div>
                    <div class="fg">
                        <label for="bm25-limit">Limit:</label>
                        <input type="number" id="bm25-limit" class="small-text" value="3" min="0">
                    </div>
                    <h3>Parameters</h3>
                    <div class="fg">
                        <label for="bm25-k1">k1 (Term Frequency Saturation): <span class="pv" id="k1-value">1.5</span></label>
                        <input type="range" id="bm25-k1" min="0.1" max="10" step="0.1" value="1.5" class="widefat">
                        <p class="description">Typical range: 1.2-2.0</p>
                    </div>
                    <div class="fg">
                        <label for="bm25-b">b (Document Length Normalization): <span class="pv" id="b-value">0.75</span></label>
                        <input type="range" id="bm25-b" min="0" max="1" step="0.05" value="0.75" class="widefat">
                        <p class="description">Typical range: 0.5-0.8</p>
                    </div>
                    <div class="fg">
                        <label for="bm25-documents">Documents (one per line):</label>
                        <textarea id="bm25-documents" rows="8" class="widefat"><?php echo esc_textarea($documents_text); ?></textarea>
                        <p class="description">Product names from wp_products table (max 100)</p>
                    </div>
                    <button id="bm25-calculate" class="button button-primary button-large">Calculate</button>
                </div>
                
                <div class="bm25-box">
                    <h2>Results</h2>
                    <div id="bm25-stats"></div>
                    <h3>Rankings</h3>
                    <div id="bm25-results"></div>
                    <h3>Details</h3>
                    <div id="bm25-details"></div>
                </div>
            </div>
        </div>
        
        <style>
        .index-mgmt{background:#fff;padding:20px;margin:20px 0;border:1px solid #ccd0d4;box-shadow:0 1px 1px rgba(0,0,0,.04)}
        .current-idx{background:#f0f6fc;border-left:4px solid #2271b1;padding:15px;margin:15px 0}
        .add-idx{background:#f9f9f9;border:1px solid #ddd;padding:15px;margin:15px 0}
        .ref-box{background:#fffbcc;border:1px solid #e6db55;padding:15px;margin:15px 0}
        .no-idx{font-style:italic;color:#666}
        .btn-del{color:#d63638}
        .bm25-grid{display:grid;grid-template-columns:1fr 2fr;gap:20px;margin-top:20px}
        .bm25-box{background:#fff;padding:20px;border:1px solid #ccd0d4}
        .fg{margin-bottom:20px}.fg label{display:block;font-weight:600;margin-bottom:5px}
        .pv{color:#2271b1;font-weight:bold}
        .result-item{background:#f9f9f9;border-left:4px solid #2271b1;padding:15px;margin-bottom:15px}
        .result-item.rank-1{border-left-color:#d4af37;background:#fffdf0}
        .result-header{display:flex;justify-content:space-between;margin-bottom:10px}
        .result-rank{font-size:24px;font-weight:bold}.result-score{font-size:18px;font-weight:bold;color:#2271b1}
        .result-meta{margin-top:8px}
        .result-meta p{margin:4px 0}
        .result-meta .label{font-weight:600;color:#1d2327}
        .result-meta .value{color:#3c434a}
        .stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px}
        .stat-item{padding:10px;background:#f0f0f1;border-radius:4px}
        .stat-label{font-size:12px;color:#646970}.stat-value{font-size:24px;font-weight:bold;color:#2271b1}
        .term-calc{margin:10px 0;padding:10px;background:#f0f0f1;border-left:3px solid #2271b1}
        @media(max-width:1200px){.bm25-grid{grid-template-columns:1fr}}
        </style>
        
        <script>
        jQuery(document).ready(function($){
            // Update slider value displays
            $('#bm25-k1').on('input',function(){$('#k1-value').text($(this).val())});
            $('#bm25-b').on('input',function(){$('#b-value').text($(this).val())});
            
            const productData = <?php echo wp_json_encode($products); ?>;

            $('#bm25-calculate').on('click',function(){
                const query=$('#bm25-query').val(),k1=$('#bm25-k1').val(),b=$('#bm25-b').val(),
                limit=parseInt($('#bm25-limit').val());
                const documents = productData.length
                    ? productData.map(p => [p.product_name, p.product_short_description, p.expanded_description].filter(Boolean).join(' '))
                    : $('#bm25-documents').val().split('\n').filter(d=>d.trim());
                if(!query.trim()||!documents.length){alert('Enter query and documents');return}
                $('#bm25-results').html('<p>Calculating...</p>');
                $.ajax({url:bm25Ajax.ajax_url,type:'POST',data:{action:'bm25_calculate',nonce:bm25Ajax.nonce,query,k1,b,limit,documents},
                    success:r=>r.success&&displayResults(r.data,query,k1,b)});
            });
            function displayResults(data,query,k1,b){
                $('#bm25-stats').html(`<div class="stats-grid">
                    <div class="stat-item"><div class="stat-label">Docs</div><div class="stat-value">${data.total_docs}</div></div>
                    <div class="stat-item"><div class="stat-label">Avg Length</div><div class="stat-value">${data.avgdl.toFixed(2)}</div></div>
                    <div class="stat-item"><div class="stat-label">k1</div><div class="stat-value">${k1}</div></div>
                    <div class="stat-item"><div class="stat-label">b</div><div class="stat-value">${b}</div></div>
                </div>`);
                let rh='';
                data.results.forEach((r,i)=>{
                    const rank=i+1,medal=rank===1?'🥇':rank===2?'🥈':rank===3?'🥉':`#${rank}`;
                    const product = productData.length ? productData[r.index - 1] : null;
                    rh+=`<div class="result-item rank-${rank}"><div class="result-header">
                        <span class="result-rank">${medal}</span><span class="result-score">Score: ${r.score.toFixed(4)}</span>
                    </div>
                    ${product ? `
                        <div class="result-meta">
                            <p><span class="label">ID:</span> <span class="value">${product.id ?? '-'}</span></p>
                            <p><span class="label">Product Name:</span> <span class="value">${product.product_name ?? '-'}</span></p>
                            <p><span class="label">Short Description:</span> <span class="value">${product.product_short_description ?? '-'}</span></p>
                            <p><span class="label">Expanded Description:</span> <span class="value">${product.expanded_description ?? '-'}</span></p>
                        </div>
                    ` : `<div>${r.document}</div>`}
                    </div>`;
                });
                $('#bm25-results').html(rh);
                let dh='';
                data.results.forEach(r=>{
                    dh+=`<h4>Doc ${r.index}</h4>`;
                    r.details.forEach(d=>dh+=`<div class="term-calc"><strong>"${d.term}"</strong>: tf=${d.tf}, score=${d.term_score.toFixed(4)}</div>`);
                    dh+=`<p><strong>Total: ${r.score.toFixed(4)}</strong></p><hr>`;
                });
                $('#bm25-details').html(dh);
            }
            $('#bm25-calculate').click();
        });
        </script>
        <?php
    }
}

BM25_Calculation_Plugin::get_instance();
