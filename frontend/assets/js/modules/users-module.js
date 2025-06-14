/**
 * Users SPA Module
 * Integrates the existing users functionality with SPA lifecycle
 */

import { CrudPageFactory } from '../ui/crud-page-factory.js';
import { UsersPageConfig } from '../config/users-page-config.js';
import { ModalManager } from '../ui/modal-manager.js';

class UsersModule {
    constructor() {
        this.name = 'users';
        this.initialized = false;
        this.usersApp = null;
    }
    
    /**
     * Initialize the users module
     */
    async init() {
        if (this.initialized) return;
        
        console.log('Initializing Users Module...');
        
        try {
            // Make ModalManager available globally for config handlers
            window.ModalManager = ModalManager;
            
            // Create the users page using the factory
            this.usersApp = CrudPageFactory.create(UsersPageConfig);
            
            // Initialize the users app
            await this.usersApp.init();
            this.initialized = true;
            console.log('Users Module initialized successfully');
            
        } catch (error) {
            console.error('Failed to initialize Users Module:', error);
            throw error;
        }
    }
    
    /**
     * Cleanup the users module
     */
    async cleanup() {
        if (!this.initialized) return;
        
        console.log('Cleaning up Users Module...');
        
        try {
            // Cleanup the users app
            if (this.usersApp) {
                this.usersApp.cleanup();
                this.usersApp = null;
            }
            this.initialized = false;
            console.log('Users Module cleaned up successfully');
            
        } catch (error) {
            console.error('Failed to cleanup Users Module:', error);
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
const usersModule = new UsersModule();

// Listen for SPA module events
document.addEventListener('module:users:init', () => {
    usersModule.init();
});

document.addEventListener('module:users:cleanup', () => {
    usersModule.cleanup();
});

export { usersModule };