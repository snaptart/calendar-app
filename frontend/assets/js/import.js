// Complete Import Page JavaScript - Ice Time Management
// Location: frontend/assets/js/import-page.js
// 
// Complete import functionality (requires core.js to be loaded first)

(function() {
    'use strict';
    
    // Check if core utilities are available
    if (!window.IceTimeApp) {
        console.error('Core utilities not loaded. Please ensure core.js is loaded first.');
        return;
    }
    
    const { EventBus, Config, Utils, APIClient, AuthGuard, UIManager } = window.IceTimeApp;
    
    // =============================================================================
    // IMPORT UTILITIES
    // =============================================================================
    
    const ImportUtils = {
        detectFileFormat(filename) {
            const ext = Utils.getFileExtension(filename);
            if (['.json'].includes(ext)) return 'json';
            if (['.csv', '.txt'].includes(ext)) return 'csv';
            if (['.ics', '.ical'].includes(ext)) return 'ics';
            return 'unknown';
        },
        
        addAnimationStyles() {
            if (!document.getElementById('import-animations')) {
                const style = document.createElement('style');
                style.id = 'import-animations';
                style.textContent = `
                    @keyframes slideInRight {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    @keyframes slideOutRight {
                        from { transform: translateX(0); opacity: 1; }
                        to { transform: translateX(100%); opacity: 0; }
                    }
                    .slide-up {
                        animation: slideUp 0.3s ease-out;
                    }
                    @keyframes slideUp {
                        from { transform: translateY(20px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                    .fade-in {
                        animation: fadeIn 0.3s ease-out;
                    }
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                `;
                document.head.appendChild(style);
            }
        },
        
        showNotification(message, type = 'info', duration = 5000) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
                padding: 16px 20px;
                border-radius: 8px;
                font-size: 0.875rem;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                animation: slideInRight 0.3s ease-out;
            `;
            
            // Set colors based on type
            const colors = {
                success: { bg: '#c6f6d5', text: '#22543d', border: '#68d391' },
                error: { bg: '#fed7d7', text: '#742a2a', border: '#fc8181' },
                warning: { bg: '#faf089', text: '#744210', border: '#f6e05e' },
                info: { bg: '#bee3f8', text: '#2a4365', border: '#90cdf4' }
            };
            
            const color = colors[type] || colors.info;
            notification.style.backgroundColor = color.bg;
            notification.style.color = color.text;
            notification.style.border = `1px solid ${color.border}`;
            
            notification.innerHTML = `
                ${message}
                <button style="float: right; background: none; border: none; font-size: 18px; cursor: pointer; color: inherit; margin-left: 12px;">&times;</button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-out forwards';
                setTimeout(() => notification.remove(), 300);
            }, duration);
            
            // Manual close
            notification.querySelector('button').onclick = () => {
                notification.style.animation = 'slideOutRight 0.3s ease-out forwards';
                setTimeout(() => notification.remove(), 300);
            };
        }
    };
    
    // =============================================================================
    // FILE MANAGER
    // =============================================================================
    
    const FileManager = (() => {
        let selectedFile = null;
        let validationResult = null;
        
        const initializeFileHandling = () => {
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('importFile');
            const clearBtn = document.getElementById('clearFile');
            
            if (!dropZone || !fileInput) {
                console.warn('File handling elements not found');
                return;
            }
            
            // File input change
            fileInput.addEventListener('change', handleFileSelect);
            
            // Drag and drop
            dropZone.addEventListener('dragover', handleDragOver);
            dropZone.addEventListener('dragleave', handleDragLeave);
            dropZone.addEventListener('drop', handleDrop);
            dropZone.addEventListener('click', () => fileInput.click());
            
            // Clear file
            clearBtn?.addEventListener('click', clearFile);
        };
        
        const handleFileSelect = (event) => {
            const file = event.target.files[0];
            if (file) {
                processFile(file);
            }
        };
        
        const handleDragOver = (event) => {
            event.preventDefault();
            event.currentTarget.classList.add('drag-over');
        };
        
        const handleDragLeave = (event) => {
            event.currentTarget.classList.remove('drag-over');
        };
        
        const handleDrop = (event) => {
            event.preventDefault();
            event.currentTarget.classList.remove('drag-over');
            
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                processFile(files[0]);
            }
        };
        
        const processFile = (file) => {
            // Validate file
            const validation = validateFileBasic(file);
            
            if (!validation.valid) {
                ImportUtils.showNotification(validation.error, 'error');
                return;
            }
            
            selectedFile = file;
            displayFileInfo(file);
            updateButtonStates(true);
            
            EventBus.emit('file:selected', { file });
        };
        
        const validateFileBasic = (file) => {
            // Check file size
            if (file.size > Config.import.maxFileSize) {
                return {
                    valid: false,
                    error: `File size (${Utils.formatFileSize(file.size)}) exceeds maximum allowed size of ${Utils.formatFileSize(Config.import.maxFileSize)}`
                };
            }
            
            // Check file type
            const extension = Utils.getFileExtension(file.name);
            if (!Config.import.allowedFormats.includes(extension)) {
                return {
                    valid: false,
                    error: `File type "${extension}" is not supported. Allowed types: ${Config.import.allowedFormats.join(', ')}`
                };
            }
            
            return { valid: true };
        };
        
        const displayFileInfo = (file) => {
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            
            if (fileInfo && fileName && fileSize) {
                fileName.textContent = file.name;
                fileSize.textContent = Utils.formatFileSize(file.size);
                fileInfo.style.display = 'block';
                fileInfo.classList.add('fade-in');
            }
            
            // Update file icon based on type
            const fileIcon = document.querySelector('.file-icon');
            if (fileIcon) {
                const format = ImportUtils.detectFileFormat(file.name);
                const formatIcons = {
                    'json': '📄',
                    'csv': '📊',
                    'ics': '📅'
                };
                fileIcon.textContent = formatIcons[format] || '📄';
            }
        };
        
        const clearFile = () => {
            selectedFile = null;
            validationResult = null;
            
            const fileInput = document.getElementById('importFile');
            const fileInfo = document.getElementById('fileInfo');
            
            if (fileInput) fileInput.value = '';
            if (fileInfo) fileInfo.style.display = 'none';
            
            updateButtonStates(false);
            clearResults();
            
            EventBus.emit('file:cleared');
        };
        
        const updateButtonStates = (hasFile) => {
            const buttons = ['validateBtn', 'previewBtn', 'importBtn'];
            buttons.forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.disabled = !hasFile;
                }
            });
        };
        
        const clearResults = () => {
            const resultsSection = document.getElementById('resultsSection');
            if (resultsSection) {
                resultsSection.style.display = 'none';
            }
        };
        
        const getSelectedFile = () => selectedFile;
        const getValidationResult = () => validationResult;
        const setValidationResult = (result) => { validationResult = result; };
        
        return {
            initializeFileHandling,
            getSelectedFile,
            getValidationResult,
            setValidationResult,
            clearFile
        };
    })();
    
    // =============================================================================
    // VALIDATION MANAGER
    // =============================================================================
    
    const ValidationManager = (() => {
        const validateFile = async () => {
            const file = FileManager.getSelectedFile();
            if (!file) {
                ImportUtils.showNotification('Please select a file first', 'warning');
                return;
            }
            
            const validateBtn = document.getElementById('validateBtn');
            UIManager.setButtonLoading(validateBtn, true);
            
            try {
                showProgressModal('Validating File', 'Please wait while we validate your file...');
                
                const result = await APIClient.validateImportFile(file);
                FileManager.setValidationResult(result);
                
                displayValidationResults(result);
                
                if (result.valid) {
                    ImportUtils.showNotification(`File is valid! Found ${result.event_count || 0} events to import.`, 'success');
                    
                    // Enable import button if validation passed
                    const importBtn = document.getElementById('importBtn');
                    if (importBtn) importBtn.disabled = false;
                } else {
                    ImportUtils.showNotification('File validation failed. Please check the results below.', 'error');
                }
                
            } catch (error) {
                console.error('Validation error:', error);
                ImportUtils.showNotification(`Validation failed: ${error.message}`, 'error');
                displayValidationError(error.message);
            } finally {
                UIManager.setButtonLoading(validateBtn, false);
                hideProgressModal();
            }
        };
        
        const displayValidationResults = (result) => {
            const resultsSection = document.getElementById('resultsSection');
            const resultsTitle = document.getElementById('resultsTitle');
            const resultsBody = document.getElementById('resultsBody');
            
            if (!resultsSection || !resultsTitle || !resultsBody) return;
            
            resultsTitle.textContent = 'Validation Results';
            
            const cardClass = result.valid ? 'success' : 'error';
            const icon = result.valid ? '✅' : '❌';
            const title = result.valid ? 'File Valid' : 'Validation Failed';
            const summary = result.valid 
                ? `Your file is valid and ready for import. ${result.event_count || 0} events found.`
                : `File validation failed. Please fix the issues below and try again.`;
            
            resultsBody.innerHTML = `
                <div class="result-card ${cardClass}">
                    <div class="result-header">
                        <span class="result-icon">${icon}</span>
                        <h4 class="result-title">${title}</h4>
                    </div>
                    <p class="result-summary">${summary}</p>
                    
                    <div class="result-stats">
                        <div class="stat-item">
                            <span class="stat-number">${result.event_count || 0}</span>
                            <span class="stat-label">Events Found</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">${result.valid_count || 0}</span>
                            <span class="stat-label">Valid Events</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">${result.error_count || 0}</span>
                            <span class="stat-label">Errors</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">${result.warning_count || 0}</span>
                            <span class="stat-label">Warnings</span>
                        </div>
                    </div>
                    
                    ${result.errors && result.errors.length > 0 ? `
                        <div class="result-details">
                            <h5>Issues Found:</h5>
                            ${result.errors.map(error => `<div class="detail-item error">${error}</div>`).join('')}
                        </div>
                    ` : ''}
                    
                    ${result.warnings && result.warnings.length > 0 ? `
                        <div class="result-details">
                            <h5>Warnings:</h5>
                            ${result.warnings.map(warning => `<div class="detail-item warning">${warning}</div>`).join('')}
                        </div>
                    ` : ''}
                </div>
            `;
            
            resultsSection.style.display = 'block';
            resultsSection.classList.add('slide-up');
        };
        
        const displayValidationError = (errorMessage) => {
            const resultsSection = document.getElementById('resultsSection');
            const resultsTitle = document.getElementById('resultsTitle');
            const resultsBody = document.getElementById('resultsBody');
            
            if (!resultsSection || !resultsTitle || !resultsBody) return;
            
            resultsTitle.textContent = 'Validation Error';
            
            resultsBody.innerHTML = `
                <div class="result-card error">
                    <div class="result-header">
                        <span class="result-icon">❌</span>
                        <h4 class="result-title">Validation Failed</h4>
                    </div>
                    <p class="result-summary">An error occurred while validating your file.</p>
                    <div class="result-details">
                        <div class="detail-item error">${errorMessage}</div>
                    </div>
                </div>
            `;
            
            resultsSection.style.display = 'block';
            resultsSection.classList.add('slide-up');
        };
        
        const showProgressModal = (title, message) => {
            const modal = document.getElementById('progressModal');
            const titleEl = document.getElementById('progressTitle');
            const messageEl = document.getElementById('progressMessage');
            
            if (modal && titleEl && messageEl) {
                titleEl.textContent = title;
                messageEl.textContent = message;
                modal.style.display = 'block';
            }
        };
        
        const hideProgressModal = () => {
            const modal = document.getElementById('progressModal');
            if (modal) {
                modal.style.display = 'none';
            }
        };
        
        return {
            validateFile
        };
    })();
    
    // =============================================================================
    // PREVIEW MANAGER
    // =============================================================================
    
    const PreviewManager = (() => {
        let previewData = null;
        
        const previewFile = async () => {
            const file = FileManager.getSelectedFile();
            if (!file) {
                ImportUtils.showNotification('Please select a file first', 'warning');
                return;
            }
            
            const previewBtn = document.getElementById('previewBtn');
            UIManager.setButtonLoading(previewBtn, true);
            
            try {
                showProgressModal('Generating Preview', 'Please wait while we analyze your file...');
                
                const result = await APIClient.previewImportFile(file);
                previewData = result;
                
                if (result.valid && result.detailed_preview) {
                    showPreviewModal(result.detailed_preview);
                    ImportUtils.showNotification(`Preview generated! Found ${result.total_events || result.event_count || 0} events.`, 'success');
                } else if (result.valid && result.preview) {
                    showPreviewModal(result.preview);
                    ImportUtils.showNotification(`Preview generated! Found ${result.event_count || 0} events.`, 'success');
                } else if (result.event_count !== undefined) {
                    displayPreviewResults(result);
                    ImportUtils.showNotification('File analyzed. Check results below for details.', 'info');
                } else {
                    ImportUtils.showNotification('Cannot preview file. Please validate first.', 'warning');
                    displayPreviewError(result.error || 'Unknown error during preview');
                }
                
            } catch (error) {
                console.error('Preview error:', error);
                ImportUtils.showNotification(`Preview failed: ${error.message}`, 'error');
                displayPreviewError(error.message);
            } finally {
                UIManager.setButtonLoading(previewBtn, false);
                hideProgressModal();
            }
        };
        
        const showPreviewModal = (previewEvents) => {
            const modal = document.getElementById('previewModal');
            const content = document.getElementById('previewContent');
            
            if (!modal || !content) {
                console.error('Preview modal elements not found');
                return;
            }
            
            let validEvents = [];
            let invalidEvents = [];
            
            if (Array.isArray(previewEvents)) {
                validEvents = previewEvents.filter(event => event.valid === true);
                invalidEvents = previewEvents.filter(event => event.valid === false);
            } else {
                console.warn('Preview events is not an array:', previewEvents);
                ImportUtils.showNotification('Preview data format is unexpected', 'warning');
                return;
            }
            
            content.innerHTML = `
                <div class="preview-summary">
                    <h4>Preview Summary</h4>
                    <div class="result-stats">
                        <div class="stat-item">
                            <span class="stat-number">${previewEvents.length}</span>
                            <span class="stat-label">Total Events</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">${validEvents.length}</span>
                            <span class="stat-label">Valid Events</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">${invalidEvents.length}</span>
                            <span class="stat-label">Invalid Events</span>
                        </div>
                    </div>
                </div>
                
                ${validEvents.length > 0 ? `
                    <h4>Valid Events (will be imported):</h4>
                    <div class="preview-table-container">
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${validEvents.map((event, index) => `
                                    <tr class="preview-row valid">
                                        <td>${event.index || index + 1}</td>
                                        <td>${event.title || 'N/A'}</td>
                                        <td>${Utils.formatDateTime(event.start)}</td>
                                        <td>${Utils.formatDateTime(event.end)}</td>
                                        <td>User ID: ${event.user_id || 'Unknown'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                ` : '<p>No valid events found in this file.</p>'}
                
                ${invalidEvents.length > 0 ? `
                    <h4>Invalid Events (will be skipped):</h4>
                    <div class="preview-table-container">
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Error</th>
                                    <th>Raw Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${invalidEvents.map((event, index) => `
                                    <tr class="preview-row invalid">
                                        <td>${event.index || index + 1}</td>
                                        <td class="error-cell">${event.error || 'Unknown error'}</td>
                                        <td class="raw-data-cell">
                                            <pre>${JSON.stringify(event.raw_data || {}, null, 2)}</pre>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                ` : ''}
            `;
            
            modal.style.display = 'block';
        };
        
        const displayPreviewResults = (result) => {
            const resultsSection = document.getElementById('resultsSection');
            const resultsTitle = document.getElementById('resultsTitle');
            const resultsBody = document.getElementById('resultsBody');
            
            if (!resultsSection || !resultsTitle || !resultsBody) return;
            
            resultsTitle.textContent = 'Preview Results';
            
            const cardClass = result.valid ? 'success' : 'warning';
            const icon = result.valid ? '👁️' : '⚠️';
            const title = 'File Preview';
            const summary = result.valid 
                ? `File contains ${result.event_count || 0} events. Use the preview button to see details.`
                : `File has issues that need to be resolved before import.`;
            
            resultsBody.innerHTML = `
                <div class="result-card ${cardClass}">
                    <div class="result-header">
                        <span class="result-icon">${icon}</span>
                        <h4 class="result-title">${title}</h4>
                    </div>
                    <p class="result-summary">${summary}</p>
                    
                    <div class="result-stats">
                        <div class="stat-item">
                            <span class="stat-number">${result.event_count || 0}</span>
                            <span class="stat-label">Events Found</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">${result.format || 'Unknown'}</span>
                            <span class="stat-label">File Format</span>
                        </div>
                    </div>
                    
                    ${result.error ? `
                        <div class="result-details">
                            <h5>Issues:</h5>
                            <div class="detail-item error">${result.error}</div>
                        </div>
                    ` : ''}
                </div>
            `;
            
            resultsSection.style.display = 'block';
            resultsSection.classList.add('slide-up');
        };
        
        const displayPreviewError = (errorMessage) => {
            const resultsSection = document.getElementById('resultsSection');
            const resultsTitle = document.getElementById('resultsTitle');
            const resultsBody = document.getElementById('resultsBody');
            
            if (!resultsSection || !resultsTitle || !resultsBody) return;
            
            resultsTitle.textContent = 'Preview Error';
            
            resultsBody.innerHTML = `
                <div class="result-card error">
                    <div class="result-header">
                        <span class="result-icon">❌</span>
                        <h4 class="result-title">Preview Failed</h4>
                    </div>
                    <p class="result-summary">An error occurred while generating the preview.</p>
                    <div class="result-details">
                        <div class="detail-item error">${errorMessage}</div>
                    </div>
                </div>
            `;
            
            resultsSection.style.display = 'block';
            resultsSection.classList.add('slide-up');
        };
        
        const closePreviewModal = () => {
            const modal = document.getElementById('previewModal');
            if (modal) {
                modal.style.display = 'none';
            }
        };
        
        const proceedWithImport = () => {
            closePreviewModal();
            ImportManager.importEvents();
        };
        
        const showProgressModal = (title, message) => {
            const modal = document.getElementById('progressModal');
            const titleEl = document.getElementById('progressTitle');
            const messageEl = document.getElementById('progressMessage');
            
            if (modal && titleEl && messageEl) {
                titleEl.textContent = title;
                messageEl.textContent = message;
                modal.style.display = 'block';
            }
        };
        
        const hideProgressModal = () => {
            const modal = document.getElementById('progressModal');
            if (modal) {
                modal.style.display = 'none';
            }
        };
        
        return {
            previewFile,
            closePreviewModal,
            proceedWithImport
        };
    })();
    
    // =============================================================================
    // IMPORT MANAGER (COMPLETE)
    // =============================================================================
    
    const ImportManager = (() => {
        const importEvents = async () => {
            const file = FileManager.getSelectedFile();
            if (!file) {
                ImportUtils.showNotification('Please select a file first', 'warning');
                return;
            }
            
            if (!confirm(`Are you sure you want to import events from "${file.name}"?`)) {
                return;
            }
            
            const importBtn = document.getElementById('importBtn');
            UIManager.setButtonLoading(importBtn, true);
            
            try {
                showProgressModal('Importing Events', 'Please wait while we import your events...');
                
                const result = await APIClient.importEvents(file);
                
                displayImportResults(result);
                
                if (result.imported_count > 0) {
                    ImportUtils.showNotification(
                        `Successfully imported ${result.imported_count} events!`, 
                        'success'
                    );
                    
                    // Clear the form after successful import
                    setTimeout(() => {
                        FileManager.clearFile();
                    }, 3000);
                } else {
                    ImportUtils.showNotification('No events were imported. Please check the results.', 'warning');
                }
                
            } catch (error) {
                console.error('Import error:', error);
                ImportUtils.showNotification(`Import failed: ${error.message}`, 'error');
                displayImportError(error.message);
            } finally {
                UIManager.setButtonLoading(importBtn, false);
                hideProgressModal();
            }
        };
        
        const displayImportResults = (result) => {
            const resultsSection = document.getElementById('resultsSection');
            const resultsTitle = document.getElementById('resultsTitle');
            const resultsBody = document.getElementById('resultsBody');
            
            if (!resultsSection || !resultsTitle || !resultsBody) return;
            
            resultsTitle.textContent = 'Import Results';
            
            const cardClass = result.imported_count > 0 ? 'success' : 'warning';
            const icon = result.imported_count > 0 ? '✅' : '⚠️';
            const title = result.imported_count > 0 ? 'Import Successful' : 'Import Completed with Issues';
            const summary = result.imported_count > 0 
                ? `Successfully imported ${result.imported_count} events into the calendar.`
                : 'Import completed but no events were imported. Check details below.';
            
            resultsBody.innerHTML = `
                <div class="result-card ${cardClass}">
                    <div class="result-header">
                        <span class="result-icon">${icon}</span>
                        <h4 class="result-title">${title}</h4>
                    </div>
                    <p class="result-summary">${summary}</p>
                    
                    <div class="result-stats">
                        <div class="stat-item">
                            <span class="stat-number">${result.total_events || result.total_processed || 0}</span>
                            <span class="stat-label">Total Processed</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">${result.imported_count || 0}</span>
                            <span class="stat-label">Successfully Imported</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">${result.error_count || 0}</span>
                            <span class="stat-label">Errors</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">${result.skipped_count || 0}</span>
                            <span class="stat-label">Skipped</span>
                        </div>
                    </div>
                    
                    ${result.imported_events && result.imported_events.length > 0 ? `
                        <div class="result-details">
                            <h5>Successfully Imported Events:</h5>
                            ${result.imported_events.map(event => `
                                <div class="detail-item success">
                                    ${event.title} - ${Utils.formatDateTime(event.start_datetime || event.start)}
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                    
                    ${result.errors && result.errors.length > 0 ? `
                        <div class="result-details">
                            <h5>Import Errors:</h5>
                            ${result.errors.map(error => `<div class="detail-item error">${typeof error === 'string' ? error : error.error || 'Unknown error'}</div>`).join('')}
                        </div>
                    ` : ''}
                    
                    ${result.skipped_events && result.skipped_events.length > 0 ? `
                        <div class="result-details">
                            <h5>Skipped Events:</h5>
                            ${result.skipped_events.map(event => `
                                <div class="detail-item warning">
                                    ${event.title || 'Unnamed Event'} - ${event.reason || 'Unknown reason'}
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            `;
            
            resultsSection.style.display = 'block';
            resultsSection.classList.add('slide-up');
        };
        
        const displayImportError = (errorMessage) => {
            const resultsSection = document.getElementById('resultsSection');
            const resultsTitle = document.getElementById('resultsTitle');
            const resultsBody = document.getElementById('resultsBody');
            
            if (!resultsSection || !resultsTitle || !resultsBody) return;
            
            resultsTitle.textContent = 'Import Error';
            
            resultsBody.innerHTML = `
                <div class="result-card error">
                    <div class="result-header">
                        <span class="result-icon">❌</span>
                        <h4 class="result-title">Import Failed</h4>
                    </div>
                    <p class="result-summary">An error occurred while importing your events.</p>
                    <div class="result-details">
                        <div class="detail-item error">${errorMessage}</div>
                    </div>
                </div>
            `;
            
            resultsSection.style.display = 'block';
            resultsSection.classList.add('slide-up');
        };
        
        const showProgressModal = (title, message) => {
            const modal = document.getElementById('progressModal');
            const titleEl = document.getElementById('progressTitle');
            const messageEl = document.getElementById('progressMessage');
            
            if (modal && titleEl && messageEl) {
                titleEl.textContent = title;
                messageEl.textContent = message;
                modal.style.display = 'block';
            }
        };
        
        const hideProgressModal = () => {
            const modal = document.getElementById('progressModal');
            if (modal) {
                modal.style.display = 'none';
            }
        };
        
        return {
            importEvents
        };
    })();
    
    // =============================================================================
    // FORMAT EXAMPLES MANAGER
    // =============================================================================
    
    const FormatExamplesManager = (() => {
        const initializeFormatTabs = () => {
            const tabs = document.querySelectorAll('.format-tab');
            const examples = document.querySelectorAll('.format-example');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const format = tab.dataset.format;
                    
                    // Update active tab
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    // Update active example
                    examples.forEach(ex => ex.classList.remove('active'));
                    const targetExample = document.getElementById(`${format}Example`);
                    if (targetExample) {
                        targetExample.classList.add('active');
                    }
                });
            });
        };
        
        return {
            initializeFormatTabs
        };
    })();
    
    // =============================================================================
    // BUTTON HANDLERS
    // =============================================================================
    
    const ButtonHandlers = (() => {
        const initializeButtons = () => {
            // Validate button
            const validateBtn = document.getElementById('validateBtn');
            validateBtn?.addEventListener('click', ValidationManager.validateFile);
            
            // Preview button
            const previewBtn = document.getElementById('previewBtn');
            previewBtn?.addEventListener('click', PreviewManager.previewFile);
            
            // Import button
            const importBtn = document.getElementById('importBtn');
            importBtn?.addEventListener('click', ImportManager.importEvents);
        };
        
        return {
            initializeButtons
        };
    })();
    
    // =============================================================================
    // APPLICATION CONTROLLER
    // =============================================================================
    
    const ImportApp = (() => {
        const init = async () => {
            console.log('Initializing Import Page...');
            
            // Add animation styles
            ImportUtils.addAnimationStyles();
            
            // Check authentication first
            const isAuthenticated = await AuthGuard.checkAuthentication();
            
            if (!isAuthenticated) {
                return;
            }
            
            // Initialize UI components
            FileManager.initializeFileHandling();
            FormatExamplesManager.initializeFormatTabs();
            ButtonHandlers.initializeButtons();
            
            // Update connection status
            UIManager.updateConnectionStatus('Ready', 'connected');
            
            console.log('Import Page initialized successfully');
        };
        
        const destroy = () => {
            console.log('Import Page destroyed');
        };
        
        return {
            init,
            destroy
        };
    })();
    
    // =============================================================================
    // MODAL HELPERS (Global Functions)
    // =============================================================================
    
    // Global functions for modal interactions
    window.closePreviewModal = () => {
        PreviewManager.closePreviewModal();
    };
    
    window.proceedWithImport = () => {
        PreviewManager.proceedWithImport();
    };
    
    // Close modals when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal')) {
            const modal = event.target;
            modal.style.display = 'none';
        }
    });
    
    // Close modals with Escape key
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    modal.style.display = 'none';
                }
            });
        }
    });
    
    // =============================================================================
    // INITIALIZATION
    // =============================================================================
    
    // Export to global scope
    window.IceTimeApp.ImportApp = ImportApp;
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            ImportApp.init();
        });
    } else {
        ImportApp.init();
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        ImportApp.destroy();
    });
    
    // Handle browser back/forward navigation
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            console.log('Page loaded from cache, checking auth...');
            AuthGuard.checkAuthentication();
        }
    });
    
})();