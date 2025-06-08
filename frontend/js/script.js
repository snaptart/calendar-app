// Frontend JavaScript for collaborative calendar with xdsoft datetimepicker
// Location: frontend/js/script.js

class CollaborativeCalendar {
    constructor() {
        this.calendar = null;
        this.eventSource = null;
        this.currentUser = '';
        this.currentUserId = null;
        this.selectedUsers = new Set();
        this.allUsers = [];
        this.currentEvent = null;
        this.lastEventId = 0;
        this.reconnectAttempts = 0;
        
        // API endpoints - relative to frontend location
        this.apiEndpoints = {
            api: '../../backend/api.php',
            sse: '../../backend/workers/sse.php'
        };
        
        this.init();
    }
    
    init() {
        this.initializeDatetimePickers();
        this.initializeCalendar();
        this.setupEventListeners();
        this.setupSSE();
        this.loadUsers();
    }
    
    initializeDatetimePickers() {
        // Initialize xdsoft datetimepicker for event start and end inputs
        const datetimePickerOptions = {
            format: 'Y-m-d H:i',
            formatTime: 'H:i',
            formatDate: 'Y-m-d',
            step: 15, // 15-minute intervals
            minDate: false,
            maxDate: false,
            allowTimes: [],
            opened: false,
            defaultTime: '09:00',
            timepicker: true,
            datepicker: true,
            weeks: false,
            theme: 'default',
            lang: 'en',
            yearStart: new Date().getFullYear() - 5,
            yearEnd: new Date().getFullYear() + 5,
            todayButton: true,
            defaultSelect: false,
            scrollMonth: false,
            scrollTime: false,
            scrollInput: false,
            lazyInit: false,
            mask: false,
            validateOnBlur: true,
            allowBlank: false,
            closeOnDateSelect: false,
            closeOnTimeSelect: true,
            closeOnWithoutClick: true,
            timepickerScrollbar: true,
            onSelectDate: function(ct, $i) {
                // Auto-set end time to 1 hour after start time if this is the start picker
                if ($i.attr('id') === 'eventStart') {
                    const endPicker = $('#eventEnd');
                    if (!endPicker.val()) {
                        const endTime = new Date(ct.getTime() + 60 * 60 * 1000); // Add 1 hour
                        endPicker.datetimepicker('setOptions', {
                            value: endTime,
                            minDate: ct
                        });
                    } else {
                        endPicker.datetimepicker('setOptions', {
                            minDate: ct
                        });
                    }
                }
            },
            onSelectTime: function(ct, $i) {
                // Auto-set end time to 1 hour after start time if this is the start picker
                if ($i.attr('id') === 'eventStart') {
                    const endPicker = $('#eventEnd');
                    if (!endPicker.val()) {
                        const endTime = new Date(ct.getTime() + 60 * 60 * 1000); // Add 1 hour
                        endPicker.datetimepicker('setOptions', {
                            value: endTime,
                            minDate: ct
                        });
                    } else {
                        endPicker.datetimepicker('setOptions', {
                            minDate: ct
                        });
                    }
                }
            }
        };
        
        // Initialize both datetime pickers
        $('#eventStart').datetimepicker(datetimePickerOptions);
        $('#eventEnd').datetimepicker(datetimePickerOptions);
        
        // Set minimum date for end picker when start changes
        $('#eventStart').on('change', function() {
            const startDate = $(this).datetimepicker('getValue');
            if (startDate) {
                $('#eventEnd').datetimepicker('setOptions', {
                    minDate: startDate
                });
                
                // If end date is before start date, update it
                const endDate = $('#eventEnd').datetimepicker('getValue');
                if (!endDate || endDate <= startDate) {
                    const newEndDate = new Date(startDate.getTime() + 60 * 60 * 1000);
                    $('#eventEnd').datetimepicker('setOptions', {
                        value: newEndDate
                    });
                }
            }
        });
    }
    
    initializeCalendar() {
        const calendarEl = document.getElementById('calendar');
        
        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            
            // CRITICAL: Enable editing for drag and drop
            editable: true,
            selectable: true,
            selectMirror: true,
            snapDuration: '00:05:00',
            
            // Enable event interaction
            eventStartEditable: true,
            eventDurationEditable: true,
            eventResizableFromStart: true,
            
            // Visual settings
            dayMaxEvents: true,
            weekends: true,
            height: 'auto',
            
            // Events array
            events: [],
            
            // Event handlers for interaction
            select: (info) => this.handleDateSelect(info),
            eventClick: (info) => this.handleEventClick(info),
            eventDrop: (info) => this.handleEventMove(info),
            eventResize: (info) => this.handleEventResize(info),
            
            // Add visual feedback during drag
            eventMouseEnter: (info) => this.handleEventMouseEnter(info),
            eventMouseLeave: (info) => this.handleEventMouseLeave(info),
            
            // Validate before allowing drag/drop
            eventAllow: (dropInfo, draggedEvent) => {
                return this.validateEventEdit(draggedEvent);
            },
            
            // Additional drag feedback
            eventDragStart: (info) => this.handleEventDragStart(info),
            eventDragStop: (info) => this.handleEventDragStop(info),
            eventResizeStart: (info) => this.handleEventResizeStart(info),
            eventResizeStop: (info) => this.handleEventResizeStop(info)
        });
        
        this.calendar.render();
    }
    
    setupEventListeners() {
        // User name input
        const userNameInput = document.getElementById('userName');
        userNameInput.addEventListener('change', (e) => {
            this.setCurrentUser(e.target.value.trim());
        });
        
        // Also trigger on blur and Enter key
        userNameInput.addEventListener('blur', (e) => {
            this.setCurrentUser(e.target.value.trim());
        });
        
        userNameInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.setCurrentUser(e.target.value.trim());
            }
        });
        
        // Add event button
        document.getElementById('addEventBtn').addEventListener('click', () => {
            this.openEventModal();
        });
        
        // Refresh users button
        document.getElementById('refreshUsers').addEventListener('click', () => {
            this.loadUsers();
        });
        
        // Modal event listeners
        this.setupModalListeners();
    }
    
    setupModalListeners() {
        const modal = document.getElementById('eventModal');
        const closeBtn = modal.querySelector('.close');
        const cancelBtn = document.getElementById('cancelBtn');
        const eventForm = document.getElementById('eventForm');
        const deleteBtn = document.getElementById('deleteEventBtn');
        
        // Close modal
        [closeBtn, cancelBtn].forEach(btn => {
            btn.addEventListener('click', () => this.closeEventModal());
        });
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) this.closeEventModal();
        });
        
        // Form submission
        eventForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveEvent();
        });
        
        // Delete event
        deleteBtn.addEventListener('click', () => {
            this.deleteEvent();
        });
    }
    
    setupSSE() {
        this.connectSSE();
    }
    
    connectSSE() {
        if (this.eventSource) {
            this.eventSource.close();
        }
        
        this.updateConnectionStatus('Connecting...');
        
        this.eventSource = new EventSource(`${this.apiEndpoints.sse}?lastEventId=${this.lastEventId}`);
        
        this.eventSource.onopen = () => {
            this.updateConnectionStatus('Connected', 'connected');
            this.reconnectAttempts = 0; // Reset on successful connection
        };
        
        this.eventSource.onerror = () => {
            this.updateConnectionStatus('Disconnected', 'disconnected');
            this.eventSource.close();
            
            // Exponential backoff for reconnection
            this.reconnectAttempts = (this.reconnectAttempts || 0) + 1;
            const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000); // Max 30 seconds
            
            console.log(`SSE connection failed. Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`);
            setTimeout(() => this.connectSSE(), delay);
        };
        
        this.eventSource.addEventListener('create', (e) => {
            const eventData = JSON.parse(e.data);
            this.addEventToCalendar(eventData);
            this.lastEventId = parseInt(e.lastEventId);
        });
        
        this.eventSource.addEventListener('update', (e) => {
            const eventData = JSON.parse(e.data);
            this.updateEventInCalendar(eventData);
            this.lastEventId = parseInt(e.lastEventId);
        });
        
        this.eventSource.addEventListener('delete', (e) => {
            const eventData = JSON.parse(e.data);
            this.removeEventFromCalendar(eventData.id);
            this.lastEventId = parseInt(e.lastEventId);
        });
        
        this.eventSource.addEventListener('user_created', (e) => {
            // Refresh users list when a new user is created
            this.loadUsers();
            this.lastEventId = parseInt(e.lastEventId);
        });
        
        this.eventSource.addEventListener('heartbeat', (e) => {
            // Keep connection alive
            this.lastEventId = parseInt(e.lastEventId) || this.lastEventId;
        });
        
        this.eventSource.addEventListener('reconnect', (e) => {
            this.lastEventId = parseInt(e.lastEventId) || this.lastEventId;
            this.connectSSE(); // Graceful reconnect
        });
        
        this.eventSource.addEventListener('timeout', () => {
            this.connectSSE(); // Reconnect on timeout
        });
    }
    
    async loadUsers() {
        try {
            const response = await fetch(`${this.apiEndpoints.api}?action=users`);
            const users = await response.json();
            this.allUsers = users;
            this.renderUserCheckboxes();
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }
    
    renderUserCheckboxes() {
        const container = document.getElementById('userCheckboxes');
        container.innerHTML = '';
        
        this.allUsers.forEach(user => {
            const checkboxItem = document.createElement('div');
            checkboxItem.className = 'checkbox-item';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = `user-${user.id}`;
            checkbox.value = user.id;
            checkbox.checked = this.selectedUsers.has(user.id.toString());
            
            const colorDot = document.createElement('div');
            colorDot.className = 'user-color';
            colorDot.style.backgroundColor = user.color;
            
            const label = document.createElement('label');
            label.htmlFor = `user-${user.id}`;
            label.textContent = user.name;
            label.style.cursor = 'pointer';
            
            checkboxItem.appendChild(checkbox);
            checkboxItem.appendChild(colorDot);
            checkboxItem.appendChild(label);
            
            // Update visual state
            if (checkbox.checked) {
                checkboxItem.classList.add('checked');
            }
            
            // Event listener
            checkbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.selectedUsers.add(user.id.toString());
                    checkboxItem.classList.add('checked');
                } else {
                    this.selectedUsers.delete(user.id.toString());
                    checkboxItem.classList.remove('checked');
                }
                this.loadEvents();
            });
            
            container.appendChild(checkboxItem);
        });
    }
    
    async setCurrentUser(userName) {
        if (!userName) {
            this.currentUser = '';
            this.currentUserId = null;
            this.updateUserStatus('');
            return;
        }
        
        try {
            // Create or get user from database
            const response = await fetch(this.apiEndpoints.api, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'create_user',
                    userName: userName
                })
            });
            
            if (!response.ok) {
                throw new Error('Failed to create/get user');
            }
            
            const userData = await response.json();
            
            this.currentUser = userData.name;
            this.currentUserId = userData.id;
            this.updateUserStatus(`Set as: ${userData.name}`, 'user-set');
            
            // Refresh users list to include the new user
            await this.loadUsers();
            
            // Auto-select the current user's calendar
            this.selectedUsers.add(userData.id.toString());
            this.renderUserCheckboxes();
            this.loadEvents();
            
        } catch (error) {
            console.error('Error setting current user:', error);
            this.updateUserStatus('Error setting user', 'disconnected');
        }
    }
    
    async loadEvents() {
        if (this.selectedUsers.size === 0) {
            this.calendar.removeAllEvents();
            return;
        }
        
        try {
            const userIds = Array.from(this.selectedUsers).join(',');
            const response = await fetch(`${this.apiEndpoints.api}?action=events&user_ids=${userIds}`);
            const events = await response.json();
            
            // Clear and add events
            this.calendar.removeAllEvents();
            events.forEach(event => {
                this.calendar.addEvent(event);
            });
        } catch (error) {
            console.error('Error loading events:', error);
        }
    }
    
    // NEW: Validate if user can edit event before allowing drag/drop
    validateEventEdit(event) {
        if (!this.currentUser) {
            return false;
        }
        return event.extendedProps.userName === this.currentUser;
    }
    
    // NEW: Visual feedback on mouse enter
    handleEventMouseEnter(info) {
        const canEdit = this.validateEventEdit(info.event);
        if (canEdit) {
            info.el.style.cursor = 'move';
            info.el.style.opacity = '0.8';
        } else {
            info.el.style.cursor = 'not-allowed';
        }
    }
    
    // NEW: Reset visual feedback on mouse leave
    handleEventMouseLeave(info) {
        info.el.style.cursor = '';
        info.el.style.opacity = '';
    }
    
    // NEW: Visual feedback when drag starts
    handleEventDragStart(info) {
        console.log('Drag started for event:', info.event.title);
        info.el.style.opacity = '0.5';
        this.showDragFeedback('Moving event...');
    }
    
    // NEW: Reset visual feedback when drag stops
    handleEventDragStop(info) {
        console.log('Drag stopped for event:', info.event.title);
        info.el.style.opacity = '';
        this.hideDragFeedback();
    }
    
    // NEW: Visual feedback when resize starts
    handleEventResizeStart(info) {
        console.log('Resize started for event:', info.event.title);
        info.el.style.opacity = '0.5';
        this.showDragFeedback('Resizing event...');
    }
    
    // NEW: Reset visual feedback when resize stops
    handleEventResizeStop(info) {
        console.log('Resize stopped for event:', info.event.title);
        info.el.style.opacity = '';
        this.hideDragFeedback();
    }
    
    // NEW: Show drag feedback message
    showDragFeedback(message) {
        const statusEl = document.getElementById('connectionStatus');
        statusEl.textContent = message;
        statusEl.className = 'status';
        statusEl.style.backgroundColor = '#f39c12';
        statusEl.style.color = 'white';
    }
    
    // NEW: Hide drag feedback message
    hideDragFeedback() {
        // Restore connection status
        setTimeout(() => {
            this.updateConnectionStatus('Connected', 'connected');
        }, 1000);
    }
    
    handleDateSelect(info) {
        if (!this.currentUser) {
            alert('Please enter your name first!');
            return;
        }
        
        this.openEventModal({
            start: info.startStr,
            end: info.endStr
        });
        
        this.calendar.unselect();
    }
    
    handleEventClick(info) {
        const event = info.event;
        const canEdit = this.validateEventEdit(event);
        
        if (!canEdit) {
            alert(`This event belongs to ${event.extendedProps.userName}. You can only edit your own events.`);
            return;
        }
        
        this.openEventModal({
            id: event.id,
            title: event.title,
            start: event.startStr,
            end: event.endStr || event.startStr
        });
    }
    
    async handleEventMove(info) {
        const event = info.event;
        
        console.log('Event move detected:', {
            eventId: event.id,
            eventTitle: event.title,
            newStart: event.startStr,
            newEnd: event.endStr,
            userName: event.extendedProps.userName,
            currentUser: this.currentUser
        });
        
        if (!this.validateEventEdit(event)) {
            console.log('Move reverted: user cannot edit this event');
            info.revert();
            alert('You can only move your own events!');
            return;
        }
        
        try {
            await this.updateEvent({
                id: event.id,
                title: event.title,
                start: event.startStr,
                end: event.endStr || event.startStr
            });
            console.log('Event move saved successfully');
        } catch (error) {
            console.error('Error saving moved event:', error);
            info.revert();
            alert('Error saving event move. Changes reverted.');
        }
    }
    
    async handleEventResize(info) {
        const event = info.event;
        
        console.log('Event resize detected:', {
            eventId: event.id,
            eventTitle: event.title,
            newStart: event.startStr,
            newEnd: event.endStr,
            userName: event.extendedProps.userName,
            currentUser: this.currentUser
        });
        
        if (!this.validateEventEdit(event)) {
            console.log('Resize reverted: user cannot edit this event');
            info.revert();
            alert('You can only resize your own events!');
            return;
        }
        
        try {
            await this.updateEvent({
                id: event.id,
                title: event.title,
                start: event.startStr,
                end: event.endStr || event.startStr
            });
            console.log('Event resize saved successfully');
        } catch (error) {
            console.error('Error saving resized event:', error);
            info.revert();
            alert('Error saving event resize. Changes reverted.');
        }
    }
    
    openEventModal(eventData = {}) {
        const modal = document.getElementById('eventModal');
        const modalTitle = document.getElementById('modalTitle');
        const deleteBtn = document.getElementById('deleteEventBtn');
        
        // Set form values
        document.getElementById('eventTitle').value = eventData.title || '';
        
        // Set datetime picker values
        if (eventData.start) {
            const startDate = this.parseEventDateTime(eventData.start);
            $('#eventStart').datetimepicker('setOptions', { value: startDate });
        } else {
            // Default to current time rounded to next 15-minute interval
            const now = new Date();
            const roundedMinutes = Math.ceil(now.getMinutes() / 15) * 15;
            now.setMinutes(roundedMinutes, 0, 0);
            $('#eventStart').datetimepicker('setOptions', { value: now });
        }
        
        if (eventData.end) {
            const endDate = this.parseEventDateTime(eventData.end);
            $('#eventEnd').datetimepicker('setOptions', { value: endDate });
        } else if (eventData.start) {
            // Set end to 1 hour after start
            const startDate = this.parseEventDateTime(eventData.start);
            const endDate = new Date(startDate.getTime() + 60 * 60 * 1000);
            $('#eventEnd').datetimepicker('setOptions', { value: endDate });
        } else {
            // Default to 1 hour from now rounded to next 15-minute interval
            const now = new Date();
            const roundedMinutes = Math.ceil(now.getMinutes() / 15) * 15;
            now.setMinutes(roundedMinutes, 0, 0);
            const endTime = new Date(now.getTime() + 60 * 60 * 1000);
            $('#eventEnd').datetimepicker('setOptions', { value: endTime });
        }
        
        // Update modal title and show/hide delete button
        if (eventData.id) {
            modalTitle.textContent = 'Edit Event';
            deleteBtn.style.display = 'inline-block';
            this.currentEvent = eventData;
        } else {
            modalTitle.textContent = 'Add Event';
            deleteBtn.style.display = 'none';
            this.currentEvent = null;
        }
        
        modal.style.display = 'block';
        document.getElementById('eventTitle').focus();
    }
    
    closeEventModal() {
        document.getElementById('eventModal').style.display = 'none';
        this.currentEvent = null;
        
        // Clear datetime picker values
        $('#eventStart').val('');
        $('#eventEnd').val('');
    }
    
    async saveEvent() {
        if (!this.currentUser) {
            alert('Please enter your name first!');
            return;
        }
        
        const title = document.getElementById('eventTitle').value.trim();
        const startValue = $('#eventStart').val();
        const endValue = $('#eventEnd').val();
        
        if (!title || !startValue) {
            alert('Please fill in all required fields!');
            return;
        }
        
        // Convert datetime picker values to ISO format
        const start = this.formatDateTimeForAPI(startValue);
        const end = endValue ? this.formatDateTimeForAPI(endValue) : start;
        
        const eventData = {
            title,
            start,
            end,
            userName: this.currentUser
        };
        
        try {
            if (this.currentEvent) {
                // Update existing event
                eventData.id = this.currentEvent.id;
                await this.updateEvent(eventData);
            } else {
                // Create new event
                await this.createEvent(eventData);
            }
            
            this.closeEventModal();
        } catch (error) {
            console.error('Error saving event:', error);
            alert('Error saving event. Please try again.');
        }
    }
    
    async createEvent(eventData) {
        console.log('Creating event:', eventData);
        
        const response = await fetch(this.apiEndpoints.api, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(eventData)
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || 'Failed to create event');
        }
        
        const result = await response.json();
        console.log('Event created successfully:', result);
        return result;
    }
    
    async updateEvent(eventData) {
        console.log('Updating event:', eventData);
        
        const response = await fetch(this.apiEndpoints.api, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(eventData)
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || 'Failed to update event');
        }
        
        const result = await response.json();
        console.log('Event updated successfully:', result);
        return result;
    }
    
    async deleteEvent() {
        if (!this.currentEvent || !confirm('Are you sure you want to delete this event?')) {
            return;
        }
        
        try {
            console.log('Deleting event:', this.currentEvent.id);
            
            const response = await fetch(`${this.apiEndpoints.api}?id=${this.currentEvent.id}`, {
                method: 'DELETE'
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || 'Failed to delete event');
            }
            
            console.log('Event deleted successfully');
            this.closeEventModal();
        } catch (error) {
            console.error('Error deleting event:', error);
            alert('Error deleting event. Please try again.');
        }
    }
    
    addEventToCalendar(eventData) {
        // Only add if this user's calendar is selected
        if (this.selectedUsers.has(eventData.extendedProps.userId.toString())) {
            console.log('Adding event to calendar:', eventData);
            this.calendar.addEvent(eventData);
        }
    }
    
    updateEventInCalendar(eventData) {
        console.log('Updating event in calendar:', eventData);
        const event = this.calendar.getEventById(eventData.id);
        if (event) {
            event.setProp('title', eventData.title);
            event.setStart(eventData.start);
            event.setEnd(eventData.end);
        }
    }
    
    removeEventFromCalendar(eventId) {
        console.log('Removing event from calendar:', eventId);
        const event = this.calendar.getEventById(eventId);
        if (event) {
            event.remove();
        }
    }
    
    updateConnectionStatus(message, className = '') {
        const statusEl = document.getElementById('connectionStatus');
        statusEl.textContent = message;
        statusEl.className = `status ${className}`;
        statusEl.style.backgroundColor = '';
        statusEl.style.color = '';
    }
    
    updateUserStatus(message, className = '') {
        const statusEl = document.getElementById('userStatus');
        statusEl.textContent = message;
        statusEl.className = `status ${className}`;
    }
    
    // Helper method to parse event datetime strings
    parseEventDateTime(dateTimeStr) {
        if (!dateTimeStr) return new Date();
        
        // Handle various datetime formats from FullCalendar
        let date;
        if (dateTimeStr.includes('T')) {
            // ISO format: 2025-06-08T14:30:00
            date = new Date(dateTimeStr);
        } else if (dateTimeStr.includes(' ')) {
            // SQL format: 2025-06-08 14:30:00
            date = new Date(dateTimeStr.replace(' ', 'T'));
        } else {
            // Date only: 2025-06-08
            date = new Date(dateTimeStr + 'T09:00:00');
        }
        
        return isNaN(date.getTime()) ? new Date() : date;
    }
    
    // Helper method to format datetime for API (MySQL format)
    formatDateTimeForAPI(dateTimeStr) {
        if (!dateTimeStr) return '';
        
        // xdsoft datetimepicker returns format: YYYY-MM-DD HH:MM
        // Convert to MySQL datetime format: YYYY-MM-DD HH:MM:SS
        const parts = dateTimeStr.split(' ');
        if (parts.length === 2) {
            return `${parts[0]} ${parts[1]}:00`;
        }
        
        // Fallback: try to parse as date and format
        const date = new Date(dateTimeStr);
        if (isNaN(date.getTime())) return '';
        
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

// Initialize the calendar when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing Collaborative Calendar with xdsoft datetimepicker...');
    new CollaborativeCalendar();
});