/**
 * Light / dark / automatic colour mode. The choice is stored per device; "auto" follows the
 * operating system. The initial mode is applied by an inline script in the layout head.
 */
const STORAGE_KEY = 'matschlacht-theme';
const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');

function storedMode() {
    try {
        return window.localStorage.getItem(STORAGE_KEY) || 'auto';
    } catch {
        return 'auto';
    }
}

function applyMode(mode) {
    const isDark = mode === 'dark' || (mode === 'auto' && darkQuery.matches);
    document.documentElement.setAttribute(
        'data-bs-theme',
        isDark ? 'dark' : 'light',
    );

    document.querySelectorAll('[data-theme-value]').forEach((button) => {
        const isActive = button.dataset.themeValue === mode;
        button.classList.toggle('active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
}

export function initTheme() {
    applyMode(storedMode());

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-theme-value]');

        if (!button) {
            return;
        }

        try {
            window.localStorage.setItem(STORAGE_KEY, button.dataset.themeValue);
        } catch {
            // Private mode: the choice applies to this page only.
        }

        applyMode(button.dataset.themeValue);
    });

    darkQuery.addEventListener('change', () => {
        if (storedMode() === 'auto') {
            applyMode('auto');
        }
    });
}
