/**
 * Theme Initialization Script - Inline
 * This script applies theme immediately to prevent FOUC (Flash of Unstyled Content)
 * Must be loaded in <head> before any stylesheets
 */
(function() {
    'use strict';
    try {
        const STORAGE_KEY = 'crex-theme';
        const THEME_LIGHT = 'light';
        const THEME_DARK = 'dark';
        const THEME_AUTO = 'auto';
        
        function detectSystemPreference() {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return THEME_DARK;
            }
            return THEME_LIGHT;
        }
        
        function getStoredPreference() {
            try {
                return localStorage.getItem(STORAGE_KEY) || THEME_AUTO;
            } catch (e) {
                return THEME_AUTO;
            }
        }
        
        const savedTheme = getStoredPreference();
        const theme = savedTheme === THEME_AUTO ? detectSystemPreference() : savedTheme;
        const html = document.documentElement;
        
        html.classList.remove('dark-mode', 'light-mode');
        html.setAttribute('data-theme', theme);
        
        if (theme === THEME_DARK) {
            html.classList.add('dark-mode');
        } else {
            html.classList.add('light-mode');
        }
    } catch (e) {
        // Fallback to light mode if error
        document.documentElement.setAttribute('data-theme', 'light');
        document.documentElement.classList.add('light-mode');
    }
})();

