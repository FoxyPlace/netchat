document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    const form = document.querySelector('.needs-validation');
    
    // Validation au submit
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);

    // Vérif mots de passe temps réel
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    
    confirmPassword.addEventListener('input', function() {
        if (this.value && this.value !== password.value) {
            this.setCustomValidity('Les mots de passe ne correspondent pas');
            this.classList.add('is-invalid');
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
        }
    });

    // Reset validation au focus
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.classList.remove('is-invalid');
            this.parentElement.querySelector('.invalid-feedback')?.classList.remove('d-block');
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Validation générale pour tous les forms
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Reset validation au focus (tous les forms)
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.classList.remove('is-invalid');
            const feedback = this.parentElement.querySelector('.invalid-feedback');
            if (feedback) feedback.style.display = 'none';
        });
    });

    // === LOGIN FORM (validation simple, PAS D'AJAX) ===
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        // Juste validation Bootstrap (POST normal)
        loginForm.addEventListener('submit', function(e) {
            if (!loginForm.checkValidity()) {
                e.preventDefault();
                loginForm.classList.add('was-validated');
            }
            // PAS D'AJAX → POST normal vers PHP
        });
    }

    // === REGISTER FORM (vérif mdp) ===
    const registerForm = document.querySelector('form[action="register.php"]');
    if (registerForm) {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        
        if (confirmPassword) {
            confirmPassword.addEventListener('input', function() {
                if (this.value && this.value !== password.value) {
                    this.setCustomValidity('Les mots de passe ne correspondent pas');
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                }
            });
        }
    }
});

