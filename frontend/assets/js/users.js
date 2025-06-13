/**
 * Users Management Page - Refactored to use CrudPageFactory
 * Location: frontend/js/users.js
 * 
 * This file has been refactored to use the shared CrudPageFactory,
 * eliminating code duplication and providing consistent patterns.
 */

// Import the factory and configuration
import { CrudPageFactory } from './ui/crud-page-factory.js';
import { UsersPageConfig } from './config/users-page-config.js';
import { ModalManager } from './ui/modal-manager.js';

// Make ModalManager available globally for config handlers
window.ModalManager = ModalManager;

// Create the users page using the factory
const UsersApp = CrudPageFactory.create(UsersPageConfig);

// =============================================================================
// APPLICATION INITIALIZATION
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing Users app...');
    UsersApp.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    UsersApp.cleanup();
});

// Handle browser back/forward navigation
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        console.log('Page loaded from cache, checking auth...');
        // AuthGuard will be checked during UsersApp.init()
        UsersApp.init();
    }
});