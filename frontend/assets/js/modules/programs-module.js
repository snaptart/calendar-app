/**
 * Programs SPA Module
 * Integrates the existing programs functionality with SPA lifecycle
 */

import { CrudPageFactory } from '../ui/crud-page-factory.js';
import { ProgramsPageConfig } from '../config/programs-page-config.js';
import { ModalManager } from '../ui/modal-manager.js';

class ProgramsModule {
    constructor() {
        this.name = 'programs';
        this.initialized = false;
        this.programsApp = null;
    }
    
    /**
     * Initialize the programs module
     */
    async init() {
        if (this.initialized) return;
        
        console.log('Initializing Programs Module...');
        
        try {
            // Make ModalManager available globally for config handlers
            window.ModalManager = ModalManager;
            
            // Create the programs page using the factory
            this.programsApp = CrudPageFactory.create(ProgramsPageConfig);
            
            // Initialize the programs app
            await this.programsApp.init();
            this.initialized = true;
            console.log('Programs Module initialized successfully');
            
        } catch (error) {
            console.error('Failed to initialize Programs Module:', error);
            throw error;
        }
    }
    
    /**
     * Cleanup the programs module
     */
    async cleanup() {
        if (!this.initialized) return;
        
        console.log('Cleaning up Programs Module...');
        
        try {
            // Cleanup the programs app
            if (this.programsApp) {
                this.programsApp.cleanup();
                this.programsApp = null;
            }
            this.initialized = false;
            console.log('Programs Module cleaned up successfully');
            
        } catch (error) {
            console.error('Failed to cleanup Programs Module:', error);
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
const programsModule = new ProgramsModule();

// Listen for SPA module events
document.addEventListener('module:programs:init', () => {
    programsModule.init();
});

document.addEventListener('module:programs:cleanup', () => {
    programsModule.cleanup();
});

export { programsModule };