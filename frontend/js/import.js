// Enhanced Import Page JavaScript with Full Functionality
// Location: frontend/js/import.js
// 
// Complete import functionality with validation, preview, and import

// =============================================================================
// CORE UTILITIES AND EVENT BUS
// =============================================================================

/**
 * Simple Event Bus for component communication
 */
const EventBus = (() => {
    const events = {};
    
    return {
        on(event, callback) {
            if (!events[event]) events[event] = [];
            events[event].push(callback);
        },
        
        off(event, callback) {
            if (!events[event]) return;
            events[event] = events[event].filter(cb => cb !== callback);
        },
        
        emit(event, data) {
            if (!events[event]) return;
            events[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in event listener for ${event}:`, error);
                }
            });
        }
    };
})();

/**
 * Configuration object for API endpoints and limits
 */
const Config = {
    apiEndpoints: {
        api: '../../backend/api.php',
        import: '../../backend/import.php'
    },
    limits: {
        maxFileSize: 5 * 1024 * 1024, // 5MB
        maxEvents: 20,
        allowedTypes: ['.json', '.csv', '.ics', '.ical', '.txt']
    },
    formats: {
        json: { name: 'JSON', icon: '📄' },
        csv: { name: 'CSV', icon: '📊' },
        ics: { name: 'iCalendar', icon: '📅' }
    }
};

/**
 * Utility functions
 */
const Utils = {
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    getFileExtension(filename) {
        return filename.toLowerCase().substring(filename.lastIndexOf('.'));
    },
    
    detectFileFormat(filename) {
        const ext = this.getFileExtension(filename);
        if (['.json'].includes(ext)) return 'json';
        if (['.csv', '.txt'].includes(ext)) return 'csv';
        if (['.ics', '.ical'].includes(ext)) return 'ics';
        return 'unknown';
    },
    
    formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return dateString;
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
            `;
            document.head.appendChild(style);
        }
    }
};

// =============================================================================
// API CLIENT
// =============================================================================

/**
 * API communication for import operations
 */
const ImportAPI = (() => {
    const makeRequest = async (url, options = {}) => {
        try {
            const response = await fetch(url, {
                credentials: 'include',
                ...options
            });
            
            if (response.status === 401) {
                EventBus.emit('auth:unauthorized');
                throw new Error('Authentication required');
            }
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Import API Request failed:', error);
            throw error;
        }
    };
    
    return {
        // Authentication check
        checkAuth() {
            return makeRequest(`${Config.apiEndpoints.api}?action=check_auth`);
        },
        
        // Validate import file
        validateFile(file) {
            const formData = new FormData();
            formData.append('import_file', file);
            formData.append('action', 'validate');
            
            return makeRequest(Config.apiEndpoints.import, {
                method: 'POST',
                body: formData
            });
        },
        
        // Preview import file
        previewFile(file) {
            const formData = new FormData();
            formData.append('import_file', file);
            formData.append('action', 'preview');
            
            return makeRequest(Config.apiEndpoints.import, {
                method: 'POST',
                body: formData
            });
        },
        
        // Import events from file
        importEvents(file) {
            const formData = new FormData();
            formData.append('import_file', file);
            formData.append('action', 'import');
            
            return makeRequest(Config.apiEndpoints.import, {
                method: 'POST',
                body: formData
            });
        },
        
        // Get supported formats
        getSupportedFormats() {
            return makeRequest(`${Config.apiEndpoints.import}?action=formats`, {
                method: 'POST',
                body: new FormData() // Empty form data for POST requirement
            });
        }
    };
})();

// =============================================================================
// AUTHENTICATION GUARD
// =============================================================================

/**
 * Handles authentication checks and redirects
 */
const AuthGuard = (() => {
    let currentUser = null;
    
    const checkAuthentication = async () => {
        try {
            const response = await ImportAPI.checkAuth();
            
            if (response.authenticated) {
                currentUser = response.user;
                EventBus.emit('auth:authenticated', { user: response.user });
                return true;
            } else {
                redirectToLogin();
                return false;
            }
        } catch (error) {
            console.error('Authentication check failed:', error);
            redirectToLogin();
            return false;
        }
    };
    
    const redirectToLogin = () => {
        window.location.href = './login.html';
    };
    
    const getCurrentUser = () => currentUser;
    
    const logout = async () => {
        try {
            await fetch(Config.apiEndpoints.api, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ action: 'logout' })
            });
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            redirectToLogin();
        }
    };
    
    // Event listeners
    EventBus.on('auth:unauthorized', redirectToLogin);
    
    return {
        checkAuthentication,
        getCurrentUser,
        logout
    };
})();

// =============================================================================
// UI MANAGER
// =============================================================================

/**
 * Manages UI updates and visual feedback
 */
const UIManager = (() => {
    const updateConnectionStatus = (message, className = '') => {
        const statusEl = document.getElementById('connectionStatus');
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.className = `status ${className}`;
        }
    };
    
    const updateUserStatus = (message, className = '') => {
        const statusEl = document.getElementById('userStatus');
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.className = `status ${className}`;
        }
    };
    
    const setupAuthenticatedUI = (user) => {
        const userNameInput = document.getElementById('userName');
        if (userNameInput) {
            userNameInput.value = user.name;
            userNameInput.disabled = true;
            userNameInput.style.backgroundColor = '#f7fafc';
            userNameInput.style.color = '#2d3748';
        }
        
        addLogoutButton();
        updateUserStatus(`Logged in as: ${user.name}`, 'user-set');
    };
    
    const addLogoutButton = () => {
        const userSection = document.querySelector('.user-section');
        if (userSection && !document.getElementById('logoutBtn')) {
            const logoutBtn = document.createElement('button');
            logoutBtn.id = 'logoutBtn';
            logoutBtn.className = 'btn btn-small btn-outline';
            logoutBtn.textContent = 'Logout';
            logoutBtn.style.marginLeft = '8px';
            
            logoutBtn.addEventListener('click', () => {
                if (confirm('Are you sure you want to logout?')) {
                    AuthGuard.logout();
                }
            });
            
            userSection.appendChild(logoutBtn);
        }
    };
    
    const setButtonLoading = (button, isLoading) => {
        if (isLoading) {
            button.disabled = true;
            button.classList.add('loading');
        } else {
            button.disabled = false;
            button.classList.remove('loading');
        }
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
    
    // Event listeners
    EventBus.on('auth:authenticated', ({ user }) => {
        setupAuthenticatedUI(user);
    });
    
    return {
        updateConnectionStatus,
        updateUserStatus,
        setupAuthenticatedUI,
        setButtonLoading,
        showProgressModal,
        hideProgressModal
    };
})();

// =============================================================================
// FILE MANAGER
// =============================================================================

/**
 * Manages file selection, validation, and display
 */
const FileManager = (() => {
    let selectedFile = null;
    let validationResult = null;
    
    const initializeFileHandling = () => {
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('importFile');
        const clearBtn = document.getElementById('clearFile');
        
        // File input change
        fileInput.addEventListener('change', handleFileSelect);
        
        // Drag and drop
        dropZone.addEventListener('dragover', handleDragOver);
        dropZone.addEventListener('dragleave', handleDragLeave);
        dropZone.addEventListener('drop', handleDrop);
        dropZone.addEventListener('click', () => fileInput.click());
        
        // Clear file
        clearBtn.addEventListener('click', clearFile);
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
            Utils.showNotification(validation.error, 'error');
            return;
        }
        
        selectedFile = file;
        displayFileInfo(file);
        updateButtonStates(true);
        
        EventBus.emit('file:selected', { file });
    };
    
    const validateFileBasic = (file) => {
        // Check file size
        if (file.size > Config.limits.maxFileSize) {
            return {
                valid: false,
                error: `File size (${Utils.formatFileSize(file.size)}) exceeds maximum allowed size of ${Utils.formatFileSize(Config.limits.maxFileSize)}`
            };
        }
        
        // Check file type
        const extension = Utils.getFileExtension(file.name);
        if (!Config.limits.allowedTypes.includes(extension)) {
            return {
                valid: false,
                error: `File type "${extension}" is not supported. Allowed types: ${Config.limits.allowedTypes.join(', ')}`
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
            const format = Utils.detectFileFormat(file.name);
            const formatInfo = Config.formats[format];
            if (formatInfo) {
                fileIcon.textContent = formatInfo.icon;
            }
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

/**
 * Manages file validation and display of results
 */
const ValidationManager = (() => {
    const validateFile = async () => {
        const file = FileManager.getSelectedFile();
        if (!file) {
            Utils.showNotification('Please select a file first', 'warning');
            return;
        }
        
        const validateBtn = document.getElementById('validateBtn');
        UIManager.setButtonLoading(validateBtn, true);
        
        try {
            UIManager.showProgressModal('Validating File', 'Please wait while we validate your file...');
            
            const result = await ImportAPI.validateFile(file);
            FileManager.setValidationResult(result);
            
            displayValidationResults(result);
            
            if (result.valid) {
                Utils.showNotification(`File is valid! Found ${result.event_count} events to import.`, 'success');
                
                // Enable import button if validation passed
                const importBtn = document.getElementById('importBtn');
                if (importBtn) importBtn.disabled = false;
            } else {
                Utils.showNotification('File validation failed. Please check the results below.', 'error');
            }
            
        } catch (error) {
            console.error('Validation error:', error);
            Utils.showNotification(`Validation failed: ${error.message}`, 'error');
            displayValidationError(error.message);
        } finally {
            UIManager.setButtonLoading(validateBtn, false);
            UIManager.hideProgressModal();
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
            ? `Your file is valid and ready for import. ${result.event_count} events found.`
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
    
    return {
        validateFile
    };
})();

// =============================================================================
// PREVIEW MANAGER
// =============================================================================

/**
 * Manages event preview functionality
 */
const PreviewManager = (() => {
    let previewData = null;
    
    const previewFile = async () => {
        const file = FileManager.getSelectedFile();
        if (!file) {
            Utils.showNotification('Please select a file first', 'warning');
            return;
        }
        
        const previewBtn = document.getElementById('previewBtn');
        UIManager.setButtonLoading(previewBtn, true);
        
        try {
            UIManager.showProgressModal('Generating Preview', 'Please wait while we analyze your file...');
            
            const result = await ImportAPI.previewFile(file);
            previewData = result;
            
            if (result.valid && result.detailed_preview) {
                showPreviewModal(result.detailed_preview);
            } else {
                Utils.showNotification('Cannot preview file. Please validate first.', 'warning');
                // Show validation results instead
                ValidationManager.displayValidationResults(result);
            }
            
        } catch (error) {
            console.error('Preview error:', error);
            Utils.showNotification(`Preview failed: ${error.message}`, 'error');
        } finally {
            UIManager.setButtonLoading(previewBtn, false);
            UIManager.hideProgressModal();
        }
    };
    
    const showPreviewModal = (previewEvents) => {
        const modal = document.getElementById('previewModal');
        const content = document.getElementById('previewContent');
        
        if (!modal || !content) return;
        
        // Create preview table
        const validEvents = previewEvents.filter(event => event.valid);
        const invalidEvents = previewEvents.filter(event => !event.valid);
        
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
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Title</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${validEvents.map(event => `
                            <tr class="preview-row valid">
                                <td><span class="preview-status valid">Valid</span></td>
                                <td>${event.title || 'N/A'}</td>
                                <td>${Utils.formatDateTime(event.start)}</td>
                                <td>${Utils.formatDateTime(event.end)}</td>
                                <td>User ID: ${event.user_id}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            ` : ''}
            
            ${invalidEvents.length > 0 ? `
                <h4>Invalid Events (will be skipped):</h4>
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Error</th>
                            <th>Raw Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${invalidEvents.map(event => `
                            <tr class="preview-row invalid">
                                <td><span class="preview-status invalid">Invalid</span></td>
                                <td>${event.error || 'Unknown error'}</td>
                                <td><pre>${JSON.stringify(event.raw_data, null, 2)}</pre></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            ` : ''}
        `;
        
        modal.style.display = 'block';
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
    
    // Make functions global for onclick handlers
    window.closePreviewModal = closePreviewModal;
    window.proceedWithImport = proceedWithImport;
    
    return {
        previewFile,
        closePreviewModal,
        proceedWithImport
    };
})();

// =============================================================================
// IMPORT MANAGER
// =============================================================================

/**
 * Manages the actual import process
 */
const ImportManager = (() => {
    const importEvents = async () => {
        const file = FileManager.getSelectedFile();
        if (!file) {
            Utils.showNotification('Please select a file first', 'warning');
            return;
        }
        
        // Check if file has been validated
        const validationResult = FileManager.getValidationResult();
        if (!validationResult || !validationResult.valid) {
            Utils.showNotification('Please validate the file first', 'warning');
            return;
        }
        
        const importBtn = document.getElementById('importBtn');
        UIManager.setButtonLoading(importBtn, true);
        
        try {
            UIManager.showProgressModal('Importing Events', 'Please wait while we import your events...');
            
            const result = await ImportAPI.importEvents(file);
            
            displayImportResults(result);
            
            if (result.imported_count > 0) {
                Utils.showNotification(
                    `Successfully imported ${result.imported_count} events!`, 
                    'success'
                );
                
                // Clear the form after successful import
                setTimeout(() => {
                    FileManager.clearFile();
                }, 3000);
            } else {
                Utils.showNotification('No events were imported. Please check the results.', 'warning');
            }
            
        } catch (error) {
            console.error('Import error:', error);
            Utils.showNotification(`Import failed: ${error.message}`, 'error');
            displayImportError(error.message);
        } finally {
            UIManager.setButtonLoading(importBtn, false);
            UIManager.hideProgressModal();
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
                        <span class="stat-number">${result.total_processed || 0}</span>
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
                                ${event.title} - ${Utils.formatDateTime(event.start_datetime)}
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
                
                ${result.errors && result.errors.length > 0 ? `
                    <div class="result-details">
                        <h5>Import Errors:</h5>
                        ${result.errors.map(error => `<div class="detail-item error">${error}</div>`).join('')}
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
    
    return {
        importEvents
    };
})();

// =============================================================================
// FORMAT EXAMPLES MANAGER
// =============================================================================

/**
 * Manages the format examples section
 */
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

/**
 * Initialize all button event handlers
 */
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

/**
 * Main application controller that coordinates all components
 */
const ImportApp = (() => {
    const init = async () => {
        console.log('Initializing Import Page...');
        
        // Add animation styles
        Utils.addAnimationStyles();
        
        // Check authentication first
        const isAuthenticated = await AuthGuard.checkAuthentication();
        
        if (!isAuthenticated) {
            // User is not authenticated, AuthGuard will handle redirect
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
// APPLICATION INITIALIZATION
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    ImportApp.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    ImportApp.destroy();
});

// Handle browser back/forward navigation
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        // Page was loaded from cache, check auth status again
        AuthGuard.checkAuthentication();
    }
});