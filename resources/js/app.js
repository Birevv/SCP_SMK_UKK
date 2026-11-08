(() => {
    const STORAGE_KEY = 'theme';
    const root = document.documentElement;

    const getStoredTheme = () => {
        try {
            const value = localStorage.getItem(STORAGE_KEY);
            if (value === 'dark' || value === 'light') {
                return value;
            }
            if (value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return 'dark';
            }
        } catch {
            // Ignore storage errors in restricted browsing modes
        }

        return 'light';
    };

    const syncButtons = (theme) => {
        document.querySelectorAll('[data-theme-btn]').forEach((button) => {
            const isTarget = button.getAttribute('data-theme-btn') === theme;
            button.setAttribute('aria-pressed', isTarget ? 'true' : 'false');
        });
    };

    const applyTheme = (theme, persist = false) => {
        const normalized = theme === 'dark' ? 'dark' : 'light';
        root.classList.toggle('dark', normalized === 'dark');
        root.style.colorScheme = normalized;

        if (persist) {
            try {
                localStorage.setItem(STORAGE_KEY, normalized);
            } catch {
                // Ignore storage errors
            }
        }

        syncButtons(normalized);
    };

    applyTheme(getStoredTheme(), false);

    document.addEventListener('DOMContentLoaded', () => {
        syncButtons(root.classList.contains('dark') ? 'dark' : 'light');

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-theme-btn]');
            if (!button) {
                return;
            }

            const chosenTheme = button.getAttribute('data-theme-btn');
            applyTheme(chosenTheme, true);
        });
    });

    window.addEventListener('storage', (event) => {
        if (event.key === STORAGE_KEY && (event.newValue === 'dark' || event.newValue === 'light')) {
            applyTheme(event.newValue, false);
        }
    });
})();
