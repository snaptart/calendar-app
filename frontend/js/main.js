/**
 * Application initialization and entry point
 * Location: frontend/js/main.js
 */
import { CollaborativeCalendarApp } from './app.js';

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    CollaborativeCalendarApp.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    CollaborativeCalendarApp.destroy();
});