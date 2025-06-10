// Universal Component Initializer and Registry
// Location: frontend/assets/js/components.js
// 
// This file provides the foundation for the component architecture where
// PHP components generate HTML with data attributes and JS handles all behavior

// =============================================================================
// COMPONENT REGISTRY
// =============================================================================

/**
 * Global component registry for managing component types and instances
 */
window.ComponentRegistry = window.ComponentRegistry || {
    components: {},
    instances: {},
    
    /**
     * Register a component type with its initialization and cleanup functions
     */
    register: function(type, definition) {
        if (typeof definition.init !== 'function') {
            console.error(`Component ${type} must have an init function`);
            return false;
        }
        
        this.components[type] = {
            init: definition.init,
            destroy: definition.destroy || function() {},
            update: definition.update || function() {},
            getInstance: definition.getInstance || function() { return null; }
        };
        
        console.log(`Component registered: ${type}`);
        return true;
    },
    
    /**
     * Get component definition by type
     */
    getComponent: function(type) {
        return this.components[type] || null;
    },
    
    /**
     * Store component instance reference
     */
    setInstance: function(id, instance, type) {
        this.instances[id] = {
            instance: instance,
            type: type,
            element: instance.element || null,
            initialized: true,
            lastUpdate: Date.now()
        };
    },
    
    /**
     * Get component instance by ID
     */
    getInstance: function(id) {
        return this.instances[id] || null;
    },
    
    /**
     * Remove component instance
     */
    removeInstance: function(id) {
        if (this.instances[id]) {
            delete this.instances[id];
            return true;
        }
        return false;
    },
    
    /**
     * Get all instances of a component type
     */
    getInstancesByType: function(type) {
        return Object.keys(this.instances)
            .filter(id => this.instances[id].type === type)
            .map(id => this.instances[id]);
    }
};

// =============================================================================
// DATA ATTRIBUTE UTILITIES
// =============================================================================

/**
 * Utilities for working with data attributes
 */
const DataAttributeUtils = {
    /**
     * Safely parse JSON from data attribute
     */
    parseJSON: function(value, defaultValue = {}) {
        if (!value) return defaultValue;
        
        try {
            return JSON.parse(value);
        } catch (error) {
            console.warn('Failed to parse JSON from data attribute:', value, error);
            return defaultValue;
        }
    },
    
    /**
     * Parse boolean from string
     */
    parseBoolean: function(value, defaultValue = false) {
        if (value === 'true') return true;
        if (value === 'false') return false;
        return defaultValue;
    },
    
    /**
     * Parse number from string
     */
    parseNumber: function(value, defaultValue = 0) {
        const parsed = parseFloat(value);
        return isNaN(parsed) ? defaultValue : parsed;
    },
    
    /**
     * Get all component data from element
     */
    getComponentData: function(element) {
        const dataset = element.dataset;
        
        return {
            component: dataset.component,
            componentId: dataset.componentId || element.id,
            componentVariant: dataset.componentVariant || 'default',
            config: this.parseJSON(dataset.config, {}),
            autoInit: this.parseBoolean(dataset.autoInit, true),
            realTime: this.parseBoolean(dataset.realTime, false),
            permissions: this.parseJSON(dataset.permissions, {}),
            state: dataset.state || 'initial',
            apiUrl: dataset.apiUrl,
            apiMethod: dataset.apiMethod || 'GET',
            validation: dataset.validation || 'none',
            debug: this.parseBoolean(dataset.debug, false)
        };
    },
    
    /**
     * Set component state
     */
    setState: function(element, state) {
        element.dataset.state = state;
        element.dispatchEvent(new CustomEvent('component:stateChange', {
            detail: { state: state, element: element }
        }));
    }
};

// =============================================================================
// COMPONENT INITIALIZER
// =============================================================================

/**
 * Main component initialization system
 */
const ComponentInitializer = {
    initialized: false,
    debugMode: false,
    
    /**
     * Initialize all components on the page
     */
    initializeAll: function() {
        if (this.initialized) {
            console.warn('Components already initialized');
            return;
        }
        
        console.log('Starting component initialization...');
        
        // Check for global debug mode
        this.debugMode = document.body.dataset.debug === 'true';
        
        // Find all elements with data-component attribute
        const elements = document.querySelectorAll('[data-component]');
        console.log(`Found ${elements.length} components to initialize`);
        
        elements.forEach((element, index) => {
            try {
                this.initializeComponent(element);
            } catch (error) {
                console.error(`Failed to initialize component ${index}:`, error, element);
            }
        });
        
        this.initialized = true;
        console.log('Component initialization complete');
        
        // Emit global event
        document.dispatchEvent(new CustomEvent('components:initialized', {
            detail: { count: elements.length }
        }));
    },
    
    /**
     * Initialize a single component
     */
    initializeComponent: function(element) {
        const data = DataAttributeUtils.getComponentData(element);
        
        if (this.debugMode || data.debug) {
            console.log('Initializing component:', data.component, data);
        }
        
        // Check if auto-init is disabled
        if (!data.autoInit) {
            if (data.debug) console.log('Auto-init disabled for component:', data.componentId);
            return;
        }
        
        // Get component definition
        const componentDef = ComponentRegistry.getComponent(data.component);
        if (!componentDef) {
            console.warn(`Component type "${data.component}" not registered`);
            return;
        }
        
        // Set loading state
        DataAttributeUtils.setState(element, 'loading');
        
		try {
			// Check if required dependencies are loaded
			if (data.component === 'calendar' && typeof FullCalendar === 'undefined') {
				console.warn('FullCalendar not loaded yet, deferring initialization');
				setTimeout(() => this.initializeComponent(element), 100);
				return;
			}

			if (data.component === 'datatable' && typeof $.fn.DataTable === 'undefined') {
				console.warn('DataTables not loaded yet, deferring initialization');
				setTimeout(() => this.initializeComponent(element), 100);
				return;
			}

			// Initialize component
			const instance = componentDef.init(element, data);
            
            if (instance) {
                // Store instance reference
                ComponentRegistry.setInstance(data.componentId, instance, data.component);
                
                // Set ready state
                DataAttributeUtils.setState(element, 'ready');
                
                if (data.debug) {
                    console.log('Component initialized successfully:', data.componentId);
                }
                
                // Emit component-specific event
                element.dispatchEvent(new CustomEvent('component:initialized', {
                    detail: { 
                        component: data.component,
                        componentId: data.componentId,
                        instance: instance
                    }
                }));
            } else {
                throw new Error('Component init function returned null/undefined');
            }
            
        } catch (error) {
            console.error(`Failed to initialize component ${data.componentId}:`, error);
            DataAttributeUtils.setState(element, 'error');
            
            // Emit error event
            element.dispatchEvent(new CustomEvent('component:error', {
                detail: { 
                    component: data.component,
                    componentId: data.componentId,
                    error: error
                }
            }));
        }
    },
    
    /**
     * Reinitialize components (useful for dynamic content)
     */
    reinitialize: function(container = document) {
        const elements = container.querySelectorAll('[data-component][data-state="initial"]');
        
        elements.forEach(element => {
            this.initializeComponent(element);
        });
    },
    
    /**
     * Destroy all components
     */
    destroyAll: function() {
        Object.keys(ComponentRegistry.instances).forEach(id => {
            this.destroyComponent(id);
        });
        
        this.initialized = false;
    },
    
    /**
     * Destroy a specific component
     */
    destroyComponent: function(componentId) {
        const instanceData = ComponentRegistry.getInstance(componentId);
        
        if (instanceData) {
            const componentDef = ComponentRegistry.getComponent(instanceData.type);
            
            if (componentDef && componentDef.destroy) {
                try {
                    componentDef.destroy(instanceData.instance);
                } catch (error) {
                    console.error(`Error destroying component ${componentId}:`, error);
                }
            }
            
            ComponentRegistry.removeInstance(componentId);
            
            if (instanceData.element) {
                DataAttributeUtils.setState(instanceData.element, 'destroyed');
            }
        }
    }
};

// =============================================================================
// GLOBAL EVENT HANDLING
// =============================================================================

/**
 * Global event system for component communication
 */
const ComponentEvents = {
    /**
     * Broadcast event to all components of a specific type
     */
    broadcast: function(componentType, eventName, data) {
        const instances = ComponentRegistry.getInstancesByType(componentType);
        
        instances.forEach(instanceData => {
            if (instanceData.element) {
                instanceData.element.dispatchEvent(new CustomEvent(eventName, {
                    detail: data
                }));
            }
        });
    },
    
    /**
     * Send event to specific component instance
     */
    send: function(componentId, eventName, data) {
        const instanceData = ComponentRegistry.getInstance(componentId);
        
        if (instanceData && instanceData.element) {
            instanceData.element.dispatchEvent(new CustomEvent(eventName, {
                detail: data
            }));
        }
    },
    
    /**
     * Listen for global component events
     */
    listen: function(eventName, callback) {
        document.addEventListener(eventName, callback);
    }
};

// =============================================================================
// API INTEGRATION HELPERS
// =============================================================================

/**
 * Helpers for API integration and real-time updates
 */
const ComponentAPI = {
    /**
     * Make API request with component context
     */
    request: function(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        };
        
        return fetch(url, { ...defaultOptions, ...options })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            });
    },
    
    /**
     * Setup SSE connection for real-time updates
     */
    setupSSE: function(url, handlers = {}) {
        const eventSource = new EventSource(url);
        
        // Default handlers
        eventSource.onopen = function() {
            console.log('SSE connection established');
            ComponentEvents.broadcast('*', 'sse:connected', {});
        };
        
        eventSource.onerror = function(error) {
            console.error('SSE connection error:', error);
            ComponentEvents.broadcast('*', 'sse:error', { error });
        };
        
        // Custom event handlers
        Object.keys(handlers).forEach(eventType => {
            eventSource.addEventListener(eventType, function(event) {
                try {
                    const data = JSON.parse(event.data);
                    handlers[eventType](data);
                } catch (error) {
                    console.error(`Error handling SSE event ${eventType}:`, error);
                }
            });
        });
        
        return eventSource;
    }
};

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

/**
 * Common utility functions for components
 */
const ComponentUtils = {
    /**
     * Debounce function execution
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    /**
     * Throttle function execution
     */
    throttle: function(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    /**
     * Generate unique ID
     */
    generateId: function(prefix = 'component') {
        return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    },
    
    /**
     * Get element by selector with error handling
     */
    getElement: function(selector, context = document) {
        try {
            return context.querySelector(selector);
        } catch (error) {
            console.error('Invalid selector:', selector, error);
            return null;
        }
    },
    
    /**
     * Show user notification
     */
    showNotification: function(message, type = 'info', duration = 5000) {
        // Try to use global notification system if available
        if (window.showNotification) {
            return window.showNotification(message, type, duration);
        }
        
        // Fallback to console
        const logMethod = type === 'error' ? 'error' : 'log';
        console[logMethod](`[${type.toUpperCase()}] ${message}`);
    }
};

// =============================================================================
// INITIALIZATION
// =============================================================================

/**
 * Auto-initialize when DOM is ready
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        ComponentInitializer.initializeAll();
    });
} else {
    // DOM already loaded
    ComponentInitializer.initializeAll();
}

/**
 * Handle dynamic content loading
 */
document.addEventListener('content:loaded', function(event) {
    if (event.detail && event.detail.container) {
        ComponentInitializer.reinitialize(event.detail.container);
    }
});

/**
 * Cleanup on page unload
 */
window.addEventListener('beforeunload', function() {
    ComponentInitializer.destroyAll();
});

// =============================================================================
// GLOBAL EXPORTS
// =============================================================================

// Make key objects available globally
window.ComponentRegistry = ComponentRegistry;
window.ComponentInitializer = ComponentInitializer;
window.ComponentEvents = ComponentEvents;
window.ComponentAPI = ComponentAPI;
window.ComponentUtils = ComponentUtils;
window.DataAttributeUtils = DataAttributeUtils;