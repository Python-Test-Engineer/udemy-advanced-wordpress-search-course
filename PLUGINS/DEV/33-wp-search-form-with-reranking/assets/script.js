jQuery(document).ready(function ($) {

    function renderFulltextResults(results) {
        const $container = $('#fts-fulltext-results');
        $container.empty();

        if (!Array.isArray(results) || results.length === 0) {
            $container.append('<p class="fts-fulltext-empty">No full-text results returned.</p>');
            return;
        }

        results.forEach(function (item, index) {
            const title = item.post_title || 'Untitled';
            const excerpt = item.content || item.post_content || '';
            const relevance = typeof item.relevance_score !== 'undefined' ? Number(item.relevance_score).toFixed(4) : '0.0000';
            const position = typeof item.position !== 'undefined' ? item.position : (index + 1);
            const link = item.url || item.permalink || '';

            const card = `
                <div class="fts-fulltext-card">
                    <div class="fts-fulltext-header">
                        <h4>${title}</h4>
                        <span class="fts-fulltext-position">#${position}</span>
                    </div>
                    <div class="fts-fulltext-meta fts-fulltext-meta-row">
                        <span class="fts-fulltext-badge">FTS: ${relevance}</span>
                    </div>
                    <p class="fts-fulltext-excerpt">${excerpt}</p>
                    ${link ? `<a class="fts-fulltext-link" href="${link}" target="_blank" rel="noopener">View Result</a>` : ''}
                </div>
            `;
            $container.append(card);
        });
    }

    function fetchFulltextResults(query) {
        const endpoint = ftsAjax.fulltextEndpoint || `${ftsAjax.siteUrl}/wp-json/fts-boolean/v1/search`;
        const url = `${endpoint}?query=${encodeURIComponent(query)}&limit=3`;

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
        $('#fts-fulltext-result').hide();
        $('#fts-fulltext-results').empty();

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
                        fetchFulltextResults(query)
                            .done(function (fulltextResponse) {
                                if (fulltextResponse && fulltextResponse.success) {
                                    renderFulltextResults(fulltextResponse.results || []);
                                } else {
                                    renderFulltextResults([]);
                                }
                                $('#fts-fulltext-result').slideDown();
                            })
                            .fail(function () {
                                renderFulltextResults([]);
                                $('#fts-fulltext-result').slideDown();
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
