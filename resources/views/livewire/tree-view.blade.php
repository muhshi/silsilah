<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\FamilyTree;

new class extends Component
{
    public $tree;
    public $isPublic = false;
    public $publicSlug = null;

    public function mount($id, $isPublic = false, $publicSlug = null)
    {
        $this->isPublic = $isPublic;
        $this->publicSlug = $publicSlug;

        $this->tree = is_numeric($id)
            ? FamilyTree::with([
                'members',
                'members.marriagesAsHusband.wife',
                'members.marriagesAsWife.husband',
            ])->find($id) ?? FamilyTree::with([
                'members',
                'members.marriagesAsHusband.wife',
                'members.marriagesAsWife.husband',
            ])->where('slug', $id)->firstOrFail()
            : FamilyTree::with([
                'members',
                'members.marriagesAsHusband.wife',
                'members.marriagesAsWife.husband',
            ])->where('slug', $id)->first() ?? FamilyTree::with([
                'members',
                'members.marriagesAsHusband.wife',
                'members.marriagesAsWife.husband',
            ])->findOrFail($id);

        if (!$this->isPublic) {
            if ($this->tree->users()->where('user_id', auth()->id())->doesntExist() && !$this->tree->is_public) {
                abort(403, 'Unauthorized access to this family tree.');
            }
        }
    }

    #[On('refresh-tree')]
    public function refreshTree()
    {
        $this->mount($this->tree->id);
    }

    public function leaveTree()
    {
        $this->tree->users()->detach(auth()->id());
        session()->flash('success', 'Anda telah keluar dari pohon ini.');
        return redirect()->route('dashboard');
    }

    public function with()
    {
        $this->tree->loadMissing([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ]);

        $allMembers = $this->tree->members;

        $nonRootIds = collect();
        foreach ($allMembers as $m) {
            if ($m->father_id !== null || $m->mother_id !== null) {
                $nonRootIds->push($m->id);
            }
        }
        foreach ($allMembers as $m) {
            if ($m->relationLoaded('marriagesAsHusband')) {
                foreach ($m->marriagesAsHusband as $marriage) {
                    $husband = $allMembers->firstWhere('id', $marriage->husband_id);
                    $wife = $allMembers->firstWhere('id', $marriage->wife_id);
                    if ($husband && $wife) {
                        $hHasParents = $husband->father_id !== null || $husband->mother_id !== null;
                        $wHasParents = $wife->father_id !== null || $wife->mother_id !== null;
                        if ($hHasParents || $wHasParents) {
                            $nonRootIds->push($husband->id);
                            $nonRootIds->push($wife->id);
                        } else {
                            $nonRootIds->push($wife->id);
                        }
                    }
                }
            }
        }
        $rootMembers = $allMembers->whereNotIn('id', $nonRootIds->unique());

        return [
            'tree' => $this->tree,
            'rootMembers' => $rootMembers,
            'allMembers' => $allMembers,
        ];
    }
};
?>

<div class="w-full max-w-[100vw]" x-data="canvasTree()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between px-4 lg:px-8 py-3.5 gap-3 border-b border-zinc-200/60 dark:border-zinc-800/80 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md sticky top-0 z-30">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white flex items-center justify-center shadow-md shadow-emerald-600/10 flex-shrink-0">
                <span class="text-xl">🌳</span>
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ $tree->name }}</h1>
                    @if($tree->is_public)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">Publik</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">Privat</span>
                    @endif
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $tree->description ?: 'Bagan silsilah keluarga interaktif' }}</p>
            </div>
        </div>

        {{-- Desktop Action Buttons --}}
        <div class="hidden md:flex items-center gap-2.5 flex-wrap">
            {{-- 1. View Mode Switcher (Segmented Pill) --}}
            <div class="flex items-center bg-zinc-100/90 dark:bg-zinc-800/90 p-1 rounded-full border border-zinc-200/60 dark:border-zinc-700/60 text-xs font-semibold">
                <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug) : '#') : route('tree.show', $tree) }}"
                   wire:navigate
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-full transition-all bg-white dark:bg-zinc-700 text-emerald-700 dark:text-emerald-300 shadow-sm font-bold">
                    <span>🌳</span>
                    <span>Bagan</span>
                </a>
                <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/vertical') : '#') : route('tree.vertical', $tree) }}"
                   wire:navigate
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-full transition-all text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200">
                    <span>🧭</span>
                    <span>Fokus</span>
                </a>
                <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/simple') : '#') : route('tree.simple', $tree) }}"
                   wire:navigate
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-full transition-all text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200">
                    <span>📄</span>
                    <span>Simple</span>
                </a>
            </div>

            {{-- 2. Utilities: Share & Export --}}
            @if($tree->slug)
                @php
                    $shareUrl = url('/public/tree/' . $tree->slug);
                    $shareMsg = "Halo! Yuk lihat diagram silsilah keluarga \"{$tree->name}\" di Silsilah:\n\n{$shareUrl}";
                @endphp
                <flux:button size="sm" icon="share" x-data="{ shared: false, async share() {
                    const text = {{ json_encode($shareMsg) }};
                    let ok = false;
                    if (navigator.clipboard && window.isSecureContext) {
                        try {
                            await navigator.clipboard.writeText(text);
                            ok = true;
                        } catch(e) {}
                    }
                    if (!ok) {
                        try {
                            const el = document.createElement('textarea');
                            el.value = text;
                            el.style.position = 'fixed';
                            el.style.left = '-999999px';
                            document.body.appendChild(el);
                            el.focus();
                            el.select();
                            document.execCommand('copy');
                            document.body.removeChild(el);
                            ok = true;
                        } catch(e) {}
                    }
                    if (ok) {
                        this.shared = true;
                        setTimeout(() => this.shared = false, 2500);
                    }
                } }"
                    x-on:click="share()"
                    class="!bg-zinc-100 !text-zinc-700 hover:!bg-emerald-50 hover:!text-emerald-700 dark:!bg-zinc-800 dark:!text-zinc-300 dark:hover:!bg-emerald-950/40">
                    <span x-show="!shared">Bagikan</span>
                    <span x-show="shared" class="text-emerald-600 dark:text-emerald-400 font-bold">Tersalin!</span>
                </flux:button>
            @endif

            <flux:dropdown>
                <flux:button size="sm" icon="arrow-down-tray" class="!bg-zinc-100 !text-zinc-700 hover:!bg-purple-50 hover:!text-purple-700 dark:!bg-zinc-800 dark:!text-zinc-300 dark:hover:!bg-purple-950/40">
                    Ekspor
                </flux:button>
                <flux:menu>
                    <flux:menu.item icon="photo" href="{{ route('tree.export', ['tree' => $tree, 'format' => 'png', 'view' => 'horizontal']) }}">
                        Export Gambar (PNG)
                    </flux:menu.item>
                    <flux:menu.item icon="document-arrow-down" href="{{ route('tree.export', ['tree' => $tree, 'format' => 'pdf', 'view' => 'horizontal']) }}">
                        Export Dokumen (PDF)
                    </flux:menu.item>
                    <flux:menu.item icon="sparkles" href="{{ route('tree.export', ['tree' => $tree, 'format' => 'prompt']) }}">
                        Export Prompt AI (.md)
                    </flux:menu.item>
                    <flux:menu.item icon="code-bracket" href="{{ route('tree.export', ['tree' => $tree, 'format' => 'json']) }}">
                        Export Data (JSON)
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            @if(!$isPublic)
                {{-- 3. Overflow Menu (Import & Tree Settings) --}}
                <flux:dropdown>
                    <flux:button size="sm" icon="ellipsis-horizontal" class="!bg-zinc-100 !text-zinc-700 hover:!bg-zinc-200 dark:!bg-zinc-800 dark:!text-zinc-300">
                        Opsi
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item icon="arrow-down-on-square" wire:click="$dispatch('open-import-modal')">
                            Import Data CSV/GEDCOM
                        </flux:menu.item>
                        <flux:menu.item icon="cog-6-tooth" wire:click="$dispatch('open-tree-settings')">
                            Pengaturan Silsilah
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                {{-- 4. Primary Action Button --}}
                <flux:button size="sm" icon="plus" wire:click="$dispatch('create-member')" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 font-bold !rounded-full shadow-md shadow-emerald-600/20">
                    Anggota Baru
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Segmented View Switcher (Mobile Only) --}}
    <div class="md:hidden px-4 mb-3">
        <div class="flex items-center bg-zinc-100 dark:bg-zinc-800 p-1 rounded-xl shadow-inner gap-1">
            <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/vertical') : '#') : route('tree.vertical', $tree) }}"
               wire:navigate
               class="flex-1 py-1.5 px-3 rounded-lg text-xs font-semibold text-center text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center justify-center gap-1.5 transition-all">
                <span>🧭</span>
                <span>Fokus Explorer</span>
            </a>
            <button class="flex-1 py-1.5 px-3 rounded-lg text-xs font-semibold bg-emerald-600 text-white shadow-md flex items-center justify-center gap-1.5 transition-all">
                <span>🌳</span>
                <span>Bagan Canvas</span>
            </button>
        </div>
    </div>

    {{-- Tree Canvas --}}
    <div class="px-4 lg:px-8 pb-20 md:pb-8">
        <div class="pt-sm touch-none select-none" x-ref="container"
             @mousedown="startDrag($event)"
             @mousemove="doDrag($event)"
             @mouseup="stopDrag()"
             @mouseleave="stopDrag()"
             @wheel.prevent="doZoom($event)"
             @touchstart="startTouch($event)"
             @touchmove.prevent="doTouch($event)"
             @touchend="stopTouch($event)"
             @touchcancel="stopTouch($event)">

            <div class="tree-inner" x-ref="inner" :style="transformStyle">
                <div class="tree" id="myTree">
                    <ul>
                        @foreach($rootMembers as $member)
                            <x-tree-node :member="$member" :all-members="$allMembers" />
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Material 3 Floating Zoom Controls --}}
            <div class="fixed md:absolute bottom-20 md:bottom-5 right-4 z-30 flex flex-col items-center bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md p-1.5 rounded-2xl shadow-xl border border-zinc-200/80 dark:border-zinc-800 gap-1 select-none" @touchstart.stop @click.stop>
                <button @click="zoomIn()" title="Zoom In" class="w-10 h-10 rounded-xl bg-zinc-100 hover:bg-emerald-50 dark:bg-zinc-800 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-200 hover:text-emerald-600 flex items-center justify-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>

                <div class="text-[10px] font-extrabold text-zinc-500 dark:text-zinc-400 py-0.5" x-text="Math.round(scale * 100) + '%'"></div>

                <button @click="resetView()" title="Fit to Screen / Pusatkan" class="w-10 h-10 rounded-xl bg-zinc-100 hover:bg-emerald-50 dark:bg-zinc-800 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-200 hover:text-emerald-600 flex items-center justify-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/></svg>
                </button>

                <button @click="zoomOut()" title="Zoom Out" class="w-10 h-10 rounded-xl bg-zinc-100 hover:bg-emerald-50 dark:bg-zinc-800 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-200 hover:text-emerald-600 flex items-center justify-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- STICKY BOTTOM NAVIGATION BAR (Mobile Only) --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-around px-2 z-40 shadow-lg">
        <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/vertical') : '#') : route('tree.vertical', $tree) }}"
           wire:navigate
           class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold text-zinc-500 dark:text-zinc-400 hover:text-emerald-600">
            <span class="text-lg">🧭</span>
            <span class="text-[11px]">Fokus</span>
        </a>

        <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/vertical?tab=directory') : '#') : route('tree.vertical', ['tree' => $tree, 'tab' => 'directory']) }}"
           wire:navigate
           class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold text-zinc-500 dark:text-zinc-400 hover:text-emerald-600">
            <span class="text-lg">👥</span>
            <span class="text-[11px]">Direktori</span>
        </a>

        <div class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
            <span class="text-lg">🌳</span>
            <span class="text-[11px]">Bagan</span>
        </div>

        @if(!$isPublic)
            <button wire:click="$dispatch('create-member')"
                    class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <span class="text-lg font-bold">➕</span>
                <span class="text-[11px]">Tambah</span>
            </button>
        @endif
    </nav>

    {{-- Member Manager & Modals --}}
    @if(!$isPublic)
        <livewire:member-manager :tree-id="$tree->id" />
        <livewire:tree-settings :tree-id="$tree->id" />
        <livewire:import-members :tree-id="$tree->id" />
    @endif
    {{-- Floating Loading Indicator --}}
    <div wire:loading.flex class="fixed bottom-20 md:bottom-6 right-6 bg-zinc-900/90 text-white px-4 py-2.5 rounded-full shadow-2xl items-center gap-3 text-sm font-medium z-50 border border-zinc-700/50 backdrop-blur-md">
        <svg class="animate-spin h-4 w-4 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Memuat data...</span>
    </div>
</div>

@script
<script>
    Alpine.data('canvasTree', () => ({
        scale: 1,
        panX: 0,
        panY: 0,
        dragging: false,
        startX: 0,
        startY: 0,
        initialPinchDistance: 0,
        initialScale: 1,
        pinchCenterX: 0,
        pinchCenterY: 0,
        lastTapTime: 0,

        get transformStyle() {
            return `transform: translate(${this.panX}px, ${this.panY}px) scale(${this.scale})`;
        },

        init() {
            this.$nextTick(() => {
                setTimeout(() => this.centerTree(), 100);
            });
            window.addEventListener('resize', () => this.centerTree());
        },

        centerTree() {
            const container = this.$refs.container;
            const inner = this.$refs.inner;
            if (!container || !inner) return;

            const treeEl = document.getElementById('myTree') || inner;
            const treeW = treeEl.scrollWidth || inner.scrollWidth || 800;
            const treeH = treeEl.scrollHeight || inner.scrollHeight || 600;
            const containerW = container.clientWidth || window.innerWidth;
            const containerH = container.clientHeight || 600;

            const marginX = containerW < 768 ? 20 : 60;
            const marginY = 40;

            const scaleX = (containerW - marginX) / treeW;
            const scaleY = (containerH - marginY) / treeH;
            
            let optimalScale = Math.min(1.0, Math.min(scaleX, scaleY));
            if (containerW < 768) {
                optimalScale = Math.min(0.85, Math.max(0.35, scaleX));
            } else {
                optimalScale = Math.min(1.0, Math.max(0.45, optimalScale));
            }

            this.scale = parseFloat(optimalScale.toFixed(2));
            this.panX = Math.round((containerW - (treeW * this.scale)) / 2);
            this.panY = 24;
        },

        startDrag(e) {
            if (e.target.closest('a') || e.target.closest('button') || e.target.closest('.pt-zoom-controls')) return;
            this.dragging = true;
            this.startX = e.clientX - this.panX;
            this.startY = e.clientY - this.panY;
        },
        doDrag(e) {
            if (!this.dragging) return;
            e.preventDefault();
            this.panX = e.clientX - this.startX;
            this.panY = e.clientY - this.startY;
        },
        stopDrag() { this.dragging = false; },

        doZoom(e) {
            const delta = e.deltaY > 0 ? -0.06 : 0.06;
            const newScale = Math.min(2.5, Math.max(0.2, parseFloat((this.scale + delta).toFixed(2))));
            const rect = this.$refs.container.getBoundingClientRect();
            const cx = e.clientX - rect.left;
            const cy = e.clientY - rect.top;
            const ratio = newScale / this.scale;
            this.panX = Math.round(cx - ratio * (cx - this.panX));
            this.panY = Math.round(cy - ratio * (cy - this.panY));
            this.scale = newScale;
        },

        startTouch(e) {
            if (e.target.closest('a') || e.target.closest('button') || e.target.closest('.pt-zoom-controls')) return;

            // Handle double tap to fit / zoom
            const now = Date.now();
            if (now - this.lastTapTime < 300 && e.touches.length === 1) {
                if (this.scale < 0.9) {
                    this.scale = 1.0;
                } else {
                    this.centerTree();
                }
                this.lastTapTime = 0;
                return;
            }
            this.lastTapTime = now;

            if (e.touches.length === 1) {
                this.dragging = true;
                this.startX = e.touches[0].clientX - this.panX;
                this.startY = e.touches[0].clientY - this.panY;
                this.initialPinchDistance = 0;
            } else if (e.touches.length === 2) {
                this.dragging = false;
                this.initialPinchDistance = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                this.initialScale = this.scale;
                const rect = this.$refs.container.getBoundingClientRect();
                this.pinchCenterX = (e.touches[0].clientX + e.touches[1].clientX) / 2 - rect.left;
                this.pinchCenterY = (e.touches[0].clientY + e.touches[1].clientY) / 2 - rect.top;
            }
        },
        doTouch(e) {
            if (e.touches.length === 1 && this.dragging) {
                this.panX = Math.round(e.touches[0].clientX - this.startX);
                this.panY = Math.round(e.touches[0].clientY - this.startY);
            } else if (e.touches.length === 2 && this.initialPinchDistance) {
                if (e.cancelable) e.preventDefault();
                const currentDistance = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                if (currentDistance > 0) {
                    const factor = currentDistance / this.initialPinchDistance;
                    const newScale = Math.min(2.5, Math.max(0.2, parseFloat((this.initialScale * factor).toFixed(2))));
                    const ratio = newScale / this.scale;
                    this.panX = Math.round(this.pinchCenterX - ratio * (this.pinchCenterX - this.panX));
                    this.panY = Math.round(this.pinchCenterY - ratio * (this.pinchCenterY - this.panY));
                    this.scale = newScale;
                }
            }
        },
        stopTouch(e) {
            if (!e || e.touches.length === 0) {
                this.dragging = false;
                this.initialPinchDistance = 0;
            } else if (e.touches.length === 1) {
                this.dragging = true;
                this.startX = e.touches[0].clientX - this.panX;
                this.startY = e.touches[0].clientY - this.panY;
                this.initialPinchDistance = 0;
            }
        },

        zoomIn() { 
            const newScale = Math.min(2.5, parseFloat((this.scale + 0.15).toFixed(2)));
            this.scale = newScale;
        },
        zoomOut() { 
            const newScale = Math.max(0.2, parseFloat((this.scale - 0.15).toFixed(2)));
            this.scale = newScale;
        },
        resetView() { 
            this.centerTree(); 
        },
    }));
</script>
@endscript