<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\FamilyTree;

new class extends Component
{
    public $tree;
    public $isPublic = false;
    public $publicSlug = null;
    public bool $dock = false;

    public function mount($id, $isPublic = false, $publicSlug = null, $dock = false)
    {
        $this->isPublic = $isPublic;
        $this->publicSlug = $publicSlug;
        $this->dock = $dock;

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
        $this->mount($this->tree->id, $this->isPublic, $this->publicSlug, $this->dock);
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

<div class="w-full max-w-[100vw] tree-view" data-component="tree-view" x-data="canvasTree()">
    {{-- Header --}}
    @if($dock)
        {{-- FLOATING PILL TOOLBAR / DOCK (macOS Dock Style at Bottom Center) --}}
        <div class="fixed bottom-3 sm:bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-1 sm:gap-2.5 px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-2xl sm:rounded-full bg-white/90 dark:bg-zinc-900/90 backdrop-blur-2xl border border-zinc-200/90 dark:border-zinc-700/80 shadow-2xl max-w-[calc(100vw-1.5rem)] sm:max-w-none select-none"
             style="bottom: clamp(0.75rem, 2vh, 1.5rem) !important;"
             @mousedown.stop
             @touchstart.stop
             @click.stop>
            {{-- Tree Identity (Hidden on narrow mobile <sm to prevent dock overflow) --}}
            <a href="{{ $isPublic ? url('/') : route('dashboard') }}" class="hidden sm:flex items-center gap-1.5 text-xs font-bold text-zinc-800 dark:text-zinc-200 hover:text-emerald-600 transition-all shrink-0 hover:scale-105 active:scale-95" title="Dashboard">
                <span class="text-base">🌳</span>
                <span class="truncate max-w-[120px] md:max-w-[200px]">{{ $tree->name }}</span>
            </a>

            <div class="hidden sm:block h-4 w-px bg-zinc-200 dark:bg-zinc-700 shrink-0"></div>

            {{-- Mode Switcher Pills --}}
            <div class="flex items-center gap-0.5 sm:gap-1 text-xs shrink-0">
                <button class="px-2 sm:px-2.5 py-1 rounded-full bg-emerald-600 text-white font-bold text-[11px] sm:text-xs shadow-xs cursor-default">
                    Bagan
                </button>
                <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/vertical') : '#') : route('tree.vertical', $tree) }}"
                   wire:navigate
                   class="px-2 sm:px-2.5 py-1 rounded-full text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-semibold text-[11px] sm:text-xs transition-all hover:scale-105 active:scale-95">
                    Fokus
                </a>
                <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/simple') : '#') : route('tree.simple', $tree) }}"
                   wire:navigate
                   class="px-2 sm:px-2.5 py-1 rounded-full text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-semibold text-[11px] sm:text-xs transition-all hover:scale-105 active:scale-95">
                    Simpel
                </a>
                <button wire:click="$dispatch('open-family-reminders')"
                        class="px-2 sm:px-2.5 py-1 rounded-full text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-semibold text-[11px] sm:text-xs transition-all flex items-center gap-1 hover:scale-105 active:scale-95 cursor-pointer"
                        title="Agenda & Pengingat Keluarga">
                    <span>🗓️</span>
                    <span class="hidden sm:inline">Kalender</span>
                </button>
            </div>

            <div class="h-4 w-px bg-zinc-200 dark:bg-zinc-700 shrink-0"></div>

            {{-- Actions --}}
            <div class="flex items-center gap-0.5 sm:gap-1 shrink-0">
                {{-- Share --}}
                @if($tree->slug)
                    <button type="button"
                            x-data="treeShare({{ json_encode(url('/public/tree/' . $tree->slug)) }}, {{ json_encode($tree->name) }})"
                            @click="share()"
                            class="p-1.5 rounded-full hover:bg-emerald-50 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-200 transition-all hover:scale-105 active:scale-95 cursor-pointer flex items-center justify-center"
                            title="Bagikan Silsilah">
                        <span class="material-symbols-outlined text-[18px] sm:text-base" x-show="!shared">share</span>
                        <span class="material-symbols-outlined text-[18px] sm:text-base text-emerald-600" x-show="shared">check</span>
                    </button>
                @endif

                {{-- Export Dropdown (opens upward above bottom dock) --}}
                <flux:dropdown position="top" align="center">
                    <button class="p-1.5 rounded-full hover:bg-purple-50 dark:hover:bg-purple-950/40 text-zinc-700 dark:text-zinc-200 transition-all hover:scale-105 active:scale-95 cursor-pointer flex items-center justify-center"
                            title="Ekspor Bagan">
                        <span class="material-symbols-outlined text-[18px] sm:text-base">download</span>
                    </button>
                    <flux:menu class="!w-64">
                        <flux:menu.item icon="photo"
                                        href="#"
                                        @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'banner']) }}', title: 'Poster Silsilah Pigura (A1/A0)' } }))">
                            Poster Pigura (A1/A0)
                        </flux:menu.item>
                        <flux:menu.item icon="book-open"
                                        href="#"
                                        @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'book']) }}', title: 'Buku Silsilah (PDF)' } }))">
                            Buku Silsilah (PDF)
                        </flux:menu.item>
                        <flux:menu.item icon="photo"
                                        href="#"
                                        @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'png', 'view' => 'horizontal']) }}', title: 'Gambar Silsilah (PNG)' } }))">
                            Export Gambar (PNG)
                        </flux:menu.item>
                        <flux:menu.item icon="document-arrow-down"
                                        href="#"
                                        @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'pdf', 'view' => 'horizontal']) }}', title: 'Bagan Canvas (PDF)' } }))">
                            Export Bagan Canvas (PDF)
                        </flux:menu.item>
                        <flux:menu.item icon="sparkles"
                                        href="#"
                                        @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'prompt']) }}', title: 'Prompt AI (.md)' } }))">
                            Export Prompt AI (.md)
                        </flux:menu.item>
                        <flux:menu.item icon="code-bracket"
                                        href="#"
                                        @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'json']) }}', title: 'Data Silsilah (JSON)' } }))">
                            Export Data (JSON)
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                {{-- Tree Settings & Management Dropdown --}}
                <flux:dropdown position="top" align="center">
                    <button class="p-1.5 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-200 transition-all hover:scale-105 active:scale-95 cursor-pointer flex items-center justify-center"
                            title="{{ $isPublic ? 'Menu & Pilihan' : 'Kelola Pohon & Pengaturan' }}">
                        <span class="material-symbols-outlined text-[18px] sm:text-base">{{ $isPublic ? 'more_vert' : 'settings' }}</span>
                    </button>
                    <flux:menu class="!w-64">
                        @if(!$isPublic)
                            <flux:menu.item icon="adjustments-horizontal" wire:click="$dispatch('open-tree-settings')">
                                Kelola Pohon (Dashboard &amp; Statistik)
                            </flux:menu.item>
                            <flux:menu.item icon="arrow-down-on-square" wire:click="$dispatch('open-import-modal')">
                                Import CSV/GEDCOM
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="user-group" wire:click="$dispatch('open-relationship-calculator')">
                                Cek Hubungan Kekerabatan
                            </flux:menu.item>
                        @else
                            <flux:menu.item icon="pencil-square" wire:click="$dispatch('open-contribute-modal')">
                                Lengkapi / Usulkan Data
                            </flux:menu.item>
                            <flux:menu.item icon="user-group" wire:click="$dispatch('open-relationship-calculator')">
                                Cek Hubungan Kekerabatan
                            </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>

                {{-- Add Member CTA --}}
                @if(!$isPublic)
                    <button wire:click="$dispatch('create-member')"
                            class="px-2.5 sm:px-3 py-1 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] sm:text-xs shadow-xs flex items-center gap-1 shrink-0 cursor-pointer hover:scale-105 active:scale-95 transition-all"
                            title="Tambah Anggota">
                        <span class="material-symbols-outlined text-sm">add</span>
                        <span class="hidden sm:inline">Anggota</span>
                    </button>
                @endif
            </div>
        </div>
    @else
    <div class="border-b border-zinc-200/80 dark:border-zinc-800 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md px-4 lg:px-6 py-2.5 flex items-center justify-between gap-4 sticky top-0 z-30">
        {{-- Left: Breadcrumb + Badges --}}
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white flex items-center justify-center shadow-sm shadow-emerald-600/10 flex-shrink-0">
                <span class="text-base">🌳</span>
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 text-xs truncate">
                    <a href="{{ $isPublic ? url('/') : route('dashboard') }}" class="text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-300">Dashboard</a>
                    <span class="text-zinc-400">/</span>
                    <span class="font-bold text-zinc-900 dark:text-white truncate max-w-[180px] sm:max-w-xs">{{ $tree->name }}</span>
                </div>
                <div class="flex items-center gap-1.5 mt-0.5">
                    @if($tree->is_public)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">Publik</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">Privat</span>
                    @endif

                    @if($tree->is_premium)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 flex items-center gap-1">
                            <span>👑</span>
                            <span>Premium</span>
                        </span>
                    @elseif(!$isPublic)
                        <a href="{{ route('dashboard', ['upgrade_tree' => $tree->id]) }}" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 flex items-center gap-1 transition-colors">
                            <span>🔒</span>
                            <span>Upgrade 5K</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Center: Unified Navigation Tabs (Desktop only) --}}
        <div class="hidden lg:flex items-center gap-1 bg-zinc-100/90 dark:bg-zinc-800/90 p-1 rounded-xl text-xs font-semibold">
            <span class="bg-white dark:bg-zinc-700 text-emerald-700 dark:text-emerald-300 shadow-sm font-bold px-3 py-1.5 rounded-lg flex items-center gap-1.5 cursor-default">
                <span>🌳</span>
                <span>Bagan Canvas</span>
            </span>
            <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/vertical') : '#') : route('tree.vertical', $tree) }}"
               wire:navigate
               class="px-3 py-1.5 rounded-lg text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center gap-1.5 transition-colors">
                <span>🧭</span>
                <span>Fokus Explorer</span>
            </a>
            <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/simple') : '#') : route('tree.simple', $tree) }}"
               wire:navigate
               class="px-3 py-1.5 rounded-lg text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center gap-1.5 transition-colors">
                <span>📄</span>
                <span>Buku Simpel</span>
            </a>
            <a href="{{ $isPublic ? route('tree.public.calendar', $tree->slug) : route('tree.calendar', $tree->slug ?: $tree->id) }}"
               class="px-3 py-1.5 rounded-lg text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center gap-1.5 transition-colors">
                <span>🗓️</span>
                <span>Kalender Acara</span>
            </a>
        </div>

        {{-- Right: Actions (Desktop) --}}
        <div class="hidden md:flex items-center gap-2">
            @if($tree->slug)
                <flux:button size="sm" icon="share"
                    x-data="treeShare({{ json_encode(url('/public/tree/' . $tree->slug)) }}, {{ json_encode($tree->name) }})"
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
                    @if($tree->is_premium)
                        <flux:menu.item icon="book-open"
                                        href="#"
                                        @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'book']) }}', title: 'Buku Silsilah (PDF)' } }))">
                            <div class="flex items-center justify-between w-full gap-2">
                                <span>Buku Silsilah (PDF)</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">👑 Premium</span>
                            </div>
                        </flux:menu.item>
                    @else
                        <flux:menu.item icon="book-open"
                                        href="{{ route('dashboard', ['upgrade_tree' => $tree->id]) }}">
                            <div class="flex items-center justify-between w-full gap-2">
                                <span class="flex items-center gap-1 text-zinc-700 dark:text-zinc-300">
                                    <span>Buku Silsilah (PDF)</span>
                                    <span class="text-xs text-amber-500 font-bold">🔒</span>
                                </span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">Upgrade 5K</span>
                            </div>
                        </flux:menu.item>
                    @endif
                    <flux:menu.item icon="photo"
                                    href="#"
                                    @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'banner']) }}', title: 'Poster Silsilah Pigura (A1/A0)' } }))">
                        Poster Silsilah Pigura (A1/A0)
                    </flux:menu.item>
                    <flux:menu.item icon="photo"
                                    href="#"
                                    @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'png', 'view' => 'horizontal']) }}', title: 'Gambar Silsilah (PNG)' } }))">
                        Export Gambar (PNG)
                    </flux:menu.item>
                    <flux:menu.item icon="document-arrow-down"
                                    href="#"
                                    @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'pdf', 'view' => 'horizontal']) }}', title: 'Bagan Canvas (PDF)' } }))">
                        Export Bagan Canvas (PDF)
                    </flux:menu.item>
                    <flux:menu.item icon="sparkles"
                                    href="#"
                                    @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'prompt']) }}', title: 'Prompt AI (.md)' } }))">
                        Export Prompt AI (.md)
                    </flux:menu.item>
                    <flux:menu.item icon="code-bracket"
                                    href="#"
                                    @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'json']) }}', title: 'Data Silsilah (JSON)' } }))">
                        Export Data (JSON)
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            @if(!$isPublic)
                <flux:dropdown>
                    <flux:button size="sm" icon="cog-6-tooth" class="!bg-zinc-100 !text-zinc-700 hover:!bg-zinc-200 dark:!bg-zinc-800 dark:!text-zinc-300">
                        Kelola
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item icon="user-group" wire:click="$dispatch('open-relationship-calculator')">
                            Cek Hubungan Kekerabatan
                        </flux:menu.item>
                        <flux:menu.item icon="calendar" wire:click="$dispatch('open-family-reminders')">
                            Agenda &amp; Pengingat Kalender
                        </flux:menu.item>
                        <flux:menu.item icon="calendar-days" href="{{ route('tree.calendar', $tree->slug ?: $tree->id) }}" target="_blank">
                            Buka Halaman Kalender Penuh
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="adjustments-horizontal" wire:click="$dispatch('open-tree-settings')">
                            Kelola Pohon (Dashboard &amp; Statistik)
                        </flux:menu.item>
                        <flux:menu.item icon="arrow-down-on-square" wire:click="$dispatch('open-import-modal')">
                            Import CSV/GEDCOM
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="window" href="{{ Route::has('tree.dock') ? route('tree.dock', $tree) : url('/tree/' . ($tree->slug ?: $tree->id) . '/dock') }}">
                            Coba Tampilan Dock (Opsi 2)
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                <flux:button size="sm" icon="plus" wire:click="$dispatch('create-member')" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 font-bold !rounded-xl shadow-md shadow-emerald-600/20">
                    Anggota Baru
                </flux:button>
            @else
                <flux:dropdown>
                    <flux:button size="sm" icon="sparkles" class="!bg-zinc-100 !text-zinc-700 hover:!bg-zinc-200 dark:!bg-zinc-800 dark:!text-zinc-300">
                        Fitur Keluarga
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item icon="pencil-square" wire:click="$dispatch('open-contribute-modal')">
                            Lengkapi / Usulkan Data
                        </flux:menu.item>
                        <flux:menu.item icon="user-group" wire:click="$dispatch('open-relationship-calculator')">
                            Cek Hubungan Kekerabatan
                        </flux:menu.item>
                        <flux:menu.item icon="calendar" wire:click="$dispatch('open-family-reminders')">
                            Agenda &amp; Pengingat Kalender
                        </flux:menu.item>
                        <flux:menu.item icon="calendar-days" href="{{ route('tree.public.calendar', $tree->slug) }}" target="_blank">
                            Buka Halaman Kalender Penuh
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="window" href="{{ Route::has('tree.public.dock') ? route('tree.public.dock', $tree->slug) : url('/public/tree/' . $tree->slug . '/dock') }}">
                            Coba Tampilan Dock (Opsi 2)
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endif
        </div>

        {{-- Mobile Action Bar --}}
        <div class="flex md:hidden items-center gap-1.5">
            @if($tree->slug)
                <button type="button"
                        x-data="treeShare({{ json_encode(url('/public/tree/' . $tree->slug)) }}, {{ json_encode($tree->name) }})"
                        @click="share()"
                        class="p-2 rounded-xl bg-zinc-100 hover:bg-emerald-50 active:scale-95 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 transition-all flex items-center justify-center shrink-0"
                        title="Bagikan Silsilah">
                    <span class="material-symbols-outlined text-lg" x-show="!shared">share</span>
                    <span class="material-symbols-outlined text-lg text-emerald-600" x-show="shared">check</span>
                </button>
            @endif

            <flux:dropdown>
                <flux:button size="sm" icon="arrow-down-tray" class="!p-2 !rounded-xl !bg-zinc-100 dark:!bg-zinc-800 text-zinc-700 dark:text-zinc-200" title="Ekspor"></flux:button>
                <flux:menu>
                    @if($tree->is_premium)
                        <flux:menu.item icon="book-open"
                                        href="#"
                                        @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'book']) }}', title: 'Buku Silsilah (PDF)' } }))">
                            <div class="flex items-center justify-between w-full gap-2">
                                <span>Buku Silsilah (PDF)</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">👑 Premium</span>
                            </div>
                        </flux:menu.item>
                    @else
                        <flux:menu.item icon="book-open"
                                        href="{{ route('dashboard', ['upgrade_tree' => $tree->id]) }}">
                            <div class="flex items-center justify-between w-full gap-2">
                                <span class="flex items-center gap-1 text-zinc-700 dark:text-zinc-300">
                                    <span>Buku Silsilah (PDF)</span>
                                    <span class="text-xs text-amber-500 font-bold">🔒</span>
                                </span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">Upgrade 5K</span>
                            </div>
                        </flux:menu.item>
                    @endif
                    <flux:menu.item icon="photo"
                                    href="#"
                                    @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'banner']) }}', title: 'Poster Silsilah Pigura (A1/A0)' } }))">
                        Poster Silsilah Pigura (A1/A0)
                    </flux:menu.item>
                    <flux:menu.item icon="photo"
                                    href="#"
                                    @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'png', 'view' => 'horizontal']) }}', title: 'Gambar Silsilah (PNG)' } }))">
                        Export Gambar (PNG)
                    </flux:menu.item>
                    <flux:menu.item icon="document-arrow-down"
                                    href="#"
                                    @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'pdf', 'view' => 'horizontal']) }}', title: 'Bagan Canvas (PDF)' } }))">
                        Export Bagan Canvas (PDF)
                    </flux:menu.item>
                    <flux:menu.item icon="sparkles"
                                    href="#"
                                    @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'prompt']) }}', title: 'Prompt AI (.md)' } }))">
                        Export Prompt AI (.md)
                    </flux:menu.item>
                    <flux:menu.item icon="code-bracket"
                                    href="#"
                                    @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'json']) }}', title: 'Data Silsilah (JSON)' } }))">
                        Export Data (JSON)
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            <button type="button"
                    wire:click="$dispatch('open-family-reminders')"
                    class="p-2 rounded-xl bg-zinc-100 hover:bg-emerald-50 active:scale-95 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 transition-all flex items-center justify-center shrink-0"
                    title="Agenda Kalender">
                <span class="material-symbols-outlined text-lg">calendar_month</span>
            </button>

            <flux:dropdown>
                <flux:button size="sm" icon="ellipsis-vertical" class="!p-2 !rounded-xl !bg-zinc-100 dark:!bg-zinc-800 text-zinc-700 dark:text-zinc-200" title="Pilihan Lainnya"></flux:button>
                <flux:menu>
                    <flux:menu.item icon="user-group" wire:click="$dispatch('open-relationship-calculator')">
                        Cek Hubungan Kekerabatan
                    </flux:menu.item>
                    <flux:menu.item icon="book-open" href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/simple') : '#') : route('tree.simple', $tree) }}">
                        Buka Buku Simpel
                    </flux:menu.item>
                    <flux:menu.item icon="calendar-days" href="{{ $isPublic ? route('tree.public.calendar', $tree->slug) : route('tree.calendar', $tree->slug ?: $tree->id) }}">
                        Halaman Kalender Penuh
                    </flux:menu.item>
                    @if(!$isPublic)
                        <flux:menu.item icon="adjustments-horizontal" wire:click="$dispatch('open-tree-settings')">
                            Kelola Pohon (Dashboard &amp; Statistik)
                        </flux:menu.item>
                        <flux:menu.item icon="arrow-down-on-square" wire:click="$dispatch('open-import-modal')">
                            Import CSV/GEDCOM
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="window" href="{{ Route::has('tree.dock') ? route('tree.dock', $tree) : url('/tree/' . ($tree->slug ?: $tree->id) . '/dock') }}">
                            Coba Tampilan Dock (Opsi 2)
                        </flux:menu.item>
                    @else
                        <flux:menu.item icon="pencil-square" wire:click="$dispatch('open-contribute-modal')">
                            Lengkapi / Usulkan Data
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="window" href="{{ Route::has('tree.public.dock') ? route('tree.public.dock', $tree->slug) : url('/public/tree/' . $tree->slug . '/dock') }}">
                            Coba Tampilan Dock (Opsi 2)
                        </flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
    @endif

    @if(!$dock)
    {{-- Segmented View Switcher (Mobile Only) --}}
    <div class="md:hidden px-4 py-2 border-b border-zinc-200/60 dark:border-zinc-800/60 bg-white/50 dark:bg-zinc-900/50">
        <div class="flex items-center bg-zinc-100 dark:bg-zinc-800 p-1 rounded-xl shadow-inner gap-1">
            <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/vertical') : '#') : route('tree.vertical', $tree) }}"
               wire:navigate
               class="flex-1 py-1.5 px-2 rounded-lg text-xs font-semibold text-center text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center justify-center gap-1 transition-all">
                <span>🧭</span>
                <span>Fokus</span>
            </a>
            <button class="flex-1 py-1.5 px-2 rounded-lg text-xs font-bold bg-white dark:bg-zinc-700 text-emerald-700 dark:text-emerald-300 shadow-sm flex items-center justify-center gap-1 transition-all">
                <span>🌳</span>
                <span>Bagan</span>
            </button>
            <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/simple') : '#') : route('tree.simple', $tree) }}"
               wire:navigate
               class="flex-1 py-1.5 px-2 rounded-lg text-xs font-semibold text-center text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center justify-center gap-1 transition-all">
                <span>📄</span>
                <span>Simpel</span>
            </a>
        </div>
    </div>
    @endif

    {{-- Tree Canvas --}}
    <div class="w-full overflow-hidden relative">
        <div class="pt-sm pt-full-bleed touch-none select-none"
             @if($dock) style="height: {{ $isPublic ? '100vh' : 'calc(100vh - 65px)' }} !important; min-height: {{ $isPublic ? '100vh' : 'calc(100vh - 65px)' }} !important;" @endif
             x-ref="container"
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
                            <x-tree-node :member="$member" :all-members="$allMembers" :is-public="$isPublic" />
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Material 3 Floating Zoom Controls --}}
            <div class="fixed md:absolute {{ $dock ? 'bottom-16 sm:bottom-6 right-3 sm:right-6' : 'bottom-20 md:bottom-5 right-4' }} z-30 flex flex-col items-center bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md p-1 sm:p-1.5 rounded-2xl shadow-xl border border-zinc-200/80 dark:border-zinc-800 gap-0.5 sm:gap-1 select-none"
                 @if($dock) style="bottom: clamp(4.25rem, 6vh, 5rem) !important;" @endif
                 @touchstart.stop
                 @click.stop>
                <button @click="zoomIn()" title="Zoom In" class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-zinc-100 hover:bg-emerald-50 dark:bg-zinc-800 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-200 hover:text-emerald-600 flex items-center justify-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="sm:w-[18px] sm:h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>

                <div class="text-[9px] sm:text-[10px] font-extrabold text-zinc-500 dark:text-zinc-400 py-0.5" x-text="Math.round(scale * 100) + '%'"></div>

                <button @click="resetView()" title="Fit to Screen / Pusatkan" class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-zinc-100 hover:bg-emerald-50 dark:bg-zinc-800 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-200 hover:text-emerald-600 flex items-center justify-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" class="sm:w-4 sm:h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/></svg>
                </button>

                <button @click="zoomOut()" title="Zoom Out" class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-zinc-100 hover:bg-emerald-50 dark:bg-zinc-800 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-200 hover:text-emerald-600 flex items-center justify-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="sm:w-[18px] sm:h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
            </div>
        </div>
    </div>

    @if(!$dock)
    {{-- STICKY BOTTOM NAVIGATION BAR (Mobile Only) --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-around px-2 z-40 shadow-lg">
        <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/vertical') : '#') : route('tree.vertical', $tree) }}"
           wire:navigate
           class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold text-zinc-500 dark:text-zinc-400 hover:text-emerald-600">
            <span class="text-lg">🧭</span>
            <span class="text-[10px]">Fokus</span>
        </a>

        <div class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400">
            <span class="text-lg">🌳</span>
            <span class="text-[10px]">Bagan</span>
        </div>

        <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/vertical?tab=directory') : '#') : route('tree.vertical', ['tree' => $tree, 'tab' => 'directory']) }}"
           wire:navigate
           class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold text-zinc-500 dark:text-zinc-400 hover:text-emerald-600">
            <span class="text-lg">👥</span>
            <span class="text-[10px]">Direktori</span>
        </a>

        <button type="button"
                wire:click="$dispatch('open-family-reminders')"
                class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold text-zinc-500 dark:text-zinc-400 hover:text-emerald-600">
            <span class="text-lg">🗓️</span>
            <span class="text-[10px]">Kalender</span>
        </button>

        @if(!$isPublic)
            <button type="button"
                    wire:click="$dispatch('create-member')"
                    class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <span class="text-lg font-bold">➕</span>
                <span class="text-[10px]">Tambah</span>
            </button>
        @else
            <button type="button"
                    wire:click="$dispatch('open-contribute-modal')"
                    class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <span class="text-lg font-bold">✏️</span>
                <span class="text-[10px]">Usul</span>
            </button>
        @endif
    </nav>
    @endif

    {{-- Member Manager & Modals --}}
    <livewire:member-manager :tree-id="$tree->id" :is-public="$isPublic" :tree-slug="$publicSlug ?? $tree->slug" />
    <livewire:relationship-calculator :tree-id="$tree->id" />
    <livewire:family-reminders :tree-id="$tree->id" />
    <livewire:tree-contribute-modal :tree-id="$tree->id" />
    @if(!$isPublic)
        <livewire:tree-settings :tree-id="$tree->id" />
        <livewire:import-members :tree-id="$tree->id" />
        @if(request()->boolean('manage') || request()->boolean('settings'))
            <div x-init="$wire.dispatch('open-tree-settings')"></div>
        @endif
    @endif
    {{-- Floating Loading Indicator --}}
    <div wire:loading.flex class="fixed bottom-20 md:bottom-6 right-6 bg-zinc-900/90 text-white px-4 py-2.5 rounded-full shadow-2xl items-center gap-3 text-sm font-medium z-50 border border-zinc-700/50 backdrop-blur-md">
        <svg class="animate-spin h-4 w-4 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Memuat data...</span>
    </div>

    {{-- Export Loading Progress Modal & Minimizable Pill --}}
    <x-export-progress-modal />
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