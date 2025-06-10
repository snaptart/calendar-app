// Authentication System for Collaborative Calendar
// Location: frontend/js/auth.js

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
        errorEl.style.display = 'none';
        successEl.style.display = 'none';
        
        // Show appropriate message
        if (type === 'error') {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        } else {
            successEl.textContent = message;
            successEl.style.display = 'block';
        }
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            errorEl.style.display = 'none';
            successEl.style.display = 'none';
        }, 5000);
    },
    
    /**
     * Set loading state for button
     */
    setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.classList.add('loading');
        } else {
            button.disabled = false;
            button.classList.remove('loading');
        }
    },
    
    /**
     * Redirect to calendar page
     */
    redirectToCalendar() {
        window.location.href = './index.html';
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
// LOGIN FORM HANDLER
// =============================================================================

const LoginForm = {
    init() {
        const form = document.getElementById('loginForm');
        if (!form) return;
        
        form.addEventListener('submit', this.handleSubmit.bind(this));
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const email = document.getElementById('loginEmail').value.trim();
        const password = document.getElementById('loginPassword').value;
        const rememberMe = document.getElementById('rememberMe').checked;
        const submitButton = e.target.querySelector('button[type="submit"]');
        
        // Validate input
        if (!AuthUtils.validateEmail(email)) {
            AuthUtils.showMessage('Please enter a valid email address');
            return;
        }
        
        if (!password) {
            AuthUtils.showMessage('Please enter your password');
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
    }
};

// =============================================================================
// REGISTRATION FORM HANDLER
// =============================================================================

const RegisterForm = {
    init() {
        const form = document.getElementById('registerForm');
        if (!form) return;
        
        form.addEventListener('submit', this.handleSubmit.bind(this));
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const name = document.getElementById('registerName').value.trim();
        const email = document.getElementById('registerEmail').value.trim();
        const password = document.getElementById('registerPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const submitButton = e.target.querySelector('button[type="submit"]');
        
        // Validate input
        if (!name || name.length < 2) {
            AuthUtils.showMessage('Please enter a valid name (at least 2 characters)');
            return;
        }
        
        if (!AuthUtils.validateEmail(email)) {
            AuthUtils.showMessage('Please enter a valid email address');
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
                    document.querySelector('.auth-tab[data-tab="login"]').click();
                    
                    // Pre-fill email in login form
                    document.getElementById('loginEmail').value = email;
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
// APPLICATION INITIALIZATION
// =============================================================================

const AuthApp = {
    async init() {
        console.log('Initializing Authentication System...');
        
        // Check if user is already logged in
        const isAuthenticated = await AuthGuard.init();
        
        if (!isAuthenticated) {
            // Initialize UI components
            TabManager.init();
            LoginForm.init();
            RegisterForm.init();
            
            // Focus on email field
            document.getElementById('loginEmail')?.focus();
            
            console.log('Authentication system initialized');
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    AuthApp.init();
});

// Handle browser back/forward navigation
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        // Page was loaded from cache, check auth status again
        AuthGuard.init();
    }
});

// ENHANCED: auth.js - Form Component Registration
// Add this to the END of the existing auth.js file

// =============================================================================
// COMPONENT REGISTRATION
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
			const submitUrl = element.dataset.submitUrl;
			const submitMethod = element.dataset.submitMethod || 'POST';
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
					const rules = field.dataset.validate.split('|');
					const value = field.value.trim();
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
				submitButton.disabled = true;
				submitButton.textContent = 'Processing...';
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
					submitButton.disabled = false;
					submitButton.textContent = submitButton.dataset.originalText || 'Submit';
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
* Validate individual rule
*/
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

/**
* Get error message for validation rule
*/
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
		},

/**
* Destroy form component
*/
		destroy: function(instance) {
			// Cleanup event listeners if needed
		}
	});
}