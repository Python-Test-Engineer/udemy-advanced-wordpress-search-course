<?php
/**
 * Plugin Name: ✅ 01 BM25 ELI5
 * Description: Comprehensive ELI5 (Explain Like I'm 5) guide to BM25 ranking algorithm with step-by-step calculations, interactive examples, and visual explanations
 * Version: 1.1
 * Author: WP AI Framework
 * Text Domain: bm25-explainer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'bm25_explainer_menu');

function bm25_explainer_menu() {
    add_menu_page(
        'BM25 ELI5Ranking Guide',
        '01 BM25 ELI5',
        'manage_options',
        'bm25-explainer',
        'bm25_explainer_page',
        'dashicons-chart-line',
        3.1
    );
}

// Main admin page
function bm25_explainer_page() {
    ?>
    <div class="wrap">
        <h1>🎯 Understanding BM25 and Ranking Algorithms</h1>
        <p style="font-size: 1.5rem;">
            Ever wonder how search engines decide which results to show you first?<br> Let's explore BM25, 
            one of the most important ranking algorithms used by search engines like Elasticsearch and many others!
        </p>

        <!-- Section 1: What is Ranking? -->
        <div class="bm25-section">
            <h2>📊 What is Ranking? (The Basics)</h2>
            <div class="bm25-card">
                <h3>Think of it like a library...</h3>
                <p>Imagine you walk into a library and ask: <strong>"I need books about cats"</strong></p>
                
                <p>The librarian could:</p>
                <ul>
                    <li>❌ Give you EVERY book that mentions "cats" (thousands of books!)</li>
                    <li>✅ Give you the MOST RELEVANT books about cats, sorted by importance</li>
                </ul>
                
                <p><strong>Ranking is how we decide which books are "most relevant"</strong></p>
                
                <div class="example-box">
                    <h4>🔍 Example: Searching for "chocolate cake recipe"</h4>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th style="background:#ffffff;font-weight:bold;font-size:1.25rem">Document</th>
                                <th style="background:#ffffff;font-weight:bold;font-size:1.25rem">Why It Ranks High/Low</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background: #d4edda;">
                                <td><strong>Rank #1:</strong> "Ultimate Chocolate Cake Recipe"</td>
                                <td>✅ Has ALL your search words + they're in the title</td>
                            </tr>
                            <tr style="background: #fff3cd;">
                                <td><strong>Rank #2:</strong> "Best Chocolate Recipes: Cookies, Cakes, and More"</td>
                                <td>⚠️ Has your words but also talks about other things</td>
                            </tr>
                            <tr style="background: #f8d7da;">
                                <td><strong>Rank #10:</strong> "Vanilla Cake Recipe (mentions chocolate once)"</td>
                                <td>❌ Only mentions "chocolate" briefly, mostly about vanilla</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 2: Simple Ranking Methods -->
        <div class="bm25-section">
            <h2>🔢 Simple Ranking: Counting Words</h2>
            <div class="bm25-card">
                <h3>The Naive Approach: Just Count!</h3>
                <p>The simplest way to rank documents is to count how many times your search words appear.</p>
                
                <div class="example-box">
                    <h4>Search query: "pizza"</h4>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th style="background:#ffffff;font-weight:bold;font-size:1.25rem">Document</th>
                                <th style="background:#ffffff;font-weight:bold;font-size:1.25rem">Times "pizza" appears</th>
                                <th style="background:#ffffff;font-weight:bold;font-size:1.25rem">Rank</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Doc A: "I love pizza! Pizza is great. Pizza for dinner!"</td>
                                <td>3</td>
                                <td>🥇 #1</td>
                            </tr>
                            <tr>
                                <td>Doc B: "Pizza recipe: make delicious pizza"</td>
                                <td>2</td>
                                <td>🥈 #2</td>
                            </tr>
                            <tr>
                                <td>Doc C: "My favorite food is pizza"</td>
                                <td>1</td>
                                <td>🥉 #3</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="warning-box">
                    <h4>⚠️ The Problem with Simple Counting</h4>
                    <p>What if someone writes "pizza pizza pizza pizza pizza" 100 times? They'd rank #1 even though it's spam!</p>
                    <p><strong>We need smarter ranking. Enter: BM25!</strong></p>
                </div>
            </div>
        </div>

        <!-- Section 3: What is BM25? -->
        <div class="bm25-section">
            <h2>🚀 What is BM25?</h2>
            <div class="bm25-card">
                <h3>BM25 = "Best Match 25"</h3>
                <p>BM25 is a smart ranking formula that considers THREE important things:</p>
                
                <ol style="font-size: 16px; line-height: 2;">
                    <li><strong>How often does the word appear?</strong> (Term Frequency)</li>
                    <li><strong>How rare is this word?</strong> (Inverse Document Frequency)</li>
                    <li><strong>How long is the document?</strong> (Document Length Normalization)</li>
                </ol>

                <div class="info-box">
                    <h4>🎓 Why "25"?</h4>
                    <p>It's the 25th variation of the "Best Match" formula family. Previous versions were BM1, BM11, BM15, etc. 
                    BM25 became the most popular because it works really well!</p>
                </div>
            </div>
        </div>

        <!-- Section 4: The Three Components -->
        <div class="bm25-section">
            <h2>🔍 The Three Magic Ingredients of BM25</h2>
            
            <!-- Component 1: Term Frequency -->
            <div class="bm25-card">
                <h3>1️⃣ Term Frequency (TF): How Often Does the Word Appear?</h3>
                <p><strong>The idea:</strong> If a document mentions your search word more times, it's probably more relevant.</p>
                
                <div class="example-box">
                    <h4>Example: Searching for "javascript"</h4>
                    <ul>
                        <li>Doc A mentions "javascript" 1 time → Less relevant</li>
                        <li>Doc B mentions "javascript" 5 times → More relevant</li>
                        <li>Doc C mentions "javascript" 50 times → Probably spam or overly repetitive</li>
                    </ul>
                </div>

                <p><strong>BM25's clever trick:</strong> It uses <em>saturation</em>. This means:</p>
                <ul>
                    <li>1 → 5 mentions = Big boost! 📈</li>
                    <li>5 → 10 mentions = Smaller boost 📊</li>
                    <li>10 → 50 mentions = Tiny boost (almost no difference) 📉</li>
                </ul>

                <p>This prevents spam documents from dominating just by repeating words endlessly!</p>
            </div>

            <!-- Component 2: IDF -->
            <div class="bm25-card">
                <h3>2️⃣ Inverse Document Frequency (IDF): How Rare Is the Word?</h3>
                <p><strong>The idea:</strong> Rare words are more important than common words.</p>
                
                <div class="example-box">
                    <h4>🤔 Think about it this way...</h4>
                    <p>You're searching for: <strong>"quantum physics"</strong></p>
                    <ul>
                        <li><strong>"quantum"</strong> → Appears in 100 documents → Rare! Very informative! 🌟</li>
                        <li><strong>"physics"</strong> → Appears in 10,000 documents → Common! Less informative! ⭐</li>
                    </ul>
                    
                    <p>Now compare to: <strong>"the physics"</strong></p>
                    <ul>
                        <li><strong>"the"</strong> → Appears in almost EVERY document → Nearly useless! 💤</li>
                        <li><strong>"physics"</strong> → Still appears in 10,000 documents → Somewhat useful ⭐</li>
                    </ul>
                </div>

                <div class="info-box">
                    <h4>📐 The Math (Simplified)</h4>
                    <p>IDF = log(Total Documents / Documents containing word)</p>
                    <ul>
                        <li>If EVERYONE uses the word → IDF is LOW (close to 0)</li>
                        <li>If NOBODY uses the word → IDF is HIGH (big number)</li>
                    </ul>
                    <p><strong>Translation:</strong> Rare words get bonus points! 🎁</p>
                </div>
            </div>

            <!-- Component 3: Document Length -->
            <div class="bm25-card">
                <h3>3️⃣ Document Length Normalization: Size Matters!</h3>
                <p><strong>The problem:</strong> Long documents naturally mention words more times.</p>
                
                <div class="example-box">
                    <h4>Example: Searching for "machine learning"</h4>
                    <p><strong>Document A:</strong> Short blog post (200 words)</p>
                    <ul><li>Mentions "machine learning" 3 times</li></ul>
                    
                    <p><strong>Document B:</strong> Entire textbook (50,000 words)</p>
                    <ul><li>Mentions "machine learning" 50 times</li></ul>
                    
                    <p><strong>Question:</strong> Is Document B really 16x more relevant? Or is it just 250x longer?</p>
                </div>

                <p><strong>BM25's solution:</strong> It adjusts scores based on document length.</p>
                <ul>
                    <li>Short document with many mentions → Gets a boost! 🚀</li>
                    <li>Long document with many mentions → Gets penalized slightly 📉</li>
                </ul>

                <div class="info-box">
                    <h4>🎯 Real-world Impact</h4>
                    <p>This prevents giant documents (like legal terms or Wikipedia articles) from always ranking #1 
                    just because they're long and mention everything!</p>
                </div>
            </div>
        </div>

        <!-- Section 5: BM25 Formula -->
        <div class="bm25-section">
            <h2>🧮 The BM25 Formula (Don't Panic!)</h2>
            <div class="bm25-card">
                <div class="formula-box">
                    <h3>The Official Formula:</h3>
                    <pre style="background: #f5f5f5; padding: 20px; overflow-x: auto; border-left: 4px solid #2271b1;">
Score(D,Q) = Σ IDF(qi) × (f(qi,D) × (k1 + 1)) / (f(qi,D) + k1 × (1 - b + b × |D| / avgdl))
                    </pre>
                </div>

                <h3>😰 Looks scary? Let's break it into smaller pieces!</h3>
                
                <div class="info-box">
                    <h4>🧩 The Formula Has THREE Main Parts:</h4>
                    <ol style="font-size: 15px; line-height: 2;">
                        <li><strong>IDF Component:</strong> <code>log((N - n + 0.5) / (n + 0.5))</code> — How rare is this word?</li>
                        <li><strong>TF Component (Numerator):</strong> <code>f(qi,D) × (k1 + 1)</code> — How often does it appear?</li>
                        <li><strong>Normalization (Denominator):</strong> <code>f(qi,D) + k1 × (1 - b + b × |D|/avgdl)</code> — Adjust for document length</li>
                    </ol>
                    <p><strong>Final formula:</strong> IDF × (TF Numerator / Normalization Denominator)</p>
                </div>

                <div class="translation-box">
                    <h4>What each symbol means:</h4>
                    <table class="widefat">
                        <tr>
                            <td><strong>D</strong></td>
                            <td>The Document we're scoring</td>
                        </tr>
                        <tr>
                            <td><strong>Q</strong></td>
                            <td>The Query (search terms)</td>
                        </tr>
                        <tr>
                            <td><strong>Σ</strong></td>
                            <td>"Sum up" - do this for each word in the query</td>
                        </tr>
                        <tr>
                            <td><strong>N</strong></td>
                            <td>Total number of documents in the collection</td>
                        </tr>
                        <tr>
                            <td><strong>n</strong></td>
                            <td>Number of documents containing this query word</td>
                        </tr>
                        <tr>
                            <td><strong>IDF(qi)</strong></td>
                            <td>How rare is this query word? Formula: log((N - n + 0.5) / (n + 0.5))</td>
                        </tr>
                        <tr>
                            <td><strong>f(qi,D)</strong></td>
                            <td>How many times does the word appear in this document? (Term Frequency)</td>
                        </tr>
                        <tr>
                            <td><strong>|D|</strong></td>
                            <td>Length of this document (word count)</td>
                        </tr>
                        <tr>
                            <td><strong>avgdl</strong></td>
                            <td>Average document length across ALL documents in collection</td>
                        </tr>
                        <tr>
                            <td><strong>k1</strong></td>
                            <td>Controls term frequency saturation (typically 1.2-2.0, default: 1.5)</td>
                        </tr>
                        <tr>
                            <td><strong>b</strong></td>
                            <td>Controls length normalization strength (0 to 1, default: 0.75)</td>
                        </tr>
                    </table>
                </div>

                <h3>🔬 Understanding Each Component:</h3>
                
                <div class="calculation-box">
                    <h4>Part 1: IDF (Inverse Document Frequency)</h4>
                    <pre>IDF = log((N - n + 0.5) / (n + 0.5))</pre>
                    <p><strong>Why the +0.5?</strong> This is called "smoothing" - it prevents division by zero and handles edge cases when a word appears in all or no documents.</p>
                    <p><strong>What it does:</strong></p>
                    <ul>
                        <li>Word appears in MANY documents → IDF is LOW (close to 0)</li>
                        <li>Word appears in FEW documents → IDF is HIGH (positive number)</li>
                        <li>Word appears in MORE than half the documents → IDF can be NEGATIVE!</li>
                    </ul>
                </div>

                <div class="calculation-box">
                    <h4>Part 2: TF Saturation (The Clever Part!)</h4>
                    <pre>TF_saturated = (f × (k1 + 1)) / (f + k1 × B)</pre>
                    <p><strong>Where B = length normalization factor = (1 - b + b × |D|/avgdl)</strong></p>
                    <p><strong>What this does:</strong></p>
                    <ul>
                        <li>When f=0: Score = 0 (word not in document)</li>
                        <li>When f=1: Score starts climbing</li>
                        <li>As f increases: Score approaches but NEVER exceeds (k1 + 1)</li>
                    </ul>
                    <p><strong>This is "saturation" in action!</strong> Even if a word appears 1000 times, the TF score is capped.</p>
                </div>

                <div class="calculation-box">
                    <h4>Part 3: Length Normalization Factor (B)</h4>
                    <pre>B = 1 - b + b × (|D| / avgdl)</pre>
                    <p><strong>Examples with b=0.75 and avgdl=100:</strong></p>
                    <ul>
                        <li>Short doc (50 words): B = 1 - 0.75 + 0.75 × (50/100) = 0.25 + 0.375 = <strong>0.625</strong></li>
                        <li>Average doc (100 words): B = 1 - 0.75 + 0.75 × (100/100) = 0.25 + 0.75 = <strong>1.0</strong></li>
                        <li>Long doc (200 words): B = 1 - 0.75 + 0.75 × (200/100) = 0.25 + 1.5 = <strong>1.75</strong></li>
                    </ul>
                    <p><strong>Effect:</strong> Smaller B = higher score boost. So shorter documents get a boost!</p>
                </div>

                <h3>In Plain English:</h3>
                <div class="plain-english-box">
                    <p style="font-size: 16px; line-height: 1.8;">
                        "For each word in the search query:<br><br>
                        1️⃣ <strong>Check how RARE it is</strong> (IDF) — rare words are more important<br>
                        2️⃣ <strong>Check how OFTEN it appears</strong> (TF) — but with diminishing returns<br>
                        3️⃣ <strong>Adjust for document LENGTH</strong> — short focused docs beat long rambling ones<br>
                        4️⃣ <strong>Multiply IDF × Adjusted TF</strong> — get the score for this word<br>
                        5️⃣ <strong>Add up scores for ALL query words</strong> — final document score!"
                    </p>
                </div>

                <div class="example-box">
                    <h4>🎨 Visualizing Saturation (Why BM25 is Smart)</h4>
                    <p>Imagine word frequency on X-axis and score contribution on Y-axis:</p>
                    <pre style="font-family: monospace; font-size: 12px;">
Score
  ^
  |                    _______________  ← Max score (k1+1) - CEILING!
  |                 .-'
  |              .-'
  |           .-'
  |        .-'     ← Diminishing returns start here
  |     .-'
  |  .-'
  |.'
  +---------------------------------> Word Frequency
  0    1    5    10   20   50   100

  With k1=1.5:
  • 1 mention  → 1.0 points
  • 5 mentions → 1.9 points (not 5x more!)
  • 10 mentions → 2.2 points (barely more than 5)
  • 100 mentions → 2.5 points (almost same as 10!)
                    </pre>
                    <p><strong>This prevents spam!</strong> Writing "pizza" 100 times doesn't give 100x the score.</p>
                </div>
            </div>
        </div>

        <!-- Section 6: Step-by-Step Example -->
        <div class="bm25-section">
            <h2>👣 Complete Step-by-Step Example</h2>
            <div class="bm25-card">
                <h3>Let's Calculate BM25 Score Together!</h3>
                
                <div class="scenario-box">
                    <h4>📚 The Scenario</h4>
                    <p><strong>Search Query:</strong> "chocolate cake"</p>
                    <p><strong>Our Collection:</strong></p>
                    <ul>
                        <li><strong>N</strong> = 100 total documents</li>
                        <li><strong>avgdl</strong> = 50 words (average document length)</li>
                    </ul>
                    
                    <p><strong>Document to Score:</strong> "This chocolate cake recipe is amazing. The chocolate frosting makes the cake perfect!"</p>
                    <ul>
                        <li><strong>|D|</strong> = 14 words (document length)</li>
                        <li><strong>f("chocolate")</strong> = 2 times</li>
                        <li><strong>f("cake")</strong> = 2 times</li>
                    </ul>

                    <p><strong>Word Statistics in Collection:</strong></p>
                    <ul>
                        <li><strong>n("chocolate")</strong> = 30 documents contain "chocolate"</li>
                        <li><strong>n("cake")</strong> = 40 documents contain "cake"</li>
                    </ul>

                    <p><strong>Parameters (using standard defaults):</strong></p>
                    <ul>
                        <li><strong>k1</strong> = 1.5 (term frequency saturation)</li>
                        <li><strong>b</strong> = 0.75 (length normalization)</li>
                    </ul>
                </div>

                <h3>📐 Step 1: Calculate IDF for Each Query Word</h3>
                <div class="calculation-box">
                    <p><strong>Formula:</strong> IDF = log((N - n + 0.5) / (n + 0.5))</p>
                    
                    <p><strong>For "chocolate" (appears in 30 of 100 docs):</strong></p>
                    <pre>IDF("chocolate") = log((100 - 30 + 0.5) / (30 + 0.5))
                 = log(70.5 / 30.5)
                 = log(2.311)
                 = <strong>0.838</strong></pre>
                    <p>✅ Interpretation: "chocolate" is moderately selective (positive IDF = useful word)</p>

                    <p><strong>For "cake" (appears in 40 of 100 docs):</strong></p>
                    <pre>IDF("cake") = log((100 - 40 + 0.5) / (40 + 0.5))
            = log(60.5 / 40.5)
            = log(1.494)
            = <strong>0.401</strong></pre>
                    <p>✅ Interpretation: "cake" is less selective (lower IDF because it's more common)</p>
                </div>

                <div class="info-box">
                    <h4>💡 Why "chocolate" has higher IDF than "cake"?</h4>
                    <p>"chocolate" appears in 30% of documents, while "cake" appears in 40%. Since "chocolate" is rarer, it's more valuable for distinguishing documents — hence the higher IDF score!</p>
                </div>

                <h3>📐 Step 2: Calculate Length Normalization Factor (B)</h3>
                <div class="calculation-box">
                    <p><strong>Formula:</strong> B = 1 - b + b × (|D| / avgdl)</p>
                    <p>Our document has 14 words, average is 50 words</p>
                    
                    <pre>B = 1 - 0.75 + 0.75 × (14 / 50)
  = 1 - 0.75 + 0.75 × 0.28
  = 0.25 + 0.21
  = <strong>0.46</strong></pre>
                    
                    <p>🚀 <strong>This is GREAT!</strong> B &lt; 1.0 means our short, focused document gets BOOSTED!</p>
                    <p>A longer document (100 words) would have B = 1.75, making it HARDER to score high.</p>
                </div>

                <div class="example-box">
                    <h4>🎯 Understanding B Value Impact</h4>
                    <table class="widefat" style="max-width: 500px;">
                        <tr><th>Document Length</th><th>B Value</th><th>Effect</th></tr>
                        <tr style="background: #d4edda;"><td>14 words (ours)</td><td>0.46</td><td>🚀 Strong boost!</td></tr>
                        <tr><td>50 words (average)</td><td>1.0</td><td>➡️ Neutral</td></tr>
                        <tr style="background: #f8d7da;"><td>100 words</td><td>1.75</td><td>📉 Penalized</td></tr>
                    </table>
                </div>

                <h3>📐 Step 3: Calculate TF Component for Each Word</h3>
                <div class="calculation-box">
                    <p><strong>Formula:</strong> TF_score = (f × (k1 + 1)) / (f + k1 × B)</p>
                    <p>Where f = term frequency, k1 = 1.5, B = 0.46</p>
                    
                    <p><strong>For "chocolate" (f = 2):</strong></p>
                    <pre>TF("chocolate") = (2 × (1.5 + 1)) / (2 + 1.5 × 0.46)
               = (2 × 2.5) / (2 + 0.69)
               = 5 / 2.69
               = <strong>1.859</strong></pre>

                    <p><strong>For "cake" (f = 2):</strong></p>
                    <pre>TF("cake") = (2 × (1.5 + 1)) / (2 + 1.5 × 0.46)
          = (2 × 2.5) / (2 + 0.69)
          = 5 / 2.69
          = <strong>1.859</strong></pre>
                    
                    <p>✅ Both words appear twice, so they have the same TF score.</p>
                </div>

                <div class="warning-box">
                    <h4>⚠️ Notice the Saturation Effect!</h4>
                    <p>Even though "chocolate" and "cake" each appear <strong>2 times</strong>, the TF score is only <strong>1.859</strong> (not 2!).</p>
                    <p>If they appeared 10 times each, the TF would be ~2.3 (not 10!). This is saturation preventing spam.</p>
                </div>

                <h3>📐 Step 4: Combine IDF × TF for Each Word</h3>
                <div class="calculation-box">
                    <p><strong>Formula:</strong> Word Score = IDF × TF_score</p>
                    
                    <p><strong>Score for "chocolate":</strong></p>
                    <pre>Score("chocolate") = IDF × TF
                   = 0.838 × 1.859
                   = <strong>1.558</strong></pre>

                    <p><strong>Score for "cake":</strong></p>
                    <pre>Score("cake") = IDF × TF
             = 0.401 × 1.859
             = <strong>0.745</strong></pre>
                </div>

                <h3>📐 Step 5: Sum All Word Scores = Final BM25 Score</h3>
                <div class="calculation-box">
                    <p><strong>Formula:</strong> BM25(D,Q) = Σ(Word Scores)</p>
                    
                    <pre style="font-size: 16px; background: #e8f5e9; padding: 15px;">
FINAL BM25 SCORE = Score("chocolate") + Score("cake")
                 = 1.558 + 0.745
                 = <strong style="font-size: 22px;">2.303</strong> 🎯</pre>
                </div>

                <div class="info-box">
                    <h4>📊 What does this score (2.303) mean?</h4>
                    <ul>
                        <li>This score is compared against ALL other documents in the collection</li>
                        <li>Higher score = Better match = Higher ranking position</li>
                        <li>If Doc B scored 3.5 → Doc B ranks higher than our document</li>
                        <li>If Doc C scored 1.2 → Our document ranks higher than Doc C</li>
                    </ul>
                </div>

                <div class="example-box">
                    <h4>🔍 Why "chocolate" Contributed More to the Score</h4>
                    <table class="widefat">
                        <tr>
                            <th>Word</th>
                            <th>IDF (Rarity)</th>
                            <th>TF (Frequency)</th>
                            <th>Contribution</th>
                            <th>% of Total</th>
                        </tr>
                        <tr style="background: #d4edda;">
                            <td><strong>chocolate</strong></td>
                            <td>0.838</td>
                            <td>1.859</td>
                            <td>1.558</td>
                            <td><strong>68%</strong></td>
                        </tr>
                        <tr>
                            <td>cake</td>
                            <td>0.401</td>
                            <td>1.859</td>
                            <td>0.745</td>
                            <td>32%</td>
                        </tr>
                    </table>
                    <p>Even though both words appear the same number of times, <strong>"chocolate" contributes 2× more</strong> because it's rarer (higher IDF)!</p>
                </div>
            </div>
        </div>

        <!-- Section 7: Tuning Parameters -->
        <div class="bm25-section">
            <h2>🎛️ Tuning BM25: The k1 and b Parameters</h2>
            
            <div class="bm25-card">
                <h3>Parameter k1: Term Frequency Saturation</h3>
                <p><strong>What it controls:</strong> How quickly repeated words stop mattering</p>
                
                <table class="widefat">
                    <thead>
                        <tr>
                            <th style="background:#ffffff;font-weight:bold;font-size:1.25rem;">k1 Value</th>
                            <th style="background:#ffffff;font-weight:bold;font-size:1.25rem;">Effect</th>
                            <th style="background:#ffffff;font-weight:bold;font-size:1.25rem;">Use Case</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>0</td>
                            <td>Term frequency doesn't matter at all (just presence/absence)</td>
                            <td>When you want binary matching</td>
                        </tr>
                        <tr>
                            <td>1.2</td>
                            <td>Quick saturation - word counts matter less after a few mentions</td>
                            <td>🌟 Most common default - good for general search</td>
                        </tr>
                        <tr>
                            <td>2.0</td>
                            <td>Slower saturation - word counts keep mattering longer</td>
                            <td>When you want repeated mentions to count more</td>
                        </tr>
                        <tr>
                            <td>∞</td>
                            <td>No saturation - linear relationship with word count</td>
                            <td>Usually not recommended (vulnerable to spam)</td>
                        </tr>
                    </tbody>
                </table>

                <div class="example-box">
                    <h4>Visual Comparison:</h4>
                    <p>Document mentions "python" this many times:</p>
                    <ul>
                        <li>k1=1.2: 1 mention→1.0x, 5 mentions→1.8x, 10 mentions→2.0x, 20 mentions→2.1x</li>
                        <li>k1=2.0: 1 mention→1.0x, 5 mentions→2.1x, 10 mentions→2.5x, 20 mentions→2.8x</li>
                    </ul>
                    <p>Notice how k1=1.2 "levels off" faster! 📉</p>
                </div>
            </div>

            <div class="bm25-card">
                <h3>Parameter b: Document Length Normalization</h3>
                <p><strong>What it controls:</strong> How much document length affects the score</p>
                
                <table class="widefat">
                    <thead>
                        <tr>
                            <th style="background:#ffffff;font-weight:bold;font-size:1.25rem;">b Value</th>
                            <th style="background:#ffffff;font-weight:bold;font-size:1.25rem;">Effect</th>
                            <th style="background:#ffffff;font-weight:bold;font-size:1.25rem;">Use Case</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>0</td>
                            <td>Length doesn't matter at all</td>
                            <td>When all documents are similar length</td>
                        </tr>
                        <tr>
                            <td>0.75</td>
                            <td>Balanced length consideration</td>
                            <td>🌟 Most common default - works well for varied content</td>
                        </tr>
                        <tr>
                            <td>1.0</td>
                            <td>Full length normalization</td>
                            <td>When document lengths vary widely</td>
                        </tr>
                    </tbody>
                </table>

                <div class="example-box">
                    <h4>Impact Example:</h4>
                    <p>Two documents, both mention "database" 5 times:</p>
                    <ul>
                        <li><strong>Doc A:</strong> 100 words (short)</li>
                        <li><strong>Doc B:</strong> 1,000 words (long)</li>
                    </ul>
                    
                    <p><strong>With b=0 (no normalization):</strong> Both score the same</p>
                    <p><strong>With b=0.75:</strong> Doc A scores higher (more focused!)</p>
                    <p><strong>With b=1.0:</strong> Doc A scores even higher</p>
                </div>
            </div>
        </div>

        <!-- Section 8: Practical Applications -->
        <div class="bm25-section">
            <h2>🌍 Where is BM25 Used?</h2>
            <div class="bm25-card">
                <h3>Real-World Systems Using BM25:</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div class="app-box">
                        <h4>🔍 Elasticsearch</h4>
                        <p>The most popular search engine for websites and apps. BM25 is the default scoring algorithm!</p>
                    </div>
                    
                    <div class="app-box">
                        <h4>🌐 Apache Lucene/Solr</h4>
                        <p>Powers search for major websites including GitHub, Netflix catalog search, and more.</p>
                    </div>
                    
                    <div class="app-box">
                        <h4>🤖 Retrieval Systems</h4>
                        <p>Used in RAG (Retrieval Augmented Generation) to find relevant documents for AI chatbots.</p>
                    </div>
                    
                    <div class="app-box">
                        <h4>📚 Digital Libraries</h4>
                        <p>Academic search engines, digital libraries, and document management systems.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 9: BM25 vs Other Methods -->
        <div class="bm25-section">
            <h2>⚖️ BM25 vs Other Ranking Methods</h2>
            <div class="bm25-card">
                <table class="widefat">
                    <thead>
                        <tr>
                            <th style="background:#ffffff;font-weight:bold;font-size:1.25rem;">Method</th>
                            <th style="background:#ffffff;font-weight:bold;font-size:1.25rem;">Pros</th>
                            <th style="background:#ffffff;font-weight:bold;font-size:1.25rem;">Cons</th>
                            <th style="background:#ffffff;font-weight:bold;font-size:1.25rem;">Best For</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Simple TF (Term Frequency)</strong></td>
                            <td>Very fast, easy to understand</td>
                            <td>Easily gamed, no nuance, favors long docs</td>
                            <td>Quick prototypes only</td>
                        </tr>
                        <tr>
                            <td><strong>TF-IDF</strong></td>
                            <td>Fast, considers word rarity</td>
                            <td>No saturation, linear scaling issues</td>
                            <td>Basic search, document classification</td>
                        </tr>
                        <tr style="background: #d4edda;">
                            <td><strong>BM25</strong></td>
                            <td>Balanced, handles length well, battle-tested, fast</td>
                            <td>Doesn't understand synonyms or meaning</td>
                            <td>🌟 Most text search applications</td>
                        </tr>
                        <tr>
                            <td><strong>Neural/Semantic Search</strong></td>
                            <td>Understands meaning, handles synonyms</td>
                            <td>Slower, needs more resources, harder to explain</td>
                            <td>When you need semantic understanding</td>
                        </tr>
                    </tbody>
                </table>

                <div class="info-box">
                    <h4>💡 Pro Tip: Hybrid Approach</h4>
                    <p>Many modern systems use BOTH BM25 and neural search:</p>
                    <ul>
                        <li>BM25 for fast, keyword-based retrieval</li>
                        <li>Neural models for re-ranking top results with semantic understanding</li>
                    </ul>
                    <p>This gives you the best of both worlds! 🎯</p>
                </div>
            </div>
        </div>

        <!-- Section 10: Common Pitfalls -->
        <div class="bm25-section">
            <h2>⚠️ Common Mistakes and How to Avoid Them</h2>
            
            <div class="bm25-card">
                <h3>Mistake #1: Not Handling Stop Words</h3>
                <div class="mistake-box">
                    <p><strong>Problem:</strong> Words like "the", "is", "at" appear everywhere and add noise.</p>
                    <p><strong>Solution:</strong> Remove stop words BEFORE indexing, or they'll affect IDF calculations.</p>
                    <p><strong>Example:</strong> "the best pizza" → "best pizza" (remove "the")</p>
                </div>
            </div>

            <div class="bm25-card">
                <h3>Mistake #2: Not Stemming/Lemmatizing</h3>
                <div class="mistake-box">
                    <p><strong>Problem:</strong> "running", "runs", "ran" are treated as completely different words!</p>
                    <p><strong>Solution:</strong> Use stemming to reduce words to their root form.</p>
                    <p><strong>Example:</strong> "running" → "run", "better" → "good"</p>
                </div>
            </div>

            <div class="bm25-card">
                <h3>Mistake #3: Wrong Parameter Values</h3>
                <div class="mistake-box">
                    <p><strong>Problem:</strong> Using default k1 and b for all use cases.</p>
                    <p><strong>Solution:</strong> Tune parameters based on your content:</p>
                    <ul>
                        <li>Short documents (tweets)? → Lower b (maybe 0.5)</li>
                        <li>Technical docs where terms repeat? → Higher k1 (maybe 1.8)</li>
                    </ul>
                </div>
            </div>

            <div class="bm25-card">
                <h3>Mistake #4: Not Considering Field Weighting</h3>
                <div class="mistake-box">
                    <p><strong>Problem:</strong> Title matches should count more than body matches!</p>
                    <p><strong>Solution:</strong> Apply BM25 separately to different fields and weight them:</p>
                    <ul>
                        <li>Title match: 3.0× weight</li>
                        <li>Body match: 1.0× weight</li>
                        <li>Comments: 0.5× weight</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Section 11: Quick Reference -->
        <div class="bm25-section">
            <h2>📋 Quick Reference Guide</h2>
            <div class="bm25-card">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div class="reference-box">
                        <h4>🎯 Best Practices</h4>
                        <ul>
                            <li>✅ Start with k1=1.2, b=0.75</li>
                            <li>✅ Remove stop words</li>
                            <li>✅ Apply stemming</li>
                            <li>✅ Weight title fields higher</li>
                        </ul>
                    </div>
                    
                    <div class="reference-box">
                        <h4>📊 When to Use BM25</h4>
                        <ul>
                            <li>✅ Keyword-based search</li>
                            <li>✅ Documents vary in length</li>
                            <li>✅ Need fast responses</li>
                            <li>✅ Exact term matching important</li>
                        </ul>
                    </div>
                    
                    <div class="reference-box">
                        <h4>🚫 When NOT to Use BM25</h4>
                        <ul>
                            <li>❌ Need semantic understanding</li>
                            <li>❌ Synonym matching crucial</li>
                            <li>❌ Multi-language search</li>
                            <li>❌ Question answering</li>
                        </ul>
                    </div>
                    
                    <div class="reference-box">
                        <h4>🔧 Default Parameters</h4>
                        <ul>
                            <li><strong>k1:</strong> 1.2 to 2.0</li>
                            <li><strong>b:</strong> 0.75</li>
                            <li><strong>δ:</strong> 0.5 (BM25+)</li>
                            <li>Tune based on your data!</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 12: Try It Yourself -->
        <div class="bm25-section">
            <h2>🧪 Mental Exercise: Calculate BM25 Yourself!</h2>
            <div class="bm25-card">
                <div class="exercise-box">
                    <h3>Practice Problem</h3>
                    <p><strong>Given:</strong></p>
                    <ul>
                        <li><strong>N</strong> = 200 total documents</li>
                        <li><strong>avgdl</strong> = 40 words (average document length)</li>
                        <li>Query: "machine learning"</li>
                        <li>k1 = 1.5, b = 0.75</li>
                    </ul>
                    
                    <p><strong>Document:</strong> "Machine learning algorithms require machine learning experts"</p>
                    <ul>
                        <li><strong>|D|</strong> = 7 words (document length)</li>
                        <li><strong>f("machine")</strong> = 2 times</li>
                        <li><strong>f("learning")</strong> = 2 times</li>
                    </ul>
                    
                    <p><strong>Word Statistics:</strong></p>
                    <ul>
                        <li><strong>n("machine")</strong> = 50 documents contain "machine"</li>
                        <li><strong>n("learning")</strong> = 60 documents contain "learning"</li>
                    </ul>
                    
                    <p><strong>Your Task:</strong> Calculate the BM25 score step-by-step!</p>
                    
                    <details style="margin-top: 20px; padding: 15px; background: #f0f0f0;">
                        <summary style="cursor: pointer; font-weight: bold;">📖 Show Complete Answer & Walkthrough</summary>
                        <div style="margin-top: 15px;">
                            <h4>Step 1: Calculate IDF for Each Word</h4>
                            <p><strong>Formula:</strong> IDF = log((N - n + 0.5) / (n + 0.5))</p>
                            <pre style="background: #f3e5f5; padding: 10px;">
IDF("machine") = log((200 - 50 + 0.5) / (50 + 0.5))
               = log(150.5 / 50.5)
               = log(2.98)
               = <strong>1.092</strong>

IDF("learning") = log((200 - 60 + 0.5) / (60 + 0.5))
                = log(140.5 / 60.5)
                = log(2.32)
                = <strong>0.842</strong>
                            </pre>
                            <p>✅ "machine" is rarer (in 25% of docs) → higher IDF</p>
                            <p>✅ "learning" is more common (in 30% of docs) → lower IDF</p>
                            
                            <h4>Step 2: Calculate Length Normalization Factor (B)</h4>
                            <p><strong>Formula:</strong> B = 1 - b + b × (|D| / avgdl)</p>
                            <pre style="background: #f3e5f5; padding: 10px;">
B = 1 - 0.75 + 0.75 × (7 / 40)
  = 0.25 + 0.75 × 0.175
  = 0.25 + 0.131
  = <strong>0.381</strong>
                            </pre>
                            <p>🚀 B < 1.0 → Our very short document gets a BIG boost!</p>
                            
                            <h4>Step 3: Calculate TF Component for Each Word</h4>
                            <p><strong>Formula:</strong> TF = (f × (k1 + 1)) / (f + k1 × B)</p>
                            <pre style="background: #f3e5f5; padding: 10px;">
TF("machine") = (2 × (1.5 + 1)) / (2 + 1.5 × 0.381)
              = (2 × 2.5) / (2 + 0.572)
              = 5 / 2.572
              = <strong>1.944</strong>

TF("learning") = (2 × 2.5) / (2 + 0.572)
               = 5 / 2.572
               = <strong>1.944</strong>
                            </pre>
                            <p>Both words appear twice → same TF score</p>
                            
                            <h4>Step 4: Combine IDF × TF for Each Word</h4>
                            <pre style="background: #f3e5f5; padding: 10px;">
Score("machine")  = 1.092 × 1.944 = <strong>2.123</strong>
Score("learning") = 0.842 × 1.944 = <strong>1.637</strong>
                            </pre>
                            
                            <h4>Step 5: Final BM25 Score</h4>
                            <pre style="background: #e8f5e9; padding: 15px; font-size: 16px;">
FINAL SCORE = Score("machine") + Score("learning")
            = 2.123 + 1.637
            = <strong style="font-size: 20px;">3.760</strong> 🎯
                            </pre>
                            
                            <h4>📊 Analysis: Why This Score?</h4>
                            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                                <tr style="background: #2271b1; color: white;">
                                    <th style="padding: 8px; border: 1px solid #ddd;">Factor</th>
                                    <th style="padding: 8px; border: 1px solid #ddd;">Value</th>
                                    <th style="padding: 8px; border: 1px solid #ddd;">Impact</th>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border: 1px solid #ddd;">Document Length</td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">7 words (vs avg 40)</td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">🚀 Major boost (B=0.38)</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border: 1px solid #ddd;">"machine" contribution</td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">2.123 (56%)</td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">Higher (rarer word)</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; border: 1px solid #ddd;">"learning" contribution</td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">1.637 (44%)</td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">Lower (more common)</td>
                                </tr>
                            </table>
                            <p style="margin-top: 15px;"><strong>Key Insight:</strong> This short, focused document scores well because it's concentrated on the topic (both query words appear twice in just 7 words)!</p>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <!-- CSS Styles -->
        <style>
            body, .wrap {
                font-size: 1.5rem;
                line-height: 1.6;
            }
            th {
            background:#ffffff;
            }
            .bm25-section {
                margin: 30px 0;
                padding: 20px;
                background: white;
                border-left: 4px solid #2271b1;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                font-size: 16px;
            }

            .bm25-card {
                margin: 20px 0;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 5px;
                font-size: 15px;
            }

            .example-box, .info-box, .warning-box, .calculation-box,
            .scenario-box, .translation-box, .plain-english-box, .mistake-box,
            .app-box, .reference-box, .exercise-box {
                margin: 15px 0;
                padding: 15px;
                border-radius: 5px;
                font-size: 15px;
            }

            .example-box {
                background: #e7f3ff;
                border-left: 4px solid #2196F3;
            }

            .info-box {
                background: #e8f5e9;
                border-left: 4px solid #4CAF50;
            }

            .warning-box {
                background: #fff3e0;
                border-left: 4px solid #FF9800;
            }

            .calculation-box {
                background: #f3e5f5;
                border-left: 4px solid #9C27B0;
                font-family: monospace;
                font-size: 14px;
            }

            .scenario-box {
                background: #e0f7fa;
                border-left: 4px solid #00BCD4;
            }

            .formula-box {
                background: #fff;
                padding: 20px;
                border: 2px solid #2271b1;
                border-radius: 5px;
                margin: 15px 0;
                font-size: 15px;
            }

            .translation-box {
                background: #fce4ec;
                border-left: 4px solid #E91E63;
            }

            .plain-english-box {
                background: #fff9c4;
                border-left: 4px solid #FFC107;
                padding: 20px;
                font-style: italic;
                font-size: 16px;
            }

            .mistake-box {
                background: #ffebee;
                border-left: 4px solid #f44336;
            }

            .app-box, .reference-box {
                background: white;
                border: 2px solid #e0e0e0;
                padding: 15px;
                font-size: 15px;
            }

            .exercise-box {
                background: #e1f5fe;
                border: 2px solid #03A9F4;
                padding: 20px;
                font-size: 15px;
            }

            h1 {
                font-size: 28px;
                color: #2271b1;
            }

            h2 {
                color: #2271b1;
                border-bottom: 2px solid #2271b1;
                padding-bottom: 10px;
                font-size: 22px;
            }

            h3 {
                color: #135e96;
                margin-top: 20px;
                font-size: 18px;
            }

            h4 {
                color: #0a3f5e;
                font-size: 16px;
            }

            p {
                font-size: 15px;
                margin-bottom: 12px;
            }

            .widefat {
                margin: 15px 0;
                font-size: 14px;
            }

            .widefat th {
                background: #2271b1;
                color: white;
                font-size: 14px;
                padding: 8px;
            }

            .widefat td {
                padding: 8px;
                font-size: 14px;
            }

            pre {
                background: #f5f5f5;
                padding: 10px;
                border-radius: 3px;
                overflow-x: auto;
                font-size: 13px;
                line-height: 1.4;
            }

            ul, ol {
                line-height: 1.8;
                font-size: 15px;
            }

            li {
                margin-bottom: 8px;
            }

            strong {
                color: #2271b1;
                font-weight: bold;
            }

            table.widefat {
                font-size: 14px;
            }

            table.widefat td, table.widefat th {
                padding: 10px 8px;
            }
        </style>
    </div>
    <?php
}
