/**
 * Global Toast Notification Helper
 *
 * Provides standardized error message extraction from API responses.
 * Priority: response.message > response.errors[firstField][0] > defaultMessage
 */

/**
 * Extract error message from API response following priority:
 * 1. response.message (if exists and not empty)
 * 2. First error from response.errors (first field, first error)
 * 3. defaultMessage
 *
 * @param {Response|Object} response - Fetch Response object or parsed JSON object
 * @param {string} defaultMessage - Fallback message if no error message found
 * @returns {Promise<string>} - The error message to display
 */
async function getErrorMessage(response, defaultMessage = 'Terjadi kesalahan') {
    let data;

    // Handle both Response object and already-parsed JSON
    if (response instanceof Response) {
        try {
            data = await response.json();
        } catch (e) {
            // If JSON parsing fails, use status text or default
            return response.statusText || defaultMessage;
        }
    } else {
        data = response;
    }

    // Priority 1: Check for message property
    if (data.message && typeof data.message === 'string' && data.message.trim() !== '') {
        return data.message;
    }

    // Priority 2: Check for errors object
    if (data.errors && typeof data.errors === 'object') {
        const errorKeys = Object.keys(data.errors);
        if (errorKeys.length > 0) {
            const firstField = errorKeys[0];
            const firstFieldErrors = data.errors[firstField];
            if (Array.isArray(firstFieldErrors) && firstFieldErrors.length > 0) {
                const firstError = firstFieldErrors[0];
                if (typeof firstError === 'string' && firstError.trim() !== '') {
                    return firstError;
                }
            }
        }
    }

    // Priority 3: Use default message
    return defaultMessage;
}

/**
 * Handle API error response and return appropriate error message
 * For use with fetch() responses
 *
 * @param {Response} response - Fetch Response object
 * @param {string} defaultMessage - Fallback message
 * @returns {Promise<{ok: boolean, message: string, data: Object|null}>}
 */
async function handleApiResponse(response, defaultMessage = 'Terjadi kesalahan') {
    const data = await response.json().catch(() => ({}));

    return {
        ok: response.ok,
        message: response.ok ? (data.message || 'Berhasil') : await getErrorMessage(response, defaultMessage),
        data: data
    };
}

/**
 * Process fetch response and extract error message (non-async version)
 * Requires data to be already parsed
 *
 * @param {Object} data - Parsed JSON response data
 * @param {string} defaultMessage - Fallback message
 * @returns {string} - The error message
 */
function extractErrorMessage(data, defaultMessage = 'Terjadi kesalahan') {
    // Priority 1: Check for message property
    if (data.message && typeof data.message === 'string' && data.message.trim() !== '') {
        return data.message;
    }

    // Priority 2: Check for errors object
    if (data.errors && typeof data.errors === 'object') {
        const errorKeys = Object.keys(data.errors);
        if (errorKeys.length > 0) {
            const firstField = errorKeys[0];
            const firstFieldErrors = data.errors[firstField];
            if (Array.isArray(firstFieldErrors) && firstFieldErrors.length > 0) {
                const firstError = firstFieldErrors[0];
                if (typeof firstError === 'string' && firstError.trim() !== '') {
                    return firstError;
                }
            }
        }
    }

    // Priority 3: Use default message
    return defaultMessage;
}

/**
 * Show toast notification with standardized styling
 *
 * @param {string} message - Message to display
 * @param {string} type - Type of toast: 'success', 'error', 'info', 'warning'
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgClass = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
    const iconClass = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';

    toast.className = `fixed bottom-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 ${bgClass} text-white`;
    toast.innerHTML = `<i class="fa-solid ${iconClass} text-lg flex-shrink-0"></i><span>${escapeHtml(message)}</span>`;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Escape HTML to prevent XSS
 *
 * @param {string} text - Text to escape
 * @returns {string} - Escaped text
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export for module usage (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { getErrorMessage, handleApiResponse, extractErrorMessage, showToast, escapeHtml };
}

// Make functions available globally
window.getErrorMessage = getErrorMessage;
window.handleApiResponse = handleApiResponse;
window.extractErrorMessage = extractErrorMessage;
window.showToast = showToast;
window.escapeHtml = escapeHtml;
