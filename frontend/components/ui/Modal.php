<?php
/**
 * Modal Component - Reusable modal dialog system
 * Location: frontend/components/ui/Modal.php
 * 
 * Flexible modal component for various use cases
 */

class Modal {
    private $config;
    private $modalId;
    private $content;
    private $buttons;
    
    /**
     * Default configuration
     */
    private $defaultConfig = [
        'modalId' => 'modal',
        'title' => 'Modal Dialog',
        'size' => 'medium', // small, medium, large, xl
        'backdrop' => true, // true, false, 'static'
        'keyboard' => true, // Allow ESC to close
        'focus' => true,    // Auto-focus on show
        'show' => false,    // Auto-show on render
        'fade' => true,     // Fade animation
        'centered' => false, // Vertically center
        'scrollable' => false, // Scrollable body
        'fullscreen' => false, // Fullscreen on mobile
        'closeButton' => true, // Show X button
        'classes' => [
            'modal' => 'modal',
            'dialog' => 'modal-content',
            'header' => 'modal-header',
            'body' => 'modal-body',
            'footer' => 'modal-footer',
            'title' => 'modal-title',
            'close' => 'close'
        ],
        'aria' => [
            'labelledby' => null, // Auto-generated if null
            'describedby' => null
        ],
        'animation' => [
            'duration' => 300,
            'easing' => 'ease-out'
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->config = array_merge_recursive($this->defaultConfig, $config);
        $this->modalId = $this->config['modalId'];
        $this->content = '';
        $this->buttons = [];
        
        // Auto-generate aria-labelledby if not provided
        if (!$this->config['aria']['labelledby']) {
            $this->config['aria']['labelledby'] = $this->modalId . 'Title';
        }
    }
    
    /**
     * Set modal title
     */
    public function setTitle($title) {
        $this->config['title'] = $title;
        return $this;
    }
    
    /**
     * Set modal size
     */
    public function setSize($size) {
        $this->config['size'] = $size;
        return $this;
    }
    
    /**
     * Set modal content
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Add content to modal body
     */
    public function addContent($content) {
        $this->content .= $content;
        return $this;
    }
    
    /**
     * Add button to modal footer
     */
    public function addButton($config) {
        $defaultButton = [
            'text' => 'Button',
            'type' => 'button',
            'class' => 'btn btn-secondary',
            'id' => null,
            'onclick' => null,
            'data' => [],
            'attributes' => []
        ];
        
        $this->buttons[] = array_merge($defaultButton, $config);
        return $this;
    }
    
    /**
     * Add primary button
     */
    public function addPrimaryButton($text, $onclick = null, $id = null) {
        return $this->addButton([
            'text' => $text,
            'class' => 'btn btn-primary',
            'onclick' => $onclick,
            'id' => $id
        ]);
    }
    
    /**
     * Add secondary button
     */
    public function addSecondaryButton($text, $onclick = null, $id = null) {
        return $this->addButton([
            'text' => $text,
            'class' => 'btn btn-secondary',
            'onclick' => $onclick,
            'id' => $id
        ]);
    }
    
    /**
     * Add danger button
     */
    public function addDangerButton($text, $onclick = null, $id = null) {
        return $this->addButton([
            'text' => $text,
            'class' => 'btn btn-danger',
            'onclick' => $onclick,
            'id' => $id
        ]);
    }
    
    /**
     * Add close button
     */
    public function addCloseButton($text = 'Close') {
        return $this->addButton([
            'text' => $text,
            'class' => 'btn btn-outline',
            'onclick' => "closeModal('{$this->modalId}')"
        ]);
    }
    
    /**
     * Clear all buttons
     */
    public function clearButtons() {
        $this->buttons = [];
        return $this;
    }
    
    /**
     * Render the modal HTML
     */
    public function render() {
        ?>
        <div id="<?php echo htmlspecialchars($this->modalId); ?>" 
             class="<?php echo htmlspecialchars($this->getModalClasses()); ?>"
             <?php echo $this->renderAriaAttributes(); ?>
             <?php echo $this->renderDataAttributes(); ?>
             style="display: none;">
            
            <div class="<?php echo htmlspecialchars($this->getDialogClasses()); ?>">
                <?php $this->renderHeader(); ?>
                <?php $this->renderBody(); ?>
                <?php $this->renderFooter(); ?>
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
        <div class="<?php echo htmlspecialchars($this->config['classes']['header']); ?>">
            <h2 id="<?php echo htmlspecialchars($this->config['aria']['labelledby']); ?>" 
                class="<?php echo htmlspecialchars($this->config['classes']['title']); ?>">
                <?php echo htmlspecialchars($this->config['title']); ?>
            </h2>
            
            <?php if ($this->config['closeButton']): ?>
                <button type="button" 
                        class="<?php echo htmlspecialchars($this->config['classes']['close']); ?>"
                        onclick="closeModal('<?php echo htmlspecialchars($this->modalId); ?>')"
                        aria-label="Close">
                    &times;
                </button>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render modal body
     */
    private function renderBody() {
        ?>
        <div class="<?php echo htmlspecialchars($this->config['classes']['body']); ?>"
             <?php if ($this->config['aria']['describedby']): ?>
                 id="<?php echo htmlspecialchars($this->config['aria']['describedby']); ?>"
             <?php endif; ?>>
            <?php 
            if (is_callable($this->content)) {
                call_user_func($this->content);
            } else {
                echo $this->content;
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Render modal footer
     */
    private function renderFooter() {
        if (empty($this->buttons)) {
            return;
        }
        
        ?>
        <div class="<?php echo htmlspecialchars($this->config['classes']['footer']); ?>">
            <?php foreach ($this->buttons as $button): ?>
                <?php $this->renderButton($button); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render individual button
     */
    private function renderButton($button) {
        $attributes = '';
        
        // Add ID if specified
        if ($button['id']) {
            $attributes .= ' id="' . htmlspecialchars($button['id']) . '"';
        }
        
        // Add onclick if specified
        if ($button['onclick']) {
            $attributes .= ' onclick="' . htmlspecialchars($button['onclick']) . '"';
        }
        
        // Add data attributes
        foreach ($button['data'] as $key => $value) {
            $attributes .= ' data-' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        
        // Add custom attributes
        foreach ($button['attributes'] as $key => $value) {
            $attributes .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        
        ?>
        <button type="<?php echo htmlspecialchars($button['type']); ?>"
                class="<?php echo htmlspecialchars($button['class']); ?>"
                <?php echo $attributes; ?>>
            <?php echo htmlspecialchars($button['text']); ?>
        </button>
        <?php
    }
    
    /**
     * Get modal CSS classes
     */
    private function getModalClasses() {
        $classes = [$this->config['classes']['modal']];
        
        if ($this->config['fade']) {
            $classes[] = 'fade';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Get dialog CSS classes
     */
    private function getDialogClasses() {
        $classes = [$this->config['classes']['dialog']];
        
        // Add size class
        switch ($this->config['size']) {
            case 'small':
                $classes[] = 'modal-sm';
                break;
            case 'large':
                $classes[] = 'modal-lg';
                break;
            case 'xl':
                $classes[] = 'modal-xl';
                break;
            case 'medium':
            default:
                // Default size, no additional class
                break;
        }
        
        if ($this->config['centered']) {
            $classes[] = 'modal-dialog-centered';
        }
        
        if ($this->config['scrollable']) {
            $classes[] = 'modal-dialog-scrollable';
        }
        
        if ($this->config['fullscreen']) {
            $classes[] = 'modal-fullscreen-sm-down';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Render ARIA attributes
     */
    private function renderAriaAttributes() {
        $attributes = '';
        
        $attributes .= ' role="dialog"';
        $attributes .= ' aria-modal="true"';
        
        if ($this->config['aria']['labelledby']) {
            $attributes .= ' aria-labelledby="' . htmlspecialchars($this->config['aria']['labelledby']) . '"';
        }
        
        if ($this->config['aria']['describedby']) {
            $attributes .= ' aria-describedby="' . htmlspecialchars($this->config['aria']['describedby']) . '"';
        }
        
        return $attributes;
    }
    
    /**
     * Render data attributes
     */
    private function renderDataAttributes() {
        $attributes = '';
        
        if (!$this->config['backdrop']) {
            $attributes .= ' data-backdrop="false"';
        } elseif ($this->config['backdrop'] === 'static') {
            $attributes .= ' data-backdrop="static"';
        }
        
        if (!$this->config['keyboard']) {
            $attributes .= ' data-keyboard="false"';
        }
        
        if (!$this->config['focus']) {
            $attributes .= ' data-focus="false"';
        }
        
        return $attributes;
    }
    
    /**
     * Generate modal JavaScript
     */
    private function generateModalJs() {
        $modalId = $this->modalId;
        $config = json_encode($this->config);
        
        return "
        // Initialize Modal: {$modalId}
        (function() {
            const modalEl = document.getElementById('{$modalId}');
            const config = {$config};
            
            if (!modalEl) return;
            
            // Modal state
            let isOpen = false;
            let backdrop = null;
            
            // Show modal function
            function showModal() {
                if (isOpen) return;
                
                // Create backdrop if needed
                if (config.backdrop) {
                    createBackdrop();
                }
                
                // Show modal
                modalEl.style.display = 'block';
                document.body.style.overflow = 'hidden';
                
                // Add fade-in animation
                if (config.fade) {
                    modalEl.style.opacity = '0';
                    modalEl.offsetHeight; // Force reflow
                    modalEl.style.transition = 'opacity ' + config.animation.duration + 'ms ' + config.animation.easing;
                    modalEl.style.opacity = '1';
                }
                
                // Focus management
                if (config.focus) {
                    const firstFocusable = modalEl.querySelector('input, textarea, select, button, [tabindex]:not([tabindex=\"-1\"])');
                    if (firstFocusable) {
                        setTimeout(() => firstFocusable.focus(), 100);
                    }
                }
                
                isOpen = true;
                
                // Emit show event
                const showEvent = new CustomEvent('modalShow', {
                    detail: { modalId: '{$modalId}' }
                });
                document.dispatchEvent(showEvent);
            }
            
            // Hide modal function
            function hideModal() {
                if (!isOpen) return;
                
                // Add fade-out animation
                if (config.fade) {
                    modalEl.style.transition = 'opacity ' + config.animation.duration + 'ms ' + config.animation.easing;
                    modalEl.style.opacity = '0';
                    
                    setTimeout(() => {
                        modalEl.style.display = 'none';
                        document.body.style.overflow = '';
                        removeBackdrop();
                    }, config.animation.duration);
                } else {
                    modalEl.style.display = 'none';
                    document.body.style.overflow = '';
                    removeBackdrop();
                }
                
                isOpen = false;
                
                // Emit hide event
                const hideEvent = new CustomEvent('modalHide', {
                    detail: { modalId: '{$modalId}' }
                });
                document.dispatchEvent(hideEvent);
            }
            
            // Create backdrop
            function createBackdrop() {
                if (backdrop) return;
                
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop';
                backdrop.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    z-index: 1040;
                `;
                
                // Click to close (if not static)
                if (config.backdrop !== 'static') {
                    backdrop.addEventListener('click', hideModal);
                }
                
                document.body.appendChild(backdrop);
                
                // Fade in backdrop
                if (config.fade) {
                    backdrop.style.opacity = '0';
                    backdrop.offsetHeight; // Force reflow
                    backdrop.style.transition = 'opacity ' + config.animation.duration + 'ms ' + config.animation.easing;
                    backdrop.style.opacity = '1';
                }
            }
            
            // Remove backdrop
            function removeBackdrop() {
                if (!backdrop) return;
                
                if (config.fade) {
                    backdrop.style.transition = 'opacity ' + config.animation.duration + 'ms ' + config.animation.easing;
                    backdrop.style.opacity = '0';
                    
                    setTimeout(() => {
                        if (backdrop && backdrop.parentNode) {
                            backdrop.parentNode.removeChild(backdrop);
                        }
                        backdrop = null;
                    }, config.animation.duration);
                } else {
                    if (backdrop.parentNode) {
                        backdrop.parentNode.removeChild(backdrop);
                    }
                    backdrop = null;
                }
            }
            
            // Event listeners
            modalEl.addEventListener('click', function(e) {
                // Close on backdrop click (if not static)
                if (e.target === modalEl && config.backdrop && config.backdrop !== 'static') {
                    hideModal();
                }
            });
            
            // Keyboard handling
            if (config.keyboard) {
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && isOpen) {
                        hideModal();
                    }
                });
            }
            
            // Close button handling
            const closeButtons = modalEl.querySelectorAll('.close, [data-dismiss=\"modal\"]');
            closeButtons.forEach(function(button) {
                button.addEventListener('click', hideModal);
            });
            
            // Auto-show if configured
            if (config.show) {
                showModal();
            }
            
            // Expose modal API
            window['{$modalId}'] = {
                show: showModal,
                hide: hideModal,
                toggle: function() {
                    if (isOpen) {
                        hideModal();
                    } else {
                        showModal();
                    }
                },
                isOpen: function() {
                    return isOpen;
                },
                setTitle: function(title) {
                    const titleEl = document.getElementById(config.aria.labelledby);
                    if (titleEl) {
                        titleEl.textContent = title;
                    }
                },
                setContent: function(content) {
                    const bodyEl = modalEl.querySelector('.' + config.classes.body);
                    if (bodyEl) {
                        bodyEl.innerHTML = content;
                    }
                }
            };
            
            // Global functions
            window.showModal = function(modalId) {
                if (modalId === '{$modalId}') {
                    showModal();
                }
            };
            
            window.closeModal = function(modalId) {
                if (modalId === '{$modalId}') {
                    hideModal();
                }
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
     * Create a simple confirmation modal
     */
    public static function createConfirmation($title, $message, $onConfirm = null, $config = []) {
        $modal = new self(array_merge([
            'modalId' => 'confirmModal',
            'title' => $title,
            'size' => 'small'
        ], $config));
        
        $modal->setContent('<p>' . htmlspecialchars($message) . '</p>');
        $modal->addDangerButton('Confirm', $onConfirm);
        $modal->addCloseButton('Cancel');
        
        return $modal;
    }
    
    /**
     * Create a simple alert modal
     */
    public static function createAlert($title, $message, $config = []) {
        $modal = new self(array_merge([
            'modalId' => 'alertModal',
            'title' => $title,
            'size' => 'small'
        ], $config));
        
        $modal->setContent('<p>' . htmlspecialchars($message) . '</p>');
        $modal->addPrimaryButton('OK', "closeModal('alertModal')");
        
        return $modal;
    }
    
    /**
     * Create a loading modal
     */
    public static function createLoading($title = 'Loading...', $message = 'Please wait...', $config = []) {
        $modal = new self(array_merge([
            'modalId' => 'loadingModal',
            'title' => $title,
            'size' => 'small',
            'backdrop' => 'static',
            'keyboard' => false,
            'closeButton' => false
        ], $config));
        
        $loadingContent = '
            <div class="text-center">
                <div class="spinner-border mb-3" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p>' . htmlspecialchars($message) . '</p>
            </div>
        ';
        
        $modal->setContent($loadingContent);
        
        return $modal;
    }
}