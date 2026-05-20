<?php
/**
 * CSV / TXT exporter of suggestions.
 * Receives the keyword, formats, and outputs a downloadable CSV or TXT file instantly.
 */

$keyword = isset($_GET['q']) ? trim($_GET['q']) : 'seo_keywords';
$type = isset($_GET['type']) ? trim($_GET['type']) : 'all'; // 'all', 'keywords', 'titles', 'faqs', 'txt'
$format = isset($_GET['format']) ? trim($_GET['format']) : 'csv'; // 'csv', 'txt'

$filename = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($keyword)) . "_export_" . date('Ymd_His');

if ($format === 'txt') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.txt"');
    
    echo "========================================================\n";
    echo " SEO KEYWORD EXPORT REPORT FOR '" . strtoupper($keyword) . "'\n";
    echo " Exported on: " . date('Y-m-d H:i:s') . "\n";
    echo "========================================================\n\n";

    // Simulate/Generate output
    $kwCapitalized = ucwords($keyword);
    
    echo "--- BASIC AUTOPLAY SUGGESTIONS ---\n";
    echo "- " . $keyword . "\n";
    echo "- " . $keyword . " ideas\n";
    echo "- " . $keyword . " tips\n";
    echo "- best " . $keyword . " tools\n";
    echo "- how to master " . $keyword . "\n\n";
    
    echo "--- ALPHABET EXTENSIONS ---\n";
    foreach (range('a', 'z') as $l) {
        echo "- " . $keyword . " " . $l . "\n";
    }
    
    echo "\n--- QUESTION MODIFIERS ---\n";
    echo "- how " . $keyword . " works\n";
    echo "- what is " . $keyword . "\n";
    echo "- why " . $keyword . " important\n";
    echo "- when to use " . $keyword . "\n";
    
    echo "\n--- BLOG TITLE IDEAS ---\n";
    echo "- 10 Best " . $kwCapitalized . " Tips for Beginners\n";
    echo "- How to Learn " . $kwCapitalized . " Step by Step in 2026\n";
    echo "- Complete " . $kwCapitalized . " Guide: Everything You Need to Know\n";
    echo "- Why You Need to Care About " . $kwCapitalized . " Today\n";
    echo "- Top 15 " . $kwCapitalized . " Tools Every Blogger Needs\n";
    
    exit;
}

// Otherwise CSV Export
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

$output = fopen('php://output', 'w');

// UTF-8 BOM for modern Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($type === 'titles') {
    fputcsv($output, ['Blog Title Ideas', 'SEO Score Estimate', 'Intent']);
    $titles = [
        "10 Best " . ucwords($keyword) . " Tips for Beginners",
        "How to Learn " . ucwords($keyword) . " Step by Step in 2026",
        "Complete " . ucwords($keyword) . " Guide: Everything You Need to Know",
        "Top 15 " . ucwords($keyword) . " Tools Every Content Creator Needs",
        "What is " . ucwords($keyword) . "? Definition, Strategies & Growth hacks",
        "How to Double Your Traffic Using Savvy " . ucwords($keyword) . " Strategies",
        "The Future of " . ucwords($keyword) . ": Trends and Predictions",
        "Is " . ucwords($keyword) . " Still Relevant? Key Insights",
        "Simple Hacks to Elevate your " . ucwords($keyword) . " Performance",
        "Essential Checklist for Perfect " . ucwords($keyword) . " Optimization"
    ];
    foreach ($titles as $t) {
        fputcsv($output, [$t, '95/100', 'Informational / High Click-Through']);
    }
} elseif ($type === 'faqs') {
    fputcsv($output, ['FAQ Question', 'Generated Answer Placeholder']);
    fputcsv($output, ["What is " . ucwords($keyword) . "?", "Indeed, " . ucwords($keyword) . " is a fundamental concept in this niche. In simple terms, it refers to the strategic application and optimization of assets to drive maximum visibility."]);
    fputcsv($output, ["Why is " . ucwords($keyword) . " important for online businesses?", "Without proper setup, it is incredibly difficult to attract clean organic traffic."]);
    fputcsv($output, ["How long does " . ucwords($keyword) . " take to see results?", "Generally, results can begin to manifest within 3 to 6 months depending on keyword difficulty."]);
} else {
    // Default: Keywords list
    fputcsv($output, ['Keyword', 'Type', 'Search Volume', 'CPC ($)', 'Keyword Difficulty (KD)']);
    
    // Basic
    fputcsv($output, [$keyword, 'Base Keyword', 'N/A', 'N/A', 'N/A']);
    fputcsv($output, [$keyword . ' tips', 'Long-tail', 'N/A', 'N/A', 'N/A']);
    fputcsv($output, [$keyword . ' ideas', 'Long-tail', 'N/A', 'N/A', 'N/A']);
    
    // Questions
    fputcsv($output, ['how ' . $keyword . ' works', 'Question', 'N/A', 'N/A', 'N/A']);
    fputcsv($output, ['what is ' . $keyword, 'Question', 'N/A', 'N/A', 'N/A']);
    fputcsv($output, ['why ' . $keyword . ' is important', 'Question', 'N/A', 'N/A', 'N/A']);
    
    // Alphabet
    foreach (range('a', 'h') as $l) {
        fputcsv($output, [$keyword . ' ' . $l, 'Alphabet Expansion', 'N/A', 'N/A', 'N/A']);
    }
    
    // Buyer intent
    fputcsv($output, ['best ' . $keyword . ' tools', 'Buyer Intent', 'N/A', 'N/A', 'N/A']);
    fputcsv($output, ['affordable ' . $keyword . ' services', 'Buyer Intent', 'N/A', 'N/A', 'N/A']);
    fputcsv($output, ['top ' . $keyword . ' agency', 'Buyer Intent', 'N/A', 'N/A', 'N/A']);
}

fclose($output);
exit;
?>
