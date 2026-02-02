-- Create the wp_products table with auto-increment ID and GUID product_id
CREATE TABLE IF NOT EXISTS wp_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id CHAR(36) NOT NULL UNIQUE,
    product_name VARCHAR(255) NOT NULL,
    product_short_description VARCHAR(500),
    expanded_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_id (product_id),
    INDEX idx_product_name (product_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;