-- ========================================
-- MySQL Script: Delete All WordPress Posts
-- ========================================
-- WARNING: This will permanently delete ALL posts and associated data
-- BACKUP YOUR DATABASE BEFORE RUNNING!
-- 
-- Usage: mysql -u username -p database_name < delete-wordpress-posts.sql
-- Or run via phpMyAdmin or MySQL Workbench
-- ========================================

-- Set variables for table prefix (change if different)
SET @prefix = 'wp_';

-- Start transaction for safety
START TRANSACTION;

-- ========================================
-- Step 1: Delete Comment Meta
-- ========================================
DELETE cm FROM wp_commentmeta cm
INNER JOIN wp_comments c ON cm.comment_id = c.comment_ID
INNER JOIN wp_posts p ON c.comment_post_ID = p.ID
WHERE p.post_type NOT IN ('revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block');

-- ========================================
-- Step 2: Delete Comments
-- ========================================
DELETE c FROM wp_comments c
INNER JOIN wp_posts p ON c.comment_post_ID = p.ID
WHERE p.post_type NOT IN ('revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block');

-- ========================================
-- Step 3: Delete Post Meta
-- ========================================
DELETE pm FROM wp_postmeta pm
INNER JOIN wp_posts p ON pm.post_id = p.ID
WHERE p.post_type NOT IN ('revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block');

-- ========================================
-- Step 4: Delete Term Relationships
-- ========================================
DELETE tr FROM wp_term_relationships tr
INNER JOIN wp_posts p ON tr.object_id = p.ID
WHERE p.post_type NOT IN ('revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block');

-- ========================================
-- Step 5: Delete Post Revisions
-- ========================================
DELETE FROM wp_posts
WHERE post_type = 'revision'
AND post_parent IN (
    SELECT ID FROM (
        SELECT ID FROM wp_posts 
        WHERE post_type NOT IN ('revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block')
    ) AS temp
);

-- ========================================
-- Step 6: Delete from wp_links (blogroll links associated with posts)
-- ========================================
-- Note: wp_links table might not exist in newer WordPress versions
-- DELETE FROM wp_links WHERE link_id IN (...); -- Uncomment if you have links tied to posts

-- ========================================
-- Step 7: Delete Attachment Metadata (for media files)
-- ========================================
DELETE FROM wp_postmeta
WHERE post_id IN (
    SELECT ID FROM wp_posts WHERE post_type = 'attachment'
);

-- ========================================
-- Step 8: Clean up orphaned term relationships
-- ========================================
-- After deleting posts, clean up any orphaned taxonomy terms
DELETE FROM wp_term_relationships
WHERE object_id NOT IN (SELECT ID FROM wp_posts);

-- ========================================
-- Step 9: Update term counts
-- ========================================
-- Recalculate term counts for taxonomies
UPDATE wp_term_taxonomy tt
SET count = (
    SELECT COUNT(*) 
    FROM wp_term_relationships tr 
    WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
);

-- ========================================
-- Step 10: Delete the actual posts
-- ========================================
DELETE FROM wp_posts
WHERE post_type IN ('post', 'page', 'attachment')
OR post_type NOT IN ('revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block');

-- Alternative: If you want to delete EVERYTHING including nav menus, custom CSS, etc:
-- DELETE FROM wp_posts;

-- ========================================
-- Step 11: Clean up orphaned metadata
-- ========================================
-- Remove any orphaned postmeta (shouldn't exist after above, but just in case)
DELETE FROM wp_postmeta
WHERE post_id NOT IN (SELECT ID FROM wp_posts);

-- Remove any orphaned comments
DELETE FROM wp_comments
WHERE comment_post_ID NOT IN (SELECT ID FROM wp_posts);

-- Remove any orphaned comment meta
DELETE FROM wp_commentmeta
WHERE comment_id NOT IN (SELECT comment_ID FROM wp_comments);

-- ========================================
-- Step 12: Reset Auto Increment (Optional)
-- ========================================
-- Uncomment these if you want to reset the ID counters
-- ALTER TABLE wp_posts AUTO_INCREMENT = 1;
-- ALTER TABLE wp_postmeta AUTO_INCREMENT = 1;
-- ALTER TABLE wp_comments AUTO_INCREMENT = 1;
-- ALTER TABLE wp_commentmeta AUTO_INCREMENT = 1;

-- ========================================
-- Commit the transaction
-- ========================================
-- Review the changes, then either:
COMMIT;   -- To save all changes
-- ROLLBACK; -- To undo all changes (uncomment this line and comment COMMIT if you want to test first)

-- ========================================
-- Verification Queries
-- ========================================
-- Run these after the script to verify deletion:

-- SELECT COUNT(*) as remaining_posts FROM wp_posts WHERE post_type IN ('post', 'page', 'attachment');
-- SELECT COUNT(*) as remaining_postmeta FROM wp_postmeta;
-- SELECT COUNT(*) as remaining_comments FROM wp_comments;
-- SELECT COUNT(*) as remaining_term_relationships FROM wp_term_relationships;

-- ========================================
-- Summary Report
-- ========================================
SELECT 
    'Posts' as table_name, 
    COUNT(*) as remaining_count 
FROM wp_posts 
WHERE post_type IN ('post', 'page', 'attachment')

UNION ALL

SELECT 
    'Post Meta' as table_name, 
    COUNT(*) as remaining_count 
FROM wp_postmeta

UNION ALL

SELECT 
    'Comments' as table_name, 
    COUNT(*) as remaining_count 
FROM wp_comments

UNION ALL

SELECT 
    'Comment Meta' as table_name, 
    COUNT(*) as remaining_count 
FROM wp_commentmeta

UNION ALL

SELECT 
    'Term Relationships' as table_name, 
    COUNT(*) as remaining_count 
FROM wp_term_relationships;
