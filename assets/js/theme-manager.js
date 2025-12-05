/**
 * Theme Manager - CREx
 * Gestion complète et unifiée du dark mode avec détection automatique
 * Version améliorée avec meilleurs contrastes et transitions fluides
 */

(function() {
    'use strict';

    // Configuration
    const STORAGE_KEY = 'crex-theme';
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';
    const THEME_AUTO = 'auto';

    // État
    let currentPreference = THEME_AUTO;
    let systemPreference = null;

    /**
     * Détecte la préférence système
     */
    function detectSystemPreference() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return THEME_DARK;
        }
        return THEME_LIGHT;
    }

    /**
     * Récupère la préférence depuis localStorage
     */
    function getStoredPreference() {
        try {
            return localStorage.getItem(STORAGE_KEY) || THEME_AUTO;
        } catch (e) {
            return THEME_AUTO;
        }
    }

    /**
     * Sauvegarde la préférence
     */
    function savePreference(theme) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
            currentPreference = theme;
        } catch (e) {
            console.warn('Impossible de sauvegarder la préférence:', e);
        }
    }

    /**
     * Détermine le thème effectif à appliquer
     */
    function getEffectiveTheme() {
        if (currentPreference === THEME_AUTO) {
            return detectSystemPreference();
        }
        return currentPreference;
    }

    /**
     * Applique le thème au document avec transition fluide
     */
    function applyTheme(theme) {
        const effectiveTheme = theme === THEME_AUTO ? detectSystemPreference() : theme;
        const html = document.documentElement;
        const body = document.body;

        // Activer les transitions
        if (body && !body.classList.contains('theme-transition-ready')) {
            body.classList.add('theme-transition-ready');
        }

        // Supprimer les classes existantes
        html.classList.remove('dark-mode', 'light-mode');
        if (body) {
            body.classList.remove('dark-mode', 'light-mode');
        }

        // Appliquer le nouveau thème
        html.setAttribute('data-theme', effectiveTheme);
        
        if (effectiveTheme === THEME_DARK) {
            html.classList.add('dark-mode');
            if (body) body.classList.add('dark-mode');
        } else {
            html.classList.add('light-mode');
            if (body) body.classList.add('light-mode');
        }

        // Mettre à jour l'icône du toggle
        updateToggleButton(effectiveTheme);

        // Déclencher un événement personnalisé
        document.dispatchEvent(new CustomEvent('themechange', {
            detail: { 
                theme: effectiveTheme, 
                preference: currentPreference 
            }
        }));

        // Forcer un reflow pour s'assurer que les styles sont appliqués
        void html.offsetHeight;
    }

    /**
     * Met à jour l'icône du bouton toggle
     */
    function updateToggleButton(theme) {
        const toggles = document.querySelectorAll('.theme-toggle, [data-theme-toggle]');
        toggles.forEach(toggle => {
            if (!toggle) return;

            const icon = toggle.querySelector('.theme-icon, [data-theme-icon]');
            
            if (theme === THEME_DARK) {
                toggle.classList.add('active');
                toggle.setAttribute('aria-label', 'Activer le mode clair');
                toggle.setAttribute('aria-pressed', 'true');
                
                if (icon) {
                    icon.textContent = '☀️';
                    icon.className = 'theme-icon sun-icon';
                } else {
                    toggle.innerHTML = '<span class="theme-icon sun-icon">☀️</span>';
                }
            } else {
                toggle.classList.remove('active');
                toggle.setAttribute('aria-label', 'Activer le mode sombre');
                toggle.setAttribute('aria-pressed', 'false');
                
                if (icon) {
                    icon.textContent = '🌙';
                    icon.className = 'theme-icon moon-icon';
                } else {
                    toggle.innerHTML = '<span class="theme-icon moon-icon">🌙</span>';
                }
            }
        });
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

    /**
     * Applique le thème immédiatement (avant le rendu)
     */
    function applyThemeImmediate() {
        const html = document.documentElement;
        const savedTheme = getStoredPreference();
        const theme = savedTheme === THEME_AUTO ? detectSystemPreference() : savedTheme;
        
        html.classList.remove('dark-mode', 'light-mode');
        html.setAttribute('data-theme', theme);
        
        if (theme === THEME_DARK) {
            html.classList.add('dark-mode');
        } else {
            html.classList.add('light-mode');
        }
    }

    /**
     * Initialise le système de thème
     */
    function init() {
        // Appliquer le thème immédiatement
        applyThemeImmediate();
        
        // Récupérer la préférence
        currentPreference = getStoredPreference();
        systemPreference = detectSystemPreference();

        // Appliquer le thème avec transitions
        applyTheme(currentPreference);

        // Écouter les changements de préférence système
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            const handleSystemChange = (e) => {
                systemPreference = e.matches ? THEME_DARK : THEME_LIGHT;
                // Seulement si l'utilisateur n'a pas défini de préférence manuelle
                if (currentPreference === THEME_AUTO) {
                    applyTheme(THEME_AUTO);
                }
            };

            if (mediaQuery.addEventListener) {
                mediaQuery.addEventListener('change', handleSystemChange);
            } else {
                // Fallback pour anciens navigateurs
                mediaQuery.addListener(handleSystemChange);
            }
        }

        // Gérer les clics sur les boutons toggle
        document.addEventListener('click', function(e) {
            const toggle = e.target.closest('.theme-toggle, [data-theme-toggle]');
            if (toggle) {
                e.preventDefault();
                e.stopPropagation();
                toggleTheme();
            }
        });

        // Exposer l'API publique
        window.CRExTheme = {
            setTheme: setTheme,
            toggle: toggleTheme,
            getTheme: () => currentPreference,
            getEffectiveTheme: getEffectiveTheme,
            THEME_LIGHT: THEME_LIGHT,
            THEME_DARK: THEME_DARK,
            THEME_AUTO: THEME_AUTO
        };
    }

    // Appliquer le thème IMMÉDIATEMENT (avant le rendu de la page)
    if (document.documentElement) {
        applyThemeImmediate();
    }

    // Initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM déjà chargé
        setTimeout(init, 10);
    }

})();

