-- Payment System Database Migration
-- Add payment-related columns to orders table
-- Run this SQL to update your database

-- Check if columns exist before adding them
-- Using ALTER TABLE to add new columns for payment tracking

ALTER TABLE orders ADD COLUMN `payment_status` VARCHAR(50) DEFAULT 'Pending' COMMENT 'Payment status: Pending, Completed, Failed, Refunded';
ALTER TABLE orders ADD COLUMN `transaction_id` VARCHAR(100) UNIQUE COMMENT 'Transaction ID from payment gateway/system';
ALTER TABLE orders ADD COLUMN `payment_details` LONGTEXT COMMENT 'JSON data of payment details (masked card/account info)';

-- Create indexes for better query performance
ALTER TABLE orders ADD INDEX `idx_payment_status` (`payment_status`);
ALTER TABLE orders ADD INDEX `idx_transaction_id` (`transaction_id`);

-- Add a payments history table for audit trail
CREATE TABLE IF NOT EXISTS `payment_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `transaction_id` VARCHAR(100),
    `payment_method` VARCHAR(50),
    `amount` DECIMAL(10, 2),
    `payment_status` VARCHAR(50),
    `status_message` TEXT,
    `payment_response` LONGTEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_transaction_id` (`transaction_id`),
    INDEX `idx_payment_status` (`payment_status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Add refund tracking table
CREATE TABLE IF NOT EXISTS `refunds` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `refund_amount` DECIMAL(10, 2),
    `reason` TEXT,
    `refund_status` VARCHAR(50) DEFAULT 'Pending',
    `refund_date` TIMESTAMP,
    `processed_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_refund_status` (`refund_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Display success message
SELECT 'Payment system tables successfully created/updated!' as status;