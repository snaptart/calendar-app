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
                    <label for="viewFilter">VIEW:</label>
                    <select id="viewFilter" class="filter-select">
                        <option value="all">All Events</option>
                        <option value="my">My Events Only</option>
                        <option value="others">Others' Events</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="statusFilter">STATUS:</label>
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="past">Past</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="userFilter">USER:</label>
                    <select id="userFilter" class="filter-select">
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

<!-- Edit Event Modal -->
<div id="editEventModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Event</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editEventForm">
                <input type="hidden" id="editEventId">
                <div class="form-group">
                    <label for="editEventTitle">Event Title</label>
                    <input type="text" id="editEventTitle" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editEventStart">Start Date & Time</label>
                        <input type="datetime-local" id="editEventStart" required>
                    </div>
                    <div class="form-group">
                        <label for="editEventEnd">End Date & Time</label>
                        <input type="datetime-local" id="editEventEnd" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>