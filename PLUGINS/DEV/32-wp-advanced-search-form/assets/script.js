jQuery(document).ready(function($) {
    
    $('#fts-search-form').on('submit', function(e) {
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
            success: function(response) {
                if (response.success) {
                    // Display the encoded query
                    $('#fts-encoded-query').text(response.data.encoded);
                    $('#fts-example-url').text(response.data.url);
                    $('#fts-result').slideDown();
                    
                    // Scroll to result
                    $('html, body').animate({
                        scrollTop: $('#fts-result').offset().top - 20
                    }, 500);
                }
            },
            error: function() {
                alert('Error generating query. Please try again.');
            }
        });
    });
    
    // Copy to clipboard functionality
    $('#fts-copy-btn').on('click', function() {
        const text = $('#fts-encoded-query').text();
        const $btn = $(this);
        const originalText = $btn.text();
        
        // Copy to clipboard
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                $btn.text('Copied!');
                setTimeout(function() {
                    $btn.text(originalText);
                }, 2000);
            }).catch(function() {
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
                setTimeout(function() {
                    $btn.text(originalText);
                }, 2000);
            } catch (err) {
                alert('Failed to copy. Please copy manually.');
            }
            
            textarea.remove();
        }
    });
});
