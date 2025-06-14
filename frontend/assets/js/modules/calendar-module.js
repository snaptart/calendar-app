/**
 * Calendar SPA Module
 * Integrates the existing calendar functionality with SPA lifecycle
 */

import { CollaborativeCalendarApp } from '../app.js';

class CalendarModule {
    constructor() {
        this.name = 'calendar';
        this.initialized = false;
        this.calendarApp = null;
    }
    
    /**
     * Initialize the calendar module
     */
    async init() {
        if (this.initialized) return;
        
        console.log('Initializing Calendar Module...');
        
        try {
            // Initialize the collaborative calendar app
            await CollaborativeCalendarApp.init();
            this.initialized = true;
            console.log('Calendar Module initialized successfully');
            
        } catch (error) {
            console.error('Failed to initialize Calendar Module:', error);
            throw error;
        }
    }
    
    /**
     * Cleanup the calendar module
     */
    async cleanup() {
        if (!this.initialized) return;
        
        console.log('Cleaning up Calendar Module...');
        
        try {
            // Destroy the collaborative calendar app
            CollaborativeCalendarApp.destroy();
            this.initialized = false;
            console.log('Calendar Module cleaned up successfully');
            
        } catch (error) {
            console.error('Failed to cleanup Calendar Module:', error);
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
const calendarModule = new CalendarModule();

// Listen for SPA module events
document.addEventListener('module:calendar:init', () => {
    calendarModule.init();
});

document.addEventListener('module:calendar:cleanup', () => {
    calendarModule.cleanup();
});

export { calendarModule };