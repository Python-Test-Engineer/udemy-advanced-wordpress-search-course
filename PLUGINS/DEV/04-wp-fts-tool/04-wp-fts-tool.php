<?php
/**
 * Plugin Name: ✅ 04 FTS TOOL
 * Plugin URI: https://example.com/fts-teaching
 * Description: Interactive demonstration of core Full-Text Search algorithms (TF, IDF, TF-IDF, BM25) for teaching purposes
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: fts-teaching
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FTS_Teaching_Plugin {
    
    private $documents;
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_fts_search', array($this, 'handle_search'));
        $this->init_documents();
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'FTS Teaching Tool',
            '04 FTS TOOL',
            'manage_options',
            'fts-teaching',
            array($this, 'render_admin_page'),
            'dashicons-search',
            3.4
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_fts-teaching') {
            return;
        }
        
        wp_enqueue_style('fts-teaching-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_enqueue_script('fts-teaching-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('fts-teaching-script', 'ftsAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fts_search_nonce')
        ));
    }
    // #region docs
    private function init_documents() {
        $this->documents = array(
            array(
                'id' => 1,
                'title' => 'Introduction to Machine Learning DEEP',
                'content' => 'Machine learning is a subset of artificial intelligence. Machine learning algorithms learn from data. Machine learning machine learning machine learning is everywhere today. Machine learning (ML) is a subset of artificial intelligence (AI) that enables systems to automatically learn and improve from data, rather than being explicitly programmed for every specific task. By identifying patterns, correlations, and anomalies in vast datasets, ML models make predictions, decisions, and inferences. It is widely used in applications like recommendation engines, chatbots, fraud detection, and self-driving cars. Machine learning (ML) is a field of study in artificial intelligence concerned with the development and study of statistical algorithms that can learn from data and generalize to unseen data, and thus perform tasks without explicit instructions.[1] Within a subdiscipline in machine learning, advances in the field of deep learning have allowed neural networks, a class of statistical algorithms, to surpass many previous machine learning approaches in performance.'
            ),
            array(
                'id' => 2,
                'title' => 'Deep Learning Fundamentals',
                'content' => 'Deep learning uses neural networks with multiple layers. This technique has revolutionized computer vision and natural language processing. Modern AI systems rely heavily on deep learning architectures.'
            ),
            array(
                'id' => 3,
                'title' => 'Natural Language Processing Overview',
                'content' => 'Natural language processing enables computers to understand human language. Applications include translation, sentiment analysis, and chatbots. NLP combines linguistics with machine learning techniques. Machine Learning.'
            ),
            array(
                'id' => 4,
                'title' => 'Computer Vision Applications',
                'content' => 'Computer vision allows machines to interpret visual information. Object detection, facial recognition, and autonomous vehicles use computer vision. Deep neural networks have dramatically improved vision accuracy.'
            ),
            array(
                'id' => 5,
                'title' => 'Reinforcement Learning Basics',
                'content' => 'Reinforcement learning trains agents through rewards and penalties. The agent learns optimal strategies by interacting with an environment. Gaming AI and robotics commonly use reinforcement learning approaches.'
            ),
            array(
                'id' => 6,
                'title' => 'Neural Network Architecture',
                'content' => 'Neural networks consist of interconnected nodes organized in layers. Each connection has a weight that adjusts during training. Backpropagation is the primary algorithm for training neural networks.'
            ),
            array(
                'id' => 7,
                'title' => 'Data Preprocessing Techniques',
                'content' => 'Data preprocessing prepares raw data for machine learning models. Common steps include normalization, handling missing values, and feature engineering. Quality data preprocessing significantly impacts model performance.'
            ),
            array(
                'id' => 8,
                'title' => 'The AI Revolution',
                'content' => 'Artificial intelligence is transforming every industry globally. From healthcare diagnostics to financial forecasting, AI applications grow daily. Ethical considerations and responsible AI development are increasingly important.'
            ),
            array(
                'id' => 9,
                'title' => 'Supervised Learning Methods',
                'content' => 'Supervised learning uses labeled training data to make predictions. Classification and regression are the two main supervised learning tasks. Popular algorithms include decision trees, support vector machines, and neural networks.'
            ),
            array(
                'id' => 10,
                'title' => 'Unsupervised Learning Techniques',
                'content' => 'Unsupervised learning finds patterns in unlabeled data without guidance. Clustering groups similar items while dimensionality reduction simplifies complex datasets. These techniques are valuable for exploratory data analysis.'
            )
        );
    }
    
    public function handle_search() {
        check_ajax_referer('fts_search_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        $method = sanitize_text_field($_POST['method']);
        
        $results = array();
        
        switch ($method) {
            case 'tf':
                $results = $this->search_tf($query);
                break;
            case 'idf':
                $results = $this->search_tfidf($query);
                break;
            case 'bm25':
                $results = $this->search_bm25($query);
                break;
        }
        
        wp_send_json_success($results);
    }
    
    private function search_tf($query) {
        $terms = array_map('strtolower', explode(' ', $query));
        $scores = array();
        $total_docs = count($this->documents);
        
        foreach ($this->documents as $doc) {
            $content = strtolower($doc['title'] . ' ' . $doc['content']);
            $doc_length = str_word_count($content);
            $score = 0;
            $term_details = array();
            
            foreach ($terms as $term) {
                $count = substr_count($content, $term);
                if ($count > 0) {
                    $score += $count;
                    $term_details[] = "$term: $count";
                }
            }
            
            if ($score > 0) {
                $scores[] = array(
                    'doc' => $doc,
                    'score' => $score,
                    'explanation' => "Total TF: $score",
                    'details' => array(
                        'method' => 'Term Frequency (TF)',
                        'formula' => 'TF = count of term in document',
                        'term_breakdown' => implode(', ', $term_details),
                        'total_score' => $score,
                        'doc_length' => $doc_length,
                        'note' => 'TF rewards documents with more term occurrences'
                    )
                );
            }
        }
        
        usort($scores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $scores;
    }
    
    private function search_tfidf($query) {
        $terms = array_map('strtolower', explode(' ', $query));
        $scores = array();
        $total_docs = count($this->documents);
        
        // Calculate IDF for each term across all documents
        $idf = array();
        $idf_details = array();
        
        foreach ($terms as $term) {
            $docs_with_term = 0;
            foreach ($this->documents as $doc) {
                $content = strtolower($doc['title'] . ' ' . $doc['content']);
                if (strpos($content, $term) !== false) {
                    $docs_with_term++;
                }
            }
            $raw_idf = $docs_with_term > 0 ? log($total_docs / $docs_with_term) : 0;
            $idf[$term] = $raw_idf;
            $idf_details[$term] = array(
                'docs_with_term' => $docs_with_term,
                'total_docs' => $total_docs,
                'calculation' => "log($total_docs / $docs_with_term) = " . round($raw_idf, 4)
            );
        }
        
        // Calculate TF-IDF scores
        foreach ($this->documents as $doc) {
            $content = strtolower($doc['title'] . ' ' . $doc['content']);
            $doc_length = str_word_count($content);
            $score = 0;
            $term_calculations = array();
            
            foreach ($terms as $term) {
                $tf = substr_count($content, $term);
                if ($tf > 0) {
                    $tfidf = $tf * $idf[$term];
                    $score += $tfidf;
                    $term_calculations[] = array(
                        'term' => $term,
                        'tf' => $tf,
                        'idf' => round($idf[$term], 4),
                        'tfidf' => round($tfidf, 4),
                        'rarity' => $idf_details[$term]['docs_with_term'] === 1 ? 'Very Rare' : 
                                   ($idf_details[$term]['docs_with_term'] <= 3 ? 'Rare' : 'Common')
                    );
                }
            }
            
            if ($score > 0) {
                $scores[] = array(
                    'doc' => $doc,
                    'score' => round($score, 2),
                    'explanation' => "TF-IDF Score: " . round($score, 2),
                    'details' => array(
                        'method' => 'TF-IDF (Term Frequency × Inverse Document Frequency)',
                        'formula' => 'TF-IDF = TF × IDF, where IDF = log(N / df)',
                        'idf_summary' => $idf_details,
                        'term_calculations' => $term_calculations,
                        'total_score' => round($score, 4),
                        'doc_length' => $doc_length,
                        'note' => 'TF-IDF balances term frequency with rarity - rare terms get higher weight'
                    )
                );
            }
        }
        
        usort($scores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $scores;
    }
    
    private function search_bm25($query, $k1 = 1.5, $b = 0.75) {
        $terms = array_map('strtolower', explode(' ', $query));
        $scores = array();
        $total_docs = count($this->documents);
        
        // Calculate document lengths and average
        $doc_lengths = array();
        $total_length = 0;
        foreach ($this->documents as $doc) {
            $length = str_word_count($doc['title'] . ' ' . $doc['content']);
            $doc_lengths[$doc['id']] = $length;
            $total_length += $length;
        }
        $avg_length = $total_length / $total_docs;
        
        // Calculate IDF with BM25's specific formula
        $idf = array();
        $idf_details = array();
        
        foreach ($terms as $term) {
            $docs_with_term = 0;
            foreach ($this->documents as $doc) {
                $content = strtolower($doc['title'] . ' ' . $doc['content']);
                if (strpos($content, $term) !== false) {
                    $docs_with_term++;
                }
            }
            // BM25 IDF formula
            $raw_idf = log(($total_docs - $docs_with_term + 0.5) / ($docs_with_term + 0.5) + 1);
            $idf[$term] = $raw_idf;
            $idf_details[$term] = array(
                'docs_with_term' => $docs_with_term,
                'total_docs' => $total_docs,
                'calculation' => "log(($total_docs - $docs_with_term + 0.5) / ($docs_with_term + 0.5) + 1) = " . round($raw_idf, 4)
            );
        }
        
        // Calculate BM25 scores
        foreach ($this->documents as $doc) {
            $content = strtolower($doc['title'] . ' ' . $doc['content']);
            $doc_length = $doc_lengths[$doc['id']];
            $score = 0;
            $term_calculations = array();
            
            foreach ($terms as $term) {
                $tf = substr_count($content, $term);
                if ($tf > 0) {
                    // BM25 term frequency saturation
                    $norm = 1 - $b + $b * ($doc_length / $avg_length);
                    $denominator = $tf + $k1 * $norm;
                    $term_score = $idf[$term] * ($tf * ($k1 + 1)) / $denominator;
                    $score += $term_score;
                    
                    $term_calculations[] = array(
                        'term' => $term,
                        'raw_tf' => $tf,
                        'idf' => round($idf[$term], 4),
                        'length_norm' => round($norm, 4),
                        'saturated_tf' => round(($tf * ($k1 + 1)) / $denominator, 4),
                        'term_score' => round($term_score, 4)
                    );
                }
            }
            
            if ($score > 0) {
                $scores[] = array(
                    'doc' => $doc,
                    'score' => round($score, 2),
                    'explanation' => "BM25 Score: " . round($score, 2) . " (k1=$k1, b=$b)",
                    'details' => array(
                        'method' => 'BM25 (Best Match 25)',
                        'formula' => 'BM25 = IDF × (TF × (k1+1)) / (TF + k1 × (1-b + b×(doc_len/avg_len)))',
                        'parameters' => array(
                            'k1' => $k1,
                            'b' => $b,
                            'k1_explanation' => 'Controls term frequency saturation (higher = less saturation)',
                            'b_explanation' => 'Controls length normalization (0 = no normalization, 1 = full normalization)'
                        ),
                        'avg_doc_length' => round($avg_length, 2),
                        'this_doc_length' => $doc_length,
                        'idf_summary' => $idf_details,
                        'term_calculations' => $term_calculations,
                        'total_score' => round($score, 4),
                        'note' => 'BM25 prevents over-weighting of repeated terms through saturation'
                    )
                );
            }
        }
        
        usort($scores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $scores;
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap fts-teaching-wrap">
            <h1>Full-Text Search Algorithms Teaching Tool</h1>
            <p style="font-size: 14px; color: #666;">Explore TF, IDF, TF-IDF, and BM25 scoring algorithms with detailed mathematical breakdowns.</p>
            
            <div class="fts-container">
                <div class="fts-search-panel">
                    <h2>Search Interface</h2>
                    
                    <div class="fts-search-form">
                        <input type="text" id="fts-query" class="fts-query-input" placeholder="Enter search query..." value="machine learning">
                        
                        <select id="fts-method" class="fts-method-select">
                            <option value="tf">Term Frequency (TF)</option>
                            <option value="idf" selected>TF-IDF</option>
                            <option value="bm25">BM25</option>
                        </select>
                        
                        <button id="fts-search-btn" class="button button-primary">Search</button>
                    </div>
                    
                    <div class="fts-method-info">
                        <h3>Algorithm Descriptions</h3>
                        <ul>
                            <li><strong>TF (Term Frequency):</strong> Simple count of term occurrences. Rewards repetition without limit.</li>
                            <li><strong>TF-IDF:</strong> Multiplies TF by IDF (Inverse Document Frequency). Rare terms get higher weight.</li>
                            <li><strong>BM25:</strong> Applies saturation to TF and includes length normalization. Prevents keyword stuffing from dominating results.</li>
                        </ul>
                        
                        <div class="fts-scoring-details" style="margin-top: 15px; padding: 15px; background: #e8f5e8; border-radius: 5px; font-size: 13px;">
                            <strong>Key Formulas:</strong>
                            <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                                <li><strong>TF:</strong> Raw count of term in document</li>
                                <li><strong>IDF:</strong> log(Total Docs / Docs with Term)</li>
                                <li><strong>TF-IDF:</strong> TF × IDF</li>
                                <li><strong>BM25:</strong> IDF × (TF×(k1+1)) / (TF + k1×(1-b + b×(L/avgL)))</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="fts-example-queries">
                        <h3>Example Queries</h3>
                        <ul>
                            <li><code>machine learning</code> - Compare TF vs BM25 saturation effect</li>
                            <li><code>learning</code> - See how common terms score differently</li>
                            <li><code>reinforcement</code> - Rare term gets high IDF weight</li>
                            <li><code>neural network</code> - Multiple term scoring</li>
                            <li><code>deep</code> - Compare across all three algorithms</li>
                            <li><code>machine learning machine learning</code> - TF explosion vs BM25 saturation</li>
                            <li><code>data</code> - Very common term (appears in many docs)</li>
                            <li><code>artificial intelligence</code> - Test phrase scoring</li>
                        </ul>
                        <p style="font-size: 12px; color: #666; margin-top: 10px;">
                            <strong>Tip:</strong> Try the same query with different algorithms to see how scoring differs!
                        </p>
                    </div>
                </div>
                
                <div class="fts-results-panel">
                    <h2>Search Results</h2>
                    <div id="fts-results" class="fts-results">
                        <p class="fts-placeholder">Enter a query and click Search to see results...</p>
                    </div>
                </div>
            </div>
            
            <div class="fts-documents-section">
                <h2>Document Collection (<?php echo count($this->documents); ?> documents)</h2>
                <div class="fts-documents">
                    <?php foreach ($this->documents as $doc): ?>
                        <div class="fts-document">
                            <h4>Doc <?php echo $doc['id']; ?>: <?php echo esc_html($doc['title']); ?></h4>
                            <p><?php echo esc_html($doc['content']); ?></p>
                            <small>Words: <?php echo str_word_count($doc['content']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <style>
        .fts-teaching-wrap {
            max-width: 1400px;
            margin: 20px;
        }
        
        .fts-container {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .fts-search-panel,
        .fts-results-panel {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        
        .fts-search-form {
            margin-bottom: 20px;
        }
        
        .fts-query-input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .fts-method-select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }
        
        .fts-method-info,
        .fts-example-queries {
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        
        .fts-method-info h3,
        .fts-example-queries h3 {
            margin-top: 0;
            font-size: 14px;
        }
        
        .fts-method-info ul,
        .fts-example-queries ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        
        .fts-method-info li,
        .fts-example-queries li {
            font-size: 13px;
            margin-bottom: 8px;
        }
        
        .fts-example-queries code {
            background: #e0e0e0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .fts-results {
            min-height: 400px;
            font-size: 1.5rem;
        }
        
        .fts-placeholder {
            color: #666;
            font-style: italic;
        }
        
        .fts-result-item {
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
            border-left: 4px solid #2271b1;
            border-radius: 4px;
        }
        
        .fts-result-rank {
            display: inline-block;
            background: #2271b1;
            color: #fff;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 1.5rem;
            margin-right: 10px;
            margin-bottom: 6px;
            padding: 5px;
        }
        
        .fts-result-score {
            display: inline-block;
            background: #46b450;
            color: #fff;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 1.5rem;
            padding: 5px;
        }
        
        .fts-result-title {
            font-weight: bold;
            margin: 10px 0 5px 0;
            font-size: 1.5rem;
            padding-top:10px;
        }
        
        .fts-result-content {
            color: #555;
            font-size: 1.5rem;
            line-height: 1.5;
        }
        
        .fts-result-explanation {
            margin-top: 8px;
            padding: 8px;
            background: #fff;
            border-radius: 3px;
            font-size: 1.5rem;
            color: green;
            border: 1px solid #46b450;
        }
        
        .fts-result-details {
            margin-top: 12px;
            padding: 15px;
            background: #f0f7ff;
            border-radius: 5px;
            font-size: 13px;
            border: 1px solid #c5d9f1;
        }
        
        .fts-result-details h4 {
            margin: 0 0 10px 0;
            color: #2271b1;
            font-size: 14px;
        }
        
        .fts-result-details .formula {
            background: #fff;
            padding: 8px 12px;
            border-radius: 3px;
            font-family: monospace;
            margin: 8px 0;
            border-left: 3px solid #2271b1;
        }
        
        .fts-result-details table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 12px;
        }
        
        .fts-result-details th,
        .fts-result-details td {
            border: 1px solid #c5d9f1;
            padding: 6px 8px;
            text-align: left;
        }
        
        .fts-result-details th {
            background: #e8f0fe;
            font-weight: 600;
        }
        
        .fts-result-details .note {
            margin-top: 10px;
            padding: 8px;
            background: #fff3cd;
            border-radius: 3px;
            color: #856404;
            font-style: italic;
        }
        
        .fts-documents-section {
            margin-top: 30px;
        }
        
        .fts-documents {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .fts-document {
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .fts-document h4 {
            margin: 0 0 10px 0;
            color: #2271b1;
        }
        
        .fts-document p {
            font-size: 13px;
            line-height: 1.5;
            color: #555;
        }
        
        .fts-document small {
            color: #999;
        }
        
        .fts-loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .fts-parameter-box {
            background: #fff;
            padding: 10px;
            border-radius: 3px;
            margin: 8px 0;
            border: 1px solid #ddd;
        }
        
        .fts-parameter-box strong {
            color: #2271b1;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#fts-search-btn').on('click', function() {
                var query = $('#fts-query').val();
                var method = $('#fts-method').val();
                
                if (!query) {
                    alert('Please enter a search query');
                    return;
                }
                
                $('#fts-results').html('<div class="fts-loading">Searching...</div>');
                
                $.ajax({
                    url: ftsAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'fts_search',
                        nonce: ftsAjax.nonce,
                        query: query,
                        method: method
                    },
                    success: function(response) {
                        if (response.success) {
                            displayResults(response.data, method);
                        } else {
                            $('#fts-results').html('<p class="error">Search failed</p>');
                        }
                    },
                    error: function() {
                        $('#fts-results').html('<p class="error">Search error occurred</p>');
                    }
                });
            });
            
            $('#fts-query').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#fts-search-btn').click();
                }
            });
            
            function displayResults(results, method) {
                if (results.length === 0) {
                    $('#fts-results').html('<p class="fts-placeholder">No results found</p>');
                    return;
                }
                
                var methodName = method.toUpperCase();
                var html = '<h3>Found ' + results.length + ' result(s) using ' + methodName + '</h3>';
                
                results.forEach(function(result, index) {
                    html += '<div class="fts-result-item">';
                    html += '<span class="fts-result-rank">#' + (index + 1) + '</span>';
                    html += '<span class="fts-result-score">Score: ' + result.score + '</span>';
                    html += '<div class="fts-result-title">Doc ' + result.doc.id + ': ' + result.doc.title + '</div>';
                    html += '<div class="fts-result-content">' + result.doc.content + '</div>';
                    html += '<div class="fts-result-explanation">' + result.explanation + '</div>';
                    
                    // Add detailed breakdown
                    if (result.details) {
                        html += '<div class="fts-result-details">';
                        html += '<h4>📊 ' + result.details.method + ' Breakdown</h4>';
                        
                        if (result.details.formula) {
                            html += '<div class="formula">' + result.details.formula + '</div>';
                        }
                        
                        // Parameters section (for BM25)
                        if (result.details.parameters) {
                            html += '<div class="fts-parameter-box">';
                            html += '<strong>Parameters:</strong><br>';
                            html += 'k1 = ' + result.details.parameters.k1 + ' - ' + result.details.parameters.k1_explanation + '<br>';
                            html += 'b = ' + result.details.parameters.b + ' - ' + result.details.parameters.b_explanation;
                            html += '</div>';
                        }
                        
                        // IDF Summary
                        if (result.details.idf_summary) {
                            html += '<strong>IDF Calculations:</strong>';
                            html += '<table>';
                            html += '<tr><th>Term</th><th>Docs With Term</th><th>Calculation</th></tr>';
                            for (var term in result.details.idf_summary) {
                                var idfInfo = result.details.idf_summary[term];
                                html += '<tr>';
                                html += '<td><code>' + term + '</code></td>';
                                html += '<td>' + idfInfo.docs_with_term + ' / ' + idfInfo.total_docs + '</td>';
                                html += '<td>' + idfInfo.calculation + '</td>';
                                html += '</tr>';
                            }
                            html += '</table>';
                        }
                        
                        // Term calculations table
                        if (result.details.term_calculations && result.details.term_calculations.length > 0) {
                            html += '<strong>Per-Term Scoring:</strong>';
                            html += '<table>';
                            
                            // Different headers based on method
                            if (method === 'tf') {
                                html += '<tr><th>Term</th><th>TF (Count)</th></tr>';
                                result.details.term_calculations.forEach(function(calc) {
                                    html += '<tr><td><code>' + calc.term + '</code></td><td>' + calc.tf + '</td></tr>';
                                });
                            } else if (method === 'idf') {
                                html += '<tr><th>Term</th><th>TF</th><th>IDF</th><th>TF×IDF</th><th>Rarity</th></tr>';
                                result.details.term_calculations.forEach(function(calc) {
                                    html += '<tr>';
                                    html += '<td><code>' + calc.term + '</code></td>';
                                    html += '<td>' + calc.tf + '</td>';
                                    html += '<td>' + calc.idf + '</td>';
                                    html += '<td><strong>' + calc.tfidf + '</strong></td>';
                                    html += '<td>' + calc.rarity + '</td>';
                                    html += '</tr>';
                                });
                            } else if (method === 'bm25') {
                                html += '<tr><th>Term</th><th>Raw TF</th><th>IDF</th><th>Length Norm</th><th>Saturated TF</th><th>Score</th></tr>';
                                result.details.term_calculations.forEach(function(calc) {
                                    html += '<tr>';
                                    html += '<td><code>' + calc.term + '</code></td>';
                                    html += '<td>' + calc.raw_tf + '</td>';
                                    html += '<td>' + calc.idf + '</td>';
                                    html += '<td>' + calc.length_norm + '</td>';
                                    html += '<td>' + calc.saturated_tf + '</td>';
                                    html += '<td><strong>' + calc.term_score + '</strong></td>';
                                    html += '</tr>';
                                });
                            }
                            html += '</table>';
                        }
                        
                        // Document info
                        html += '<div style="margin-top: 10px; font-size: 12px; color: #666;">';
                        html += 'Document Length: ' + result.details.doc_length + ' words';
                        if (result.details.avg_doc_length) {
                            html += ' | Average Doc Length: ' + result.details.avg_doc_length + ' words';
                        }
                        html += '</div>';
                        
                        // Note
                        if (result.details.note) {
                            html += '<div class="note">💡 ' + result.details.note + '</div>';
                        }
                        
                        html += '</div>';
                    }
                    
                    html += '</div>';
                });
                
                $('#fts-results').html(html);
            }
        });
        </script>
        <?php
    }
}

// Initialize the plugin
new FTS_Teaching_Plugin();
