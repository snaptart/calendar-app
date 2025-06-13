/**
 * Configuration object for API endpoints and settings
 * Location: frontend/js/core/config.js
 */
export const Config = {
    apiEndpoints: {
        api: 'backend/api.php',
        sse: 'backend/workers/sse.php'
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