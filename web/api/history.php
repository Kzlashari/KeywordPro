<?php
/**
 * Real history fetcher API for local search history UI
 */
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config.php';

$history = [];
$trending = [];

if (isset($dbConnected) && $dbConnected && isset($pdo)) {
    try {
        // Fetch 10 most recent searches
        $stmt = $pdo->query("SELECT DISTINCT `keyword`, `created_at` FROM `search_history` ORDER BY `id` DESC LIMIT 10");
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch top 5 trending
        $stmtT = $pdo->query("SELECT `keyword`, `search_count` FROM `trending_keywords` ORDER BY `search_count` DESC LIMIT 5");
        $trending = $stmtT->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback gracefully
    }
}

// Fallback suggestions if MySQL not configured or empty
if (empty($history)) {
    $history = [
        ['keyword' => 'seo tools', 'created_at' => date('Y-m-d H:i:s')],
        ['keyword' => 'digital marketing', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
        ['keyword' => 'blog name generator', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]
    ];
}

if (empty($trending)) {
    $trending = [
        ['keyword' => 'artificial intelligence seo', 'search_count' => 140],
        ['keyword' => 'long tail keyword tips', 'search_count' => 110],
        ['keyword' => 'blog topics 2026', 'search_count' => 95]
    ];
}

echo json_encode([
    'success' => true,
    'history' => $history,
    'trending' => $trending,
    'mysql_connected' => $dbConnected,
    'mysql_error' => isset($dbError) ? $dbError : null
]);
?>
