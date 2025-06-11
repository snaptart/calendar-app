<?php
// Events page - Table view of all events
// This page is included by index.php, so all variables and authentication are already available
?>

<div class="page-header">
    <h2>All Events</h2>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="window.location.href='index.php'">
            <span class="btn-icon">📅</span>
            Back to Calendar
        </button>
        <button class="btn btn-outline" id="exportBtn">
            <span class="btn-icon">📤</span>
            Export Events
        </button>
    </div>
</div>

<div class="stats-section">
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

<div class="filters-section">
    <div class="filter-group">
        <label for="userFilter">Filter by User:</label>
        <select id="userFilter" class="filter-select">
            <option value="">All Users</option>
        </select>
    </div>
    <div class="filter-group">
        <label for="dateFilter">Date Range:</label>
        <select id="dateFilter" class="filter-select">
            <option value="all">All Time</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="custom">Custom Range</option>
        </select>
    </div>
    <div class="filter-group" id="customDateRange" style="display: none;">
        <input type="date" id="startDate" class="filter-input">
        <span>to</span>
        <input type="date" id="endDate" class="filter-input">
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