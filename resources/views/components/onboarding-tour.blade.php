@props([
    'mode' => 'canvas', // 'dashboard' or 'canvas'
    'treeId' => null,
    'isOwner' => true,
])

<div x-data="onboardingTour('{{ $mode }}', {{ json_encode($treeId) }}, {{ $isOwner ? 'true' : 'false' }})"
     x-show="active"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[100] pointer-events-auto select-none"
     style="display: none;"
     @keydown.escape.window="skipTour()"
     @start-onboarding-tour.window="startTour()">

    {{-- Darkened Backdrop with SVG Spotlight Cutout --}}
    <svg class="absolute inset-0 w-full h-full pointer-events-none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <mask id="spotlight-mask-{{ $mode }}-{{ $treeId ?? 'global' }}">
                <rect x="0" y="0" width="100%" height="100%" fill="white" />
                <template x-if="targetRect.width > 0">
                    <rect :x="targetRect.x - 8"
                          :y="targetRect.y - 8"
                          :width="targetRect.width + 16"
                          :height="targetRect.height + 16"
                          rx="16"
                          ry="16"
                          fill="black" />
                </template>
            </mask>
        </defs>
        <rect x="0" y="0" width="100%" height="100%" fill="rgba(15, 23, 42, 0.75)" mask="url(#spotlight-mask-{{ $mode }}-{{ $treeId ?? 'global' }})" class="backdrop-blur-[2px]" />
    </svg>

    {{-- Pulsing Target Highlight Ring --}}
    <div x-show="targetRect.width > 0"
         class="absolute pointer-events-none border-2 border-emerald-400 rounded-2xl transition-all duration-300 shadow-[0_0_25px_rgba(52,211,153,0.6)] animate-pulse"
         :style="`left: ${targetRect.x - 8}px; top: ${targetRect.y - 8}px; width: ${targetRect.width + 16}px; height: ${targetRect.height + 16}px;`">
    </div>

    {{-- Game-Like Floating Tooltip Popover --}}
    <div class="absolute transition-all duration-300 z-[101]"
         :style="popoverStyle">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-5 shadow-2xl max-w-sm w-[90vw] sm:w-96 text-zinc-900 dark:text-zinc-100 relative">
            
            {{-- Top Bar: Quest Badge, Step Counter, and Close Button --}}
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                    <span x-text="currentStep.badgeIcon"></span>
                    <span x-text="currentStep.badgeText"></span>
                </span>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-zinc-400 font-medium" x-text="`Langkah ${currentStepIndex + 1} dari ${steps.length}`"></span>
                    <button type="button" @click="skipTour()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-0.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer" title="Lewati Tutorial">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Quest Progress Bar --}}
            <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-1.5 rounded-full overflow-hidden mb-3">
                <div class="bg-emerald-500 h-full transition-all duration-300"
                     :style="`width: ${((currentStepIndex + 1) / steps.length) * 100}%`"></div>
            </div>

            {{-- Title & Body Description --}}
            <h3 class="text-base font-bold mb-1.5 flex items-center gap-2 text-zinc-900 dark:text-white" x-text="currentStep.title"></h3>
            <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-300 leading-relaxed mb-4" x-html="currentStep.description"></p>

            {{-- Actions: Skip (Lewati) + Prev + Next/Finish --}}
            <div class="flex items-center justify-between pt-3 border-t border-zinc-100 dark:border-zinc-800">
                <button type="button"
                        @click="skipTour()"
                        class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 font-medium hover:underline cursor-pointer">
                    Lewati Tutorial
                </button>
                <div class="flex items-center gap-2">
                    <button type="button"
                            x-show="currentStepIndex > 0"
                            @click="prevStep()"
                            class="px-3 py-1.5 rounded-xl border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer">
                        Kembali
                    </button>
                    <button type="button"
                            @click="nextStep()"
                            class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1 cursor-pointer">
                        <span x-text="currentStepIndex === steps.length - 1 ? 'Selesai! 🎉' : 'Lanjut ➔'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('onboardingTour', (mode, treeId, isOwner) => ({
        active: false,
        currentStepIndex: 0,
        targetRect: { x: 0, y: 0, width: 0, height: 0 },
        storageKey: mode === 'dashboard' ? 'silsilah_tour_dashboard_seen' : 'silsilah_tour_canvas_seen',

        get steps() {
            if (mode === 'dashboard') {
                return [
                    {
                        target: '[data-tour="create-tree-btn"]',
                        badgeIcon: '🌳',
                        badgeText: 'Misi 1: Buat Silsilah',
                        title: 'Membuat Pohon Silsilah Baru',
                        description: 'Klik tombol <strong>Buat Silsilah</strong> untuk memulai pohon keluarga baru. Beri nama keluarga (misal: <em>Keluarga Besar Bani Fulan</em>) dan tentukan akses silsilah Anda.'
                    }
                ];
            }

            return [
                {
                    target: '[data-tour="empty-tree-cta"], [data-tour="add-member-top"], [data-tour="member-card"]',
                    badgeIcon: '👑',
                    badgeText: 'Misi 1: Anggota Pertama',
                    title: 'Tambahkan Sosok Leluhur / Kepala Keluarga',
                    description: 'Mulai silsilah Anda dengan memasukkan <strong>anggota pertama</strong> sebagai akar utama keluarga. Klik tombol <strong>+ Tambah Anggota</strong> untuk mengisi nama, jenis kelamin, serta foto profil.'
                },
                {
                    target: '[data-tour="add-rel-btn"], [data-tour="member-card"]',
                    badgeIcon: '💍',
                    badgeText: 'Misi 2: Tambah Pasangan',
                    title: 'Hubungkan Pasangan (Istri / Suami)',
                    description: 'Arahkan kursor ke kartu anggota dan klik tombol <strong>+ (Tambah Relasi)</strong>. Pilih jenis hubungan <strong>Pasangan</strong>, lalu tentukan status pernikahan: <strong>Menikah (Aktif)</strong>, <strong>Cerai Hidup</strong>, atau <strong>Cerai Mati</strong>.'
                },
                {
                    target: '[data-tour="add-rel-btn"], [data-tour="member-card"]',
                    badgeIcon: '👶',
                    badgeText: 'Misi 3: Tambahkan Anak',
                    title: 'Tambahkan Garis Keturunan (Anak)',
                    description: 'Klik tombol <strong>+ (Tambah Relasi)</strong> pada kartu anggota, lalu pilih jenis hubungan <strong>Anak</strong>. Anda bisa memilih kedua orang tuanya agar garis silsilah langsung tersambung otomatis ke bawah.'
                },
                {
                    target: null,
                    badgeIcon: '🎉',
                    badgeText: 'Tutorial Selesai!',
                    title: 'Siap Membangun Silsilah!',
                    description: 'Luar biasa! Sekarang Anda sudah memahami alur dasar membuat silsilah: pohon baru, leluhur, pasangan, dan anak. Anda bisa menggeser kanvas (drag), zoom, dan mengekspor hasilnya kapan saja.'
                }
            ];
        },

        get currentStep() {
            return this.steps[this.currentStepIndex] || {};
        },

        get popoverStyle() {
            if (!this.targetRect.width || !this.currentStep.target) {
                return 'top: 50%; left: 50%; transform: translate(-50%, -50%);';
            }
            const margin = 20;
            const popoverWidth = 384;
            const screenW = window.innerWidth;
            const screenH = window.innerHeight;

            let left = this.targetRect.x + (this.targetRect.width / 2) - (popoverWidth / 2);
            left = Math.max(margin, Math.min(screenW - popoverWidth - margin, left));

            let top = this.targetRect.y + this.targetRect.height + 16;
            if (top + 260 > screenH) {
                top = Math.max(margin, this.targetRect.y - 270);
            }

            return `top: ${top}px; left: ${left}px;`;
        },

        init() {
            if (!isOwner) return;
            const seen = localStorage.getItem(this.storageKey);
            if (!seen) {
                setTimeout(() => this.startTour(), 1000);
            }
            window.addEventListener('resize', () => {
                if (this.active) this.updateTargetRect();
            });
            window.addEventListener('scroll', () => {
                if (this.active) this.updateTargetRect();
            });
        },

        startTour() {
            this.currentStepIndex = 0;
            this.active = true;
            this.$nextTick(() => this.updateTargetRect());
        },

        nextStep() {
            if (this.currentStepIndex < this.steps.length - 1) {
                this.currentStepIndex++;
                this.$nextTick(() => this.updateTargetRect());
            } else {
                this.finishTour();
            }
        },

        prevStep() {
            if (this.currentStepIndex > 0) {
                this.currentStepIndex--;
                this.$nextTick(() => this.updateTargetRect());
            }
        },

        skipTour() {
            this.active = false;
            localStorage.setItem(this.storageKey, 'true');
        },

        finishTour() {
            this.active = false;
            localStorage.setItem(this.storageKey, 'true');
        },

        updateTargetRect() {
            const selector = this.currentStep.target;
            if (!selector) {
                this.targetRect = { x: 0, y: 0, width: 0, height: 0 };
                return;
            }
            const selectors = selector.split(',').map(s => s.trim());
            let foundEl = null;
            for (const sel of selectors) {
                const el = document.querySelector(sel);
                if (el && el.offsetParent !== null) {
                    foundEl = el;
                    break;
                }
            }

            if (foundEl) {
                const rect = foundEl.getBoundingClientRect();
                this.targetRect = {
                    x: Math.round(rect.left),
                    y: Math.round(rect.top),
                    width: Math.round(rect.width),
                    height: Math.round(rect.height)
                };
            } else {
                this.targetRect = { x: 0, y: 0, width: 0, height: 0 };
            }
        }
    }));
});
</script>
