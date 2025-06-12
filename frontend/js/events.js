/**
 * Events Management Page - Refactored to use CrudPageFactory
 * Location: frontend/js/events.js
 * 
 * This file has been refactored to use the shared CrudPageFactory,
 * eliminating code duplication and providing consistent patterns.
 */

// Import the factory and configuration
import { CrudPageFactory } from './ui/crud-page-factory.js';
import { EventsPageConfig } from './config/events-page-config.js';
import { ModalManager } from './ui/modal-manager.js';

// Make ModalManager available globally for config handlers
window.ModalManager = ModalManager;

// Create the events page using the factory
const EventsApp = CrudPageFactory.create(EventsPageConfig);

// =============================================================================
// APPLICATION INITIALIZATION
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing Events app...');
    EventsApp.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    EventsApp.cleanup();
});

// Handle browser back/forward navigation
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        console.log('Page loaded from cache, checking auth...');
        // AuthGuard will be checked during EventsApp.init()
        EventsApp.init();
    }
});