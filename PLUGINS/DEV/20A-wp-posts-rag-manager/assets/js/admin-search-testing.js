/**
 * WP Posts RAG Manager - Search Testing JavaScript
 *
 * This file handles the search testing page functionality including:
 * - Testing Full-Text Search (FTS)
 * - Testing Vector Search
 * - Comparing both search methods side by side
 *
 * @package WP_Posts_RAG_Manager
 * @subpackage Assets
 *
 * ============================================================================
 * CONSOLE.LOG REFERENCE:
 * ============================================================================
 * This file uses extensive console logging to help with debugging.
 * Look for these emoji prefixes:
 * - 🔄: Function entry/start
 * - ✅: Success state
 * - ❌: Error state
 * - ℹ️: Information/Status messages
 * - 🔍: Search related
 * - 📡: AJAX related
 * ============================================================================
 */

(function($) {
    'use strict';

    // ============================================================================
    // DOCUMENT READY
    // ============================================================================

    /**
     * Document ready function.
     * This runs when the DOM is fully loaded.
     *
     * @since 1.7.0
     */
    $(document).ready(function() {
        // Log that the document is ready.
        console.log('🔍 WPRAG Search Testing: Document ready');
        console.log('   jQuery version: ' + $.fn.jquery);
        console.log('   AJAX URL: ' + wprag_ajax.ajax_url);
        console.log('   Nonce: ' + wprag_ajax.nonce.substring(0, 10) + '...');

        // Initialize all event handlers.
        WPRAGSearch.initEventHandlers();

        // Log initialization complete.
        console.log('✅ WPRAG Search: Event handlers initialized');
    });

    // ============================================================================
    // WPRAGSEARCH NAMESPACE
    // ============================================================================

    /**
     * WPRAGSearch namespace.
     * All functions are contained within this namespace.
     *
     * @since 1.7.0
     */
    var WPRAGSearch = window.WPRAGSearch || {};

    // ============================================================================
    // EVENT HANDLERS
    // ============================================================================

    /**
     * Initialize all event handlers.
     * This sets up click handlers for all buttons.
     *
     * @since 1.7.0
     */
    WPRAGSearch.initEventHandlers = function() {
        console.log('🔄 WPRAG Search: Initializing event handlers');

        // Test Full-Text Search button handler.
        $('#wprag-test-fts-btn').on('click', function() {
            console.log('🖱️  Clicked: Test FTS button');
            WPRAGSearch.testFulltextSearch();
        });

        // Test Vector Search button handler.
        $('#wprag-test-vector-btn').on('click', function() {
            console.log('🖱️  Clicked: Test Vector Search button');
            WPRAGSearch.testVectorSearch();
        });

        // Compare Both Methods button handler.
        $('#wprag-compare-btn').on('click', function() {
            console.log('🖱️  Clicked: Compare Both Methods button');
            WPRAGSearch.compareSearchMethods();
        });

        console.log('✅ Event handlers registered');
    };

    // ============================================================================
    // SEARCH FUNCTIONS
    // ============================================================================

    /**
     * Test Full-Text Search (FTS).
     * This sends a search query to the FTS endpoint.
     *
     * @since 1.7.0
     */
    WPRAGSearch.testFulltextSearch = function() {
        console.log('🔍 WPRAG Search: Testing Full-Text Search');

        // Get the search query.
        var query = $('#wprag-fts-query').val();
        var limit = $('#wprag-fts-limit').val();

        console.log('   Query: ' + query);
        console.log('   Limit: ' + limit);

        // Validate the query.
        if (!query) {
            console.log('   ❌ No query provided');
            WPRAGSearch.showMessage('Please enter a search query', 'error');
            return;
        }

        // Disable the button during the request.
        var $btn = $('#wprag-test-fts-btn');
        $btn.prop('disabled', true).text('Searching...');
        console.log('   Button disabled');

        // Hide results container while loading.
        $('#wprag-fts-results').hide();

        // Build the API URL.
        // The endpoint is defined in the PHP file and passed via wp_localize_script.
        var endpoint = wprag_ajax.fts_endpoint || '/wp-json/posts-rag/v1/search';
        var url = endpoint + '?query=' + encodeURIComponent(query) + '&limit=' + limit;

        console.log('   📡 URL: ' + url);

        // Make the GET request.
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                console.log('   ✅ Response received');
                console.log('   Response:', response);

                // Display the results.
                $('#wprag-fts-json').text(JSON.stringify(response, null, 2));
                $('#wprag-fts-results').show();

                // Show appropriate message.
                if (response.success && response.count > 0) {
                    console.log('   ℹ️  Found ' + response.count + ' results');
                    WPRAGSearch.showMessage('Found ' + response.count + ' results', 'success');
                } else if (response.success) {
                    console.log('   ℹ️  No results found');
                    WPRAGSearch.showMessage('No results found', 'info');
                } else {
                    console.log('   ℹ️  Search completed with no matches');
                    WPRAGSearch.showMessage('Search completed with no matches', 'warning');
                }
            },
            error: function(xhr) {
                console.log('   ❌ Error occurred');
                console.log('   XHR:', xhr);

                // Try to parse error response.
                var error = xhr.responseJSON || {message: 'Unknown error'};
                $('#wprag-fts-json').text(JSON.stringify(error, null, 2));
                $('#wprag-fts-results').show();

                console.log('   ❌ Error: ' + (error.message || xhr.statusText));
                WPRAGSearch.showMessage('Error: ' + (error.message || xhr.statusText), 'error');
            },
            complete: function() {
                // Re-enable the button.
                $btn.prop('disabled', false).text('Test Full-Text Search');
                console.log('   ✅ Button re-enabled');
            }
        });
    };

    /**
     * Test Vector Search.
     * This sends a search query to the vector search endpoint.
     *
     * @since 1.7.0
     */
    WPRAGSearch.testVectorSearch = function() {
        console.log('🔍 WPRAG Search: Testing Vector Search');

        // Get the search query.
        var query = $('#wprag-vector-query').val();
        var limit = $('#wprag-vector-limit').val();

        console.log('   Query: ' + query);
        console.log('   Limit: ' + limit);

        // Validate the query.
        if (!query) {
            console.log('   ❌ No query provided');
            WPRAGSearch.showMessage('Please enter a search query', 'error');
            return;
        }

        // Disable the button during the request.
        var $btn = $('#wprag-test-vector-btn');
        $btn.prop('disabled', true).text('Searching...');
        console.log('   Button disabled');

        // Hide results container while loading.
        $('#wprag-vector-results').hide();

        // Build the API URL.
        var endpoint = wprag_ajax.vector_endpoint || '/wp-json/posts-rag/v1/vector-search';
        var url = endpoint + '?query=' + encodeURIComponent(query) + '&limit=' + limit;

        console.log('   📡 URL: ' + url);

        // Make the GET request.
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                console.log('   ✅ Response received');
                console.log('   Response:', response);

                // Display the results.
                $('#wprag-vector-json').text(JSON.stringify(response, null, 2));
                $('#wprag-vector-results').show();

                // Show appropriate message.
                if (response.success && response.count > 0) {
                    console.log('   ℹ️  Found ' + response.count + ' results');
                    WPRAGSearch.showMessage('Found ' + response.count + ' results', 'success');
                } else if (response.success) {
                    console.log('   ℹ️  No results found');
                    WPRAGSearch.showMessage('No results found', 'info');
                } else {
                    console.log('   ℹ️  Search completed with no matches');
                    WPRAGSearch.showMessage('Search completed with no matches', 'warning');
                }
            },
            error: function(xhr) {
                console.log('   ❌ Error occurred');
                console.log('   XHR:', xhr);

                // Try to parse error response.
                var error = xhr.responseJSON || {message: 'Unknown error'};
                $('#wprag-vector-json').text(JSON.stringify(error, null, 2));
                $('#wprag-vector-results').show();

                console.log('   ❌ Error: ' + (error.message || xhr.statusText));
                WPRAGSearch.showMessage('Error: ' + (error.message || xhr.statusText), 'error');
            },
            complete: function() {
                // Re-enable the button.
                $btn.prop('disabled', false).text('Test Vector Search');
                console.log('   ✅ Button re-enabled');
            }
        });
    };

    /**
     * Compare Both Search Methods.
     * This runs the same query through both FTS and Vector search.
     *
     * @since 1.7.0
     */
    WPRAGSearch.compareSearchMethods = function() {
        console.log('🔄 WPRAG Search: Comparing search methods');

        // Get the search query.
        var query = $('#wprag-compare-query').val();

        console.log('   Query: ' + query);

        // Validate the query.
        if (!query) {
            console.log('   ❌ No query provided');
            WPRAGSearch.showMessage('Please enter a search query', 'error');
            return;
        }

        // Disable the button during the request.
        var $btn = $('#wprag-compare-btn');
        $btn.prop('disabled', true).text('Comparing...');
        console.log('   Button disabled');

        // Hide results container while loading.
        $('#wprag-compare-results').hide();

        // Build the API URLs.
        var ftsEndpoint = wprag_ajax.fts_endpoint || '/wp-json/posts-rag/v1/search';
        var vectorEndpoint = wprag_ajax.vector_endpoint || '/wp-json/posts-rag/v1/vector-search';

        var ftsUrl = ftsEndpoint + '?query=' + encodeURIComponent(query) + '&limit=3';
        var vectorUrl = vectorEndpoint + '?query=' + encodeURIComponent(query) + '&limit=3';

        console.log('   📡 FTS URL: ' + ftsUrl);
        console.log('   📡 Vector URL: ' + vectorUrl);

        // Make both requests simultaneously using jQuery.when.
        // This allows us to wait for both to complete before processing.
        $.when(
            $.ajax({url: ftsUrl, type: 'GET'}),
            $.ajax({url: vectorUrl, type: 'GET'})
        ).done(function(ftsResult, vectorResult) {
            // Both requests completed successfully.
            console.log('   ✅ Both requests completed');
            console.log('   FTS Result:', ftsResult[0]);
            console.log('   Vector Result:', vectorResult[0]);

            // Display FTS results.
            $('#wprag-compare-fts-json').text(JSON.stringify(ftsResult[0], null, 2));

            // Display Vector results.
            $('#wprag-compare-vector-json').text(JSON.stringify(vectorResult[0], null, 2));

            // Show the comparison results.
            $('#wprag-compare-results').show();

            // Show success message.
            console.log('   ℹ️  Comparison complete');
            WPRAGSearch.showMessage('Comparison complete!', 'success');

        }).fail(function(xhr) {
            // One or both requests failed.
            console.log('   ❌ Comparison failed');
            console.log('   XHR:', xhr);

            WPRAGSearch.showMessage('Error during comparison', 'error');

        }).always(function() {
            // Re-enable the button.
            $btn.prop('disabled', false).text('Compare Both Methods');
            console.log('   ✅ Button re-enabled');
        });
    };

    // ============================================================================
    // UI HELPER FUNCTIONS
    // ============================================================================

    /**
     * Show a message to the user.
     * This displays a WordPress-style notice message.
     *
     * @since 1.7.0
     * @param {string} message - The message to display.
     * @param {string} type - The message type (success, error, info, warning).
     */
    WPRAGSearch.showMessage = function(message, type) {
        console.log('💬 WPRAG Search: Showing message');
        console.log('   Message: ' + message);
        console.log('   Type: ' + type);

        // Get the message container.
        var $msg = $('#wprag-search-message');

        // Remove all notice classes and add the new one.
        $msg.removeClass('notice-success notice-error notice-info notice-warning notice')
            .addClass('notice notice-' + type);

        // Set the message text.
        $msg.find('p').text(message);

        // Show the message.
        $msg.show();
        console.log('   ✅ Message displayed');

        // Auto-hide after 5 seconds.
        setTimeout(function() {
            console.log('   🔄 Auto-hiding message');
            $msg.fadeOut();
        }, 5000);
    };

    // ============================================================================
    // EXPOSE WPRAGSEARCH TO GLOBAL SCOPE
    // ============================================================================

    // Expose the WPRAGSearch namespace to the global scope.
    window.WPRAGSearch = WPRAGSearch;

    // ============================================================================
    // END OF FILE
    // ============================================================================

})(jQuery);

// ============================================================================
// ADDITIONAL CONSOLE LOG HELPERS
// ============================================================================

// Log that the script has loaded.
console.log('🔍 WPRAG Search Testing JS: Script loaded and ready');
console.log('   Version: 1.7.0');
console.log('   Loaded at: ' + new Date().toISOString());

// ============================================================================
// HELPER FUNCTION FOR DEBUGGING
// ============================================================================

/**
 * Helper function to pretty-print JSON to console.
 * Useful for debugging API responses.
 *
 * @since 1.7.0
 * @param {object} obj - The object to print.
 */
window.WPRAGDebug = function(obj) {
    console.log('🔍 WPRAG Debug Output:');
    console.log(JSON.stringify(obj, null, 2));
};

// Add a global function to test from browser console.
console.log('💡 Use WPRAGDebug(object) to pretty-print objects to console');
