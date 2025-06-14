/**
 * SPA Application Controller
 * Manages the single page application lifecycle and routing
 */

import { router } from './core/router.js';
import { Config, getBaseUrl } from './core/config.js';

class SPAApplication {
    constructor() {
        this.initialized = false;
        this.pageModules = new Map();
    }
    
    /**
     * Initialize the SPA application
     */
    async init() {
        if (this.initialized) return;
        
        console.log('Initializing SPA Application...');
        
        try {
            // Register all routes
            this.registerRoutes();
            
            // Start the router
            await router.start();
            
            this.initialized = true;
            console.log('SPA Application initialized successfully');
            
        } catch (error) {
            console.error('Failed to initialize SPA application:', error);
        }
    }
    
    /**
     * Load core modules that should be available across all pages
     */
    async loadCoreModules() {
        console.log('Loading core modules...');
        
        try {
            const baseUrl = getBaseUrl();
            // Load core authentication and SSE functionality
            await router.loadScript(`${baseUrl}/frontend/assets/js/auth/auth-guard.js`);
            await router.loadScript(`${baseUrl}/frontend/assets/js/auth/user-manager.js`);
            await router.loadScript(`${baseUrl}/frontend/assets/js/realtime/sse-manager.js`);
            await router.loadScript(`${baseUrl}/frontend/assets/js/ui/ui-manager.js`);
            await router.loadScript(`${baseUrl}/frontend/assets/js/ui/modal-manager.js`);
            await router.loadScript(`${baseUrl}/frontend/assets/js/core/event-bus.js`);
            
            console.log('Core modules loaded successfully');
            
        } catch (error) {
            console.error('Failed to load core modules:', error);
            throw error;
        }
    }
    
    /**
     * Register all application routes
     */
    registerRoutes() {
        // Calendar route (default)
        router.register('/', async () => this.loadModule('calendar'));
        router.register('/calendar', async () => this.loadModule('calendar'));
        
        // Events route
        router.register('/events', async () => this.loadModule('events'));
        
        // Users route  
        router.register('/users', async () => this.loadModule('users'));
        
        // Programs route
        router.register('/programs', async () => this.loadModule('programs'));
        
        // Import route
        router.register('/import', async () => this.loadModule('import'));
    }
    
    /**
     * Load a page module dynamically
     */
    async loadModule(moduleName) {
        // Check if module is already loaded
        if (this.pageModules.has(moduleName)) {
            return this.pageModules.get(moduleName);
        }
        
        console.log(`Loading module: ${moduleName}`);
        
        try {
            // Get page configuration
            const config = await this.getPageConfig(moduleName);
            
            // Load required CSS
            if (config.styles) {
                await router.loadStyles(config.styles);
            }
            
            // Load required external dependencies
            if (config.requires) {
                await this.loadDependencies(config.requires);
            }
            
            // Modules are already loaded in index.php, no need to load them dynamically
            
            // Load the page module
            const module = await this.createPageModule(moduleName, config);
            
            // Cache the module
            this.pageModules.set(moduleName, module);
            
            return module;
            
        } catch (error) {
            console.error(`Failed to load module ${moduleName}:`, error);
            throw error;
        }
    }
    
    /**
     * Create a page module with lifecycle methods
     */
    async createPageModule(moduleName, config) {
        const module = {
            name: moduleName,
            config: config,
            
            async load() {
                console.log(`Loading content for ${moduleName}...`);
                
                // Load page content via main API
                const response = await fetch(`${Config.apiEndpoints.api}?action=get_page_content&page=${moduleName}`, {
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error(`Failed to load page content: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                // Update main content
                const mainContent = document.getElementById('main-content');
                if (mainContent) {
                    mainContent.innerHTML = data.content;
                }
                
                // Update sidebar if needed
                const sidebar = document.querySelector('.sidebar');
                if (config.sidebar && sidebar) {
                    sidebar.style.display = 'block';
                    if (data.sidebar) {
                        sidebar.innerHTML = data.sidebar;
                    }
                } else if (sidebar) {
                    sidebar.style.display = 'none';
                }
                
                // Update page title
                if (config.title) {
                    document.title = `${config.title} - Ice Time Finder`;
                }
            },
            
            async init() {
                console.log(`Initializing ${moduleName} module...`);
                
                // Trigger module-specific initialization
                const initEvent = new CustomEvent(`module:${moduleName}:init`);
                document.dispatchEvent(initEvent);
                
                // Update window globals for backwards compatibility
                window.currentPage = moduleName;
            },
            
            async cleanup() {
                console.log(`Cleaning up ${moduleName} module...`);
                
                // Trigger module-specific cleanup
                const cleanupEvent = new CustomEvent(`module:${moduleName}:cleanup`);
                document.dispatchEvent(cleanupEvent);
                
                // Clean up any timers, event listeners, etc.
                // This is handled by individual modules listening to the cleanup event
            }
        };
        
        return module;
    }
    
    /**
     * Get page configuration from server
     */
    async getPageConfig(pageName) {
        try {
            const response = await fetch(`${Config.apiEndpoints.api}?action=get_page_config&page=${pageName}`, {
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`Failed to get page config: ${response.statusText}`);
            }
            
            return await response.json();
            
        } catch (error) {
            console.error('Failed to get page config:', error);
            // Return default config
            return {
                title: pageName.charAt(0).toUpperCase() + pageName.slice(1),
                styles: ['components.css'],
                scripts: [`${pageName}.js`],
                requires: [],
                sidebar: true
            };
        }
    }
    
    /**
     * Load external dependencies
     */
    async loadDependencies(dependencies) {
        try {
            const response = await fetch(Config.apiEndpoints.api, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ action: 'get_dependencies', dependencies })
            });
            
            if (!response.ok) {
                throw new Error(`Failed to get dependencies: ${response.statusText}`);
            }
            
            const deps = await response.json();
            
            // Load CSS dependencies
            for (const dep of dependencies) {
                if (deps[dep] && deps[dep].css) {
                    for (const css of deps[dep].css) {
                        if (!document.querySelector(`link[href="${css}"]`)) {
                            const link = document.createElement('link');
                            link.rel = 'stylesheet';
                            link.href = css;
                            document.head.appendChild(link);
                        }
                    }
                }
            }
            
            // Load JS dependencies
            for (const dep of dependencies) {
                if (deps[dep] && deps[dep].js) {
                    for (const js of deps[dep].js) {
                        if (!document.querySelector(`script[src="${js}"]`)) {
                            await new Promise((resolve, reject) => {
                                const script = document.createElement('script');
                                script.src = js;
                                script.onload = resolve;
                                script.onerror = reject;
                                document.head.appendChild(script);
                            });
                        }
                    }
                }
            }
            
        } catch (error) {
            console.error('Failed to load dependencies:', error);
        }
    }
    
    /**
     * Navigate to a specific page
     */
    async navigate(path) {
        return router.navigate(path);
    }
    
    /**
     * Destroy the application
     */
    destroy() {
        console.log('Destroying SPA Application...');
        
        // Cleanup all modules
        for (const [name, module] of this.pageModules) {
            if (module && typeof module.cleanup === 'function') {
                module.cleanup();
            }
        }
        
        router.destroy();
        this.pageModules.clear();
        this.initialized = false;
    }
}

// Create and export singleton instance
export const spaApp = new SPAApplication();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => spaApp.init());
} else {
    spaApp.init();
}