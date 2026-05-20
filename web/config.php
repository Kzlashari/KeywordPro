<?php
/**
 * Configuration File for SEO Keyword Tool
 * Handles DB connection via PDO and defensive security sanitization.
 */

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate basic CSRF token if empty
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database Credentials (Customize for your hosting environment)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'seo_keyword_tool');

try {
    // Attempt local connection
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pwd = ""; // change if necessary
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Create database and tables automatically if connection succeeds
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    
    // Create history table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `search_history` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `keyword` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create trending table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `trending_keywords` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `keyword` VARCHAR(255) NOT NULL,
        `search_count` INT DEFAULT 1,
        `last_searched` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    $dbConnected = true;
} catch (PDOException $e) {
    // Graceful fallback for environments where MySQL is not configured yet
    $dbConnected = false;
    $dbError = $e->getMessage();
}

/**
 * XSS Attack prevention helper
 */
function sanitize($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate simple CSRF
 */
function validate_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
