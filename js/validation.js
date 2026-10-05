/**
 * StudentHub Portal - Registration Form Validation & UX Module
 * Practical 5: HTML5 input types, Regular Expressions, Real-time Validation, Password Strength Meter, Canvas CAPTCHA
 */

document.addEventListener('DOMContentLoaded', () => {
    initRegistrationValidation();
});

function initRegistrationValidation() {
    const form = document.getElementById('student-registration-form');
    if (!form) return;

    // Elements
    const fullnameInput  = document.getElementById('fullname');
    const emailInput     = document.getElementById('email');
    const mobileInput    = document.getElementById('mobile');
    const passwordInput  = document.getElementById('reg-password');
    const confirmInput   = document.getElementById('confirm-password');
    const courseSelect   = document.getElementById('course');
    const yearSelect     = document.getElementById('year');
    const termsCheckbox  = document.getElementById('terms');
    const captchaInput   = document.getElementById('captcha-input');
    const captchaCanvas  = document.getElementById('captcha-canvas');
    const refreshCaptcha = document.getElementById('refresh-captcha');

    // Regex Patterns
    const regexName     = /^[a-zA-Z\s]{3,60}$/;
    const regexEmail    = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const regexMobile   = /^[6-9]\d{9}$/; // Standard 10-digit Indian mobile format
    const regexPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    // CAPTCHA Code State
    let generatedCaptcha = '';

    // Initialize Canvas CAPTCHA
    generateCaptcha();

    if (refreshCaptcha) {
        refreshCaptcha.addEventListener('click', (e) => {
            e.preventDefault();
            generateCaptcha();
            if (captchaInput) {
                captchaInput.value = '';
                clearError(captchaInput);
            }
        });
    }

    // 1. Real-time Event Listeners
    if (fullnameInput) {
        fullnameInput.addEventListener('input', () => validateName());
        fullnameInput.addEventListener('blur', () => validateName());
    }

    if (emailInput) {
        emailInput.addEventListener('input', () => validateEmail());
        emailInput.addEventListener('blur', () => validateEmail());
    }

    if (mobileInput) {
        mobileInput.addEventListener('input', () => validateMobile());
        mobileInput.addEventListener('blur', () => validateMobile());
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', () => {
            validatePassword();
            updatePasswordStrength(passwordInput.value);
            if (confirmInput.value) validateConfirmPassword();
        });
        passwordInput.addEventListener('blur', () => validatePassword());
    }

    if (confirmInput) {
        confirmInput.addEventListener('input', () => validateConfirmPassword());
        confirmInput.addEventListener('blur', () => validateConfirmPassword());
    }

    if (courseSelect) {
        courseSelect.addEventListener('change', () => validateCourse());
    }

    if (yearSelect) {
        yearSelect.addEventListener('change', () => validateYear());
    }

    if (termsCheckbox) {
        termsCheckbox.addEventListener('change', () => validateTerms());
    }

    // 2. Individual Field Validation Functions
    function validateName() {
        const val = fullnameInput.value.trim();
        if (!val) {
            return showError(fullnameInput, 'Full Name is required.');
        } else if (!regexName.test(val)) {
            return showError(fullnameInput, 'Name must be 3-60 characters and contain letters and spaces only.');
        }
        return showSuccess(fullnameInput);
    }

    function validateEmail() {
        const val = emailInput.value.trim();
        if (!val) {
            return showError(emailInput, 'Email Address is required.');
        } else if (!regexEmail.test(val)) {
            return showError(emailInput, 'Please enter a valid email address (e.g. name@domain.com).');
        }
        return showSuccess(emailInput);
    }

    function validateMobile() {
        const val = mobileInput.value.trim();
        if (!val) {
            return showError(mobileInput, 'Mobile Number is required.');
        } else if (!regexMobile.test(val)) {
            return showError(mobileInput, 'Enter a valid 10-digit mobile number starting with 6-9.');
        }
        return showSuccess(mobileInput);
    }

    function validatePassword() {
        const val = passwordInput.value;
        if (!val) {
            return showError(passwordInput, 'Password is required.');
        } else if (!regexPassword.test(val)) {
            return showError(passwordInput, 'Min 8 chars: at least 1 uppercase, 1 lowercase, 1 number, and 1 special symbol.');
        }
        return showSuccess(passwordInput);
    }

    function validateConfirmPassword() {
        const passVal = passwordInput.value;
        const confirmVal = confirmInput.value;
        if (!confirmVal) {
            return showError(confirmInput, 'Please confirm your password.');
        } else if (confirmVal !== passVal) {
            return showError(confirmInput, 'Passwords do not match.');
        }
        return showSuccess(confirmInput);
    }

    function validateCourse() {
        if (!courseSelect.value) {
            return showError(courseSelect, 'Please select your enrolled course.');
        }
        return showSuccess(courseSelect);
    }

    function validateYear() {
        if (!yearSelect.value) {
            return showError(yearSelect, 'Please select your current academic year.');
        }
        return showSuccess(yearSelect);
    }

    function validateGender() {
        const checkedGender = form.querySelector('input[name="gender"]:checked');
        const genderWrap = document.getElementById('gender-group-wrap');
        const feedback = document.getElementById('gender-error');
        if (!checkedGender) {
            if (feedback) {
                feedback.textContent = 'Please choose a gender identity.';
                feedback.classList.add('visible');
            }
            return false;
        }
        if (feedback) feedback.classList.remove('visible');
        return true;
    }

    function validateTerms() {
        const feedback = document.getElementById('terms-error');
        if (!termsCheckbox.checked) {
            if (feedback) {
                feedback.textContent = 'You must agree to the Terms of Service to continue.';
                feedback.classList.add('visible');
            }
            return false;
        }
        if (feedback) feedback.classList.remove('visible');
        return true;
    }

    function validateCaptcha() {
        if (!captchaInput) return true;
        const entered = captchaInput.value.trim();
        if (!entered) {
            return showError(captchaInput, 'Please enter the CAPTCHA characters shown.');
        } else if (entered.toUpperCase() !== generatedCaptcha.toUpperCase()) {
            return showError(captchaInput, 'Incorrect CAPTCHA. Please try again.');
        }
        return showSuccess(captchaInput);
    }

    // Helper functions for showing UI feedback
    function showError(element, message) {
        element.classList.add('is-invalid');
        element.classList.remove('is-valid');
        const feedback = element.parentElement.querySelector('.error-feedback');
        if (feedback) {
            feedback.textContent = message;
            feedback.classList.add('visible');
        }
        return false;
    }

    function showSuccess(element) {
        element.classList.remove('is-invalid');
        element.classList.add('is-valid');
        const feedback = element.parentElement.querySelector('.error-feedback');
        if (feedback) {
            feedback.textContent = '';
            feedback.classList.remove('visible');
        }
        return true;
    }

    function clearError(element) {
        element.classList.remove('is-invalid');
        element.classList.remove('is-valid');
        const feedback = element.parentElement.querySelector('.error-feedback');
        if (feedback) {
            feedback.textContent = '';
            feedback.classList.remove('visible');
        }
    }

    // 3. Password Strength Meter Logic
    function updatePasswordStrength(password) {
        const meterFill = document.getElementById('strength-fill');
        const meterText = document.getElementById('strength-text');
        if (!meterFill || !meterText) return;

        let score = 0;
        if (password.length >= 8) score += 25;
        if (/[A-Z]/.test(password)) score += 25;
        if (/[0-9]/.test(password)) score += 25;
        if (/[@$!%*?&]/.test(password)) score += 25;

        meterFill.className = 'strength-bar-fill';
        if (score === 0) {
            meterFill.style.width = '0%';
            meterText.textContent = 'None';
            meterText.style.color = 'var(--text-secondary)';
        } else if (score <= 50) {
            meterFill.classList.add('weak');
            meterText.textContent = 'Weak';
            meterText.style.color = '#EF4444';
        } else if (score === 75) {
            meterFill.classList.add('medium');
            meterText.textContent = 'Medium';
            meterText.style.color = '#F59E0B';
        } else {
            meterFill.classList.add('strong');
            meterText.textContent = 'Strong';
            meterText.style.color = '#10B981';
        }
    }

    // 4. Custom Canvas CAPTCHA Generator (Practical 5 Advanced)
    function generateCaptcha() {
        if (!captchaCanvas) return;
        const ctx = captchaCanvas.getContext('2d');
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let code = '';
        for (let i = 0; i < 5; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        generatedCaptcha = code;

        // Canvas Drawing
        ctx.clearRect(0, 0, captchaCanvas.width, captchaCanvas.height);
        
        // Background tint
        ctx.fillStyle = '#F8FAFC';
        ctx.fillRect(0, 0, captchaCanvas.width, captchaCanvas.height);

        // Distractor lines
        for (let i = 0; i < 4; i++) {
            ctx.strokeStyle = `rgba(13, 148, 136, ${0.2 + Math.random() * 0.4})`;
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.moveTo(Math.random() * captchaCanvas.width, Math.random() * captchaCanvas.height);
            ctx.lineTo(Math.random() * captchaCanvas.width, Math.random() * captchaCanvas.height);
            ctx.stroke();
        }

        // Draw distorted text characters
        ctx.font = 'bold 22px "Courier New", monospace';
        for (let i = 0; i < code.length; i++) {
            ctx.save();
            ctx.fillStyle = '#0F766E';
            const x = 16 + i * 22;
            const y = 28 + (Math.random() * 6 - 3);
            const angle = (Math.random() * 20 - 10) * Math.PI / 180;
            ctx.translate(x, y);
            ctx.rotate(angle);
            ctx.fillText(code[i], 0, 0);
            ctx.restore();
        }
    }

    // 5. Form Submit Handler
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const isNameValid    = validateName();
        const isEmailValid   = validateEmail();
        const isMobileValid  = validateMobile();
        const isPassValid    = validatePassword();
        const isConfirmValid = validateConfirmPassword();
        const isCourseValid  = validateCourse();
        const isYearValid    = validateYear();
        const isGenderValid  = validateGender();
        const isTermsValid   = validateTerms();
        const isCaptchaValid = validateCaptcha();

        const isFormValid = isNameValid && isEmailValid && isMobileValid &&
                            isPassValid && isConfirmValid && isCourseValid &&
                            isYearValid && isGenderValid && isTermsValid && isCaptchaValid;

        if (!isFormValid) {
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) firstInvalid.focus();
            return;
        }

        // Prepare data for PHP submission
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing Registration...';

        const formData = new FormData(form);

        try {
            // Attempt to submit to PHP backend
            const response = await fetch('../php/process_register.php', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });

            if (response.ok) {
                const result = await response.json();
                saveRegisteredUser(formData);
                showSuccessModal(result.message || 'Registration successfully completed!');
                form.reset();
                generateCaptcha();
                updatePasswordStrength('');
            } else {
                const errorData = await response.json().catch(() => null);
                if (errorData && errorData.errors) {
                    Object.keys(errorData.errors).forEach(field => {
                        const input = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
                        if (input) showError(input, errorData.errors[field]);
                    });
                } else {
                    // Fallback client simulation if running locally via static server
                    simulateClientSuccess(formData);
                }
            }
        } catch (err) {
            // Static environment fallback
            simulateClientSuccess(formData);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
        }
    });

    function saveRegisteredUser(formData) {
        const fullname = formData.get('fullname') || 'Student';
        const email = formData.get('email') || '';
        const registeredUser = {
            name: fullname,
            id: '24IT' + Math.floor(100 + Math.random() * 900),
            email: email,
            role: 'Student',
            avatar: '🎓',
            isLoggedIn: true,
            loginTime: new Date().toISOString()
        };
        localStorage.setItem('studenthub_user', JSON.stringify(registeredUser));
        if (window.StudentHubAuth && window.StudentHubAuth.initAuthUI) {
            window.StudentHubAuth.initAuthUI();
        }
    }

    function simulateClientSuccess(formData) {
        saveRegisteredUser(formData);
        showSuccessModal(`Account created for ${formData.get('fullname')}! Note: Running in static mode; record saved locally.`);
        form.reset();
        generateCaptcha();
        updatePasswordStrength('');
    }

    function showSuccessModal(message) {
        const modal = document.getElementById('reg-success-modal');
        if (modal) {
            const msgEl = modal.querySelector('.modal-message-content');
            if (msgEl) msgEl.textContent = message;
            window.StudentHub.openModal('reg-success-modal');
        } else {
            alert(message);
        }
    }
}
