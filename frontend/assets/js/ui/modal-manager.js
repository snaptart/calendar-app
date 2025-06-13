/**
 * Enhanced Modal Manager for generic modal dialogs
 * Location: frontend/js/ui/modal-manager.js
 */
import { EventBus } from '../core/event-bus.js';
import { Utils } from '../core/utils.js';
import { UserManager } from '../auth/user-manager.js';
import { DateTimeManager } from '../calendar/datetime-manager.js';

export const ModalManager = (() => {
    let currentEvent = null;
    let activeModals = new Map();
    let modalIdCounter = 0;
    
    /**
     * Create a generic modal dialog
     */
    const create = (options = {}) => {
        const modalId = `modal_${++modalIdCounter}`;
        
        const defaultOptions = {
            title: 'Modal',
            body: '',
            footer: '',
            size: 'medium', // small, medium, large, xl
            closable: true,
            backdrop: true,
            keyboard: true,
            className: ''
        };
        
        const config = { ...defaultOptions, ...options };
        
        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog" aria-labelledby="${modalId}Label" aria-hidden="true" data-backdrop="${config.backdrop ? 'true' : 'static'}" data-keyboard="${config.keyboard}">
                <div class="modal-dialog modal-${config.size} ${config.className}" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="${modalId}Label">${config.title}</h5>
                            ${config.closable ? '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' : ''}
                        </div>
                        <div class="modal-body">
                            ${config.body}
                        </div>
                        ${config.footer ? `<div class="modal-footer">${config.footer}</div>` : ''}
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modalElement = document.getElementById(modalId);
        
        // Store modal info
        const modalInfo = {
            id: modalId,
            element: modalElement,
            config,
            isOpen: false
        };
        
        activeModals.set(modalId, modalInfo);
        
        // Set up event listeners for this modal
        setupGenericModalEvents(modalInfo);
        
        // Return modal API
        return {
            show: () => showGenericModal(modalId),
            hide: () => hideGenericModal(modalId),
            destroy: () => destroyGenericModal(modalId),
            update: (newOptions) => updateGenericModal(modalId, newOptions),
            getId: () => modalId,
            getElement: () => modalElement
        };
    };
    
    /**
     * Show a generic modal
     */
    const showGenericModal = (modalId) => {
        const modalInfo = activeModals.get(modalId);
        if (!modalInfo) return false;
        
        const modal = modalInfo.element;
        
        // Use Bootstrap modal if available, otherwise custom implementation
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $(modal).modal('show');
        } else {
            modal.style.display = 'block';
            modal.classList.add('show');
            document.body.classList.add('modal-open');
            
            // Add backdrop
            if (modalInfo.config.backdrop) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.id = `${modalId}_backdrop`;
                document.body.appendChild(backdrop);
            }
        }
        
        modalInfo.isOpen = true;
        
        EventBus.emit('modal:shown', { modalId, config: modalInfo.config });
        return true;
    };
    
    /**
     * Hide a generic modal
     */
    const hideGenericModal = (modalId) => {
        const modalInfo = activeModals.get(modalId);
        if (!modalInfo) return false;
        
        const modal = modalInfo.element;
        
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $(modal).modal('hide');
        } else {
            modal.style.display = 'none';
            modal.classList.remove('show');
            
            // Remove backdrop
            const backdrop = document.getElementById(`${modalId}_backdrop`);
            if (backdrop) {
                backdrop.remove();
            }
            
            // Check if any modals are still open
            const openModals = Array.from(activeModals.values()).filter(m => m.isOpen && m.id !== modalId);
            if (openModals.length === 0) {
                document.body.classList.remove('modal-open');
            }
        }
        
        modalInfo.isOpen = false;
        
        EventBus.emit('modal:hidden', { modalId, config: modalInfo.config });
        return true;
    };
    
    /**
     * Destroy a generic modal
     */
    const destroyGenericModal = (modalId) => {
        const modalInfo = activeModals.get(modalId);
        if (!modalInfo) return false;
        
        // Hide first if open
        if (modalInfo.isOpen) {
            hideGenericModal(modalId);
        }
        
        // Remove from DOM
        modalInfo.element.remove();
        
        // Remove backdrop if exists
        const backdrop = document.getElementById(`${modalId}_backdrop`);
        if (backdrop) {
            backdrop.remove();
        }
        
        // Remove from tracking
        activeModals.delete(modalId);
        
        EventBus.emit('modal:destroyed', { modalId });
        return true;
    };
    
    /**
     * Update modal content
     */
    const updateGenericModal = (modalId, options) => {
        const modalInfo = activeModals.get(modalId);
        if (!modalInfo) return false;
        
        const modal = modalInfo.element;
        
        if (options.title) {
            const titleElement = modal.querySelector('.modal-title');
            if (titleElement) titleElement.textContent = options.title;
        }
        
        if (options.body) {
            const bodyElement = modal.querySelector('.modal-body');
            if (bodyElement) bodyElement.innerHTML = options.body;
        }
        
        if (options.footer !== undefined) {
            let footerElement = modal.querySelector('.modal-footer');
            if (options.footer) {
                if (!footerElement) {
                    footerElement = document.createElement('div');
                    footerElement.className = 'modal-footer';
                    modal.querySelector('.modal-content').appendChild(footerElement);
                }
                footerElement.innerHTML = options.footer;
            } else if (footerElement) {
                footerElement.remove();
            }
        }
        
        // Update config
        modalInfo.config = { ...modalInfo.config, ...options };
        
        EventBus.emit('modal:updated', { modalId, options });
        return true;
    };
    
    /**
     * Set up event listeners for generic modal
     */
    const setupGenericModalEvents = (modalInfo) => {
        const modal = modalInfo.element;
        
        // Close button
        const closeBtn = modal.querySelector('.close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => hideGenericModal(modalInfo.id));
        }
        
        // Backdrop click
        if (modalInfo.config.backdrop) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    hideGenericModal(modalInfo.id);
                }
            });
        }
        
        // Keyboard events
        if (modalInfo.config.keyboard) {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modalInfo.isOpen) {
                    hideGenericModal(modalInfo.id);
                }
            });
        }
    };
    
    /**
     * Close the most recently opened modal
     */
    const closeActive = () => {
        const openModals = Array.from(activeModals.values()).filter(m => m.isOpen);
        if (openModals.length > 0) {
            const lastModal = openModals[openModals.length - 1];
            hideGenericModal(lastModal.id);
        }
    };
    
    /**
     * Close all open modals
     */
    const closeAll = () => {
        activeModals.forEach((modalInfo) => {
            if (modalInfo.isOpen) {
                hideGenericModal(modalInfo.id);
            }
        });
    };
    
    /**
     * Get list of open modals
     */
    const getOpenModals = () => {
        return Array.from(activeModals.values()).filter(m => m.isOpen).map(m => m.id);
    };
    
    // ===========================================================================
    // LEGACY EVENT MODAL FUNCTIONALITY (preserved for backward compatibility)
    // ===========================================================================
    
    const openModal = (eventData = {}) => {
        const modal = document.getElementById('eventModal');
        const modalTitle = document.getElementById('modalTitle');
        const deleteBtn = document.getElementById('deleteEventBtn');
        
        if (!modal) return;
        
        // Check if user is authenticated
        const currentUser = UserManager.getCurrentUser();
        if (!currentUser) {
            EventBus.emit('ui:showNotification', { 
                message: 'Please login to create events', 
                type: 'error' 
            });
            return;
        }
        
        // Set form values
        document.getElementById('eventTitle').value = eventData.title || '';
        
        // Set datetime values
        if (eventData.start || eventData.end) {
            DateTimeManager.setDateTimeValues(eventData.start, eventData.end);
        } else {
            DateTimeManager.setDefaultDateTime();
        }
        
        // Update modal state
        if (eventData.id) {
            modalTitle.textContent = 'Edit Event';
            deleteBtn.style.display = 'inline-block';
            currentEvent = eventData;
        } else {
            modalTitle.textContent = 'Add Event';
            deleteBtn.style.display = 'none';
            currentEvent = null;
        }
        
        modal.style.display = 'block';
        document.getElementById('eventTitle').focus();
        
        EventBus.emit('modal:opened', { event: eventData });
    };
    
    const closeModal = () => {
        const modal = document.getElementById('eventModal');
        if (modal) {
            modal.style.display = 'none';
        }
        
        currentEvent = null;
        DateTimeManager.clearDateTimeValues();
        
        EventBus.emit('modal:closed');
    };
    
    const getCurrentEvent = () => currentEvent;
    
    const validateForm = () => {
        const title = document.getElementById('eventTitle').value.trim();
        const { start } = DateTimeManager.getDateTimeValues();
        
        if (!title) {
            EventBus.emit('ui:showNotification', { 
                message: 'Please enter an event title', 
                type: 'error' 
            });
            return false;
        }
        
        if (!start) {
            EventBus.emit('ui:showNotification', { 
                message: 'Please select a start date and time', 
                type: 'error' 
            });
            return false;
        }
        
        const currentUser = UserManager.getCurrentUser();
        if (!currentUser) {
            EventBus.emit('ui:showNotification', { 
                message: 'Please login to create events', 
                type: 'error' 
            });
            return false;
        }
        
        return true;
    };
    
    const getFormData = () => {
        const title = document.getElementById('eventTitle').value.trim();
        const { start, end } = DateTimeManager.getDateTimeValues();
        
        return {
            title,
            start: Utils.formatDateTimeForAPI(start),
            end: Utils.formatDateTimeForAPI(end || start)
        };
    };
    
    const setupEventListeners = () => {
        const modal = document.getElementById('eventModal');
        const closeBtn = modal?.querySelector('.close');
        const cancelBtn = document.getElementById('cancelBtn');
        const eventForm = document.getElementById('eventForm');
        const deleteBtn = document.getElementById('deleteEventBtn');
        
        // Close modal events
        [closeBtn, cancelBtn].forEach(btn => {
            btn?.addEventListener('click', closeModal);
        });
        
        // Close on outside click
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        
        // Form submission
        eventForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            if (validateForm()) {
                EventBus.emit('event:save', {
                    eventData: getFormData(),
                    isEdit: !!currentEvent,
                    eventId: currentEvent?.id
                });
            }
        });
        
        // Delete event
        deleteBtn?.addEventListener('click', async () => {
            if (currentEvent) {
                const confirmed = await confirm({
                    title: 'Delete Event',
                    message: 'Are you sure you want to delete this event? This action cannot be undone.',
                    confirmText: 'Delete',
                    cancelText: 'Cancel',
                    confirmClass: 'btn-danger'
                });
                
                if (confirmed) {
                    EventBus.emit('event:delete', { eventId: currentEvent.id });
                }
            }
        });
    };
    
    // Event listeners
    EventBus.on('calendar:dateSelect', ({ start, end }) => {
        const currentUser = UserManager.getCurrentUser();
        if (!currentUser) {
            EventBus.emit('ui:showNotification', { 
                message: 'Please login to create events', 
                type: 'error' 
            });
            return;
        }
        
        openModal({ start, end });
    });
    
    EventBus.on('calendar:eventClick', ({ event }) => {
        const canEdit = UserManager.canUserEditEvent(event);
        if (!canEdit) {
            EventBus.emit('ui:showNotification', { 
                message: `This event belongs to ${event.extendedProps.userName}. You can only edit your own events.`, 
                type: 'error' 
            });
            return;
        }
        
        openModal({
            id: event.id,
            title: event.title,
            start: event.startStr,
            end: event.endStr || event.startStr
        });
    });
    
    EventBus.on('event:saved', closeModal);
    EventBus.on('event:deleted', closeModal);
    
    /**
     * Show a confirmation dialog
     */
    const confirm = (options = {}) => {
        return new Promise((resolve) => {
            const defaultOptions = {
                title: 'Confirm Action',
                message: 'Are you sure?',
                confirmText: 'Confirm',
                cancelText: 'Cancel',
                confirmClass: 'btn-danger',
                cancelClass: 'btn-secondary'
            };
            
            const config = { ...defaultOptions, ...options };
            
            const footer = `
                <button type="button" class="btn ${config.cancelClass}" data-action="cancel">
                    ${config.cancelText}
                </button>
                <button type="button" class="btn ${config.confirmClass}" data-action="confirm">
                    ${config.confirmText}
                </button>
            `;
            
            const modalApi = create({
                title: config.title,
                body: `<p>${config.message}</p>`,
                footer: footer,
                size: 'small',
                closable: false,
                backdrop: false,
                keyboard: true,
                className: 'confirm-modal'
            });
            
            const modalId = modalApi.getId();
            const modal = modalApi.getElement();
            
            if (!modal) {
                console.error('Modal element not found');
                resolve(false);
                return;
            }
            
            // Handle button clicks
            const handleClick = (e) => {
                const action = e.target.getAttribute('data-action');
                if (action === 'confirm') {
                    resolve(true);
                    modalApi.hide();
                    setTimeout(() => modalApi.destroy(), 300);
                } else if (action === 'cancel') {
                    resolve(false);
                    modalApi.hide();
                    setTimeout(() => modalApi.destroy(), 300);
                }
            };
            
            modal.addEventListener('click', handleClick);
            
            // Handle keyboard escape
            const handleKeydown = (e) => {
                if (e.key === 'Escape') {
                    resolve(false);
                    modalApi.hide();
                    setTimeout(() => modalApi.destroy(), 300);
                    document.removeEventListener('keydown', handleKeydown);
                }
            };
            
            document.addEventListener('keydown', handleKeydown);
            
            // Show the modal
            modalApi.show();
            
            // Focus the confirm button
            setTimeout(() => {
                const confirmBtn = modal.querySelector('[data-action="confirm"]');
                if (confirmBtn) confirmBtn.focus();
            }, 300);
        });
    };

    return {
        // Generic modal API
        create,
        confirm,
        closeActive,
        closeAll,
        getOpenModals,
        
        // Legacy event modal API (preserved for backward compatibility)
        openModal,
        closeModal,
        getCurrentEvent,
        setupEventListeners
    };
})();