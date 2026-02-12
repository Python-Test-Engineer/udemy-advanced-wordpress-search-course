<?php
/**
 * Plugin Name: MySQL FTS Demo with Categories
 * Plugin URI: https://example.com/fts-demo
 * Description: Demonstrates MySQL Full-Text Search with WordPress categories as prefilters. Includes 30 different query examples and performance comparisons.
 * Version: 1.0.0
 * Author: FTS Demo
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: fts-demo
 */

if (!defined('ABSPATH')) {
    exit;
}

class MySQL_FTS_Demo_Plugin {
    
    private $table_name;
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'fts_demo_articles';
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_fts1_run_query', array($this, 'ajax_run_query'));
        add_action('wp_ajax_fts1_populate_data', array($this, 'ajax_populate_data'));
        add_action('wp_ajax_fts1_clear_data', array($this, 'ajax_clear_data'));
    }
    
    /**
     * Plugin activation - create table with FULLTEXT index
     */
    public function activate() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            category_id bigint(20) NOT NULL,
            category_slug varchar(100) NOT NULL,
            author varchar(100) NOT NULL,
            publish_date datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'published',
            views int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_category (category_id),
            KEY idx_status (status),
            KEY idx_publish_date (publish_date),
            KEY idx_category_status (category_id, status),
            FULLTEXT KEY ft_title_content (title, content),
            FULLTEXT KEY ft_title (title),
            FULLTEXT KEY ft_content (content)
        ) $charset_collate ENGINE=InnoDB;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add some initial test data
        $this->maybe_populate_initial_data();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Optionally clean up (commented out to preserve data)
        // $this->wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
    }
    
    /**
     * Add admin menu with 30 different examples
     */
    public function add_admin_menu() {
        add_menu_page(
            'FTS Demo',
            'FTS Demo',
            'manage_options',
            'fts-demo',
            array($this, 'render_main_page'),
            'dashicons-search',
            30
        );
        
        // Submenu pages for different example categories
        add_submenu_page('fts-demo', 'Dashboard', 'Dashboard', 'manage_options', 'fts-demo');
        add_submenu_page('fts-demo', 'Basic Examples (1-10)', 'Basic Examples', 'manage_options', 'fts-basic', array($this, 'render_basic_examples'));
        add_submenu_page('fts-demo', 'Advanced Examples (11-20)', 'Advanced Examples', 'manage_options', 'fts-advanced', array($this, 'render_advanced_examples'));
        add_submenu_page('fts-demo', 'Performance Examples (21-30)', 'Performance Tests', 'manage_options', 'fts-performance', array($this, 'render_performance_examples'));
        add_submenu_page('fts-demo', 'Data Management', 'Manage Data', 'manage_options', 'fts-data', array($this, 'render_data_management'));
    }
    
    /**
     * Render main dashboard page
     */
    public function render_main_page() {
        ?>
        <div class="wrap">
            <h1>MySQL Full-Text Search Demo with Category Prefiltering</h1>
            
            <div class="notice notice-info">
                <p><strong>Purpose:</strong> This plugin demonstrates how MySQL FTS MATCH queries work with WHERE clause prefilters (using WordPress categories).</p>
                <p><strong>Key Learning:</strong> MATCH is evaluated first, then WHERE conditions filter the results.</p>
            </div>
            
            <h2>Quick Stats</h2>
            <?php $this->display_stats(); ?>
            
            <h2>Quick Start</h2>
            <div class="card">
                <h3>Getting Started</h3>
                <ol>
                    <li>Go to <strong>Manage Data</strong> to populate test data (if not already done)</li>
                    <li>Explore <strong>Basic Examples (1-10)</strong> to understand FTS fundamentals</li>
                    <li>Review <strong>Advanced Examples (11-20)</strong> for complex queries</li>
                    <li>Test <strong>Performance Examples (21-30)</strong> to see execution order impacts</li>
                </ol>
            </div>
            
            <h2>Understanding the Examples</h2>
            <div class="card">
                <h3>Example Categories</h3>
                <ul>
                    <li><strong>Examples 1-10 (Basic):</strong> Core FTS concepts, category filtering, natural language mode</li>
                    <li><strong>Examples 11-20 (Advanced):</strong> Boolean mode, complex filters, relevance scoring</li>
                    <li><strong>Examples 21-30 (Performance):</strong> Execution order, index usage, optimization techniques</li>
                </ul>
            </div>
            
            <h2>WordPress Categories Used</h2>
            <?php $this->display_categories_info(); ?>
        </div>
        <?php
    }
    
    /**
     * Render basic examples (1-10)
     */
    public function render_basic_examples() {
        $this->enqueue_admin_scripts();
        ?>
        <div class="wrap">
            <h1>Basic FTS Examples (1-10)</h1>
            
            <div class="notice notice-info">
                <p>These examples demonstrate fundamental FTS concepts with category prefiltering.</p>
            </div>
            
            <?php
            $examples = array(
                array(
                    'num' => 1,
                    'title' => 'Simple MATCH with Category Filter',
                    'description' => 'Basic FTS search filtered by a single category. MATCH executes first, then category filter.',
                    'sql' => "SELECT id, title, category_slug, 
                             MATCH(title, content) AGAINST(:search) as relevance
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id = :category_id
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'wordpress', 'category_id' => 1)
                ),
                array(
                    'num' => 2,
                    'title' => 'Category Filter BEFORE MATCH (Same Result)',
                    'description' => 'Putting category before MATCH doesn\'t change execution order - MySQL optimizer still uses FTS index first.',
                    'sql' => "SELECT id, title, category_slug,
                             MATCH(title, content) AGAINST(:search) as relevance
                             FROM {$this->table_name}
                             WHERE category_id = :category_id
                             AND MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'wordpress', 'category_id' => 1)
                ),
                array(
                    'num' => 3,
                    'title' => 'Multiple Category Filter (IN Clause)',
                    'description' => 'Search across multiple categories. MATCH first, then IN filter applied.',
                    'sql' => "SELECT id, title, category_slug,
                             MATCH(title, content) AGAINST(:search) as relevance
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id IN (1, 2, 3)
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'tutorial')
                ),
                array(
                    'num' => 4,
                    'title' => 'Category with Status Filter',
                    'description' => 'Multiple WHERE conditions: MATCH → category → status. All filters applied after FTS.',
                    'sql' => "SELECT id, title, category_slug, status,
                             MATCH(title, content) AGAINST(:search) as relevance
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id = :category_id
                             AND status = 'published'
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'guide', 'category_id' => 2)
                ),
                array(
                    'num' => 5,
                    'title' => 'Date Range with Category',
                    'description' => 'Combining date filter with category and FTS. Execution: MATCH → category → date range.',
                    'sql' => "SELECT id, title, category_slug, publish_date,
                             MATCH(title, content) AGAINST(:search) as relevance
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id = :category_id
                             AND publish_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'php', 'category_id' => 1)
                ),
                array(
                    'num' => 6,
                    'title' => 'Title-Only MATCH with Category',
                    'description' => 'Search only in title field. Uses ft_title index.',
                    'sql' => "SELECT id, title, category_slug,
                             MATCH(title) AGAINST(:search) as relevance
                             FROM {$this->table_name}
                             WHERE MATCH(title) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id = :category_id
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'beginner', 'category_id' => 1)
                ),
                array(
                    'num' => 7,
                    'title' => 'Content-Only MATCH with Category',
                    'description' => 'Search only in content field. Uses ft_content index.',
                    'sql' => "SELECT id, title, category_slug,
                             MATCH(content) AGAINST(:search) as relevance
                             FROM {$this->table_name}
                             WHERE MATCH(content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id = :category_id
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'database', 'category_id' => 2)
                ),
                array(
                    'num' => 8,
                    'title' => 'Category Slug Filter (String Comparison)',
                    'description' => 'Using category slug instead of ID. Shows string comparison after FTS.',
                    'sql' => "SELECT id, title, category_slug,
                             MATCH(title, content) AGAINST(:search) as relevance
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_slug = :category_slug
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'learning', 'category_slug' => 'tutorials')
                ),
                array(
                    'num' => 9,
                    'title' => 'Minimum Views with Category',
                    'description' => 'Filter by popularity (views) and category. MATCH → category → views comparison.',
                    'sql' => "SELECT id, title, category_slug, views,
                             MATCH(title, content) AGAINST(:search) as relevance
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id = :category_id
                             AND views >= :min_views
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'advanced', 'category_id' => 3, 'min_views' => 100)
                ),
                array(
                    'num' => 10,
                    'title' => 'Exclude Category (NOT IN)',
                    'description' => 'Search everywhere except specific categories. MATCH first, then exclusion filter.',
                    'sql' => "SELECT id, title, category_slug,
                             MATCH(title, content) AGAINST(:search) as relevance
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id NOT IN (4, 5)
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'introduction')
                )
            );
            
            $this->render_example_cards($examples);
            ?>
        </div>
        <?php
    }
    
    /**
     * Render advanced examples (11-20)
     */
    public function render_advanced_examples() {
        $this->enqueue_admin_scripts();
        ?>
        <div class="wrap">
            <h1>Advanced FTS Examples (11-20)</h1>
            
            <div class="notice notice-warning">
                <p>These examples show Boolean mode, complex operators, and advanced filtering techniques.</p>
            </div>
            
            <?php
            $examples = array(
                array(
                    'num' => 11,
                    'title' => 'Boolean Mode: Required Terms',
                    'description' => 'Boolean mode with + operator (required terms). Category filter applied after MATCH.',
                    'sql' => "SELECT id, title, category_slug
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST('+wordpress +plugin' IN BOOLEAN MODE)
                             AND category_id = :category_id
                             LIMIT 10",
                    'params' => array('category_id' => 1)
                ),
                array(
                    'num' => 12,
                    'title' => 'Boolean Mode: Excluded Terms',
                    'description' => 'Search with excluded terms (-). Useful for filtering out irrelevant results within a category.',
                    'sql' => "SELECT id, title, category_slug
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST('+tutorial -beginner' IN BOOLEAN MODE)
                             AND category_id = :category_id
                             LIMIT 10",
                    'params' => array('category_id' => 1)
                ),
                array(
                    'num' => 13,
                    'title' => 'Boolean Mode: Phrase Search',
                    'description' => 'Exact phrase matching with category filter. Quotes ensure word order matters.',
                    'sql' => "SELECT id, title, category_slug
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST('\"wordpress development\"' IN BOOLEAN MODE)
                             AND category_id IN (1, 2)
                             LIMIT 10",
                    'params' => array()
                ),
                array(
                    'num' => 14,
                    'title' => 'Boolean Mode: Wildcard Search',
                    'description' => 'Prefix matching with * operator. Find variations within category.',
                    'sql' => "SELECT id, title, category_slug
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST('program*' IN BOOLEAN MODE)
                             AND category_id = :category_id
                             LIMIT 10",
                    'params' => array('category_id' => 2)
                ),
                array(
                    'num' => 15,
                    'title' => 'Boolean Mode: Complex Expression',
                    'description' => 'Combining multiple Boolean operators with category and status filters.',
                    'sql' => "SELECT id, title, category_slug, status
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST('+php +(tutorial guide) -beginner' IN BOOLEAN MODE)
                             AND category_id = :category_id
                             AND status = 'published'
                             LIMIT 10",
                    'params' => array('category_id' => 1)
                ),
                array(
                    'num' => 16,
                    'title' => 'Relevance Threshold Filter',
                    'description' => 'Filter results by minimum relevance score. MATCH calculates score, HAVING filters it.',
                    'sql' => "SELECT id, title, category_slug,
                             MATCH(title, content) AGAINST(:search) as relevance
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id = :category_id
                             HAVING relevance > :min_relevance
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'database', 'category_id' => 2, 'min_relevance' => 0.5)
                ),
                array(
                    'num' => 17,
                    'title' => 'Multi-Field Relevance Weighting',
                    'description' => 'Manually weight title matches higher than content matches.',
                    'sql' => "SELECT id, title, category_slug,
                             (MATCH(title) AGAINST(:search) * 2 + 
                              MATCH(content) AGAINST(:search)) as weighted_relevance
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id = :category_id
                             ORDER BY weighted_relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'security', 'category_id' => 3)
                ),
                array(
                    'num' => 18,
                    'title' => 'Category OR Author Filter',
                    'description' => 'Complex WHERE with OR logic. MATCH first, then (category OR author) evaluation.',
                    'sql' => "SELECT id, title, category_slug, author
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND (category_id = :category_id OR author = :author)
                             ORDER BY MATCH(title, content) AGAINST(:search) DESC
                             LIMIT 10",
                    'params' => array('search' => 'optimization', 'category_id' => 2, 'author' => 'John Doe')
                ),
                array(
                    'num' => 19,
                    'title' => 'UNION: Multiple Categories Separately',
                    'description' => 'Run separate FTS queries per category, then combine. Shows each category processes MATCH independently.',
                    'sql' => "(SELECT id, title, category_slug, 'Tutorials' as source
                              FROM {$this->table_name}
                              WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                              AND category_id = 1
                              LIMIT 5)
                             UNION ALL
                             (SELECT id, title, category_slug, 'News' as source
                              FROM {$this->table_name}
                              WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                              AND category_id = 2
                              LIMIT 5)",
                    'params' => array('search' => 'performance')
                ),
                array(
                    'num' => 20,
                    'title' => 'Subquery: Category-Specific Top Results',
                    'description' => 'Use subquery to find top match per category. Demonstrates nested FTS evaluation.',
                    'sql' => "SELECT t1.id, t1.title, t1.category_slug,
                             MATCH(t1.title, t1.content) AGAINST(:search) as relevance
                             FROM {$this->table_name} t1
                             INNER JOIN (
                                 SELECT category_id, MAX(MATCH(title, content) AGAINST(:search)) as max_relevance
                                 FROM {$this->table_name}
                                 WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                                 GROUP BY category_id
                             ) t2 ON t1.category_id = t2.category_id
                             WHERE MATCH(t1.title, t1.content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND MATCH(t1.title, t1.content) AGAINST(:search) = t2.max_relevance
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'best practices')
                )
            );
            
            $this->render_example_cards($examples);
            ?>
        </div>
        <?php
    }
    
    /**
     * Render performance examples (21-30)
     */
    public function render_performance_examples() {
        $this->enqueue_admin_scripts();
        ?>
        <div class="wrap">
            <h1>Performance & Execution Order Examples (21-30)</h1>
            
            <div class="notice notice-success">
                <p>These examples demonstrate execution order, index usage, and optimization techniques using EXPLAIN.</p>
            </div>
            
            <?php
            $examples = array(
                array(
                    'num' => 21,
                    'title' => 'EXPLAIN: Basic MATCH with Category',
                    'description' => 'Shows that FULLTEXT index is used as primary access method.',
                    'sql' => "EXPLAIN SELECT id, title 
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id = :category_id",
                    'params' => array('search' => 'wordpress', 'category_id' => 1),
                    'is_explain' => true
                ),
                array(
                    'num' => 22,
                    'title' => 'EXPLAIN: Category Before MATCH (Same Plan)',
                    'description' => 'Proves that query clause order doesn\'t affect execution plan.',
                    'sql' => "EXPLAIN SELECT id, title 
                             FROM {$this->table_name}
                             WHERE category_id = :category_id
                             AND MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)",
                    'params' => array('search' => 'wordpress', 'category_id' => 1),
                    'is_explain' => true
                ),
                array(
                    'num' => 23,
                    'title' => 'EXPLAIN: WITHOUT MATCH (Category Index)',
                    'description' => 'When no MATCH, optimizer uses regular category index instead.',
                    'sql' => "EXPLAIN SELECT id, title 
                             FROM {$this->table_name}
                             WHERE category_id = :category_id
                             AND title LIKE :like_term",
                    'params' => array('category_id' => 1, 'like_term' => '%wordpress%'),
                    'is_explain' => true
                ),
                array(
                    'num' => 24,
                    'title' => 'Performance: MATCH vs LIKE',
                    'description' => 'Compare FTS performance against LIKE wildcard search with category filter.',
                    'sql' => "SELECT 'FTS' as method, COUNT(*) as result_count, 
                             BENCHMARK(1000, (
                                 SELECT COUNT(*) FROM {$this->table_name}
                                 WHERE MATCH(title, content) AGAINST('wordpress' IN NATURAL LANGUAGE MODE)
                                 AND category_id = 1
                             )) as benchmark
                             UNION ALL
                             SELECT 'LIKE' as method, COUNT(*) as result_count,
                             BENCHMARK(1000, (
                                 SELECT COUNT(*) FROM {$this->table_name}
                                 WHERE (title LIKE '%wordpress%' OR content LIKE '%wordpress%')
                                 AND category_id = 1
                             )) as benchmark",
                    'params' => array(),
                    'show_timing' => true
                ),
                array(
                    'num' => 25,
                    'title' => 'Highly Selective Category First?',
                    'description' => 'Test if very selective category (1 row) changes optimizer decision.',
                    'sql' => "EXPLAIN SELECT id, title 
                             FROM {$this->table_name}
                             WHERE category_id = 999
                             AND MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)",
                    'params' => array('search' => 'common'),
                    'is_explain' => true
                ),
                array(
                    'num' => 26,
                    'title' => 'Query Cache Impact',
                    'description' => 'Run same query twice to show query cache effect (if enabled).',
                    'sql' => "SELECT SQL_NO_CACHE id, title, category_slug,
                             MATCH(title, content) AGAINST(:search) as relevance
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id = :category_id
                             ORDER BY relevance DESC
                             LIMIT 10",
                    'params' => array('search' => 'tutorial', 'category_id' => 1),
                    'run_twice' => true
                ),
                array(
                    'num' => 27,
                    'title' => 'COUNT(*) Performance',
                    'description' => 'Compare counting FTS results with and without category filter.',
                    'sql' => "SELECT 
                             (SELECT COUNT(*) FROM {$this->table_name} 
                              WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)) as total_matches,
                             (SELECT COUNT(*) FROM {$this->table_name} 
                              WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                              AND category_id = :category_id) as category_matches",
                    'params' => array('search' => 'guide', 'category_id' => 1),
                    'show_timing' => true
                ),
                array(
                    'num' => 28,
                    'title' => 'Composite Index Usage',
                    'description' => 'Test if composite index (category_id, status) is used after FTS.',
                    'sql' => "EXPLAIN SELECT id, title 
                             FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id = :category_id
                             AND status = 'published'",
                    'params' => array('search' => 'advanced', 'category_id' => 2),
                    'is_explain' => true
                ),
                array(
                    'num' => 29,
                    'title' => 'JOIN Performance with FTS',
                    'description' => 'Self-join to show FTS in JOIN conditions. MATCH evaluated before JOIN.',
                    'sql' => "EXPLAIN SELECT DISTINCT t1.id, t1.title, t1.category_slug
                             FROM {$this->table_name} t1
                             INNER JOIN {$this->table_name} t2 
                                 ON t1.category_id = t2.category_id
                             WHERE MATCH(t1.title, t1.content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND t1.category_id = :category_id
                             LIMIT 10",
                    'params' => array('search' => 'optimization', 'category_id' => 3),
                    'is_explain' => true
                ),
                array(
                    'num' => 30,
                    'title' => 'Full Analysis: Best vs Worst Query',
                    'description' => 'Compare optimized FTS query against inefficient alternatives.',
                    'sql' => "-- GOOD: FTS with category filter
                             EXPLAIN FORMAT=JSON
                             SELECT id, title FROM {$this->table_name}
                             WHERE MATCH(title, content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                             AND category_id = :category_id
                             LIMIT 10",
                    'params' => array('search' => 'comprehensive', 'category_id' => 1),
                    'is_explain' => true,
                    'show_alternatives' => true
                )
            );
            
            $this->render_example_cards($examples);
            ?>
        </div>
        <?php
    }
    
    /**
     * Render data management page
     */
    public function render_data_management() {
        $this->enqueue_admin_scripts();
        
        $total_records = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        ?>
        <div class="wrap">
            <h1>Data Management</h1>
            
            <div class="card">
                <h2>Current Database State</h2>
                <p><strong>Total Records:</strong> <?php echo number_format($total_records); ?></p>
                
                <?php $this->display_stats(); ?>
            </div>
            
            <div class="card">
                <h2>Populate Test Data</h2>
                <p>Generate sample articles across different categories for testing.</p>
                
                <button class="button button-primary" id="populate-data-btn" data-count="100">
                    Add 100 Records
                </button>
                <button class="button button-primary" id="populate-data-btn-500" data-count="500">
                    Add 500 Records
                </button>
                <button class="button button-primary" id="populate-data-btn-1000" data-count="1000">
                    Add 1,000 Records
                </button>
                
                <div id="populate-progress" style="display: none; margin-top: 15px;">
                    <div class="notice notice-info">
                        <p>Populating data... <span id="populate-status"></span></p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>Clear Data</h2>
                <p><strong>Warning:</strong> This will delete all test data from the demo table.</p>
                
                <button class="button button-secondary" id="clear-data-btn" 
                        onclick="return confirm('Are you sure you want to delete all demo data?');">
                    Clear All Data
                </button>
            </div>
            
            <div class="card">
                <h2>Index Information</h2>
                <p>Current FULLTEXT indexes on the demo table:</p>
                <ul>
                    <li><code>ft_title_content</code> - FULLTEXT on (title, content)</li>
                    <li><code>ft_title</code> - FULLTEXT on (title)</li>
                    <li><code>ft_content</code> - FULLTEXT on (content)</li>
                </ul>
                
                <p>Regular indexes:</p>
                <ul>
                    <li><code>idx_category</code> - INDEX on (category_id)</li>
                    <li><code>idx_status</code> - INDEX on (status)</li>
                    <li><code>idx_publish_date</code> - INDEX on (publish_date)</li>
                    <li><code>idx_category_status</code> - COMPOSITE INDEX on (category_id, status)</li>
                </ul>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Populate data handlers
            $('[id^="populate-data-btn"]').on('click', function() {
                var count = $(this).data('count');
                var $progress = $('#populate-progress');
                var $status = $('#populate-status');
                
                $progress.show();
                $status.text('0%');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fts1_populate_data',
                        count: count,
                        nonce: '<?php echo wp_create_nonce('fts1_demo_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text('100% - Complete! Added ' + response.data.inserted + ' records.');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('AJAX error occurred');
                        $progress.hide();
                    }
                });
            });
            
            // Clear data handler
            $('#clear-data-btn').on('click', function(e) {
                if (!confirm('Are you sure you want to delete all demo data?')) {
                    e.preventDefault();
                    return false;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fts1_clear_data',
                        nonce: '<?php echo wp_create_nonce('fts1_demo_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('All data cleared successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render example cards with run buttons
     */
    private function render_example_cards($examples) {
        foreach ($examples as $example) {
            ?>
            <div class="card fts-example-card" data-example="<?php echo $example['num']; ?>">
                <h2>Example <?php echo $example['num']; ?>: <?php echo esc_html($example['title']); ?></h2>
                <p class="description"><?php echo esc_html($example['description']); ?></p>
                
                <h3>SQL Query:</h3>
                <pre class="fts-sql-code"><?php echo esc_html($this->format_sql($example['sql'])); ?></pre>
                
                <?php if (!empty($example['params'])): ?>
                <h4>Parameters:</h4>
                <pre><?php echo esc_html(json_encode($example['params'], JSON_PRETTY_PRINT)); ?></pre>
                <?php endif; ?>
                
                <button class="button button-primary run-query-btn" 
                        data-example="<?php echo $example['num']; ?>"
                        data-sql="<?php echo esc_attr($example['sql']); ?>"
                        data-params="<?php echo esc_attr(json_encode(isset($example['params']) ? $example['params'] : array())); ?>"
                        data-is-explain="<?php echo !empty($example['is_explain']) ? '1' : '0'; ?>"
                        data-run-twice="<?php echo !empty($example['run_twice']) ? '1' : '0'; ?>">
                    Run Query
                </button>
                
                <div class="fts-results" id="results-<?php echo $example['num']; ?>" style="display: none;">
                    <h3>Results:</h3>
                    <div class="fts-results-content"></div>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Display database statistics
     */
    private function display_stats() {
        $total = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        $by_category = $this->wpdb->get_results("
            SELECT category_slug, COUNT(*) as count 
            FROM {$this->table_name} 
            GROUP BY category_slug 
            ORDER BY count DESC
        ");
        
        $by_status = $this->wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM {$this->table_name} 
            GROUP BY status
        ");
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Total Articles</strong></td>
                    <td><?php echo number_format($total); ?></td>
                </tr>
                <?php foreach ($by_category as $cat): ?>
                <tr>
                    <td>Category: <?php echo esc_html($cat->category_slug); ?></td>
                    <td><?php echo number_format($cat->count); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php foreach ($by_status as $stat): ?>
                <tr>
                    <td>Status: <?php echo esc_html($stat->status); ?></td>
                    <td><?php echo number_format($stat->count); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Display categories information
     */
    private function display_categories_info() {
        $categories = array(
            array('id' => 1, 'slug' => 'tutorials', 'name' => 'Tutorials'),
            array('id' => 2, 'slug' => 'news', 'name' => 'News'),
            array('id' => 3, 'slug' => 'guides', 'name' => 'Guides'),
            array('id' => 4, 'slug' => 'reviews', 'name' => 'Reviews'),
            array('id' => 5, 'slug' => 'documentation', 'name' => 'Documentation')
        );
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Slug</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?php echo $cat['id']; ?></td>
                    <td><code><?php echo $cat['slug']; ?></code></td>
                    <td><?php echo $cat['name']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * AJAX: Run a query example
     */
    public function ajax_run_query() {
        // First, send something to prove this function is being called
        if (!isset($_POST['action']) || $_POST['action'] !== 'fts1_run_query') {
            wp_send_json_error(array(
                'message' => 'Wrong action or action not set',
                'received_action' => isset($_POST['action']) ? $_POST['action'] : 'not set'
            ));
            return;
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Permission denied - user cannot manage options'
            ));
            return;
        }
        
        $debug_info = array();
        
        // Collect debug info
        $debug_info['post_keys'] = array_keys($_POST);
        $debug_info['nonce_exists'] = isset($_POST['nonce']);
        $debug_info['sql_exists'] = isset($_POST['sql']);
        $debug_info['params_exists'] = isset($_POST['params']);
        $debug_info['action'] = isset($_POST['action']) ? $_POST['action'] : 'not set';
        $debug_info['handler_reached'] = true;
        
        // Verify nonce
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(array(
                'message' => 'Nonce not provided in request',
                'debug' => $debug_info
            ));
            return;
        }
        
        $nonce_check = wp_verify_nonce($_POST['nonce'], 'fts1_demo_nonce');
        $debug_info['nonce_verify_result'] = $nonce_check;
        
        if (!$nonce_check) {
            wp_send_json_error(array(
                'message' => 'Invalid nonce verification failed',
                'debug' => $debug_info
            ));
            return;
        }
        
        if (!isset($_POST['sql'])) {
            wp_send_json_error(array(
                'message' => 'SQL parameter not provided',
                'debug' => $debug_info
            ));
            return;
        }
        
        $sql = stripslashes($_POST['sql']);
        $params_raw = isset($_POST['params']) ? stripslashes($_POST['params']) : '{}';
        $params = json_decode($params_raw, true);
        $is_explain = isset($_POST['is_explain']) && $_POST['is_explain'] === '1';
        $run_twice = isset($_POST['run_twice']) && $_POST['run_twice'] === '1';
        
        $debug_info['params_raw'] = $params_raw;
        $debug_info['params_decoded'] = $params;
        $debug_info['sql_length'] = strlen($sql);
        $debug_info['json_last_error'] = json_last_error_msg();
        
        if (empty($sql)) {
            wp_send_json_error(array(
                'message' => 'SQL is empty',
                'debug' => $debug_info
            ));
            return;
        }
        
        if (!is_array($params)) {
            $params = array();
        }
        
        // Replace placeholders with actual values
        $prepared_sql = $sql;
        
        if (!empty($params) && is_array($params)) {
            foreach ($params as $key => $value) {
                // Properly escape the value based on type
                if (is_numeric($value)) {
                    $escaped_value = intval($value);
                } else {
                    $escaped_value = "'" . $this->wpdb->_real_escape($value) . "'";
                }
                $prepared_sql = str_replace(':' . $key, $escaped_value, $prepared_sql);
            }
        }
        
        // Measure execution time
        $start_time = microtime(true);
        
        if ($run_twice) {
            // First run
            $results1 = $this->wpdb->get_results($prepared_sql, ARRAY_A);
            $time1 = microtime(true) - $start_time;
            
            // Check for errors
            if ($this->wpdb->last_error) {
                wp_send_json_error('SQL Error: ' . $this->wpdb->last_error);
                return;
            }
            
            // Second run
            $start_time2 = microtime(true);
            $results2 = $this->wpdb->get_results($prepared_sql, ARRAY_A);
            $time2 = microtime(true) - $start_time2;
            
            wp_send_json_success(array(
                'results' => $results1,
                'execution_time_first' => number_format($time1 * 1000, 4),
                'execution_time_second' => number_format($time2 * 1000, 4),
                'row_count' => count($results1),
                'run_twice' => true,
                'sql_executed' => $prepared_sql
            ));
        } else {
            $results = $this->wpdb->get_results($prepared_sql, ARRAY_A);
            $execution_time = microtime(true) - $start_time;
            
            // Check for errors
            if ($this->wpdb->last_error) {
                wp_send_json_error('SQL Error: ' . $this->wpdb->last_error . ' | Query: ' . $prepared_sql);
                return;
            }
            
            wp_send_json_success(array(
                'results' => $results ? $results : array(),
                'execution_time' => number_format($execution_time * 1000, 4),
                'row_count' => $results ? count($results) : 0,
                'is_explain' => $is_explain,
                'sql_executed' => $prepared_sql
            ));
        }
    }
    
    /**
     * AJAX: Populate test data
     */
    public function ajax_populate_data() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fts1_demo_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $count = isset($_POST['count']) ? intval($_POST['count']) : 100;
        $inserted = $this->populate_test_data($count);
        
        wp_send_json_success(array('inserted' => $inserted));
    }
    
    /**
     * AJAX: Clear all data
     */
    public function ajax_clear_data() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fts1_demo_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $this->wpdb->query("TRUNCATE TABLE {$this->table_name}");
        
        wp_send_json_success();
    }
    
    /**
     * Populate initial test data
     */
    private function maybe_populate_initial_data() {
        $count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        if ($count == 0) {
            $this->populate_test_data(50);
        }
    }
    
    /**
     * Generate test data
     */
    private function populate_test_data($count = 100) {
        $categories = array(
            array('id' => 1, 'slug' => 'tutorials'),
            array('id' => 2, 'slug' => 'news'),
            array('id' => 3, 'slug' => 'guides'),
            array('id' => 4, 'slug' => 'reviews'),
            array('id' => 5, 'slug' => 'documentation')
        );
        
        $keywords = array(
            'wordpress', 'plugin', 'theme', 'tutorial', 'guide', 'beginner', 'advanced',
            'php', 'mysql', 'database', 'optimization', 'performance', 'security',
            'development', 'programming', 'code', 'function', 'class', 'api',
            'rest', 'ajax', 'javascript', 'css', 'html', 'responsive', 'design'
        );
        
        $authors = array('John Doe', 'Jane Smith', 'Bob Johnson', 'Alice Williams', 'Charlie Brown');
        $statuses = array('published', 'draft', 'pending');
        
        $inserted = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $category = $categories[array_rand($categories)];
            $keyword1 = $keywords[array_rand($keywords)];
            $keyword2 = $keywords[array_rand($keywords)];
            $keyword3 = $keywords[array_rand($keywords)];
            
            $title = ucfirst($keyword1) . ' ' . ucfirst($keyword2) . ' - A Complete Guide';
            $content = "This is a comprehensive article about {$keyword1} and {$keyword2}. ";
            $content .= "Learn everything you need to know about {$keyword3} in this tutorial. ";
            $content .= "We cover best practices, common pitfalls, and advanced techniques. ";
            $content .= str_repeat("Lorem ipsum dolor sit amet, consectetur adipiscing elit. ", rand(5, 20));
            
            $result = $this->wpdb->insert(
                $this->table_name,
                array(
                    'title' => $title,
                    'content' => $content,
                    'category_id' => $category['id'],
                    'category_slug' => $category['slug'],
                    'author' => $authors[array_rand($authors)],
                    'publish_date' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 365) . ' days')),
                    'status' => $statuses[array_rand($statuses)],
                    'views' => rand(0, 1000)
                ),
                array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d')
            );
            
            if ($result) {
                $inserted++;
            }
        }
        
        return $inserted;
    }
    
    /**
     * Format SQL for display
     */
    private function format_sql($sql) {
        // Basic SQL formatting
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = str_replace(array(' FROM ', ' WHERE ', ' AND ', ' OR ', ' ORDER BY ', ' LIMIT ', ' GROUP BY ', ' HAVING '),
                          array("\nFROM ", "\nWHERE ", "\n  AND ", "\n  OR ", "\nORDER BY ", "\nLIMIT ", "\nGROUP BY ", "\nHAVING "),
                          $sql);
        return trim($sql);
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    private function enqueue_admin_scripts() {
        ?>
        <style>
            .fts-example-card {
                margin-bottom: 30px;
                padding: 20px;
            }
            .fts-sql-code {
                background: #f5f5f5;
                padding: 15px;
                border-left: 4px solid #2271b1;
                overflow-x: auto;
                font-family: monospace;
                font-size: 13px;
                line-height: 1.6;
            }
            .fts-results {
                margin-top: 20px;
                padding: 15px;
                background: #f0f0f1;
                border-radius: 4px;
            }
            .fts-results-content {
                max-height: 400px;
                overflow-y: auto;
            }
            .fts-results table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                background: white;
            }
            .fts-results th,
            .fts-results td {
                padding: 8px;
                border: 1px solid #ddd;
                text-align: left;
            }
            .fts-results th {
                background: #2271b1;
                color: white;
                font-weight: bold;
            }
            .fts-results tr:nth-child(even) {
                background: #f9f9f9;
            }
            .fts-timing {
                margin: 10px 0;
                padding: 10px;
                background: #d4edda;
                border-left: 4px solid #28a745;
                color: #155724;
            }
            .notice.inline {
                margin: 10px 0;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.run-query-btn').on('click', function() {
                var $btn = $(this);
                var exampleNum = $btn.data('example');
                var sql = $btn.data('sql');
                var paramsAttr = $btn.attr('data-params');
                var params;
                
                // Parse params
                try {
                    params = JSON.parse(paramsAttr);
                } catch(e) {
                    console.error('Error parsing params:', e);
                    params = {};
                }
                
                var isExplain = $btn.data('is-explain');
                var runTwice = $btn.data('run-twice');
                var $results = $('#results-' + exampleNum);
                var $content = $results.find('.fts-results-content');
                
                // Debug logging
                console.log('Example:', exampleNum);
                console.log('SQL:', sql);
                console.log('Params attr:', paramsAttr);
                console.log('Params parsed:', params);
                console.log('Params type:', typeof params);
                
                $btn.prop('disabled', true).text('Running...');
                $content.html('<p>Executing query...</p>');
                $results.show();
                
                var paramsJson = JSON.stringify(params);
                
                var ajaxData = {
                    action: 'fts1_run_query',
                    sql: sql,
                    params: paramsJson,
                    is_explain: isExplain ? '1' : '0',
                    run_twice: runTwice ? '1' : '0',
                    nonce: '<?php echo wp_create_nonce('fts1_demo_nonce'); ?>'
                };
                
                console.log('AJAX Data:', ajaxData);
                console.log('Params JSON:', paramsJson);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: ajaxData,
                    success: function(response) {
                        if (response.success) {
                            var html = '';
                            
                            // Show executed SQL for debugging
                            if (response.data.sql_executed) {
                                html += '<div class="notice notice-info inline">';
                                html += '<p><strong>Executed SQL:</strong></p>';
                                html += '<pre style="background: white; padding: 10px; overflow-x: auto;">' + 
                                        response.data.sql_executed + '</pre>';
                                html += '</div>';
                            }
                            
                            if (response.data.run_twice) {
                                html += '<div class="fts-timing">';
                                html += '<strong>First Run:</strong> ' + response.data.execution_time_first + ' ms<br>';
                                html += '<strong>Second Run:</strong> ' + response.data.execution_time_second + ' ms<br>';
                                html += '<strong>Difference:</strong> ' + (response.data.execution_time_first - response.data.execution_time_second).toFixed(4) + ' ms';
                                html += '</div>';
                            } else {
                                html += '<div class="fts-timing">';
                                html += '<strong>Execution Time:</strong> ' + response.data.execution_time + ' ms';
                                html += '</div>';
                            }
                            
                            html += '<p><strong>Rows Returned:</strong> ' + response.data.row_count + '</p>';
                            
                            if (response.data.results.length > 0) {
                                html += '<table>';
                                html += '<thead><tr>';
                                
                                // Table headers
                                for (var key in response.data.results[0]) {
                                    html += '<th>' + key + '</th>';
                                }
                                html += '</tr></thead><tbody>';
                                
                                // Table rows
                                response.data.results.forEach(function(row) {
                                    html += '<tr>';
                                    for (var key in row) {
                                        var value = row[key];
                                        if (value === null) value = '<em>NULL</em>';
                                        html += '<td>' + value + '</td>';
                                    }
                                    html += '</tr>';
                                });
                                
                                html += '</tbody></table>';
                            } else {
                                html += '<p><em>No results returned.</em></p>';
                            }
                            
                            $content.html(html);
                        } else {
                            console.error('Error response:', response.data);
                            var errorMsg = '<div class="notice notice-error inline">';
                            
                            if (typeof response.data === 'object') {
                                errorMsg += '<p><strong>Error:</strong> ' + (response.data.message || response.data) + '</p>';
                                if (response.data.debug) {
                                    errorMsg += '<p><strong>Debug Info:</strong></p>';
                                    errorMsg += '<pre style="background: white; padding: 10px; overflow-x: auto;">' + 
                                               JSON.stringify(response.data.debug, null, 2) + '</pre>';
                                }
                            } else {
                                errorMsg += '<p>Error: ' + response.data + '</p>';
                            }
                            
                            errorMsg += '</div>';
                            $content.html(errorMsg);
                        }
                        
                        $btn.prop('disabled', false).text('Run Query');
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', xhr, status, error);
                        console.log('Response:', xhr.responseText);
                        $content.html('<div class="notice notice-error inline"><p>AJAX error: ' + error + '</p><pre>' + xhr.responseText + '</pre></div>');
                        $btn.prop('disabled', false).text('Run Query');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// Initialize the plugin
new MySQL_FTS_Demo_Plugin();
