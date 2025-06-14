<?php
// Sidebar is currently only used on the calendar page
// Contains user filters and calendar-specific actions
?>

<div class="user-filters">
    <h3>Show Calendars</h3>
    <div id="userCheckboxes" class="checkbox-group">
        <!-- User checkboxes will be populated dynamically by JavaScript -->
        <div class="loading-message">Loading users...</div>
    </div>
    <button id="refreshUsers" class="btn btn-small btn-outline">
        <i data-lucide="refresh-cw"></i> Refresh Users
    </button>
</div>

<div class="calendar-actions">
    <button id="addEventBtn" class="btn btn-primary">
        <i data-lucide="plus"></i>
        Add Event
    </button>
    <button id="viewEventsBtn" class="btn btn-outline" data-route="/events">
        <i data-lucide="list-checks"></i>
        View All Events
    </button>
    <button id="importEventsBtn" class="btn btn-outline" data-route="/import">
        <i data-lucide="download"></i>
        Import Events
    </button>
    <div class="connection-status">
        <span id="connectionStatus" class="status disconnected">Connecting...</span>
    </div>
</div>