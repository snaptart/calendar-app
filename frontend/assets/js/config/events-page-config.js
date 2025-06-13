/**
 * Events Page Configuration for CrudPageFactory
 * Location: frontend/js/config/events-page-config.js
 */

import { Utils } from '../core/utils.js';
import { APIClient } from '../core/api-client.js';

export const EventsPageConfig = {
    entityType: 'events',
    apiAction: 'events_datatable',
    tableId: 'eventsTable',
    
    columns: [
        { 
            data: 'title', 
            title: 'Title',
            name: 'title'
        },
        { 
            data: 'start', 
            title: 'Start',
            name: 'start',
            render: (data) => Utils.formatDateTime(data)
        },
        { 
            data: 'end', 
            title: 'End',
            name: 'end',
            render: (data) => Utils.formatDateTime(data)
        },
        { 
            data: 'duration', 
            title: 'Duration',
            name: 'duration'
        },
        { 
            data: 'owner', 
            title: 'Owner',
            name: 'owner'
        },
        { 
            data: 'status', 
            title: 'Status',
            name: 'status',
            render: (data) => {
                const statusClass = data === 'upcoming' ? 'badge-primary' : 
                                   data === 'ongoing' ? 'badge-success' : 'badge-secondary';
                return `<span class="badge ${statusClass}">${data}</span>`;
            }
        },
        {
            data: null,
            title: 'Actions',
            orderable: false,
            render: (data, type, row) => `
                <button class="btn btn-sm btn-outline-primary view-event-btn" data-event-id="${row.id}">
                    👁️ View
                </button>
                <button class="btn btn-sm btn-outline-secondary edit-event-btn" data-event-id="${row.id}">
                    ✏️ Edit
                </button>
            `
        }
    ],
    
    defaultOrder: [[1, 'desc']],
    
    filters: {
        defaults: {
            status: '',
            user: '',
            search: ''
        },
        elements: {
            status: 'eventStatusFilter',
            user: 'eventUserFilter',
            search: 'searchFilter',
            refresh: 'refreshEventsBtn',
            create: 'addEventBtn'
        }
    },
    
    api: {
        loadData: 'getEventsForTable'
    },
    
    dataTransform: (event) => {
        // Calculate status based on start/end times
        const now = new Date();
        const start = new Date(event.start);
        const end = new Date(event.end);
        
        let status = 'past';
        if (now < start) status = 'upcoming';
        else if (now >= start && now <= end) status = 'ongoing';
        
        // Calculate duration
        const diff = end - start;
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const duration = `${hours}h ${minutes}m`;
        
        return {
            ...event,
            status,
            duration,
            owner: event.owner_name || event.owner || 'Unknown'
        };
    },
    
    customHandlers: {
        onButtonClick: (data) => {
            const { button, rowData } = data;
            const action = button.getAttribute('class').includes('view-event-btn') ? 'view' : 'edit';
            
            if (action === 'view') {
                // Handle view event
                window.ModalManager?.openModal('events', rowData, 'view');
            } else if (action === 'edit') {
                // Handle edit event
                window.ModalManager?.openModal('events', rowData, 'edit');
            }
        },
        
        onSSEMessage: (data) => {
            // Handle real-time event updates
            if (data.type === 'event_updated' || data.type === 'event_created' || data.type === 'event_deleted') {
                // Refresh events data
                console.log('SSE event received:', data.type);
                // The factory will handle refreshing through the DataManager
            }
        }
    }
};