jQuery(document).ready(function ($) {

    function renderRerankedResults(results) {
        const $container = $('#fts-rerank-results');
        $container.empty();

        if (!Array.isArray(results) || results.length === 0) {
            $container.append('<p class="fts-rerank-empty">No reranked results returned.</p>');
            return;
        }

        results.forEach(function (item) {
            const title = item.post_title || 'Untitled';
            const excerpt = item.excerpt || item.content || item.post_content || '';
            const method = item.method || 'UNKNOWN';
            const relevance = typeof item.relevance_score !== 'undefined' ? Number(item.relevance_score).toFixed(4) : '0.0000';
            const similarity = typeof item.similarity_score !== 'undefined' ? Number(item.similarity_score).toFixed(4) : '0.0000';
            const position = item.position || '-';
            const link = item.url || item.permalink || '';

            const card = `
                <div class="fts-rerank-card">
                    <div class="fts-rerank-header">
                        <h4>${title}</h4>
                        <span class="fts-rerank-position">#${position}</span>
                    </div>
                    <div class="fts-rerank-meta">
                        <span class="fts-rerank-badge">${method}</span>
                    </div>
                    <div class="fts-rerank-meta fts-rerank-meta-row">
                        <span class="fts-rerank-badge">FTS: ${relevance}</span>
                        <span class="fts-rerank-badge">Vector: ${similarity}</span>
                    </div>
                    <p class="fts-rerank-excerpt">${excerpt}</p>
                    ${link ? `<a class="fts-rerank-link" href="${link}" target="_blank" rel="noopener">View Result</a>` : ''}
                </div>
            `;
            $container.append(card);
        });
    }

    function fetchRerankResults(query) {
        const endpoint = ftsAjax.rerankEndpoint || `${ftsAjax.siteUrl}/wp-json/reranker/v1/reranked`;
        const url = `${endpoint}?query=${encodeURIComponent(query)}`;

        return $.ajax({
            url: url,
            type: 'GET'
        });
    }

    $('#fts-search-form').on('submit', function (e) {
        e.preventDefault();

        const basicQuery = $('#fts-basic-query').val().trim();
        const mustContain = $('#fts-must-contain').val().trim();
        const mustNotContain = $('#fts-must-not-contain').val().trim();
        const wildcard = $('#fts-wildcard').val().trim();
        const phrase = $('#fts-phrase').val().trim();
        const lessThan = $('#fts-less-than').val().trim();
        const greaterThan = $('#fts-greater-than').val().trim();
        const orTerms = $('#fts-or-terms').val().trim();
        const parentheses = $('#fts-parentheses').val().trim();

        // Check if at least one field has a value
        if (!basicQuery && !mustContain && !mustNotContain && !wildcard && !phrase && !lessThan && !greaterThan && !orTerms && !parentheses) {
            alert('Please enter at least one search term');
            return;
        }

        // Send AJAX request
        $('#fts-rerank-result').hide();
        $('#fts-rerank-results').empty();

        $.ajax({
            url: ftsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_fts_query',
                nonce: ftsAjax.nonce,
                basic_query: basicQuery,
                must_contain: mustContain,
                must_not_contain: mustNotContain,
                wildcard: wildcard,
                phrase: phrase,
                less_than: lessThan,
                greater_than: greaterThan,
                or_terms: orTerms,
                parentheses: parentheses
            },
            success: function (response) {
                if (response.success) {
                    // Display the encoded query
                    $('#fts-encoded-query').text(response.data.encoded);
                    $('#fts-example-url').text(response.data.url);
                    $('#fts-result').slideDown();

                    const query = response.data.query || response.data.decoded || response.data.raw || basicQuery;
                    if (query) {
                        fetchRerankResults(query)
                            .done(function (rerankResponse) {
                                if (rerankResponse && rerankResponse.success) {
                                    renderRerankedResults(rerankResponse.results || []);
                                } else {
                                    renderRerankedResults([]);
                                }
                                $('#fts-rerank-result').slideDown();
                            })
                            .fail(function () {
                                renderRerankedResults([]);
                                $('#fts-rerank-result').slideDown();
                            });
                    }

                    // Scroll to result
                    $('html, body').animate({
                        scrollTop: $('#fts-result').offset().top - 20
                    }, 500);
                }
            },
            error: function () {
                alert('Error generating query. Please try again.');
            }
        });
    });

    // Copy to clipboard functionality
    $('#fts-copy-btn').on('click', function () {
        const text = $('#fts-example-url').text();
        const $btn = $(this);
        const originalText = $btn.text();

        // Copy to clipboard
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                $btn.text('Copied!');
                setTimeout(function () {
                    $btn.text(originalText);
                }, 2000);
            }).catch(function () {
                fallbackCopy(text);
            });
        } else {
            fallbackCopy(text);
        }

        function fallbackCopy(text) {
            const textarea = $('<textarea>');
            textarea.val(text);
            textarea.css({
                position: 'fixed',
                opacity: 0
            });
            $('body').append(textarea);
            textarea[0].select();

            try {
                document.execCommand('copy');
                $btn.text('Copied!');
                setTimeout(function () {
                    $btn.text(originalText);
                }, 2000);
            } catch (err) {
                alert('Failed to copy. Please copy manually.');
            }

            textarea.remove();
        }
    });
});
