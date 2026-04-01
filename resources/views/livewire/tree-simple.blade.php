<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\FamilyTree;

new class extends Component
{
    public $tree;

    public function mount($id): void
    {
        $this->tree = FamilyTree::with([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ])->findOrFail($id);

        if ($this->tree->users()->where('user_id', auth()->id())->doesntExist() && ! $this->tree->is_public) {
            abort(403, 'Unauthorized access to this family tree.');
        }
    }

    #[On('refresh-tree')]
    public function refreshTree(): void
    {
        $this->mount($this->tree->id);
    }

    public function with(): array
    {
        $allMembers = $this->tree->members;

        $wifeIdsInMarriages = collect();
        foreach ($allMembers as $m) {
            if ($m->gender === 'male') {
                foreach ($m->marriagesAsHusband as $marriage) {
                    $wifeIdsInMarriages->push($marriage->wife_id);
                }
            }
        }

        $parentless = $allMembers->whereNull('father_id')->whereNull('mother_id');
        $rootMembers = $parentless->whereNotIn('id', $wifeIdsInMarriages->unique());

        return [
            'rootMembers' => $rootMembers,
            'allMembers' => $allMembers,
        ];
    }
};
?>

<div class="w-full max-w-[100vw]">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between px-4 lg:px-8 py-4 gap-3">
        <div class="min-w-0">
            <h2 class="text-2xl font-bold dark:text-white truncate">{{ $tree->name }} — Simple View</h2>
            @if($tree->description)
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $tree->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <flux:button size="sm" icon="arrow-left" href="{{ route('tree.show', $tree->id) }}" wire:navigate class="!bg-zinc-100 !text-zinc-700 hover:!bg-zinc-200 dark:!bg-zinc-800 dark:!text-zinc-300 dark:hover:!bg-zinc-700">Horizontal</flux:button>
            <flux:button size="sm" icon="list-bullet" href="{{ route('tree.vertical', $tree->id) }}" wire:navigate class="!bg-amber-50 !text-amber-700 hover:!bg-amber-100 dark:!bg-amber-900/30 dark:!text-amber-400 dark:hover:!bg-amber-900/50">Vertikal</flux:button>
            @if($tree->slug)
                <flux:button size="sm" icon="share" x-data="{ shared: false }"
                    x-on:click="navigator.clipboard.writeText('{{ url('/public/tree/' . $tree->slug) }}'); shared = true; setTimeout(() => shared = false, 2000)"
                    class="!bg-emerald-50 !text-emerald-700 hover:!bg-emerald-100 dark:!bg-emerald-900/30 dark:!text-emerald-400 dark:hover:!bg-emerald-900/50">
                    <span x-show="!shared">Share</span>
                    <span x-show="shared" class="text-emerald-600 dark:text-emerald-400">Tersalin!</span>
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Simple Tree Canvas --}}
    <div class="px-4 lg:px-8 pb-8">
        <div class="pt-sm" x-data="canvasTree()" x-ref="container"
             @mousedown="startDrag($event)"
             @mousemove="doDrag($event)"
             @mouseup="stopDrag()"
             @mouseleave="stopDrag()"
             @wheel.prevent="doZoom($event)"
             @touchstart.passive="startTouch($event)"
             @touchmove.prevent="doTouch($event)"
             @touchend="stopTouch()">

            <div class="tree-inner" x-ref="inner" :style="transformStyle">
                <div class="tree simple-tree" id="simpleTree">
                    <ul>
                        @foreach($rootMembers as $member)
                            <x-simple-tree-node :member="$member" :all-members="$allMembers" />
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Zoom Controls --}}
            <div class="pt-zoom-controls">
                <button @click="zoomOut()" title="Zoom Out">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <button @click="resetView()" title="Reset">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                </button>
                <button @click="zoomIn()" title="Zoom In">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
            </div>
        </div>
    </div>

    <livewire:member-manager :tree-id="$tree->id" />
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

        get transformStyle() {
            return `transform: translate(${this.panX}px, ${this.panY}px) scale(${this.scale})`;
        },

        init() {
            this.$nextTick(() => this.centerTree());
        },

        centerTree() {
            const container = this.$refs.container;
            const inner = this.$refs.inner;
            if (!container || !inner) return;
            this.panX = Math.max(0, (container.clientWidth - inner.scrollWidth) / 2);
            this.panY = 20;
            this.scale = 1;
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
            const delta = e.deltaY > 0 ? -0.08 : 0.08;
            const newScale = Math.min(3, Math.max(0.2, this.scale + delta));
            const rect = this.$refs.container.getBoundingClientRect();
            const cx = e.clientX - rect.left;
            const cy = e.clientY - rect.top;
            const ratio = newScale / this.scale;
            this.panX = cx - ratio * (cx - this.panX);
            this.panY = cy - ratio * (cy - this.panY);
            this.scale = newScale;
        },

        startTouch(e) {
            if (e.touches.length === 1) {
                this.dragging = true;
                this.startX = e.touches[0].clientX - this.panX;
                this.startY = e.touches[0].clientY - this.panY;
            }
        },
        doTouch(e) {
            if (e.touches.length === 1 && this.dragging) {
                this.panX = e.touches[0].clientX - this.startX;
                this.panY = e.touches[0].clientY - this.startY;
            }
        },
        stopTouch() { this.dragging = false; },

        zoomIn() { this.scale = Math.min(3, this.scale + 0.15); },
        zoomOut() { this.scale = Math.max(0.2, this.scale - 0.15); },
        resetView() { this.centerTree(); }
    }));
</script>
@endscript
