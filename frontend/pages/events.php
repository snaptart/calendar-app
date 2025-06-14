<?php
// Events page - Table view of all events
// This page is included by index.php, so all variables and authentication are already available

// Include the shared data section header component
require_once __DIR__ . '/../components/data-section-header.php';
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

<?php renderEventsPageHeader(); ?>


<div class="table-container">
    <table id="eventsTable" class="display">
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