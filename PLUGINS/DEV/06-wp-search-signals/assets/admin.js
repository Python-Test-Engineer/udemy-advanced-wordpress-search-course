(function () {
    if (typeof wpSignalsData === 'undefined') {
        console.log('[wp-signals] wpSignalsData not found.');
        return;
    }

    console.log('[wp-signals] Admin script loaded.', wpSignalsData);

    const queryInput = document.querySelector('#ws_query');
    const queryButton = document.querySelector('#ws_query_run');
    const resultsContainer = document.querySelector('#wp-signals-results');
    const debugContainer = document.querySelector('#wp-signals-debug');

    if (!queryInput || !queryButton || !resultsContainer || !debugContainer) {
        console.log('[wp-signals] Missing DOM elements.', {
            queryInput,
            queryButton,
            resultsContainer,
            debugContainer,
        });
        return;
    }

    console.log('[wp-signals] DOM ready.', {
        queryInput,
        queryButton,
        resultsContainer,
        debugContainer,
    });

    let currentQueryId = null;

    const clearResults = () => {
        console.log('[wp-signals] Clearing results.');
        resultsContainer.innerHTML = '';
        currentQueryId = null;
    };

    const renderEmptyState = (message) => {
        console.log('[wp-signals] Render empty state:', message);
        resultsContainer.innerHTML = `<p>${message}</p>`;
    };

    const addDebugEntry = (eventName, payload) => {
        console.log('[wp-signals] Debug entry:', eventName, payload);
        const entry = document.createElement('li');
        entry.innerHTML = `<strong>${eventName}</strong><pre>${JSON.stringify(payload, null, 2)}</pre>`;
        debugContainer.prepend(entry);
    };

    const createQuery = async (queryText, resultIds) => {
        console.log('[wp-signals] Creating query record:', queryText, resultIds);

        const data = new FormData();
        data.append('action', 'wp_signals_create_query');
        data.append('nonce', wpSignalsData.nonce);
        data.append('query_text', queryText);
        data.append('result_ids', JSON.stringify(resultIds));

        try {
            const response = await fetch(wpSignalsData.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data,
            });

            const result = await response.json();
            console.log('[wp-signals] Query created:', result);

            if (result.success && result.data.query_id) {
                currentQueryId = result.data.query_id;
                addDebugEntry('query_created', {
                    query_id: currentQueryId,
                    query_text: queryText,
                    result_ids: resultIds
                });
                return currentQueryId;
            }
        } catch (error) {
            console.log('[wp-signals] Error creating query:', error);
        }

        return null;
    };

    const sendEvent = (eventName, payload, options = {}) => {
        console.log('[wp-signals] Sending event:', eventName, payload);

        const eventPayload = {
            ...payload,
            query_id: currentQueryId
        };

        addDebugEntry(eventName, eventPayload);

        const data = new FormData();
        data.append('action', 'wp_signals_log_event');
        data.append('nonce', wpSignalsData.nonce);
        data.append('event_name', eventName);
        data.append('event_meta_details', JSON.stringify(eventPayload));

        if (currentQueryId) {
            data.append('query_id', currentQueryId);
        }

        if (options.postId) {
            data.append('post_id', options.postId);
        }

        fetch(wpSignalsData.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data,
        })
            .then((response) => {
                console.log('[wp-signals] Event response:', response);
            })
            .catch((error) => {
                console.log('[wp-signals] Event error:', error);
            });
    };

    const createResultCard = (item) => {
        console.log('[wp-signals] Creating card for item:', item);
        const card = document.createElement('div');
        card.className = 'wp-signals-card';

        const title = item.title || item.post_title || item.heading || 'Untitled';
        const content = item.content || item.excerpt || item.summary || '';
        const permalink = item.permalink || item.link || item.url || '#';
        const postId = item.post_id || item.id || '';

        const body = document.createElement('div');
        body.className = 'wp-signals-body';

        const titleEl = document.createElement('div');
        titleEl.className = 'wp-signals-title';
        titleEl.textContent = title;

        const idEl = document.createElement('div');
        idEl.className = 'wp-signals-post-id';
        idEl.textContent = `Post ID: ${postId || 'N/A'}`;

        const contentEl = document.createElement('div');
        contentEl.className = 'wp-signals-content';
        contentEl.textContent = content || 'No content preview.';

        body.append(titleEl, idEl, contentEl);

        let hoverLogged = false;
        body.addEventListener('mouseenter', () => {
            if (hoverLogged) {
                return;
            }
            hoverLogged = true;
            console.log('[wp-signals] Hover on result textbox:', item);
            sendEvent(
                'event_hover',
                {
                    postId,
                    label: title,
                },
                {
                    postId
                }
            );
        });

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'button button-secondary wp-signals-record';
        button.textContent = '✓ Mark as Useful';
        button.addEventListener('click', () => {
            console.log('[wp-signals] Record Click button pressed for item:', item);
            sendEvent(
                'event_useful',
                {
                    postId,
                    label: title,
                },
                {
                    postId
                }
            );
        });

        card.append(body, button);
        return card;
    };

    const fetchResults = async (query) => {
        console.log('[wp-signals] Fetching results for query:', query);
        clearResults();
        renderEmptyState('Loading results...');

        try {
            const limit = Number(wpSignalsData.limit) || 3;
            const url = new URL(wpSignalsData.hybridSearchUrl, window.location.origin);
            url.searchParams.set('query', query);
            url.searchParams.set('limit', String(limit));

            const response = await fetch(url.toString());

            console.log('[wp-signals] Raw response:', response);

            if (!response.ok) {
                throw new Error('Failed to fetch results.');
            }

            const payload = await response.json();
            console.log('[wp-signals] Parsed payload:', payload);
            const items = Array.isArray(payload)
                ? payload
                : payload?.results || payload?.data || [];

            console.log('[wp-signals] Normalized items:', items);

            const trimmedItems = items.slice(0, 3);
            console.log('[wp-signals] Trimmed items (take first 3):', trimmedItems);

            clearResults();

            if (!trimmedItems.length) {
                renderEmptyState('No results found.');
                return;
            }

            // Extract result IDs
            const resultIds = trimmedItems
                .map((item) => item.post_id || item.id)
                .filter(Boolean)
                .map(id => parseInt(id, 10));

            // Create query record first
            await createQuery(query, resultIds);

            // Render results
            trimmedItems.forEach((item) => {
                resultsContainer.appendChild(createResultCard(item));
            });

            // Log search event with query_id
            sendEvent(
                'event_search',
                {
                    query: query,
                    results: trimmedItems,
                    resultCount: trimmedItems.length,
                    resultIds: resultIds
                }
            );
        } catch (error) {
            console.log('[wp-signals] Fetch error:', error);
            clearResults();
            renderEmptyState('Unable to load results.');
        }
    };

    queryButton.addEventListener('click', () => {
        console.log('[wp-signals] Run query clicked.');
        const query = queryInput.value.trim();
        if (!query) {
            renderEmptyState('Please enter a query.');
            return;
        }
        fetchResults(query);
    });

    queryInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            console.log('[wp-signals] Enter pressed in query input.');
            event.preventDefault();
            queryButton.click();
        }
    });
})();
