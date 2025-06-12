/**
 * Centralized API communication with authentication
 * Location: frontend/js/core/api-client.js
 */
import { Config } from './config.js';
import { EventBus } from './event-bus.js';

export const APIClient = (() => {
    const makeRequest = async (url, options = {}) => {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                credentials: 'include', // Include cookies for session
                ...options
            });
            
            if (response.status === 401) {
                // Unauthorized - redirect to login
                EventBus.emit('auth:unauthorized');
                throw new Error('Authentication required');
            }
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API Request failed:', error);
            throw error;
        }
    };
    
    return {
        // Generic request method
        makeRequest(params) {
            if (typeof params === 'string') {
                // If params is a string, treat it as URL
                return makeRequest(params);
            } else if (params.action) {
                // If params has action, build URL
                const url = `${Config.apiEndpoints.api}?action=${params.action}`;
                return makeRequest(url);
            } else {
                // Otherwise, treat as full request config
                return makeRequest(Config.apiEndpoints.api, params);
            }
        },
        
        // Authentication operations
        checkAuth() {
            return makeRequest(`${Config.apiEndpoints.api}?action=check_auth`);
        },
        
        // User operations
        getUsers() {
            return makeRequest(`${Config.apiEndpoints.api}?action=users`);
        },
        
        getUsersWithStats() {
            return makeRequest(`${Config.apiEndpoints.api}?action=users_with_stats`);
        },
        
        // Event operations
        getEvents(userIds = []) {
            const userIdsParam = userIds.length ? `&user_ids=${userIds.join(',')}` : '';
            return makeRequest(`${Config.apiEndpoints.api}?action=events${userIdsParam}`);
        },
        
        getEventsForTable() {
            return makeRequest(`${Config.apiEndpoints.api}?action=events_datatable&length=1000&start=0&draw=1`);
        },
        
        createEvent(eventData) {
            return makeRequest(Config.apiEndpoints.api, {
                method: 'POST',
                body: JSON.stringify(eventData)
            });
        },
        
        updateEvent(eventData) {
            return makeRequest(Config.apiEndpoints.api, {
                method: 'PUT',
                body: JSON.stringify(eventData)
            });
        },
        
        deleteEvent(eventId) {
            return makeRequest(`${Config.apiEndpoints.api}?id=${eventId}`, {
                method: 'DELETE'
            });
        },
        
        // Test endpoint
        testConnection() {
            return makeRequest(`${Config.apiEndpoints.api}?action=test`);
        }
    };
})();