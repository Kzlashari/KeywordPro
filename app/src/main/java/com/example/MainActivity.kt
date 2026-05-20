package com.example

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.animation.*
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalClipboardManager
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import android.app.Application
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.room.Room
import com.example.db.SearchHistoryDatabase
import com.example.db.SearchHistoryEntity
import com.example.ui.theme.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONArray
import java.io.BufferedReader
import java.io.InputStreamReader
import java.net.HttpURLConnection
import java.net.URL
import java.net.URLEncoder

// Data Class for individual keywords
data class KeywordMetric(
    val keyword: String,
    val type: String,
    val searchVolume: String = "N/A",
    val cpc: String = "N/A",
    val difficulty: String = "N/A"
)

// Main aggregated suggestion model
data class SuggestionResult(
    val seedKeyword: String = "",
    val basic: List<KeywordMetric> = emptyList(),
    val questions: List<KeywordMetric> = emptyList(),
    val longTail: List<KeywordMetric> = emptyList(),
    val buyerIntent: List<KeywordMetric> = emptyList(),
    val comparisons: List<KeywordMetric> = emptyList(),
    val blogTitles: List<String> = emptyList(),
    val faqs: List<Pair<String, String>> = emptyList(),
    val listicles: List<String> = emptyList(),
    val articles: List<String> = emptyList(),
    val faqIdeas: List<String> = emptyList()
)

// UI State holder
sealed interface AppUiState {
    object Idle : AppUiState
    object Loading : AppUiState
    data class Success(val result: SuggestionResult) : AppUiState
    data class Error(val message: String) : AppUiState
}

// ViewModel with Room and Live Crawl network routine
class KeywordViewModel(application: Application) : AndroidViewModel(application) {
    private val db = Room.databaseBuilder(
        application,
        SearchHistoryDatabase::class.java, "search_history_db"
    ).fallbackToDestructiveMigration().build()
    
    private val dao = db.searchHistoryDao()
    
    val searchHistory = dao.getAllHistory()

    private val _uiState = MutableStateFlow<AppUiState>(AppUiState.Idle)
    val uiState: StateFlow<AppUiState> = _uiState.asStateFlow()

    private val _isDarkTheme = MutableStateFlow(true) // Start dark mode by default for premium SaaS feel
    val isDarkTheme: StateFlow<Boolean> = _isDarkTheme.asStateFlow()

    fun toggleTheme() {
        _isDarkTheme.value = !_isDarkTheme.value
    }

    fun deleteHistoryItem(id: Int) {
        viewModelScope.launch(Dispatchers.IO) {
            dao.deleteHistory(id)
        }
    }

    fun clearAllHistory() {
        viewModelScope.launch(Dispatchers.IO) {
            dao.clearAll()
        }
    }

    // Google Autocomplete crawl API core
    private fun executeGetSuggestions(query: String, lang: String = "en"): List<String> {
        val list = mutableListOf<String>()
        try {
            val encoded = URLEncoder.encode(query, "UTF-8")
            val urlSpec = "https://suggestqueries.google.com/complete/search?client=firefox&hl=$lang&q=$encoded"
            val url = URL(urlSpec)
            val connection = url.openConnection() as HttpURLConnection
            connection.requestMethod = "GET"
            connection.setRequestProperty("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64)")
            connection.connectTimeout = 4000
            connection.readTimeout = 4000

            if (connection.responseCode == 200) {
                val reader = BufferedReader(InputStreamReader(connection.inputStream))
                val sb = StringBuilder()
                var line: String?
                while (reader.readLine().also { line = it } != null) {
                    sb.append(line)
                }
                reader.close()

                val jsonArray = JSONArray(sb.toString())
                if (jsonArray.length() > 1) {
                    val suggests = jsonArray.getJSONArray(1)
                    for (i in 0 until suggests.length()) {
                        list.add(suggests.getString(i))
                    }
                }
            }
            connection.disconnect()
        } catch (e: Exception) {
            e.printStackTrace()
        }
        return list
    }

    // Comprehensive Analyzer combining live API crawl + local rule engines
    fun performAnalysis(seed: String, country: String = "US", language: String = "en") {
        if (seed.trim().isEmpty()) return
        
        _uiState.value = AppUiState.Loading

        viewModelScope.launch {
            // Log history
            withContext(Dispatchers.IO) {
                dao.insertSearch(SearchHistoryEntity(keyword = seed.trim()))
            }

            try {
                val result = withContext(Dispatchers.IO) {
                    // 1. Fetch live suggests from Google
                    val basicCrawled = executeGetSuggestions(seed, language)
                    val basicMetrics = if (basicCrawled.isNotEmpty()) {
                        basicCrawled.map { KeywordMetric(it, "Basic Autocomplete") }
                    } else {
                        // Fallback elements
                        listOf(
                            KeywordMetric(seed, "Seed Keyword"),
                            KeywordMetric("$seed tips", "Autocomplete"),
                            KeywordMetric("$seed tools", "Autocomplete"),
                            KeywordMetric("$seed guide", "Autocomplete"),
                            KeywordMetric("$seed software", "Autocomplete")
                        )
                    }

                    // 2. Alphabet extensions (Crawl first 3 letters, generate others locally to stay fast)
                    val generatedLongTail = mutableListOf<KeywordMetric>()
                    val testLetters = listOf("a", "b", "c")
                    for (letter in testLetters) {
                        val subCrawled = executeGetSuggestions("$seed $letter", language)
                        subCrawled.take(3).forEach {
                            if (it != seed) {
                                generatedLongTail.add(KeywordMetric(it, "Alphabet Expansion"))
                            }
                        }
                    }
                    // Generate secondary alphabet elements locally
                    ('d'..'h').forEach { char ->
                        generatedLongTail.add(KeywordMetric("$seed $char", "Alphabet Expansion"))
                    }

                    // 3. Questions
                    val questionsList = mutableListOf<KeywordMetric>()
                    val questionCrawled = executeGetSuggestions("how $seed", language)
                    questionCrawled.take(4).forEach {
                        questionsList.add(KeywordMetric(it, "Question Modifier"))
                    }
                    // Local question generators
                    val questionModifiers = listOf("what is", "why is", "when to use", "where to get")
                    questionModifiers.forEach { mod ->
                        questionsList.add(KeywordMetric("$mod $seed", "Question Modifier"))
                    }

                    // 4. Buyer Intent
                    val intentList = mutableListOf<KeywordMetric>()
                    val intentCrawled = executeGetSuggestions("best $seed", language)
                    intentCrawled.take(4).forEach {
                        intentList.add(KeywordMetric(it, "Buyer Intent"))
                    }
                    val intentModifiers = listOf("$seed reviews", "buy $seed", "cheap $seed tools", "top $seed services")
                    intentModifiers.forEach { item ->
                        intentList.add(KeywordMetric(item, "Buyer Intent"))
                    }

                    // 5. Comparisons
                    val comparisonsList = listOf(
                        KeywordMetric("$seed vs competitors", "Comparison"),
                        KeywordMetric("$seed alternative list", "Comparison"),
                        KeywordMetric("best $seed comparison", "Comparison"),
                        KeywordMetric("$seed or similar tools", "Comparison")
                    )

                    // 6. Blog Titles Suite (at least 20 premium customizable click-worthy titles)
                    val capitalized = seed.split(' ').joinToString(" ") { word ->
                        word.replaceFirstChar { if (it.isLowerCase()) it.titlecase() else it.toString() }
                    }
                    
                    val blogTemplateList = listOf(
                        "10 Best $capitalized Tips for Beginners (That Really Work)",
                        "How to Master $capitalized Step-by-Step in 2026",
                        "The Ultimate $capitalized Guide: Everything You Need to Know",
                        "Why You Need to Invest in $capitalized Today",
                        "Top 15 $capitalized Software Systems Every Blogger Needs",
                        "The Secret of High-Performing $capitalized Revealed",
                        "How to Choose the Best $capitalized Service Provider",
                        "12 Costly $capitalized Mistakes You are Probably Making",
                        "$capitalized vs Competitors: Which is Truly Worth It?",
                        "How to Double Your Conversions Using $capitalized Tactics",
                        "What is $capitalized? Definitions, Strategies, & Growth Hacks",
                        "7 High-Impact $capitalized Checklists to Try Today",
                        "The Smarter $capitalized Strategies for E-commerce Success",
                        "Is $capitalized Dead? Key Growth Trends & Predictions",
                        "How to Automate Yours $capitalized Under 5 Minutes",
                        "How We Scaled Organic Traffic via $capitalized Case Study",
                        "Essential Checklist of $capitalized Optimization Methods",
                        "Affordable $capitalized: Succeeding on a Tight Budget",
                        "Easy Habits to Upgrade Your $capitalized Performance",
                        "Creating a Perfect Content Blueprint for $capitalized Projects",
                        "Why Expert Marketers Always Prioritize $capitalized Assets",
                        "How to Build a Sustainable Career in $capitalized"
                    )

                    // 7. FAQs (5 items with answers)
                    val faqList = listOf(
                        "What is $capitalized?" to "Indeed, $capitalized is a crucial term in this industry segment. Fundamentally, it refers to strategic activities that drive organic branding, authority, and continuous reach.",
                        "Why is $capitalized crucial for web businesses?" to "Without structured $capitalized protocols, obtaining organic traffic and buyer queries becomes exceedingly resource-intensive and expensive.",
                        "How long does it take for $capitalized implementations to rank?" to "Usually, organic improvements materialize within 3 to 6 months depending on local competition parameters, backlink authority, and quality.",
                        "What options are available for automated $capitalized?" to "Core industry choices include Ahrefs, Semrush, Moz, and various customized local API query portals.",
                        "Is it realistic to bootstrap $capitalized on zero budget?" to "Absolutely. Focus on low-difficulty question terms and long-tail alphabet suggestions to accumulate authority safely."
                    )

                    // 8. Content Ideas (Listicles, Articles, FAQs)
                    val contentListicles = listOf(
                        "9 Best $capitalized Frameworks for Speedy Growth",
                        "5 Hidden $capitalized Secret Hacks of Top Authors"
                    )
                    val contentArticles = listOf(
                        "A Deep Dive Into $capitalized Mechanics",
                        "Exploring Hidden Untapped Channels of $capitalized"
                    )
                    val contentFaqs = listOf(
                        "How to trace $capitalized performance reports?",
                        "Is there a safe way to automate tasks without penalties?"
                    )

                    SuggestionResult(
                        seedKeyword = seed,
                        basic = basicMetrics,
                        questions = questionsList,
                        longTail = generatedLongTail,
                        buyerIntent = intentList,
                        comparisons = comparisonsList,
                        blogTitles = blogTemplateList,
                        faqs = faqList,
                        listicles = contentListicles,
                        articles = contentArticles,
                        faqIdeas = contentFaqs
                    )
                }
                _uiState.value = AppUiState.Success(result)
            } catch (e: Exception) {
                _uiState.value = AppUiState.Error(e.localizedMessage ?: "Crawl error")
            }
        }
    }
}

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            val factViewModel: KeywordViewModel = viewModel()
            val isDark by factViewModel.isDarkTheme.collectAsState()

            MyApplicationTheme(darkTheme = isDark, dynamicColor = false) {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    SaeKeywordScreen(viewModel = factViewModel)
                }
            }
        }
    }
}

@OptIn(ExperimentalLayoutApi::class)
@Composable
fun SaeKeywordScreen(viewModel: KeywordViewModel) {
    val uiState by viewModel.uiState.collectAsState()
    val historyList by viewModel.searchHistory.collectAsState(initial = emptyList())
    val isDark by viewModel.isDarkTheme.collectAsState()
    val clipboardManager = LocalClipboardManager.current
    val context = LocalContext.current

    var searchInput by remember { mutableStateOf("") }
    var selectedCountry by remember { mutableStateOf("US") }
    var selectedLang by remember { mutableStateOf("en") }

    // Tab state for categories
    var activeCategoryTab by remember { mutableStateOf(0) }
    val tabTitles = listOf("Alphabet Extensions", "Questions", "Buyer Intent", "Comparisons")

    // Local Helper to copy text to clipboard
    fun doCopy(text: String, label: String) {
        clipboardManager.setText(AnnotatedString(text))
        Toast.makeText(context, "$label copied to clipboard!", Toast.LENGTH_SHORT).show()
    }

    // Export generator
    fun triggerExportShare(results: SuggestionResult, format: String) {
        val dataText = StringBuilder()
        if (format == "csv") {
            dataText.append("Keyword,Type,Search Volume,CPC,Difficulty\n")
            results.basic.forEach { dataText.append("\"${it.keyword}\",\"Basic Autocomplete\",N/A,N/A,N/A\n") }
            results.longTail.forEach { dataText.append("\"${it.keyword}\",\"Alphabet Expansion\",N/A,N/A,N/A\n") }
            results.questions.forEach { dataText.append("\"${it.keyword}\",\"Questions\",N/A,N/A,N/A\n") }
            results.buyerIntent.forEach { dataText.append("\"${it.keyword}\",\"Buyer Intent\",N/A,N/A,N/A\n") }
        } else {
            dataText.append("=== SEO KEYWORDS REPORT: ${results.seedKeyword.uppercase()} ===\n\n")
            dataText.append("BASIC SUGGESTIONS:\n")
            results.basic.forEach { dataText.append("- ${it.keyword}\n") }
            dataText.append("\nEXPANSIONS:\n")
            results.longTail.forEach { dataText.append("- ${it.keyword}\n") }
            dataText.append("\nQUESTIONS:\n")
            results.questions.forEach { dataText.append("- ${it.keyword}\n") }
        }

        val shareIntent = Intent(Intent.ACTION_SEND).apply {
            type = "text/plain"
            putExtra(Intent.EXTRA_SUBJECT, "SEO Search Report - ${results.seedKeyword}")
            putExtra(Intent.EXTRA_TEXT, dataText.toString())
        }
        context.startActivity(Intent.createChooser(shareIntent, "Save or Export Report"))
    }

    Scaffold(
        topBar = {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(MaterialTheme.colorScheme.background)
                    .statusBarsPadding()
                    .padding(horizontal = 16.dp, vertical = 14.dp)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        // Brand Icon 'K' inside a gorgeous Indigo box matching HTML
                        Box(
                            modifier = Modifier
                                .size(40.dp)
                                .clip(RoundedCornerShape(12.dp))
                                .background(BentoIndigo),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                "K",
                                color = Color.White,
                                fontWeight = FontWeight.Bold,
                                fontSize = 18.sp
                            )
                        }

                        Column {
                            Text(
                                text = "KeywordPro",
                                style = MaterialTheme.typography.titleLarge.copy(
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.onBackground,
                                    letterSpacing = (-0.5).sp
                                )
                            )
                        }
                    }

                    // Theme Toggle Button
                    IconButton(
                        onClick = { viewModel.toggleTheme() },
                        modifier = Modifier
                            .size(40.dp)
                            .clip(CircleShape)
                            .background(if (isDark) Color(0xFF1E293B) else Color(0xFFF1F5F9))
                    ) {
                        Icon(
                            imageVector = Icons.Default.Settings,
                            contentDescription = "Toggle Theme",
                            tint = if (isDark) Color(0xFFFBBF24) else Color(0xFF475569),
                            modifier = Modifier.size(20.dp)
                        )
                    }
                }
            }
        }
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
                .background(MaterialTheme.colorScheme.background)
        ) {
            // Search Hero Section (Bento Style Input Board)
            val searchBorderColor = if (isDark) Color(0xFF1E293B) else Color(0xFFE2E8F0)
            val searchBgColor = if (isDark) Color(0xFF161D2F) else Color(0xFFFFFFFF)

            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp)
            ) {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(20.dp))
                        .background(searchBgColor)
                        .border(1.dp, searchBorderColor, RoundedCornerShape(20.dp))
                        .padding(4.dp)
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Box(
                            modifier = Modifier.padding(start = 12.dp, end = 4.dp)
                        ) {
                            Icon(
                                imageVector = Icons.Default.Search,
                                contentDescription = null,
                                tint = Color.Gray,
                                modifier = Modifier.size(20.dp)
                            )
                        }

                        TextField(
                            value = searchInput,
                            onValueChange = { searchInput = it },
                            placeholder = { Text("Enter base keyword (e.g. SEO)", color = Color.Gray, fontSize = 14.sp) },
                            singleLine = true,
                            colors = TextFieldDefaults.colors(
                                focusedContainerColor = Color.Transparent,
                                unfocusedContainerColor = Color.Transparent,
                                disabledContainerColor = Color.Transparent,
                                focusedIndicatorColor = Color.Transparent,
                                unfocusedIndicatorColor = Color.Transparent,
                                disabledIndicatorColor = Color.Transparent
                            ),
                            textStyle = MaterialTheme.typography.bodyMedium.copy(
                                color = MaterialTheme.colorScheme.onBackground
                            ),
                            modifier = Modifier
                                .weight(1f)
                                .minimumInteractiveComponentSize()
                        )

                        if (searchInput.isNotEmpty()) {
                            IconButton(
                                onClick = { searchInput = "" },
                                modifier = Modifier.padding(end = 4.dp)
                            ) {
                                Icon(
                                    imageVector = Icons.Default.Close,
                                    contentDescription = "Clear",
                                    tint = Color.Gray,
                                    modifier = Modifier.size(16.dp)
                                )
                            }
                        }

                        Button(
                            onClick = {
                                if (searchInput.isNotBlank()) {
                                    viewModel.performAnalysis(searchInput, selectedCountry, selectedLang)
                                }
                            },
                            enabled = searchInput.isNotBlank(),
                            shape = RoundedCornerShape(14.dp),
                            colors = ButtonDefaults.buttonColors(
                                containerColor = BentoIndigo,
                                contentColor = Color.White
                            ),
                            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 10.dp),
                            modifier = Modifier.padding(2.dp).minimumInteractiveComponentSize()
                        ) {
                            Text(
                                text = "GENERATE",
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Bold,
                                letterSpacing = 0.5.sp
                            )
                        }
                    }
                }

                Spacer(modifier = Modifier.height(8.dp))

                // Market & Language pills below input
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Box(
                        modifier = Modifier
                            .weight(1f)
                            .clip(RoundedCornerShape(12.dp))
                            .background(if (isDark) Color(0xFF131A26) else Color(0xFFF8FAFC))
                            .border(1.dp, if (isDark) Color(0xFF1E293B) else Color(0xFFE2E8F0), RoundedCornerShape(12.dp))
                            .clickable {
                                selectedCountry = if (selectedCountry == "US") "UK" else "US"
                            }
                            .padding(horizontal = 12.dp, vertical = 8.dp)
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.Center,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Icon(
                                imageVector = Icons.Default.LocationOn,
                                contentDescription = null,
                                tint = BentoIndigo,
                                modifier = Modifier.size(14.dp)
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(
                                text = "Market: $selectedCountry",
                                style = MaterialTheme.typography.bodySmall.copy(
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.onBackground
                                )
                            )
                        }
                    }

                    Box(
                        modifier = Modifier
                            .weight(1f)
                            .clip(RoundedCornerShape(12.dp))
                            .background(if (isDark) Color(0xFF131A26) else Color(0xFFF8FAFC))
                            .border(1.dp, if (isDark) Color(0xFF1E293B) else Color(0xFFE2E8F0), RoundedCornerShape(12.dp))
                            .clickable {
                                selectedLang = if (selectedLang == "en") "es" else "en"
                            }
                            .padding(horizontal = 12.dp, vertical = 8.dp)
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.Center,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Icon(
                                imageVector = Icons.Default.Info,
                                contentDescription = null,
                                tint = BentoIndigo,
                                modifier = Modifier.size(14.dp)
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(
                                text = "Lang: ${selectedLang.uppercase()}",
                                style = MaterialTheme.typography.bodySmall.copy(
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.onBackground
                                )
                            )
                        }
                    }
                }
            }

            // Stateful Content Display (Idle, Loading, Error, Success)
            Box(modifier = Modifier.weight(1f)) {
                when (val state = uiState) {
                    is AppUiState.Idle -> {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(horizontal = 16.dp),
                            verticalArrangement = Arrangement.spacedBy(16.dp),
                            contentPadding = PaddingValues(top = 8.dp, bottom = 24.dp)
                        ) {
                            // Welcome Information Bento
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    colors = CardDefaults.cardColors(containerColor = if (isDark) BentoIndigoDark else BentoIndigoLight),
                                    shape = RoundedCornerShape(24.dp),
                                    border = BorderStroke(1.dp, if (isDark) Color(0xFF1E2445) else Color(0xFFE0E7FF))
                                ) {
                                    Column(modifier = Modifier.padding(20.dp)) {
                                        Icon(
                                            imageVector = Icons.Default.Info,
                                            contentDescription = null,
                                            tint = BentoIndigo,
                                            modifier = Modifier.size(36.dp)
                                        )
                                        Spacer(modifier = Modifier.height(12.dp))
                                        Text(
                                            text = "Let's Get Started",
                                            style = MaterialTheme.typography.titleMedium,
                                            fontWeight = FontWeight.Bold,
                                            color = if (isDark) Color.White else BentoIndigo
                                        )
                                        Spacer(modifier = Modifier.height(6.dp))
                                        Text(
                                            text = "Enter a base keyword above. We'll crawl Google Autocomplete to generate an expert SEO Bento grid loaded with 100+ organized insights, questions, and blog titles.",
                                            style = MaterialTheme.typography.bodySmall,
                                            color = if (isDark) Color(0xFFBCD0FC) else Color(0xFF4F46E5).copy(alpha = 0.8f),
                                            lineHeight = 16.sp
                                        )
                                    }
                                }
                            }

                            // Interactive Mock Category Bento Layout
                            item {
                                Column(
                                    modifier = Modifier.fillMaxWidth(),
                                    verticalArrangement = Arrangement.spacedBy(10.dp)
                                ) {
                                    Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                                        BentoCategoryCard(
                                            modifier = Modifier.weight(1f),
                                            title = "Questions",
                                            count = "Crawl Ideas",
                                            textIcon = "?",
                                            iconColor = BentoQuestionsTextLight,
                                            iconBg = if (isDark) Color(0xFF3E2F20) else Color(0xFFFFEDD5),
                                            bg = if (isDark) BentoQuestionsBgDark else BentoQuestionsBgLight,
                                            textGroupColor = if (isDark) BentoQuestionsTextDark else BentoQuestionsTextLight,
                                            isSelected = false,
                                            onClick = {}
                                        )
                                        BentoCategoryCard(
                                            modifier = Modifier.weight(1f),
                                            title = "Buyer Intent",
                                            count = "Commercial queries",
                                            textIcon = "$",
                                            iconColor = BentoIntentTextLight,
                                            iconBg = if (isDark) Color(0xFF143021) else Color(0xFFDCFCE7),
                                            bg = if (isDark) BentoIntentBgDark else BentoIntentBgLight,
                                            textGroupColor = if (isDark) BentoIntentTextDark else BentoIntentTextLight,
                                            isSelected = false,
                                            onClick = {}
                                        )
                                    }
                                }
                            }

                            // Search History Bento Box
                            if (historyList.isNotEmpty()) {
                                item {
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        shape = RoundedCornerShape(24.dp),
                                        colors = CardDefaults.cardColors(containerColor = if (isDark) BentoSurfaceDark else BentoSurfaceLight),
                                        border = BorderStroke(1.dp, if (isDark) BentoBorderDark else BentoBorderLight)
                                    ) {
                                        Column(modifier = Modifier.padding(18.dp)) {
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.SpaceBetween,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Row(verticalAlignment = Alignment.CenterVertically) {
                                                    Icon(
                                                        imageVector = Icons.Default.List,
                                                        contentDescription = null,
                                                        tint = BentoIndigo,
                                                        modifier = Modifier.size(16.dp)
                                                    )
                                                    Spacer(modifier = Modifier.width(6.dp))
                                                    Text(
                                                        text = "Search Record Library",
                                                        style = MaterialTheme.typography.bodyMedium,
                                                        fontWeight = FontWeight.Bold,
                                                        color = MaterialTheme.colorScheme.onBackground
                                                    )
                                                }
                                                Text(
                                                    text = "Clear All",
                                                    style = MaterialTheme.typography.bodySmall,
                                                    fontWeight = FontWeight.Bold,
                                                    color = Color.Red,
                                                    modifier = Modifier.clickable { viewModel.clearAllHistory() }
                                                )
                                            }
                                            Spacer(modifier = Modifier.height(12.dp))

                                            Column(
                                                verticalArrangement = Arrangement.spacedBy(6.dp)
                                            ) {
                                                historyList.forEach { historyItem ->
                                                    Row(
                                                        modifier = Modifier
                                                            .fillMaxWidth()
                                                            .clip(RoundedCornerShape(12.dp))
                                                            .background(if (isDark) Color(0xFF131A26) else Color(0xFFF1F5F9))
                                                            .clickable {
                                                                searchInput = historyItem.keyword
                                                                viewModel.performAnalysis(historyItem.keyword, selectedCountry, selectedLang)
                                                            }
                                                            .padding(horizontal = 12.dp, vertical = 10.dp),
                                                        horizontalArrangement = Arrangement.SpaceBetween,
                                                        verticalAlignment = Alignment.CenterVertically
                                                    ) {
                                                        Text(
                                                            text = historyItem.keyword,
                                                            style = MaterialTheme.typography.bodySmall,
                                                            fontWeight = FontWeight.SemiBold,
                                                            color = MaterialTheme.colorScheme.onBackground
                                                        )
                                                        IconButton(
                                                            onClick = { viewModel.deleteHistoryItem(historyItem.id) },
                                                            modifier = Modifier.size(20.dp)
                                                        ) {
                                                            Icon(
                                                                imageVector = Icons.Default.Delete,
                                                                contentDescription = "Delete",
                                                                tint = Color.Gray,
                                                                modifier = Modifier.size(14.dp)
                                                            )
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    is AppUiState.Loading -> {
                        Column(
                            modifier = Modifier.fillMaxSize(),
                            verticalArrangement = Arrangement.Center,
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(48.dp),
                                strokeWidth = 4.dp,
                                color = BentoIndigo
                            )
                            Spacer(modifier = Modifier.height(16.dp))
                            Text(
                                text = "SCRAPING GOOGLE AUTOCOMPLETE...",
                                style = MaterialTheme.typography.bodySmall.copy(
                                    fontWeight = FontWeight.ExtraBold,
                                    letterSpacing = 1.sp,
                                    color = BentoIndigo
                                )
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "Assembling your bento grid interface",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.6f)
                            )
                        }
                    }

                    is AppUiState.Error -> {
                        Column(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(24.dp),
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.Center
                        ) {
                            Icon(
                                imageVector = Icons.Default.Warning,
                                contentDescription = null,
                                tint = Color.Red,
                                modifier = Modifier.size(48.dp)
                            )
                            Spacer(modifier = Modifier.height(12.dp))
                            Text(
                                text = "Crawl Interrupted",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold,
                                color = Color.Red
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = state.message,
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.6f),
                                textAlign = TextAlign.Center
                            )
                        }
                    }

                    is AppUiState.Success -> {
                        val result = state.result
                        val totalCount = result.basic.size + result.longTail.size + result.questions.size + result.buyerIntent.size + result.comparisons.size

                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(horizontal = 16.dp),
                            verticalArrangement = Arrangement.spacedBy(16.dp),
                            contentPadding = PaddingValues(top = 8.dp, bottom = 24.dp)
                        ) {
                            // Bento Grid Row 1: Summary Banner Card
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    colors = CardDefaults.cardColors(containerColor = if (isDark) BentoIndigoDark else BentoIndigoLight),
                                    shape = RoundedCornerShape(26.dp),
                                    border = BorderStroke(1.dp, if (isDark) Color(0xFF1E2445) else Color(0xFFE0E7FF))
                                ) {
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(18.dp),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Column(modifier = Modifier.weight(1f)) {
                                            Text(
                                                text = "FOUND FOR: \"${result.seedKeyword.uppercase()}\"",
                                                fontSize = 10.sp,
                                                fontWeight = FontWeight.Bold,
                                                color = if (isDark) Color(0xFF818CF8) else BentoIndigo,
                                                letterSpacing = 0.5.sp
                                            )
                                            Spacer(modifier = Modifier.height(2.dp))
                                            Text(
                                                text = "$totalCount Suggestions",
                                                style = MaterialTheme.typography.titleLarge,
                                                fontWeight = FontWeight.ExtraBold,
                                                color = if (isDark) Color.White else Color(0xFF1E1B4B)
                                            )
                                        }

                                        Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                                            IconButton(
                                                onClick = { triggerExportShare(result, "csv") },
                                                modifier = Modifier
                                                    .size(40.dp)
                                                    .clip(CircleShape)
                                                    .background(if (isDark) Color(0xFF1A1F3C) else Color(0xFFFFFFFF))
                                            ) {
                                                Icon(
                                                    imageVector = Icons.Default.Share,
                                                    contentDescription = "Export CSV",
                                                    tint = BentoIndigo,
                                                    modifier = Modifier.size(18.dp)
                                                )
                                            }
                                        }
                                    }
                                }
                            }

                            // Bento Grid Row 2: Visual Metics Squares Grid (2x2 grid buttons)
                            item {
                                Column(
                                    modifier = Modifier.fillMaxWidth(),
                                    verticalArrangement = Arrangement.spacedBy(10.dp)
                                ) {
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                                    ) {
                                        BentoCategoryCard(
                                            modifier = Modifier.weight(1f),
                                            title = "Questions",
                                            count = "${result.questions.size} queries",
                                            textIcon = "?",
                                            iconColor = BentoQuestionsTextLight,
                                            iconBg = if (isDark) Color(0xFF3E2F20) else Color(0xFFFFEDD5),
                                            bg = if (isDark) BentoQuestionsBgDark else BentoQuestionsBgLight,
                                            textGroupColor = if (isDark) BentoQuestionsTextDark else BentoQuestionsTextLight,
                                            isSelected = activeCategoryTab == 1,
                                            onClick = { activeCategoryTab = 1 }
                                        )

                                        BentoCategoryCard(
                                            modifier = Modifier.weight(1f),
                                            title = "Buyer Intent",
                                            count = "${result.buyerIntent.size} valuable",
                                            textIcon = "$",
                                            iconColor = BentoIntentTextLight,
                                            iconBg = if (isDark) Color(0xFF143021) else Color(0xFFDCFCE7),
                                            bg = if (isDark) BentoIntentBgDark else BentoIntentBgLight,
                                            textGroupColor = if (isDark) BentoIntentTextDark else BentoIntentTextLight,
                                            isSelected = activeCategoryTab == 2,
                                            onClick = { activeCategoryTab = 2 }
                                        )
                                    }

                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                                    ) {
                                        BentoCategoryCard(
                                            modifier = Modifier.weight(1f),
                                            title = "Alphabet Ext",
                                            count = "${result.longTail.size} variants",
                                            textIcon = "A",
                                            iconColor = BentoAlphabetTextLight,
                                            iconBg = if (isDark) Color(0xFF0F3232) else Color(0xFFCCFBF1),
                                            bg = if (isDark) BentoAlphabetBgDark else BentoAlphabetBgLight,
                                            textGroupColor = if (isDark) BentoAlphabetTextDark else BentoAlphabetTextLight,
                                            isSelected = activeCategoryTab == 0,
                                            onClick = { activeCategoryTab = 0 }
                                        )

                                        BentoCategoryCard(
                                            modifier = Modifier.weight(1f),
                                            title = "Comparisons",
                                            count = "${result.comparisons.size} listings",
                                            textIcon = "VS",
                                            iconColor = BentoComparisonsTextLight,
                                            iconBg = if (isDark) Color(0xFF2D1B4E) else Color(0xFFF3E8FF),
                                            bg = if (isDark) BentoComparisonsBgDark else BentoComparisonsBgLight,
                                            textGroupColor = if (isDark) BentoComparisonsTextDark else BentoComparisonsTextLight,
                                            isSelected = activeCategoryTab == 3,
                                            onClick = { activeCategoryTab = 3 }
                                        )
                                    }
                                }
                            }

                            // Bento Grid Row 3: Active Category Swapped List Detail
                            val activeListItems = when (activeCategoryTab) {
                                0 -> result.longTail
                                1 -> result.questions
                                2 -> result.buyerIntent
                                else -> result.comparisons
                            }

                            val themeAccentColor = when (activeCategoryTab) {
                                0 -> if (isDark) BentoAlphabetTextDark else BentoAlphabetTextLight
                                1 -> if (isDark) BentoQuestionsTextDark else BentoQuestionsTextLight
                                2 -> if (isDark) BentoIntentTextDark else BentoIntentTextLight
                                else -> if (isDark) BentoComparisonsTextDark else BentoComparisonsTextLight
                            }

                            val themeAccentBg = when (activeCategoryTab) {
                                0 -> if (isDark) BentoAlphabetBgDark else BentoAlphabetBgLight
                                1 -> if (isDark) BentoQuestionsBgDark else BentoQuestionsBgLight
                                2 -> if (isDark) BentoIntentBgDark else BentoIntentBgLight
                                else -> if (isDark) BentoComparisonsBgDark else BentoComparisonsBgLight
                            }

                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(26.dp),
                                    colors = CardDefaults.cardColors(containerColor = if (isDark) BentoSurfaceDark else BentoSurfaceLight),
                                    border = BorderStroke(1.dp, if (isDark) BentoBorderDark else BentoBorderLight)
                                ) {
                                    Column(modifier = Modifier.padding(18.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Row(
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Box(
                                                    modifier = Modifier
                                                        .size(8.dp)
                                                        .clip(CircleShape)
                                                        .background(themeAccentColor)
                                                )
                                                Spacer(modifier = Modifier.width(8.dp))
                                                Text(
                                                    text = tabTitles[activeCategoryTab],
                                                    style = MaterialTheme.typography.bodyMedium,
                                                    fontWeight = FontWeight.Bold,
                                                    color = MaterialTheme.colorScheme.onBackground
                                                )
                                            }

                                            Text(
                                                text = "Copy List",
                                                style = MaterialTheme.typography.labelSmall,
                                                fontWeight = FontWeight.Bold,
                                                color = BentoIndigo,
                                                modifier = Modifier.clickable {
                                                    val joined = activeListItems.joinToString("\n") { it.keyword }
                                                    doCopy(joined, "Active List")
                                                }
                                            )
                                        }

                                        Spacer(modifier = Modifier.height(12.dp))

                                        Column(
                                            verticalArrangement = Arrangement.spacedBy(6.dp)
                                        ) {
                                            activeListItems.forEach { metric ->
                                                Row(
                                                    modifier = Modifier
                                                        .fillMaxWidth()
                                                        .clip(RoundedCornerShape(12.dp))
                                                        .background(if (isDark) Color(0xFF131A26) else Color(0xFFF8FAFC))
                                                        .clickable { doCopy(metric.keyword, "Keyword") }
                                                        .padding(horizontal = 12.dp, vertical = 10.dp),
                                                    verticalAlignment = Alignment.CenterVertically,
                                                    horizontalArrangement = Arrangement.SpaceBetween
                                                ) {
                                                    Row(
                                                        verticalAlignment = Alignment.CenterVertically,
                                                        modifier = Modifier.weight(1f)
                                                    ) {
                                                        Box(
                                                            modifier = Modifier
                                                                .size(18.dp)
                                                                .clip(RoundedCornerShape(4.dp))
                                                                .background(themeAccentBg),
                                                            contentAlignment = Alignment.Center
                                                        ) {
                                                            Icon(
                                                                imageVector = Icons.Default.Done,
                                                                contentDescription = null,
                                                                tint = themeAccentColor,
                                                                modifier = Modifier.size(10.dp)
                                                            )
                                                        }
                                                        Spacer(modifier = Modifier.width(8.dp))
                                                        Text(
                                                            text = metric.keyword,
                                                            style = MaterialTheme.typography.bodySmall,
                                                            fontWeight = FontWeight.SemiBold,
                                                            color = MaterialTheme.colorScheme.onBackground
                                                        )
                                                    }

                                                    Icon(
                                                        imageVector = Icons.Default.Share,
                                                        contentDescription = "Copy Item",
                                                        tint = Color.Gray,
                                                        modifier = Modifier.size(12.dp)
                                                    )
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // Bento Grid Row 4: Top Blog Titles suggestions with pill "Auto-Generated"
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(26.dp),
                                    colors = CardDefaults.cardColors(containerColor = if (isDark) BentoSurfaceDark else BentoSurfaceLight),
                                    border = BorderStroke(1.dp, if (isDark) BentoBorderDark else BentoBorderLight)
                                ) {
                                    Column(modifier = Modifier.padding(18.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Row(
                                                verticalAlignment = Alignment.CenterVertically,
                                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                                            ) {
                                                Box(
                                                    modifier = Modifier
                                                        .size(6.dp)
                                                        .clip(CircleShape)
                                                        .background(BentoIndigo)
                                                )
                                                Text(
                                                    text = "Top Blog Titles",
                                                    style = MaterialTheme.typography.bodyMedium,
                                                    fontWeight = FontWeight.Bold,
                                                    color = MaterialTheme.colorScheme.onBackground
                                                )
                                            }

                                            Box(
                                                modifier = Modifier
                                                    .clip(RoundedCornerShape(6.dp))
                                                    .background(if (isDark) Color(0xFF1E293B) else Color(0xFFF1F5F9))
                                                    .padding(horizontal = 6.dp, vertical = 2.dp)
                                            ) {
                                                Text(
                                                    text = "AUTO-GENERATED",
                                                    fontSize = 8.sp,
                                                    fontWeight = FontWeight.ExtraBold,
                                                    color = Color.Gray
                                                )
                                            }
                                        }

                                        Spacer(modifier = Modifier.height(12.dp))

                                        Column(
                                            verticalArrangement = Arrangement.spacedBy(8.dp)
                                        ) {
                                            result.blogTitles.take(8).forEach { title ->
                                                Row(
                                                    modifier = Modifier
                                                        .fillMaxWidth()
                                                        .clip(RoundedCornerShape(12.dp))
                                                        .background(if (isDark) Color(0xFF131A26) else Color(0xFFF8FAFC))
                                                        .clickable { doCopy(title, "Blog Title") }
                                                        .padding(12.dp),
                                                    verticalAlignment = Alignment.Top
                                                ) {
                                                    Icon(
                                                        imageVector = Icons.Default.Star,
                                                        contentDescription = null,
                                                        tint = BentoIndigo,
                                                        modifier = Modifier.size(14.dp).padding(top = 2.dp)
                                                    )
                                                    Spacer(modifier = Modifier.width(8.dp))
                                                    Text(
                                                        text = title,
                                                        style = MaterialTheme.typography.bodySmall,
                                                        fontWeight = FontWeight.SemiBold,
                                                        color = MaterialTheme.colorScheme.onBackground,
                                                        lineHeight = 16.sp,
                                                        modifier = Modifier.weight(1f)
                                                    )
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // Bento Grid Row 5: Suggestions and Metrics Table View (API Ready engine)
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(26.dp),
                                    colors = CardDefaults.cardColors(containerColor = if (isDark) BentoSurfaceDark else BentoSurfaceLight),
                                    border = BorderStroke(1.dp, if (isDark) BentoBorderDark else BentoBorderLight)
                                ) {
                                    Column(modifier = Modifier.padding(18.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text(
                                                text = "Suggestions & Metrics",
                                                style = MaterialTheme.typography.bodyMedium,
                                                fontWeight = FontWeight.Bold,
                                                color = MaterialTheme.colorScheme.onBackground
                                            )
                                            Box(
                                                modifier = Modifier
                                                    .clip(RoundedCornerShape(6.dp))
                                                    .background(if (isDark) Color(0xFF143021) else Color(0xFFDCFCE7))
                                                    .padding(horizontal = 6.dp, vertical = 2.dp)
                                            ) {
                                                Text(
                                                    text = "API READY ENGINE",
                                                    fontSize = 8.sp,
                                                    fontWeight = FontWeight.ExtraBold,
                                                    color = if (isDark) BentoIntentTextDark else BentoIntentTextLight
                                                )
                                            }
                                        }

                                        Spacer(modifier = Modifier.height(12.dp))

                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text("Keyword", style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, color = Color.Gray, modifier = Modifier.weight(2f))
                                            Text("Vol", style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, color = Color.Gray, modifier = Modifier.weight(1f), textAlign = TextAlign.End)
                                            Text("CPC", style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, color = Color.Gray, modifier = Modifier.weight(1f), textAlign = TextAlign.End)
                                            Text("KD", style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, color = Color.Gray, modifier = Modifier.weight(1.2f), textAlign = TextAlign.End)
                                        }

                                        HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp), color = if (isDark) Color(0xFF1E293B) else Color(0xFFF1F5F9))

                                        Column(
                                            verticalArrangement = Arrangement.spacedBy(6.dp)
                                        ) {
                                            result.basic.take(6).forEach { metric ->
                                                Row(
                                                    modifier = Modifier
                                                        .fillMaxWidth()
                                                        .clickable { doCopy(metric.keyword, "Keyword") }
                                                        .padding(vertical = 4.dp),
                                                    horizontalArrangement = Arrangement.SpaceBetween,
                                                    verticalAlignment = Alignment.CenterVertically
                                                ) {
                                                    Text(
                                                        text = metric.keyword,
                                                        style = MaterialTheme.typography.bodySmall,
                                                        fontWeight = FontWeight.SemiBold,
                                                        maxLines = 1,
                                                        overflow = TextOverflow.Ellipsis,
                                                        modifier = Modifier.weight(2f),
                                                        color = MaterialTheme.colorScheme.onBackground
                                                    )
                                                    Text(metric.searchVolume, style = MaterialTheme.typography.bodySmall, color = Color.Gray, modifier = Modifier.weight(1f), textAlign = TextAlign.End)
                                                    Text(metric.cpc, style = MaterialTheme.typography.bodySmall, color = Color.Gray, modifier = Modifier.weight(1f), textAlign = TextAlign.End)
                                                    Text(metric.difficulty, style = MaterialTheme.typography.bodySmall, color = Color.Gray, modifier = Modifier.weight(1.2f), textAlign = TextAlign.End)
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // Bento Grid Row 6: FAQ Accordion Bento Block
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(26.dp),
                                    colors = CardDefaults.cardColors(containerColor = if (isDark) BentoSurfaceDark else BentoSurfaceLight),
                                    border = BorderStroke(1.dp, if (isDark) BentoBorderDark else BentoBorderLight)
                                ) {
                                    Column(modifier = Modifier.padding(18.dp)) {
                                        Text(
                                            text = "FAQ Schema Generator",
                                            style = MaterialTheme.typography.bodyMedium,
                                            fontWeight = FontWeight.Bold,
                                            color = MaterialTheme.colorScheme.onBackground
                                        )
                                        Spacer(modifier = Modifier.height(12.dp))

                                        Column(
                                            verticalArrangement = Arrangement.spacedBy(8.dp)
                                        ) {
                                            result.faqs.forEach { pair ->
                                                FaqItem(pair = pair, isDark = isDark)
                                            }
                                        }
                                    }
                                }
                            }

                            // Bento Grid Row 7: Content Ideas Card Grid (Listicles & Draft Outlines)
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(26.dp),
                                    colors = CardDefaults.cardColors(containerColor = if (isDark) BentoSurfaceDark else BentoSurfaceLight),
                                    border = BorderStroke(1.dp, if (isDark) BentoBorderDark else BentoBorderLight)
                                ) {
                                    Column(modifier = Modifier.padding(18.dp)) {
                                        Text(
                                            text = "Strategic Content Briefings",
                                            style = MaterialTheme.typography.bodyMedium,
                                            fontWeight = FontWeight.Bold,
                                            color = MaterialTheme.colorScheme.onBackground
                                        )
                                        Spacer(modifier = Modifier.height(12.dp))

                                        // Listicles Column
                                        Text(
                                            text = "Listicles Format ideas",
                                            style = MaterialTheme.typography.labelSmall,
                                            fontWeight = FontWeight.ExtraBold,
                                            color = BentoIndigo
                                        )
                                        Spacer(modifier = Modifier.height(4.dp))
                                        result.listicles.forEach { ideatxt ->
                                            Box(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .padding(vertical = 4.dp)
                                                    .clip(RoundedCornerShape(8.dp))
                                                    .background(if (isDark) Color(0xFF131A26) else Color(0xFFF1F5F9))
                                                    .padding(8.dp)
                                            ) {
                                                Text(ideatxt, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onBackground)
                                            }
                                        }

                                        Spacer(modifier = Modifier.height(12.dp))

                                        // Articles Column
                                        Text(
                                            text = "Article Drafts Outlines",
                                            style = MaterialTheme.typography.labelSmall,
                                            fontWeight = FontWeight.ExtraBold,
                                            color = if (isDark) BentoIntentTextDark else BentoIntentTextLight
                                        )
                                        Spacer(modifier = Modifier.height(4.dp))
                                        result.articles.forEach { ideatxt ->
                                            Box(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .padding(vertical = 4.dp)
                                                    .clip(RoundedCornerShape(8.dp))
                                                    .background(if (isDark) Color(0xFF131A26) else Color(0xFFF1F5F9))
                                                    .padding(8.dp)
                                            ) {
                                                Text(ideatxt, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onBackground)
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun BentoCategoryCard(
    modifier: Modifier = Modifier,
    title: String,
    count: String,
    textIcon: String,
    iconColor: Color,
    iconBg: Color,
    bg: Color,
    textGroupColor: Color,
    isSelected: Boolean,
    onClick: () -> Unit
) {
    val borderStroke = if (isSelected) BorderStroke(2.dp, BentoIndigo) else BorderStroke(1.dp, Color.Transparent)

    Card(
        modifier = modifier
            .fillMaxWidth()
            .height(96.dp)
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(20.dp),
        border = borderStroke,
        colors = CardDefaults.cardColors(containerColor = bg)
    ) {
        Row(
            modifier = Modifier
                .fillMaxSize()
                .padding(14.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.Center
            ) {
                Text(
                    text = title,
                    style = MaterialTheme.typography.bodyMedium.copy(
                        fontWeight = FontWeight.Bold,
                        color = textGroupColor
                    ),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                Spacer(modifier = Modifier.height(3.dp))
                Text(
                    text = count,
                    fontSize = 10.sp,
                    fontWeight = FontWeight.ExtraBold,
                    color = textGroupColor.copy(alpha = 0.75f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }

            Box(
                modifier = Modifier
                    .size(36.dp)
                    .clip(RoundedCornerShape(10.dp))
                    .background(iconBg),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = textIcon,
                    fontWeight = FontWeight.ExtraBold,
                    fontSize = 13.sp,
                    color = iconColor
                )
            }
        }
    }
}

@Composable
fun FaqItem(pair: Pair<String, String>, isDark: Boolean) {
    var isExpanded by remember { mutableStateOf(false) }
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(14.dp))
            .background(if (isDark) Color(0xFF131A26) else Color(0xFFF8FAFC))
            .border(1.dp, if (isDark) Color(0xFF1E293B) else Color(0xFFE2E8F0), RoundedCornerShape(14.dp))
            .clickable { isExpanded = !isExpanded }
            .padding(12.dp)
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = pair.first,
                style = MaterialTheme.typography.bodySmall,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onBackground,
                modifier = Modifier.weight(1f)
            )
            Text(
                text = if (isExpanded) "▲" else "▼",
                color = BentoIndigo,
                fontWeight = FontWeight.Bold,
                fontSize = 12.sp,
                modifier = Modifier.padding(horizontal = 4.dp)
            )
        }
        if (isExpanded) {
            Spacer(modifier = Modifier.height(6.dp))
            Text(
                text = pair.second,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.7f),
                fontWeight = FontWeight.Medium,
                lineHeight = 16.sp
            )
        }
    }
}

@Composable
fun Greeting(name: String, modifier: Modifier = Modifier) {
    Text(text = "Hello $name!", modifier = modifier)
}
