-- =============================================================================
-- PrintDepotCo — Complete Database Schema
-- Server: MariaDB 11.x
-- Charset: utf8mb4 / utf8mb4_unicode_ci
--
-- HOW TO USE:
--   FRESH INSTALL  → Run Section 1 (everything up to "MIGRATION QUERIES").
--   EXISTING DB    → Skip to Section 2 at the bottom and run those ALTER
--                    TABLE statements on the live Hostinger database.
-- =============================================================================

SET SQL_MODE   = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone  = "+00:00";
SET NAMES utf8mb4;
START TRANSACTION;


-- =============================================================================
-- SECTION 1 — FRESH INSTALL
-- Drop order respects FK dependencies (children first, then parents).
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `cart`;          -- old session-cart table, replaced by cart_items
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `blog`;
DROP VIEW  IF EXISTS `v_active_products`;
DROP VIEW  IF EXISTS `v_order_summary`;

SET FOREIGN_KEY_CHECKS = 1;


-- ─────────────────────────────────────────────────────────────────────────────
-- categories
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE `categories` (
  `id`                 int(11)       NOT NULL AUTO_INCREMENT,
  `name`               varchar(255)  NOT NULL,
  `slug`               varchar(255)  NOT NULL,
  `description`        text          DEFAULT NULL,
  `image_url`          varchar(500)  DEFAULT NULL,
  `parent_category_id` int(11)       DEFAULT NULL,
  `is_active`          tinyint(1)    DEFAULT 1,
  `display_order`      int(11)       DEFAULT 0,
  `created_at`         timestamp     NULL DEFAULT current_timestamp(),
  `updated_at`         timestamp     NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_name` (`name`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_slug`              (`slug`),
  KEY `idx_active`            (`is_active`),
  KEY `parent_category_id`    (`parent_category_id`),

  CONSTRAINT `categories_ibfk_1`
    FOREIGN KEY (`parent_category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `is_active`, `display_order`) VALUES
(1, 'Electronics',     'electronics',    'Electronic products and devices',       1, 1),
(2, 'Appliances',      'appliances',     'Home and kitchen appliances',           1, 2),
(3, 'Hardware',        'hardware',       'Tools and hardware supplies',           1, 3),
(4, 'Software',        'software',       'Software licenses and programs',        1, 4),
(5, 'Furniture',       'furniture',      'Office and home furniture',             1, 5),
(6, 'Office Supplies', 'office-supplies','Office supplies and stationery',        1, 6),
(7, 'Clothing',        'clothing',       'Apparel and fashion items',             1, 7),
(8, 'Home Decor',      'home-decor',     'Decorative items and accessories',      1, 8);


-- ─────────────────────────────────────────────────────────────────────────────
-- users
-- phone is nullable here because registration allows an optional phone number.
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE `users` (
  `id`          int(11)                       NOT NULL AUTO_INCREMENT,
  `email`       varchar(255)                  NOT NULL,
  `password`    varchar(255)                  NOT NULL,
  `full_name`   varchar(255)                  NOT NULL,
  `phone`       varchar(20)                   DEFAULT NULL,
  `address`     text                          DEFAULT NULL,
  `city`        varchar(100)                  DEFAULT NULL,
  `state`       varchar(100)                  DEFAULT NULL,
  `postal_code` varchar(20)                   DEFAULT NULL,
  `country`     varchar(100)                  DEFAULT 'USA',
  `user_type`   enum('customer','admin')      DEFAULT 'customer',
  `is_active`   tinyint(1)                    DEFAULT 1,
  `created_at`  timestamp                     NULL DEFAULT current_timestamp(),
  `updated_at`  timestamp                     NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email`       (`email`),
  KEY `idx_email`             (`email`),
  KEY `idx_user_type`         (`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin account (password: Admin@123 — CHANGE ON FIRST LOGIN)
INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `phone`, `user_type`, `is_active`) VALUES
(1, 'admin@distributors.com',
    '$2a$12$zdRq3B4PFMD0zfOaNOReae1KvC1SOz4fSpHvyTRvSwbXPP9I5wuBi',
    'Admin User', '+1-800-000-0000', 'admin', 1);


-- ─────────────────────────────────────────────────────────────────────────────
-- products
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE `products` (
  `id`                int(11)       NOT NULL AUTO_INCREMENT,
  `category_id`       int(11)       NOT NULL,
  `name`              varchar(500)  NOT NULL,
  `slug`              varchar(500)  NOT NULL,
  `description`       longtext      DEFAULT NULL,
  `short_description` varchar(255)  DEFAULT NULL,
  `price`             decimal(10,2) NOT NULL,
  `quantity`          int(11)       DEFAULT 0,
  `sku`               varchar(100)  DEFAULT NULL,
  `image_url`         varchar(500)  DEFAULT NULL,
  `gallery_images`    longtext      DEFAULT NULL,
  `is_active`         tinyint(1)    DEFAULT 1,
  `featured`          tinyint(1)    DEFAULT 0,
  `created_at`        timestamp     NULL DEFAULT current_timestamp(),
  `updated_at`        timestamp     NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug`            (`slug`),
  UNIQUE KEY `uq_sku`             (`sku`),
  KEY `idx_category`              (`category_id`),
  KEY `idx_slug`                  (`slug`),
  KEY `idx_active`                (`is_active`),
  KEY `idx_featured`              (`featured`),
  KEY `idx_price_range`           (`price`, `is_active`),
  KEY `idx_category_featured`     (`category_id`, `featured`, `is_active`),
  FULLTEXT KEY `ft_search`        (`name`, `description`),

  CONSTRAINT `products_ibfk_1`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────────
-- cart_items  (DB-backed cart — keyed by cart_token stored in session/cookie)
-- Replaces the old `cart` table which used user_id + session_id and could not
-- support guest carts without a users FK conflict.
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE `cart_items` (
  `id`          int(11)  NOT NULL AUTO_INCREMENT,
  `cart_token`  char(64) NOT NULL,
  `product_id`  int(11)  NOT NULL,
  `quantity`    smallint UNSIGNED NOT NULL DEFAULT 1,
  `created_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_product`  (`cart_token`, `product_id`),
  KEY `idx_cart_token`          (`cart_token`),
  KEY `idx_updated_at`          (`updated_at`),

  CONSTRAINT `fk_cart_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional maintenance: purge stale guest carts older than 60 days
-- DELETE FROM cart_items WHERE updated_at < NOW() - INTERVAL 60 DAY;


-- ─────────────────────────────────────────────────────────────────────────────
-- orders
--
-- Fixes vs original schema:
--   + customer_name    VARCHAR(255) — guest name captured at checkout
--   + payment_status   VARCHAR(50)  — 'Pending'|'Completed'|'Failed'|'Refunded'
--   + transaction_id   VARCHAR(255) — reference ID from payment gateway
--   + payment_details  JSON         — raw gateway response (card last4, etc.)
--   ~ payment_method   enum: added 'Klarna' (used in checkout.php)
--   ~ order_status     enum: added 'Payment Received' (set by orders/create.php
--                      for non-COD payments)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE `orders` (
  `id`                   int(11)       NOT NULL AUTO_INCREMENT,
  `order_id`             varchar(50)   NOT NULL,
  `user_id`              int(11)       DEFAULT NULL,
  `customer_name`        varchar(255)  DEFAULT NULL,
  `email`                varchar(255)  NOT NULL,
  `phone`                varchar(20)   NOT NULL,
  `shipping_address`     text          NOT NULL,
  `shipping_city`        varchar(100)  DEFAULT NULL,
  `shipping_state`       varchar(100)  DEFAULT NULL,
  `shipping_postal_code` varchar(20)   DEFAULT NULL,
  `shipping_country`     varchar(100)  DEFAULT 'USA',
  `total_amount`         decimal(12,2) NOT NULL,
  `payment_method`       enum(
                           'COD',
                           'Credit Card',
                           'Debit Card',
                           'PayPal',
                           'Bank Transfer',
                           'Klarna'
                         ) DEFAULT 'COD',
  `payment_status`       varchar(50)   DEFAULT 'Pending',
  `transaction_id`       varchar(255)  DEFAULT NULL,
  `payment_details`      json          DEFAULT NULL,
  `order_status`         enum(
                           'Pending',
                           'Payment Received',
                           'Processing',
                           'Shipped',
                           'Delivered',
                           'Cancelled'
                         ) DEFAULT 'Pending',
  `notes`                text          DEFAULT NULL,
  `card_last4`           varchar(4)    DEFAULT NULL,
  `created_at`           timestamp     NULL DEFAULT current_timestamp(),
  `updated_at`           timestamp     NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_id`          (`order_id`),
  KEY `idx_order_id`                (`order_id`),
  KEY `idx_user`                    (`user_id`),
  KEY `idx_email`                   (`email`),
  KEY `idx_status`                  (`order_status`),
  KEY `idx_created`                 (`created_at`),
  KEY `idx_order_date_range`        (`created_at`, `order_status`),
  KEY `idx_customer_lookup`         (`email`, `user_id`),

  CONSTRAINT `orders_ibfk_1`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────────
-- order_items  (order_id references orders.id — the AUTO_INCREMENT PK)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE `order_items` (
  `id`           int(11)       NOT NULL AUTO_INCREMENT,
  `order_id`     int(11)       NOT NULL,
  `product_id`   int(11)       NOT NULL,
  `product_name` varchar(500)  NOT NULL,
  `quantity`     int(11)       NOT NULL,
  `price`        decimal(10,2) NOT NULL,
  `subtotal`     decimal(12,2) NOT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_order`     (`order_id`),
  KEY `product_id`    (`product_id`),

  CONSTRAINT `order_items_ibfk_1`
    FOREIGN KEY (`order_id`)   REFERENCES `orders`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────────
-- contacts  (used by contact_handler.php)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE `contacts` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `name`       varchar(255) NOT NULL,
  `email`      varchar(255) NOT NULL,
  `phone`      varchar(20)  DEFAULT NULL,
  `subject`    varchar(255) NOT NULL,
  `message`    text         NOT NULL,
  `status`     varchar(20)  NOT NULL DEFAULT 'New',
  `created_at` timestamp    NULL DEFAULT current_timestamp(),
  `replied_at` timestamp    NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_email`      (`email`),
  KEY `idx_status`     (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────────
-- contact_messages  (legacy table — not used by current code, kept for safety)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE `contact_messages` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `name`       varchar(255) NOT NULL,
  `email`      varchar(255) NOT NULL,
  `phone`      varchar(20)  DEFAULT NULL,
  `subject`    varchar(500) NOT NULL,
  `message`    longtext     NOT NULL,
  `is_read`    tinyint(1)   DEFAULT 0,
  `created_at` timestamp    NULL DEFAULT current_timestamp(),

  PRIMARY KEY (`id`),
  KEY `idx_email`   (`email`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────────
-- blog
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE `blog` (
  `id`           int(11)      NOT NULL AUTO_INCREMENT,
  `title`        varchar(500) NOT NULL,
  `slug`         varchar(500) NOT NULL,
  `content`      longtext     NOT NULL,
  `excerpt`      varchar(500) DEFAULT NULL,
  `image_url`    varchar(500) DEFAULT NULL,
  `author`       varchar(255) DEFAULT 'Admin',
  `is_published` tinyint(1)   DEFAULT 1,
  `created_at`   timestamp    NULL DEFAULT current_timestamp(),
  `updated_at`   timestamp    NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug`          (`slug`),
  KEY `idx_slug`                (`slug`),
  KEY `idx_published`           (`is_published`),
  KEY `idx_created`             (`created_at`),
  KEY `idx_published_date`      (`is_published`, `created_at`),
  FULLTEXT KEY `ft_search`      (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────────
-- Views
-- ─────────────────────────────────────────────────────────────────────────────
CREATE OR REPLACE VIEW `v_active_products` AS
  SELECT
    p.id,  p.name,        p.slug,          p.price,
    p.image_url,          p.quantity,      p.featured,      p.sku,
    c.id   AS category_id,
    c.name AS category_name,
    c.slug AS category_slug
  FROM products p
  JOIN categories c ON p.category_id = c.id
  WHERE p.is_active = 1 AND c.is_active = 1;

CREATE OR REPLACE VIEW `v_order_summary` AS
  SELECT
    o.id,           o.order_id,       o.customer_name,
    o.email,        o.total_amount,   o.payment_method,
    o.payment_status, o.order_status, o.created_at,
    COUNT(oi.id)          AS item_count,
    COALESCE(SUM(oi.quantity), 0) AS total_quantity
  FROM orders o
  LEFT JOIN order_items oi ON o.id = oi.order_id
  GROUP BY o.id;


COMMIT;


-- =============================================================================
-- SECTION 2 — MIGRATION QUERIES
-- Run these on the EXISTING Hostinger database.
-- Safe to run multiple times (all use IF NOT EXISTS / IF EXISTS guards).
-- =============================================================================

-- ── 1. Create cart_items (new token-based cart) ───────────────────────────────
CREATE TABLE IF NOT EXISTS `cart_items` (
  `id`          int(11)  NOT NULL AUTO_INCREMENT,
  `cart_token`  char(64) NOT NULL,
  `product_id`  int(11)  NOT NULL,
  `quantity`    smallint UNSIGNED NOT NULL DEFAULT 1,
  `created_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_product` (`cart_token`, `product_id`),
  KEY `idx_cart_token`         (`cart_token`),
  KEY `idx_updated_at`         (`updated_at`),

  CONSTRAINT `fk_cart_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2. Add missing columns to orders ─────────────────────────────────────────

-- customer_name: guest checkout name, not stored previously
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `customer_name`   varchar(255) DEFAULT NULL AFTER `user_id`;

-- payment_status: 'Pending' | 'Completed' | 'Failed' | 'Refunded'
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `payment_status`  varchar(50)  DEFAULT 'Pending' AFTER `total_amount`;

-- transaction_id: reference from payment gateway / dummy flow
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `transaction_id`  varchar(255) DEFAULT NULL AFTER `payment_status`;

-- payment_details: JSON blob of gateway response or card info
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `payment_details` json         DEFAULT NULL AFTER `transaction_id`;


-- ── 3. Fix payment_method enum — add Klarna ───────────────────────────────────
-- Check current definition before running; this replaces the enum in-place.
ALTER TABLE `orders`
  MODIFY COLUMN `payment_method` enum(
    'COD',
    'Credit Card',
    'Debit Card',
    'PayPal',
    'Bank Transfer',
    'Klarna'
  ) DEFAULT 'COD';


-- ── 4. Fix order_status enum — add 'Payment Received' ────────────────────────
-- orders/create.php sets status to 'Payment Received' for non-COD orders.
-- Without this the INSERT silently stores an empty string on strict mode.
ALTER TABLE `orders`
  MODIFY COLUMN `order_status` enum(
    'Pending',
    'Payment Received',
    'Processing',
    'Shipped',
    'Delivered',
    'Cancelled'
  ) DEFAULT 'Pending';


-- ── 5. Make users.phone nullable (registration makes phone optional) ──────────
ALTER TABLE `users`
  MODIFY COLUMN `phone` varchar(20) DEFAULT NULL;


-- ── 6. Update v_order_summary view to include new columns ─────────────────────
CREATE OR REPLACE VIEW `v_order_summary` AS
  SELECT
    o.id,             o.order_id,       o.customer_name,
    o.email,          o.total_amount,   o.payment_method,
    o.payment_status, o.order_status,   o.created_at,
    COUNT(oi.id)                  AS item_count,
    COALESCE(SUM(oi.quantity), 0) AS total_quantity
  FROM orders o
  LEFT JOIN order_items oi ON o.id = oi.order_id
  GROUP BY o.id;


-- ── Done ──────────────────────────────────────────────────────────────────────
-- Verify with:
--   SHOW COLUMNS FROM orders;
--   SHOW COLUMNS FROM cart_items;
--   SHOW CREATE TABLE orders\G
