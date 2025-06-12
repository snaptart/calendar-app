/**
 * Utility functions
 * Location: frontend/js/core/utils.js
 */
export const Utils = {
    debounce(func, wait) {
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
    
    formatDateTimeForAPI(dateTimeStr) {
        if (!dateTimeStr) return '';
        
        const parts = dateTimeStr.split(' ');
        if (parts.length === 2) {
            return `${parts[0]} ${parts[1]}:00`;
        }
        
        const date = new Date(dateTimeStr);
        if (isNaN(date.getTime())) return '';
        
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    },
    
    parseEventDateTime(dateTimeStr) {
        if (!dateTimeStr) return new Date();
        
        let date;
        if (dateTimeStr.includes('T')) {
            date = new Date(dateTimeStr);
        } else if (dateTimeStr.includes(' ')) {
            date = new Date(dateTimeStr.replace(' ', 'T'));
        } else {
            date = new Date(dateTimeStr + 'T09:00:00');
        }
        
        return isNaN(date.getTime()) ? new Date() : date;
    },
    
    generateEventId() {
        return Math.random().toString(36).substr(2, 9);
    }
};