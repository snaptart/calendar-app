// Authentication Page JavaScript - Ice Time Management
// Location: frontend/assets/js/auth-page.js
// 
// Page-specific authentication functionality (requires core.js to be loaded first)

(function() {
    'use strict';
    
    // Check if core utilities are available
    if (!window.IceTimeApp) {
        console.error('Core utilities not loaded. Please ensure core.js is loaded first.');
        return;
    }
    
    const { EventBus, APIClient, AuthGuard, UIManager } = window.IceTimeApp;
    
    // =============================================================================
    // AUTHENTICATION UTILITIES
    // =============================================================================
    
    const AuthUtils = {
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
    // LOGIN FORM HANDLER
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
            const email = AuthUtils.getFieldValue(form, ['email', 'loginEmail', '[name="email"]']);
            const password = AuthUtils.getFieldValue(form, ['password', 'loginPassword', '[name="password"]']);
            const rememberMe = AuthUtils.getCheckboxValue(form, ['rememberMe', '[name="rememberMe"]']);
            
            const submitButton = form.querySelector('button[type="submit"]') || form.querySelector('.auth-button');
            
            console.log('Login attempt:', { 
                email: email ? 'present' : 'missing', 
                password: password ? 'present' : 'missing' 
            });
            
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
                
                const response = await APIClient.login(email, password, rememberMe);
                
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
        }
    };
    
    // =============================================================================
    // REGISTRATION FORM HANDLER
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
            const name = AuthUtils.getFieldValue(form, ['name', 'registerName', '[name="name"]']);
            const email = AuthUtils.getFieldValue(form, ['email', 'registerEmail', '[name="email"]']);
            const password = AuthUtils.getFieldValue(form, ['password', 'registerPassword', '[name="password"]']);
            const confirmPassword = AuthUtils.getFieldValue(form, ['confirmPassword', '[name="confirmPassword"]']);
            
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
                
                const response = await APIClient.register(name, email, password);
                
                if (response.success) {
                    AuthUtils.showMessage('Account created successfully! You can now sign in.', 'success');
                    
                    // Switch to login tab after successful registration
                    setTimeout(() => {
                        const loginTab = document.querySelector('.auth-tab[data-tab="login"]');
                        if (loginTab) {
                            loginTab.click();
                            
                            // Pre-fill email in login form
                            const emailFields = ['#loginEmail', '[name="email"]'];
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
        }
    };
    
    // =============================================================================
    // AUTH GUARD - Check if user is already logged in
    // =============================================================================
    
    const AuthPageGuard = {
        async init() {
            try {
                const response = await APIClient.checkAuth();
                
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
    // APPLICATION CONTROLLER
    // =============================================================================
    
    const AuthApp = {
        async init() {
            console.log('Initializing Authentication System...');
            
            // Check if user is already logged in
            const isAuthenticated = await AuthPageGuard.init();
            
            if (!isAuthenticated) {
                // Initialize UI components
                TabManager.init();
                
                // Wait a moment for DOM to be fully ready
                setTimeout(() => {
                    LoginForm.init();
                    RegisterForm.init();
                    
                    // Focus on first available email field
                    const emailFields = ['#loginEmail', '[name="email"]'];
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
        },
        
        destroy() {
            console.log('Authentication system destroyed');
        }
    };
    
    // =============================================================================
    // INITIALIZATION
    // =============================================================================
    
    // Export to global scope
    window.IceTimeApp.AuthApp = AuthApp;
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            AuthApp.init();
        });
    } else {
        AuthApp.init();
    }
    
    // Handle browser back/forward navigation
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            // Page was loaded from cache, check auth status again
            AuthPageGuard.init();
        }
    });
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        AuthApp.destroy();
    });
    
})();