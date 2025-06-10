<?php
/**
 * Import Page - Updated with Component Architecture
 * Location: frontend/pages/import.php
 * 
 * Events import page using the new component-based architecture
 */

// Include service layer
require_once __DIR__ . '/../services/ConfigService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../layouts/AppLayout.php';

// Initialize services
$config = ConfigService::getInstance();
$auth = AuthService::guard(true); // Require authentication

// Get page configuration
$pageConfig = $config->forLayout('import', [
    'pageTitle' => '📥 Import Events',
    'activeNavItem' => 'import',
    'showUserSection' => true,
    'showNavigation' => true,
    'breadcrumbs' => [
        ['title' => 'Calendar', 'url' => './calendar.php'],
        ['title' => 'Import Events']
    ]
]);

// Render the import page using component architecture
AppLayout::createComponentPage($pageConfig, function() use ($config) {
    ?>
    <div class="import-container" data-component-container="import">
        
        <!-- Import Instructions -->
        <div class="import-instructions">
            <div class="instruction-content">
                <h3>📋 Import Instructions</h3>
                <div class="instruction-grid">
                    <div class="instruction-item">
                        <div class="instruction-icon">📄</div>
                        <div class="instruction-text">
                            <h4>Supported Formats</h4>
                            <p>JSON, CSV, and iCalendar (.ics) files</p>
                        </div>
                    </div>
                    <div class="instruction-item">
                        <div class="instruction-icon">📊</div>
                        <div class="instruction-text">
                            <h4>File Limits</h4>
                            <p>Maximum 5MB file size, up to 50 events</p>
                        </div>
                    </div>
                    <div class="instruction-item">
                        <div class="instruction-icon">👥</div>
                        <div class="instruction-text">
                            <h4>User Matching</h4>
                            <p>Events must reference existing user names</p>
                        </div>
                    </div>
                    <div class="instruction-item">
                        <div class="instruction-icon">⏰</div>
                        <div class="instruction-text">
                            <h4>Future Events Only</h4>
                            <p>Only future-dated events will be imported</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Upload Section -->
        <div class="import-form-container">
            <div class="import-form-content">
                <h3>Select File to Import</h3>
                
                <!-- File Upload Component -->
                <div id="importFileUpload" 
                     class="file-upload-wrapper"
                     data-component="upload"
                     data-component-id="import-file-upload"
                     data-config='<?php echo json_encode([
                         'multiple' => false,
                         'dragDrop' => true,
                         'autoUpload' => false,
                         'showProgress' => true,
                         'maxFileSize' => 5242880, // 5MB
                         'allowedTypes' => ['.json', '.csv', '.ics', '.ical', '.txt'],
                         'uploadUrl' => '../../backend/api.php',
                         'fieldName' => 'import_file'
                     ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                     data-upload-url="../../backend/api.php"
                     data-max-file-size="5242880"
                     data-allowed-types=".json,.csv,.ics,.ical,.txt"
                     data-multiple="false"
                     data-drag-drop="true"
                     data-auto-upload="false"
                     data-show-progress="true"
                     data-auto-init="true">
                    
                    <div class="file-dropzone" data-dropzone="true">
                        <div class="dropzone-content">
                            <div class="dropzone-icon">📁</div>
                            <div class="dropzone-text">
                                Drag and drop your file here or click to browse
                            </div>
                            <button type="button" class="btn btn-primary btn-small">
                                Browse Files
                            </button>
                        </div>
                        
                        <input type="file" 
                               class="file-input" 
                               name="import_file"
                               accept=".json,.csv,.ics,.ical,.txt"
                               style="display: none;">
                    </div>
                    
                    <div class="file-preview" 
                         data-file-preview="true" 
                         style="display: none;">
                        <!-- File preview will be populated by JavaScript -->
                    </div>
                    
                    <div class="upload-progress" 
                         data-upload-progress="true" 
                         style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 0%"></div>
                        </div>
                        <div class="progress-text">0%</div>
                    </div>
                </div>
                
                <!-- Import Options Form -->
                <form id="importOptionsForm"
                      data-component="form"
                      data-component-id="import-options-form"
                      data-validation="false"
                      data-auto-init="true">
                    
                    <div class="form-group">
                        <div class="checkbox-item">
                            <input type="checkbox" 
                                   id="validateUsers" 
                                   name="validate_users" 
                                   value="1" 
                                   checked>
                            <label for="validateUsers">Validate user names against existing users</label>
                            <small class="form-help">Events with non-existent users will be skipped</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-item">
                            <input type="checkbox" 
                                   id="futureOnly" 
                                   name="future_only" 
                                   value="1" 
                                   checked>
                            <label for="futureOnly">Import only future events</label>
                            <small class="form-help">Events in the past will be ignored</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-item">
                            <input type="checkbox" 
                                   id="dryRun" 
                                   name="dry_run" 
                                   value="1">
                            <label for="dryRun">Dry run (preview only)</label>
                            <small class="form-help">Preview import results without actually importing</small>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Import Actions -->
        <div class="import-actions">
            <button id="validateBtn" 
                    class="btn btn-outline"
                    data-component="button"
                    data-action="validate"
                    data-target="#importFileUpload"
                    disabled>
                🔍 Validate File
            </button>
            <button id="previewBtn" 
                    class="btn btn-outline"
                    data-component="button"
                    data-action="preview"
                    data-target="#importFileUpload"
                    disabled>
                👁️ Preview Events
            </button>
            <button id="importBtn" 
                    class="btn btn-primary"
                    data-component="button"
                    data-action="import"
                    data-target="#importFileUpload"
                    disabled>
                📥 Import Events
            </button>
        </div>

        <!-- Messages Container -->
        <div id="importMessages" 
             class="import-messages"
             data-component="messages"
             data-component-id="import-messages"
             data-auto-init="true">
            <!-- Messages will be displayed here -->
        </div>

        <!-- Results Section -->
        <div id="resultsSection" 
             class="results-section" 
             style="display: none;"
             data-component="results"
             data-component-id="import-results"
             data-auto-init="true">
            <div class="results-content">
                <h3 id="resultsTitle">Import Results</h3>
                <div id="resultsBody">
                    <!-- Results will be populated here -->
                </div>
            </div>
        </div>

        <!-- Sample Files Section -->
        <div class="sample-files-section">
            <div class="sample-files-content">
                <h3>📚 Sample Files & Format Guide</h3>
                <div class="format-tabs"
                     data-component="tabs"
                     data-component-id="format-tabs"
                     data-auto-init="true">
                    <button class="format-tab active" data-format="json">JSON Format</button>
                    <button class="format-tab" data-format="csv">CSV Format</button>
                    <button class="format-tab" data-format="ics">iCalendar Format</button>
                </div>

                <div class="format-examples">
                    <div id="jsonExample" class="format-example active" data-format-content="json">
                        <h4>JSON Format Example</h4>
                        <pre><code>[
  {
    "title": "Team Meeting",
    "start": "2025-06-15 10:00:00",
    "end": "2025-06-15 11:00:00",
    "user_name": "John Doe",
    "description": "Weekly team sync"
  },
  {
    "title": "Ice Time Session",
    "start": "2025-06-16 14:00:00",
    "end": "2025-06-16 15:30:00",
    "user_name": "Jane Smith",
    "description": "Figure skating practice"
  }
]</code></pre>
                    </div>

                    <div id="csvExample" class="format-example" data-format-content="csv">
                        <h4>CSV Format Example</h4>
                        <pre><code>title,start,end,user_name,description
Team Meeting,2025-06-15 10:00:00,2025-06-15 11:00:00,John Doe,Weekly team sync
Ice Time Session,2025-06-16 14:00:00,2025-06-16 15:30:00,Jane Smith,Figure skating practice</code></pre>
                    </div>

                    <div id="icsExample" class="format-example" data-format-content="ics">
                        <h4>iCalendar (.ics) Format Example</h4>
                        <pre><code>BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Calendar App//EN
BEGIN:VEVENT
UID:1@example.com
DTSTART:20250615T100000Z
DTEND:20250615T110000Z
SUMMARY:Team Meeting
DESCRIPTION:Weekly team sync
ORGANIZER:John Doe
END:VEVENT
BEGIN:VEVENT
UID:2@example.com
DTSTART:20250616T140000Z
DTEND:20250616T153000Z
SUMMARY:Ice Time Session
DESCRIPTION:Figure skating practice
ORGANIZER:Jane Smith
END:VEVENT
END:VCALENDAR</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Modal -->
        <div id="progressModal" 
             class="modal"
             data-component="modal"
             data-component-id="progress-modal"
             data-config='<?php echo json_encode([
                 'backdrop' => 'static',
                 'keyboard' => false,
                 'closeButton' => false
             ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-auto-init="true">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Processing Import...</h2>
                </div>
                <div class="modal-body">
                    <div class="progress-container">
                        <div class="progress-spinner">
                            <div class="spinner"></div>
                        </div>
                        <p id="progressMessage">Please wait while we process your file...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div id="previewModal" 
             class="modal"
             data-component="modal"
             data-component-id="preview-modal"
             data-config='<?php echo json_encode([
                 'size' => 'large',
                 'backdrop' => 'static',
                 'keyboard' => false,
                 'closeButton' => true
             ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-auto-init="true">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Import Preview</h2>
                    <span class="close" data-action="close-modal">&times;</span>
                </div>
                <div class="modal-body">
                    <div id="previewContent">
                        <!-- Preview content will be populated here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" 
                            data-component="button"
                            data-action="proceed-import">
                        Proceed with Import
                    </button>
                    <button class="btn btn-outline" 
                            data-action="close-modal">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Enhanced JavaScript for Import Functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format tab switching
        const formatTabs = document.querySelectorAll('.format-tab');
        const formatExamples = document.querySelectorAll('.format-example');
        
        formatTabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                const targetFormat = tab.dataset.format;
                
                // Update tab states
                formatTabs.forEach(function(t) {
                    t.classList.remove('active');
                });
                tab.classList.add('active');
                
                // Update example visibility
                formatExamples.forEach(function(example) {
                    example.classList.remove('active');
                    if (example.dataset.formatContent === targetFormat) {
                        example.classList.add('active');
                    }
                });
            });
        });
        
        console.log('Import page loaded with component architecture');
    });
    </script>
    <?php
});

// Add authentication status and configuration to JavaScript
?>
<script>
<?php echo $auth->generateAuthJs(); ?>

// Add import page specific configuration
window.ImportPage = {
    maxFileSize: <?php echo $config->get('import.max_file_size', 5242880); ?>,
    maxEvents: <?php echo $config->get('import.max_events', 50); ?>,
    allowedFormats: <?php echo json_encode($config->get('import.allowed_formats', ['.json', '.csv', '.ics'])); ?>,
    apiUrl: '../../backend/api.php'
};

// Configuration for the component system
<?php echo $config->generateConfigJs(); ?>

console.log('Import page configuration:', window.ImportPage);
</script>