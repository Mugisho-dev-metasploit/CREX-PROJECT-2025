/**
 * Theme Initialization Script
 * Applies theme immediately to prevent flash of unstyled content (FOUC)
 * This script must be loaded in the <head> before any stylesheets
 */
(function() {
    'use strict';
    
    // Get theme preference
    const theme = localStorage.getItem('crex-theme') || 
        (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    
    // Apply theme immediately to html element
    const html = document.documentElement;
    html.classList.remove('dark-mode', 'light-mode');
    html.classList.add(theme === 'dark' ? 'dark-mode' : 'light-mode');
    html.setAttribute('data-theme', theme);
    
    // Apply theme to body if it exists
    if (document.body) {
        document.body.classList.remove('dark-mode', 'light-mode');
        document.body.classList.add(theme === 'dark' ? 'dark-mode' : 'light-mode');
        document.body.setAttribute('data-theme', theme);
    }
})();

