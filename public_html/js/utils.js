// public_html/js/utils.js

/**
 * Fetches data from a given URL using GET request.
 * @param {string} url The URL to fetch data from.
 * @returns {Promise<Object>} A promise that resolves with the parsed JSON response.
 * @throws {Error} If the network request fails or response is not JSON.
 */
async function fetchData(url) {
    try {
        const response = await fetch(url);
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! Status: ${response.status}, Details: ${errorText}`);
        }
        return await response.json();
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
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! Status: ${response.status}, Details: ${errorText}`);
        }
        return await response.json();
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