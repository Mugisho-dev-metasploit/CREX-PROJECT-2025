/**
 * JavaScript pour la page de connexion Admin
 * CREx Admin Panel
 */

(function() {
    'use strict';
    
    // Dark Mode Toggle
    const darkModeToggle = document.getElementById('darkModeToggle');
    const html = document.documentElement;
    const darkModeIcon = document.getElementById('darkModeIcon');
    
    // Load saved theme preference
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    updateDarkModeIcon(savedTheme);
    
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateDarkModeIcon(newTheme);
        });
    }
    
    function updateDarkModeIcon(theme) {
        if (darkModeIcon) {
            darkModeIcon.style.transition = 'transform 0.3s ease';
            darkModeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            darkModeIcon.style.transform = 'rotate(360deg)';
            setTimeout(() => {
                darkModeIcon.style.transform = 'rotate(0deg)';
            }, 300);
        }
    }
    
    // Password Toggle
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordInput = document.getElementById('password');
    const passwordToggleIcon = document.getElementById('passwordToggleIcon');
    
    if (passwordToggle && passwordInput && passwordToggleIcon) {
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            passwordToggleIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            
            // Add animation
            passwordToggleIcon.style.transform = 'scale(1.2)';
            setTimeout(() => {
                passwordToggleIcon.style.transform = 'scale(1)';
            }, 200);
        });
    }
    
    // Form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            
            if (!username || !password) {
                e.preventDefault();
                return false;
            }
            
            const usernameValue = username.value.trim();
            const passwordValue = password.value;
            
            if (!usernameValue || !passwordValue) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const loginBtn = document.getElementById('loginBtn');
            if (loginBtn) {
                loginBtn.disabled = true;
                loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Connexion...';
            }
        });
    }
    
    // Add enter key support for form submission
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const form = document.getElementById('loginForm');
            if (form && document.activeElement.tagName !== 'BUTTON') {
                form.requestSubmit();
            }
        }
    });
})();

