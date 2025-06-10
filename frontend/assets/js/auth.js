// Authentication System for Collaborative Calendar
// Location: frontend/assets/js/auth.js
// FIXED VERSION - Updated to match PHP component structure

// =============================================================================
// AUTHENTICATION UTILITIES
// =============================================================================

const AuthUtils = {
    apiEndpoint: '../../backend/api.php',
    
    /**
     * Make authenticated API request
     */
    async makeRequest(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                credentials: 'include', // Include cookies for session
                ...options
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Auth API Request failed:', error);
            throw error;
        }
    },
    
    /**
     * Validate email format
     */
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    /**
     * Validate password strength
     */
    validatePassword(password) {
        return password && password.length >= 6;
    },
    
    /**
     * Show message to user
     */
    showMessage(message, type = 'error') {
        const errorEl = document.getElementById('errorMessage');
        const successEl = document.getElementById('successMessage');
        
        // Hide all messages first
        if (errorEl) errorEl.style.display = 'none';
        if (successEl) successEl.style.display = 'none';
        
        // Show appropriate message
        if (type === 'error' && errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        } else if (type === 'success' && successEl) {
            successEl.textContent = message;
            successEl.style.display = 'block';
        }
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (errorEl) errorEl.style.display = 'none';
            if (successEl) successEl.style.display = 'none';
        }, 5000);
    },
    
    /**
     * Set loading state for button
     */
    setButtonLoading(button, isLoading) {
        if (!button) return;
        
        if (isLoading) {
            button.disabled = true;
            button.classList.add('loading');
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.textContent;
            }
            button.textContent = 'Processing...';
        } else {
            button.disabled = false;
            button.classList.remove('loading');
            if (button.dataset.originalText) {
                button.textContent = button.dataset.originalText;
            }
        }
    },
    
    /**
     * Redirect to calendar page
     */
    redirectToCalendar() {
        window.location.href = './calendar.php';
    },
    
    /**
     * Get form data safely
     */
    getFormData(formId) {
        const form = document.getElementById(formId);
        if (!form) {
            console.error(`Form with ID ${formId} not found`);
            return null;
        }
        
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    },
    
    /**
     * Get element value safely
     */
    getElementValue(selector, defaultValue = '') {
        const element = document.querySelector(selector);
        return element ? element.value.trim() : defaultValue;
    },
    
    /**
     * Get checkbox state safely
     */
    getCheckboxState(selector, defaultValue = false) {
        const element = document.querySelector(selector);
        return element ? element.checked : defaultValue;
    }
};

// =============================================================================
// AUTHENTICATION API CLIENT
// =============================================================================

const AuthAPI = {
    /**
     * Login user
     */
    async login(email, password, rememberMe = false) {
        return AuthUtils.makeRequest(AuthUtils.apiEndpoint, {
            method: 'POST',
            body: JSON.stringify({
                action: 'login',
                email,
                password,
                rememberMe
            })
        });
    },
    
    /**
     * Register new user
     */
    async register(name, email, password) {
        return AuthUtils.makeRequest(AuthUtils.apiEndpoint, {
            method: 'POST',
            body: JSON.stringify({
                action: 'register',
                name,
                email,
                password
            })
        });
    },
    
    /**
     * Check if user is authenticated
     */
    async checkAuth() {
        return AuthUtils.makeRequest(`${AuthUtils.apiEndpoint}?action=check_auth`);
    },
    
    /**
     * Logout user
     */
    async logout() {
        return AuthUtils.makeRequest(AuthUtils.apiEndpoint, {
            method: 'POST',
            body: JSON.stringify({
                action: 'logout'
            })
        });
    }
};

// =============================================================================
// TAB MANAGEMENT
// =============================================================================

const TabManager = {
    init() {
        const tabs = document.querySelectorAll('.auth-tab');
        const forms = document.querySelectorAll('.auth-form');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetTab = tab.dataset.tab;
                
                // Update tab states
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Update form states
                forms.forEach(form => {
                    form.classList.remove('active');
                    if (form.id === `${targetTab}Form`) {
                        form.classList.add('active');
                    }
                });
                
                // Clear any messages
                AuthUtils.showMessage('', 'clear');
            });
        });
    }
};

// =============================================================================
// ENHANCED LOGIN FORM HANDLER
// =============================================================================

const LoginForm = {
    init() {
        // Try multiple possible form selectors
        const possibleSelectors = ['#loginForm', '[data-component-id="loginForm"]', 'form.auth-form.active'];
        let form = null;
        
        for (const selector of possibleSelectors) {
            form = document.querySelector(selector);
            if (form) break;
        }
        
        if (!form) {
            console.warn('Login form not found with any selector');
            return;
        }
        
        console.log('Login form found:', form.id || form.className);
        form.addEventListener('submit', this.handleSubmit.bind(this));
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        
        // Try to get field values using multiple strategies
        const email = this.getFieldValue(form, ['email', 'loginEmail', '[name="email"]', '#loginForm_email']);
        const password = this.getFieldValue(form, ['password', 'loginPassword', '[name="password"]', '#loginForm_password']);
        const rememberMe = this.getCheckboxValue(form, ['rememberMe', '[name="rememberMe"]', '#loginForm_rememberMe']);
        
        const submitButton = form.querySelector('button[type="submit"]') || form.querySelector('.auth-button');
        
        console.log('Login attempt:', { email: email ? 'present' : 'missing', password: password ? 'present' : 'missing' });
        
        // Validate input
        if (!email) {
            AuthUtils.showMessage('Email field not found or empty');
            return;
        }
        
        if (!AuthUtils.validateEmail(email)) {
            AuthUtils.showMessage('Please enter a valid email address');
            return;
        }
        
        if (!password) {
            AuthUtils.showMessage('Password field not found or empty');
            return;
        }
        
        try {
            AuthUtils.setButtonLoading(submitButton, true);
            
            const response = await AuthAPI.login(email, password, rememberMe);
            
            if (response.success) {
                AuthUtils.showMessage('Login successful! Redirecting...', 'success');
                
                // Redirect after a short delay
                setTimeout(() => {
                    AuthUtils.redirectToCalendar();
                }, 1000);
            } else {
                AuthUtils.showMessage(response.error || 'Login failed');
            }
            
        } catch (error) {
            console.error('Login error:', error);
            AuthUtils.showMessage(error.message || 'Login failed. Please try again.');
        } finally {
            AuthUtils.setButtonLoading(submitButton, false);
        }
    },
    
    /**
     * Get field value using multiple selector strategies
     */
    getFieldValue(form, selectors) {
        for (const selector of selectors) {
            // Try as ID first
            let element = document.getElementById(selector);
            
            // Try as CSS selector within form
            if (!element) {
                element = form.querySelector(selector);
            }
            
            // Try as CSS selector globally
            if (!element) {
                element = document.querySelector(selector);
            }
            
            if (element && element.value) {
                return element.value.trim();
            }
        }
        
        console.warn('Field not found with selectors:', selectors);
        return '';
    },
    
    /**
     * Get checkbox value using multiple selector strategies
     */
    getCheckboxValue(form, selectors) {
        for (const selector of selectors) {
            let element = document.getElementById(selector);
            
            if (!element) {
                element = form.querySelector(selector);
            }
            
            if (!element) {
                element = document.querySelector(selector);
            }
            
            if (element && element.type === 'checkbox') {
                return element.checked;
            }
        }
        
        return false;
    }
};

// =============================================================================
// ENHANCED REGISTRATION FORM HANDLER
// =============================================================================

const RegisterForm = {
    init() {
        // Try multiple possible form selectors
        const possibleSelectors = ['#registerForm', '[data-component-id="registerForm"]', 'form.auth-form:not(.active)'];
        let form = null;
        
        for (const selector of possibleSelectors) {
            form = document.querySelector(selector);
            if (form) break;
        }
        
        if (!form) {
            console.warn('Register form not found');
            return;
        }
        
        console.log('Register form found:', form.id || form.className);
        form.addEventListener('submit', this.handleSubmit.bind(this));
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        
        // Try to get field values using multiple strategies
        const name = this.getFieldValue(form, ['name', 'registerName', '[name="name"]', '#registerForm_name']);
        const email = this.getFieldValue(form, ['email', 'registerEmail', '[name="email"]', '#registerForm_email']);
        const password = this.getFieldValue(form, ['password', 'registerPassword', '[name="password"]', '#registerForm_password']);
        const confirmPassword = this.getFieldValue(form, ['confirmPassword', '[name="confirmPassword"]', '#registerForm_confirmPassword']);
        
        const submitButton = form.querySelector('button[type="submit"]') || form.querySelector('.auth-button');
        
        console.log('Registration attempt:', {
            name: name ? 'present' : 'missing',
            email: email ? 'present' : 'missing',
            password: password ? 'present' : 'missing',
            confirmPassword: confirmPassword ? 'present' : 'missing'
        });
        
        // Validate input
        if (!name || name.length < 2) {
            AuthUtils.showMessage('Please enter a valid name (at least 2 characters)');
            return;
        }
        
        if (!email) {
            AuthUtils.showMessage('Email field not found or empty');
            return;
        }
        
        if (!AuthUtils.validateEmail(email)) {
            AuthUtils.showMessage('Please enter a valid email address');
            return;
        }
        
        if (!password) {
            AuthUtils.showMessage('Password field not found or empty');
            return;
        }
        
        if (!AuthUtils.validatePassword(password)) {
            AuthUtils.showMessage('Password must be at least 6 characters long');
            return;
        }
        
        if (password !== confirmPassword) {
            AuthUtils.showMessage('Passwords do not match');
            return;
        }
        
        try {
            AuthUtils.setButtonLoading(submitButton, true);
            
            const response = await AuthAPI.register(name, email, password);
            
            if (response.success) {
                AuthUtils.showMessage('Account created successfully! You can now sign in.', 'success');
                
                // Switch to login tab after successful registration
                setTimeout(() => {
                    const loginTab = document.querySelector('.auth-tab[data-tab="login"]');
                    if (loginTab) {
                        loginTab.click();
                        
                        // Pre-fill email in login form
                        const emailFields = ['#loginEmail', '[name="email"]', '#loginForm_email'];
                        for (const selector of emailFields) {
                            const emailField = document.querySelector(selector);
                            if (emailField) {
                                emailField.value = email;
                                break;
                            }
                        }
                    }
                }, 1500);
            } else {
                AuthUtils.showMessage(response.error || 'Registration failed');
            }
            
        } catch (error) {
            console.error('Registration error:', error);
            AuthUtils.showMessage(error.message || 'Registration failed. Please try again.');
        } finally {
            AuthUtils.setButtonLoading(submitButton, false);
        }
    },
    
    /**
     * Get field value using multiple selector strategies
     */
    getFieldValue(form, selectors) {
        for (const selector of selectors) {
            let element = document.getElementById(selector);
            
            if (!element) {
                element = form.querySelector(selector);
            }
            
            if (!element) {
                element = document.querySelector(selector);
            }
            
            if (element && element.value) {
                return element.value.trim();
            }
        }
        
        console.warn('Field not found with selectors:', selectors);
        return '';
    }
};

// =============================================================================
// AUTH GUARD - Check if user is already logged in
// =============================================================================

const AuthGuard = {
    async init() {
        try {
            const response = await AuthAPI.checkAuth();
            
            if (response.authenticated) {
                // User is already logged in, redirect to calendar
                console.log('User already authenticated:', response.user);
                AuthUtils.redirectToCalendar();
                return true;
            }
        } catch (error) {
            console.log('User not authenticated or session expired');
        }
        
        return false;
    }
};

// =============================================================================
// ENHANCED APPLICATION INITIALIZATION
// =============================================================================

const AuthApp = {
    async init() {
        console.log('Initializing Authentication System...');
        
        // Check if user is already logged in
        const isAuthenticated = await AuthGuard.init();
        
        if (!isAuthenticated) {
            // Initialize UI components
            TabManager.init();
            
            // Wait a moment for DOM to be fully ready
            setTimeout(() => {
                LoginForm.init();
                RegisterForm.init();
                
                // Focus on first available email field
                const emailFields = ['#loginEmail', '[name="email"]', '#loginForm_email'];
                for (const selector of emailFields) {
                    const emailField = document.querySelector(selector);
                    if (emailField) {
                        emailField.focus();
                        break;
                    }
                }
                
                console.log('Authentication system initialized');
            }, 100);
        }
    }
};

// =============================================================================
// INITIALIZATION
// =============================================================================

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        AuthApp.init();
    });
} else {
    // DOM already loaded
    AuthApp.init();
}

// Handle browser back/forward navigation
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        // Page was loaded from cache, check auth status again
        AuthGuard.init();
    }
});

// =============================================================================
// COMPONENT REGISTRATION (Enhanced)
// =============================================================================

/**
 * Register Form Component with the Universal Component Registry
 */
if (typeof ComponentRegistry !== 'undefined') {
    ComponentRegistry.register('form', {
        /**
         * Initialize form component from data attributes
         */
        init: function(element, data) {
            console.log('Initializing form component:', data.componentId);

            // Parse configuration from data attributes
            const validation = DataAttributeUtils.parseBoolean(element.dataset.validation, true);
            const submitUrl = element.dataset.submitUrl || element.action;
            const submitMethod = element.dataset.submitMethod || element.method || 'POST';
            const permissions = DataAttributeUtils.parseJSON(element.dataset.permissions, {});

            // Set up form submission
            element.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmit(element, {
                    url: submitUrl,
                    method: submitMethod,
                    validation: validation
                });
            });

            // Set up field validation if enabled
            if (validation) {
                this.setupFieldValidation(element);
            }

            const instance = {
                element: element,
                validation: validation,
                permissions: permissions,

                // Public methods
                validate: function() {
                    return validation ? this.validateAllFields() : true;
                },

                validateAllFields: function() {
                    const fields = element.querySelectorAll('[data-validate]');
                    let isValid = true;

                    fields.forEach(field => {
                        if (!this.validateField(field)) {
                            isValid = false;
                        }
                    });

                    return isValid;
                },

                validateField: function(field) {
                    const rules = field.dataset.validate ? field.dataset.validate.split('|') : [];
                    const value = field.value ? field.value.trim() : '';
                    const errorElement = document.getElementById(field.id + 'Error') ||
                        field.parentNode.querySelector('.form-error');

                    // Clear previous errors
                    this.clearFieldError(field, errorElement);

                    for (let rule of rules) {
                        const [ruleName, ruleValue] = rule.split(':');

                        if (!this.validateRule(value, ruleName, ruleValue)) {
                            this.showFieldError(field, errorElement, this.getErrorMessage(ruleName, ruleValue));
                            return false;
                        }
                    }

                    return true;
                },

                showFieldError: function(field, errorElement, message) {
                    field.classList.add('error');
                    if (errorElement) {
                        errorElement.textContent = message;
                        errorElement.style.display = 'block';
                    }
                },

                clearFieldError: function(field, errorElement) {
                    field.classList.remove('error');
                    if (errorElement) {
                        errorElement.style.display = 'none';
                    }
                },

                reset: function() {
                    element.reset();
                    this.clearAllErrors();
                },

                clearAllErrors: function() {
                    const errorFields = element.querySelectorAll('.error');
                    const errorElements = element.querySelectorAll('.form-error');

                    errorFields.forEach(field => field.classList.remove('error'));
                    errorElements.forEach(el => el.style.display = 'none');
                },

                submit: function() {
                    if (this.validate()) {
                        element.dispatchEvent(new Event('submit'));
                    }
                },

                validateRule: function(value, ruleName, ruleValue) {
                    switch (ruleName) {
                        case 'required':
                            return value.length > 0;
                        case 'email':
                            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                        case 'minlength':
                            return value.length >= parseInt(ruleValue);
                        case 'maxlength':
                            return value.length <= parseInt(ruleValue);
                        case 'min':
                            return parseFloat(value) >= parseFloat(ruleValue);
                        case 'max':
                            return parseFloat(value) <= parseFloat(ruleValue);
                        case 'pattern':
                            return new RegExp(ruleValue).test(value);
                        default:
                            return true;
                    }
                },

                getErrorMessage: function(ruleName, ruleValue) {
                    const messages = {
                        required: 'This field is required',
                        email: 'Please enter a valid email address',
                        minlength: `Must be at least ${ruleValue} characters`,
                        maxlength: `Must be no more than ${ruleValue} characters`,
                        min: `Value must be at least ${ruleValue}`,
                        max: `Value must be no more than ${ruleValue}`,
                        pattern: 'Please match the required format'
                    };

                    return messages[ruleName] || 'Invalid value';
                }
            };

            return instance;
        },

        /**
         * Setup field validation
         */
        setupFieldValidation: function(form) {
            const fields = form.querySelectorAll('[data-validate]');

            fields.forEach(field => {
                // Real-time validation on blur
                field.addEventListener('blur', (e) => {
                    const instance = ComponentRegistry.getInstance(form.id);
                    if (instance && instance.instance.validateField) {
                        instance.instance.validateField(e.target);
                    }
                });

                // Clear error on input
                field.addEventListener('input', (e) => {
                    if (e.target.classList.contains('error')) {
                        const errorElement = document.getElementById(e.target.id + 'Error') ||
                            e.target.parentNode.querySelector('.form-error');
                        const instance = ComponentRegistry.getInstance(form.id);
                        if (instance && instance.instance.clearFieldError) {
                            instance.instance.clearFieldError(e.target, errorElement);
                        }
                    }
                });
            });
        },

        /**
         * Handle form submission
         */
        handleFormSubmit: function(form, config) {
            const instance = ComponentRegistry.getInstance(form.id);

            if (!instance || !instance.instance.validate()) {
                return;
            }

            const submitButton = form.querySelector('[type="submit"]');
            const formData = new FormData(form);

            // Show loading state
            if (submitButton) {
                AuthUtils.setButtonLoading(submitButton, true);
            }

            // Prepare request options
            const requestOptions = {
                method: config.method,
                credentials: 'include'
            };

            if (config.method === 'POST') {
                requestOptions.body = formData;
            }

            // Make request
            fetch(config.url, requestOptions)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        this.handleFormSuccess(form, data);
                    } else {
                        this.handleFormError(form, data.error || 'Form submission failed');
                    }
                })
                .catch(error => {
                    console.error('Form submission error:', error);
                    this.handleFormError(form, error.message);
                })
                .finally(() => {
                    // Reset loading state
                    if (submitButton) {
                        AuthUtils.setButtonLoading(submitButton, false);
                    }
                });
        },

        /**
         * Handle successful form submission
         */
        handleFormSuccess: function(form, data) {
            // Show success message
            AuthUtils.showMessage(data.message || 'Form submitted successfully', 'success');

            // Emit success event
            form.dispatchEvent(new CustomEvent('form:success', {
                detail: data
            }));

            // Handle redirects for auth forms
            if (form.id === 'loginForm' && data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            }
        },

        /**
         * Handle form submission error
         */
        handleFormError: function(form, error) {
            AuthUtils.showMessage(error, 'error');

            // Emit error event
            form.dispatchEvent(new CustomEvent('form:error', {
                detail: { error: error }
            }));
        },

        /**
         * Destroy form component
         */
        destroy: function(instance) {
            // Cleanup event listeners if needed
        }
    });
}