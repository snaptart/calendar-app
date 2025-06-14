/**
 * Events SPA Module
 * Integrates the existing events functionality with SPA lifecycle
 */

import { CrudPageFactory } from '../ui/crud-page-factory.js';
import { EventsPageConfig } from '../config/events-page-config.js';
import { ModalManager } from '../ui/modal-manager.js';

class EventsModule {
    constructor() {
        this.name = 'events';
        this.initialized = false;
        this.eventsApp = null;
    }
    
    /**
     * Initialize the events module
     */
    async init() {
        if (this.initialized) return;
        
        console.log('Initializing Events Module...');
        
        try {
            // Make ModalManager available globally for config handlers
            window.ModalManager = ModalManager;
            
            // Create the events page using the factory
            this.eventsApp = CrudPageFactory.create(EventsPageConfig);
            
            // Initialize the events app
            await this.eventsApp.init();
            this.initialized = true;
            console.log('Events Module initialized successfully');
            
        } catch (error) {
            console.error('Failed to initialize Events Module:', error);
            throw error;
        }
    }
    
    /**
     * Cleanup the events module
     */
    async cleanup() {
        if (!this.initialized) return;
        
        console.log('Cleaning up Events Module...');
        
        try {
            // Cleanup the events app
            if (this.eventsApp) {
                this.eventsApp.cleanup();
                this.eventsApp = null;
            }
            this.initialized = false;
            console.log('Events Module cleaned up successfully');
            
        } catch (error) {
            console.error('Failed to cleanup Events Module:', error);
        }
    }
    
    /**
     * Get module status
     */
    isInitialized() {
        return this.initialized;
    }
}

// Create and export singleton instance
const eventsModule = new EventsModule();

// Listen for SPA module events
document.addEventListener('module:events:init', () => {
    eventsModule.init();
});

document.addEventListener('module:events:cleanup', () => {
    eventsModule.cleanup();
});

export { eventsModule };