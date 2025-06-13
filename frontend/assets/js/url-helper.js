/**
 * URL Helper JavaScript module for generating pretty URLs
 */

class UrlHelper {
    
    static getBasePath() {
        const basePath = window.location.pathname.split('/').slice(0, -1).join('/');
        return basePath || '';
    }
    
    static base(path = '') {
        const basePath = this.getBasePath();
        if (path && !path.startsWith('/')) {
            path = '/' + path;
        }
        return basePath + path;
    }
    
    static calendar(year = null, month = null, day = null) {
        if (year && month && day) {
            return this.base(`/calendar/${year}/${month}/${day}`);
        } else if (year && month) {
            return this.base(`/calendar/${year}/${month}`);
        }
        return this.base('/calendar');
    }
    
    static events(eventId = null, action = null) {
        if (eventId && action === 'edit') {
            return this.base(`/events/${eventId}/edit`);
        } else if (eventId) {
            return this.base(`/events/${eventId}`);
        }
        return this.base('/events');
    }
    
    static users(userId = null) {
        if (userId) {
            return this.base(`/users/${userId}`);
        }
        return this.base('/users');
    }
    
    static userEvents(userId) {
        return this.base(`/users/${userId}/events`);
    }
    
    static import() {
        return this.base('/import');
    }
    
    static api(endpoint = '') {
        if (endpoint && !endpoint.startsWith('/')) {
            endpoint = '/' + endpoint;
        }
        return this.base('/api' + endpoint);
    }
    
    static redirect(url) {
        if (!url.startsWith('http')) {
            url = this.base(url);
        }
        window.location.href = url;
    }
    
    static isActive(path, exact = false) {
        const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
        path = path.replace(/\/$/, '') || '/';
        
        if (exact) {
            return currentPath === path;
        }
        
        return currentPath.startsWith(path);
    }
    
    static updateBrowserUrl(url, title = null) {
        if (!url.startsWith('http')) {
            url = this.base(url);
        }
        
        if (title) {
            document.title = title;
        }
        
        history.pushState(null, title, url);
    }
    
    static getCurrentParams() {
        return window.urlParameters || {};
    }
}

// Make it available globally
window.UrlHelper = UrlHelper;

// Export for ES6 modules
export default UrlHelper;