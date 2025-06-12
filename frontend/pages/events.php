<?php
// Events page - Table view of all events
// This page is included by index.php, so all variables and authentication are already available
?>


<div class="stats-section" style="display: none;">
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-number" id="totalEventsCount">0</div>
            <div class="stat-label">Total Events</div>
        </div>
        <div class="stat-item">
            <div class="stat-number" id="upcomingEventsCount">0</div>
            <div class="stat-label">Upcoming</div>
        </div>
        <div class="stat-item">
            <div class="stat-number" id="ongoingEventsCount">0</div>
            <div class="stat-label">Ongoing</div>
        </div>
        <div class="stat-item">
            <div class="stat-number" id="myEventsCount">0</div>
            <div class="stat-label">My Events</div>
        </div>
    </div>
</div>

<div class="calendar-events-section">
    <div class="section-header">
        <div class="section-info">
            <h3>Calendar Events</h3>
            <p class="section-description">View and manage calendar events with advanced filtering and search</p>
        </div>
        <div class="section-controls">
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="eventViewFilter">VIEW:</label>
                    <select id="eventViewFilter" class="filter-select">
                        <option value="all">All Events</option>
                        <option value="my">My Events Only</option>
                        <option value="others">Others' Events</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="eventStatusFilter">STATUS:</label>
                    <select id="eventStatusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="past">Past</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="eventUserFilter">USER:</label>
                    <select id="eventUserFilter" class="filter-select">
                        <option value="">All Users</option>
                    </select>
                </div>
                <div class="action-buttons">
                    <button id="refreshEventsBtn" class="btn btn-outline">
                        🔄 Refresh Data
                    </button>
                    <button id="addEventBtn" class="btn btn-primary">
                        + Add Event
                    </button>
                </div>
                <div class="connection-status">
                    <span id="connectionStatus" class="status">Connected</span>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="table-container">
    <table id="eventsTable" class="data-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Start</th>
                <th>End</th>
                <th>Duration</th>
                <th>User</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="eventsTableBody">
            <!-- Data will be populated by JavaScript -->
        </tbody>
    </table>
</div>

<!-- Event Modal -->
<div id="eventModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Event Details</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Event Details View -->
            <div id="eventDetails">
                <!-- Content populated by JavaScript -->
            </div>
            
            <!-- Event Edit Form -->
            <div id="eventForm" style="display: none;">
                <form id="editEventForm">
                    <div class="form-group">
                        <label for="eventTitle">Event Title</label>
                        <input type="text" id="eventTitle" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="eventStart">Start Date & Time</label>
                            <input type="datetime-local" id="eventStart" required>
                        </div>
                        <div class="form-group">
                            <label for="eventEnd">End Date & Time</label>
                            <input type="datetime-local" id="eventEnd" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" id="cancelEditBtn" class="btn btn-outline">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>