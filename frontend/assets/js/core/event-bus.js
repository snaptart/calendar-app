/**
 * Simple Event Bus for component communication
 * Location: frontend/js/core/event-bus.js
 */
export const EventBus = (() => {
    const events = {};
    
    return {
        on(event, callback) {
            if (!events[event]) events[event] = [];
            events[event].push(callback);
        },
        
        off(event, callback) {
            if (!events[event]) return;
            events[event] = events[event].filter(cb => cb !== callback);
        },
        
        emit(event, data) {
            if (!events[event]) return;
            events[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in event listener for ${event}:`, error);
                }
            });
        }
    };
})();