<?php
/**
 * Plugin Name: ✅ 04 FTS TOOL
 * Plugin URI: https://example.com/fts-teaching
 * Description: Interactive demonstration of different Full-Text Search methods (TF, IDF, BM25, Natural, Boolean, Expansion) for teaching purposes
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
    
    private function init_documents() {
        $this->documents = array(
            array(
                'id' => 1,
                'title' => 'Introduction to Machine Learning',
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
                'content' => 'Natural language processing enables computers to understand human language. Applications include translation, sentiment analysis, and chatbots. NLP combines linguistics with machine learning techniques.'
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
            case 'natural':
                $results = $this->search_natural($query);
                break;
            case 'boolean':
                $results = $this->search_boolean($query);
                break;
            case 'expansion':
                $results = $this->search_expansion($query);
                break;
        }
        
        wp_send_json_success($results);
    }
    
    private function search_tf($query) {
        $terms = array_map('strtolower', explode(' ', $query));
        $scores = array();
        
        foreach ($this->documents as $doc) {
            $content = strtolower($doc['title'] . ' ' . $doc['content']);
            $score = 0;
            
            foreach ($terms as $term) {
                $score += substr_count($content, $term);
            }
            
            if ($score > 0) {
                $scores[] = array(
                    'doc' => $doc,
                    'score' => $score,
                    'explanation' => "Term frequency: $score occurrences"
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
        
        // Calculate IDF for each term
        $idf = array();
        $total_docs = count($this->documents);
        
        foreach ($terms as $term) {
            $docs_with_term = 0;
            foreach ($this->documents as $doc) {
                $content = strtolower($doc['title'] . ' ' . $doc['content']);
                if (strpos($content, $term) !== false) {
                    $docs_with_term++;
                }
            }
            $idf[$term] = $docs_with_term > 0 ? log($total_docs / $docs_with_term) : 0;
        }
        
        // Calculate TF-IDF scores
        foreach ($this->documents as $doc) {
            $content = strtolower($doc['title'] . ' ' . $doc['content']);
            $score = 0;
            $details = array();
            
            foreach ($terms as $term) {
                $tf = substr_count($content, $term);
                $tfidf = $tf * $idf[$term];
                $score += $tfidf;
                if ($tf > 0) {
                    $details[] = "$term: TF=$tf × IDF=" . round($idf[$term], 2) . " = " . round($tfidf, 2);
                }
            }
            
            if ($score > 0) {
                $scores[] = array(
                    'doc' => $doc,
                    'score' => round($score, 2),
                    'explanation' => implode(', ', $details)
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
        
        // Calculate average document length
        $total_length = 0;
        foreach ($this->documents as $doc) {
            $total_length += str_word_count($doc['title'] . ' ' . $doc['content']);
        }
        $avg_length = $total_length / count($this->documents);
        
        // Calculate IDF
        $idf = array();
        $total_docs = count($this->documents);
        foreach ($terms as $term) {
            $docs_with_term = 0;
            foreach ($this->documents as $doc) {
                $content = strtolower($doc['title'] . ' ' . $doc['content']);
                if (strpos($content, $term) !== false) {
                    $docs_with_term++;
                }
            }
            $idf[$term] = log(($total_docs - $docs_with_term + 0.5) / ($docs_with_term + 0.5) + 1);
        }
        
        // Calculate BM25 scores
        foreach ($this->documents as $doc) {
            $content = strtolower($doc['title'] . ' ' . $doc['content']);
            $doc_length = str_word_count($content);
            $score = 0;
            
            foreach ($terms as $term) {
                $tf = substr_count($content, $term);
                if ($tf > 0) {
                    $norm = 1 - $b + $b * ($doc_length / $avg_length);
                    $score += $idf[$term] * ($tf * ($k1 + 1)) / ($tf + $k1 * $norm);
                }
            }
            
            if ($score > 0) {
                $scores[] = array(
                    'doc' => $doc,
                    'score' => round($score, 2),
                    'explanation' => "BM25 score with saturation (k1=$k1, b=$b)"
                );
            }
        }
        
        usort($scores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $scores;
    }
    
    private function search_natural($query) {
        $terms = array_map('strtolower', explode(' ', $query));
        $scores = array();
        
        foreach ($this->documents as $doc) {
            $content = strtolower($doc['title'] . ' ' . $doc['content']);
            $words = explode(' ', $content);
            $score = 0;
            $proximity_bonus = 0;
            
            // Find positions of query terms
            $positions = array();
            foreach ($terms as $term) {
                $positions[$term] = array();
                foreach ($words as $idx => $word) {
                    if (strpos($word, $term) !== false) {
                        $positions[$term][] = $idx;
                        $score += 1;
                    }
                }
            }
            
            // Calculate proximity bonus
            if (count($terms) > 1) {
                foreach ($positions as $term_positions) {
                    foreach ($term_positions as $pos) {
                        foreach ($terms as $other_term) {
                            if (isset($positions[$other_term])) {
                                foreach ($positions[$other_term] as $other_pos) {
                                    $distance = abs($pos - $other_pos);
                                    if ($distance > 0 && $distance <= 5) {
                                        $proximity_bonus += (5 - $distance) / 5;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            $total_score = $score + $proximity_bonus;
            
            if ($total_score > 0) {
                $scores[] = array(
                    'doc' => $doc,
                    'score' => round($total_score, 2),
                    'explanation' => "Base: $score + Proximity: " . round($proximity_bonus, 2)
                );
            }
        }
        
        usort($scores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $scores;
    }
    
    private function search_boolean($query) {
        $query = strtolower($query);
        $results = array();
        
        foreach ($this->documents as $doc) {
            $content = strtolower($doc['title'] . ' ' . $doc['content']);
            $match = $this->evaluate_boolean($query, $content);
            
            if ($match) {
                $results[] = array(
                    'doc' => $doc,
                    'score' => 1,
                    'explanation' => "Boolean match"
                );
            }
        }
        
        return $results;
    }
    
    private function evaluate_boolean($query, $content) {
        // Simple boolean evaluation (supports AND, OR, NOT)
        $query = str_replace(' and ', ' AND ', $query);
        $query = str_replace(' or ', ' OR ', $query);
        $query = str_replace(' not ', ' NOT ', $query);
        
        // Handle NOT
        if (preg_match('/NOT\s+(\w+)/', $query, $matches)) {
            if (strpos($content, strtolower($matches[1])) !== false) {
                return false;
            }
            $query = preg_replace('/NOT\s+\w+/', '', $query);
        }
        
        // Handle AND
        if (strpos($query, ' AND ') !== false) {
            $terms = explode(' AND ', $query);
            foreach ($terms as $term) {
                $term = trim($term);
                if (!empty($term) && strpos($content, strtolower($term)) === false) {
                    return false;
                }
            }
            return true;
        }
        
        // Handle OR
        if (strpos($query, ' OR ') !== false) {
            $terms = explode(' OR ', $query);
            foreach ($terms as $term) {
                $term = trim($term);
                if (!empty($term) && strpos($content, strtolower($term)) !== false) {
                    return true;
                }
            }
            return false;
        }
        
        // Simple term search
        return strpos($content, $query) !== false;
    }
    
    private function search_expansion($query) {
        $expansions = array(
            'ai' => array('artificial intelligence', 'machine learning'),
            'ml' => array('machine learning'),
            'nlp' => array('natural language processing'),
            'nn' => array('neural network'),
            'dl' => array('deep learning')
        );
        
        $query_lower = strtolower($query);
        $expanded_terms = array($query_lower);
        
        foreach ($expansions as $abbr => $full_terms) {
            if (strpos($query_lower, $abbr) !== false) {
                $expanded_terms = array_merge($expanded_terms, $full_terms);
            }
        }
        
        $scores = array();
        
        foreach ($this->documents as $doc) {
            $content = strtolower($doc['title'] . ' ' . $doc['content']);
            $score = 0;
            $matched_terms = array();
            
            foreach ($expanded_terms as $term) {
                $count = substr_count($content, $term);
                if ($count > 0) {
                    $score += $count;
                    $matched_terms[] = $term;
                }
            }
            
            if ($score > 0) {
                $scores[] = array(
                    'doc' => $doc,
                    'score' => $score,
                    'explanation' => "Matched: " . implode(', ', $matched_terms)
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
            <h1>Full-Text Search Methods Teaching Tool</h1>
            
            <div class="fts-container">
                <div class="fts-search-panel">
                    <h2>Search Interface</h2>
                    
                    <div class="fts-search-form">
                        <input type="text" id="fts-query" class="fts-query-input" placeholder="Enter search query..." value="machine learning">
                        
                        <select id="fts-method" class="fts-method-select">
                            <option value="tf">Term Frequency (TF)</option>
                            <option value="idf" selected>TF-IDF</option>
                            <option value="bm25">BM25</option>
                            <option value="natural">Natural (Positional)</option>
                            <option value="boolean">Boolean</option>
                            <option value="expansion">Query Expansion</option>
                        </select>
                        
                        <button id="fts-search-btn" class="button button-primary">Search</button>
                    </div>
                    
                    <div class="fts-method-info">
                        <h3>Method Descriptions</h3>
                        <ul>
                            <li><strong>TF:</strong> Counts term occurrences (rewards repetition)</li>
                            <li><strong>TF-IDF:</strong> Balances frequency with rarity across documents</li>
                            <li><strong>BM25:</strong> Prevents over-weighting repeated terms (saturation)</li>
                            <li><strong>Natural:</strong> Base score = term count, Proximity bonus = (5-distance)/5 for term pairs within 5 words</li>
                            <li><strong>Boolean:</strong> Strict AND/OR/NOT matching</li>
                            <li><strong>Expansion:</strong> Expands abbreviations (AI→artificial intelligence)</li>
                        </ul>
                        <div class="fts-scoring-details" style="margin-top: 15px; padding: 10px; background: #e8f5e8; border-radius: 3px; font-size: 12px;">
                            <strong>Scoring Details:</strong>
                            <ul style="margin: 5px 0 0 0; padding-left: 15px;">
                                <li><strong>Base Score:</strong> Number of times query terms appear in document</li>
                                <li><strong>Proximity Bonus:</strong> For each pair of query terms within 5 words: (5 - distance) ÷ 5</li>
                                <li><strong>Example:</strong> "Base: 4 + Proximity: 1.6" means 4 term matches + 1.6 proximity bonus</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="fts-example-queries">
                        <h3>Example Queries</h3>
                        <ul>
                            <li><code>machine learning</code> - Compare TF vs BM25 (note repetition weighting)</li>
                            <li><code>reinforcement</code> - See IDF for rare terms vs common terms</li>
                            <li><code>neural network training</code> - Test positional ranking (proximity bonus)</li>
                            <li><code>machine AND learning NOT deep</code> - Boolean query (strict matching)</li>
                            <li><code>AI</code> - Query expansion demo (abbreviations expanded)</li>
                            <li><code>learning learning learning</code> - TF vs BM25 saturation effect</li>
                            <li><code>data</code> - Common term (low IDF) vs rare term ranking</li>
                            <li><code>artificial intelligence OR machine learning</code> - Boolean OR operation</li>
                            <li><code>deep learning neural</code> - Natural search proximity scoring</li>
                            <li><code>vision computer</code> - Compare positional vs TF ranking</li>
                            <li><code>NOT supervised</code> - Boolean NOT exclusion</li>
                            <li><code>nlp OR processing language</code> - Mixed boolean and natural search</li>
                            <li><code>ML</code> - Query expansion (ML → machine learning)</li>
                            <li><code>training algorithm model</code> - Multiple term positional ranking</li>
                        </ul>
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
                <h2>Document Collection</h2>
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
            margin-bottom: 5px;
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
                
                var html = '<h3>Found ' + results.length + ' result(s) using ' + method.toUpperCase() + '</h3>';
                
                results.forEach(function(result, index) {
                    html += '<div class="fts-result-item">';
                    html += '<span class="fts-result-rank">#' + (index + 1) + '</span>';
                    html += '<span class="fts-result-score">Score: ' + result.score + '</span>';
                    html += '<div class="fts-result-title">Doc ' + result.doc.id + ': ' + result.doc.title + '</div>';
                    html += '<div class="fts-result-content">' + result.doc.content + '</div>';
                    html += '<div class="fts-result-explanation">' + result.explanation + '</div>';
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
