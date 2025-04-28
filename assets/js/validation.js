document.addEventListener('DOMContentLoaded', function() {
    // Registration form validation
    const registrationForm = document.querySelector('.registration-form');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const weddingDate = document.getElementById('wedding_date').value;

            let isValid = true;
            const errors = [];

            // Password validation
            if (password.length < 8) {
                errors.push('Password must be at least 8 characters long');
                isValid = false;
            }

            if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}/.test(password)) {
                errors.push('Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character');
                isValid = false;
            }

            // Confirm password validation
            if (password !== confirmPassword) {
                errors.push('Passwords do not match');
                isValid = false;
            }

            // Wedding date validation
            if (weddingDate) {
                const today = new Date();
                const wedding = new Date(weddingDate);
                if (wedding < today) {
                    errors.push('Wedding date must be in the future');
                    isValid = false;
                }
            }

            if (!isValid) {
                e.preventDefault();
                const errorContainer = document.querySelector('.error-messages') || 
                                     document.createElement('div');
                errorContainer.className = 'error-messages';
                errorContainer.innerHTML = errors.map(error => `<p class="error">${error}</p>`).join('');
                
                if (!document.querySelector('.error-messages')) {
                    registrationForm.insertBefore(errorContainer, registrationForm.firstChild);
                }
            }
        });
    }

    // Password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            // Length check
            if (password.length >= 8) strength++;
            // Uppercase check
            if (/[A-Z]/.test(password)) strength++;
            // Lowercase check
            if (/[a-z]/.test(password)) strength++;
            // Number check
            if (/\d/.test(password)) strength++;
            // Special character check
            if (/[@$!%*?&]/.test(password)) strength++;

            const strengthIndicator = this.parentElement.querySelector('.strength-indicator') || 
                                    document.createElement('div');
            strengthIndicator.className = 'strength-indicator';

            let strengthText = '';
            let strengthClass = '';

            switch(strength) {
                case 0:
                case 1:
                    strengthText = 'Weak';
                    strengthClass = 'weak';
                    break;
                case 2:
                case 3:
                    strengthText = 'Moderate';
                    strengthClass = 'moderate';
                    break;
                case 4:
                case 5:
                    strengthText = 'Strong';
                    strengthClass = 'strong';
                    break;
            }

            strengthIndicator.innerHTML = `Password Strength: <span class="${strengthClass}">${strengthText}</span>`;
            
            if (!this.parentElement.querySelector('.strength-indicator')) {
                this.parentElement.appendChild(strengthIndicator);
            }
        });
    }

    // Form input validation styling
    const formInputs = document.querySelectorAll('.form-input');
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim() === '') {
                this.classList.add('invalid');
            } else {
                this.classList.remove('invalid');
            }
        });
    });
});
