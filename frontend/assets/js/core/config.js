/**
 * Configuration object for API endpoints and settings
 * Location: frontend/js/core/config.js
 */

// Function to determine the base URL for the application
export function getBaseUrl() {
    const currentPath = window.location.pathname;
    
    // If we're in a subdirectory like frontend/pages/, go up to the app root
    if (currentPath.includes('/frontend/pages/')) {
        return currentPath.split('/frontend/pages/')[0];
    }
    
    // If we're at the root of the app, use the directory path
    const pathParts = currentPath.split('/');
    pathParts.pop(); // Remove the current file name
    return pathParts.join('/') || '/';
}

export const Config = {
    apiEndpoints: {
        api: getBaseUrl() + '/backend/api.php',
        sse: getBaseUrl() + '/backend/workers/sse.php'
    },
    calendar: {
        defaultView: 'dayGridMonth',
        snapDuration: '00:05:00',
        timeInterval: 15 // minutes
    },
    sse: {
        maxReconnectAttempts: 10,
        baseReconnectDelay: 1000,
        maxReconnectDelay: 30000
    }
};