<?php
/**
 * Event Modal Component - Reusable event creation/editing modal
 * Location: frontend/components/calendar/EventModal.php
 * 
 * Configurable modal for event CRUD operations
 */

class EventModal {
    private $config;
    private $modalId;
    private $formFields;
    
    /**
     * Default configuration
     */
    private $defaultConfig = [
        'modalId' => 'eventModal',
        'title' => [
            'create' => 'Add Event',
            'edit' => 'Edit Event',
            'view' => 'Event Details'
        ],
        'fields' => [
            'title' => [
                'type' => 'text',
                'label' => 'Event Title',
                'required' => true,
                'placeholder' => 'Enter event title...'
            ],
            'start' => [
                'type' => 'datetime',
                'label' => 'Start Date & Time',
                'required' => true,
                'placeholder' => 'Select start date & time...'
            ],
            'end' => [
                'type' => 'datetime',
                'label' => 'End Date & Time',
                'required' => false,
                'placeholder' => 'Select end date & time...'
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description',
                'required' => false,
                'placeholder' => 'Event description...',
                'rows' => 3
            ]
        ],
        'buttons' => [
            'save' => [
                'text' => 'Save Event',
                'class' => 'btn btn-primary',
                'type' => 'submit'
            ],
            'delete' => [
                'text' => 'Delete Event',
                'class' => 'btn btn-danger',
                'type' => 'button',
                'showOnEdit' => true
            ],
            'cancel' => [
                'text' => 'Cancel',
                'class' => 'btn btn-outline',
                'type' => 'button'
            ]
        ],
        'permissions' => [
            'canCreate' => true,
            'canEdit' => true,
            'canDelete' => true,
            'editOwnOnly' => true
        ],
        'validation' => [
            'clientSide' => true,
            'serverSide' => true
        ],
        'dateTimePicker' => [
            'enabled' => true,
            'format' => 'Y-m-d H:i',
            'step' => 15,
            'minDate' => false,
            'maxDate' => false
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->config = array_merge_recursive($this->defaultConfig, $config);
        $this->modalId = $this->config['modalId'];
        $this->formFields = $this->config['fields'];
    }
    
    /**
     * Render the modal HTML
     */
    public function render() {
        ?>
        <div id="<?php echo htmlspecialchars($this->modalId); ?>" class="modal event-modal">
            <div class="modal-content">
                <?php $this->renderHeader(); ?>
                <div class="modal-body">
                    <?php $this->renderViewMode(); ?>
                    <?php $this->renderEditMode(); ?>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php echo $this->generateModalJs(); ?>
        });
        </script>
        <?php
    }
    
    /**
     * Render modal header
     */
    private function renderHeader() {
        ?>
        <div class="modal-header">
            <h2 id="<?php echo $this->modalId; ?>Title">
                <?php echo htmlspecialchars($this->config['title']['create']); ?>
            </h2>
            <span class="close modal-close">&times;</span>
        </div>
        <?php
    }
    
    /**
     * Render view mode (read-only event details)
     */
    private function renderViewMode() {
        ?>
        <div id="<?php echo $this->modalId; ?>ViewMode" class="modal-view-mode" style="display: none;">
            <div class="event-details">
                <div class="detail-grid">
                    <div class="detail-row">
                        <label>Title:</label>
                        <span id="<?php echo $this->modalId; ?>ViewTitle">-</span>
                    </div>
                    <div class="detail-row">
                        <label>Start:</label>
                        <span id="<?php echo $this->modalId; ?>ViewStart">-</span>
                    </div>
                    <div class="detail-row">
                        <label>End:</label>
                        <span id="<?php echo $this->modalId; ?>ViewEnd">-</span>
                    </div>
                    <div class="detail-row">
                        <label>Duration:</label>
                        <span id="<?php echo $this->modalId; ?>ViewDuration">-</span>
                    </div>
                    <div class="detail-row">
                        <label>Owner:</label>
                        <span id="<?php echo $this->modalId; ?>ViewOwner">-</span>
                    </div>
                    <?php if (isset($this->formFields['description'])): ?>
                        <div class="detail-row">
                            <label>Description:</label>
                            <span id="<?php echo $this->modalId; ?>ViewDescription">-</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="modal-actions">
                <?php if ($this->config['permissions']['canEdit']): ?>
                    <button type="button" id="<?php echo $this->modalId; ?>EditBtn" class="btn btn-primary">
                        ✏️ Edit Event
                    </button>
                <?php endif; ?>
                
                <?php if ($this->config['permissions']['canDelete']): ?>
                    <button type="button" id="<?php echo $this->modalId; ?>DeleteFromViewBtn" class="btn btn-danger">
                        🗑️ Delete Event
                    </button>
                <?php endif; ?>
                
                <button type="button" class="btn btn-outline modal-close">Close</button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render edit mode (form for creating/editing)
     */
    private function renderEditMode() {
        ?>
        <div id="<?php echo $this->modalId; ?>EditMode" class="modal-edit-mode">
            <form id="<?php echo $this->modalId; ?>Form" novalidate>
                <?php $this->renderFormFields(); ?>
                <?php $this->renderFormActions(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render form fields
     */
    private function renderFormFields() {
        foreach ($this->formFields as $fieldName => $fieldConfig) {
            $this->renderFormField($fieldName, $fieldConfig);
        }
    }
    
    /**
     * Render individual form field
     */
    private function renderFormField($fieldName, $fieldConfig) {
        $fieldId = $this->modalId . ucfirst($fieldName);
        $required = $fieldConfig['required'] ?? false;
        $requiredAttr = $required ? 'required' : '';
        $requiredMark = $required ? ' *' : '';
        
        ?>
        <div class="form-group">
            <label for="<?php echo htmlspecialchars($fieldId); ?>">
                <?php echo htmlspecialchars($fieldConfig['label']); ?><?php echo $requiredMark; ?>
            </label>
            
            <?php
            switch ($fieldConfig['type']) {
                case 'textarea':
                    $this->renderTextarea($fieldId, $fieldConfig, $requiredAttr);
                    break;
                case 'datetime':
                    $this->renderDateTimeInput($fieldId, $fieldConfig, $requiredAttr);
                    break;
                case 'select':
                    $this->renderSelect($fieldId, $fieldConfig, $requiredAttr);
                    break;
                default:
                    $this->renderTextInput($fieldId, $fieldConfig, $requiredAttr);
                    break;
            }
            ?>
            
            <?php if (isset($fieldConfig['help'])): ?>
                <small class="form-help"><?php echo htmlspecialchars($fieldConfig['help']); ?></small>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render text input
     */
    private function renderTextInput($fieldId, $fieldConfig, $requiredAttr) {
        ?>
        <input 
            type="<?php echo htmlspecialchars($fieldConfig['inputType'] ?? 'text'); ?>"
            id="<?php echo htmlspecialchars($fieldId); ?>"
            placeholder="<?php echo htmlspecialchars($fieldConfig['placeholder'] ?? ''); ?>"
            class="form-control"
            <?php echo $requiredAttr; ?>
            <?php if (isset($fieldConfig['maxlength'])): ?>
                maxlength="<?php echo intval($fieldConfig['maxlength']); ?>"
            <?php endif; ?>
        >
        <?php
    }
    
    /**
     * Render textarea
     */
    private function renderTextarea($fieldId, $fieldConfig, $requiredAttr) {
        ?>
        <textarea 
            id="<?php echo htmlspecialchars($fieldId); ?>"
            placeholder="<?php echo htmlspecialchars($fieldConfig['placeholder'] ?? ''); ?>"
            class="form-control"
            rows="<?php echo intval($fieldConfig['rows'] ?? 3); ?>"
            <?php echo $requiredAttr; ?>
        ></textarea>
        <?php
    }
    
    /**
     * Render datetime input
     */
    private function renderDateTimeInput($fieldId, $fieldConfig, $requiredAttr) {
        $readonly = $this->config['dateTimePicker']['enabled'] ? 'readonly' : '';
        ?>
        <input 
            type="text"
            id="<?php echo htmlspecialchars($fieldId); ?>"
            placeholder="<?php echo htmlspecialchars($fieldConfig['placeholder'] ?? ''); ?>"
            class="form-control datetime-picker"
            <?php echo $requiredAttr; ?>
            <?php echo $readonly; ?>
        >
        <?php
    }
    
    /**
     * Render select dropdown
     */
    private function renderSelect($fieldId, $fieldConfig, $requiredAttr) {
        ?>
        <select 
            id="<?php echo htmlspecialchars($fieldId); ?>"
            class="form-control"
            <?php echo $requiredAttr; ?>
        >
            <?php if (isset($fieldConfig['placeholder'])): ?>
                <option value=""><?php echo htmlspecialchars($fieldConfig['placeholder']); ?></option>
            <?php endif; ?>
            
            <?php if (isset($fieldConfig['options'])): ?>
                <?php foreach ($fieldConfig['options'] as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <?php
    }
    
    /**
     * Render form actions
     */
    private function renderFormActions() {
        ?>
        <div class="form-actions">
            <?php foreach ($this->config['buttons'] as $buttonName => $buttonConfig): ?>
                <?php $this->renderButton($buttonName, $buttonConfig); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render individual button
     */
    private function renderButton($buttonName, $buttonConfig) {
        $buttonId = $this->modalId . ucfirst($buttonName) . 'Btn';
        $style = '';
        
        if ($buttonName === 'delete' && ($buttonConfig['showOnEdit'] ?? false)) {
            $style = 'style="display: none;"';
        }
        
        ?>
        <button 
            type="<?php echo htmlspecialchars($buttonConfig['type']); ?>"
            id="<?php echo htmlspecialchars($buttonId); ?>"
            class="<?php echo htmlspecialchars($buttonConfig['class']); ?>"
            <?php echo $style; ?>
        >
            <?php echo htmlspecialchars($buttonConfig['text']); ?>
        </button>
        <?php
    }
    
    /**
     * Generate modal JavaScript
     */
    private function generateModalJs() {
        $modalId = $this->modalId;
        $config = json_encode($this->config);
        
        return "
        // Initialize Event Modal: {$modalId}
        (function() {
            const modalEl = document.getElementById('{$modalId}');
            const config = {$config};
            let currentEvent = null;
            let isEditMode = false;
            
            // Modal state management
            function openModal(eventData = {}, mode = 'create') {
                if (!modalEl) return;
                
                currentEvent = eventData;
                isEditMode = mode === 'edit' || mode === 'view';
                
                // Update modal title
                const titleEl = document.getElementById('{$modalId}Title');
                if (titleEl) {
                    const titleKey = mode === 'view' ? 'view' : (mode === 'edit' ? 'edit' : 'create');
                    titleEl.textContent = config.title[titleKey] || config.title.create;
                }
                
                if (mode === 'view') {
                    showViewMode(eventData);
                } else {
                    showEditMode(eventData, mode === 'edit');
                }
                
                modalEl.style.display = 'block';
                
                // Focus first input in edit mode
                if (mode !== 'view') {
                    const firstInput = modalEl.querySelector('input:not([readonly]), textarea, select');
                    if (firstInput) {
                        setTimeout(() => firstInput.focus(), 100);
                    }
                }
            }
            
            function closeModal() {
                if (modalEl) {
                    modalEl.style.display = 'none';
                }
                currentEvent = null;
                isEditMode = false;
                clearForm();
            }
            
            function showViewMode(eventData) {
                const viewMode = document.getElementById('{$modalId}ViewMode');
                const editMode = document.getElementById('{$modalId}EditMode');
                
                if (viewMode) viewMode.style.display = 'block';
                if (editMode) editMode.style.display = 'none';
                
                populateViewData(eventData);
            }
            
            function showEditMode(eventData, isEdit = false) {
                const viewMode = document.getElementById('{$modalId}ViewMode');
                const editMode = document.getElementById('{$modalId}EditMode');
                
                if (viewMode) viewMode.style.display = 'none';
                if (editMode) editMode.style.display = 'block';
                
                populateFormData(eventData);
                
                // Show/hide delete button
                const deleteBtn = document.getElementById('{$modalId}DeleteBtn');
                if (deleteBtn) {
                    deleteBtn.style.display = isEdit ? 'inline-block' : 'none';
                }
            }
            
            function populateViewData(eventData) {
                // Populate view fields
                const fields = ['Title', 'Start', 'End', 'Duration', 'Owner', 'Description'];
                
                fields.forEach(field => {
                    const el = document.getElementById('{$modalId}View' + field);
                    if (el) {
                        let value = eventData[field.toLowerCase()] || '-';
                        
                        // Special formatting for certain fields
                        if (field === 'Start' || field === 'End') {
                            value = formatDateTime(value);
                        } else if (field === 'Duration') {
                            value = calculateDuration(eventData.start, eventData.end);
                        } else if (field === 'Owner') {
                            value = eventData.owner || eventData.userName || 'Unknown';
                        }
                        
                        el.textContent = value;
                    }
                });
            }
            
            function populateFormData(eventData) {
                // Populate form fields
                Object.keys(config.fields).forEach(fieldName => {
                    const fieldEl = document.getElementById('{$modalId}' + capitalizeFirst(fieldName));
                    if (fieldEl) {
                        const value = eventData[fieldName] || '';
                        
                        if (fieldEl.type === 'checkbox') {
                            fieldEl.checked = !!value;
                        } else {
                            fieldEl.value = value;
                        }
                        
                        // Initialize datetime picker if needed
                        if (config.fields[fieldName].type === 'datetime' && config.dateTimePicker.enabled) {
                            initializeDateTimePicker(fieldEl, value);
                        }
                    }
                });
            }
            
            function clearForm() {
                Object.keys(config.fields).forEach(fieldName => {
                    const fieldEl = document.getElementById('{$modalId}' + capitalizeFirst(fieldName));
                    if (fieldEl) {
                        if (fieldEl.type === 'checkbox') {
                            fieldEl.checked = false;
                        } else {
                            fieldEl.value = '';
                        }
                    }
                });
            }
            
            function initializeDateTimePicker(element, value) {
                if (typeof jQuery !== 'undefined' && jQuery.fn.datetimepicker) {
                    const options = {
                        format: config.dateTimePicker.format,
                        step: config.dateTimePicker.step,
                        timepicker: true,
                        datepicker: true,
                        closeOnDateSelect: false,
                        closeOnTimeSelect: true
                    };
                    
                    if (value) {
                        options.value = new Date(value);
                    }
                    
                    jQuery(element).datetimepicker(options);
                }
            }
            
            function validateForm() {
                if (!config.validation.clientSide) return true;
                
                let isValid = true;
                const errors = [];
                
                Object.keys(config.fields).forEach(fieldName => {
                    const fieldConfig = config.fields[fieldName];
                    const fieldEl = document.getElementById('{$modalId}' + capitalizeFirst(fieldName));
                    
                    if (fieldEl && fieldConfig.required) {
                        const value = fieldEl.value.trim();
                        if (!value) {
                            isValid = false;
                            errors.push(fieldConfig.label + ' is required');
                            fieldEl.classList.add('error');
                        } else {
                            fieldEl.classList.remove('error');
                        }
                    }
                });
                
                if (!isValid) {
                    showMessage('Please fill in all required fields: ' + errors.join(', '), 'error');
                }
                
                return isValid;
            }
            
            function saveEvent() {
                if (!validateForm()) return;
                
                const formData = {};
                
                Object.keys(config.fields).forEach(fieldName => {
                    const fieldEl = document.getElementById('{$modalId}' + capitalizeFirst(fieldName));
                    if (fieldEl) {
                        formData[fieldName] = fieldEl.type === 'checkbox' ? fieldEl.checked : fieldEl.value;
                    }
                });
                
                if (currentEvent && currentEvent.id) {
                    formData.id = currentEvent.id;
                }
                
                // Emit save event
                const saveEvent = new CustomEvent('eventModalSave', {
                    detail: {
                        data: formData,
                        isEdit: !!(currentEvent && currentEvent.id),
                        originalEvent: currentEvent
                    }
                });
                
                document.dispatchEvent(saveEvent);
            }
            
            function deleteEvent() {
                if (!currentEvent || !currentEvent.id) return;
                
                if (confirm('Are you sure you want to delete this event?')) {
                    const deleteEvent = new CustomEvent('eventModalDelete', {
                        detail: {
                            eventId: currentEvent.id,
                            originalEvent: currentEvent
                        }
                    });
                    
                    document.dispatchEvent(deleteEvent);
                }
            }
            
            // Utility functions
            function capitalizeFirst(str) {
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
            
            function formatDateTime(dateStr) {
                if (!dateStr) return '-';
                try {
                    const date = new Date(dateStr);
                    return date.toLocaleString();
                } catch (e) {
                    return dateStr;
                }
            }
            
            function calculateDuration(start, end) {
                if (!start || !end) return '-';
                try {
                    const startDate = new Date(start);
                    const endDate = new Date(end);
                    const diffMs = endDate - startDate;
                    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                    const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
                    
                    if (diffHours === 0) {
                        return diffMinutes + 'm';
                    } else if (diffMinutes === 0) {
                        return diffHours + 'h';
                    } else {
                        return diffHours + 'h ' + diffMinutes + 'm';
                    }
                } catch (e) {
                    return '-';
                }
            }
            
            function showMessage(message, type = 'info') {
                if (window.showNotification) {
                    window.showNotification(message, type);
                } else {
                    console.log(type.toUpperCase() + ': ' + message);
                }
            }
            
            // Event listeners
            if (modalEl) {
                // Close modal events
                modalEl.addEventListener('click', function(e) {
                    if (e.target === modalEl || e.target.classList.contains('modal-close')) {
                        closeModal();
                    }
                });
                
                // Form submission
                const form = document.getElementById('{$modalId}Form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        saveEvent();
                    });
                }
                
                // Button events
                const saveBtn = document.getElementById('{$modalId}SaveBtn');
                if (saveBtn) {
                    saveBtn.addEventListener('click', saveEvent);
                }
                
                const deleteBtn = document.getElementById('{$modalId}DeleteBtn');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', deleteEvent);
                }
                
                const deleteFromViewBtn = document.getElementById('{$modalId}DeleteFromViewBtn');
                if (deleteFromViewBtn) {
                    deleteFromViewBtn.addEventListener('click', deleteEvent);
                }
                
                const editBtn = document.getElementById('{$modalId}EditBtn');
                if (editBtn) {
                    editBtn.addEventListener('click', function() {
                        showEditMode(currentEvent, true);
                    });
                }
                
                const cancelBtn = document.getElementById('{$modalId}CancelBtn');
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', closeModal);
                }
            }
            
            // Expose modal API
            window['{$modalId}'] = {
                open: openModal,
                close: closeModal,
                getCurrentEvent: function() { return currentEvent; },
                isOpen: function() { return modalEl && modalEl.style.display === 'block'; }
            };
            
        })();
        ";
    }
    
    /**
     * Create a modal with configuration
     */
    public static function create($config = []) {
        return new self($config);
    }
    
    /**
     * Quick render method
     */
    public static function render($config = []) {
        $modal = new self($config);
        $modal->render();
    }
}