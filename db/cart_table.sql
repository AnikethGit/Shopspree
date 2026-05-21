-- PrintDepotCo — DB-backed cart migration
-- Run this once on your Hostinger database before deploying.

CREATE TABLE IF NOT EXISTS cart_items (
    id         INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_token CHAR(64)     NOT NULL,
    product_id INT          UNSIGNED NOT NULL,
    quantity   SMALLINT     UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE  KEY uq_cart_product (cart_token, product_id),
    INDEX       idx_cart_token  (cart_token),
    INDEX       idx_updated_at  (updated_at),

    CONSTRAINT fk_cart_items_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: purge stale guest carts older than 60 days (run via a cron job)
-- DELETE FROM cart_items WHERE updated_at < NOW() - INTERVAL 60 DAY;
