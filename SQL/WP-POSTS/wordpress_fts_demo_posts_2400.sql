-- ============================================================================
-- WordPress FTS Demo Data - 2400 Series Posts
-- INSERT statements only, starting at ID 2400
-- ============================================================================

-- Post 2400: Heavy focus on caching (will rank high for "wordpress performance caching")
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2400, 
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
    'wordpress-performance-caching-guide-2400',
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

-- Post 2401: Focus on performance tuning (good for title ranking example)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2401,
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
    'performance-tuning-wordpress-2401',
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

-- Post 2402: Long-form content about object cache and Redis
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2402,
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
    'object-cache-redis-guide-2402',
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

-- Post 2403: Contains stopwords - demonstrates they're ignored
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2403,
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
    'how-to-speed-up-wordpress-2403',
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

-- Post 2404: About WordPress cache WITHOUT plugins (for exclusion example)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2404,
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
    'server-level-wordpress-cache-2404',
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

-- Post 2405: WordPress caching PLUGIN (will be excluded by -plugin search)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2405,
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
    'wordpress-caching-plugins-2405',
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

-- Post 2406: Multiple variations of "optimize" (for prefix matching)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2406,
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
    'database-optimization-techniques-2406',
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

-- Post 2407: Contains exact phrase "object cache" (for phrase matching)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2407,
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
    'understanding-object-cache-2407',
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

-- Post 2408: Object cache with Redis, NOT Memcached (for combined boolean example)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2408,
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
    'redis-object-cache-advantages-2408',
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

-- Post 2409: About cache and Redis (for tilde relevance boosting)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2409,
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
    'advanced-cache-redis-strategies-2409',
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

-- Post 2410: Just about cache (for tilde example - Redis optional)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2410,
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
    'cache-fundamentals-wordpress-2410',
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

-- Post 2411: SEO content with related terms (for query expansion)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2411,
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
    'search-engine-optimization-guide-2411',
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

-- Post 2412: More SEO-related content using synonym terms
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2412,
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
    'on-page-optimization-checklist-2412',
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

-- Post 2413: WordPress security with related terms (for query expansion)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2413,
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
    'wordpress-security-practices-2413',
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

-- Post 2414: More security content with related vocabulary
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2414,
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
    'advanced-security-hardening-2414',
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

-- Post 2415: Speed-related content with synonyms (for query expansion)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2415,
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
    'mastering-website-performance-2415',
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

-- Post 2416: Hosting with related terms (for query expansion)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2416,
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
    'choosing-wordpress-hosting-2416',
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

-- Post 2417: More hosting content with related terms
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2417,
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
    'comparing-hosting-options-2417',
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

-- Post 2418: General WordPress post (lower relevance for most searches)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2418,
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
    'introduction-to-wordpress-2418',
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

-- Post 2419: About themes (different topic - provides variety)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
VALUES (
    2419,
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
    'guide-to-wordpress-themes-2419',
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

COMMIT;
