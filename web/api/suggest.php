<?php
/**
 * API Suggest Endpoint
 * Performs background keyword expansion, crawls Google Autocomplete securely,
 * logs history, and generates blog titles and FAQs.
 */

header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config.php';

// Fetch criteria
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$country = isset($_GET['country']) ? trim($_GET['country']) : 'us';
$language = isset($_GET['lang']) ? trim($_GET['lang']) : 'en';

if (empty($keyword)) {
    echo json_encode([
        'success' => false,
        'message' => 'Keyword query cannot be empty.'
    ]);
    exit;
}

// Save Search History safely if MySQL is connected
if (isset($dbConnected) && $dbConnected && isset($pdo)) {
    try {
        // Insert search history
        $stmt = $pdo->prepare("INSERT INTO `search_history` (`keyword`) VALUES (:kw)");
        $stmt->execute([':kw' => $keyword]);
        
        // Track trending / search volume frequency
        $stmtTrend = $pdo->prepare("
            INSERT INTO `trending_keywords` (`keyword`, `search_count`) 
            VALUES (:kw, 1) 
            ON DUPLICATE KEY UPDATE `search_count` = `search_count` + 1
        ");
        $stmtTrend->execute([':kw' => strtolower($keyword)]);
    } catch (Exception $e) {
        // Log error silently, do not break public JSON output
    }
}

// 1. Fetch live suggestions from Google Autocomplete API
function fetchGoogleSuggestions($query, $lang = 'en') {
    $url = "https://suggestqueries.google.com/complete/search?client=firefox&hl=" . urlencode($lang) . "&q=" . urlencode($query);
    
    // Create connection
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && !empty($response)) {
        $decoded = json_decode($response, true);
        if (isset($decoded[1]) && is_array($decoded[1])) {
            return $decoded[1];
        }
    }
    return [];
}

// Get basic autocompleted matches
$basicSuggestions = fetchGoogleSuggestions($keyword, $language);
if (empty($basicSuggestions)) {
    // Local fallback if offline or request blocked
    $basicSuggestions = [
        $keyword,
        $keyword . " ideas",
        $keyword . " tutorial",
        $keyword . " guide",
        $keyword . " tools",
        $keyword . " tips for beginners",
    ];
}

// 2. Generate long-tail variants by alphabet expansion
$alphabetSuggestions = [];
$letters = range('a', 'z');
// Select a subset to speed up API performance so curl does not hang, or expand locally
// Live fetch for first 5 letters, build the rest locally to keep response immediate
foreach (array_slice($letters, 0, 5) as $letter) {
    $subSuggests = fetchGoogleSuggestions($keyword . " " . $letter, $language);
    foreach ($subSuggests as $s) {
        if (!in_array($s, $basicSuggestions)) {
            $alphabetSuggestions[] = $s;
        }
    }
    if (count($alphabetSuggestions) >= 15) break;
}
// Local expansions as fallback/enrichment
foreach ($letters as $letter) {
    $alphabetSuggestions[] = $keyword . " " . $letter;
}
$alphabetSuggestions = array_unique($alphabetSuggestions);

// 3. Question Modifiers
$questionModifiers = ['how', 'what', 'why', 'when', 'where', 'is', 'can', 'are'];
$questionKeywords = [];
foreach ($questionModifiers as $mod) {
    $questionKeywords[] = $mod . " " . $keyword;
    $questionKeywords[] = $keyword . " " . $mod;
}
// Try actual Google suggest for top question
$quickQuestions = fetchGoogleSuggestions("how " . $keyword, $language);
foreach ($quickQuestions as $q) {
    $questionKeywords[] = $q;
}
$questionKeywords = array_values(array_unique(array_filter($questionKeywords)));

// 4. Intent Modifiers (Buying Intent)
$intentModifiers = ['best', 'cheap', 'top', 'buy', 'review', 'pricing', 'coupon', 'deals'];
$buyerKeywords = [];
foreach ($intentModifiers as $mod) {
    $buyerKeywords[] = $mod . " " . $keyword;
    $buyerKeywords[] = $keyword . " " . $mod;
}
$buyerKeywords = array_values(array_unique($buyerKeywords));

// 5. Comparison Modifiers
$comparisonModifiers = ['vs', 'alternative', 'comparison', 'or'];
$comparisonKeywords = [];
foreach ($comparisonModifiers as $mod) {
    if ($mod === 'vs' || $mod === 'or') {
        $comparisonKeywords[] = $keyword . " " . $mod;
    } else {
        $comparisonKeywords[] = $keyword . " " . $mod;
        $comparisonKeywords[] = $mod . " " . $keyword;
    }
}
$comparisonKeywords = array_values(array_unique($comparisonKeywords));

// 6. Generate Blog Title Ideas (at least 20 clickable premium ideas)
$kwCapitalized = ucwords($keyword);
$titleTemplates = [
    "10 Best {keyword} Tips for Beginners (That Actually Work)",
    "How to Master {keyword} Step by Step: A Complete 2026 Guide",
    "The Ultimate Guide to {keyword}: Everything You Need to Know",
    "Why You Need to Care About {keyword} This Year",
    "Top 15 {keyword} Tools Every Digital Marketer Needs",
    "The Secret of Successful {keyword} (And How to Clean Up)",
    "How to Choose the Best {keyword} Services for Your Business",
    "12 Common {keyword} Mistakes and How to Avoid Them",
    "{keyword} vs Competitors: Which is Truly Better in 2026?",
    "How to Double Your Traffic Using Savvy {keyword} Strategies",
    "What is {keyword}? Definition, Strategies, and Growth Hacks",
    "7 High-Impact {keyword} Tactics You Should Implement Today",
    "The Best {keyword} Strategy for E-commerce Websites",
    "Is {keyword} Still Relevant? Here Is What the Data Suggests",
    "How to Automate Your {keyword} Workflow in Under 10 Minutes",
    "How We Achieved #1 Rank for {keyword} (Case Study)",
    "The Future of {keyword}: Trends and Predictions to Watch",
    "Essential Checklist for Perfect {keyword} Optimization",
    "Affordable {keyword}: How to Succeed on a Tiny Budget",
    "Simple Hacks to Elevate Your {keyword} Performance Over Night",
    "How to Create a Winning Content Roadmap for {keyword}",
    "Top Experts Share Their Number One Secret for {keyword}"
];

$blogTitles = [];
foreach ($titleTemplates as $tmpl) {
    $blogTitles[] = str_replace('{keyword}', $kwCapitalized, $tmpl);
}

// 7. FAQs Generator (At least 5 FAQs with helpful answers placeholder)
$faqs = [
    [
        'question' => "What is " . $kwCapitalized . "?",
        'answer' => "Indeed, " . $kwCapitalized . " is a fundamental concept in this niche. In simple terms, it refers to the strategic application and optimization of assets to drive maximum visibility and user engagement."
    ],
    [
        'question' => "Why is " . $kwCapitalized . " important for online businesses?",
        'answer' => "Without " . $kwCapitalized . ", it is incredibly difficult for your website or target platform to attract clean organic traffic. Focusing on this helps rank higher and reach high-intent customers."
    ],
    [
        'question' => "How long does it take to see results with " . $kwCapitalized . "?",
        'answer' => "Generally, results can begin to manifest within 3 to 6 months depending on keyword difficulty, domain authority, and resource allocation."
    ],
    [
        'question' => "What are the best tools for " . $kwCapitalized . "?",
        'answer' => "Top recommended platforms include specialized SaaS setups (like Semrush, Ahrefs, Moz) as well as free Google resources like Google Trends and Search Console."
    ],
    [
        'question' => "Can I optimize " . $kwCapitalized . " with a tight budget?",
        'answer' => "Absolutely! By prioritizing high-traffic, low-competition long-tail keywords, you can achieve remarkable ROI without expensive tools."
    ],
];

// 8. Content Ideas (Article ideas, FAQ ideas, Listicle ideas)
$contentIdeas = [
    'articles' => [
        "A Deep Dive into " . $kwCapitalized . " Architecture",
        "Exploring the Untapped Potential of " . $kwCapitalized,
        "How " . $kwCapitalized . " Changed the Landscape of Modern Web Apps"
    ],
    'faq_ideas' => [
        "How can I trace " . $kwCapitalized . " performance updates?",
        "Is there a safe way to automate " . $kwCapitalized . " tasks without penalty?"
    ],
    'listicles' => [
        "9 Best " . $kwCapitalized . " Frameworks for Fast Ranking",
        "5 Hidden " . $kwCapitalized . " Secret Weapons of Top Creators"
    ]
];

// Return structured unified JSON response
echo json_encode([
    'success' => true,
    'keyword' => $keyword,
    'country' => $country,
    'language' => $language,
    'timestamp' => time(),
    'data' => [
        'basic' => array_values($basicSuggestions),
        'questions' => $questionKeywords,
        'long_tail' => array_values($alphabetSuggestions),
        'buyer_intent' => $buyerKeywords,
        'comparisons' => $comparisonKeywords,
        'blog_titles' => $blogTitles,
        'faqs' => $faqs,
        'content_ideas' => $contentIdeas,
        'metrics_placeholder' => [
            'search_volume' => 'N/A',
            'cpc' => 'N/A',
            'difficulty' => 'N/A'
        ]
    ]
]);
?>
