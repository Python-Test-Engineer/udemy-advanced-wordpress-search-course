# SET UP

## 1. INSTALL FROM SCRATCH

Fresh Install

Uncheck media (otional)

Disable comments (optional)

GeneratePress (optional)

The following are all optional:

`PLUGINS\WP-UTILITY\01_CREATE_CHILD_THEME.zip`

`PLUGINS\WP-UTILITY\02_ALL_IN_ONE_32GB.zip`

`PLUGINS\WP-UTILITY\03_PREFIX_CHANGE.zip`

`PLUGINS\WP-UTILITY\04_WIDER_MENU.zip`

`PLUGINS\WP-UTILITY\05_OPENAI_KEYS_REMOVAL.zip`

### Load in posts and plugins

`PLUGINS\WP-EXPORTER\UDEMY-ADV-WP-SEARCH.xml` use WordPress Importer Tool to load in all the posts

### Custom tables

`SQL\ARTICLES\articles.sql` for articles table

`SQL\ARTICLES\mysql-docs-data.sql` for data

`SQL\PRODUCTS\create_wp_products.sql` to create wp_products table

`SQL\PRODUCTS\create_and_insert_wp_products.sql` to load in data

### Course Plugins

We can test each after installing it...

`PLUGINS\ZIPS` - load in all the  course plugins.

### SQL Scripts

#### Articles table

Article table that MySQL docs uses:

`SQL\ARTICLES\articles.sql`

Posts that MySQL docs use for WP Posts examples:

`SQL\ARTICLES\mysql-docs-data.sql`

#### wp_products

`SQL\PRODUCTS\create_wp_products.sql` - create table.

`SQL\PRODUCTS\create_and_insert_wp_products.sql` - insert data.

#### WP Posts index

`SQL\WP-POSTS\index.sql` = create index for WP Posts examples.


## 2. RESTORE FROM WPRESS BACKUP

Using `PLUGINS\WP-UTILITY\02_ALL_IN_ONE_32GB.zip` that has been loaded, import the UEDEMY-ADVANCED-WORDPRESS-SEARCH.wpress file as shown in lesson. It is attached to lesson on restoring this backup.