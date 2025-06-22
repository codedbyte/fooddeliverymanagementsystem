// public_html/js/utils.js

/**
 * Fetches data from a given URL using GET request.
 * @param {string} url The URL to fetch data from.
 * @returns {Promise<Object>} A promise that resolves with the parsed JSON response.
 * @throws {Error} If the network request fails or response is not JSON.
 */
async function fetchData(url) {
    try {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json' // Ensure server returns JSON
            }
        });
        const rawText = await response.text(); // Log raw response for debugging
        console.log('[fetchData] Raw response:', rawText); // Log raw response
        if (!response.ok) {
            // Log and throw error with status and body if not ok
            console.error(`[fetchData] HTTP error! Status: ${response.status}, Body:`, rawText);
            throw new Error(`HTTP error! Status: ${response.status}, Details: ${rawText}`);
        }
        try {
            return JSON.parse(rawText); // Try to parse JSON
        } catch (jsonErr) {
            // Log invalid JSON payloads
            console.error('[fetchData] Invalid JSON:', rawText);
            throw new Error('Invalid JSON response');
        }
    } catch (error) {
        console.error('FetchData error:', error);
        throw error; // Re-throw to be caught by the caller
    }
}

/**
 * Sends data to a given URL using POST request with JSON payload.
 * @param {string} url The URL to send data to.
 * @param {Object} data The data object to send as JSON.
 * @returns {Promise<Object>} A promise that resolves with the parsed JSON response.
 * @throws {Error} If the network request fails or response is not JSON.
 */
async function postData(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json' // Ensure server returns JSON
            },
            body: JSON.stringify(data)
        });
        const rawText = await response.text(); // Log raw response for debugging
        console.log('[postData] Raw response:', rawText); // Log raw response
        if (!response.ok) {
            // Log and throw error with status and body if not ok
            console.error(`[postData] HTTP error! Status: ${response.status}, Body:`, rawText);
            throw new Error(`HTTP error! Status: ${response.status}, Details: ${rawText}`);
        }
        try {
            return JSON.parse(rawText); // Try to parse JSON
        } catch (jsonErr) {
            // Log invalid JSON payloads
            console.error('[postData] Invalid JSON:', rawText);
            throw new Error('Invalid JSON response');
        }
    } catch (error) {
        console.error('PostData error:', error);
        throw error; // Re-throw to be caught by the caller
    }
}

// Add more utility functions as needed, e.g.:
// - debounce(func, delay)
// - throttle(func, delay)
// - formatCurrency(amount)
// - getUrlParameter(name)