/**
 * Handles authentication checks and redirects
 * Location: frontend/js/auth/auth-guard.js
 */
import { APIClient } from '../core/api-client.js';
import { EventBus } from '../core/event-bus.js';
import { Config } from '../core/config.js';

export const AuthGuard = (() => {
    let currentUser = null;
    
    const checkAuthentication = async () => {
        // Check if currentUser is already set by PHP
        if (window.currentUser) {
            currentUser = window.currentUser;
            EventBus.emit('auth:authenticated', { user: currentUser });
            return true;
        }
        
        // Fallback to API check
        try {
            const response = await APIClient.checkAuth();
            
            if (response.authenticated) {
                currentUser = response.user;
                EventBus.emit('auth:authenticated', { user: response.user });
                return true;
            } else {
                redirectToLogin();
                return false;
            }
        } catch (error) {
            console.error('Authentication check failed:', error);
            redirectToLogin();
            return false;
        }
    };
    
    const redirectToLogin = () => {
        window.location.href = '../login.php';
    };
    
    const getCurrentUser = () => currentUser;
    
    const logout = async () => {
        try {
            await fetch(Config.apiEndpoints.api, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ action: 'logout' })
            });
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            redirectToLogin();
        }
    };
    
    // Event listeners
    EventBus.on('auth:unauthorized', redirectToLogin);
    
    return {
        checkAuthentication,
        getCurrentUser,
        logout
    };
})();