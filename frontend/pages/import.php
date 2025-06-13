<?php
// Import page - Import events from various file formats
// This page is included by index.php, so all variables and authentication are already available
?>

<div class="page-header">
    <h2>Import Events</h2>
    <div class="header-actions">
        <button class="btn btn-outline" onclick="window.location.href='index.php'">
            <i data-lucide="calendar"></i>
            Back to Calendar
        </button>
    </div>
</div>

<div class="import-container">
    <div class="import-section">
        <h3>Upload File</h3>
        <p class="section-description">
            Import events from JSON, CSV, or iCal (.ics) files. Maximum file size: 5MB, Maximum events: 20.
        </p>
        
        <form id="importForm" enctype="multipart/form-data">
            <div class="file-upload-area" id="fileUploadArea">
                <div class="upload-icon">
                    <i data-lucide="folder-open" style="width: 48px; height: 48px;"></i>
                </div>
                <p>Drag and drop your file here or click to browse</p>
                <input type="file" id="fileInput" accept=".json,.csv,.ics,.ical" style="display: none;">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                    Choose File
                </button>
            </div>
            
            <div id="fileInfo" class="file-info" style="display: none;">
                <h4>Selected File:</h4>
                <p id="fileName"></p>
                <p id="fileSize"></p>
                <button type="button" class="btn btn-small btn-outline" onclick="clearFile()">
                    Remove File
                </button>
            </div>
            
            <div class="form-group">
                <label for="assignToUser">Assign Events To:</label>
                <select id="assignToUser" required>
                    <option value="<?php echo $currentUser['id']; ?>" selected>
                        Myself (<?php echo htmlspecialchars($currentUser['name']); ?>)
                    </option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="previewBtn" disabled>
                    Preview Import
                </button>
            </div>
        </form>
    </div>
    
    <div id="previewSection" class="preview-section" style="display: none;">
        <h3>Import Preview</h3>
        <div id="importSummary" class="import-summary"></div>
        
        <div class="preview-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll" checked>
                        </th>
                        <th>Title</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="previewTableBody">
                </tbody>
            </table>
        </div>
        
        <div class="form-actions">
            <button type="button" class="btn btn-primary" id="confirmImportBtn">
                Import Selected Events
            </button>
            <button type="button" class="btn btn-outline" onclick="cancelImport()">
                Cancel
            </button>
        </div>
    </div>
    
    <div id="importProgress" class="import-progress" style="display: none;">
        <h3>Importing Events...</h3>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <p id="progressText">Processing...</p>
    </div>
    
    <div id="importResults" class="import-results" style="display: none;">
        <h3>Import Complete</h3>
        <div id="resultsContent"></div>
        <button class="btn btn-primary" onclick="window.location.href='index.php'">
            View Calendar
        </button>
        <button class="btn btn-outline" onclick="resetImport()">
            Import More Events
        </button>
    </div>
</div>

<!-- Error/Success Messages -->
<div id="importMessages" class="import-messages"></div>