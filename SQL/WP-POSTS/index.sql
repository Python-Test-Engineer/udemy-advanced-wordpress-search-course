SELECT ID, post_title,
       (MATCH(post_title) AGAINST ('performance tuning') * 3 +
        MATCH(post_content) AGAINST ('performance tuning')) AS relevance
FROM wp_posts
WHERE MATCH(post_title) AGAINST ('performance tuning') 
   OR MATCH(post_content) AGAINST ('performance tuning')
ORDER BY relevance DESC;