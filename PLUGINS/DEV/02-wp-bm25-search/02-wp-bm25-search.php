<?php
/**
 * Plugin Name: ✅ 02 BM25 SEARCH
 * Plugin URI: https://example.com/bm25-search
 * Description: A WordPress plugin to demonstrate and test BM25 search algorithm
 * Version: 1.0.0
 * Author: Craig West 
 * Author URI: https://example.com
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class BM25_Search_Plugin {
    
    private $option_name = 'bm25_documents';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_bm25_search', array($this, 'handle_search'));
    }
    
    /**
     * Add admin menu item at level 30
     */
    public function add_admin_menu() {
        add_menu_page(
            'BM25 Search Analyzer',           // Page title
            '02 BM25 SEARCH',                     // Menu title
            'manage_options',                  // Capability
            'bm25-search-analyzer',            // Menu slug
            array($this, 'display_admin_page'), // Callback
            'dashicons-search',                // Icon
            3.2                            // Position
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('bm25_settings', $this->option_name);
    }
    
    /**
     * Get default documents
     */
    private function get_default_documents() {
        return "Introduction to Python programming for beginners. Learn Python basics and start coding today.
Advanced machine learning techniques using TensorFlow and PyTorch for deep learning applications.
Python web development with Django and Flask frameworks. Build modern web applications quickly.
Data science with Python: pandas, numpy, and matplotlib for data analysis and visualization.
JavaScript vs Python: comparing two popular programming languages for web development.
Machine learning algorithms explained: supervised learning, unsupervised learning, and reinforcement learning.
Getting started with PHP and MySQL for database-driven web applications.
Python automation scripts for everyday tasks. Automate your workflow with Python.
Natural language processing with Python and NLTK library for text analysis.
Building RESTful APIs with Python Flask and authentication best practices.";
    }
    
    /**
     * Display the admin page
     */
    public function display_admin_page() {
        // Get saved documents or use defaults
        $documents_text = get_option($this->option_name, $this->get_default_documents());
        
        // Handle form submission for saving documents
        if (isset($_POST['save_documents']) && check_admin_referer('bm25_save_docs')) {
            $documents_text = sanitize_textarea_field($_POST['documents']);
            update_option($this->option_name, $documents_text);
            echo '<div class="notice notice-success is-dismissible"><p>Documents saved successfully!</p></div>';
        }
        
        // Handle search
        $search_query = '';
        $k1 = isset($_POST['k1']) ? floatval($_POST['k1']) : 1.5;
        $b = isset($_POST['b']) ? floatval($_POST['b']) : 0.75;
        $search_results = null;
        $reranked_results = null;
        $explanation = null;

        if (isset($_POST['search_query']) && check_admin_referer('bm25_search_action')) {
            $search_query = sanitize_text_field($_POST['search_query']);
            $k1 = isset($_POST['k1']) ? floatval($_POST['k1']) : 1.5;
            $b = isset($_POST['b']) ? floatval($_POST['b']) : 0.75;

            if (!empty($search_query)) {
                $documents = array_filter(array_map('trim', explode("\n", $documents_text)));
                $bm25 = new BM25($documents, $k1, $b);

                $search_results = $bm25->search($search_query);
                $reranked_results = $bm25->searchWithReranking($search_query, 10);

                if (!empty($reranked_results)) {
                    $explanation = $bm25->explainScore($search_query, $reranked_results[0]['doc_id']);
                }
            }
        }
        
        ?>
        <div class="wrap" style="font-size:1.25rem;line-height:1.5;max-width:1000px;">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2>What is BM25?</h2>
                <p>BM25 (Best Matching 25) is a ranking function used by search engines to estimate the relevance of documents to a given search query. It improves upon TF-IDF by adding:</p>
                <ul>
                    <li><strong>Term frequency saturation</strong>: Additional occurrences of a term have diminishing returns</li>
                    <li><strong>Document length normalization</strong>: Adjusts for varying document lengths</li>
                    <li><strong>Tunable parameters</strong>: k1 (saturation) and b (length normalization)</li>
                </ul>

                <h3>BM25 Parameters</h3>
                <dl>
                    <dt><strong>k1 (Saturation Parameter)</strong></dt>
                    <dd>Controls how much additional occurrences of a term contribute to the score. Higher values (e.g., 2.0) make the score more sensitive to term frequency, while lower values (e.g., 0.5) cause saturation earlier. Typical range: 1.2-2.0, default: 1.5.</dd>

                    <dt><strong>b (Length Normalization Parameter)</strong></dt>
                    <dd>Controls the degree of document length normalization. Higher values (e.g., 0.75) heavily penalize long documents, while lower values (e.g., 0.25) reduce this effect. Set to 0 for no length normalization, 1 for full normalization. Default: 0.75.</dd>
                </dl>
            </div>
            
            <!-- Document Management Section -->
            <div style="background: #f9f9f9; padding: 20px; margin: 20px 0;">
                <h2>Manage Documents</h2>
                <h3>Enter one document per line. These will be used as your search corpus.</h3>
                
                <form method="post" action="">
                    <?php wp_nonce_field('bm25_save_docs'); ?>
                    <textarea 
                        name="documents" 
                        rows="15" 
                        style="width: 100%; font-family: monospace;"
                        placeholder="Enter one document per line..."><?php echo esc_textarea($documents_text); ?></textarea>
                    <br><br>
                    <button type="submit" name="save_documents" class="button button-primary">Save Documents</button>
                    <button type="submit" name="reset_documents" class="button" 
                            onclick="return confirm('Reset to default documents?');">Reset to Defaults</button>
                </form>
            </div>
            
            <!-- Search Section -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2>Search Documents</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('bm25_search_action'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="search_query">Search Query</label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    name="search_query"
                                    id="search_query"
                                    class="regular-text"
                                    value="<?php echo esc_attr($search_query); ?>"
                                    placeholder="e.g., python programming">
                                <button type="submit" class="button button-primary">Search</button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="k1">k1 (Saturation)</label>
                            </th>
                            <td>
                                <input
                                    type="number"
                                    name="k1"
                                    id="k1"
                                    step="0.1"
                                    min="0.1"
                                    max="5.0"
                                    value="<?php echo esc_attr($k1); ?>">
                                <small>Default: 1.5</small>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="b">b (Length Normalization)</label>
                            </th>
                            <td>
                                <input
                                    type="number"
                                    name="b"
                                    id="b"
                                    step="0.05"
                                    min="0"
                                    max="1"
                                    value="<?php echo esc_attr($b); ?>">
                                <small>Default: 0.75</small>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
            
            <?php if ($search_results !== null): ?>
            
            <!-- All Results Section -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2>All Results (Including Zero Scores)</h2>
                <p>Shows all documents ranked by BM25 score, even those with no matching terms.</p>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th width="5%">Rank</th>
                            <th width="8%">Doc ID</th>
                            <th width="10%">Score</th>
                            <th>Document</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $i => $result): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo $result['doc_id']; ?></td>
                            <td>
                                <span style="color: <?php echo $result['score'] > 0 ? 'green' : ($result['score'] < 0 ? 'red' : 'gray'); ?>">
                                    <?php echo number_format($result['score'], 4); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($result['document']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Reranked Results Section -->
            <?php if (!empty($reranked_results)): ?>
            <div style="background: #e7f7e7; padding: 20px; margin: 20px 0; border: 2px solid #4caf50;">
                <h2>Reranked Results (Only Matching Documents)</h2>
                <p>Shows only documents containing at least one query term.</p>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th width="5%">Rank</th>
                            <th width="8%">Doc ID</th>
                            <th width="10%">Score</th>
                            <th width="10%">Matches</th>
                            <th>Document</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reranked_results as $i => $result): ?>
                        <tr style="background: <?php echo $i === 0 ? '#fffacd' : ''; ?>">
                            <td><strong><?php echo $i + 1; ?></strong></td>
                            <td><?php echo $result['doc_id']; ?></td>
                            <td>
                                <strong style="color: <?php echo $result['score'] > 0 ? 'green' : 'red'; ?>">
                                    <?php echo number_format($result['score'], 4); ?>
                                </strong>
                            </td>
                            <td><?php echo $result['match_count']; ?> terms</td>
                            <td><?php echo esc_html($result['document']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Score Explanation Section -->
            <?php if ($explanation): ?>
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2>Detailed Score Breakdown (Top Result)</h2>
                <p><strong>Document #<?php echo $explanation['doc_id']; ?>:</strong> 
                   <?php echo esc_html($explanation['doc_id'] < count(explode("\n", $documents_text)) 
                       ? explode("\n", $documents_text)[$explanation['doc_id']] 
                       : 'N/A'); ?>
                </p>
                
                <table class="form-table">
                    <tr>
                        <th>Document Length:</th>
                        <td><?php echo $explanation['doc_length']; ?> words</td>
                    </tr>
                    <tr>
                        <th>Average Doc Length:</th>
                        <td><?php echo number_format($explanation['avg_doc_length'], 2); ?> words</td>
                    </tr>
                    <tr>
                        <th>Total Score:</th>
                        <td><strong style="font-size: 1.2em; color: green;">
                            <?php echo number_format($explanation['total_score'], 4); ?>
                        </strong></td>
                    </tr>
                </table>
                
                <h3>Term-by-term Breakdown:</h3>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th>Term</th>
                            <th>TF</th>
                            <th>IDF</th>
                            <th>TF Component</th>
                            <th>Doc Frequency</th>
                            <th>Score Contribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($explanation['terms'] as $term => $details): ?>
                        <tr>
                            <td><code><?php echo esc_html($term); ?></code></td>
                            <?php if (isset($details['note'])): ?>
                                <td colspan="5"><em><?php echo esc_html($details['note']); ?></em></td>
                            <?php else: ?>
                                <td><?php echo $details['tf']; ?></td>
                                <td><?php echo number_format($details['idf'], 4); ?></td>
                                <td><?php echo number_format($details['tf_component'], 4); ?></td>
                                <td><?php echo $details['doc_frequency']; ?> docs</td>
                                <td><strong><?php echo number_format($details['score'], 4); ?></strong></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
            
        </div>
        
        <style>
            .wp-list-table th {
                background: #f0f0f0;
            }
            code {
                background: #f4f4f4;
                padding: 2px 6px;
                border-radius: 3px;
            }
        </style>
        <?php
    }
}

// ============================================================================
// BM25 Class (Same as before)
// ============================================================================

class BM25 {
    private $k1;
    private $b;
    private $documents;
    private $avgDocLength;
    private $docFrequencies;
    private $totalDocs;
    
    public function __construct(array $documents, float $k1 = 1.5, float $b = 0.75) {
        $this->k1 = $k1;
        $this->b = $b;
        $this->documents = $documents;
        $this->totalDocs = count($documents);
        $this->preprocess();
    }
    
    private function preprocess() {
        $totalLength = 0;
        $this->docFrequencies = [];
        
        foreach ($this->documents as $doc) {
            $terms = $this->tokenize($doc);
            $totalLength += count($terms);
            
            $uniqueTerms = array_unique($terms);
            foreach ($uniqueTerms as $term) {
                if (!isset($this->docFrequencies[$term])) {
                    $this->docFrequencies[$term] = 0;
                }
                $this->docFrequencies[$term]++;
            }
        }
        
        $this->avgDocLength = $totalLength / $this->totalDocs;
    }
    
    private function tokenize(string $text): array {
        return preg_split('/\s+/', strtolower(trim($text)));
    }
    
    private function calculateIDF(string $term): float {
        $df = $this->docFrequencies[$term] ?? 0;
        
        if ($df === 0) {
            return 0.0;
        }
        
        return log(($this->totalDocs - $df + 0.5) / ($df + 0.5));
    }
    
    private function scoreDocument(array $queryTerms, array $docTerms, int $docLength): float {
        $score = 0.0;
        $termFreqs = array_count_values($docTerms);
        
        foreach ($queryTerms as $term) {
            if (!isset($termFreqs[$term])) {
                continue;
            }
            
            $tf = $termFreqs[$term];
            $idf = $this->calculateIDF($term);
            
            $numerator = $tf * ($this->k1 + 1);
            $denominator = $tf + $this->k1 * (1 - $this->b + $this->b * $docLength / $this->avgDocLength);
            
            $score += $idf * ($numerator / $denominator);
        }
        
        return $score;
    }
    
    public function search(string $query): array {
        $queryTerms = $this->tokenize($query);
        $results = [];
        
        foreach ($this->documents as $docId => $document) {
            $docTerms = $this->tokenize($document);
            $score = $this->scoreDocument($queryTerms, $docTerms, count($docTerms));
            
            $results[] = [
                'doc_id' => $docId,
                'score' => $score,
                'document' => $document
            ];
        }
        
        usort($results, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $results;
    }
    
    public function searchWithReranking(string $query, int $topK = null): array {
        $queryTerms = $this->tokenize($query);
        $results = [];
        
        foreach ($this->documents as $docId => $document) {
            $docTerms = $this->tokenize($document);
            
            $hasMatch = false;
            foreach ($queryTerms as $term) {
                if (in_array($term, $docTerms)) {
                    $hasMatch = true;
                    break;
                }
            }
            
            if (!$hasMatch) {
                continue;
            }
            
            $score = $this->scoreDocument($queryTerms, $docTerms, count($docTerms));
            
            $results[] = [
                'doc_id' => $docId,
                'score' => $score,
                'document' => $document,
                'match_count' => $this->countMatches($queryTerms, $docTerms)
            ];
        }
        
        usort($results, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        if ($topK !== null) {
            return array_slice($results, 0, $topK);
        }
        
        return $results;
    }
    
    private function countMatches(array $queryTerms, array $docTerms): int {
        $matches = 0;
        foreach ($queryTerms as $term) {
            if (in_array($term, $docTerms)) {
                $matches++;
            }
        }
        return $matches;
    }
    
    public function explainScore(string $query, int $docId): array {
        if (!isset($this->documents[$docId])) {
            return ['error' => 'Document not found'];
        }
        
        $queryTerms = $this->tokenize($query);
        $docTerms = $this->tokenize($this->documents[$docId]);
        $docLength = count($docTerms);
        $termFreqs = array_count_values($docTerms);
        
        $explanation = [
            'query' => $query,
            'doc_id' => $docId,
            'doc_length' => $docLength,
            'avg_doc_length' => $this->avgDocLength,
            'total_score' => 0.0,
            'terms' => []
        ];
        
        foreach ($queryTerms as $term) {
            $tf = $termFreqs[$term] ?? 0;
            $idf = $this->calculateIDF($term);
            
            if ($tf > 0) {
                $numerator = $tf * ($this->k1 + 1);
                $denominator = $tf + $this->k1 * (1 - $this->b + $this->b * $docLength / $this->avgDocLength);
                $tfComponent = $numerator / $denominator;
                $termScore = $idf * $tfComponent;
                
                $explanation['terms'][$term] = [
                    'tf' => $tf,
                    'idf' => $idf,
                    'tf_component' => $tfComponent,
                    'score' => $termScore,
                    'doc_frequency' => $this->docFrequencies[$term] ?? 0
                ];
                
                $explanation['total_score'] += $termScore;
            } else {
                $explanation['terms'][$term] = [
                    'tf' => 0,
                    'score' => 0.0,
                    'note' => 'Term not found in document'
                ];
            }
        }
        
        return $explanation;
    }
}

// Initialize the plugin
if (is_admin()) {
    new BM25_Search_Plugin();
}
