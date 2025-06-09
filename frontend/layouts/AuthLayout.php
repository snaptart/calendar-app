<?php
/**
 * Authentication Layout Template - For login/register pages
 * Location: frontend/layouts/AuthLayout.php
 * 
 * Extends BaseLayout to provide authentication-specific layout (login, register)
 */

require_once __DIR__ . '/BaseLayout.php';

class AuthLayout extends BaseLayout {
    protected $formTitle = '';
    protected $formSubtitle = '';
    protected $showDemoNotice = true;
    protected $backgroundGradient = true;
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        // Set auth-specific defaults
        $defaultConfig = [
            'title' => 'Login - Collaborative Calendar',
            'css' => ['assets/css/login.css'],
            'bodyClasses' => ['auth-page']
        ];
        
        // Merge with provided config
        $config = array_merge($defaultConfig, $config);
        
        parent::__construct($config);
        
        // Apply auth-specific configuration
        $this->configureAuth($config);
    }
    
    /**
     * Configure auth-specific settings
     */
    protected function configureAuth($config) {
        if (isset($config['formTitle'])) {
            $this->formTitle = $config['formTitle'];
        }
        
        if (isset($config['formSubtitle'])) {
            $this->formSubtitle = $config['formSubtitle'];
        }
        
        if (isset($config['showDemoNotice'])) {
            $this->showDemoNotice = $config['showDemoNotice'];
        }
        
        if (isset($config['backgroundGradient'])) {
            $this->backgroundGradient = $config['backgroundGradient'];
        }
    }
    
    /**
     * Render the login container
     */
    protected function renderLoginContainer($content) {
        ?>
        <div class="login-container">
            <div class="login-card">
                <?php $this->renderLoginHeader(); ?>
                
                <?php if ($this->showDemoNotice): ?>
                    <?php $this->renderDemoNotice(); ?>
                <?php endif; ?>
                
                <?php $this->renderMessageContainers(); ?>
                
                <?php 
                if (is_callable($content)) {
                    call_user_func($content);
                } else {
                    echo $content;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render login header
     */
    protected function renderLoginHeader() {
        ?>
        <div class="login-header">
            <h1><?php echo $this->formTitle ?: '📅 Calendar'; ?></h1>
            <?php if ($this->formSubtitle): ?>
                <p><?php echo $this->e($this->formSubtitle); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render demo notice
     */
    protected function renderDemoNotice() {
        ?>
        <div class="demo-notice">
            <h3>Quick Demo Access</h3>
            <p>You can register with any email or use existing demo accounts to explore the collaborative features.</p>
        </div>
        <?php
    }
    
    /**
     * Render authentication tabs
     */
    public function renderAuthTabs($tabs = ['login' => 'Sign In', 'register' => 'Sign Up']) {
        ?>
        <div class="auth-tabs">
            <?php foreach ($tabs as $key => $label): ?>
                <button class="auth-tab<?php echo $key === 'login' ? ' active' : ''; ?>" data-tab="<?php echo $key; ?>">
                    <?php echo $this->e($label); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render form container
     */
    public function renderFormContainer($formId, $content, $active = true) {
        ?>
        <form id="<?php echo $this->e($formId); ?>" class="auth-form<?php echo $active ? ' active' : ''; ?>">
            <?php 
            if (is_callable($content)) {
                call_user_func($content);
            } else {
                echo $content;
            }
            ?>
        </form>
        <?php
    }
    
    /**
     * Render form group
     */
    public function renderFormGroup($id, $label, $type = 'text', $placeholder = '', $required = false, $attributes = []) {
        $attributeString = '';
        foreach ($attributes as $attr => $value) {
            $attributeString .= ' ' . $attr . '="' . $this->e($value) . '"';
        }
        
        ?>
        <div class="form-group">
            <label for="<?php echo $this->e($id); ?>"><?php echo $this->e($label); ?></label>
            <input 
                type="<?php echo $this->e($type); ?>" 
                id="<?php echo $this->e($id); ?>" 
                placeholder="<?php echo $this->e($placeholder); ?>"
                <?php echo $required ? 'required' : ''; ?>
                <?php echo $attributeString; ?>
            >
        </div>
        <?php
    }
    
    /**
     * Render checkbox group
     */
    public function renderCheckboxGroup($id, $label, $checked = false) {
        ?>
        <div class="checkbox-group">
            <input type="checkbox" id="<?php echo $this->e($id); ?>"<?php echo $checked ? ' checked' : ''; ?>>
            <label for="<?php echo $this->e($id); ?>"><?php echo $this->e($label); ?></label>
        </div>
        <?php
    }
    
    /**
     * Render submit button
     */
    public function renderSubmitButton($text, $classes = 'auth-button') {
        ?>
        <button type="submit" class="<?php echo $this->e($classes); ?>"><?php echo $this->e($text); ?></button>
        <?php
    }
    
    /**
     * Render message containers for auth pages
     */
    protected function renderMessageContainers() {
        ?>
        <div id="errorMessage" class="error-message" style="display: none;"></div>
        <div id="successMessage" class="success-message" style="display: none;"></div>
        <?php
    }
    
    /**
     * Add auth-specific JavaScript
     */
    public function addAuthScripts() {
        $this->addInlineJS('
            document.addEventListener("DOMContentLoaded", function() {
                // Tab switching functionality
                const tabs = document.querySelectorAll(".auth-tab");
                const forms = document.querySelectorAll(".auth-form");
                
                tabs.forEach(function(tab) {
                    tab.addEventListener("click", function() {
                        const targetTab = tab.dataset.tab;
                        
                        // Update tab states
                        tabs.forEach(function(t) {
                            t.classList.remove("active");
                        });
                        tab.classList.add("active");
                        
                        // Update form states
                        forms.forEach(function(form) {
                            form.classList.remove("active");
                            if (form.id === targetTab + "Form") {
                                form.classList.add("active");
                            }
                        });
                        
                        // Clear messages
                        clearMessages();
                    });
                });
                
                // Clear messages function
                function clearMessages() {
                    const errorEl = document.getElementById("errorMessage");
                    const successEl = document.getElementById("successMessage");
                    
                    if (errorEl) errorEl.style.display = "none";
                    if (successEl) successEl.style.display = "none";
                }
                
                // Auto-hide messages after 5 seconds
                function autoHideMessages() {
                    setTimeout(clearMessages, 5000);
                }
                
                // Message display function
                window.showAuthMessage = function(message, type) {
                    clearMessages();
                    
                    const elementId = type === "error" ? "errorMessage" : "successMessage";
                    const element = document.getElementById(elementId);
                    
                    if (element) {
                        element.textContent = message;
                        element.style.display = "block";
                        autoHideMessages();
                    }
                };
            });
        ');
        
        return $this;
    }
    
    /**
     * Create a complete authentication page
     */
    public function renderAuthPage($contentCallback) {
        $this->start();
        $this->renderLoginContainer($contentCallback);
        $this->end();
    }
    
    /**
     * Create a login page with tabs
     */
    public static function createLoginPage($config = []) {
        $layout = new self(array_merge([
            'formTitle' => '📅 Calendar',
            'formSubtitle' => 'Collaborative calendar for teams',
            'showDemoNotice' => true
        ], $config));
        
        $layout->addAuthScripts();
        
        $layout->renderAuthPage(function() use ($layout) {
            // Render auth tabs
            $layout->renderAuthTabs();
            
            // Login form
            $layout->renderFormContainer('loginForm', function() use ($layout) {
                $layout->renderFormGroup('loginEmail', 'Email Address', 'email', 'Enter your email...', true);
                $layout->renderFormGroup('loginPassword', 'Password', 'password', 'Enter your password...', true);
                $layout->renderCheckboxGroup('rememberMe', 'Remember me for 30 days');
                $layout->renderSubmitButton('Sign In');
            });
            
            // Registration form
            $layout->renderFormContainer('registerForm', function() use ($layout) {
                $layout->renderFormGroup('registerName', 'Full Name', 'text', 'Enter your full name...', true);
                $layout->renderFormGroup('registerEmail', 'Email Address', 'email', 'Enter your email...', true);
                $layout->renderFormGroup('registerPassword', 'Password', 'password', 'Create a password...', true, ['minlength' => '6']);
                $layout->renderFormGroup('confirmPassword', 'Confirm Password', 'password', 'Confirm your password...', true, ['minlength' => '6']);
                $layout->renderSubmitButton('Create Account');
            }, false);
        });
    }
    
    /**
     * Create a simple login page (no registration)
     */
    public static function createSimpleLoginPage($config = []) {
        $layout = new self(array_merge([
            'formTitle' => '📅 Calendar Login',
            'formSubtitle' => 'Sign in to continue',
            'showDemoNotice' => false
        ], $config));
        
        $layout->addAuthScripts();
        
        $layout->renderAuthPage(function() use ($layout) {
            // Single login form
            $layout->renderFormContainer('loginForm', function() use ($layout) {
                $layout->renderFormGroup('loginEmail', 'Email Address', 'email', 'Enter your email...', true);
                $layout->renderFormGroup('loginPassword', 'Password', 'password', 'Enter your password...', true);
                $layout->renderCheckboxGroup('rememberMe', 'Remember me');
                $layout->renderSubmitButton('Sign In');
            });
        });
    }
}