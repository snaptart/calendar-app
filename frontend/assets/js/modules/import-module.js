/**
 * Import SPA Module
 * Integrates the existing import functionality with SPA lifecycle
 */

import { EventBus } from '../core/event-bus.js';
import { Config } from '../core/config.js';
import { Utils } from '../core/utils.js';
import { APIClient } from '../core/api-client.js';
import { AuthGuard } from '../auth/auth-guard.js';
import { UIManager } from '../ui/ui-manager.js';
import { ModalManager } from '../ui/modal-manager.js';
import { ImportManager } from '../features/import-manager.js';

class ImportModule {
    constructor() {
        this.name = 'import';
        this.initialized = false;
        this.importManager = null;
    }
    
    /**
     * Initialize the import module
     */
    async init() {
        if (this.initialized) return;
        
        console.log('Initializing Import Module...');
        
        try {
            // Check authentication first
            const isAuthenticated = await AuthGuard.checkAuthentication();
            
            if (!isAuthenticated) {
                // User is not authenticated, AuthGuard will handle redirect
                return;
            }
            
            // Make managers available globally
            window.ModalManager = ModalManager;
            window.UIManager = UIManager;
            
            // Initialize import manager
            this.importManager = ImportManager;
            await this.importManager.init();
            
            this.initialized = true;
            console.log('Import Module initialized successfully');
            
        } catch (error) {
            console.error('Failed to initialize Import Module:', error);
            throw error;
        }
    }
    
    /**
     * Cleanup the import module
     */
    async cleanup() {
        if (!this.initialized) return;
        
        console.log('Cleaning up Import Module...');
        
        try {
            // Cleanup the import manager
            if (this.importManager) {
                this.importManager.cleanup();
                this.importManager = null;
            }
            this.initialized = false;
            console.log('Import Module cleaned up successfully');
            
        } catch (error) {
            console.error('Failed to cleanup Import Module:', error);
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
const importModule = new ImportModule();

// Listen for SPA module events
document.addEventListener('module:import:init', () => {
    importModule.init();
});

document.addEventListener('module:import:cleanup', () => {
    importModule.cleanup();
});

export { importModule };