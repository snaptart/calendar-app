/**
 * Manages calendar event CRUD operations with authentication
 * Location: frontend/js/calendar/event-manager.js
 */
import { APIClient } from '../core/api-client.js';
import { EventBus } from '../core/event-bus.js';
import { Utils } from '../core/utils.js';
import { UserManager } from '../auth/user-manager.js';

export const EventManager = (() => {
    const processedEventIds = new Set();
    
    const saveEvent = async (eventData, isEdit = false, eventId = null) => {
        try {
            let result;
            
            if (isEdit && eventId) {
                result = await APIClient.updateEvent({ ...eventData, id: eventId });
            } else {
                result = await APIClient.createEvent(eventData);
            }
            
            EventBus.emit('event:saved', { event: result, isEdit });
            return result;
            
        } catch (error) {
            console.error('Error saving event:', error);
            EventBus.emit('event:saveError', { error });
            throw error;
        }
    };
    
    const deleteEvent = async (eventId) => {
        try {
            await APIClient.deleteEvent(eventId);
            EventBus.emit('event:deleted', { eventId });
            
        } catch (error) {
            console.error('Error deleting event:', error);
            EventBus.emit('event:deleteError', { error });
            throw error;
        }
    };
    
    const loadEvents = async () => {
        const selectedUserIds = UserManager.getSelectedUserIds();
        
        if (selectedUserIds.length === 0) {
            EventBus.emit('events:loaded', { events: [] });
            return;
        }
        
        try {
            const events = await APIClient.getEvents(selectedUserIds);
            EventBus.emit('events:loaded', { events });
            
        } catch (error) {
            console.error('Error loading events:', error);
            EventBus.emit('events:loadError', { error });
        }
    };
    
    const handleEventMove = Utils.debounce(async (eventData) => {
        try {
            await APIClient.updateEvent(eventData);
            console.log('Event move saved successfully');
        } catch (error) {
            console.error('Error saving moved event:', error);
            EventBus.emit('event:moveError', { error });
        }
    }, 100);
    
    const handleEventResize = Utils.debounce(async (eventData) => {
        try {
            await APIClient.updateEvent(eventData);
            console.log('Event resize saved successfully');
        } catch (error) {
            console.error('Error saving resized event:', error);
            EventBus.emit('event:resizeError', { error });
        }
    }, 100);
    
    const preventDuplicateProcessing = (eventId, operation) => {
        const key = `${operation}-${eventId}`;
        
        if (processedEventIds.has(key)) {
            return true; // Is duplicate
        }
        
        processedEventIds.add(key);
        
        // Clean up after 30 seconds (much shorter to allow legitimate updates)
        setTimeout(() => processedEventIds.delete(key), 30000);
        
        return false; // Not duplicate
    };
    
    // Event listeners
    EventBus.on('event:save', async ({ eventData, isEdit, eventId }) => {
        try {
            await saveEvent(eventData, isEdit, eventId);
        } catch (error) {
            // UIManager will be available via event bus
            EventBus.emit('ui:showNotification', { 
                message: `Error saving event: ${error.message}`, 
                type: 'error' 
            });
        }
    });
    
    EventBus.on('event:delete', async ({ eventId }) => {
        try {
            await deleteEvent(eventId);
        } catch (error) {
            EventBus.emit('ui:showNotification', { 
                message: `Error deleting event: ${error.message}`, 
                type: 'error' 
            });
        }
    });
    
    EventBus.on('users:selectionChanged', loadEvents);
    EventBus.on('user:set', loadEvents);
    
    EventBus.on('calendar:eventDrop', ({ event, revert }) => {
        const canEdit = UserManager.canUserEditEvent(event);
        if (!canEdit) {
            revert();
            EventBus.emit('ui:showNotification', { 
                message: 'You can only move your own events!', 
                type: 'error' 
            });
            return;
        }
        
        const eventData = {
            id: event.id,
            title: event.title,
            start: event.startStr,
            end: event.endStr || event.startStr
        };
        
        handleEventMove(eventData);
    });
    
    EventBus.on('calendar:eventResize', ({ event, revert }) => {
        const canEdit = UserManager.canUserEditEvent(event);
        if (!canEdit) {
            revert();
            EventBus.emit('ui:showNotification', { 
                message: 'You can only resize your own events!', 
                type: 'error' 
            });
            return;
        }
        
        const eventData = {
            id: event.id,
            title: event.title,
            start: event.startStr,
            end: event.endStr || event.startStr
        };
        
        handleEventResize(eventData);
    });
    
    return {
        loadEvents,
        preventDuplicateProcessing
    };
})();