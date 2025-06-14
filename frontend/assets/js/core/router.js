/**
 * SPA Router for Calendar Application
 * Handles client-side routing and module loading
 */

class Router {
    constructor() {
        this.routes = {};
        this.currentModule = null;
        this.loadedScripts = new Set();
        this.isNavigating = false;
        
        // Bind event handlers
        this.handleHashChange = this.handleHashChange.bind(this);
        this.handleLinkClick = this.handleLinkClick.bind(this);
        
        // Set up event listeners
        window.addEventListener('hashchange', this.handleHashChange);
        document.addEventListener('click', this.handleLinkClick);
    }
    
    /**
     * Register a route with its module loader
     */
    register(path, moduleLoader) {
        this.routes[path] = moduleLoader;
    }
    
    /**
     * Navigate to a specific route
     */
    async navigate(path, updateHistory = true) {
        if (this.isNavigating) return;
        this.isNavigating = true;
        
        try {
            // Update URL hash if needed
            if (updateHistory && window.location.hash !== '#' + path) {
                window.location.hash = '#' + path;
            }
            
            // Clean up current module
            if (this.currentModule && typeof this.currentModule.cleanup === 'function') {
                await this.currentModule.cleanup();
            }
            
            // Load new module
            const moduleLoader = this.routes[path] || this.routes['/'];
            if (!moduleLoader) {
                throw new Error(`Route not found: ${path}`);
            }
            
            const module = await moduleLoader();
            
            // Load the module content
            if (typeof module.load === 'function') {
                await module.load();
            }
            
            // Initialize the module
            if (typeof module.init === 'function') {
                await module.init();
            }
            
            this.currentModule = module;
            this.updateActiveNavigation(path);
            
        } catch (error) {
            console.error('Navigation error:', error);
            // Fallback to default route on error
            if (path !== '/') {
                await this.navigate('/', updateHistory);
            }
        } finally {
            this.isNavigating = false;
        }
    }
    
    /**
     * Handle browser back/forward buttons and hash changes
     */
    async handleHashChange(event) {
        const hash = window.location.hash;
        const path = hash.startsWith('#') ? hash.substring(1) : '/';
        await this.navigate(path, false);
    }
    
    /**
     * Handle link clicks for SPA navigation
     */
    handleLinkClick(event) {
        // Check if it's a navigation link
        const link = event.target.closest('[data-route]');
        if (!link) return;
        
        event.preventDefault();
        const route = link.getAttribute('data-route');
        this.navigate(route);
    }
    
    /**
     * Update active navigation state
     */
    updateActiveNavigation(currentPath) {
        // Remove active class from all nav items
        document.querySelectorAll('[data-route]').forEach(link => {
            link.classList.remove('active');
        });
        
        // Add active class to current route
        const activeLink = document.querySelector(`[data-route="${currentPath}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }
    
    /**
     * Load a script dynamically if not already loaded
     */
    async loadScript(src) {
        if (this.loadedScripts.has(src)) {
            return Promise.resolve();
        }
        
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.type = 'module';
            script.onload = () => {
                this.loadedScripts.add(src);
                resolve();
            };
            script.onerror = (error) => {
                console.error(`Failed to load script: ${src}`, error);
                reject(error);
            };
            document.head.appendChild(script);
        });
    }
    
    /**
     * Load CSS dynamically if not already loaded
     */
    async loadStyles(hrefs) {
        const promises = hrefs.map(href => {
            if (document.querySelector(`link[href*="${href}"]`)) {
                return Promise.resolve();
            }
            
            return new Promise((resolve, reject) => {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = `frontend/assets/css/${href}`;
                link.onload = resolve;
                link.onerror = reject;
                document.head.appendChild(link);
            });
        });
        
        return Promise.all(promises);
    }
    
    /**
     * Get current route path
     */
    getCurrentPath() {
        const hash = window.location.hash;
        return hash.startsWith('#') ? hash.substring(1) : '/';
    }
    
    /**
     * Start the router
     */
    async start() {
        const currentPath = this.getCurrentPath();
        await this.navigate(currentPath, false);
    }
    
    /**
     * Cleanup router
     */
    destroy() {
        window.removeEventListener('hashchange', this.handleHashChange);
        document.removeEventListener('click', this.handleLinkClick);
        
        if (this.currentModule && typeof this.currentModule.cleanup === 'function') {
            this.currentModule.cleanup();
        }
    }
}

// Create and export singleton instance
export const router = new Router();