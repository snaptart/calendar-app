<?php
/**
 * ImportForm Component - Specialized form for file import functionality
 * Location: frontend/components/forms/ImportForm.php
 * 
 * Pre-configured form component specifically for importing events from files
 */

require_once __DIR__ . '/BaseForm.php';
require_once __DIR__ . '/../ui/FileUpload.php';
require_once __DIR__ . '/../ui/Modal.php';

class ImportForm extends BaseForm {
    private $fileUpload;
    private $previewModal;
    private $progressModal;
    
    /**
     * Constructor with import-specific defaults
     */
    public function __construct($config = []) {
        // Import form specific configuration
        $importConfig = [
            'formId' => 'importForm',
            'method' => 'POST',
            'enctype' => 'multipart/form-data',
            'class' => 'import-form',
            'validation' => [
                'clientSide' => true,
                'realTime' => false,
                'showErrors' => true
            ],
            'submission' => [
                'ajax' => true,
                'url' => '../../backend/api.php',
                'loadingText' => 'Processing import...',
                'resetOnSuccess' => false
            ],
            'csrf' => [
                'enabled' => false // Handled by backend API
            ],
            'import' => [
                'maxFileSize' => 5 * 1024 * 1024, // 5MB
                'maxEvents' => 20,
                'allowedFormats' => ['.json', '.csv', '.ics', '.ical', '.txt'],
                'supportedTypes' => ['application/json', 'text/csv', 'text/calendar', 'text/plain']
            ]
        ];
        
        // Merge with provided config
        $mergedConfig = array_merge_recursive($importConfig, $config);
        
        parent::__construct($mergedConfig);
        
        // Initialize components
        $this->initializeComponents();
        
        // Set up import form fields
        $this->setupImportFields();
        
        // Add import-specific JavaScript
        $this->addImportFormJs();
    }
    
    /**
     * Initialize file upload and modal components
     */
    private function initializeComponents() {
        // File upload component
        $this->fileUpload = new FileUpload([
            'uploadId' => 'importFileUpload',
            'name' => 'import_file',
            'maxFileSize' => $this->config['import']['maxFileSize'],
            'acceptedExtensions' => $this->config['import']['allowedFormats'],
            'acceptedTypes' => $this->config['import']['supportedTypes'],
            'multiple' => false,
            'auto' => false, // Manual upload trigger
            'dragDrop' => true,
            'preview' => true,
            'validation' => [
                'clientSide' => true,
                'showErrors' => true
            ],
            'messages' => [
                'dragHere' => 'Drag and drop your file here',
                'clickBrowse' => 'or click to browse files',
                'selectFiles' => 'Browse Files',
                'invalidType' => 'Invalid file type. Supported formats: ' . implode(', ', $this->config['import']['allowedFormats']),
                'tooLarge' => 'File too large. Maximum size: ' . $this->formatFileSize($this->config['import']['maxFileSize'])
            ]
        ]);
        
        // Preview modal
        $this->previewModal = new Modal([
            'modalId' => 'previewModal',
            'title' => 'Import Preview',
            'size' => 'large',
            'backdrop' => 'static',
            'keyboard' => false,
            'closeButton' => true
        ]);
        
        $this->previewModal->addPrimaryButton('Proceed with Import', 'proceedWithImport()')
                           ->addCloseButton('Cancel');
        
        // Progress modal
        $this->progressModal = new Modal([
            'modalId' => 'progressModal',
            'title' => 'Processing Import...',
            'size' => 'medium',
            'backdrop' => 'static',
            'keyboard' => false,
            'closeButton' => false
        ]);
    }
    
    /**
     * Set up import form fields
     */
    private function setupImportFields() {
        // Hidden action field
        $this->addHidden('action', 'import_events');
        
        // File upload field (handled by FileUpload component)
        // This is just a placeholder for the file upload component
        $this->addField([
            'name' => 'import_file_placeholder',
            'type' => 'custom',
            'label' => 'Import File',
            'required' => true,
            'wrapper' => 'file-upload-wrapper'
        ]);
        
        // Import options
        $this->addCheckbox('validate_users', 'Validate user names against existing users', [
            'value' => '1',
            'checked' => true,
            'help' => 'Events with non-existent users will be skipped'
        ]);
        
        $this->addCheckbox('future_only', 'Import only future events', [
            'value' => '1',
            'checked' => true,
            'help' => 'Events in the past will be ignored'
        ]);
        
        $this->addCheckbox('dry_run', 'Dry run (preview only)', [
            'value' => '1',
            'checked' => false,
            'help' => 'Preview import results without actually importing'
        ]);
    }
    
    /**
     * Override renderField to handle custom file upload field
     */
    protected function renderField($field) {
        if ($field['name'] === 'import_file_placeholder') {
            $this->renderFileUploadField();
            return;
        }
        
        parent::renderField($field);
    }
    
    /**
     * Render file upload field
     */
    private function renderFileUploadField() {
        ?>
        <div class="form-group file-upload-section">
            <label class="form-label">Import File *</label>
            <?php $this->fileUpload->render(); ?>
        </div>
        <?php
    }
    
    /**
     * Add import-specific JavaScript
     */
    private function addImportFormJs() {
        $this->config['submission']['beforeSubmit'] = 'importBeforeSubmit';
        $this->config['submission']['onSuccess'] = 'importOnSuccess';
        $this->config['submission']['onError'] = 'importOnError';
        
        $this->addInlineJS('
            let selectedFile = null;
            let previewData = null;
            
            // Import form specific handlers
            function importBeforeSubmit(form) {
                // Check if file is selected
                if (!selectedFile) {
                    showImportMessage("Please select a file to import", "error");
                    return false;
                }
                
                // Show progress modal
                showProgressModal("Validating file...");
                
                return true;
            }
            
            function importOnSuccess(data, form) {
                hideProgressModal();
                
                if (data.success) {
                    if (data.preview && !data.imported) {
                        // Show preview data
                        showPreviewModal(data);
                    } else {
                        // Import completed
                        showImportResults(data);
                    }
                } else {
                    showImportMessage(data.error || "Import failed", "error");
                }
            }
            
            function importOnError(data, form) {
                hideProgressModal();
                const errorMessage = data.error || data.message || "Import failed. Please try again.";
                showImportMessage(errorMessage, "error");
            }
            
            // File upload event handlers
            document.addEventListener("fileSelect", function(e) {
                if (e.detail.uploadId === "importFileUpload") {
                    selectedFile = e.detail.files[0];
                    updateFileInfo(selectedFile);
                    enableImportButtons();
                }
            });
            
            document.addEventListener("fileRemove", function(e) {
                if (e.detail.uploadId === "importFileUpload") {
                    selectedFile = null;
                    clearFileInfo();
                    disableImportButtons();
                }
            });
            
            function updateFileInfo(file) {
                const fileInfo = document.getElementById("fileInfo");
                const fileName = document.getElementById("fileName");
                const fileSize = document.getElementById("fileSize");
                
                if (fileInfo && fileName && fileSize) {
                    fileName.textContent = file.name;
                    fileSize.textContent = formatFileSize(file.size);
                    fileInfo.style.display = "block";
                }
            }
            
            function clearFileInfo() {
                const fileInfo = document.getElementById("fileInfo");
                if (fileInfo) {
                    fileInfo.style.display = "none";
                }
            }
            
            function enableImportButtons() {
                const validateBtn = document.getElementById("validateBtn");
                const previewBtn = document.getElementById("previewBtn");
                const importBtn = document.getElementById("importBtn");
                
                if (validateBtn) validateBtn.disabled = false;
                if (previewBtn) previewBtn.disabled = false;
                if (importBtn) importBtn.disabled = false;
            }
            
            function disableImportButtons() {
                const validateBtn = document.getElementById("validateBtn");
                const previewBtn = document.getElementById("previewBtn");
                const importBtn = document.getElementById("importBtn");
                
                if (validateBtn) validateBtn.disabled = true;
                if (previewBtn) previewBtn.disabled = true;
                if (importBtn) importBtn.disabled = true;
            }
            
            // Action button handlers
            function validateFile() {
                if (!selectedFile) {
                    showImportMessage("No file selected", "error");
                    return;
                }
                
                showProgressModal("Validating file...");
                
                const formData = new FormData();
                formData.append("action", "validate_import_file");
                formData.append("import_file", selectedFile);
                
                performImportRequest(formData, function(data) {
                    hideProgressModal();
                    
                    if (data.success) {
                        showImportMessage("File validation successful", "success");
                    } else {
                        showImportMessage(data.error || "File validation failed", "error");
                    }
                });
            }
            
            function previewImport() {
                if (!selectedFile) {
                    showImportMessage("No file selected", "error");
                    return;
                }
                
                showProgressModal("Generating preview...");
                
                const formData = new FormData();
                formData.append("action", "preview_import");
                formData.append("import_file", selectedFile);
                addFormOptions(formData);
                
                performImportRequest(formData, function(data) {
                    hideProgressModal();
                    
                    if (data.success) {
                        showPreviewModal(data);
                    } else {
                        showImportMessage(data.error || "Preview generation failed", "error");
                    }
                });
            }
            
            function importEvents() {
                if (!selectedFile) {
                    showImportMessage("No file selected", "error");
                    return;
                }
                
                showProgressModal("Importing events...");
                
                const formData = new FormData();
                formData.append("action", "import_events");
                formData.append("import_file", selectedFile);
                addFormOptions(formData);
                
                performImportRequest(formData, function(data) {
                    hideProgressModal();
                    
                    if (data.success) {
                        showImportResults(data);
                    } else {
                        showImportMessage(data.error || "Import failed", "error");
                    }
                });
            }
            
            function addFormOptions(formData) {
                const form = document.getElementById("importForm");
                if (form) {
                    const validateUsers = form.querySelector("[name=\"validate_users\"]");
                    const futureOnly = form.querySelector("[name=\"future_only\"]");
                    const dryRun = form.querySelector("[name=\"dry_run\"]");
                    
                    if (validateUsers && validateUsers.checked) {
                        formData.append("validate_users", "1");
                    }
                    if (futureOnly && futureOnly.checked) {
                        formData.append("future_only", "1");
                    }
                    if (dryRun && dryRun.checked) {
                        formData.append("dry_run", "1");
                    }
                }
            }
            
            function performImportRequest(formData, callback) {
                fetch("../../backend/api.php", {
                    method: "POST",
                    body: formData,
                    credentials: "include"
                })
                .then(response => response.json())
                .then(callback)
                .catch(error => {
                    hideProgressModal();
                    showImportMessage("Network error: " + error.message, "error");
                });
            }
            
            function showPreviewModal(data) {
                previewData = data;
                
                const modal = document.getElementById("previewModal");
                const content = modal.querySelector(".modal-body");
                
                if (content) {
                    content.innerHTML = generatePreviewContent(data);
                }
                
                if (window.previewModal && window.previewModal.show) {
                    window.previewModal.show();
                }
            }
            
            function generatePreviewContent(data) {
                let html = "<div class=\"preview-summary\">";
                
                if (data.summary) {
                    html += "<h4>Import Summary</h4>";
                    html += "<div class=\"summary-stats\">";
                    html += "<div class=\"stat-item\"><strong>Total Events:</strong> " + (data.summary.total || 0) + "</div>";
                    html += "<div class=\"stat-item\"><strong>Valid Events:</strong> " + (data.summary.valid || 0) + "</div>";
                    html += "<div class=\"stat-item\"><strong>Errors:</strong> " + (data.summary.errors || 0) + "</div>";
                    html += "</div>";
                }
                
                if (data.events && data.events.length > 0) {
                    html += "<h4>Events to Import</h4>";
                    html += "<div class=\"events-preview\">";
                    
                    data.events.slice(0, 10).forEach(function(event) {
                        html += "<div class=\"event-preview-item\">";
                        html += "<strong>" + escapeHtml(event.title || "Untitled") + "</strong><br>";
                        html += "Start: " + (event.start || "Unknown") + "<br>";
                        if (event.end) {
                            html += "End: " + event.end + "<br>";
                        }
                        if (event.user_name) {
                            html += "User: " + escapeHtml(event.user_name);
                        }
                        html += "</div>";
                    });
                    
                    if (data.events.length > 10) {
                        html += "<p>... and " + (data.events.length - 10) + " more events</p>";
                    }
                    
                    html += "</div>";
                }
                
                if (data.errors && data.errors.length > 0) {
                    html += "<h4>Validation Errors</h4>";
                    html += "<div class=\"errors-preview\">";
                    
                    data.errors.slice(0, 5).forEach(function(error) {
                        html += "<div class=\"error-item\">" + escapeHtml(error) + "</div>";
                    });
                    
                    if (data.errors.length > 5) {
                        html += "<p>... and " + (data.errors.length - 5) + " more errors</p>";
                    }
                    
                    html += "</div>";
                }
                
                html += "</div>";
                
                return html;
            }
            
            function proceedWithImport() {
                if (window.previewModal && window.previewModal.hide) {
                    window.previewModal.hide();
                }
                
                // Proceed with actual import
                importEvents();
            }
            
            function showProgressModal(message) {
                const modal = document.getElementById("progressModal");
                const messageEl = modal ? modal.querySelector("#progressMessage") : null;
                
                if (messageEl) {
                    messageEl.textContent = message;
                }
                
                if (window.progressModal && window.progressModal.show) {
                    window.progressModal.show();
                }
            }
            
            function hideProgressModal() {
                if (window.progressModal && window.progressModal.hide) {
                    window.progressModal.hide();
                }
            }
            
            function showImportResults(data) {
                const resultsSection = document.getElementById("resultsSection");
                const resultsTitle = document.getElementById("resultsTitle");
                const resultsBody = document.getElementById("resultsBody");
                
                if (resultsSection && resultsTitle && resultsBody) {
                    resultsTitle.textContent = "Import Results";
                    
                    let html = "<div class=\"import-results\">";
                    
                    if (data.summary) {
                        html += "<div class=\"results-summary\">";
                        html += "<h4>Summary</h4>";
                        html += "<div class=\"summary-grid\">";
                        html += "<div class=\"summary-item success\"><span class=\"number\">" + (data.summary.imported || 0) + "</span><span class=\"label\">Events Imported</span></div>";
                        html += "<div class=\"summary-item warning\"><span class=\"number\">" + (data.summary.skipped || 0) + "</span><span class=\"label\">Events Skipped</span></div>";
                        html += "<div class=\"summary-item error\"><span class=\"number\">" + (data.summary.errors || 0) + "</span><span class=\"label\">Errors</span></div>";
                        html += "</div>";
                        html += "</div>";
                    }
                    
                    if (data.imported && data.imported.length > 0) {
                        html += "<div class=\"imported-events\">";
                        html += "<h4>Successfully Imported Events</h4>";
                        data.imported.forEach(function(event) {
                            html += "<div class=\"imported-event\">";
                            html += "<strong>" + escapeHtml(event.title || "Untitled") + "</strong> - ";
                            html += escapeHtml(event.start || "Unknown start time");
                            html += "</div>";
                        });
                        html += "</div>";
                    }
                    
                    if (data.errors && data.errors.length > 0) {
                        html += "<div class=\"import-errors\">";
                        html += "<h4>Import Errors</h4>";
                        data.errors.forEach(function(error) {
                            html += "<div class=\"error-item\">" + escapeHtml(error) + "</div>";
                        });
                        html += "</div>";
                    }
                    
                    html += "</div>";
                    
                    resultsBody.innerHTML = html;
                    resultsSection.style.display = "block";
                    
                    // Scroll to results
                    resultsSection.scrollIntoView({ behavior: "smooth" });
                }
                
                // Clear the form for next import
                selectedFile = null;
                clearFileInfo();
                disableImportButtons();
                
                if (window.importFileUpload && window.importFileUpload.clear) {
                    window.importFileUpload.clear();
                }
            }
            
            function showImportMessage(message, type) {
                const messagesArea = document.getElementById("importMessages");
                
                if (messagesArea) {
                    const messageEl = document.createElement("div");
                    messageEl.className = "import-message " + type;
                    messageEl.textContent = message;
                    
                    messagesArea.appendChild(messageEl);
                    
                    // Auto-remove after 5 seconds
                    setTimeout(function() {
                        if (messageEl.parentNode) {
                            messageEl.parentNode.removeChild(messageEl);
                        }
                    }, 5000);
                }
            }
            
            function formatFileSize(bytes) {
                if (bytes === 0) return "0 Bytes";
                const k = 1024;
                const sizes = ["Bytes", "KB", "MB", "GB"];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
            }
            
            function escapeHtml(text) {
                const div = document.createElement("div");
                div.textContent = text;
                return div.innerHTML;
            }
        ');
    }
    
    /**
     * Format file size for display
     */
    private function formatFileSize($bytes) {
        if ($bytes === 0) return '0 Bytes';
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    
    /**
     * Render the complete import form
     */
    public function render() {
        parent::render();
        
        // Render modals
        $this->previewModal->render();
        $this->progressModal->setContent('
            <div class="progress-container">
                <div class="progress-spinner">
                    <div class="spinner"></div>
                </div>
                <p id="progressMessage">Please wait while we process your file...</p>
            </div>
        ');
        $this->progressModal->render();
    }
    
    /**
     * Render with action buttons
     */
    public function renderWithActions() {
        $this->render();
        
        ?>
        <div class="import-actions">
            <button type="button" id="validateBtn" class="btn btn-outline" disabled>
                🔍 Validate File
            </button>
            <button type="button" id="previewBtn" class="btn btn-outline" disabled>
                👁️ Preview Events
            </button>
            <button type="button" id="importBtn" class="btn btn-primary" disabled>
                📥 Import Events
            </button>
        </div>
        
        <div id="importMessages" class="import-messages">
            <!-- Messages will be displayed here -->
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Attach action button handlers
            const validateBtn = document.getElementById('validateBtn');
            const previewBtn = document.getElementById('previewBtn');
            const importBtn = document.getElementById('importBtn');
            
            if (validateBtn) validateBtn.addEventListener('click', validateFile);
            if (previewBtn) previewBtn.addEventListener('click', previewImport);
            if (importBtn) importBtn.addEventListener('click', importEvents);
        });
        </script>
        <?php
    }
    
    /**
     * Create an import form with default configuration
     */
    public static function createImportForm($config = []) {
        return new self($config);
    }
    
    /**
     * Quick render method for import forms
     */
    public static function renderImportForm($config = []) {
        $form = new self($config);
        $form->renderWithActions();
    }
}