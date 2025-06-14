// Frontend JavaScript for collaborative calendar
// Save as: script.js

class CollaborativeCalendar {
    constructor() {
        this.calendar = null;
        this.pollInterval = null;
        this.currentUser = '';
        this.selectedUsers = new Set();
        this.allUsers = [];
        this.currentEvent = null;
        this.lastEventId = 0;
        
        this.init();
    }
    
    init() {
        this.initializeCalendar();
        this.setupEventListeners();
        this.setupPolling();
        this.loadUsers();
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
            dayMaxEvents: true,
            weekends: true,
            events: [],
            
            // Event handlers
            select: (info) => this.handleDateSelect(info),
            eventClick: (info) => this.handleEventClick(info),
            eventDrop: (info) => this.handleEventMove(info),
            eventResize: (info) => this.handleEventResize(info)
        });
        
        this.calendar.render();
    }
    
    setupEventListeners() {
        // User name input
        const userNameInput = document.getElementById('userName');
        userNameInput.addEventListener('change', (e) => {
            this.setCurrentUser(e.target.value.trim());
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
    
    setupPolling() {
        this.startPolling();
    }
    
    startPolling() {
        this.updateConnectionStatus('Connected', 'connected');
        
        // Poll for updates every 3 seconds
        this.pollInterval = setInterval(async () => {
            try {
                await this.checkForUpdates();
            } catch (error) {
                console.error('Polling error:', error);
                this.updateConnectionStatus('Connection Error', 'disconnected');
            }
        }, 3000);
        
        // Initial check
        this.checkForUpdates();
    }
    
    async checkForUpdates() {
        try {
            const response = await fetch(`api.php?action=updates&lastId=${this.lastEventId}`);
            const updates = await response.json();
            
            if (Array.isArray(updates)) {
                updates.forEach(update => {
                    this.handleUpdate(update);
                    this.lastEventId = Math.max(this.lastEventId, update.id);
                });
                
                if (updates.length > 0) {
                    this.updateConnectionStatus('Connected', 'connected');
                }
            }
        } catch (error) {
            this.updateConnectionStatus('Connection Error', 'disconnected');
            throw error;
        }
    }
    
    handleUpdate(update) {
        const eventData = JSON.parse(update.event_data);
        
        switch (update.event_type) {
            case 'create':
                this.addEventToCalendar(eventData);
                break;
            case 'update':
                this.updateEventInCalendar(eventData);
                break;
            case 'delete':
                this.removeEventFromCalendar(eventData.id);
                break;
        }
    }
    
    async loadUsers() {
        try {
            const response = await fetch('api.php?action=users');
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
    
    setCurrentUser(userName) {
        if (!userName) return;
        
        this.currentUser = userName;
        this.updateUserStatus(`Set as: ${userName}`, 'user-set');
        
        // Find user in list and auto-select their calendar
        const user = this.allUsers.find(u => u.name === userName);
        if (user) {
            this.selectedUsers.add(user.id.toString());
            this.renderUserCheckboxes();
            this.loadEvents();
        }
    }
    
    async loadEvents() {
        if (this.selectedUsers.size === 0) {
            this.calendar.removeAllEvents();
            return;
        }
        
        try {
            const userIds = Array.from(this.selectedUsers).join(',');
            const response = await fetch(`api.php?action=events&user_ids=${userIds}`);
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
        const canEdit = event.extendedProps.userName === this.currentUser;
        
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
        
        if (event.extendedProps.userName !== this.currentUser) {
            info.revert();
            alert('You can only move your own events!');
            return;
        }
        
        await this.updateEvent({
            id: event.id,
            title: event.title,
            start: event.startStr,
            end: event.endStr || event.startStr
        });
    }
    
    async handleEventResize(info) {
        const event = info.event;
        
        if (event.extendedProps.userName !== this.currentUser) {
            info.revert();
            alert('You can only resize your own events!');
            return;
        }
        
        await this.updateEvent({
            id: event.id,
            title: event.title,
            start: event.startStr,
            end: event.endStr || event.startStr
        });
    }
    
    openEventModal(eventData = {}) {
        const modal = document.getElementById('eventModal');
        const modalTitle = document.getElementById('modalTitle');
        const deleteBtn = document.getElementById('deleteEventBtn');
        
        // Set form values
        document.getElementById('eventTitle').value = eventData.title || '';
        document.getElementById('eventStart').value = this.formatDateTimeLocal(eventData.start || new Date());
        document.getElementById('eventEnd').value = this.formatDateTimeLocal(eventData.end || eventData.start || new Date());
        
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
    }
    
    async saveEvent() {
        if (!this.currentUser) {
            alert('Please enter your name first!');
            return;
        }
        
        const title = document.getElementById('eventTitle').value.trim();
        const start = document.getElementById('eventStart').value;
        const end = document.getElementById('eventEnd').value;
        
        if (!title || !start) {
            alert('Please fill in all required fields!');
            return;
        }
        
        const eventData = {
            title,
            start,
            end: end || start,
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
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(eventData)
        });
        
        if (!response.ok) {
            throw new Error('Failed to create event');
        }
        
        return response.json();
    }
    
    async updateEvent(eventData) {
        const response = await fetch('api.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(eventData)
        });
        
        if (!response.ok) {
            throw new Error('Failed to update event');
        }
        
        return response.json();
    }
    
    async deleteEvent() {
        if (!this.currentEvent || !confirm('Are you sure you want to delete this event?')) {
            return;
        }
        
        try {
            const response = await fetch(`api.php?id=${this.currentEvent.id}`, {
                method: 'DELETE'
            });
            
            if (!response.ok) {
                throw new Error('Failed to delete event');
            }
            
            this.closeEventModal();
        } catch (error) {
            console.error('Error deleting event:', error);
            alert('Error deleting event. Please try again.');
        }
    }
    
    addEventToCalendar(eventData) {
        // Only add if this user's calendar is selected
        if (this.selectedUsers.has(eventData.extendedProps.userId.toString())) {
            this.calendar.addEvent(eventData);
        }
    }
    
    updateEventInCalendar(eventData) {
        const event = this.calendar.getEventById(eventData.id);
        if (event) {
            event.setProp('title', eventData.title);
            event.setStart(eventData.start);
            event.setEnd(eventData.end);
        }
    }
    
    removeEventFromCalendar(eventId) {
        const event = this.calendar.getEventById(eventId);
        if (event) {
            event.remove();
        }
    }
    
    updateConnectionStatus(message, className = '') {
        const statusEl = document.getElementById('connectionStatus');
        statusEl.textContent = message;
        statusEl.className = `status ${className}`;
    }
    
    updateUserStatus(message, className = '') {
        const statusEl = document.getElementById('userStatus');
        statusEl.textContent = message;
        statusEl.className = `status ${className}`;
    }
    
    formatDateTimeLocal(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        
        // Format as YYYY-MM-DDTHH:MM for datetime-local input
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
}

// Initialize the calendar when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new CollaborativeCalendar();
});