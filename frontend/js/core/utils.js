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
    },
    
    formatDateTime(dateTimeString) {
        if (!dateTimeString || dateTimeString === '0000-00-00 00:00:00' || dateTimeString === null) {
            return 'Not set';
        }
        
        const date = new Date(dateTimeString);
        if (isNaN(date.getTime())) {
            return 'Invalid Date';
        }
        
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    formatDateOnly(dateTimeString) {
        if (!dateTimeString || dateTimeString === '0000-00-00 00:00:00' || dateTimeString === null) {
            return 'Not set';
        }
        
        const date = new Date(dateTimeString);
        if (isNaN(date.getTime())) {
            return 'Invalid';
        }
        
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    formatTimeOnly(dateTimeString) {
        if (!dateTimeString || dateTimeString === '0000-00-00 00:00:00' || dateTimeString === null) {
            return 'Not set';
        }
        
        const date = new Date(dateTimeString);
        if (isNaN(date.getTime())) {
            return 'Invalid';
        }
        
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    calculateDuration(startDateTime, endDateTime) {
        if (!startDateTime || !endDateTime) {
            return 'Unknown';
        }
        
        const start = new Date(startDateTime);
        const end = new Date(endDateTime);
        
        if (isNaN(start.getTime()) || isNaN(end.getTime())) {
            return 'Invalid';
        }
        
        const diffMs = end - start;
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
        const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
        
        if (diffHours === 0) {
            return `${diffMinutes}m`;
        } else if (diffMinutes === 0) {
            return `${diffHours}h`;
        } else {
            return `${diffHours}h ${diffMinutes}m`;
        }
    },
    
    getEventStatus(startDateTime, endDateTime) {
        const now = new Date();
        const start = new Date(startDateTime);
        const end = new Date(endDateTime);
        
        if (isNaN(start.getTime()) || isNaN(end.getTime())) {
            return 'unknown';
        }
        
        if (now < start) {
            return 'upcoming';
        } else if (now >= start && now <= end) {
            return 'ongoing';
        } else {
            return 'past';
        }
    },
    
    getRelativeTime(dateTimeString) {
        if (!dateTimeString) return 'Unknown';
        
        const date = new Date(dateTimeString);
        const now = new Date();
        const diffMs = date - now;
        const diffMinutes = Math.floor(diffMs / (1000 * 60));
        const diffHours = Math.floor(diffMinutes / 60);
        const diffDays = Math.floor(diffHours / 24);
        
        if (Math.abs(diffMinutes) < 60) {
            return diffMinutes > 0 ? `in ${diffMinutes}m` : `${Math.abs(diffMinutes)}m ago`;
        } else if (Math.abs(diffHours) < 24) {
            return diffHours > 0 ? `in ${diffHours}h` : `${Math.abs(diffHours)}h ago`;
        } else {
            return diffDays > 0 ? `in ${diffDays}d` : `${Math.abs(diffDays)}d ago`;
        }
    }
};