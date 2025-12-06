/**
 * Password Strength Checker
 * Validates password against security requirements and displays strength indicator
 */

class PasswordStrengthChecker {
    constructor(passwordInputId, strengthBarId, strengthTextId, requirementsId) {
        this.passwordInput = document.getElementById(passwordInputId);
        this.strengthBar = document.getElementById(strengthBarId);
        this.strengthText = document.getElementById(strengthTextId);
        this.requirementsContainer = document.getElementById(requirementsId);
        
        this.requirements = {
            length: { regex: /.{8,}/, text: 'At least 8 characters', met: false },
            uppercase: { regex: /[A-Z]/, text: 'At least one uppercase letter', met: false },
            lowercase: { regex: /[a-z]/, text: 'At least one lowercase letter', met: false },
            number: { regex: /[0-9]/, text: 'At least one number', met: false },
            special: { regex: /[^A-Za-z0-9]/, text: 'At least one special character', met: false }
        };
        
        this.init();
    }
    
    init() {
        if (this.passwordInput) {
            this.passwordInput.addEventListener('input', () => this.checkStrength());
            this.renderRequirements();
        }
    }
    
    renderRequirements() {
        if (!this.requirementsContainer) return;
        
        let html = '<ul class="list-unstyled mb-0">';
        for (let key in this.requirements) {
            html += `<li id="req-${key}" class="requirement-item">
                <i class="bi bi-x-circle text-danger"></i>
                <span>${this.requirements[key].text}</span>
            </li>`;
        }
        html += '</ul>';
        this.requirementsContainer.innerHTML = html;
    }
    
    checkStrength() {
        const password = this.passwordInput.value;
        let strength = 0;
        let metCount = 0;
        
        // Check each requirement
        for (let key in this.requirements) {
            const req = this.requirements[key];
            req.met = req.regex.test(password);
            
            if (req.met) {
                metCount++;
                this.updateRequirementUI(key, true);
            } else {
                this.updateRequirementUI(key, false);
            }
        }
        
        // Calculate strength score
        if (password.length >= 8) strength += 20;
        if (password.length >= 12) strength += 10;
        if (password.length >= 16) strength += 10;
        if (this.requirements.lowercase.met) strength += 15;
        if (this.requirements.uppercase.met) strength += 15;
        if (this.requirements.number.met) strength += 15;
        if (this.requirements.special.met) strength += 15;
        
        // Update strength indicator
        this.updateStrengthUI(strength, metCount);
        
        return {
            strength: strength,
            valid: metCount === Object.keys(this.requirements).length
        };
    }
    
    updateRequirementUI(key, met) {
        const element = document.getElementById(`req-${key}`);
        if (!element) return;
        
        const icon = element.querySelector('i');
        if (met) {
            icon.className = 'bi bi-check-circle text-success';
            element.classList.add('text-success');
            element.classList.remove('text-danger');
        } else {
            icon.className = 'bi bi-x-circle text-danger';
            element.classList.add('text-danger');
            element.classList.remove('text-success');
        }
    }
    
    updateStrengthUI(strength, metCount) {
        if (!this.strengthBar || !this.strengthText) return;
        
        let strengthLevel = 'Weak';
        let strengthClass = 'bg-danger';
        
        if (strength >= 80 && metCount === 5) {
            strengthLevel = 'Strong';
            strengthClass = 'bg-success';
        } else if (strength >= 50) {
            strengthLevel = 'Medium';
            strengthClass = 'bg-warning';
        }
        
        this.strengthBar.style.width = strength + '%';
        this.strengthBar.className = 'progress-bar ' + strengthClass;
        this.strengthBar.setAttribute('aria-valuenow', strength);
        
        this.strengthText.textContent = strengthLevel;
        this.strengthText.className = 'small fw-bold ' + 
            (strengthLevel === 'Strong' ? 'text-success' : 
             strengthLevel === 'Medium' ? 'text-warning' : 'text-danger');
    }
    
    isValid() {
        const result = this.checkStrength();
        return result.valid;
    }
}

// Initialize password strength checker when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (passwordInput && document.getElementById('password-strength-bar')) {
        window.passwordChecker = new PasswordStrengthChecker(
            'password',
            'password-strength-bar',
            'password-strength-text',
            'password-requirements'
        );
    }
    
    // Confirm password validation
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;
            const feedback = document.getElementById('confirm-password-feedback');
            
            if (confirmPassword === '') {
                feedback.textContent = '';
                this.classList.remove('is-valid', 'is-invalid');
            } else if (password === confirmPassword) {
                feedback.textContent = 'Passwords match';
                feedback.className = 'small text-success';
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                feedback.textContent = 'Passwords do not match';
                feedback.className = 'small text-danger';
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    }
});

