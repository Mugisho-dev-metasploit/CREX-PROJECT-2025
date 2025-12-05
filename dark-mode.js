/**
 * Dark Mode Toggle System for CREx Website
 * Handles theme switching, persistence, and smooth transitions
 */

(function() {
    'use strict';

    // Theme configuration
    const THEME_KEY = 'crex-theme';
    const THEME_DARK = 'dark';
    const THEME_LIGHT = 'light';

    /**
     * Get the current theme preference
     */
    function getTheme() {
        // Check localStorage first
        try {
            const savedTheme = localStorage.getItem(THEME_KEY);
            if (savedTheme === THEME_DARK || savedTheme === THEME_LIGHT) {
                return savedTheme;
            }
        } catch (e) {
            console.warn('localStorage not available:', e);
        }
        
        // Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return THEME_DARK;
        }
        
        // Default to light
        return THEME_LIGHT;
    }

    /**
     * Apply theme to the document immediately (before page render)
     */
    function applyThemeImmediate(theme) {
        const html = document.documentElement;
        const body = document.body;
        
        // Remove all theme classes first
        html.classList.remove('dark-mode', 'light-mode');
        html.removeAttribute('data-theme');
        
        if (theme === THEME_DARK) {
            html.classList.add('dark-mode');
            html.setAttribute('data-theme', 'dark');
            if (body) {
                body.classList.add('dark-mode');
                body.setAttribute('data-theme', 'dark');
            }
        } else {
            html.classList.add('light-mode');
            html.setAttribute('data-theme', 'light');
            if (body) {
                body.classList.add('light-mode');
                body.setAttribute('data-theme', 'light');
            }
        }
    }

    /**
     * Apply theme to the document
     */
    function applyTheme(theme) {
        const html = document.documentElement;
        const body = document.body;
        
        // Prevent flash of unstyled content
        if (body && !body.classList.contains('theme-transition-ready')) {
            body.classList.add('theme-transition-ready');
        }
        
        // Remove all theme classes first
        html.classList.remove('dark-mode', 'light-mode');
        html.removeAttribute('data-theme');
        
        if (body) {
            body.classList.remove('dark-mode', 'light-mode');
            body.removeAttribute('data-theme');
        }
        
        if (theme === THEME_DARK) {
            html.classList.add('dark-mode');
            html.setAttribute('data-theme', 'dark');
            if (body) {
                body.classList.add('dark-mode');
                body.setAttribute('data-theme', 'dark');
            }
        } else {
            html.classList.add('light-mode');
            html.setAttribute('data-theme', 'light');
            if (body) {
                body.classList.add('light-mode');
                body.setAttribute('data-theme', 'light');
            }
        }
        
        // Save preference
        try {
            localStorage.setItem(THEME_KEY, theme);
        } catch (e) {
            console.warn('Cannot save theme preference:', e);
        }
        
        // Update toggle button state
        updateToggleButton(theme);
        
        // Force a repaint to ensure styles are applied
        if (html.offsetHeight) {
            // Trigger reflow
        }
    }

    /**
     * Toggle between light and dark themes
     */
    function toggleTheme() {
        const currentTheme = getTheme();
        const newTheme = currentTheme === THEME_DARK ? THEME_LIGHT : THEME_DARK;
        applyTheme(newTheme);
        
        // Dispatch custom event for other scripts
        document.dispatchEvent(new CustomEvent('themeChanged', {
            detail: { theme: newTheme }
        }));
    }

    /**
     * Update toggle button appearance
     */
    function updateToggleButton(theme) {
        const toggles = document.querySelectorAll('.theme-toggle, [data-theme-toggle]');
        toggles.forEach(toggle => {
            if (!toggle) return;
            
            if (theme === THEME_DARK) {
                toggle.classList.add('active');
                toggle.setAttribute('aria-label', 'Activer le mode clair');
                toggle.setAttribute('aria-pressed', 'true');
                
                // Update icon if present
                const icon = toggle.querySelector('.theme-icon, [data-theme-icon]');
                if (icon) {
                    icon.textContent = '☀️';
                    icon.className = 'theme-icon sun-icon';
                } else if (toggle.innerHTML) {
                    // If no icon element, update innerHTML
                    toggle.innerHTML = '<span class="theme-icon sun-icon">☀️</span>';
                }
            } else {
                toggle.classList.remove('active');
                toggle.setAttribute('aria-label', 'Activer le mode sombre');
                toggle.setAttribute('aria-pressed', 'false');
                
                // Update icon if present
                const icon = toggle.querySelector('.theme-icon, [data-theme-icon]');
                if (icon) {
                    icon.textContent = '🌙';
                    icon.className = 'theme-icon moon-icon';
                } else if (toggle.innerHTML) {
                    // If no icon element, update innerHTML
                    toggle.innerHTML = '<span class="theme-icon moon-icon">🌙</span>';
                }
            }
        });
    }

    /**
     * Create theme toggle button
     */
    function createToggleButton() {
        // Check if toggle already exists in HTML or was created
        let toggle = document.querySelector('.theme-toggle');
        if (toggle) {
            // Update existing button
            updateToggleButton(getTheme());
            return toggle;
        }

        // Create new toggle button
        toggle = document.createElement('button');
        toggle.className = 'theme-toggle';
        toggle.setAttribute('aria-label', 'Basculer le thème');
        toggle.setAttribute('aria-pressed', 'false');
        toggle.setAttribute('type', 'button');
        
        const currentTheme = getTheme();
        toggle.innerHTML = currentTheme === THEME_DARK 
            ? '<span class="theme-icon sun-icon">☀️</span>'
            : '<span class="theme-icon moon-icon">🌙</span>';
        
        // Add click handler
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleTheme();
        });
        
        // Find header to insert toggle - better positioning
        const header = document.querySelector('.main-header, .admin-header');
        if (header) {
            const headerContent = header.querySelector('.header-flex, .admin-header-content, .container');
            if (headerContent) {
                // Try to insert before burger button (mobile menu)
                const burger = headerContent.querySelector('.burger');
                if (burger) {
                    headerContent.insertBefore(toggle, burger);
                } else {
                    // Try to insert after navigation
                    const nav = headerContent.querySelector('.main-nav, .admin-nav-links');
                    if (nav && nav.nextSibling) {
                        headerContent.insertBefore(toggle, nav.nextSibling);
                    } else if (nav) {
                        nav.parentNode.insertBefore(toggle, nav.nextSibling);
                    } else {
                        // Fallback: append to header content
                        headerContent.appendChild(toggle);
                    }
                }
            } else {
                // If no header content, append to header directly
                header.appendChild(toggle);
            }
        }
        
        // Update button state
        updateToggleButton(getTheme());
        
        return toggle;
    }

    /**
     * Initialize dark mode system
     */
    function initDarkMode() {
        // Apply initial theme immediately
        const theme = getTheme();
        applyTheme(theme);
        
        // Create toggle button after a short delay to ensure DOM is ready
        setTimeout(function() {
            createToggleButton();
        }, 100);
        
        // Listen for system theme changes
        if (window.matchMedia) {
            try {
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                if (mediaQuery.addEventListener) {
                    mediaQuery.addEventListener('change', function(e) {
                        // Only auto-switch if user hasn't manually set a preference
                        try {
                            if (!localStorage.getItem(THEME_KEY)) {
                                applyTheme(e.matches ? THEME_DARK : THEME_LIGHT);
                            }
                        } catch (err) {
                            console.warn('Cannot access localStorage:', err);
                        }
                    });
                } else {
                    // Fallback for older browsers
                    mediaQuery.addListener(function(e) {
                        try {
                            if (!localStorage.getItem(THEME_KEY)) {
                                applyTheme(e.matches ? THEME_DARK : THEME_LIGHT);
                            }
                        } catch (err) {
                            console.warn('Cannot access localStorage:', err);
                        }
                    });
                }
            } catch (e) {
                console.warn('Cannot setup media query listener:', e);
            }
        }
        
        // Add click handlers to existing toggles (delegation)
        document.addEventListener('click', function(e) {
            const toggle = e.target.closest('.theme-toggle, [data-theme-toggle]');
            if (toggle) {
                e.preventDefault();
                e.stopPropagation();
                toggleTheme();
            }
        });
    }

    // Apply theme IMMEDIATELY before page render (critical for avoiding flash)
    // This must run before any styles are applied
    (function() {
        if (document.documentElement) {
            const theme = getTheme();
            applyThemeImmediate(theme);
        }
    })();

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDarkMode);
    } else {
        // DOM already loaded, but wait a bit for all scripts to load
        setTimeout(initDarkMode, 50);
    }

    // Expose API for external use
    window.CRExTheme = {
        toggle: toggleTheme,
        setTheme: applyTheme,
        getTheme: getTheme
    };
})();
