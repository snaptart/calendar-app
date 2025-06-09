<?php
/**
 * LoginForm Component - Specialized form for authentication
 * Location: frontend/components/forms/LoginForm.php
 * 
 * Pre-configured form component specifically for login/registration
 */

require_once __DIR__ . '/BaseForm.php';

class LoginForm extends BaseForm {
    
    /**
     * Constructor with login-specific defaults
     */
    public function __construct($config = []) {
        // Login form specific configuration
        $loginConfig = [
            'formId' => 'loginForm',
            'method' => 'POST',
            'class' => 'auth-form active',
            'validation' => [
                'clientSide' => true,
                'realTime' => false,
                'showErrors' => true
            ],
            'submission' => [
                'ajax' => true,
                'url' => '../../backend/api.php',
                'loadingText' => 'Signing in...',
                'resetOnSuccess' => false
            ],
            'csrf' => [
                'enabled' => false // Handled by backend API
            ],
            'messages' => [
                'required' => 'This field is required',
                'email' => 'Please enter a valid email address',
                'minlength' => 'Password must be at least 6 characters'
            ]
        ];
        
        // Merge with provided config
        $mergedConfig = array_merge_recursive($loginConfig, $config);
        
        parent::__construct($mergedConfig);
        
        // Set up login form fields
        $this->setupLoginFields();
        
        // Add form-specific validation rules
        $this->setupValidationRules();
        
        // Add form-specific JavaScript
        $this->addLoginFormJs();
    }
    
    /**
     * Set up default login form fields
     */
    private function setupLoginFields() {
        // Hidden action field
        $this->addHidden('action', 'login');
        
        // Email field
        $this->addEmail('email', 'Email Address', [
            'required' => true,
            'placeholder' => 'Enter your email...',
            'autocomplete' => 'email',
            'validation' => ['required', 'email']
        ]);
        
        // Password field
        $this->addPassword('password', 'Password', [
            'required' => true,
            'placeholder' => 'Enter your password...',
            'autocomplete' => 'current-password',
            'validation' => ['required']
        ]);
        
        // Remember me checkbox
        $this->addCheckbox('rememberMe', 'Remember me for 30 days');
        
        // Submit button
        $this->addSubmit('Sign In', [
            'class' => 'auth-button btn-primary'
        ]);
    }
    
    /**
     * Set up validation rules
     */
    private function setupValidationRules() {
        $this->addRule('email', 'required')
             ->addRule('email', 'email')
             ->addRule('password', 'required')
             ->addRule('password', 'minlength', 1); // Allow any password length for login
    }
    
    /**
     * Add login-specific JavaScript
     */
    private function addLoginFormJs() {
        $this->config['submission']['beforeSubmit'] = 'loginBeforeSubmit';
        $this->config['submission']['onSuccess'] = 'loginOnSuccess';
        $this->config['submission']['onError'] = 'loginOnError';
        
        $this->addInlineJS('
            // Login form specific handlers
            function loginBeforeSubmit(form) {
                // Clear any existing messages
                clearAuthMessages();
                
                // Validate required fields
                const email = form.querySelector("[name=\"email\"]").value.trim();
                const password = form.querySelector("[name=\"password\"]").value;
                
                if (!email || !password) {
                    showAuthMessage("Please fill in all fields", "error");
                    return false;
                }
                
                return true;
            }
            
            function loginOnSuccess(data, form) {
                if (data.success) {
                    showAuthMessage("Login successful! Redirecting...", "success");
                    
                    // Redirect after short delay
                    setTimeout(function() {
                        const returnUrl = new URLSearchParams(window.location.search).get("return");
                        if (returnUrl) {
                            window.location.href = returnUrl;
                        } else {
                            window.location.href = "./calendar.php";
                        }
                    }, 1000);
                } else {
                    showAuthMessage(data.error || "Login failed", "error");
                }
            }
            
            function loginOnError(data, form) {
                const errorMessage = data.error || data.message || "Login failed. Please try again.";
                showAuthMessage(errorMessage, "error");
            }
            
            function clearAuthMessages() {
                const errorEl = document.getElementById("errorMessage");
                const successEl = document.getElementById("successMessage");
                
                if (errorEl) errorEl.style.display = "none";
                if (successEl) successEl.style.display = "none";
            }
            
            function showAuthMessage(message, type) {
                clearAuthMessages();
                
                const elementId = type === "error" ? "errorMessage" : "successMessage";
                const element = document.getElementById(elementId);
                
                if (element) {
                    element.textContent = message;
                    element.style.display = "block";
                    
                    // Auto-hide after 5 seconds
                    setTimeout(function() {
                        element.style.display = "none";
                    }, 5000);
                }
            }
        ');
    }
    
    /**
     * Create registration form
     */
    public static function createRegistrationForm($config = []) {
        // Registration form specific configuration
        $regConfig = [
            'formId' => 'registerForm',
            'class' => 'auth-form',
            'submission' => [
                'ajax' => true,
                'url' => '../../backend/api.php',
                'loadingText' => 'Creating account...',
                'resetOnSuccess' => true
            ]
        ];
        
        $form = new self(array_merge($regConfig, $config));
        
        // Clear default login fields and add registration fields
        $form->fields = [];
        $form->setupRegistrationFields();
        $form->addRegistrationFormJs();
        
        return $form;
    }
    
    /**
     * Set up registration form fields
     */
    private function setupRegistrationFields() {
        // Hidden action field
        $this->addHidden('action', 'register');
        
        // Full name field
        $this->addText('name', 'Full Name', [
            'required' => true,
            'placeholder' => 'Enter your full name...',
            'autocomplete' => 'name',
            'validation' => ['required', ['minlength' => 2]]
        ]);
        
        // Email field
        $this->addEmail('email', 'Email Address', [
            'required' => true,
            'placeholder' => 'Enter your email...',
            'autocomplete' => 'email',
            'validation' => ['required', 'email']
        ]);
        
        // Password field
        $this->addPassword('password', 'Password', [
            'required' => true,
            'placeholder' => 'Create a password...',
            'autocomplete' => 'new-password',
            'minlength' => 6,
            'validation' => ['required', ['minlength' => 6]]
        ]);
        
        // Confirm password field
        $this->addPassword('confirmPassword', 'Confirm Password', [
            'required' => true,
            'placeholder' => 'Confirm your password...',
            'autocomplete' => 'new-password',
            'minlength' => 6,
            'validation' => ['required', ['minlength' => 6]]
        ]);
        
        // Submit button
        $this->addSubmit('Create Account', [
            'class' => 'auth-button btn-primary'
        ]);
    }
    
    /**
     * Add registration-specific JavaScript
     */
    private function addRegistrationFormJs() {
        $this->config['submission']['beforeSubmit'] = 'registerBeforeSubmit';
        $this->config['submission']['onSuccess'] = 'registerOnSuccess';
        $this->config['submission']['onError'] = 'registerOnError';
        
        $this->addInlineJS('
            // Registration form specific handlers
            function registerBeforeSubmit(form) {
                // Clear any existing messages
                clearAuthMessages();
                
                // Get form values
                const name = form.querySelector("[name=\"name\"]").value.trim();
                const email = form.querySelector("[name=\"email\"]").value.trim();
                const password = form.querySelector("[name=\"password\"]").value;
                const confirmPassword = form.querySelector("[name=\"confirmPassword\"]").value;
                
                // Validate required fields
                if (!name || !email || !password || !confirmPassword) {
                    showAuthMessage("Please fill in all fields", "error");
                    return false;
                }
                
                // Validate name length
                if (name.length < 2) {
                    showAuthMessage("Please enter a valid name (at least 2 characters)", "error");
                    return false;
                }
                
                // Validate email format
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    showAuthMessage("Please enter a valid email address", "error");
                    return false;
                }
                
                // Validate password length
                if (password.length < 6) {
                    showAuthMessage("Password must be at least 6 characters long", "error");
                    return false;
                }
                
                // Validate password confirmation
                if (password !== confirmPassword) {
                    showAuthMessage("Passwords do not match", "error");
                    return false;
                }
                
                return true;
            }
            
            function registerOnSuccess(data, form) {
                if (data.success) {
                    showAuthMessage("Account created successfully! You can now sign in.", "success");
                    
                    // Switch to login tab and pre-fill email
                    setTimeout(function() {
                        const loginTab = document.querySelector(".auth-tab[data-tab=\"login\"]");
                        if (loginTab) {
                            loginTab.click();
                            
                            const loginEmail = document.getElementById("loginEmail");
                            const registerEmail = document.getElementById("registerEmail");
                            if (loginEmail && registerEmail) {
                                loginEmail.value = registerEmail.value;
                            }
                        }
                    }, 2000);
                } else {
                    showAuthMessage(data.error || "Registration failed", "error");
                }
            }
            
            function registerOnError(data, form) {
                const errorMessage = data.error || data.message || "Registration failed. Please try again.";
                showAuthMessage(errorMessage, "error");
            }
        ');
    }
    
    /**
     * Render login form with tabs
     */
    public function renderWithTabs() {
        ?>
        <div class="auth-tabs">
            <button class="auth-tab active" data-tab="login">Sign In</button>
            <button class="auth-tab" data-tab="register">Sign Up</button>
        </div>
        
        <div id="errorMessage" class="error-message" style="display: none;"></div>
        <div id="successMessage" class="success-message" style="display: none;"></div>
        
        <?php
        // Render login form
        $this->render();
        
        // Create and render registration form
        $registerForm = self::createRegistrationForm();
        $registerForm->render();
        
        // Add tab switching JavaScript
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
                        
                        // Clear messages when switching tabs
                        clearAuthMessages();
                    });
                });
            });
        ');
    }
    
    /**
     * Create a simple login form (no registration)
     */
    public static function createSimpleLogin($config = []) {
        return new self($config);
    }
    
    /**
     * Quick render method for login forms
     */
    public static function renderLoginForm($config = []) {
        $form = new self($config);
        $form->render();
    }
    
    /**
     * Quick render method for login form with tabs
     */
    public static function renderLoginWithTabs($config = []) {
        $form = new self($config);
        $form->renderWithTabs();
    }
}