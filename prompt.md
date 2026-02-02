For PLUGINS\DEV\wp-bm25-calc\wp-bm25-calc.php

In my WordPress MySQL database I have a `wp_products` table with structure:

CREATE TABLE `wp_products` (
  `id` int NOT NULL,
  `product_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_short_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expanded_description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

To this plugin add buttons for:

DELETE ALL FTS INDEXES

ADD FTS index on product_name

ADD FTS index on product_short_description

When `ADD FTS index on product_name` has been done, populate the Documents text box with all the `product_name` each on a new line and ensure that the search button uses all these as it did before

DO the same `ADD FTS index on product_short_description` but use `product_short_description` items