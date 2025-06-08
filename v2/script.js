class CalendarApp {
    constructor() {
        this.username = '';
        this.eventSource = null;
        this.lastNotificationId = 0;
        this.calendar = null;
        this.users = new Map();
        this.visibleUsers = new Set();
        this.currentEvent = null;
        
        this.initializeElements();
        this.bindEvents();
        this.loadStoredUsername();
        this.initializeCalendar();
        this.loadUsers();
    }
    
    initializeElements() {
        this.usernameInput = document.getElementById('username');
        this.statusElement = document.getElementById('status');
        this.userListElement = document.getElementById('userList');
        this.modal = document.getElementById('eventModal');
        this.modalTitle = document.getElementById('modalTitle');
        this.eventForm = document.getElementById('eventForm');
        this.eventIdInput = document.getElementById('eventId');
        this.eventTitleInput = document.getElementById('eventTitle');
        this.eventStartInput = document.getElementById('eventStart');
        this.eventEndInput = document.getElementById('eventEnd');
        this.allDayInput = document.getElementById('allDay');
        this.saveEventButton = document.getElementById('saveEvent');
        this.deleteEventButton = document.getElementById('deleteEvent');
        this.cancelEventButton = document.getElementById('cancelEvent');
        this.closeModalButton = document.getElementById('closeModal');
    }
    
    bindEvents() {
        // Username change
        this.usernameInput.addEventListener('input', (e) => this.handleUsernameChange(e));
        this.usernameInput.addEventListener('blur', () => this.saveUsername());
        
        // Modal events
        this.eventForm.addEventListener('submit', (e) => this.handleEventSubmit(e));
        this.deleteEventButton.addEventListener('click', () => this.handleEventDelete());
        this.cancelEventButton.addEventListener('click', () => this.closeModal());
        this.closeModalButton.addEventListener('click', () => this.closeModal());
        
        // All day checkbox
        this.allDayInput.addEventListener('change', (e) => this.handleAllDayChange(e));
        
        // Close modal when clicking outside
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeModal();
            }
        });
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.username) {
                this.reconnectSSE();
            }
        });
    }
    
    loadStoredUsername() {
        const stored = localStorage.getItem('calendarUsername');
        if (stored) {
            this.usernameInput.value = stored;
            this.handleUsernameChange({ target: { value: stored } });
        }
    }
    
    saveUsername() {
        if (this.username) {
            localStorage.setItem('calendarUsername', this.username);
        }
    }
    
    handleUsernameChange(e) {
        this.username = e.target.value.trim();
        
        if (this.username) {
            this.startSSE();
            this.loadUsers();
        } else {
            this.stopSSE();
        }
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
            selectable: true,
            selectMirror: true,
            editable: true,
            height: '100%',
            
            // Event handlers
            select: (info) => this.handleDateSelect(info),
            eventClick: (info) => this.handleEventClick(info),
            eventDrop: (info) => this.handleEventDrop(info),
            eventResize: (info) => this.handleEventResize(info),
            
            // Event rendering
            eventDisplay: 'block',
            eventTextColor: 'white',
            eventBorderColor: 'transparent'
        });
        
        this.calendar.render();
    }
    
    async loadUsers() {
        try {
            const response = await fetch('get_users.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateUsersList(data.users);
            }
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }
    
    updateUsersList(users) {
        this.userListElement.innerHTML = '';
        this.users.clear();
        
        users.forEach(user => {
            this.users.set(user.user_name, user);
            
            const userItem = document.createElement('div');
            userItem.className = 'user-item';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'user-checkbox';
            checkbox.id = `user-${user.user_name}`;
            checkbox.checked = this.visibleUsers.has(user.user_name) || user.user_name === this.username;
            checkbox.addEventListener('change', (e) => this.handleUserToggle(user.user_name, e.target.checked));
            
            const colorDiv = document.createElement('div');
            colorDiv.className = 'user-color';
            colorDiv.style.backgroundColor = user.color;
            
            const nameSpan = document.createElement('span');
            nameSpan.className = 'user-name';
            nameSpan.textContent = user.user_name;
            
            const countSpan = document.createElement('span');
            countSpan.className = 'user-count';
            countSpan.textContent = user.event_count;
            
            userItem.appendChild(checkbox);
            userItem.appendChild(colorDiv);
            userItem.appendChild(nameSpan);
            userItem.appendChild(countSpan);
            
            this.userListElement.appendChild(userItem);
            
            // Auto-check current user and previously selected users
            if (user.user_name === this.username || this.visibleUsers.has(user.user_name)) {
                this.visibleUsers.add(user.user_name);
                checkbox.checked = true;
            }
        });
        
        this.filterCalendarEvents();
    }
    
    handleUserToggle(username, checked) {
        if (checked) {
            this.visibleUsers.add(username);
        } else {
            this.visibleUsers.delete(username);
        }
        this.filterCalendarEvents();
    }
    
    filterCalendarEvents() {
        const events = this.calendar.getEvents();
        events.forEach(event => {
            const shouldShow = this.visibleUsers.has(event.extendedProps.user_name);
            event.setProp('display', shouldShow ? 'block' : 'none');
        });
    }
    
    handleDateSelect(info) {
        if (!this.username) {
            this.showError('Please enter your name first');
            this.usernameInput.focus();
            return;
        }
        
        this.openModal('Create Event');
        this.currentEvent = null;
        this.eventIdInput.value = '';
        this.eventTitleInput.value = '';
        
        // Set default times
        const start = new Date(info.start);
        const end = new Date(info.end);
        
        if (info.allDay) {
            // For all-day selection, set to current time
            const now = new Date();
            start.setHours(now.getHours(), now.getMinutes());
            end.setHours(now.getHours() + 1, now.getMinutes());
            this.allDayInput.checked = false;
        }
        
        this.eventStartInput.value = this.formatDateTimeLocal(start);
        this.eventEndInput.value = this.formatDateTimeLocal(end);
        
        this.updateDateTimeVisibility();
        this.deleteEventButton.style.display = 'none';
    }
    
    handleEventClick(info) {
        const event = info.event;
        
        // Only allow editing own events
        if (event.extendedProps.user_name !== this.username) {
            this.showError('You can only edit your own events');
            return;
        }
        
        this.openModal('Edit Event');
        this.currentEvent = event;
        this.eventIdInput.value = event.id;
        this.eventTitleInput.value = event.title;
        this.allDayInput.checked = event.allDay;
        
        if (event.allDay) {
            // For all-day events, show just the date part
            this.eventStartInput.value = event.startStr.split('T')[0];
            this.eventEndInput.value = event.endStr ? event.endStr.split('T')[0] : event.startStr.split('T')[0];
        } else {
            this.eventStartInput.value = this.formatDateTimeLocal(event.start);
            this.eventEndInput.value = this.formatDateTimeLocal(event.end || event.start);
        }
        
        this.updateDateTimeVisibility();
        this.deleteEventButton.style.display = 'inline-block';
    }
    
    async handleEventDrop(info) {
        if (info.event.extendedProps.user_name !== this.username) {
            info.revert();
            this.showError('You can only move your own events');
            return;
        }
        
        await this.updateEventDates(info.event);
    }
    
    async handleEventResize(info) {
        if (info.event.extendedProps.user_name !== this.username) {
            info.revert();
            this.showError('You can only resize your own events');
            return;
        }
        
        await this.updateEventDates(info.event);
    }
    
    async updateEventDates(event) {
        try {
            const formData = new URLSearchParams();
            formData.append('id', event.id);
            formData.append('username', this.username);
            formData.append('title', event.title);
            formData.append('start', this.formatDateTime(event.start));
            formData.append('end', this.formatDateTime(event.end || event.start));
            formData.append('all_day', event.allDay ? '1' : '0');
            
            const response = await fetch('manage_event.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            });
            
            if (!response.ok) {
                throw new Error('Failed to update event');
            }
        } catch (error) {
            console.error('Error updating event:', error);
            this.showError('Failed to update event');
        }
    }
    
    handleAllDayChange(e) {
        this.updateDateTimeVisibility();
    }
    
    updateDateTimeVisibility() {
        const startInput = this.eventStartInput;
        const endInput = this.eventEndInput;
        
        if (this.allDayInput.checked) {
            startInput.type = 'date';
            endInput.type = 'date';
        } else {
            startInput.type = 'datetime-local';
            endInput.type = 'datetime-local';
        }
    }
    
    async handleEventSubmit(e) {
        e.preventDefault();
        
        if (!this.username) {
            this.showError('Please enter your name first');
            return;
        }
        
        const title = this.eventTitleInput.value.trim();
        const start = this.eventStartInput.value;
        const end = this.eventEndInput.value;
        const allDay = this.allDayInput.checked;
        
        if (!title || !start || !end) {
            this.showError('Please fill in all required fields');
            return;
        }
        
        this.saveEventButton.disabled = true;
        
        try {
            const eventId = this.eventIdInput.value;
            const method = eventId ? 'PUT' : 'POST';
            const url = 'manage_event.php';
            
            const formData = new URLSearchParams();
            if (eventId) formData.append('id', eventId);
            formData.append('username', this.username);
            formData.append('title', title);
            formData.append('start', start);
            formData.append('end', end);
            formData.append('all_day', allDay ? '1' : '0');
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.closeModal();
                this.loadUsers(); // Refresh user list to update event counts
            } else {
                throw new Error(result.error || 'Failed to save event');
            }
        } catch (error) {
            console.error('Error saving event:', error);
            this.showError(error.message || 'Failed to save event');
        } finally {
            this.saveEventButton.disabled = false;
        }
    }
    
    async handleEventDelete() {
        if (!this.currentEvent) return;
        
        if (!confirm('Are you sure you want to delete this event?')) {
            return;
        }
        
        this.deleteEventButton.disabled = true;
        
        try {
            const formData = new URLSearchParams();
            formData.append('id', this.currentEvent.id);
            formData.append('username', this.username);
            
            const response = await fetch('manage_event.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.closeModal();
                this.loadUsers(); // Refresh user list to update event counts
            } else {
                throw new Error(result.error || 'Failed to delete event');
            }
        } catch (error) {
            console.error('Error deleting event:', error);
            this.showError(error.message || 'Failed to delete event');
        } finally {
            this.deleteEventButton.disabled = false;
        }
    }
    
    openModal(title) {
        this.modalTitle.textContent = title;
        this.modal.style.display = 'block';
        this.eventTitleInput.focus();
    }
    
    closeModal() {
        this.modal.style.display = 'none';
        this.currentEvent = null;
        this.eventForm.reset();
    }
    
    startSSE() {
        this.stopSSE();
        
        this.eventSource = new EventSource(`get_events.php?lastId=${this.lastNotificationId}`);
        
        this.eventSource.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                
                if (data.type === 'initial_load') {
                    this.loadInitialEvents(data.events);
                } else if (data.type === 'event_change') {
                    this.handleEventChange(data);
                    this.lastNotificationId = Math.max(this.lastNotificationId, data.notification_id);
                }
            } catch (error) {
                console.error('Error parsing SSE data:', error);
            }
        };
        
        this.eventSource.onopen = () => {
            this.updateStatus('Connected', true);
        };
        
        this.eventSource.onerror = (error) => {
            console.error('SSE error:', error);
            this.updateStatus('Disconnected', false);
            
            // Attempt to reconnect after a delay
            setTimeout(() => {
                if (this.username) {
                    this.reconnectSSE();
                }
            }, 3000);
        };
    }
    
    stopSSE() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        this.updateStatus('Disconnected', false);
    }
    
    reconnectSSE() {
        this.stopSSE();
        if (this.username) {
            setTimeout(() => this.startSSE(), 1000);
        }
    }
    
    loadInitialEvents(events) {
        this.calendar.removeAllEvents();
        
        events.forEach(eventData => {
            this.addEventToCalendar(eventData);
        });
        
        this.filterCalendarEvents();
    }
    
    handleEventChange(data) {
        const { action, event, event_id } = data;
        
        if (action === 'create' || action === 'update') {
            // Remove existing event if it's an update
            if (action === 'update') {
                const existingEvent = this.calendar.getEventById(event.id);
                if (existingEvent) {
                    existingEvent.remove();
                }
            }
            
            this.addEventToCalendar(event);
        } else if (action === 'delete') {
            const existingEvent = this.calendar.getEventById(event_id);
            if (existingEvent) {
                existingEvent.remove();
            }
        }
        
        this.filterCalendarEvents();
        this.loadUsers(); // Refresh user list to update event counts
    }
    
    addEventToCalendar(eventData) {
        const event = {
            id: eventData.id,
            title: eventData.title,
            start: eventData.start_datetime,
            end: eventData.end_datetime,
            allDay: !!eventData.all_day,
            backgroundColor: eventData.color,
            borderColor: eventData.color,
            extendedProps: {
                user_name: eventData.user_name
            }
        };
        
        this.calendar.addEvent(event);
    }
    
    updateStatus(text, connected) {
        this.statusElement.textContent = text;
        this.statusElement.className = `status ${connected ? 'connected' : ''}`;
    }
    
    formatDateTimeLocal(date) {
        if (!date) return '';
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    
    formatDateTime(date) {
        if (!date) return '';
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
    
    showError(message) {
        const errorElement = document.createElement('div');
        errorElement.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #f44336;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            z-index: 1001;
            animation: fadeIn 0.3s ease-in;
            max-width: 300px;
        `;
        errorElement.textContent = message;
        
        document.body.appendChild(errorElement);
        
        setTimeout(() => {
            errorElement.remove();
        }, 5000);
    }
}

// Initialize the calendar app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new CalendarApp();
});