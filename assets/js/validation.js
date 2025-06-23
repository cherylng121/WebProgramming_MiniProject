// JavaScript Validation Functions
// This file contains client-side validation functions for form inputs

// Email validation
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Phone validation
function validatePhone(phone) {
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone);
}

// Age validation
function validateAge(age) {
    const ageNum = parseInt(age);
    return !isNaN(ageNum) && ageNum > 0 && ageNum < 150;
}

// Required field validation
function validateRequired(value) {
    return value.trim() !== '';
}

// Date validation (future date)
function validateFutureDate(date) {
    const selectedDate = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return selectedDate >= today;
}

// Time validation
function validateTime(time) {
    return time !== '';
}

// Capacity validation
function validateCapacity(capacity) {
    const capNum = parseInt(capacity);
    return !isNaN(capNum) && capNum > 0;
}

// Password strength validation
function validatePassword(password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
    return passwordRegex.test(password);
}

// Username validation
function validateUsername(username) {
    // 3-20 characters, alphanumeric and underscore only
    const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
    return usernameRegex.test(username);
}

// Show error message
function showError(input, message) {
    const formGroup = input.closest('.form-group');
    const errorDiv = formGroup.querySelector('.error-message') || document.createElement('div');
    errorDiv.className = 'error-message text-danger mt-1';
    errorDiv.textContent = message;
    
    if (!formGroup.querySelector('.error-message')) {
        formGroup.appendChild(errorDiv);
    }
    
    input.classList.add('is-invalid');
}

// Remove error message
function removeError(input) {
    const formGroup = input.closest('.form-group');
    const errorDiv = formGroup.querySelector('.error-message');
    
    if (errorDiv) {
        errorDiv.remove();
    }
    
    input.classList.remove('is-invalid');
}

// Real-time validation for login form
function setupLoginValidation() {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) return;

    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    if (usernameInput) {
        usernameInput.addEventListener('blur', function() {
            if (!validateRequired(this.value)) {
                showError(this, 'Username is required');
            } else if (!validateUsername(this.value)) {
                showError(this, 'Username must be 3-20 characters, alphanumeric and underscore only');
            } else {
                removeError(this);
            }
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('blur', function() {
            if (!validateRequired(this.value)) {
                showError(this, 'Password is required');
            } else {
                removeError(this);
            }
        });
    }

    loginForm.addEventListener('submit', function(e) {
        let isValid = true;

        if (usernameInput && !validateRequired(usernameInput.value)) {
            showError(usernameInput, 'Username is required');
            isValid = false;
        }

        if (passwordInput && !validateRequired(passwordInput.value)) {
            showError(passwordInput, 'Password is required');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
}

// Real-time validation for user registration form
function setupUserValidation() {
    const userForm = document.getElementById('userForm');
    if (!userForm) return;

    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const ageInput = document.getElementById('age');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    // Name validation
    if (nameInput) {
        nameInput.addEventListener('blur', function() {
            if (!validateRequired(this.value)) {
                showError(this, 'Name is required');
            } else if (this.value.length < 2) {
                showError(this, 'Name must be at least 2 characters');
            } else {
                removeError(this);
            }
        });
    }

    // Email validation
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            if (!validateRequired(this.value)) {
                showError(this, 'Email is required');
            } else if (!validateEmail(this.value)) {
                showError(this, 'Please enter a valid email address');
            } else {
                removeError(this);
            }
        });
    }

    // Phone validation
    if (phoneInput) {
        phoneInput.addEventListener('blur', function() {
            if (this.value && !validatePhone(this.value)) {
                showError(this, 'Please enter a valid phone number');
            } else {
                removeError(this);
            }
        });
    }

    // Age validation
    if (ageInput) {
        ageInput.addEventListener('blur', function() {
            if (this.value && !validateAge(this.value)) {
                showError(this, 'Please enter a valid age (1-150)');
            } else {
                removeError(this);
            }
        });
    }

    // Username validation
    if (usernameInput) {
        usernameInput.addEventListener('blur', function() {
            if (!validateRequired(this.value)) {
                showError(this, 'Username is required');
            } else if (!validateUsername(this.value)) {
                showError(this, 'Username must be 3-20 characters, alphanumeric and underscore only');
            } else {
                removeError(this);
            }
        });
    }

    // Password validation
    if (passwordInput) {
        passwordInput.addEventListener('blur', function() {
            if (!validateRequired(this.value)) {
                showError(this, 'Password is required');
            } else if (!validatePassword(this.value)) {
                showError(this, 'Password must be at least 8 characters with uppercase, lowercase, and number');
            } else {
                removeError(this);
            }
        });
    }

    // Form submission validation
    userForm.addEventListener('submit', function(e) {
        let isValid = true;

        // Validate all required fields
        const requiredFields = [nameInput, emailInput, usernameInput, passwordInput];
        requiredFields.forEach(field => {
            if (field && !validateRequired(field.value)) {
                showError(field, `${field.name || 'Field'} is required`);
                isValid = false;
            }
        });

        // Validate email format
        if (emailInput && !validateEmail(emailInput.value)) {
            showError(emailInput, 'Please enter a valid email address');
            isValid = false;
        }

        // Validate phone if provided
        if (phoneInput && phoneInput.value && !validatePhone(phoneInput.value)) {
            showError(phoneInput, 'Please enter a valid phone number');
            isValid = false;
        }

        // Validate age if provided
        if (ageInput && ageInput.value && !validateAge(ageInput.value)) {
            showError(ageInput, 'Please enter a valid age (1-150)');
            isValid = false;
        }

        // Validate username format
        if (usernameInput && !validateUsername(usernameInput.value)) {
            showError(usernameInput, 'Username must be 3-20 characters, alphanumeric and underscore only');
            isValid = false;
        }

        // Validate password strength
        if (passwordInput && !validatePassword(passwordInput.value)) {
            showError(passwordInput, 'Password must be at least 8 characters with uppercase, lowercase, and number');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
}

// Real-time validation for event form
function setupEventValidation() {
    const eventForm = document.getElementById('eventForm');
    if (!eventForm) return;

    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const eventDateInput = document.getElementById('event_date');
    const eventTimeInput = document.getElementById('event_time');
    const locationInput = document.getElementById('location');
    const capacityInput = document.getElementById('capacity');

    // Title validation
    if (titleInput) {
        titleInput.addEventListener('blur', function() {
            if (!validateRequired(this.value)) {
                showError(this, 'Event title is required');
            } else if (this.value.length < 5) {
                showError(this, 'Title must be at least 5 characters');
            } else {
                removeError(this);
            }
        });
    }

    // Description validation
    if (descriptionInput) {
        descriptionInput.addEventListener('blur', function() {
            if (!validateRequired(this.value)) {
                showError(this, 'Event description is required');
            } else if (this.value.length < 20) {
                showError(this, 'Description must be at least 20 characters');
            } else {
                removeError(this);
            }
        });
    }

    // Date validation
    if (eventDateInput) {
        eventDateInput.addEventListener('blur', function() {
            if (!validateRequired(this.value)) {
                showError(this, 'Event date is required');
            } else if (!validateFutureDate(this.value)) {
                showError(this, 'Event date must be today or in the future');
            } else {
                removeError(this);
            }
        });
    }

    // Time validation
    if (eventTimeInput) {
        eventTimeInput.addEventListener('blur', function() {
            if (!validateRequired(this.value)) {
                showError(this, 'Event time is required');
            } else {
                removeError(this);
            }
        });
    }

    // Location validation
    if (locationInput) {
        locationInput.addEventListener('blur', function() {
            if (!validateRequired(this.value)) {
                showError(this, 'Event location is required');
            } else {
                removeError(this);
            }
        });
    }

    // Capacity validation
    if (capacityInput) {
        capacityInput.addEventListener('blur', function() {
            if (this.value && !validateCapacity(this.value)) {
                showError(this, 'Capacity must be a positive number');
            } else {
                removeError(this);
            }
        });
    }

    // Form submission validation
    eventForm.addEventListener('submit', function(e) {
        let isValid = true;

        // Validate required fields
        const requiredFields = [titleInput, descriptionInput, eventDateInput, eventTimeInput, locationInput];
        requiredFields.forEach(field => {
            if (field && !validateRequired(field.value)) {
                showError(field, `${field.name || 'Field'} is required`);
                isValid = false;
            }
        });

        // Validate date
        if (eventDateInput && !validateFutureDate(eventDateInput.value)) {
            showError(eventDateInput, 'Event date must be today or in the future');
            isValid = false;
        }

        // Validate capacity if provided
        if (capacityInput && capacityInput.value && !validateCapacity(capacityInput.value)) {
            showError(capacityInput, 'Capacity must be a positive number');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
}

// Search functionality
function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('tbody tr');
        
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// Filter functionality
function setupFilter() {
    const filterSelect = document.getElementById('filterSelect');
    if (!filterSelect) return;

    filterSelect.addEventListener('change', function() {
        const filterValue = this.value;
        const tableRows = document.querySelectorAll('tbody tr');
        
        tableRows.forEach(row => {
            const statusCell = row.querySelector('.status-cell');
            if (statusCell) {
                const status = statusCell.textContent.toLowerCase();
                if (filterValue === '' || status === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    });
}

// Initialize all validation functions when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setupLoginValidation();
    setupUserValidation();
    setupEventValidation();
    setupSearch();
    setupFilter();
});

// Password strength indicator
function checkPasswordStrength(password) {
    let strength = 0;
    const feedback = [];

    if (password.length >= 8) strength++;
    else feedback.push('At least 8 characters');

    if (/[a-z]/.test(password)) strength++;
    else feedback.push('Lowercase letter');

    if (/[A-Z]/.test(password)) strength++;
    else feedback.push('Uppercase letter');

    if (/\d/.test(password)) strength++;
    else feedback.push('Number');

    if (/[@$!%*?&]/.test(password)) strength++;
    else feedback.push('Special character');

    return { strength, feedback };
}

// Show password strength
function showPasswordStrength(password) {
    const strengthDiv = document.getElementById('passwordStrength');
    if (!strengthDiv) return;

    const result = checkPasswordStrength(password);
    const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
    const strengthClass = ['danger', 'danger', 'warning', 'info', 'success', 'success'];

    if (password.length === 0) {
        strengthDiv.innerHTML = '';
        return;
    }

    strengthDiv.innerHTML = `
        <div class="mt-1">
            <small class="text-${strengthClass[result.strength - 1]}">
                Strength: ${strengthText[result.strength - 1]}
            </small>
            ${result.feedback.length > 0 ? '<br><small class="text-muted">' + result.feedback.join(', ') + '</small>' : ''}
        </div>
    `;
} 