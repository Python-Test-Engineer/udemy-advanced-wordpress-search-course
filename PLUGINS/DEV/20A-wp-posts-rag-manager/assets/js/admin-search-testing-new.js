/**
 * WP Posts RAG Manager - Search Testing (New) JavaScript
 *
 * Fresh, minimal implementation for the Search Testing subpage.
 * Includes heavy console logging to confirm script loads and click handlers fire.
 */

(function ($) {
    'use strict';

    console.log('🚀 WPRAG Search Testing NEW: Script loaded');

    $(document).ready(function () {
        console.log('✅ WPRAG Search Testing NEW: Document ready');

        if (typeof wprag_search_testing === 'undefined') {
            console.error('❌ wprag_search_testing is undefined. Script localization failed.');
            return;
        }

        console.log('🔧 wprag_search_testing config:', wprag_search_testing);

        // Button handlers
        $('#wprag-test-fts-btn').on('click', function () {
            console.log('🖱️ NEW FTS button clicked');
            runSearch('fts');
        });

        $('#wprag-test-vector-btn').on('click', function () {
            console.log('🖱️ NEW Vector button clicked');
            runSearch('vector');
        });

        $('#wprag-compare-btn').on('click', function () {
            console.log('🖱️ NEW Compare button clicked');
            runCompare();
        });
    });

    function runSearch(type) {
        var query = type === 'fts' ? $('#wprag-fts-query').val() : $('#wprag-vector-query').val();
        var limit = type === 'fts' ? $('#wprag-fts-limit').val() : $('#wprag-vector-limit').val();

        console.log('🔍 NEW Search:', { type: type, query: query, limit: limit });

        if (!query) {
            alert('Please enter a search query');
            return;
        }

        var endpoint = type === 'fts' ? wprag_search_testing.fts_endpoint : wprag_search_testing.vector_endpoint;
        var url = endpoint + '?query=' + encodeURIComponent(query) + '&limit=' + limit;

        console.log('📡 NEW Request URL:', url);

        $.ajax({
            url: url,
            type: 'GET'
        })
            .done(function (response) {
                console.log('✅ NEW Search response:', response);

                if (type === 'fts') {
                    $('#wprag-fts-json').text(JSON.stringify(response, null, 2));
                    $('#wprag-fts-results').show();
                } else {
                    $('#wprag-vector-json').text(JSON.stringify(response, null, 2));
                    $('#wprag-vector-results').show();
                }
            })
            .fail(function (xhr) {
                console.error('❌ NEW Search error:', xhr);
            });
    }

    function runCompare() {
        var query = $('#wprag-compare-query').val();
        console.log('🔁 NEW Compare query:', query);

        if (!query) {
            alert('Please enter a search query');
            return;
        }

        var ftsUrl = wprag_search_testing.fts_endpoint + '?query=' + encodeURIComponent(query) + '&limit=3';
        var vectorUrl = wprag_search_testing.vector_endpoint + '?query=' + encodeURIComponent(query) + '&limit=3';

        console.log('📡 NEW Compare URLs:', { ftsUrl: ftsUrl, vectorUrl: vectorUrl });

        $.when(
            $.ajax({ url: ftsUrl, type: 'GET' }),
            $.ajax({ url: vectorUrl, type: 'GET' })
        )
            .done(function (ftsResult, vectorResult) {
                console.log('✅ NEW Compare results:', { fts: ftsResult[0], vector: vectorResult[0] });

                $('#wprag-compare-fts-json').text(JSON.stringify(ftsResult[0], null, 2));
                $('#wprag-compare-vector-json').text(JSON.stringify(vectorResult[0], null, 2));
                $('#wprag-compare-results').show();
            })
            .fail(function (xhr) {
                console.error('❌ NEW Compare error:', xhr);
            });
    }
})(jQuery);