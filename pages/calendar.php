<?php
// Calendar page - Main calendar view
// This page is included by index.php, so all variables and authentication are already available
?>

<div class="calendar-wrapper">
    <div id="calendar"></div>
</div>

<!-- Event Modal -->
<div id="eventModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Event</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="eventForm">
                <div class="form-group">
                    <label for="eventTitle">Event Title</label>
                    <input type="text" id="eventTitle" placeholder="Enter event title..." required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="eventStart">Start Date & Time</label>
                        <input type="text" id="eventStart" placeholder="Select start date & time..." required readonly>
                    </div>
                    <div class="form-group">
                        <label for="eventEnd">End Date & Time</label>
                        <input type="text" id="eventEnd" placeholder="Select end date & time..." required readonly>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Event</button>
                    <button type="button" id="deleteEventBtn" class="btn btn-danger" style="display: none;">Delete Event</button>
                    <button type="button" class="btn btn-outline" id="cancelBtn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>