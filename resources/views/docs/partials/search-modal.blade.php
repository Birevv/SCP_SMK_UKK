<div
    id="docs-search-modal"
    class="fixed inset-0 z-50 bg-slate-900/60 dark:bg-black/80 backdrop-blur-xs hidden flex items-start justify-center pt-16 sm:pt-24 px-4 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
>
    <div class="relative w-full max-w-2xl bg-white dark:bg-[#12141c] border border-slate-200 dark:border-zinc-800 rounded-2xl shadow-xl overflow-hidden flex flex-col transition-all">
        
        {{-- Search Input Header --}}
        <div class="flex items-center gap-3 px-4 py-3.5 border-b border-slate-200 dark:border-zinc-800 bg-slate-50/70 dark:bg-zinc-900/70">
            <svg class="w-5 h-5 text-[#1D4ED8] dark:text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
                type="text"
                id="docs-search-input"
                placeholder="Cari dokumentasi SCP, algoritma, SOP, endpoint API..."
                class="w-full bg-transparent text-slate-900 dark:text-white text-sm sm:text-base placeholder:text-slate-400 dark:placeholder:text-zinc-500 focus:outline-none"
                autocomplete="off"
            />
            <button
                type="button"
                data-docs-search-close
                class="p-1 rounded-md text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-zinc-800 transition-colors"
                aria-label="Tutup pencarian"
            >
                <kbd class="text-[11px] font-mono px-2 py-0.5 rounded bg-slate-200 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300">ESC</kbd>
            </button>
        </div>

        {{-- Search Results Body --}}
        <div
            id="docs-search-results"
            class="max-h-[60vh] overflow-y-auto p-3 space-y-1.5 divide-y divide-slate-100 dark:divide-zinc-800/60"
        >
            <div class="px-4 py-8 text-center text-xs text-slate-500 dark:text-zinc-400">
                Ketik kata kunci untuk mencari dokumentasi SCP, SOP, atau evaluasi proyek...
            </div>
        </div>

        {{-- Footer Search Shortcuts --}}
        <div class="px-4 py-2.5 bg-slate-50 dark:bg-zinc-900/90 border-t border-slate-200 dark:border-zinc-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-zinc-400">
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-300 font-mono text-[10px]">↑</kbd>
                    <kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-300 font-mono text-[10px]">↓</kbd>
                    <span>Navigasi</span>
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-300 font-mono text-[10px]">↵</kbd>
                    <span>Pilih</span>
                </span>
            </div>
            <span class="text-slate-400 dark:text-zinc-500">Dokumentasi Resmi SCP SMK</span>
        </div>
    </div>
</div>

