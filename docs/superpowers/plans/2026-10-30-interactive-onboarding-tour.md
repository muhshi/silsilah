# Interactive Game-Like Onboarding Walkthrough Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a game-like, interactive onboarding walkthrough (spotlight tutorial) for new users with a clear **Skip button**, focused strictly on:
1. Cara menambah pohon (Dashboard)
2. Cara menambah anggota pertama / akar silsilah (Tree Canvas)
3. Cara menambah pasangan beserta status pernikahan (Tree Canvas)
4. Cara menambah anak (Tree Canvas)
5. Kemudahan untuk melewati (Skip) kapan saja serta membuka kembali panduan dari menu pengaturan/bantuan.

**Architecture:**
- A unified, lightweight Alpine.js component `x-onboarding-tour` supporting SVG spotlight overlay, pulsing highlight rings, step cards with quest badges, progress counters, and prominent "Lewati / Skip" buttons.
- Supports two context-aware modes:
  - `mode="dashboard"`: Highlights the **"Buat Silsilah"** CTA on the dashboard for first-time users.
  - `mode="canvas"`: Highlights (1) Anggota Pertama, (2) Tombol `+` Pasangan & Status Pernikahan, and (3) Tombol `+` Anak di kanvas silsilah.
- State is persisted via `localStorage` (`silsilah_tour_dashboard_seen`, `silsilah_tour_canvas_seen`) so it never nags returning users.
- Replayable anytime via a "Panduan Tutorial" button in the Dashboard and Tree Settings menu.

**Tech Stack:** Laravel 13, Livewire 4, Alpine.js, Tailwind CSS v4, Flux UI, Pest PHP.

---

### Task 1: Create the Reusable Alpine.js Onboarding Tour Component

**Files:**
- Create: `resources/views/components/onboarding-tour.blade.php`

- [ ] **Step 1: Write `resources/views/components/onboarding-tour.blade.php`**

```blade
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
                <rect :x="targetRect.x - 8"
                      :y="targetRect.y - 8"
                      :width="targetRect.width + 16"
                      :height="targetRect.height + 16"
                      rx="16"
                      ry="16"
                      fill="black" />
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
            
            {{-- Top Bar: Quest Badge & Step Counter & Close/Skip 'X' --}}
            <div class="flex items-center justify-between gap-2 mb-3">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                    <span x-text="currentStep.badgeIcon"></span>
                    <span x-text="currentStep.badgeText"></span>
                </span>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-zinc-400 font-medium" x-text="`Langkah ${currentStepIndex + 1} dari ${steps.length}`"></span>
                    <button type="button" @click="skipTour()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-0.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors" title="Lewati Tutorial">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Title & Body Description --}}
            <h3 class="text-base font-bold mb-1.5 flex items-center gap-2 text-zinc-900 dark:text-white" x-text="currentStep.title"></h3>
            <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-300 leading-relaxed mb-4" x-html="currentStep.description"></p>

            {{-- Actions: Skip (Lewati) + Prev + Next/Finish --}}
            <div class="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-800">
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
        storageKey: mode === 'dashboard' ? 'silsilah_tour_dashboard_seen' : `silsilah_tour_canvas_seen_${treeId}`,

        get steps() {
            if (mode === 'dashboard') {
                return [
                    {
                        target: '[data-tour="create-tree-btn"]',
                        badgeIcon: '🌳',
                        badgeText: 'Misi 1: Buat Silsilah',
                        title: 'Membuat Pohon Silsilah Baru',
                        description: 'Klik tombol <strong>Buat Silsilah</strong> untuk memulai pohon silsilah keluarga Anda. Beri nama keluarga (misal: <em>Keluarga Besar Bani Fulan</em>) dan pilih apakah pohon bersifat privat atau publik.'
                    }
                ];
            }

            // Canvas mode steps: 1. Tambah Pertama, 2. Tambah Pasangan, 3. Tambah Anak
            return [
                {
                    target: '[data-tour="empty-tree-cta"], [data-tour="add-member-top"], [data-tour="first-member-node"]',
                    badgeIcon: '👑',
                    badgeText: 'Misi 1: Anggota Pertama',
                    title: 'Tambahkan Sosok Leluhur / Kepala Keluarga',
                    description: 'Mulai silsilah Anda dengan memasukkan <strong>anggota pertama</strong> (misal: kakek tertua atau kepala keluarga). Klik tombol <strong>+ Tambah Anggota</strong> untuk mengisi nama, jenis kelamin, serta foto/gelar.'
                },
                {
                    target: '[data-tour="spouse-action-btn"], [data-tour="first-member-node"]',
                    badgeIcon: '💍',
                    badgeText: 'Misi 2: Tambah Pasangan',
                    title: 'Hubungkan Pasangan (Suami / Istri)',
                    description: 'Arahkan kursor ke kartu anggota dan klik ikon <strong>+ Pasangan</strong> (atau tombol pasangan). Anda dapat mencatat nama pasangan serta status pernikahan: <strong>Menikah</strong>, <strong>Cerai Hidup</strong>, atau <strong>Cerai Mati</strong>.'
                },
                {
                    target: '[data-tour="child-action-btn"], [data-tour="first-member-node"]',
                    badgeIcon: '👶',
                    badgeText: 'Misi 3: Tambah Anak',
                    title: 'Tambahkan Keturunan (Anak)',
                    description: 'Klik ikon <strong>+ Anak</strong> di bawah kartu untuk menyambungkan anak ke orang tua. Pohon akan otomatis bercabang ke bawah secara rapi menghubungkan generasi berikutnya.'
                },
                {
                    target: null,
                    badgeIcon: '🎉',
                    badgeText: 'Tutorial Selesai!',
                    title: 'Siap Membangun Silsilah!',
                    description: 'Luar biasa! Sekarang Anda sudah memahami 3 langkah utama membangun silsilah. Anda bisa menggeser kanvas (drag), memperbesar/memperkecil (zoom), dan mengekspor hasilnya kapan saja.'
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
            if (top + 240 > screenH) {
                top = Math.max(margin, this.targetRect.y - 250);
            }

            return `top: ${top}px; left: ${left}px;`;
        },

        init() {
            if (!isOwner) return;
            const seen = localStorage.getItem(this.storageKey);
            if (!seen) {
                setTimeout(() => this.startTour(), 1200);
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
            // If comma-separated, pick first matching visible element
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
```

---

### Task 2: Integrate Tour into Dashboard and Canvas

**Files:**
- Modify: `resources/views/components/⚡dashboard-trees.blade.php`
- Modify: `resources/views/livewire/tree-view.blade.php`
- Modify: `resources/views/components/tree-node.blade.php`

- [ ] **Step 1: Update `⚡dashboard-trees.blade.php`**
  - Add `data-tour="create-tree-btn"` on the "Buat Silsilah" button:
    ```blade
    <button wire:click="openCreateTree"
            data-tour="create-tree-btn"
            class="...">
        ...
    </button>
    ```
  - Include `<x-onboarding-tour mode="dashboard" />` at the bottom.
  - Add a "Panduan Tutorial" help button next to the hero section or header so users can trigger `@click="$dispatch('start-onboarding-tour')"` anytime.

- [ ] **Step 2: Update `tree-view.blade.php`**
  - Add empty state in `#myTree` with `data-tour="empty-tree-cta"` if `$rootMembers->isEmpty()`.
  - Add `data-tour="add-member-top"` on the top "+ Anggota Baru" / "+ Anggota" button.
  - Include `<x-onboarding-tour mode="canvas" :tree-id="$tree->id" :is-owner="!$isPublic" />` at the bottom.
  - Add "Panduan Tutorial Silsilah" inside the Settings Dropdown menu:
    ```blade
    <flux:menu.item icon="academic-cap" @click="$dispatch('start-onboarding-tour')">
        Panduan Tutorial Silsilah
    </flux:menu.item>
    ```

- [ ] **Step 3: Update `tree-node.blade.php`**
  - Add `data-tour="first-member-node"` on the main member card container.
  - Add `data-tour="spouse-action-btn"` on the `+` spouse button.
  - Add `data-tour="child-action-btn"` on the `+` child button.

---

### Task 3: Feature Tests & Verification

**Files:**
- Create: `tests/Feature/OnboardingTourTest.php`

- [ ] **Step 1: Write Pest tests for Onboarding Tour**

```php
<?php

use App\Models\User;
use App\Models\FamilyTree;
use App\Models\Member;

test('dashboard renders onboarding tour component and create tree trigger', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-tour="create-tree-btn"', false)
        ->assertSee('onboardingTour(\'dashboard\'', false);
});

test('tree canvas renders onboarding tour component, actions, and settings trigger', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user)
        ->get(route('tree.show', $tree->id))
        ->assertOk()
        ->assertSee('onboardingTour(\'canvas\'', false)
        ->assertSee('data-tour="add-member-top"', false)
        ->assertSee('Panduan Tutorial Silsilah');
});

test('empty tree canvas shows empty state CTA with tour hook', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user)
        ->get(route('tree.show', $tree->id))
        ->assertOk()
        ->assertSee('data-tour="empty-tree-cta"', false)
        ->assertSee('Tambah Anggota Pertama');
});
```

- [ ] **Step 2: Run Pest tests**
```bash
php artisan test --compact tests/Feature/OnboardingTourTest.php
```

- [ ] **Step 3: Run Pint Code Formatter**
```bash
vendor/bin/pint --dirty --format agent
```

---

### Task 4: Deployment & Review

- [ ] **Step 1: Git commit & push**
- [ ] **Step 2: Deploy to server via SSH (`deploy.sh`)**
- [ ] **Step 3: Verify live on server**
