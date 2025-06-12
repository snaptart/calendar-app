/**
 * Manages modal dialogs for event creation/editing
 * Location: frontend/js/ui/modal-manager.js
 */
import { EventBus } from '../core/event-bus.js';
import { Utils } from '../core/utils.js';
import { UserManager } from '../auth/user-manager.js';
import { DateTimeManager } from '../calendar/datetime-manager.js';

export const ModalManager = (() => {
    let currentEvent = null;
    
    const openModal = (eventData = {}) => {
        const modal = document.getElementById('eventModal');
        const modalTitle = document.getElementById('modalTitle');
        const deleteBtn = document.getElementById('deleteEventBtn');
        
        if (!modal) return;
        
        // Check if user is authenticated
        const currentUser = UserManager.getCurrentUser();
        if (!currentUser) {
            EventBus.emit('ui:showNotification', { 
                message: 'Please login to create events', 
                type: 'error' 
            });
            return;
        }
        
        // Set form values
        document.getElementById('eventTitle').value = eventData.title || '';
        
        // Set datetime values
        if (eventData.start || eventData.end) {
            DateTimeManager.setDateTimeValues(eventData.start, eventData.end);
        } else {
            DateTimeManager.setDefaultDateTime();
        }
        
        // Update modal state
        if (eventData.id) {
            modalTitle.textContent = 'Edit Event';
            deleteBtn.style.display = 'inline-block';
            currentEvent = eventData;
        } else {
            modalTitle.textContent = 'Add Event';
            deleteBtn.style.display = 'none';
            currentEvent = null;
        }
        
        modal.style.display = 'block';
        document.getElementById('eventTitle').focus();
        
        EventBus.emit('modal:opened', { event: eventData });
    };
    
    const closeModal = () => {
        const modal = document.getElementById('eventModal');
        if (modal) {
            modal.style.display = 'none';
        }
        
        currentEvent = null;
        DateTimeManager.clearDateTimeValues();
        
        EventBus.emit('modal:closed');
    };
    
    const getCurrentEvent = () => currentEvent;
    
    const validateForm = () => {
        const title = document.getElementById('eventTitle').value.trim();
        const { start } = DateTimeManager.getDateTimeValues();
        
        if (!title) {
            EventBus.emit('ui:showNotification', { 
                message: 'Please enter an event title', 
                type: 'error' 
            });
            return false;
        }
        
        if (!start) {
            EventBus.emit('ui:showNotification', { 
                message: 'Please select a start date and time', 
                type: 'error' 
            });
            return false;
        }
        
        const currentUser = UserManager.getCurrentUser();
        if (!currentUser) {
            EventBus.emit('ui:showNotification', { 
                message: 'Please login to create events', 
                type: 'error' 
            });
            return false;
        }
        
        return true;
    };
    
    const getFormData = () => {
        const title = document.getElementById('eventTitle').value.trim();
        const { start, end } = DateTimeManager.getDateTimeValues();
        
        return {
            title,
            start: Utils.formatDateTimeForAPI(start),
            end: Utils.formatDateTimeForAPI(end || start)
        };
    };
    
    const setupEventListeners = () => {
        const modal = document.getElementById('eventModal');
        const closeBtn = modal?.querySelector('.close');
        const cancelBtn = document.getElementById('cancelBtn');
        const eventForm = document.getElementById('eventForm');
        const deleteBtn = document.getElementById('deleteEventBtn');
        
        // Close modal events
        [closeBtn, cancelBtn].forEach(btn => {
            btn?.addEventListener('click', closeModal);
        });
        
        // Close on outside click
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        
        // Form submission
        eventForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            if (validateForm()) {
                EventBus.emit('event:save', {
                    eventData: getFormData(),
                    isEdit: !!currentEvent,
                    eventId: currentEvent?.id
                });
            }
        });
        
        // Delete event
        deleteBtn?.addEventListener('click', () => {
            if (currentEvent && confirm('Are you sure you want to delete this event?')) {
                EventBus.emit('event:delete', { eventId: currentEvent.id });
            }
        });
    };
    
    // Event listeners
    EventBus.on('calendar:dateSelect', ({ start, end }) => {
        const currentUser = UserManager.getCurrentUser();
        if (!currentUser) {
            EventBus.emit('ui:showNotification', { 
                message: 'Please login to create events', 
                type: 'error' 
            });
            return;
        }
        
        openModal({ start, end });
    });
    
    EventBus.on('calendar:eventClick', ({ event }) => {
        const canEdit = UserManager.canUserEditEvent(event);
        if (!canEdit) {
            EventBus.emit('ui:showNotification', { 
                message: `This event belongs to ${event.extendedProps.userName}. You can only edit your own events.`, 
                type: 'error' 
            });
            return;
        }
        
        openModal({
            id: event.id,
            title: event.title,
            start: event.startStr,
            end: event.endStr || event.startStr
        });
    });
    
    EventBus.on('event:saved', closeModal);
    EventBus.on('event:deleted', closeModal);
    
    return {
        openModal,
        closeModal,
        getCurrentEvent,
        setupEventListeners
    };
})();