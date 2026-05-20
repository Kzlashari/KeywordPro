-- Database installation script for SEO Keyword Suggestion Web App

CREATE DATABASE IF NOT EXISTS `seo_keyword_tool` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `seo_keyword_tool`;

-- History table
CREATE TABLE IF NOT EXISTS `search_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `keyword` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trending keywords table (for bonus feature)
CREATE TABLE IF NOT EXISTS `trending_keywords` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `keyword` VARCHAR(255) NOT NULL,
    `search_count` INT DEFAULT 1,
    `last_searched` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed initial trending data
INSERT INTO `trending_keywords` (`keyword`, `search_count`) VALUES
('digital marketing tips', 120),
('learn SEO in 2026', 95),
('AI keyword research', 150),
('affiliate marketing tools', 85),
('best blogging platforms', 74)
ON DUPLICATE KEY UPDATE `search_count` = `search_count`;
