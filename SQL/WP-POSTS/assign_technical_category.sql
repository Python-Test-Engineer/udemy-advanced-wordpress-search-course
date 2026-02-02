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
-- WHERE p.ID IN (
--     1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1009, 1010,
--     1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020,
--     1021, 1022, 1023, 1024, 1025, 1026, 1027, 1028, 1029, 1030,
--     1031, 1032, 1033, 1034, 1035, 1036, 1037, 1038, 1039, 1040,
--     1041, 1042, 1043, 1044, 1045, 1046, 1047, 1051, 1052, 1053
-- )
WHERE p.ID > 2399
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

-- SELECT 
--     p.ID,
--     p.post_title,
--     t.name as category_name
-- FROM wp_posts p
-- INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id
-- INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
-- INNER JOIN wp_terms t ON tt.term_id = t.term_id
-- WHERE p.ID IN (
--     1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1009, 1010,
--     1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020,
--     1021, 1022, 1023, 1024, 1025, 1026, 1027, 1028, 1029, 1030,
--     1031, 1032, 1033, 1034, 1035, 1036, 1037, 1038, 1039, 1040,
--     1041, 1042, 1043, 1044, 1045, 1046, 1047, 1051, 1052, 1053
-- )
-- AND tt.taxonomy = 'category'
-- ORDER BY p.ID;
