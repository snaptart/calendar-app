/**
 * Modular Collaborative Calendar Application Entry Point
 * Location: frontend/js/script.js
 * 
 * This file provides backward compatibility by importing the modular application
 * All components have been extracted to separate modules for better maintainability
 */

// Import and initialize the modular application
import { CollaborativeCalendarApp } from './app.js';

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    CollaborativeCalendarApp.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    CollaborativeCalendarApp.destroy();
});