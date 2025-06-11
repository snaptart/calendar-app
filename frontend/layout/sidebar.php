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
    <button id="refreshUsers" class="btn btn-small btn-outline">🔄 Refresh Users</button>
</div>

<div class="calendar-actions">
    <button id="addEventBtn" class="btn btn-primary">
        <span class="btn-icon">+</span>
        Add Event
    </button>
    <button id="viewEventsBtn" class="btn btn-outline" onclick="window.location.href='index.php?page=events'">
        <span class="btn-icon">📋</span>
        View All Events
    </button>
    <button id="importEventsBtn" class="btn btn-outline" onclick="window.location.href='index.php?page=import'">
        <span class="btn-icon">📥</span>
        Import Events
    </button>
    <div class="connection-status">
        <span id="connectionStatus" class="status">Initializing...</span>
    </div>
</div>