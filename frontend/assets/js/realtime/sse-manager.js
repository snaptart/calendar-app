/**
 * Manages Server-Sent Events for real-time updates
 * Location: frontend/js/realtime/sse-manager.js
 */
import { Config } from '../core/config.js';
import { EventBus } from '../core/event-bus.js';
import { EventManager } from '../calendar/event-manager.js';
import { UserManager } from '../auth/user-manager.js';

export const SSEManager = (() => {
    let eventSource = null;
    let lastEventId = 0;
    let reconnectAttempts = 0;
    let isConnected = false;
    
    const connect = () => {
        if (eventSource) {
            eventSource.close();
            isConnected = false;
        }
        
        EventBus.emit('connection:status', {
            status: 'connecting',
            message: 'Connecting...'
        });
        
        console.log('Attempting SSE connection with lastEventId:', lastEventId);
        
        eventSource = new EventSource(`${Config.apiEndpoints.sse}?lastEventId=${lastEventId}`);
        
        eventSource.onopen = () => {
            EventBus.emit('connection:status', {
                status: 'connected',
                message: 'Connected'
            });
            isConnected = true;
            reconnectAttempts = 0;
            console.log('SSE connection established');
        };
        
        eventSource.onerror = (e) => {
            console.log('SSE connection error:', e);
            EventBus.emit('connection:status', {
                status: 'disconnected',
                message: 'Disconnected'
            });
            isConnected = false;
            eventSource.close();
            
            // Exponential backoff for reconnection
            reconnectAttempts++;
            if (reconnectAttempts <= Config.sse.maxReconnectAttempts) {
                const delay = Math.min(
                    Config.sse.baseReconnectDelay * Math.pow(2, reconnectAttempts),
                    Config.sse.maxReconnectDelay
                );
                
                console.log(`SSE reconnecting in ${delay}ms (attempt ${reconnectAttempts})`);
                setTimeout(connect, delay);
            } else {
                console.log('Max reconnection attempts reached');
                EventBus.emit('connection:status', {
                    status: 'failed',
                    message: 'Connection failed'
                });
            }
        };
        
        setupEventListeners();
    };
    
    const setupEventListeners = () => {
        const handleSSEEvent = (eventType, handler) => {
            eventSource.addEventListener(eventType, (e) => {
                try {
                    const eventData = JSON.parse(e.data);
                    lastEventId = parseInt(e.lastEventId) || lastEventId;
                    
                    // Use the SSE event ID to prevent true duplicates (same SSE message)
                    // but allow legitimate moves that may return to previous positions
                    const sseEventId = `sse-${lastEventId}`;
                    
                    if (!EventManager.preventDuplicateProcessing(sseEventId, 'sse')) {
                        console.log(`Processing SSE ${eventType} event:`, eventData);
                        handler(eventData);
                    } else {
                        console.log(`Skipping duplicate SSE message ID ${lastEventId} for event:`, eventData.id);
                    }
                } catch (error) {
                    console.error(`Error handling SSE ${eventType} event:`, error);
                }
            });
        };
        
        handleSSEEvent('create', (eventData) => {
            console.log('SSE: Creating event', eventData.id);
            EventBus.emit('sse:eventCreate', { eventData });
        });
        
        handleSSEEvent('update', (eventData) => {
            console.log('SSE: Updating event', eventData.id);
            EventBus.emit('sse:eventUpdate', { eventData });
        });
        
        handleSSEEvent('delete', (eventData) => {
            console.log('SSE: Deleting event', eventData.id);
            EventBus.emit('sse:eventDelete', { eventId: eventData.id });
        });
        
        eventSource.addEventListener('user_created', (e) => {
            console.log('SSE: User created, refreshing users list');
            EventBus.emit('users:refresh');
            lastEventId = parseInt(e.lastEventId) || lastEventId;
        });
        
        eventSource.addEventListener('heartbeat', (e) => {
            lastEventId = parseInt(e.lastEventId) || lastEventId;
        });
        
        eventSource.addEventListener('reconnect', (e) => {
            console.log('SSE: Server requested reconnect');
            lastEventId = parseInt(e.lastEventId) || lastEventId;
            connect();
        });
        
        eventSource.addEventListener('timeout', () => {
            console.log('SSE: Connection timeout, reconnecting');
            connect();
        });
    };
    
    const disconnect = () => {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
            isConnected = false;
        }
    };
    
    const getConnectionStatus = () => ({
        isConnected,
        reconnectAttempts,
        lastEventId
    });
    
    // Event listeners
    EventBus.on('users:refresh', () => {
        UserManager.loadUsers();
    });
    
    return {
        connect,
        disconnect,
        getConnectionStatus
    };
})();