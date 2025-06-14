/**
 * Modular Collaborative Calendar Application Entry Point
 * Location: frontend/js/script.js
 * 
 * This file provides backward compatibility by importing the modular application
 * All components have been extracted to separate modules for better maintainability
 */

// Import and initialize the modular application
import { CollaborativeCalendarApp } from './app.js';

// SPA handles initialization - removed DOMContentLoaded listener to prevent double init

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    CollaborativeCalendarApp.destroy();
});