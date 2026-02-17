/**
 * WP Posts RAG Manager - Main Admin JavaScript
 *
 * This file handles all the interactive functionality on the main
 * admin page including:
 * - Saving OpenAI API key
 * - Syncing posts to the RAG table
 * - Creating/deleting full-text indexes
 * - Generating embeddings
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
        console.log('📄 WPRAG Admin: Document ready');
        console.log('   jQuery version: ' + $.fn.jquery);
        console.log('   AJAX URL: ' + wprag_ajax.ajax_url);
        console.log('   Nonce: ' + wprag_ajax.nonce.substring(0, 10) + '...');

        // Initialize all event handlers.
        WPRAG.initEventHandlers();

        // Log initialization complete.
        console.log('✅ WPRAG: Event handlers initialized');
    });

    // ============================================================================
    // WPRAG NAMESPACE
    // ============================================================================

    /**
     * WPRAG namespace.
     * All functions are contained within this namespace to avoid
     * conflicts with other plugins.
     *
     * @since 1.7.0
     */
    var WPRAG = window.WPRAG || {};

    // ============================================================================
    // EVENT HANDLERS
    // ============================================================================

    /**
     * Initialize all event handlers.
     * This sets up click handlers for all buttons.
     *
     * @since 1.7.0
     */
    WPRAG.initEventHandlers = function() {
        console.log('🔄 WPRAG: Initializing event handlers');

        // Save API Key button handler.
        $('#wprag-save-api-key-btn').on('click', function() {
            console.log('🖱️  Clicked: Save API Key button');
            WPRAG.saveApiKey();
        });

        // Sync Posts button handler.
        $('#wprag-sync-posts-btn').on('click', function() {
            console.log('🖱️  Clicked: Sync Posts button');
            WPRAG.syncPosts();
        });

        // Create Full-Text Index button handler.
        $('#wprag-create-fulltext-btn').on('click', function() {
            console.log('🖱️  Clicked: Create Full-Text Index button');
            WPRAG.createFulltextIndex();
        });

        // Delete Full-Text Index button handler.
        $('#wprag-delete-fulltext-btn').on('click', function() {
            console.log('🖱️  Clicked: Delete Full-Text Index button');

            // Confirm before deleting.
            if (confirm(wprag_ajax.i18n.confirm)) {
                console.log('   User confirmed deletion');
                WPRAG.deleteFulltextIndex();
            } else {
                console.log('   User cancelled deletion');
            }
        });

        // Generate Embeddings button handler.
        $('#wprag-generate-embeddings-btn').on('click', function() {
            console.log('🖱️  Clicked: Generate Embeddings button');
            WPRAG.generateEmbeddings();
        });

        console.log('✅ Event handlers registered');
    };

    // ============================================================================
    // AJAX HELPER FUNCTIONS
    // ============================================================================

    /**
     * Generic AJAX request handler.
     * This is a helper function that handles common AJAX operations.
     *
     * @since 1.7.0
     * @param {string} action - The AJAX action name.
     * @param {object} data - Additional data to send.
     * @param {function} successCallback - Function to call on success.
     * @param {function} errorCallback - Function to call on error.
     */
    WPRAG.ajaxRequest = function(action, data, successCallback, errorCallback) {
        // Log the AJAX request.
        console.log('📡 WPRAG: Sending AJAX request');
        console.log('   Action: ' + action);
        console.log('   Data:', data);

        // Prepare the AJAX data.
        var ajaxData = {
            action: action,
            nonce: wprag_ajax.nonce
        };

        // Merge additional data.
        $.extend(ajaxData, data);

        // Make the AJAX request.
        $.ajax({
            url: wprag_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                console.log('📥 WPRAG: AJAX response received');
                console.log('   Response:', response);

                if (response.success) {
                    console.log('   ✅ Request successful');
                    if (successCallback) {
                        successCallback(response.data);
                    }
                } else {
                    console.log('   ❌ Request failed: ' + response.data);
                    if (errorCallback) {
                        errorCallback(response.data);
                    } else {
                        WPRAG.showMessage(response.data, 'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('   ❌ AJAX Error');
                console.log('   Status: ' + status);
                console.log('   Error: ' + error);
                console.log('   XHR:', xhr);

                var errorMsg = status + ': ' + error;
                if (errorCallback) {
                    errorCallback(errorMsg);
                } else {
                    WPRAG.showMessage(errorMsg, 'error');
                }
            }
        });
    };

    // ============================================================================
    // BUTTON ACTION FUNCTIONS
    // ============================================================================

    /**
     * Save the OpenAI API key.
     * This sends the API key to the server for storage.
     *
     * @since 1.7.0
     */
    WPRAG.saveApiKey = function() {
        console.log('🔄 WPRAG: Saving API key');

        // Get the API key from the input field.
        var apiKey = $('#openai_api_key').val();
        console.log('   API Key length: ' + (apiKey ? apiKey.length : 0));

        // Validate that API key is provided.
        if (!apiKey) {
            console.log('   ❌ No API key provided');
            WPRAG.showMessage('Please enter an API key', 'error');
            return;
        }

        // Disable the button during the request.
        var $btn = $('#wprag-save-api-key-btn');
        $btn.prop('disabled', true).text(wprag_ajax.i18n.saving);
        console.log('   Button disabled, state: saving');

        // Make the AJAX request.
        WPRAG.ajaxRequest(
            'wprag_save_openai_key',
            { api_key: apiKey },
            function(successMsg) {
                console.log('   ✅ API key saved successfully');
                console.log('   Message: ' + successMsg);

                // Re-enable the button.
                $btn.prop('disabled', false).text(wprag_ajax.i18n.saved);

                // Show success message.
                WPRAG.showMessage(successMsg, 'success');

                // Reset button text after delay.
                setTimeout(function() {
                    $btn.text('Save API Key');
                }, 2000);
            },
            function(errorMsg) {
                console.log('   ❌ Failed to save API key');
                console.log('   Error: ' + errorMsg);

                // Re-enable the button.
                $btn.prop('disabled', false).text('Save API Key');

                // Show error message.
                WPRAG.showMessage(errorMsg, 'error');
            }
        );
    };

    /**
     * Sync posts to the RAG table.
     * This copies all published WordPress posts to the custom table.
     *
     * @since 1.7.0
     */
    WPRAG.syncPosts = function() {
        console.log('🔄 WPRAG: Syncing posts');

        // Disable the button during the request.
        var $btn = $('#wprag-sync-posts-btn');
        $btn.prop('disabled', true).text(wprag_ajax.i18n.processing);
        console.log('   Button disabled, state: processing');

        // Make the AJAX request.
        WPRAG.ajaxRequest(
            'wprag_sync_posts',
            {},
            function(successMsg) {
                console.log('   ✅ Posts synced successfully');
                console.log('   Message: ' + successMsg);

                // Re-enable the button.
                $btn.prop('disabled', false).text('Sync Posts');

                // Show success message.
                WPRAG.showMessage(successMsg, 'success');

                // Refresh stats after a short delay.
                console.log('   🔄 Refreshing stats...');
                setTimeout(function() {
                    WPRAG.refreshStats();
                }, 500);
            },
            function(errorMsg) {
                console.log('   ❌ Failed to sync posts');
                console.log('   Error: ' + errorMsg);

                // Re-enable the button.
                $btn.prop('disabled', false).text('Sync Posts');

                // Show error message.
                WPRAG.showMessage(errorMsg, 'error');
            }
        );
    };

    /**
     * Create a full-text index.
     * This creates a MySQL FULLTEXT index on selected fields.
     *
     * @since 1.7.0
     */
    WPRAG.createFulltextIndex = function() {
        console.log('🔄 WPRAG: Creating full-text index');

        // Get selected checkboxes.
        var indexTitle = $('#wprag-index-title').is(':checked');
        var indexContent = $('#wprag-index-content').is(':checked');

        console.log('   Index title: ' + indexTitle);
        console.log('   Index content: ' + indexContent);

        // Validate that at least one field is selected.
        if (!indexTitle && !indexContent) {
            console.log('   ❌ No fields selected');
            WPRAG.showMessage('Please select at least one field to index', 'error');
            return;
        }

        // Disable the button during the request.
        var $btn = $('#wprag-create-fulltext-btn');
        $btn.prop('disabled', true).text(wprag_ajax.i18n.processing);
        console.log('   Button disabled, state: processing');

        // Make the AJAX request.
        WPRAG.ajaxRequest(
            'wprag_create_fulltext_index',
            {
                index_title: indexTitle,
                index_content: indexContent
            },
            function(data) {
                console.log('   ✅ Index created successfully');
                console.log('   Data:', data);

                // Re-enable the button.
                $btn.prop('disabled', false).text('Create Full-Text Index');

                // Show success message.
                var message = typeof data === 'object' ? data.message : data;
                WPRAG.showMessage(message, 'success');

                // Reload the page to show updated status.
                console.log('   🔄 Reloading page to show updated status...');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            },
            function(errorMsg) {
                console.log('   ❌ Failed to create index');
                console.log('   Error: ' + errorMsg);

                // Re-enable the button.
                $btn.prop('disabled', false).text('Create Full-Text Index');

                // Show error message.
                WPRAG.showMessage(errorMsg, 'error');
            }
        );
    };

    /**
     * Delete the full-text index.
     * This removes the MySQL FULLTEXT index.
     *
     * @since 1.7.0
     */
    WPRAG.deleteFulltextIndex = function() {
        console.log('🔄 WPRAG: Deleting full-text index');

        // Disable the button during the request.
        var $btn = $('#wprag-delete-fulltext-btn');
        $btn.prop('disabled', true).text(wprag_ajax.i18n.processing);
        console.log('   Button disabled, state: processing');

        // Make the AJAX request.
        WPRAG.ajaxRequest(
            'wprag_delete_fulltext_index',
            {},
            function(successMsg) {
                console.log('   ✅ Index deleted successfully');
                console.log('   Message: ' + successMsg);

                // Re-enable the button.
                $btn.prop('disabled', false).text('Delete Index');

                // Show success message.
                WPRAG.showMessage(successMsg, 'success');

                // Reload the page to show updated status.
                console.log('   🔄 Reloading page to show updated status...');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            },
            function(errorMsg) {
                console.log('   ❌ Failed to delete index');
                console.log('   Error: ' + errorMsg);

                // Re-enable the button.
                $btn.prop('disabled', false).text('Delete Index');

                // Show error message.
                WPRAG.showMessage(errorMsg, 'error');
            }
        );
    };

    /**
     * Generate embeddings for posts.
     * This generates OpenAI embeddings for posts without them.
     *
     * @since 1.7.0
     */
    WPRAG.generateEmbeddings = function() {
        console.log('🔄 WPRAG: Generating embeddings');

        // Disable the button during the request.
        var $btn = $('#wprag-generate-embeddings-btn');
        $btn.prop('disabled', true).text(wprag_ajax.i18n.processing);
        console.log('   Button disabled, state: processing');

        // Make the AJAX request.
        WPRAG.ajaxRequest(
            'wprag_generate_embeddings',
            {},
            function(successMsg) {
                console.log('   ✅ Embeddings generated successfully');
                console.log('   Message: ' + successMsg);

                // Re-enable the button.
                $btn.prop('disabled', false).text('Generate Embeddings');

                // Show success message.
                WPRAG.showMessage(successMsg, 'success');

                // Refresh stats after a short delay.
                console.log('   🔄 Refreshing stats...');
                setTimeout(function() {
                    WPRAG.refreshStats();
                }, 500);
            },
            function(errorMsg) {
                console.log('   ❌ Failed to generate embeddings');
                console.log('   Error: ' + errorMsg);

                // Re-enable the button.
                $btn.prop('disabled', false).text('Generate Embeddings');

                // Show error message.
                WPRAG.showMessage(errorMsg, 'error');
            }
        );
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
    WPRAG.showMessage = function(message, type) {
        console.log('💬 WPRAG: Showing message');
        console.log('   Message: ' + message);
        console.log('   Type: ' + type);

        // Get the message container.
        var $msg = $('#wprag-message');

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

    /**
     * Refresh the statistics display.
     * This fetches最新的 stats from the server.
     *
     * @since 1.7.0
     */
    WPRAG.refreshStats = function() {
        console.log('🔄 WPRAG: Refreshing stats');

        // Make the AJAX request.
        WPRAG.ajaxRequest(
            'wprag_get_stats',
            {},
            function(stats) {
                console.log('   ✅ Stats received');
                console.log('   Stats:', stats);

                // Build the stats HTML.
                var html = '<p><strong>Total Posts in RAG Table:</strong> ' + stats.total_posts + '</p>';
                html += '<p><strong>Posts with Embeddings:</strong> ' + stats.embedded_posts + '</p>';
                html += '<p><strong>Full-Text Index:</strong> ';

                if (stats.index_exists) {
                    html += '<span style="color: green;">✅ ' + stats.index_name + '</span>';
                } else {
                    html += '<span style="color: red;">❌ Not Created</span>';
                }

                html += '</p>';

                // Update the stats container.
                $('#wprag-stats-container').html(html);
                console.log('   ✅ Stats display updated');
            },
            function(errorMsg) {
                console.log('   ❌ Failed to refresh stats');
                console.log('   Error: ' + errorMsg);
            }
        );
    };

    // ============================================================================
    // EXPOSE WPRAG TO GLOBAL SCOPE
    // ============================================================================

    // Expose the WPRAG namespace to the global scope.
    window.WPRAG = WPRAG;

    // ============================================================================
    // END OF FILE
    // ============================================================================

})(jQuery);

// ============================================================================
// ADDITIONAL CONSOLE LOG HELPERS
// ============================================================================

// Log that the script has loaded.
console.log('📜 WPRAG Admin Main JS: Script loaded and ready');
console.log('   Version: 1.7.0');
console.log('   Loaded at: ' + new Date().toISOString());
