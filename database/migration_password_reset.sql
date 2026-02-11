-- ============================================
-- Nusantara Rental Car - Database Migration
-- Adds Password Reset & Email Verification
-- Compatible with MySQL 5.7+ and MariaDB
-- ============================================

-- Run this SQL in phpMyAdmin or MySQL command line
-- This updates your existing database WITHOUT losing data
-- Safe to run multiple times (idempotent)

USE nusantara_rental_car;

-- ============================================
-- STEP 1: Add Columns (if they don't exist)
-- ============================================

-- Check and add email_verified column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'nusantara_rental_car' 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'email_verified';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE AFTER role',
    'SELECT "Column email_verified already exists" AS Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add verification_token column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'nusantara_rental_car' 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'verification_token';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN verification_token VARCHAR(100) AFTER email_verified',
    'SELECT "Column verification_token already exists" AS Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add reset_token column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'nusantara_rental_car' 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'reset_token';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN reset_token VARCHAR(100) AFTER verification_token',
    'SELECT "Column reset_token already exists" AS Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add reset_token_expires column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'nusantara_rental_car' 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'reset_token_expires';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN reset_token_expires DATETIME AFTER reset_token',
    'SELECT "Column reset_token_expires already exists" AS Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- STEP 2: Add Indexes (if they don't exist)
-- ============================================

-- Check and add idx_email index
SET @idx_exists = 0;
SELECT COUNT(*) INTO @idx_exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'nusantara_rental_car' 
  AND TABLE_NAME = 'users' 
  AND INDEX_NAME = 'idx_email';

SET @sql = IF(@idx_exists = 0, 
    'ALTER TABLE users ADD INDEX idx_email (email)',
    'SELECT "Index idx_email already exists" AS Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add idx_verification_token index
SET @idx_exists = 0;
SELECT COUNT(*) INTO @idx_exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'nusantara_rental_car' 
  AND TABLE_NAME = 'users' 
  AND INDEX_NAME = 'idx_verification_token';

SET @sql = IF(@idx_exists = 0, 
    'ALTER TABLE users ADD INDEX idx_verification_token (verification_token)',
    'SELECT "Index idx_verification_token already exists" AS Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add idx_reset_token index
SET @idx_exists = 0;
SELECT COUNT(*) INTO @idx_exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'nusantara_rental_car' 
  AND TABLE_NAME = 'users' 
  AND INDEX_NAME = 'idx_reset_token';

SET @sql = IF(@idx_exists = 0, 
    'ALTER TABLE users ADD INDEX idx_reset_token (reset_token)',
    'SELECT "Index idx_reset_token already exists" AS Info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- STEP 3: Verification (Show Results)
-- ============================================

SELECT '✓ Migration completed successfully!' AS Status;
SELECT '' AS '';
SELECT 'New columns added to users table:' AS Info;

-- Show new columns
SELECT 
    COLUMN_NAME AS 'Column Name', 
    DATA_TYPE AS 'Data Type',
    IS_NULLABLE AS 'Nullable',
    COLUMN_DEFAULT AS 'Default Value'
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'nusantara_rental_car' 
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME IN ('email_verified', 'verification_token', 'reset_token', 'reset_token_expires')
ORDER BY ORDINAL_POSITION;

SELECT '' AS '';
SELECT 'New indexes added to users table:' AS Info;

-- Show new indexes
SELECT 
    INDEX_NAME AS 'Index Name',
    COLUMN_NAME AS 'Column',
    NON_UNIQUE AS 'Non-Unique'
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'nusantara_rental_car' 
  AND TABLE_NAME = 'users' 
  AND INDEX_NAME IN ('idx_email', 'idx_verification_token', 'idx_reset_token')
ORDER BY INDEX_NAME;

-- ============================================
-- OPTIONAL: Update Admin Password
-- ============================================
-- Uncomment the line below to update admin password from 'admin123' to 'Admin@2024!'
-- UPDATE users SET password = '$2y$10$xQZ7YvH8K5WJN.9hGzHqBuqK5rDp0L2WyXqxFGZvN4EhJ8K5WJN.9' WHERE email = 'admin@nusantararental.com';
