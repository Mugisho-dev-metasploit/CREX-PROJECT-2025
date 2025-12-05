/**
 * Dark Mode Manager - CREx
 * Gestion complète du mode sombre avec détection automatique et option manuelle
 */

(function() {
    'use strict';

    // Configuration
    const STORAGE_KEY = 'crex_theme_preference';
    const THEME_ATTRIBUTE = 'data-theme';
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';
    const THEME_AUTO = 'auto';

    // État actuel
    let currentTheme = THEME_AUTO;
    let systemPreference = null;

    /**
     * Détecte la préférence système
     */
    function detectSystemPreference() {
        if (window.matchMedia) {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? THEME_DARK : THEME_LIGHT;
        }
        return THEME_LIGHT;
    }

    /**
     * Récupère la préférence depuis le localStorage
     */
    function getStoredPreference() {
        try {
            return localStorage.getItem(STORAGE_KEY) || THEME_AUTO;
        } catch (e) {
            return THEME_AUTO;
        }
    }

    /**
     * Sauvegarde la préférence dans le localStorage
     */
    function savePreference(theme) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
            currentTheme = theme;
        } catch (e) {
            console.warn('Impossible de sauvegarder la préférence du thème:', e);
        }
    }

    /**
     * Détermine le thème actuel à appliquer
     */
    function getEffectiveTheme() {
        if (currentTheme === THEME_AUTO) {
            return detectSystemPreference();
        }
        return currentTheme;
    }

    /**
     * Applique le thème au document
     */
    function applyTheme(theme) {
        const effectiveTheme = theme === THEME_AUTO ? detectSystemPreference() : theme;
        const html = document.documentElement;
        
        // Supprimer les classes de thème existantes
        html.classList.remove('dark-mode', 'light-mode');
        
        // Ajouter l'attribut data-theme
        html.setAttribute(THEME_ATTRIBUTE, effectiveTheme);
        
        // Ajouter la classe correspondante pour compatibilité
        if (effectiveTheme === THEME_DARK) {
            html.classList.add('dark-mode');
        } else {
            html.classList.add('light-mode');
        }

        // Déclencher un événement personnalisé
        const event = new CustomEvent('themechange', {
            detail: { theme: effectiveTheme, preference: currentTheme }
        });
        document.dispatchEvent(event);

        // Mettre à jour l'icône du toggle si présent
        updateThemeToggleIcon(effectiveTheme);
    }

    /**
     * Met à jour l'icône du bouton de bascule
     */
    function updateThemeToggleIcon(theme) {
        const toggles = document.querySelectorAll('.theme-toggle, [data-theme-toggle]');
        toggles.forEach(toggle => {
            const icon = toggle.querySelector('i');
            if (icon) {
                if (theme === THEME_DARK) {
                    icon.className = 'fas fa-sun';
                } else {
                    icon.className = 'fas fa-moon';
                }
            }
        });
    }

    /**
     * Initialise le système de dark mode
     */
    function init() {
        // Récupérer la préférence stockée
        currentTheme = getStoredPreference();
        systemPreference = detectSystemPreference();

        // Appliquer le thème initial
        applyTheme(currentTheme);

        // Écouter les changements de préférence système
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addEventListener('change', (e) => {
                systemPreference = e.matches ? THEME_DARK : THEME_LIGHT;
                if (currentTheme === THEME_AUTO) {
                    applyTheme(THEME_AUTO);
                }
            });
        }

        // Ajouter la classe de transition après le chargement
        document.body.classList.add('theme-transition-ready');

        // Gérer les clics sur les boutons de bascule
        document.addEventListener('click', (e) => {
            const toggle = e.target.closest('.theme-toggle, [data-theme-toggle]');
            if (toggle) {
                e.preventDefault();
                toggleTheme();
            }
        });

        // Exposer l'API publique
        window.DarkMode = {
            setTheme: setTheme,
            toggle: toggleTheme,
            getTheme: () => currentTheme,
            getEffectiveTheme: getEffectiveTheme
        };
    }

    /**
     * Définit le thème
     */
    function setTheme(theme) {
        if ([THEME_LIGHT, THEME_DARK, THEME_AUTO].includes(theme)) {
            savePreference(theme);
            applyTheme(theme);
        }
    }

    /**
     * Bascule entre light et dark (ignore auto)
     */
    function toggleTheme() {
        const effectiveTheme = getEffectiveTheme();
        const newTheme = effectiveTheme === THEME_DARK ? THEME_LIGHT : THEME_DARK;
        setTheme(newTheme);
    }

    // Initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

