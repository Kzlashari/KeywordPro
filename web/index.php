<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Keyword + Blog Title Suggestion Suite</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Google Keyboard Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
        }
        [v-cloak] { display: none !important; }
        /* Smooth height transition */
        .result-section {
            transition: all 0.4s ease-in-out;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 min-h-screen font-sans transition-colors duration-200">

    <!-- Header -->
    <header class="border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="bg-gradient-to-tr from-brand-600 to-indigo-600 text-white p-2 rounded-xl shadow-md">
                    <span class="material-symbols-outlined block">insights</span>
                </div>
                <div>
                    <span class="font-bold text-lg bg-gradient-to-r from-brand-600 to-indigo-600 bg-clip-text text-transparent dark:from-brand-500 dark:to-indigo-400">SEO Keyword Pro</span>
                    <span class="text-xs block text-slate-400 font-medium">SaaS Autocomplete Suite</span>
                </div>
            </div>

            <!-- Stats & Mode toggler -->
            <div class="flex items-center space-x-4">
                <div id="db-status-badge" class="hidden md:flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 block animate-pulse"></span>
                    <span>Database Connected</span>
                </div>

                <!-- Live clock -->
                <div class="hidden lg:block text-xs text-slate-400 font-mono">
                    UTC: <span id="clock">09:30:00</span>
                </div>

                <!-- Dark Mode Button -->
                <button id="dark-toggle" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition" aria-label="Toggle Theme">
                    <span id="dark-icon" class="material-symbols-outlined">dark_mode</span>
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Search Jumbotron -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/60 dark:border-slate-800/60 mb-8 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-tr from-brand-50/50 via-transparent to-indigo-50/20 dark:from-brand-950/10 dark:to-indigo-950/5 pointer-events-none"></div>
            
            <div class="relative z-10 max-w-3xl">
                <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight mb-3">
                    Unlock Google Autocomplete <br class="hidden sm:inline">
                    <span class="bg-gradient-to-r from-brand-500 to-indigo-500 bg-clip-text text-transparent">Power Long-Tail Keywords</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm sm:text-base mb-6">
                    Enter any seed topic and instantly crawl Google autocomplete modifiers, questions, commercial intent terms, clickable SEO titles, and FAQ generators.
                </p>

                <!-- Search Form -->
                <form id="search-form" class="space-y-4">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <div class="relative flex-1">
                            <span class="material-symbols-outlined absolute left-4 top-3.5 text-slate-400">search</span>
                            <input 
                                type="text" 
                                id="keyword" 
                                required 
                                placeholder="Enter keyword to research (e.g. SEO tips, digital marketing, AI tools)..." 
                                class="w-full pl-12 pr-4 py-3.5 bg-slate-50 focus:bg-white dark:bg-slate-950 dark:focus:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500 font-medium transition"
                            />
                        </div>
                        
                        <!-- Country Selectors -->
                        <div class="flex gap-2 min-w-[200px]">
                            <select id="country" class="py-3 px-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm flex-1">
                                <option value="us">🇺🇸 United States</option>
                                <option value="uk">🇬🇧 United Kingdom</option>
                                <option value="ca">🇨🇦 Canada</option>
                                <option value="in">🇮🇳 India</option>
                                <option value="au">🇦🇺 Australia</option>
                            </select>
                            
                            <select id="lang" class="py-3 px-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm flex-1">
                                <option value="en">English (EN)</option>
                                <option value="es">Español (ES)</option>
                                <option value="fr">Français (FR)</option>
                                <option value="de">Deutsch (DE)</option>
                            </select>
                        </div>

                        <button 
                            type="submit" 
                            class="px-8 py-3.5 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 active:scale-[0.98] text-white font-bold rounded-2xl transition shadow-lg shadow-brand-500/20 flex items-center justify-center space-x-2"
                        >
                            <span>Analyze</span>
                            <span class="material-symbols-outlined text-sm">bolt</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Main Body Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Sidebar / History Pane -->
            <aside class="lg:col-span-3 space-y-6">
                
                <!-- Info Status -->
                <div class="bg-gradient-to-tr from-brand-500 to-indigo-600 text-white rounded-2xl p-4 shadow-sm">
                    <h3 class="font-bold flex items-center space-x-1">
                        <span class="material-symbols-outlined text-sm">verified_user</span>
                        <span>API ready Structure</span>
                    </h3>
                    <p class="text-xs text-brand-100 mt-2 leading-relaxed">
                        This web tool queries Google dynamically. Its Clean architecture is ready to integrate direct premium APIs such as DataForSEO, Ahrefs, Semrush or OpenAI.
                    </p>
                </div>

                <!-- Recent Searches -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-4 shadow-sm">
                    <h3 class="font-bold text-sm text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3 flex items-center justify-between">
                        <span>Recent Searches</span>
                        <span class="material-symbols-outlined text-xs">history</span>
                    </h3>
                    <div id="recent-search-list" class="space-y-1 text-sm font-medium">
                        <!-- Loaded dynamically -->
                        <div class="text-xs text-slate-400 py-2">Loading search log...</div>
                    </div>
                </div>

                <!-- Trending Topics -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-4 shadow-sm">
                    <h3 class="font-bold text-sm text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3 flex items-center justify-between">
                        <span>Global Trends</span>
                        <span class="material-symbols-outlined text-xs">analytics</span>
                    </h3>
                    <div id="trending-list" class="space-y-2 text-sm">
                        <!-- Loaded dynamically -->
                        <div class="text-xs text-slate-400 py-1">Loading trends...</div>
                    </div>
                </div>
            </aside>

            <!-- Results Panel -->
            <section class="lg:col-span-9 relative">
                
                <!-- Loading State overlay -->
                <div id="loading" class="hidden absolute inset-0 bg-slate-50/80 dark:bg-slate-950/90 flex flex-col items-center justify-center z-30 rounded-3xl min-h-[400px]">
                    <div class="relative flex items-center justify-center mb-4">
                        <div class="w-16 h-16 border-4 border-slate-200 dark:border-slate-800 border-t-brand-500 rounded-full animate-spin"></div>
                        <span class="material-symbols-outlined text-2xl text-brand-500 absolute animate-pulse">insights</span>
                    </div>
                    <p class="font-bold text-slate-700 dark:text-slate-300">Crawling Google Autocomplete Suggestions...</p>
                    <p class="text-xs text-slate-400 mt-1">Expanding modifiers 'a-z', questions, and search intent.</p>
                </div>

                <!-- Placeholder state -->
                <div id="placeholder-box" class="bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-800 rounded-3xl p-12 text-center text-slate-400 dark:text-slate-500 min-h-[400px] flex flex-col justify-center items-center">
                    <span class="material-symbols-outlined text-5xl mb-3 text-slate-300 dark:text-slate-700">travel_explore</span>
                    <h2 class="text-lg font-bold text-slate-700 dark:text-slate-300 mb-1">Begin Keyword Analysis</h2>
                    <p class="text-sm max-w-sm mx-auto leading-normal">
                        Enter a keyword inside the search terminal above, select language constraints, and discover thousands of optimized keywords.
                    </p>
                </div>

                <!-- Main suggest results output -->
                <div id="results-container" class="hidden space-y-8">
                    
                    <!-- Search Head Overview -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl gap-4">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Currently Displaying</span>
                            <h2 class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400 flex items-center space-x-1.5">
                                <span class="material-symbols-outlined text-lg">search_insights</span>
                                <span id="display-keyword">"SEO"</span>
                            </h2>
                        </div>
                        
                        <!-- Global Action Exporters -->
                        <div class="flex flex-wrap items-center gap-2">
                            <button id="export-csv-all" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold rounded-xl transition flex items-center space-x-1.5">
                                <span class="material-symbols-outlined text-sm">download</span>
                                <span>Export Full CSV</span>
                            </button>
                            <button id="export-txt-all" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold rounded-xl transition flex items-center space-x-1.5">
                                <span class="material-symbols-outlined text-sm">description</span>
                                <span>Export Full TXT</span>
                            </button>
                            <button id="copy-all-kw" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold rounded-xl transition flex items-center space-x-1.5">
                                <span class="material-symbols-outlined text-xs">content_copy</span>
                                <span>Copy All Keywords</span>
                            </button>
                        </div>
                    </div>

                    <!-- Metrics Table View -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="font-bold text-md flex items-center space-x-1.5">
                                <span class="material-symbols-outlined text-brand-500 text-base">list_alt</span>
                                <span>Premium Suggestions Table</span>
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 flex items-center space-x-1">
                                <span class="material-symbols-outlined text-[10px]">offline_pin</span>
                                <span>Simulated Metrics</span>
                            </span>
                        </div>
                        
                        <div class="overflow-x-auto max-h-[350px] overflow-y-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-slate-55/60 dark:bg-slate-900 sticky top-0 bg-slate-100 dark:bg-slate-900/90">
                                    <tr class="text-xs text-slate-400 dark:text-slate-500 font-semibold text-left">
                                        <th scope="col" class="px-6 py-3">Keyword Idea</th>
                                        <th scope="col" class="px-6 py-3">Type</th>
                                        <th scope="col" class="px-6 py-3">Search Volume</th>
                                        <th scope="col" class="px-6 py-3">CPC ($)</th>
                                        <th scope="col" class="px-6 py-3">Difficulty (KD)</th>
                                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="metric-table-body" class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                    <!-- Rendered dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Categorized Tabs Groups Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Long-Tail Keywords Group (Alphabet + Modifiers) -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4 border-b border-light-200 dark:border-slate-800 pb-3">
                                <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-400 flex items-center space-x-1.5">
                                    <span class="material-symbols-outlined text-red-400">text_fields</span>
                                    <span>Alphabet Expansions</span>
                                </h3>
                                <button onclick="copyGroupTxt('long-tail-group-results')" class="text-xs text-brand-500 hover:underline flex items-center space-x-1">
                                    <span class="material-symbols-outlined text-xs">content_copy</span>
                                    <span>Copy List</span>
                                </button>
                            </div>
                            <div id="long-tail-group-results" class="space-y-1.5 max-h-[250px] overflow-y-auto text-sm pr-2">
                                <!-- Loaded dynamically -->
                            </div>
                        </div>

                        <!-- Questions Modifiers Group -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4 border-b border-light-200 dark:border-slate-800 pb-3">
                                <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-400 flex items-center space-x-1.5">
                                    <span class="material-symbols-outlined text-amber-500">help_outline</span>
                                    <span>Question Modifiers</span>
                                </h3>
                                <button onclick="copyGroupTxt('questions-group-results')" class="text-xs text-brand-500 hover:underline flex items-center space-x-1">
                                    <span class="material-symbols-outlined text-xs">content_copy</span>
                                    <span>Copy List</span>
                                </button>
                            </div>
                            <div id="questions-group-results" class="space-y-1.5 max-h-[250px] overflow-y-auto text-sm pr-2">
                                <!-- Loaded dynamically -->
                            </div>
                        </div>

                        <!-- Buyer Intent Modifiers Group -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4 border-b border-light-200 dark:border-slate-800 pb-3">
                                <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-400 flex items-center space-x-1.5">
                                    <span class="material-symbols-outlined text-emerald-500">shopping_cart</span>
                                    <span>Buyer Intent Modifiers</span>
                                </h3>
                                <button onclick="copyGroupTxt('buyer-intent-group-results')" class="text-xs text-brand-500 hover:underline flex items-center space-x-1">
                                    <span class="material-symbols-outlined text-xs">content_copy</span>
                                    <span>Copy List</span>
                                </button>
                            </div>
                            <div id="buyer-intent-group-results" class="space-y-1.5 max-h-[250px] overflow-y-auto text-sm pr-2">
                                <!-- Loaded dynamically -->
                            </div>
                        </div>

                        <!-- Comparisons Modifiers Group -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4 border-b border-light-200 dark:border-slate-800 pb-3">
                                <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-400 flex items-center space-x-1.5">
                                    <span class="material-symbols-outlined text-blue-500">compare_arrows</span>
                                    <span>Comparison Modifiers</span>
                                </h3>
                                <button onclick="copyGroupTxt('comparisons-group-results')" class="text-xs text-brand-500 hover:underline flex items-center space-x-1">
                                    <span class="material-symbols-outlined text-xs">content_copy</span>
                                    <span>Copy List</span>
                                </button>
                            </div>
                            <div id="comparisons-group-results" class="space-y-1.5 max-h-[250px] overflow-y-auto text-sm pr-2">
                                <!-- Loaded dynamically -->
                            </div>
                        </div>

                    </div>

                    <!-- SEO Clickable Blog Title Suite (At least 20 Clickable Titles) -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div>
                                <h3 class="font-extrabold text-lg flex items-center space-x-2">
                                    <span class="material-symbols-outlined text-red-500">campaign</span>
                                    <span>High Click-Through Blog Title Ideas</span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-1">Stunning, human-readable templates optimized for maximum search click-through rates.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a id="export-titles-csv" href="#" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                                    <span class="material-symbols-outlined text-xs">download</span>
                                    <span>CSV</span>
                                </a>
                                <button onclick="copyGroupTxt('blog-titles-results')" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                                    <span class="material-symbols-outlined text-xs">content_copy</span>
                                    <span>Copy All 20+</span>
                                </button>
                            </div>
                        </div>
                        <div id="blog-titles-results" class="grid grid-cols-1 md:grid-cols-2 gap-3 pr-2">
                            <!-- Loaded dynamically -->
                        </div>
                    </div>

                    <!-- FAQ Generator -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div>
                                <h3 class="font-bold text-lg flex items-center space-x-2">
                                    <span class="material-symbols-outlined text-amber-500 animate-pulse">quiz</span>
                                    <span>FAQ Generator</span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-1">Pre-formatted questions with concise answers to boost Google FAQ schema presence.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a id="export-faqs-csv" href="#" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                                    <span class="material-symbols-outlined text-xs">download</span>
                                    <span>CSV</span>
                                </a>
                                <button id="copy-faqs" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                                    <span class="material-symbols-outlined text-xs">content_copy</span>
                                    <span>Copy FAQs</span>
                                </button>
                            </div>
                        </div>
                        <div id="faqs-results" class="space-y-3.5 pr-2">
                            <!-- Loaded dynamically -->
                        </div>
                    </div>

                    <!-- Content Ideas Grid (Listicles, FAQ, Outlines) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Listicle structures -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm">
                            <h4 class="font-bold text-sm text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2 mb-3 flex items-center justify-between">
                                <span>Listicles Ideas</span>
                                <span class="material-symbols-outlined text-xs text-indigo-500">format_list_bulleted</span>
                            </h4>
                            <div id="listicles-results" class="space-y-2 text-sm">
                                <!-- Loaded dynamically -->
                            </div>
                        </div>

                        <!-- Article directions -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm">
                            <h4 class="font-bold text-sm text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2 mb-3 flex items-center justify-between">
                                <span>Article Outlines</span>
                                <span class="material-symbols-outlined text-xs text-teal-500">map</span>
                            </h4>
                            <div id="articles-results" class="space-y-2 text-sm">
                                <!-- Loaded dynamically -->
                            </div>
                        </div>

                        <!-- FAQ conceptual directions -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm">
                            <h4 class="font-bold text-sm text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2 mb-3 flex items-center justify-between">
                                <span>FAQ Ideations</span>
                                <span class="material-symbols-outlined text-xs text-emerald-500">help</span>
                            </h4>
                            <div id="faq-ideas-results" class="space-y-2 text-sm">
                                <!-- Loaded dynamically -->
                            </div>
                        </div>

                    </div>

                </div>

            </section>

        </div>

    </main>

    <!-- Toast Component -->
    <div id="toast" class="fixed bottom-5 right-5 z-50 bg-slate-900 text-white dark:bg-white dark:text-slate-900 px-4 py-3 rounded-2xl shadow-xl transition-all translate-y-24 opacity-0 pointer-events-none flex items-center space-x-2 text-sm font-semibold">
        <span class="material-symbols-outlined text-base">check_circle</span>
        <span id="toast-message">Copied to clipboard!</span>
    </div>

    <!-- Footer -->
    <footer class="mt-20 border-t border-slate-200 dark:border-slate-800 py-8 bg-white dark:bg-slate-900/40 text-xs text-slate-400 text-center">
        <p>&copy; 2026 SEO Keyword Suggestion Tool. All rights reserved. Made using clean raw Core PHP 8+ and modern responsive Tailwind.</p>
    </footer>

    <!-- JS logic -->
    <script>
        const darkToggle = document.getElementById('dark-toggle');
        const darkIcon = document.getElementById('dark-icon');
        const html = document.documentElement;

        // Dark/Light toggling
        darkToggle.addEventListener('click', () => {
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                darkIcon.textContent = 'dark_mode';
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                darkIcon.textContent = 'light_mode';
                localStorage.setItem('theme', 'dark');
            }
        });

        // Load theme from preference
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
            darkIcon.textContent = 'light_mode';
        } else {
            html.classList.remove('dark');
            darkIcon.textContent = 'dark_mode';
        }

        // Live clock (UTC)
        function updateClock() {
            const now = new Date();
            const timeString = now.toISOString().substring(11, 19);
            document.getElementById('clock').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Log clipboard success toast
        function showToast(message) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-message').textContent = message;
            toast.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
            toast.classList.add('translate-y-0', 'opacity-100');
            setTimeout(() => {
                toast.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
                toast.classList.remove('translate-y-0', 'opacity-100');
            }, 2500);
        }

        // Copy generic string safely
        function copyText(text, entityType = "Keyword") {
            navigator.clipboard.writeText(text).then(() => {
                showToast(`${entityType} copied to clipboard!`);
            }).catch(() => {
                // Secondary fallback
                const el = document.createElement('textarea');
                el.value = text;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                showToast(`${entityType} copied!`);
            });
        }

        // Copy entire list inside container
        function copyGroupTxt(containerId) {
            const container = document.getElementById(containerId);
            const items = Array.from(container.querySelectorAll('.copyable-val')).map(el => el.textContent.trim());
            if (items.length > 0) {
                copyText(items.join('\n'), "Complete list");
            } else {
                showToast("Nothing to copy!");
            }
        }

        // Fetch History & Trends live
        function loadHistory() {
            fetch('api/history.php')
                .then(r => r.json())
                .then(res => {
                    const badge = document.getElementById('db-status-badge');
                    if (res.mysql_connected) {
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }

                    // Render recent search list
                    const recentSList = document.getElementById('recent-search-list');
                    recentSList.innerHTML = '';
                    if (res.history && res.history.length > 0) {
                        res.history.forEach(item => {
                            const btn = document.createElement('button');
                            btn.className = "w-full text-left py-1.5 px-2 bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-900 rounded-lg text-xs truncate block text-slate-600 dark:text-slate-300 transition-colors duration-155";
                            btn.textContent = item.keyword;
                            btn.onclick = () => {
                                document.getElementById('keyword').value = item.keyword;
                                triggerAnalysis(item.keyword);
                            };
                            recentSList.appendChild(btn);
                        });
                    } else {
                        recentSList.innerHTML = '<div class="text-xs text-slate-400">Empty search log.</div>';
                    }

                    // Render Global Trends
                    const trendList = document.getElementById('trending-list');
                    trendList.innerHTML = '';
                    if (res.trending && res.trending.length > 0) {
                        res.trending.forEach(item => {
                            const btn = document.createElement('button');
                            btn.className = "w-full text-left flex items-center justify-between py-1 px-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-xs transition";
                            btn.onclick = () => {
                                document.getElementById('keyword').value = item.keyword;
                                triggerAnalysis(item.keyword);
                            };
                            btn.innerHTML = `
                                <span class="font-semibold text-slate-600 dark:text-slate-300">#${item.keyword}</span>
                                <span class="text-[10px] bg-brand-50 text-brand-600 px-1.5 py-0.5 rounded-full dark:bg-indigo-950/40 dark:text-indigo-400 font-mono">${item.search_count} seeks</span>
                            `;
                            trendList.appendChild(btn);
                        });
                    } else {
                        trendList.innerHTML = '<div class="text-xs text-slate-400">No active trends.</div>';
                    }
                })
                .catch(() => {
                    // Fail silently
                });
        }

        // Trigger analysis
        function triggerAnalysis(q) {
            if (!q) return;
            const loading = document.getElementById('loading');
            const placeholder = document.getElementById('placeholder-box');
            const resultsContainer = document.getElementById('results-container');
            
            loading.classList.remove('hidden');
            
            const countryVal = document.getElementById('country').value;
            const langVal = document.getElementById('lang').value;
            
            fetch(`api/suggest.php?q=${encodeURIComponent(q)}&country=${countryVal}&lang=${langVal}`)
                .then(res => res.json())
                .then(response => {
                    loading.classList.add('hidden');
                    placeholder.classList.add('hidden');
                    resultsContainer.classList.remove('hidden');

                    if (!response.success) {
                        alert(response.message || "An error occurred.");
                        return;
                    }

                    const data = response.data;
                    document.getElementById('display-keyword').textContent = `"${response.keyword}"`;

                    // Update Exporters
                    document.getElementById('export-csv-all').onclick = () => {
                        window.location.href = `exports/csv.php?q=${encodeURIComponent(response.keyword)}&type=all`;
                    };
                    document.getElementById('export-txt-all').onclick = () => {
                        window.location.href = `exports/csv.php?q=${encodeURIComponent(response.keyword)}&type=all&format=txt`;
                    };
                    document.getElementById('export-titles-csv').href = `exports/csv.php?q=${encodeURIComponent(response.keyword)}&type=titles`;
                    document.getElementById('export-faqs-csv').href = `exports/csv.php?q=${encodeURIComponent(response.keyword)}&type=faqs`;

                    // Update metrics table
                    const tableBody = document.getElementById('metric-table-body');
                    tableBody.innerHTML = '';
                    
                    // Render ALL keywords into table with pagination limit
                    const allKws = [
                        {kw: response.keyword, type: 'Query'},
                        ...data.basic.map(k => ({kw: k, type: 'Basic Autocomplete'})),
                        ...data.questions.map(k => ({kw: k, type: 'Question'})),
                        ...data.buyer_intent.map(k => ({kw: k, type: 'Buyer Intent'})),
                    ];

                    allKws.slice(0, 15).forEach(item => {
                        const tr = document.createElement('tr');
                        tr.className = "hover:bg-slate-50 dark:hover:bg-slate-800/40 text-slate-600 dark:text-slate-300 font-medium transition duration-150";
                        tr.innerHTML = `
                            <td class="px-6 py-3 cursor-pointer hover:text-brand-500 font-semibold" onclick="copyText('${item.kw}', 'Keyword')">${item.kw}</td>
                            <td class="px-6 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800">${item.type}</span></td>
                            <td class="px-6 py-3 font-mono text-xs text-slate-400">${data.metrics_placeholder.search_volume}</td>
                            <td class="px-6 py-3 font-mono text-xs text-slate-400">${data.metrics_placeholder.cpc}</td>
                            <td class="px-6 py-3 font-mono text-xs text-slate-400">${data.metrics_placeholder.difficulty}</td>
                            <td class="px-6 py-3 text-right">
                                <button onclick="copyText('${item.kw}', 'Keyword')" class="text-slate-400 hover:text-indigo-500 transition-colors p-1" title="Copy Keyword">
                                    <span class="material-symbols-outlined text-sm">content_copy</span>
                                </button>
                            </td>
                        `;
                        tableBody.appendChild(tr);
                    });

                    // Update General Keyword Copy Actions
                    document.getElementById('copy-all-kw').onclick = () => {
                        const combinedList = allKws.map(i => i.kw).join('\n');
                        copyText(combinedList, "Complete keywords list");
                    };

                    // Populate Alphabet expansions group
                    const alphaBox = document.getElementById('long-tail-group-results');
                    alphaBox.innerHTML = '';
                    data.long_tail.slice(0, 15).forEach(k => {
                        const row = document.createElement('div');
                        row.className = "flex items-center justify-between py-1.5 px-3 bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800 rounded-xl transition duration-150";
                        row.innerHTML = `
                            <span class="copyable-val font-medium text-slate-600 dark:text-slate-300 cursor-pointer" onclick="copyText('${k}', 'Keyword')">${k}</span>
                            <span class="text-[10px] text-slate-400 font-mono">N/A Volume</span>
                        `;
                        alphaBox.appendChild(row);
                    });

                    // Populate Questions Box
                    const questionsBox = document.getElementById('questions-group-results');
                    questionsBox.innerHTML = '';
                    data.questions.slice(0, 10).forEach(k => {
                        const row = document.createElement('div');
                        row.className = "flex items-center justify-between py-1.5 px-3 bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800 rounded-xl transition duration-150";
                        row.innerHTML = `
                            <span class="copyable-val font-medium text-slate-600 dark:text-slate-300 cursor-pointer" onclick="copyText('${k}', 'Keyword')">${k}</span>
                            <span class="text-[10px] text-slate-400 font-mono">Question</span>
                        `;
                        questionsBox.appendChild(row);
                    });

                    // Populate Commercial / Buyer Intent Box
                    const buyerBox = document.getElementById('buyer-intent-group-results');
                    buyerBox.innerHTML = '';
                    data.buyer_intent.slice(0, 10).forEach(k => {
                        const row = document.createElement('div');
                        row.className = "flex items-center justify-between py-1.5 px-3 bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800 rounded-xl transition duration-150";
                        row.innerHTML = `
                            <span class="copyable-val font-medium text-slate-600 dark:text-slate-300 cursor-pointer" onclick="copyText('${k}', 'Keyword')">${k}</span>
                            <span class="text-[10px] bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 px-1.5 py-0.5 rounded text-[10px] font-bold">Intent</span>
                        `;
                        buyerBox.appendChild(row);
                    });

                    // Populate Comparisions Box
                    const compBox = document.getElementById('comparisons-group-results');
                    compBox.innerHTML = '';
                    data.comparisons.slice(0, 10).forEach(k => {
                        const row = document.createElement('div');
                        row.className = "flex items-center justify-between py-1.5 px-3 bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-800 rounded-xl transition duration-150";
                        row.innerHTML = `
                            <span class="copyable-val font-medium text-slate-600 dark:text-slate-300 cursor-pointer" onclick="copyText('${k}', 'Keyword')">${k}</span>
                            <span class="text-[10px] text-blue-500 font-semibold font-mono">VS</span>
                        `;
                        compBox.appendChild(row);
                    });

                    // Populate blog titles list (At least 20 items)
                    const titleBox = document.getElementById('blog-titles-results');
                    titleBox.innerHTML = '';
                    data.blog_titles.forEach(item => {
                        const div = document.createElement('div');
                        div.className = "group text-left p-3.5 bg-slate-50 hover:bg-slate-100 border border-slate-100 hover:border-brand-500/30 dark:bg-slate-950 dark:hover:bg-slate-800 dark:border-slate-800 dark:hover:border-brand-500/40 rounded-2xl cursor-pointer hover:shadow-xs transition duration-150 flex items-start gap-2.5";
                        div.onclick = () => copyText(item, "Blog Title Option");
                        div.innerHTML = `
                            <span class="material-symbols-outlined text-slate-400 group-hover:text-brand-500 text-sm mt-0.5 transition-colors">check_circle</span>
                            <span class="copyable-val font-semibold text-xs sm:text-sm text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-slate-100 leading-normal">${item}</span>
                        `;
                        titleBox.appendChild(div);
                    });

                    // Populate FAQs list (At least 5)
                    const faqBox = document.getElementById('faqs-results');
                    faqBox.innerHTML = '';
                    let faqPlainTxtList = [];
                    data.faqs.forEach((item, index) => {
                        const qaTxt = `Question: ${item.question}\nAnswer: ${item.answer}`;
                        faqPlainTxtList.push(qaTxt);

                        const faqEl = document.createElement('div');
                        faqEl.className = "border border-slate-100 dark:border-slate-800 rouded-2xl rounded-2xl overflow-hidden transition duration-150";
                        faqEl.innerHTML = `
                            <button onclick="toggleFaq(${index})" class="w-full text-left px-5 py-3.5 bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-900 text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-200 flex items-center justify-between">
                                <span class="copyable-val font-bold">${item.question}</span>
                                <span id="faq-chevron-${index}" class="material-symbols-outlined text-slate-400 transition-transform duration-200">expand_more</span>
                            </button>
                            <div id="faq-ans-${index}" class="hidden px-5 py-4 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed font-semibold">
                                ${item.answer}
                            </div>
                        `;
                        faqBox.appendChild(faqEl);
                    });
                    document.getElementById('copy-faqs').onclick = () => {
                        copyText(faqPlainTxtList.join('\n\n'), "Generated FAQ Schemas");
                    };

                    // Populate Secondary Content Ideas
                    // Listicles
                    const listiclesBox = document.getElementById('listicles-results');
                    listiclesBox.innerHTML = '';
                    data.content_ideas.listicles.forEach(idea => {
                        const item = document.createElement('div');
                        item.className = "py-2 px-3 bg-slate-50/60 dark:bg-slate-950/60 rounded-xl cursor-default hover:text-indigo-500 transition-colors duration-150";
                        item.textContent = idea;
                        listiclesBox.appendChild(item);
                    });

                    // Outline Ideas
                    const articlesBox = document.getElementById('articles-results');
                    articlesBox.innerHTML = '';
                    data.content_ideas.articles.forEach(idea => {
                        const item = document.createElement('div');
                        item.className = "py-2 px-3 bg-slate-50/60 dark:bg-slate-950/60 rounded-xl cursor-default hover:text-teal-500 transition-colors duration-150";
                        item.textContent = idea;
                        articlesBox.appendChild(item);
                    });

                    // FAQ Conceptual Directions
                    const faqIdeasBox = document.getElementById('faq-ideas-results');
                    faqIdeasBox.innerHTML = '';
                    data.content_ideas.faq_ideas.forEach(idea => {
                        const item = document.createElement('div');
                        item.className = "py-2 px-3 bg-slate-50/60 dark:bg-slate-950/60 rounded-xl cursor-default hover:text-emerald-500 transition-colors duration-150";
                        item.textContent = idea;
                        faqIdeasBox.appendChild(item);
                    });

                    // Reload search logs after saving to history DB
                    loadHistory();
                })
                .catch(err => {
                    loading.classList.add('hidden');
                    console.error(err);
                    alert("Unable to fetch autocomplete suggestions. Please check if your PHP local server is up and database is configured correctly.");
                });
        }

        // Toggle Accordion Item
        function toggleFaq(index) {
            const ans = document.getElementById(`faq-ans-${index}`);
            const chevron = document.getElementById(`faq-chevron-${index}`);
            if (ans.classList.contains('hidden')) {
                ans.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            } else {
                ans.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            }
        }

        // Handle Search Submit
        document.getElementById('search-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const q = document.getElementById('keyword').value.trim();
            if (q) {
                triggerAnalysis(q);
            }
        });

        // Initialize state
        loadHistory();
    </script>
</body>
</html>
