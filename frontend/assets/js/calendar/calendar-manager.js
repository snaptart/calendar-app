/**
 * Manages FullCalendar initialization and interactions
 * Location: frontend/js/calendar/calendar-manager.js
 */
import { Config } from '../core/config.js';
import { EventBus } from '../core/event-bus.js';
import { UserManager } from '../auth/user-manager.js';

export const CalendarManager = (() => {
    let calendar = null;
    
    const initializeCalendar = () => {
        if (calendar) {
            console.log('Calendar already initialized, skipping...');
            return;
        }
        
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;
        
        console.log('Creating new FullCalendar instance...');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: Config.calendar.defaultView,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            
            // Enable editing
            editable: true,
            selectable: true,
            selectMirror: true,
            snapDuration: Config.calendar.snapDuration,
            eventStartEditable: true,
            eventDurationEditable: true,
            eventResizableFromStart: true,
            
            // Visual settings
            dayMaxEvents: true,
            weekends: true,
            height: '100%',
            
            events: [],
            
            // Event handlers
            select: (info) => {
                EventBus.emit('calendar:dateSelect', {
                    start: info.startStr,
                    end: info.endStr
                });
                calendar.unselect();
            },
            
            eventClick: (info) => {
                EventBus.emit('calendar:eventClick', { event: info.event });
            },
            
            eventDrop: (info) => {
                EventBus.emit('calendar:eventDrop', {
                    event: info.event,
                    revert: info.revert
                });
            },
            
            eventResize: (info) => {
                EventBus.emit('calendar:eventResize', {
                    event: info.event,
                    revert: info.revert
                });
            },
            
            eventAllow: (dropInfo, draggedEvent) => {
                return UserManager.canUserEditEvent(draggedEvent);
            },
            
            eventMouseEnter: (info) => {
                const canEdit = UserManager.canUserEditEvent(info.event);
                info.el.style.cursor = canEdit ? 'move' : 'not-allowed';
                if (canEdit) info.el.style.opacity = '0.8';
            },
            
            eventMouseLeave: (info) => {
                info.el.style.cursor = '';
                info.el.style.opacity = '';
            },
            
            eventDragStart: (info) => {
                info.el.style.opacity = '0.5';
                EventBus.emit('drag:start', { message: 'Moving event...' });
            },
            
            eventDragStop: (info) => {
                info.el.style.opacity = '';
                EventBus.emit('drag:stop');
            },
            
            eventResizeStart: (info) => {
                info.el.style.opacity = '0.5';
                EventBus.emit('drag:start', { message: 'Resizing event...' });
            },
            
            eventResizeStop: (info) => {
                info.el.style.opacity = '';
                EventBus.emit('drag:stop');
            }
        });
        
        calendar.render();
    };

    const destroy = () => {
        if (calendar) {
            calendar.destroy();
            calendar = null;
            console.log('FullCalendar destroyed');
        }
    };
    
    const addEvent = (eventData) => {
        if (!calendar) return;
        const selectedUserIds = UserManager.getSelectedUserIds();
        if (selectedUserIds.includes(eventData.extendedProps.userId.toString())) {
            calendar.addEvent(eventData);
        }
    };
    
    const updateEvent = (eventData) => {
        if (!calendar) return;
        const event = calendar.getEventById(eventData.id);
        if (event) {
            event.setProp('title', eventData.title);
            event.setStart(eventData.start);
            event.setEnd(eventData.end);
            
            // Update extended properties if they exist
            if (eventData.extendedProps) {
                Object.keys(eventData.extendedProps).forEach(key => {
                    event.setExtendedProp(key, eventData.extendedProps[key]);
                });
            }
            
            // Update colors if they exist
            if (eventData.backgroundColor) {
                event.setProp('backgroundColor', eventData.backgroundColor);
            }
            if (eventData.borderColor) {
                event.setProp('borderColor', eventData.borderColor);
            }
        } else {
            // Event doesn't exist in calendar, check if we should add it
            const selectedUserIds = UserManager.getSelectedUserIds();
            if (selectedUserIds.includes(eventData.extendedProps.userId.toString())) {
                calendar.addEvent(eventData);
            }
        }
    };
    
    const removeEvent = (eventId) => {
        if (!calendar) return;
        const event = calendar.getEventById(eventId);
        event?.remove();
    };
    
    const clearAllEvents = () => {
        if (!calendar) return;
        calendar.removeAllEvents();
    };
    
    const loadEvents = (events) => {
        if (!calendar || !events) return;
        clearAllEvents();
        events.forEach(event => calendar.addEvent(event));
    };
    
    // Event listeners
    EventBus.on('events:loaded', ({ events }) => {
        if (calendar) loadEvents(events);
    });
    
    EventBus.on('sse:eventCreate', ({ eventData }) => {
        if (calendar) addEvent(eventData);
    });
    
    EventBus.on('sse:eventUpdate', ({ eventData }) => {
        if (calendar) updateEvent(eventData);
    });
    
    EventBus.on('sse:eventDelete', ({ eventId }) => {
        if (calendar) removeEvent(eventId);
    });
    
    return {
        initializeCalendar,
        destroy,
        addEvent,
        updateEvent,
        removeEvent,
        clearAllEvents,
        loadEvents
    };
})();