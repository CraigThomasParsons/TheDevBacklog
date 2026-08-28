/**
 * ThemeEngine — CSS Custom Property theme system for TheDevBacklog.
 *
 * Manages 5 built-in presets + arbitrary custom themes.
 * Persists to localStorage and applies instantly via CSS vars on <html>.
 */
(function () {
    'use strict';

    const STORAGE_KEY_THEME = 'tdb-theme-name';
    const STORAGE_KEY_CUSTOM = 'tdb-custom-theme';

    /* ─────────────────────────── Built-in Presets ─────────────────────────── */
    const presets = {
        default: {
            label: 'Default',
            description: 'Clean green on gray — the original look',
            vars: {
                '--theme-primary': '#16a34a',
                '--theme-primary-hover': '#15803d',
                '--theme-primary-light': '#bbf7d0',
                '--theme-bg': '#f3f4f6',
                '--theme-bg-dark': '#111827',
                '--theme-surface': '#ffffff',
                '--theme-surface-dark': '#1f2937',
                '--theme-text': '#111827',
                '--theme-text-dark': '#f3f4f6',
                '--theme-text-muted': '#6b7280',
                '--theme-text-muted-dark': '#9ca3af',
                '--theme-nav-bg': '#ffffff',
                '--theme-nav-bg-dark': '#1f2937',
                '--theme-nav-border': '#e5e7eb',
                '--theme-nav-border-dark': '#374151',
                '--theme-card-bg': '#ffffff',
                '--theme-card-bg-dark': '#1f2937',
                '--theme-card-border': '#e5e7eb',
                '--theme-card-border-dark': '#374151',
                '--theme-accent': '#16a34a',
                '--theme-accent-text': '#ffffff',
                '--theme-btn-bg': '#16a34a',
                '--theme-btn-bg-hover': '#15803d',
                '--theme-input-bg': '#ffffff',
                '--theme-input-bg-dark': '#374151',
                '--theme-input-border': '#d1d5db',
                '--theme-input-border-dark': '#4b5563',
                '--theme-topbar-bg': '#111827',
                '--theme-topbar-text': '#d1d5db',
                '--theme-badge-green': '#dcfce7',
                '--theme-badge-green-text': '#166534',
                '--theme-radius': '0.5rem',
                '--theme-font': "'Figtree', ui-sans-serif, system-ui, sans-serif",
            }
        },

        cyberpunk: {
            label: 'Cyberpunk',
            description: 'Neon pink & cyan on dark chrome',
            vars: {
                '--theme-primary': '#f472b6',
                '--theme-primary-hover': '#ec4899',
                '--theme-primary-light': '#4a1942',
                '--theme-bg': '#0a0a0f',
                '--theme-bg-dark': '#0a0a0f',
                '--theme-surface': '#141420',
                '--theme-surface-dark': '#141420',
                '--theme-text': '#e0e0ff',
                '--theme-text-dark': '#e0e0ff',
                '--theme-text-muted': '#8888aa',
                '--theme-text-muted-dark': '#8888aa',
                '--theme-nav-bg': '#0d0d1a',
                '--theme-nav-bg-dark': '#0d0d1a',
                '--theme-nav-border': '#2a2a4a',
                '--theme-nav-border-dark': '#2a2a4a',
                '--theme-card-bg': '#141420',
                '--theme-card-bg-dark': '#141420',
                '--theme-card-border': '#3a2a5a',
                '--theme-card-border-dark': '#3a2a5a',
                '--theme-accent': '#22d3ee',
                '--theme-accent-text': '#0a0a0f',
                '--theme-btn-bg': '#f472b6',
                '--theme-btn-bg-hover': '#ec4899',
                '--theme-input-bg': '#1a1a2e',
                '--theme-input-bg-dark': '#1a1a2e',
                '--theme-input-border': '#3a2a5a',
                '--theme-input-border-dark': '#3a2a5a',
                '--theme-topbar-bg': '#05050a',
                '--theme-topbar-text': '#f472b6',
                '--theme-badge-green': '#1a3a2a',
                '--theme-badge-green-text': '#4ade80',
                '--theme-radius': '0.25rem',
                '--theme-font': "'JetBrains Mono', 'Fira Code', monospace",
            }
        },

        ocean: {
            label: 'Ocean',
            description: 'Deep blue & teal — calm and professional',
            vars: {
                '--theme-primary': '#0891b2',
                '--theme-primary-hover': '#0e7490',
                '--theme-primary-light': '#cffafe',
                '--theme-bg': '#ecfeff',
                '--theme-bg-dark': '#0c1929',
                '--theme-surface': '#ffffff',
                '--theme-surface-dark': '#0f2942',
                '--theme-text': '#0c4a6e',
                '--theme-text-dark': '#e0f2fe',
                '--theme-text-muted': '#6b8fa3',
                '--theme-text-muted-dark': '#7dd3fc',
                '--theme-nav-bg': '#f0fdfa',
                '--theme-nav-bg-dark': '#0a1f33',
                '--theme-nav-border': '#99f6e4',
                '--theme-nav-border-dark': '#164e63',
                '--theme-card-bg': '#ffffff',
                '--theme-card-bg-dark': '#0f2942',
                '--theme-card-border': '#a5f3fc',
                '--theme-card-border-dark': '#164e63',
                '--theme-accent': '#14b8a6',
                '--theme-accent-text': '#ffffff',
                '--theme-btn-bg': '#0891b2',
                '--theme-btn-bg-hover': '#0e7490',
                '--theme-input-bg': '#ffffff',
                '--theme-input-bg-dark': '#0f2942',
                '--theme-input-border': '#a5f3fc',
                '--theme-input-border-dark': '#164e63',
                '--theme-topbar-bg': '#083344',
                '--theme-topbar-text': '#67e8f9',
                '--theme-badge-green': '#ccfbf1',
                '--theme-badge-green-text': '#065f46',
                '--theme-radius': '0.75rem',
                '--theme-font': "'Inter', ui-sans-serif, system-ui, sans-serif",
            }
        },

        sunset: {
            label: 'Sunset',
            description: 'Warm orange & amber — energetic vibes',
            vars: {
                '--theme-primary': '#ea580c',
                '--theme-primary-hover': '#c2410c',
                '--theme-primary-light': '#ffedd5',
                '--theme-bg': '#fffbeb',
                '--theme-bg-dark': '#1c1007',
                '--theme-surface': '#ffffff',
                '--theme-surface-dark': '#271c0d',
                '--theme-text': '#431407',
                '--theme-text-dark': '#fef3c7',
                '--theme-text-muted': '#92400e',
                '--theme-text-muted-dark': '#fbbf24',
                '--theme-nav-bg': '#fffbeb',
                '--theme-nav-bg-dark': '#1a0f06',
                '--theme-nav-border': '#fde68a',
                '--theme-nav-border-dark': '#78350f',
                '--theme-card-bg': '#ffffff',
                '--theme-card-bg-dark': '#271c0d',
                '--theme-card-border': '#fcd34d',
                '--theme-card-border-dark': '#78350f',
                '--theme-accent': '#d97706',
                '--theme-accent-text': '#ffffff',
                '--theme-btn-bg': '#ea580c',
                '--theme-btn-bg-hover': '#c2410c',
                '--theme-input-bg': '#ffffff',
                '--theme-input-bg-dark': '#271c0d',
                '--theme-input-border': '#fde68a',
                '--theme-input-border-dark': '#78350f',
                '--theme-topbar-bg': '#431407',
                '--theme-topbar-text': '#fdba74',
                '--theme-badge-green': '#fef3c7',
                '--theme-badge-green-text': '#92400e',
                '--theme-radius': '0.625rem',
                '--theme-font': "'Outfit', ui-sans-serif, system-ui, sans-serif",
            }
        },

        midnight: {
            label: 'Midnight',
            description: 'Purple & indigo on near-black — sleek and modern',
            vars: {
                '--theme-primary': '#8b5cf6',
                '--theme-primary-hover': '#7c3aed',
                '--theme-primary-light': '#2e1065',
                '--theme-bg': '#faf5ff',
                '--theme-bg-dark': '#09090f',
                '--theme-surface': '#ffffff',
                '--theme-surface-dark': '#13111f',
                '--theme-text': '#1e1b4b',
                '--theme-text-dark': '#e9e5ff',
                '--theme-text-muted': '#6366f1',
                '--theme-text-muted-dark': '#a5b4fc',
                '--theme-nav-bg': '#f5f3ff',
                '--theme-nav-bg-dark': '#0f0d1a',
                '--theme-nav-border': '#c4b5fd',
                '--theme-nav-border-dark': '#312e81',
                '--theme-card-bg': '#ffffff',
                '--theme-card-bg-dark': '#13111f',
                '--theme-card-border': '#c4b5fd',
                '--theme-card-border-dark': '#312e81',
                '--theme-accent': '#6366f1',
                '--theme-accent-text': '#ffffff',
                '--theme-btn-bg': '#8b5cf6',
                '--theme-btn-bg-hover': '#7c3aed',
                '--theme-input-bg': '#ffffff',
                '--theme-input-bg-dark': '#13111f',
                '--theme-input-border': '#c4b5fd',
                '--theme-input-border-dark': '#312e81',
                '--theme-topbar-bg': '#0c0a14',
                '--theme-topbar-text': '#a78bfa',
                '--theme-badge-green': '#1e1b4b',
                '--theme-badge-green-text': '#c4b5fd',
                '--theme-radius': '0.875rem',
                '--theme-font': "'Inter', ui-sans-serif, system-ui, sans-serif",
            }
        }
    };

    /* ────────────────────────── Core Engine ────────────────────────── */

    function getActiveThemeName() {
        return localStorage.getItem(STORAGE_KEY_THEME) || 'default';
    }

    function getCustomTheme() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY_CUSTOM);
            return raw ? JSON.parse(raw) : null;
        } catch (_) {
            return null;
        }
    }

    function isDarkMode() {
        return document.documentElement.classList.contains('dark');
    }

    /**
     * Apply a set of theme variables to the document root.
     * Dark-mode aware: picks *-dark variants when dark class is present.
     */
    function applyVars(vars) {
        const root = document.documentElement;
        const dark = isDarkMode();

        Object.entries(vars).forEach(([key, value]) => {
            // Skip dark variants during assignment — handled below
            if (key.endsWith('-dark')) return;

            // If there's a dark variant and we ARE in dark mode, use it
            const darkKey = key + '-dark';
            const resolved = (dark && vars[darkKey]) ? vars[darkKey] : value;
            root.style.setProperty(key, resolved);
        });

        // Also set the raw vars for the customizer to read back
        Object.entries(vars).forEach(([key, value]) => {
            root.style.setProperty(key + '--raw', value);
        });

        // Set font
        if (vars['--theme-font']) {
            root.style.setProperty('--theme-font', vars['--theme-font']);
        }
    }

    function applyTheme(name) {
        if (name === 'custom') {
            const custom = getCustomTheme();
            if (custom && custom.vars) {
                applyVars(custom.vars);
            }
        } else if (presets[name]) {
            applyVars(presets[name].vars);
        }
        localStorage.setItem(STORAGE_KEY_THEME, name);
        // Dispatch event so Alpine components can react
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { name } }));
    }

    function saveCustomTheme(vars) {
        const theme = { label: 'Custom', description: 'Your custom theme', vars };
        localStorage.setItem(STORAGE_KEY_CUSTOM, JSON.stringify(theme));
        localStorage.setItem(STORAGE_KEY_THEME, 'custom');
        applyVars(vars);
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { name: 'custom' } }));
    }

    function exportTheme() {
        const name = getActiveThemeName();
        let theme;
        if (name === 'custom') {
            theme = getCustomTheme() || presets.default;
        } else {
            theme = presets[name] || presets.default;
        }
        return JSON.stringify(theme, null, 2);
    }

    function importTheme(json) {
        try {
            const theme = JSON.parse(json);
            if (theme && theme.vars) {
                saveCustomTheme(theme.vars);
                return true;
            }
            return false;
        } catch (_) {
            return false;
        }
    }

    function getPresets() {
        return presets;
    }

    function getCurrentVars() {
        const name = getActiveThemeName();
        if (name === 'custom') {
            const custom = getCustomTheme();
            return custom ? custom.vars : { ...presets.default.vars };
        }
        return { ...(presets[name] || presets.default).vars };
    }

    /* ─────────────── Auto-apply on dark mode change ─────────────── */

    function reapply() {
        applyTheme(getActiveThemeName());
    }

    // Watch for dark class toggle
    const observer = new MutationObserver((mutations) => {
        for (const m of mutations) {
            if (m.attributeName === 'class') {
                reapply();
            }
        }
    });

    /* ─────────────────────── Initialization ─────────────────────── */

    // Apply theme ASAP to avoid flash of un-themed content
    reapply();

    // Observe class changes on <html> for dark mode toggling
    if (document.documentElement) {
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    }

    /* ─────────────────────── Public API ─────────────────────── */

    window.ThemeEngine = {
        apply: applyTheme,
        reapply,
        getActive: getActiveThemeName,
        getPresets,
        getCurrentVars,
        saveCustom: saveCustomTheme,
        exportTheme,
        importTheme,
        isDarkMode
    };
})();
