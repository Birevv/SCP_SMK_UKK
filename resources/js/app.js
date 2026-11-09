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

    // Buttery Smooth Animated FAQ Accordion (Interruptible, Zero Layout Shift)
    class SmoothFaqAccordion {
        constructor(el) {
            this.el = el;
            this.summary = el.querySelector('summary');
            this.content = el.querySelector('.faq-content') || el.querySelector('div');
            this.animation = null;
            this.isClosing = false;
            this.isExpanding = false;

            el._smoothAccordion = this;

            this.summary.addEventListener('click', (e) => this.onClick(e));
        }

        onClick(e) {
            e.preventDefault();

            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (prefersReducedMotion) {
                if (this.el.open) {
                    this.el.open = false;
                } else {
                    document.querySelectorAll('#faq details[open]').forEach((other) => {
                        if (other !== this.el) other.open = false;
                    });
                    this.el.open = true;
                }
                return;
            }

            if (this.isClosing || !this.el.open) {
                this.open();
            } else if (this.isExpanding || this.el.open) {
                this.shrink();
            }
        }

        shrink() {
            if (!this.content) {
                this.el.open = false;
                return;
            }

            this.isClosing = true;
            this.isExpanding = false;

            const startHeight = this.content.getBoundingClientRect().height;

            if (this.animation) {
                this.animation.cancel();
            }

            this.content.style.overflow = 'hidden';

            this.animation = this.content.animate(
                {
                    height: [`${startHeight}px`, '0px'],
                    opacity: [1, 0],
                },
                {
                    duration: 280,
                    easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
                }
            );

            this.animation.onfinish = () => {
                this.el.open = false;
                this.animation = null;
                this.isClosing = false;
                this.content.style.height = '';
                this.content.style.opacity = '';
                this.content.style.overflow = '';
            };

            this.animation.oncancel = () => {
                this.isClosing = false;
            };
        }

        open() {
            if (!this.content) {
                this.el.open = true;
                return;
            }

            // Tutup baris lain yang sedang terbuka secara sinkron dan halus
            document.querySelectorAll('#faq details[open]').forEach((other) => {
                if (other !== this.el && other._smoothAccordion) {
                    other._smoothAccordion.shrink();
                } else if (other !== this.el) {
                    other.open = false;
                }
            });

            this.isExpanding = true;
            this.isClosing = false;

            const wasOpen = this.el.open;
            if (!wasOpen) {
                this.content.style.height = '0px';
                this.content.style.opacity = '0';
                this.el.open = true;
            }

            const startHeight = this.content.getBoundingClientRect().height;
            this.content.style.height = '';
            const targetHeight = this.content.scrollHeight;

            if (this.animation) {
                this.animation.cancel();
            }

            this.content.style.overflow = 'hidden';

            this.animation = this.content.animate(
                {
                    height: [`${startHeight}px`, `${targetHeight}px`],
                    opacity: [startHeight > 0 ? 0.5 : 0, 1],
                },
                {
                    duration: 320,
                    easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
                }
            );

            this.animation.onfinish = () => {
                this.animation = null;
                this.isExpanding = false;
                this.content.style.height = '';
                this.content.style.opacity = '';
                this.content.style.overflow = '';
            };

            this.animation.oncancel = () => {
                this.isExpanding = false;
            };
        }
    }

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

        // Inisialisasi Smooth FAQ Accordion
        const faqDetails = document.querySelectorAll('#faq details');
        faqDetails.forEach((detail) => {
            new SmoothFaqAccordion(detail);
        });
    });

    window.addEventListener('storage', (event) => {
        if (event.key === STORAGE_KEY && (event.newValue === 'dark' || event.newValue === 'light')) {
            applyTheme(event.newValue, false);
        }
    });
})();
