-- ============================================================================
-- WordPress FTS Demo Data - Sample Posts for Full-Text Search Examples
-- ============================================================================
-- This script creates sample blog posts to demonstrate the FTS examples at:
-- https://advanced-wordpress-search.netlify.app/fts/wp-fts-sql-examples/
--
-- Prerequisites:
-- These posts use sequential IDs starting from 1000 to avoid conflicts
-- ============================================================================

-- Clear any existing demo data (optional)
-- DELETE FROM wp_posts WHERE ID >= 999;

-- ============================================================================
-- Create FULLTEXT Indexes
-- ============================================================================
-- Combined index for most queries
CREATE FULLTEXT INDEX idx_posts_fulltext ON wp_posts(post_title, post_content);

-- Separate indexes needed for Example B (weighted title ranking)
CREATE FULLTEXT INDEX idx_posts_title ON wp_posts(post_title);
CREATE FULLTEXT INDEX idx_posts_content ON wp_posts(post_content);

-- ============================================================================
-- NATURAL LANGUAGE MODE Examples
-- Posts about WordPress performance, caching, and optimization
-- ============================================================================

-- Post 1: Heavy focus on caching (will rank high for "wordpress performance caching")
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1001, 
    1, 
    NOW(), 
    UTC_TIMESTAMP(), 
    'WordPress performance is critical for user experience and SEO. Caching is the single most important factor in WordPress performance optimization. When you implement WordPress caching, you can dramatically improve page load times. There are several types of caching to consider: object cache, page cache, browser cache, and CDN caching. WordPress performance tuning requires understanding how caching works at each level. Object cache using Redis or Memcached stores database query results. Page caching saves fully rendered HTML. Browser caching tells browsers to store static assets locally. Together, these caching strategies can make WordPress performance exceptional.',
    'Ultimate Guide to WordPress Performance and Caching',
    'Learn how caching improves WordPress performance',
    'publish',
    'open',
    'open',
    '',
    'wordpress-performance-caching-guide',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 2: Focus on performance tuning (good for title ranking example)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1002,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Optimizing database queries is essential for site speed. Enable gzip compression on your server. Minify CSS and JavaScript files. Use a content delivery network. Lazy load images below the fold. Reduce HTTP requests by combining files. Choose a fast hosting provider with SSD storage.',
    'Performance Tuning for WordPress Sites',
    'Advanced techniques for WordPress optimization',
    'publish',
    'open',
    'open',
    '',
    'performance-tuning-wordpress',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 3: Long-form content about object cache and Redis
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1003,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Understanding object cache in WordPress is crucial for high-traffic sites. Object cache stores the results of complex database queries in memory, eliminating the need to run them repeatedly. Redis is a powerful in-memory data structure store perfect for persistent object caching. When you configure Redis as your object cache backend, WordPress can retrieve cached objects in microseconds instead of milliseconds. Redis persistent storage ensures your cache survives server restarts. The object cache drop-in file connects WordPress to Redis. Popular plugins like Redis Object Cache make setup straightforward. For enterprise WordPress installations, Redis object cache is essential. Persistent object caching with Redis can handle millions of requests per day. The key advantage of Redis over other solutions is its persistence and advanced data structures.',
    'Deep Dive into Object Cache with Redis',
    'Complete guide to persistent object caching',
    'publish',
    'open',
    'open',
    '',
    'object-cache-redis-guide',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 4: Contains stopwords - demonstrates they're ignored
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1004,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'There are many ways to speed up a WordPress site. First, choose quality hosting. Second, implement caching at multiple levels. Third, optimize your images using compression. Fourth, minimize plugins and only keep essential ones active. Fifth, use a lightweight theme. These strategies work together to make your WordPress site load faster.',
    'How to Speed Up a WordPress Site',
    'Simple strategies for faster page loads',
    'publish',
    'open',
    'open',
    '',
    'how-to-speed-up-wordpress',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- ============================================================================
-- BOOLEAN MODE Examples
-- Posts for testing required, excluded, and phrase matching
-- ============================================================================

-- Post 5: About WordPress cache WITHOUT plugins (for exclusion example)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1005,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'WordPress cache configuration can be done at the server level without relying on additional software. Apache mod_cache and nginx fastcgi_cache provide powerful WordPress cache solutions. Server-side WordPress cache delivers better performance than most alternatives. You can configure cache headers, set expiration rules, and implement cache purging strategies directly in your web server configuration. This approach to WordPress cache gives you complete control.',
    'Server-Level WordPress Cache Configuration',
    'Implementing cache without third-party tools',
    'publish',
    'open',
    'open',
    '',
    'server-level-wordpress-cache',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 6: WordPress caching PLUGIN (will be excluded by -plugin search)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1006,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'The best WordPress caching plugin options include WP Rocket, W3 Total Cache, and WP Super Cache. Each plugin offers different features for WordPress cache management. WP Rocket is a premium plugin with automatic cache configuration. W3 Total Cache is a free plugin with extensive options. Installing a caching plugin is the easiest way to improve WordPress performance.',
    'Top WordPress Caching Plugins Compared',
    'Review of popular caching plugin solutions',
    'publish',
    'open',
    'open',
    '',
    'wordpress-caching-plugins',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 7: Multiple variations of "optimize" (for prefix matching)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1007,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'When you optimize your database, you improve query performance. An optimizer tool can analyze slow queries. Database optimization is an ongoing process. The query optimizer chooses the most efficient execution plan. Regular optimization maintenance prevents bloat. Optimized tables use less disk space and respond faster. Image optimization reduces bandwidth usage. Code optimization eliminates redundant operations.',
    'Database Optimization Techniques',
    'How to optimize WordPress databases effectively',
    'publish',
    'open',
    'open',
    '',
    'database-optimization-techniques',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 8: Contains exact phrase "object cache" (for phrase matching)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1008,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'The object cache in WordPress stores database query results in memory. When WordPress needs data, it first checks the object cache. If the data exists in the object cache, WordPress skips the database query entirely. The object cache is particularly effective for repeated queries. Enabling persistent object cache requires a drop-in file and a backend like Redis or Memcached. The object cache API provides functions like wp_cache_get() and wp_cache_set().',
    'Understanding WordPress Object Cache',
    'Technical overview of the object cache system',
    'publish',
    'open',
    'open',
    '',
    'understanding-object-cache',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 9: Object cache with Redis, NOT Memcached (for combined boolean example)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1009,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Redis has become the preferred backend for object cache in modern WordPress deployments. Unlike other solutions, Redis offers persistence, replication, and advanced data structures. Setting up object cache with Redis involves installing Redis server, adding the object cache drop-in, and configuring the connection. Redis handles object cache operations with exceptional speed. The combination of object cache and Redis provides enterprise-grade performance.',
    'Why Redis is Perfect for Object Cache',
    'Advantages of Redis over alternatives',
    'publish',
    'open',
    'open',
    '',
    'redis-object-cache-advantages',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 10: About cache and Redis (for tilde relevance boosting)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1010,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Modern cache strategies rely on in-memory storage solutions. Redis excels at cache operations with its lightning-fast read/write capabilities. Implementing cache with Redis reduces database load significantly. Redis cache can store various data types including strings, hashes, lists, and sets. Cache invalidation becomes manageable with Redis TTL features. The Redis cache implementation supports clustering for high availability.',
    'Advanced Cache Strategies with Redis',
    'Leveraging Redis for optimal cache performance',
    'publish',
    'open',
    'open',
    '',
    'advanced-cache-redis-strategies',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 11: Just about cache (for tilde example - Redis optional)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1011,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Cache fundamentals apply across all WordPress installations. Browser cache stores static assets locally. Page cache saves rendered HTML output. Database query cache reduces repetitive queries. Fragment cache stores specific page components. Cache warming prepopulates cache before traffic arrives. Cache headers control client-side caching behavior. Effective cache strategy combines multiple cache layers.',
    'Cache Fundamentals for WordPress',
    'Understanding different types of cache',
    'publish',
    'open',
    'open',
    '',
    'cache-fundamentals-wordpress',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- ============================================================================
-- QUERY EXPANSION MODE Examples
-- Posts with related terms and synonyms
-- ============================================================================

-- Post 12: SEO content with related terms (for query expansion)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1012,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Search engine optimization requires understanding how search engines rank content. Improving your search ranking depends on multiple factors including content quality, backlinks, and technical SEO. Organic traffic comes from unpaid search results. To increase organic traffic, focus on keyword research, on-page optimization, and link building. Search engine algorithms evaluate relevance and authority. Higher search ranking leads to more organic traffic and better visibility.',
    'Complete Guide to Search Engine Optimization',
    'Boost your search ranking and organic traffic',
    'publish',
    'open',
    'open',
    '',
    'search-engine-optimization-guide',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 13: More SEO-related content using synonym terms
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1013,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Content optimization for search engines involves strategic keyword placement and natural language. Meta descriptions influence click-through rates from search results. Title tags are critical ranking factors. Internal linking helps search engines understand site structure. Mobile optimization affects search ranking. Page speed impacts both ranking and organic traffic. Schema markup provides search engines with structured data.',
    'On-Page Optimization Checklist',
    'Essential elements for better search performance',
    'publish',
    'open',
    'open',
    '',
    'on-page-optimization-checklist',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 14: WordPress security with related terms (for query expansion)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1014,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'WordPress security requires multiple layers of protection. Install a web application firewall to block malicious requests. Scan regularly for malware using security plugins. Prevent brute force attacks by limiting login attempts. Security hardening includes disabling file editing, changing the database prefix, and hiding WordPress version. A firewall provides the first line of defense against attacks. Regular malware scans detect infections early.',
    'WordPress Security Best Practices',
    'Protect your site from malware and attacks',
    'publish',
    'open',
    'open',
    '',
    'wordpress-security-practices',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 15: More security content with related vocabulary
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1015,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Implementing security hardening protects against common vulnerabilities. Two-factor authentication adds login protection beyond passwords. Regular backups provide recovery options after security incidents. SSL certificates encrypt data transmission. Login protection includes CAPTCHA, IP whitelisting, and account lockouts after failed attempts. Security hardening checklist items include file permissions, database security, and removing unused themes.',
    'Advanced WordPress Security Hardening',
    'Comprehensive login protection and hardening techniques',
    'publish',
    'open',
    'open',
    '',
    'advanced-security-hardening',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 16: Speed-related content with synonyms (for query expansion)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1016,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Site performance affects user experience and search rankings. Caching reduces server load and improves response times. Content delivery networks distribute assets globally for faster delivery. Image optimization reduces file sizes without quality loss. TTFB (Time To First Byte) measures server responsiveness. Performance optimization techniques include minification, compression, and lazy loading. Fast TTFB indicates efficient server processing.',
    'Mastering Website Performance',
    'Optimization techniques for maximum speed',
    'publish',
    'open',
    'open',
    '',
    'mastering-website-performance',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 17: Hosting with related terms (for query expansion)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1017,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'WordPress hosting quality impacts site performance and reliability. Uptime guarantees ensure your site remains accessible. Bandwidth limits determine traffic capacity. Shared hosting is economical but resources are shared among multiple sites. VPS (Virtual Private Server) hosting provides dedicated resources and better performance. CDN (Content Delivery Network) integration improves global load times. Monitor uptime to ensure hosting reliability.',
    'Choosing the Right WordPress Hosting',
    'Understanding uptime, bandwidth, and hosting types',
    'publish',
    'open',
    'open',
    '',
    'choosing-wordpress-hosting',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 18: More hosting content with related terms
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1018,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Managed WordPress hosting includes automatic updates and security monitoring. Shared hosting suits small sites with limited traffic. VPS hosting scales better for growing sites. Cloud hosting offers flexibility and redundancy. CDN integration is essential for international audiences. Evaluate hosting based on uptime history, bandwidth allocation, and support quality. Premium hosting typically includes CDN, backups, and performance optimization.',
    'Managed vs Shared vs VPS Hosting',
    'Comparing hosting options for WordPress',
    'publish',
    'open',
    'open',
    '',
    'comparing-hosting-options',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- ============================================================================
-- Additional supporting posts for variety
-- ============================================================================

-- Post 19: General WordPress post (lower relevance for most searches)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1019,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'WordPress is a versatile content management system powering millions of websites worldwide. It offers thousands of themes and plugins for customization. The WordPress community provides extensive documentation and support. Regular updates ensure security and add new features. WordPress can be used for blogs, business sites, portfolios, and e-commerce stores.',
    'Introduction to WordPress',
    'Getting started with the WordPress platform',
    'publish',
    'open',
    'open',
    '',
    'introduction-to-wordpress',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 20: About themes (different topic - provides variety)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1020,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Choosing the right WordPress theme affects both aesthetics and performance. Lightweight themes load faster and use fewer resources. Responsive themes adapt to different screen sizes. Premium themes often include advanced features and dedicated support. Theme customization options range from basic color changes to complete layout control. Consider page builders for complex designs.',
    'Guide to WordPress Themes',
    'Finding and customizing the perfect theme',
    'publish',
    'open',
    'open',
    '',
    'guide-to-wordpress-themes',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 1: Heavy focus on caching (will rank high for "wordpress performance caching")
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1021, 
    1, 
    NOW(), 
    UTC_TIMESTAMP(), 
    'WordPress performance is critical for user experience and SEO. Caching is the single most important factor in WordPress performance optimization. When you implement WordPress caching, you can dramatically improve page load times. There are several types of caching to consider: object cache, page cache, browser cache, and CDN caching. WordPress performance tuning requires understanding how caching works at each level. Object cache using Redis or Memcached stores database query results. Page caching saves fully rendered HTML. Browser caching tells browsers to store static assets locally. Together, these caching strategies can make WordPress performance exceptional.',
    'Ultimate Guide to WordPress Performance and Caching',
    'Learn how caching improves WordPress performance',
    'publish',
    'open',
    'open',
    '',
    'wordpress-performance-caching-guide',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 2: Focus on performance tuning (good for title ranking example)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1022,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Optimizing database queries is essential for site speed. Enable gzip compression on your server. Minify CSS and JavaScript files. Use a content delivery network. Lazy load images below the fold. Reduce HTTP requests by combining files. Choose a fast hosting provider with SSD storage.',
    'Performance Tuning for WordPress Sites',
    'Advanced techniques for WordPress optimization',
    'publish',
    'open',
    'open',
    '',
    'performance-tuning-wordpress',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 3: Long-form content about object cache and Redis
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1023,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Understanding object cache in WordPress is crucial for high-traffic sites. Object cache stores the results of complex database queries in memory, eliminating the need to run them repeatedly. Redis is a powerful in-memory data structure store perfect for persistent object caching. When you configure Redis as your object cache backend, WordPress can retrieve cached objects in microseconds instead of milliseconds. Redis persistent storage ensures your cache survives server restarts. The object cache drop-in file connects WordPress to Redis. Popular plugins like Redis Object Cache make setup straightforward. For enterprise WordPress installations, Redis object cache is essential. Persistent object caching with Redis can handle millions of requests per day. The key advantage of Redis over other solutions is its persistence and advanced data structures.',
    'Deep Dive into Object Cache with Redis',
    'Complete guide to persistent object caching',
    'publish',
    'open',
    'open',
    '',
    'object-cache-redis-guide',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 4: Contains stopwords - demonstrates they're ignored
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1024,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'There are many ways to speed up a WordPress site. First, choose quality hosting. Second, implement caching at multiple levels. Third, optimize your images using compression. Fourth, minimize plugins and only keep essential ones active. Fifth, use a lightweight theme. These strategies work together to make your WordPress site load faster.',
    'How to Speed Up a WordPress Site',
    'Simple strategies for faster page loads',
    'publish',
    'open',
    'open',
    '',
    'how-to-speed-up-wordpress',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- ============================================================================
-- BOOLEAN MODE Examples
-- Posts for testing required, excluded, and phrase matching
-- ============================================================================

-- Post 5: About WordPress cache WITHOUT plugins (for exclusion example)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1025,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'WordPress cache configuration can be done at the server level without relying on additional software. Apache mod_cache and nginx fastcgi_cache provide powerful WordPress cache solutions. Server-side WordPress cache delivers better performance than most alternatives. You can configure cache headers, set expiration rules, and implement cache purging strategies directly in your web server configuration. This approach to WordPress cache gives you complete control.',
    'Server-Level WordPress Cache Configuration',
    'Implementing cache without third-party tools',
    'publish',
    'open',
    'open',
    '',
    'server-level-wordpress-cache',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 6: WordPress caching PLUGIN (will be excluded by -plugin search)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1026,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'The best WordPress caching plugin options include WP Rocket, W3 Total Cache, and WP Super Cache. Each plugin offers different features for WordPress cache management. WP Rocket is a premium plugin with automatic cache configuration. W3 Total Cache is a free plugin with extensive options. Installing a caching plugin is the easiest way to improve WordPress performance.',
    'Top WordPress Caching Plugins Compared',
    'Review of popular caching plugin solutions',
    'publish',
    'open',
    'open',
    '',
    'wordpress-caching-plugins',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 7: Multiple variations of "optimize" (for prefix matching)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1027,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'When you optimize your database, you improve query performance. An optimizer tool can analyze slow queries. Database optimization is an ongoing process. The query optimizer chooses the most efficient execution plan. Regular optimization maintenance prevents bloat. Optimized tables use less disk space and respond faster. Image optimization reduces bandwidth usage. Code optimization eliminates redundant operations.',
    'Database Optimization Techniques',
    'How to optimize WordPress databases effectively',
    'publish',
    'open',
    'open',
    '',
    'database-optimization-techniques',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 8: Contains exact phrase "object cache" (for phrase matching)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1028,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'The object cache in WordPress stores database query results in memory. When WordPress needs data, it first checks the object cache. If the data exists in the object cache, WordPress skips the database query entirely. The object cache is particularly effective for repeated queries. Enabling persistent object cache requires a drop-in file and a backend like Redis or Memcached. The object cache API provides functions like wp_cache_get() and wp_cache_set().',
    'Understanding WordPress Object Cache',
    'Technical overview of the object cache system',
    'publish',
    'open',
    'open',
    '',
    'understanding-object-cache',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 9: Object cache with Redis, NOT Memcached (for combined boolean example)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1029,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Redis has become the preferred backend for object cache in modern WordPress deployments. Unlike other solutions, Redis offers persistence, replication, and advanced data structures. Setting up object cache with Redis involves installing Redis server, adding the object cache drop-in, and configuring the connection. Redis handles object cache operations with exceptional speed. The combination of object cache and Redis provides enterprise-grade performance.',
    'Why Redis is Perfect for Object Cache',
    'Advantages of Redis over alternatives',
    'publish',
    'open',
    'open',
    '',
    'redis-object-cache-advantages',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 10: About cache and Redis (for tilde relevance boosting)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1030,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Modern cache strategies rely on in-memory storage solutions. Redis excels at cache operations with its lightning-fast read/write capabilities. Implementing cache with Redis reduces database load significantly. Redis cache can store various data types including strings, hashes, lists, and sets. Cache invalidation becomes manageable with Redis TTL features. The Redis cache implementation supports clustering for high availability.',
    'Advanced Cache Strategies with Redis',
    'Leveraging Redis for optimal cache performance',
    'publish',
    'open',
    'open',
    '',
    'advanced-cache-redis-strategies',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 11: Just about cache (for tilde example - Redis optional)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1031,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Cache fundamentals apply across all WordPress installations. Browser cache stores static assets locally. Page cache saves rendered HTML output. Database query cache reduces repetitive queries. Fragment cache stores specific page components. Cache warming prepopulates cache before traffic arrives. Cache headers control client-side caching behavior. Effective cache strategy combines multiple cache layers.',
    'Cache Fundamentals for WordPress',
    'Understanding different types of cache',
    'publish',
    'open',
    'open',
    '',
    'cache-fundamentals-wordpress',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- ============================================================================
-- QUERY EXPANSION MODE Examples
-- Posts with related terms and synonyms
-- ============================================================================

-- Post 12: SEO content with related terms (for query expansion)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1032,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Search engine optimization requires understanding how search engines rank content. Improving your search ranking depends on multiple factors including content quality, backlinks, and technical SEO. Organic traffic comes from unpaid search results. To increase organic traffic, focus on keyword research, on-page optimization, and link building. Search engine algorithms evaluate relevance and authority. Higher search ranking leads to more organic traffic and better visibility.',
    'Complete Guide to Search Engine Optimization',
    'Boost your search ranking and organic traffic',
    'publish',
    'open',
    'open',
    '',
    'search-engine-optimization-guide',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 13: More SEO-related content using synonym terms
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1033,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Content optimization for search engines involves strategic keyword placement and natural language. Meta descriptions influence click-through rates from search results. Title tags are critical ranking factors. Internal linking helps search engines understand site structure. Mobile optimization affects search ranking. Page speed impacts both ranking and organic traffic. Schema markup provides search engines with structured data.',
    'On-Page Optimization Checklist',
    'Essential elements for better search performance',
    'publish',
    'open',
    'open',
    '',
    'on-page-optimization-checklist',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 14: WordPress security with related terms (for query expansion)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1034,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'WordPress security requires multiple layers of protection. Install a web application firewall to block malicious requests. Scan regularly for malware using security plugins. Prevent brute force attacks by limiting login attempts. Security hardening includes disabling file editing, changing the database prefix, and hiding WordPress version. A firewall provides the first line of defense against attacks. Regular malware scans detect infections early.',
    'WordPress Security Best Practices',
    'Protect your site from malware and attacks',
    'publish',
    'open',
    'open',
    '',
    'wordpress-security-practices',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 15: More security content with related vocabulary
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1035,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Implementing security hardening protects against common vulnerabilities. Two-factor authentication adds login protection beyond passwords. Regular backups provide recovery options after security incidents. SSL certificates encrypt data transmission. Login protection includes CAPTCHA, IP whitelisting, and account lockouts after failed attempts. Security hardening checklist items include file permissions, database security, and removing unused themes.',
    'Advanced WordPress Security Hardening',
    'Comprehensive login protection and hardening techniques',
    'publish',
    'open',
    'open',
    '',
    'advanced-security-hardening',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 16: Speed-related content with synonyms (for query expansion)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1036,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Site performance affects user experience and search rankings. Caching reduces server load and improves response times. Content delivery networks distribute assets globally for faster delivery. Image optimization reduces file sizes without quality loss. TTFB (Time To First Byte) measures server responsiveness. Performance optimization techniques include minification, compression, and lazy loading. Fast TTFB indicates efficient server processing.',
    'Mastering Website Performance',
    'Optimization techniques for maximum speed',
    'publish',
    'open',
    'open',
    '',
    'mastering-website-performance',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 17: Hosting with related terms (for query expansion)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1037,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'WordPress hosting quality impacts site performance and reliability. Uptime guarantees ensure your site remains accessible. Bandwidth limits determine traffic capacity. Shared hosting is economical but resources are shared among multiple sites. VPS (Virtual Private Server) hosting provides dedicated resources and better performance. CDN (Content Delivery Network) integration improves global load times. Monitor uptime to ensure hosting reliability.',
    'Choosing the Right WordPress Hosting',
    'Understanding uptime, bandwidth, and hosting types',
    'publish',
    'open',
    'open',
    '',
    'choosing-wordpress-hosting',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 18: More hosting content with related terms
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1038,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Managed WordPress hosting includes automatic updates and security monitoring. Shared hosting suits small sites with limited traffic. VPS hosting scales better for growing sites. Cloud hosting offers flexibility and redundancy. CDN integration is essential for international audiences. Evaluate hosting based on uptime history, bandwidth allocation, and support quality. Premium hosting typically includes CDN, backups, and performance optimization.',
    'Managed vs Shared vs VPS Hosting',
    'Comparing hosting options for WordPress',
    'publish',
    'open',
    'open',
    '',
    'comparing-hosting-options',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);




-- Post 1: Heavy focus on caching (will rank high for "wordpress performance caching")
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1039, 
    1, 
    NOW(), 
    UTC_TIMESTAMP(), 
    'WordPress performance is critical for user experience and SEO. Caching is the single most important factor in WordPress performance optimization. When you implement WordPress caching, you can dramatically improve page load times. There are several types of caching to consider: object cache, page cache, browser cache, and CDN caching. WordPress performance tuning requires understanding how caching works at each level. Object cache using Redis or Memcached stores database query results. Page caching saves fully rendered HTML. Browser caching tells browsers to store static assets locally. Together, these caching strategies can make WordPress performance exceptional.',
    'Ultimate Guide to WordPress Performance and Caching',
    'Learn how caching improves WordPress performance',
    'publish',
    'open',
    'open',
    '',
    'wordpress-performance-caching-guide',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 2: Focus on performance tuning (good for title ranking example)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1040,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Optimizing database queries is essential for site speed. Enable gzip compression on your server. Minify CSS and JavaScript files. Use a content delivery network. Lazy load images below the fold. Reduce HTTP requests by combining files. Choose a fast hosting provider with SSD storage.',
    'Performance Tuning for WordPress Sites',
    'Advanced techniques for WordPress optimization',
    'publish',
    'open',
    'open',
    '',
    'performance-tuning-wordpress',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 3: Long-form content about object cache and Redis
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1041,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Understanding object cache in WordPress is crucial for high-traffic sites. Object cache stores the results of complex database queries in memory, eliminating the need to run them repeatedly. Redis is a powerful in-memory data structure store perfect for persistent object caching. When you configure Redis as your object cache backend, WordPress can retrieve cached objects in microseconds instead of milliseconds. Redis persistent storage ensures your cache survives server restarts. The object cache drop-in file connects WordPress to Redis. Popular plugins like Redis Object Cache make setup straightforward. For enterprise WordPress installations, Redis object cache is essential. Persistent object caching with Redis can handle millions of requests per day. The key advantage of Redis over other solutions is its persistence and advanced data structures.',
    'Deep Dive into Object Cache with Redis',
    'Complete guide to persistent object caching',
    'publish',
    'open',
    'open',
    '',
    'object-cache-redis-guide',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 4: Contains stopwords - demonstrates they're ignored
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1042,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'There are many ways to speed up a WordPress site. First, choose quality hosting. Second, implement caching at multiple levels. Third, optimize your images using compression. Fourth, minimize plugins and only keep essential ones active. Fifth, use a lightweight theme. These strategies work together to make your WordPress site load faster.',
    'How to Speed Up a WordPress Site',
    'Simple strategies for faster page loads',
    'publish',
    'open',
    'open',
    '',
    'how-to-speed-up-wordpress',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- ============================================================================
-- BOOLEAN MODE Examples
-- Posts for testing required, excluded, and phrase matching
-- ============================================================================

-- Post 5: About WordPress cache WITHOUT plugins (for exclusion example)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1043,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'WordPress cache configuration can be done at the server level without relying on additional software. Apache mod_cache and nginx fastcgi_cache provide powerful WordPress cache solutions. Server-side WordPress cache delivers better performance than most alternatives. You can configure cache headers, set expiration rules, and implement cache purging strategies directly in your web server configuration. This approach to WordPress cache gives you complete control.',
    'Server-Level WordPress Cache Configuration',
    'Implementing cache without third-party tools',
    'publish',
    'open',
    'open',
    '',
    'server-level-wordpress-cache',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 6: WordPress caching PLUGIN (will be excluded by -plugin search)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1044,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'The best WordPress caching plugin options include WP Rocket, W3 Total Cache, and WP Super Cache. Each plugin offers different features for WordPress cache management. WP Rocket is a premium plugin with automatic cache configuration. W3 Total Cache is a free plugin with extensive options. Installing a caching plugin is the easiest way to improve WordPress performance.',
    'Top WordPress Caching Plugins Compared',
    'Review of popular caching plugin solutions',
    'publish',
    'open',
    'open',
    '',
    'wordpress-caching-plugins',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 7: Multiple variations of "optimize" (for prefix matching)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1045,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'When you optimize your database, you improve query performance. An optimizer tool can analyze slow queries. Database optimization is an ongoing process. The query optimizer chooses the most efficient execution plan. Regular optimization maintenance prevents bloat. Optimized tables use less disk space and respond faster. Image optimization reduces bandwidth usage. Code optimization eliminates redundant operations.',
    'Database Optimization Techniques',
    'How to optimize WordPress databases effectively',
    'publish',
    'open',
    'open',
    '',
    'database-optimization-techniques',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 8: Contains exact phrase "object cache" (for phrase matching)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1046,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'The object cache in WordPress stores database query results in memory. When WordPress needs data, it first checks the object cache. If the data exists in the object cache, WordPress skips the database query entirely. The object cache is particularly effective for repeated queries. Enabling persistent object cache requires a drop-in file and a backend like Redis or Memcached. The object cache API provides functions like wp_cache_get() and wp_cache_set().',
    'Understanding WordPress Object Cache',
    'Technical overview of the object cache system',
    'publish',
    'open',
    'open',
    '',
    'understanding-object-cache',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 9: Object cache with Redis, NOT Memcached (for combined boolean example)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1047,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Redis has become the preferred backend for object cache in modern WordPress deployments. Unlike other solutions, Redis offers persistence, replication, and advanced data structures. Setting up object cache with Redis involves installing Redis server, adding the object cache drop-in, and configuring the connection. Redis handles object cache operations with exceptional speed. The combination of object cache and Redis provides enterprise-grade performance.',
    'Why Redis is Perfect for Object Cache',
    'Advantages of Redis over alternatives',
    'publish',
    'open',
    'open',
    '',
    'redis-object-cache-advantages',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);




-- Post 33: Caching without Redis or Memcached mentioned
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1051,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'File-based caching stores cached content in the filesystem. Transient caching uses the WordPress database for temporary data. Varnish caching operates as a reverse proxy. CDN caching distributes content across global edge servers. Browser caching leverages HTTP cache headers. Application-level caching stores computed results. Full-page caching saves complete HTML output. Fragment caching stores reusable page components. Caching strategies should match traffic patterns.',
    'Alternative Caching Approaches',
    'Exploring different caching methods',
    'publish',
    'open',
    'open',
    '',
    'alternative-caching-approaches',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 34: Object-oriented programming (contains "object" but not "object cache")
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1052,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'Object-oriented programming in WordPress follows modern PHP standards. Creating custom objects encapsulates related functionality. Object properties store state while object methods define behavior. WordPress core extensively uses object-oriented design patterns. The WP_Query object handles database queries. The WP_User object represents users. Object inheritance enables code reuse through parent classes. Understanding object scope prevents memory leaks. Object instantiation creates new instances. Object serialization stores complex data structures.',
    'Object-Oriented WordPress Development',
    'Using OOP principles in WordPress',
    'publish',
    'open',
    'open',
    '',
    'object-oriented-wordpress',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- Post 35: All key terms used but in different contexts
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    1053,
    1,
    NOW(),
    UTC_TIMESTAMP(),
    'WordPress performance optimization requires multiple strategies. The object cache improves database performance when properly configured with Redis or Memcached. Performance caching at the page level complements object-level caching. Installing a cache plugin simplifies cache management. Redis provides persistent object cache capabilities. Memcached offers high-performance key-value caching. WordPress cache strategies should match site requirements. Performance monitoring reveals cache effectiveness. Plugin selection impacts both performance and caching efficiency.',
    'Complete WordPress Performance Guide',
    'Comprehensive optimization strategies',
    'publish',
    'open',
    'open',
    '',
    'complete-wordpress-performance',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP(),
    '',
    0,
    '',
    0,
    'post',
    '',
    0
);

-- ============================================================================
-- Assign all posts to 'technical' category
-- WordPress Category Assignment Script
-- ============================================================================

-- Step 1: Create the 'technical' term if it doesn't exist
-- This prevents errors if the category already exists
INSERT INTO wp_terms (name, slug, term_group)
SELECT 'Technical', 'technical', 0
WHERE NOT EXISTS (
    SELECT 1 FROM wp_terms WHERE slug = 'technical'
);

-- Step 2: Create the taxonomy entry for 'technical' as a category
-- Get the term_id that was just created (or already exists)
INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
SELECT 
    t.term_id,
    'category',
    'Technical articles and tutorials',
    0,
    0
FROM wp_terms t
WHERE t.slug = 'technical'
AND NOT EXISTS (
    SELECT 1 
    FROM wp_term_taxonomy tt 
    WHERE tt.term_id = t.term_id 
    AND tt.taxonomy = 'category'
);

-- Step 3: Assign all posts to the 'technical' category
-- This links each post to the technical category
INSERT INTO wp_term_relationships (object_id, term_taxonomy_id, term_order)
SELECT 
    p.ID,
    tt.term_taxonomy_id,
    0
FROM wp_posts p
CROSS JOIN wp_term_taxonomy tt
INNER JOIN wp_terms t ON tt.term_id = t.term_id
WHERE p.ID IN (
    1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1009, 1010,
    1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020,
    1021, 1022, 1023, 1024, 1025, 1026, 1027, 1028, 1029, 1030,
    1031, 1032, 1033, 1034, 1035, 1036, 1037, 1038, 1039, 1040,
    1041, 1042, 1043, 1044, 1045, 1046, 1047, 1051, 1052, 1053
)
AND t.slug = 'technical'
AND tt.taxonomy = 'category'
AND NOT EXISTS (
    SELECT 1 
    FROM wp_term_relationships tr 
    WHERE tr.object_id = p.ID 
    AND tr.term_taxonomy_id = tt.term_taxonomy_id
);

-- Step 4: Update the post count for the technical category
UPDATE wp_term_taxonomy tt
INNER JOIN wp_terms t ON tt.term_id = t.term_id
SET tt.count = (
    SELECT COUNT(DISTINCT tr.object_id)
    FROM wp_term_relationships tr
    INNER JOIN wp_posts p ON tr.object_id = p.ID
    WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
    AND p.post_status = 'publish'
    AND p.post_type = 'post'
)
WHERE t.slug = 'technical'
AND tt.taxonomy = 'category';

-- ============================================================================
-- Verification Query (optional - uncomment to verify)
-- ============================================================================

SELECT 
    p.ID,
    p.post_title,
    t.name as category_name
FROM wp_posts p
INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id
INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
INNER JOIN wp_terms t ON tt.term_id = t.term_id
WHERE p.ID IN (
    1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1009, 1010,
    1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020,
    1021, 1022, 1023, 1024, 1025, 1026, 1027, 1028, 1029, 1030,
    1031, 1032, 1033, 1034, 1035, 1036, 1037, 1038, 1039, 1040,
    1041, 1042, 1043, 1044, 1045, 1046, 1047, 1051, 1052, 1053
)
AND tt.taxonomy = 'category'
ORDER BY p.ID;
