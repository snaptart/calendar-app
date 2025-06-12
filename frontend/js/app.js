/**
 * Main application controller that coordinates all components with authentication
 * Location: frontend/js/app.js
 */
import { EventBus } from './core/event-bus.js';
import { AuthGuard } from './auth/auth-guard.js';
import { UserManager } from './auth/user-manager.js';
import { DateTimeManager } from './calendar/datetime-manager.js';
import { CalendarManager } from './calendar/calendar-manager.js';
import { EventManager } from './calendar/event-manager.js';
import { UIManager } from './ui/ui-manager.js';
import { ModalManager } from './ui/modal-manager.js';
import { SSEManager } from './realtime/sse-manager.js';

export const CollaborativeCalendarApp = (() => {
    const setupUIEventListeners = () => {
        // Remove user name input handler since it's now disabled
        // User authentication is handled by the login system
        
        // Add event button
        const addEventBtn = document.getElementById('addEventBtn');
        addEventBtn?.addEventListener('click', () => {
            const currentUser = UserManager.getCurrentUser();
            if (!currentUser) {
                UIManager.showNotification('Please login to create events', 'error');
                return;
            }
            EventBus.emit('calendar:dateSelect', {});
        });
        
        // Refresh users button
        const refreshUsersBtn = document.getElementById('refreshUsers');
        refreshUsersBtn?.addEventListener('click', () => {
            UserManager.loadUsers();
        });
    };
    
    const init = async () => {
        console.log('Initializing Collaborative Calendar with authentication...');
        
        // Check authentication first
        const isAuthenticated = await AuthGuard.checkAuthentication();
        
        if (!isAuthenticated) {
            // User is not authenticated, AuthGuard will handle redirect
            return;
        }
        
        // Initialize components in order
        DateTimeManager.initializeDateTimePickers();
        CalendarManager.initializeCalendar();
        ModalManager.setupEventListeners();
        setupUIEventListeners();
        
        // Start SSE connection
        SSEManager.connect();
        
        // Emit app initialization event
        EventBus.emit('app:init');
        
        console.log('Collaborative Calendar initialized successfully');
    };
    
    const destroy = () => {
        SSEManager.disconnect();
        console.log('Collaborative Calendar destroyed');
    };
    
    return {
        init,
        destroy
    };
})();