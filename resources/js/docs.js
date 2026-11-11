(() => {
    // 1. Search Modal & Indexing
    const searchModal = document.getElementById('docs-search-modal');
    const searchInput = document.getElementById('docs-search-input');
    const searchResults = document.getElementById('docs-search-results');
    const searchOpenTriggers = document.querySelectorAll('[data-docs-search-trigger]');
    const searchCloseTriggers = document.querySelectorAll('[data-docs-search-close]');

    // Build searchable index from document content headings
    let searchIndex = [];
    const buildSearchIndex = () => {
        searchIndex = [];
        const contentAreas = document.querySelectorAll('#docs-content section[id]');
        contentAreas.forEach((sec) => {
            const secId = sec.getAttribute('id');
            const secCategory = sec.getAttribute('data-category') || 'Dokumentasi';
            const secTitle = sec.querySelector('h1, h2')?.textContent?.trim() || secId;
            
            // Subheadings
            const subheadings = sec.querySelectorAll('h3[id], h4[id]');
            
            // Push main section
            searchIndex.push({
                id: secId,
                title: secTitle,
                category: secCategory,
                text: sec.textContent.slice(0, 300).replace(/\s+/g, ' ').trim(),
                url: `#${secId}`
            });

            subheadings.forEach((sub) => {
                const subId = sub.getAttribute('id');
                searchIndex.push({
                    id: subId,
                    title: `${secTitle} → ${sub.textContent.trim()}`,
                    category: secCategory,
                    text: sub.nextElementSibling?.textContent?.slice(0, 200).replace(/\s+/g, ' ').trim() || '',
                    url: `#${subId}`
                });
            });
        });
    };

    const openSearch = () => {
        if (!searchModal) return;
        searchModal.classList.remove('hidden');
        searchModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            searchInput?.focus();
            if (searchInput) searchInput.value = '';
            renderSearchResults('');
        }, 50);
    };

    const closeSearch = () => {
        if (!searchModal) return;
        searchModal.classList.add('hidden');
        searchModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    const renderSearchResults = (query) => {
        if (!searchResults) return;
        const q = query.trim().toLowerCase();

        if (q === '') {
            searchResults.innerHTML = `
                <div class="px-4 py-8 text-center text-xs text-slate-500">
                    Ketik kata kunci untuk mencari dokumentasi SCP, SOP, atau evaluasi proyek...
                </div>
            `;
            return;
        }

        const matches = searchIndex.filter(item => {
            return item.title.toLowerCase().includes(q) ||
                   item.category.toLowerCase().includes(q) ||
                   item.text.toLowerCase().includes(q);
        }).slice(0, 8);

        if (matches.length === 0) {
            searchResults.innerHTML = `
                <div class="px-4 py-8 text-center text-xs text-slate-500 dark:text-zinc-400">
                    Tidak ditemukan hasil untuk "<span class="text-slate-800 dark:text-white font-medium">${query}</span>". Coba kata kunci lain seperti <span class="text-blue-600 dark:text-blue-400">jadwal</span>, <span class="text-blue-600 dark:text-blue-400">jurnal</span>, <span class="text-blue-600 dark:text-blue-400">SOP</span>, atau <span class="text-blue-600 dark:text-blue-400">evaluasi</span>.
                </div>
            `;
            return;
        }

        searchResults.innerHTML = matches.map((item, idx) => `
            <a href="${item.url}" class="search-result-item flex flex-col gap-1 p-3.5 rounded-xl hover:bg-blue-50/60 dark:hover:bg-zinc-800 border border-transparent hover:border-blue-200 dark:hover:border-zinc-700 transition-all ${idx === 0 ? 'bg-slate-50 dark:bg-zinc-800/50' : ''}" data-index="${idx}">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-base font-semibold text-slate-900 dark:text-white tracking-tight">${item.title}</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/60 text-[#1D4ED8] dark:text-blue-400 border border-blue-200 dark:border-blue-800 shrink-0 uppercase tracking-wider">${item.category}</span>
                </div>
                <p class="text-sm text-slate-500 dark:text-zinc-400 line-clamp-1">${item.text}</p>
            </a>
        `).join('');

        // Attach click listeners to auto close modal
        searchResults.querySelectorAll('.search-result-item').forEach(el => {
            el.addEventListener('click', () => {
                closeSearch();
            });
        });
    };

    searchOpenTriggers.forEach(t => t.addEventListener('click', openSearch));
    searchCloseTriggers.forEach(t => t.addEventListener('click', closeSearch));

    if (searchModal) {
        searchModal.addEventListener('click', (e) => {
            if (e.target === searchModal) closeSearch();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            renderSearchResults(e.target.value);
        });

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeSearch();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                const firstResult = searchResults?.querySelector('.search-result-item');
                firstResult?.focus();
            }
        });
    }

    // Global Keyboard Shortcut: Ctrl + K / Cmd + K
    window.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (searchModal?.classList.contains('hidden')) {
                openSearch();
            } else {
                closeSearch();
            }
        } else if (e.key === 'Escape' && !searchModal?.classList.contains('hidden')) {
            closeSearch();
        }
    });

    // 2. Mobile Sidebar Toggle
    const mobileMenuBtn = document.getElementById('docs-mobile-menu-btn');
    const mobileSidebar = document.getElementById('docs-sidebar');
    const mobileOverlay = document.getElementById('docs-mobile-overlay');

    const toggleMobileSidebar = () => {
        if (!mobileSidebar || !mobileOverlay) return;
        const isOpen = !mobileSidebar.classList.contains('-translate-x-full');
        if (isOpen) {
            mobileSidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = '';
        } else {
            mobileSidebar.classList.remove('-translate-x-full');
            mobileOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    };

    mobileMenuBtn?.addEventListener('click', toggleMobileSidebar);
    mobileOverlay?.addEventListener('click', toggleMobileSidebar);

    // Auto close mobile sidebar when link inside sidebar is clicked
    mobileSidebar?.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                toggleMobileSidebar();
            }
        });
    });

    // 3. Scrollspy & Active Navigation Highlight
    const updateActiveNav = () => {
        const sections = document.querySelectorAll('#docs-content section[id]');
        const scrollPosition = window.scrollY + 120;
        let currentSectionId = '';

        sections.forEach(sec => {
            const top = sec.offsetTop;
            const height = sec.offsetHeight;
            if (scrollPosition >= top && scrollPosition < top + height) {
                currentSectionId = sec.getAttribute('id');
            }
        });

        if (!currentSectionId && sections.length > 0) {
            currentSectionId = sections[0].getAttribute('id');
        }

        // Highlight in Left Sidebar
        document.querySelectorAll('#docs-sidebar a[href^="#"]').forEach(a => {
            const href = a.getAttribute('href').replace('#', '');
            if (href === currentSectionId) {
                a.classList.add('text-[#1D4ED8]', 'dark:text-blue-400', 'font-semibold', 'bg-blue-50', 'dark:bg-blue-950/40', 'border-l-2', 'border-[#1D4ED8]');
                a.classList.remove('text-slate-600', 'dark:text-slate-400', 'hover:text-slate-900', 'dark:hover:text-white');
            } else {
                a.classList.remove('text-[#1D4ED8]', 'dark:text-blue-400', 'font-semibold', 'bg-blue-50', 'dark:bg-blue-950/40', 'border-l-2', 'border-[#1D4ED8]');
                a.classList.add('text-slate-600', 'dark:text-slate-400', 'hover:text-slate-900', 'dark:hover:text-white');
            }
        });

        // Highlight in Right On-This-Page TOC
        document.querySelectorAll('#docs-toc a[href^="#"]').forEach(a => {
            const href = a.getAttribute('href').replace('#', '');
            if (href === currentSectionId) {
                a.classList.add('text-[#1D4ED8]', 'dark:text-blue-400', 'font-medium', 'border-l-2', 'border-[#1D4ED8]', '-ml-px');
                a.classList.remove('text-slate-500', 'hover:text-slate-900', 'dark:text-zinc-400', 'dark:hover:text-zinc-200');
            } else {
                a.classList.remove('text-[#1D4ED8]', 'dark:text-blue-400', 'font-medium', 'border-l-2', 'border-[#1D4ED8]', '-ml-px');
                a.classList.add('text-slate-500', 'hover:text-slate-900', 'dark:text-zinc-400', 'dark:hover:text-zinc-200');
            }
        });
    };

    window.addEventListener('scroll', updateActiveNav, { passive: true });

    // 4. Code Block Copy to Clipboard
    const setupCopyButtons = () => {
        document.querySelectorAll('.docs-code-block').forEach(wrapper => {
            const copyBtn = wrapper.querySelector('.copy-code-btn');
            const codeEl = wrapper.querySelector('code');
            if (!copyBtn || !codeEl) return;

            copyBtn.addEventListener('click', async () => {
                const textToCopy = codeEl.innerText.trim();
                try {
                    await navigator.clipboard.writeText(textToCopy);
                    const originalContent = copyBtn.innerHTML;
                    copyBtn.innerHTML = `
                        <svg class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span class="text-emerald-400 text-[11px] font-medium">Tersalin</span>
                    `;
                    setTimeout(() => {
                        copyBtn.innerHTML = originalContent;
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy', err);
                }
            });
        });
    };

    // Initialize once DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        buildSearchIndex();
        updateActiveNav();
        setupCopyButtons();
    });
})();

