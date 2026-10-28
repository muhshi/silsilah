<div x-data="exportProgressManager()"
     @start-export.window="startExport($event.detail)"
     class="relative"
     style="font-family: inherit;">

    {{-- 1. FULL / CENTERED MODAL DIALOG (Shown when active and NOT minimized) --}}
    <div x-show="active && !minimized"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         style="display: none;">

        <div @click.away="minimize()"
             class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-2xl p-6 text-zinc-900 dark:text-zinc-100">

            {{-- Top Bar: Status Icon & Controls --}}
            <div class="flex items-center justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                         :class="{
                             'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400': status === 'completed',
                             'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400': status === 'error',
                             'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400 animate-pulse': status !== 'completed' && status !== 'error'
                         }">
                        <template x-if="status === 'completed'">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <template x-if="status === 'error'">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </template>
                        <template x-if="status !== 'completed' && status !== 'error'">
                            <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                    </div>

                    <div>
                        <h4 class="font-bold text-base text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <span x-text="statusTitle">Mengekspor Silsilah</span>
                        </h4>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400" x-text="exportTitle || 'Memproses berkas...'"></p>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    {{-- Minimize Button --}}
                    <button @click="minimize()"
                            type="button"
                            title="Minimalkan ke pojok bawah"
                            class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    </button>
                    {{-- Close Button --}}
                    <button @click="closeModal()"
                            type="button"
                            title="Tutup / Batalkan"
                            class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Main Progress Section --}}
            <div class="py-5">
                <template x-if="status !== 'error'">
                    <div>
                        {{-- Progress Percentage Bar --}}
                        <div class="flex items-center justify-between text-xs font-semibold mb-2">
                            <span class="text-zinc-600 dark:text-zinc-300" x-text="stepDescription">Menyiapkan berkas...</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="`${Math.round(progress)}%`"></span>
                        </div>

                        {{-- Bar container --}}
                        <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2.5 overflow-hidden p-0.5">
                            <div class="h-full rounded-full transition-all duration-300 ease-out"
                                 :class="{
                                     'bg-emerald-500': status === 'completed',
                                     'bg-gradient-to-r from-emerald-500 via-teal-400 to-indigo-500': status !== 'completed'
                                 }"
                                 :style="`width: ${progress}%`"></div>
                        </div>

                        {{-- Helpful Tip / Context --}}
                        <p class="mt-4 text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed bg-zinc-50 dark:bg-zinc-800/40 p-3 rounded-xl border border-zinc-100 dark:border-zinc-800">
                            💡 <strong>Tips:</strong> Proses ekspor menyusun dokumen resolusi tinggi secara utuh. Anda dapat mengklik <strong>"Minimalkan"</strong> untuk terus menjelajahi silsilah selagi dokumen diproses.
                        </p>
                    </div>
                </template>

                <template x-if="status === 'error'">
                    <div class="text-center py-2">
                        <p class="text-sm text-rose-600 dark:text-rose-400 font-medium mb-2" x-text="errorMessage"></p>
                        <template x-if="errorMessage.includes('Premium')">
                            <div class="mt-3 mb-2">
                                <a href="{{ route('dashboard') }}"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition shadow-xs">
                                    <span>👑 Upgrade Pohon Rp 5K</span>
                                </a>
                            </div>
                        </template>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4" x-show="!errorMessage.includes('Premium')">
                            Silakan periksa koneksi Anda atau coba ekspor kembali beberapa saat lagi.
                        </p>
                    </div>
                </template>
            </div>

            {{-- Bottom Actions --}}
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800/80">
                <template x-if="status !== 'completed' && status !== 'error'">
                    <div class="flex w-full gap-2 justify-end">
                        <button type="button"
                                @click="minimize()"
                                class="px-4 py-2 text-xs font-semibold rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 transition">
                            Minimalkan ke Pojok
                        </button>
                        <button type="button"
                                @click="cancelExport()"
                                class="px-4 py-2 text-xs font-semibold rounded-xl bg-zinc-50 hover:bg-rose-50 text-zinc-500 hover:text-rose-600 dark:bg-zinc-800/50 dark:hover:bg-rose-950/40 dark:text-zinc-400 dark:hover:text-rose-400 transition">
                            Batalkan
                        </button>
                    </div>
                </template>

                <template x-if="status === 'completed'">
                    <button type="button"
                            @click="closeModal()"
                            class="px-5 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/20 transition">
                        Selesai
                    </button>
                </template>

                <template x-if="status === 'error'">
                    <div class="flex gap-2">
                        <button type="button"
                                @click="retryExport()"
                                class="px-4 py-2 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 transition">
                            Coba Lagi
                        </button>
                        <button type="button"
                                @click="closeModal()"
                                class="px-4 py-2 text-xs font-semibold rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                            Tutup
                        </button>
                    </div>
                </template>
            </div>

        </div>
    </div>

    {{-- 2. MINIMIZED FLOATING PILL (Bottom Right Corner) --}}
    <div x-show="active && minimized"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-6 scale-95"
         class="fixed bottom-6 right-6 z-50 flex items-center gap-3 bg-zinc-900/95 dark:bg-zinc-950/95 text-white backdrop-blur-md px-4 py-3 rounded-2xl shadow-2xl border border-white/10 hover:border-emerald-500/40 transition-all group"
         style="display: none;">

        {{-- Progress ring or icon --}}
        <div class="relative w-7 h-7 flex-shrink-0 flex items-center justify-center">
            <template x-if="status === 'completed'">
                <div class="w-7 h-7 rounded-full bg-emerald-500/20 border border-emerald-400 flex items-center justify-center text-emerald-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </template>
            <template x-if="status === 'error'">
                <div class="w-7 h-7 rounded-full bg-rose-500/20 border border-rose-400 flex items-center justify-center text-rose-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
            </template>
            <template x-if="status !== 'completed' && status !== 'error'">
                <svg class="w-7 h-7 animate-spin text-emerald-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </template>
        </div>

        {{-- Text Info (Clickable to maximize) --}}
        <div @click="maximize()" class="cursor-pointer select-none pr-1">
            <div class="text-xs font-bold text-white flex items-center gap-1.5">
                <span x-text="exportTitle || 'Ekspor Silsilah'"></span>
                <span class="text-emerald-400 font-extrabold" x-show="status !== 'error'" x-text="`(${Math.round(progress)}%)`"></span>
            </div>
            <div class="text-[11px] text-zinc-400 truncate max-w-[200px]" x-text="stepDescription"></div>
        </div>

        {{-- Maximize Button --}}
        <button @click="maximize()"
                type="button"
                title="Buka kembali tampilan besar"
                class="p-1 text-zinc-400 hover:text-white rounded-lg hover:bg-white/10 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
        </button>

        {{-- Dismiss Button --}}
        <button @click="closeModal()"
                type="button"
                title="Tutup"
                class="p-1 text-zinc-400 hover:text-white rounded-lg hover:bg-white/10 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

</div>

<script>
function exportProgressManager() {
    return {
        active: false,
        minimized: false,
        status: 'idle', // 'idle' | 'preparing' | 'rendering' | 'downloading' | 'completed' | 'error'
        exportTitle: '',
        exportUrl: '',
        progress: 0,
        statusTitle: 'Mengekspor Silsilah',
        stepDescription: 'Menyiapkan berkas...',
        errorMessage: '',
        tickerInterval: null,
        autoCloseTimer: null,
        abortController: null,

        startExport(detail) {
            if (!detail || !detail.url) return;

            // Reset state
            this.active = true;
            this.minimized = false;
            this.status = 'preparing';
            this.exportUrl = detail.url;
            this.exportTitle = detail.title || 'Dokumen Silsilah';
            this.statusTitle = `Mengekspor ${this.exportTitle}`;
            this.progress = 8;
            this.stepDescription = 'Menyiapkan data anggota & relasi silsilah...';
            this.errorMessage = '';

            if (this.autoCloseTimer) clearTimeout(this.autoCloseTimer);
            if (this.tickerInterval) clearInterval(this.tickerInterval);
            if (this.abortController) this.abortController.abort();

            this.abortController = new AbortController();

            // Realistic simulated progress steps while waiting for server Browsershot/render
            this.tickerInterval = setInterval(() => {
                if (this.status === 'completed' || this.status === 'error') {
                    clearInterval(this.tickerInterval);
                    return;
                }

                if (this.progress < 25) {
                    this.progress += 2.5;
                    this.stepDescription = 'Menyiapkan data anggota & relasi silsilah...';
                } else if (this.progress < 55) {
                    this.progress += 1.8;
                    this.stepDescription = 'Mengolah foto, avatar, dan menyusun tata letak...';
                } else if (this.progress < 80) {
                    this.progress += 1.2;
                    this.stepDescription = 'Merender halaman dokumen resolusi tinggi dengan Chromium...';
                } else if (this.progress < 93) {
                    this.progress += 0.5;
                    this.stepDescription = 'Menyelesaikan konversi dokumen...';
                }
            }, 300);

            // Execute fetch
            fetch(this.exportUrl, {
                method: 'GET',
                signal: this.abortController.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async (response) => {
                if (!response.ok) {
                    if (response.status === 403) {
                        throw new Error('Fitur Cetak Buku Silsilah (PDF) khusus untuk Pohon Premium.');
                    }
                    throw new Error(`Gagal mengunduh (Status ${response.status})`);
                }

                this.stepDescription = 'Mengunduh file ke perangkat Anda...';
                this.progress = 96;

                // Extract filename
                let filename = 'silsilah-keluarga';
                const disposition = response.headers.get('content-disposition');
                if (disposition && disposition.includes('filename=')) {
                    const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                    if (match && match[1]) {
                        filename = match[1].replace(/['"]/g, '').trim();
                    }
                }

                const blob = await response.blob();
                clearInterval(this.tickerInterval);

                // Trigger direct browser file save
                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();

                setTimeout(() => {
                    a.remove();
                    window.URL.revokeObjectURL(downloadUrl);
                }, 1000);

                // Success state
                this.progress = 100;
                this.status = 'completed';
                this.statusTitle = 'Ekspor Berhasil!';
                this.stepDescription = `File ${filename} berhasil diunduh.`;

                // Auto-close after 4 seconds
                this.autoCloseTimer = setTimeout(() => {
                    this.closeModal();
                }, 4000);
            })
            .catch((err) => {
                if (err.name === 'AbortError') {
                    this.closeModal();
                    return;
                }
                clearInterval(this.tickerInterval);
                this.status = 'error';
                this.statusTitle = 'Ekspor Gagal';
                this.errorMessage = err.message || 'Terjadi kesalahan saat memproses ekspor.';
                this.stepDescription = 'Gagal memproses dokumen.';
            });
        },

        minimize() {
            this.minimized = true;
        },

        maximize() {
            this.minimized = false;
        },

        cancelExport() {
            if (this.abortController) {
                this.abortController.abort();
            }
            this.closeModal();
        },

        retryExport() {
            if (this.exportUrl) {
                this.startExport({
                    url: this.exportUrl,
                    title: this.exportTitle
                });
            }
        },

        closeModal() {
            this.active = false;
            this.minimized = false;
            if (this.tickerInterval) clearInterval(this.tickerInterval);
            if (this.autoCloseTimer) clearTimeout(this.autoCloseTimer);
        }
    };
}
</script>
