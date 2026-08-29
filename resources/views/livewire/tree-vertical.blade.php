<?php

use App\Models\FamilyTree;
use App\Models\Member;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    public $tree;

    #[Url(as: 'member')]
    public ?int $focusMemberId = null;

    #[Url(as: 'tab')]
    public string $tab = 'explorer'; // explorer, directory

    public string $search = '';

    public string $filter = 'all'; // all, living, deceased, male, female

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

        if (! $this->isPublic) {
            if ($this->tree->users()->where('user_id', auth()->id())->doesntExist() && ! $this->tree->is_public) {
                abort(403, 'Unauthorized access.');
            }
        }

        if (request()->query('member')) {
            $this->focusMemberId = (int) request()->query('member');
        }
    }

    #[On('refresh-tree')]
    public function refreshTree()
    {
        $this->mount($this->tree->id);
    }

    public function focusOn(int $memberId)
    {
        $this->focusMemberId = $memberId;
        $this->tab = 'explorer';
    }

    public function setTab(string $tabName)
    {
        $this->tab = $tabName;
    }

    public function setFilter(string $filterType)
    {
        $this->filter = $filterType;
    }

    public function goToRoot()
    {
        $this->focusMemberId = null;
    }

    public function with()
    {
        $this->tree->loadMissing([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ]);

        $allMembers = $this->tree->members;

        // Helper: get avatar URL
        $getAvatar = function ($m) {
            if (! $m) {
                return asset('images/no_profile_pic.jpg');
            }
            if ($m->photo) {
                return str_starts_with($m->photo, 'http') ? $m->photo : asset('storage/'.$m->photo);
            }

            return $m->avatar_id
                ? asset('images/avatar/'.$m->avatar_id)
                : asset('images/no_profile_pic.jpg');
        };

        // Determine default focus if not set
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

        if ($this->focusMemberId) {
            $focusMember = $allMembers->firstWhere('id', $this->focusMemberId);
        } else {
            $focusMember = $rootMembers->first() ?? $allMembers->first();
        }

        // Get Parents of focus member
        $father = $focusMember && $focusMember->father_id ? $allMembers->firstWhere('id', $focusMember->father_id) : null;
        $mother = $focusMember && $focusMember->mother_id ? $allMembers->firstWhere('id', $focusMember->mother_id) : null;

        // Get spouses for a member
        $getSpouses = function ($member) use ($allMembers) {
            if (! $member) {
                return collect();
            }
            if ($member->gender === 'male' && $member->relationLoaded('marriagesAsHusband')) {
                return $member->marriagesAsHusband->map(function ($m) use ($allMembers) {
                    $wife = $allMembers->firstWhere('id', $m->wife_id);
                    if ($wife) {
                        $wife = clone $wife;
                        $wife->is_current_marriage = $m->is_current;
                        $wife->marriage_date = $m->marriage_date;
                    }

                    return $wife;
                })->filter();
            } elseif ($member->gender === 'female' && $member->relationLoaded('marriagesAsWife')) {
                return $member->marriagesAsWife->map(function ($m) use ($allMembers) {
                    $husband = $allMembers->firstWhere('id', $m->husband_id);
                    if ($husband) {
                        $husband = clone $husband;
                        $husband->is_current_marriage = $m->is_current;
                        $husband->marriage_date = $m->marriage_date;
                    }

                    return $husband;
                })->filter();
            }

            return collect();
        };

        // Get children sorted
        $getChildren = function ($member) use ($allMembers) {
            if (! $member) {
                return collect();
            }
            $childFilter = $member->gender === 'male' ? 'father_id' : 'mother_id';

            return $allMembers->where($childFilter, $member->id)
                ->sortBy(function ($child) {
                    $bd = $child->birth_date ? strtotime($child->birth_date) : PHP_INT_MAX;

                    return [$bd, $child->order ?? 999, $child->id];
                });
        };

        // Count grandchildren for a given child
        $countDescendants = function ($member) use ($allMembers) {
            if (! $member) {
                return 0;
            }
            $count = 0;
            $count += $allMembers->where('father_id', $member->id)->count();
            $count += $allMembers->where('mother_id', $member->id)->count();

            return $count;
        };

        // Build breadcrumbs for focus member
        $breadcrumbs = collect();
        if ($focusMember) {
            $current = $focusMember;
            $visited = collect();
            while ($current && ! $visited->contains($current->id)) {
                $visited->push($current->id);
                $breadcrumbs->prepend($current);
                $parentId = $current->father_id ?? $current->mother_id;
                $current = $parentId ? $allMembers->firstWhere('id', $parentId) : null;
            }
        }

        // Directory filtered list
        $directoryMembers = $allMembers;
        if (! empty($this->search)) {
            $q = mb_strtolower(trim($this->search));
            $directoryMembers = $directoryMembers->filter(function ($m) use ($q) {
                $fullName = mb_strtolower("{$m->first_name} {$m->last_name}");
                $profession = mb_strtolower($m->profession ?? '');
                $place = mb_strtolower($m->birth_place ?? '');

                return str_contains($fullName, $q) || str_contains($profession, $q) || str_contains($place, $q);
            });
        }

        if ($this->filter === 'living') {
            $directoryMembers = $directoryMembers->where('is_living', true);
        } elseif ($this->filter === 'deceased') {
            $directoryMembers = $directoryMembers->where('is_living', false);
        } elseif ($this->filter === 'male') {
            $directoryMembers = $directoryMembers->where('gender', 'male');
        } elseif ($this->filter === 'female') {
            $directoryMembers = $directoryMembers->where('gender', 'female');
        }

        return [
            'tree' => $this->tree,
            'allMembers' => $allMembers,
            'rootMembers' => $rootMembers,
            'focusMember' => $focusMember,
            'father' => $father,
            'mother' => $mother,
            'getAvatar' => $getAvatar,
            'getSpouses' => $getSpouses,
            'getChildren' => $getChildren,
            'countDescendants' => $countDescendants,
            'breadcrumbs' => $breadcrumbs,
            'directoryMembers' => $directoryMembers,
        ];
    }
};
?>

<div class="w-full max-w-4xl mx-auto pb-20 md:pb-10">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between px-4 lg:px-6 py-3.5 gap-3 border-b border-zinc-200/60 dark:border-zinc-800/80 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md sticky top-0 z-30">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-700 text-white flex items-center justify-center shadow-md shadow-amber-600/10 flex-shrink-0">
                <span class="text-xl">🧭</span>
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
                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $tree->description ?: 'Mode fokus eksplorasi silsilah keluarga' }}</p>
            </div>
        </div>

        {{-- Desktop Action Buttons --}}
        <div class="hidden md:flex items-center gap-2.5 flex-wrap">
            {{-- 1. View Mode Switcher (Segmented Pill) --}}
            <div class="flex items-center bg-zinc-100/90 dark:bg-zinc-800/90 p-1 rounded-full border border-zinc-200/60 dark:border-zinc-700/60 text-xs font-semibold">
                <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug) : '#') : route('tree.show', $tree) }}"
                   wire:navigate
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-full transition-all text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200">
                    <span>🌳</span>
                    <span>Bagan</span>
                </a>
                <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/vertical') : '#') : route('tree.vertical', $tree) }}"
                   wire:navigate
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-full transition-all bg-white dark:bg-zinc-700 text-amber-700 dark:text-amber-300 shadow-sm font-bold">
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
                    <flux:menu.item icon="photo" href="{{ route('tree.export', ['tree' => $tree, 'format' => 'png', 'view' => 'vertical']) }}">
                        Export Gambar (PNG)
                    </flux:menu.item>
                    <flux:menu.item icon="document-arrow-down" href="{{ route('tree.export', ['tree' => $tree, 'format' => 'pdf', 'view' => 'vertical']) }}">
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
                    </flux:menu>
                </flux:dropdown>

                {{-- 4. Primary Action Button --}}
                <flux:button size="sm" icon="plus" wire:click="$dispatch('create-member')" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 font-bold !rounded-full shadow-md shadow-emerald-600/20">
                    Anggota Baru
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Segmented Mode Switcher (Mobile & Desktop) --}}
    <div class="px-4 lg:px-6 mb-4">
        <div class="flex items-center bg-zinc-100 dark:bg-zinc-800 p-1 rounded-xl shadow-inner gap-1">
            <button wire:click="setTab('explorer')"
                    class="flex-1 py-2 px-3 rounded-lg text-xs sm:text-sm font-semibold flex items-center justify-center gap-1.5 transition-all duration-200 {{ $tab === 'explorer' ? 'bg-emerald-600 text-white shadow-md' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}">
                <span>🧭</span>
                <span>Fokus Explorer</span>
            </button>
            <button wire:click="setTab('directory')"
                    class="flex-1 py-2 px-3 rounded-lg text-xs sm:text-sm font-semibold flex items-center justify-center gap-1.5 transition-all duration-200 {{ $tab === 'directory' ? 'bg-emerald-600 text-white shadow-md' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}">
                <span>👥</span>
                <span>Direktori ({{ $allMembers->count() }})</span>
            </button>
            <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug) : '#') : route('tree.show', $tree) }}"
               wire:navigate
               class="flex-1 py-2 px-3 rounded-lg text-xs sm:text-sm font-semibold flex items-center justify-center gap-1.5 transition-all duration-200 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100">
                <span>🌳</span>
                <span>Bagan Canvas</span>
            </a>
        </div>
    </div>

    {{-- TAB 1: FOCUS EXPLORER MODE --}}
    @if($tab === 'explorer')
        <div class="px-4 lg:px-6 space-y-4">
            
            {{-- Breadcrumbs / Ancestor Trail --}}
            @if($breadcrumbs->count() > 1)
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs text-zinc-500 dark:text-zinc-400 no-scrollbar">
                    <button wire:click="goToRoot" class="inline-flex items-center gap-1 px-2.5 py-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-full font-medium hover:border-emerald-500 transition-colors shrink-0">
                        🏠 Root
                    </button>
                    @foreach($breadcrumbs as $crumb)
                        <span class="text-zinc-300 dark:text-zinc-600">›</span>
                        @if($loop->last)
                            <span class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 rounded-full font-bold shrink-0">
                                {{ $crumb->first_name }}
                            </span>
                        @else
                            <button wire:click="focusOn({{ $crumb->id }})" class="px-2.5 py-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-full font-medium hover:border-emerald-500 transition-colors shrink-0">
                                {{ $crumb->first_name }}
                            </button>
                        @endif
                    @endforeach
                </div>
            @endif

            @if($focusMember)
                @php
                    $focusSpouses = $getSpouses($focusMember);
                    $focusChildren = $getChildren($focusMember);
                @endphp

                {{-- 1. PARENTS SECTION (LELUHUR) --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 px-1">
                        <span>⬆️ Orang Tua (Leluhur)</span>
                        @if(!$isPublic)
                            <button wire:click="$dispatch('create-member', { targetId: {{ $focusMember->id }}, relType: 'parent_of' })"
                                    class="text-emerald-600 dark:text-emerald-400 font-semibold normal-case text-xs hover:underline inline-flex items-center gap-1">
                                <span>➕ Tambah Orang Tua</span>
                            </button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Father Card --}}
                        @if($father)
                            <div class="bg-white dark:bg-zinc-900 border-l-4 border-l-teal-500 border border-zinc-200 dark:border-zinc-800 rounded-xl p-3.5 shadow-xs flex items-center justify-between gap-3 hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-3 min-w-0 cursor-pointer flex-1" wire:click="focusOn({{ $father->id }})">
                                    <div class="w-11 h-11 rounded-full overflow-hidden border-2 border-teal-400 shrink-0 bg-teal-50">
                                        <img src="{{ $getAvatar($father) }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/no_profile_pic.jpg') }}'" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold text-teal-600 dark:text-teal-400">Ayah</div>
                                        <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ $father->first_name }} {{ $father->last_name }}</div>
                                        @if(!$father->is_living)
                                            <span class="text-[10px] bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400 px-1.5 py-0.2 rounded font-medium">Wafat</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button wire:click="$dispatch('show-member', { id: {{ $father->id }} })" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg" title="Lihat Profil">
                                        <flux:icon name="eye" class="size-4" />
                                    </button>
                                    <button wire:click="focusOn({{ $father->id }})" class="p-1.5 bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400 rounded-lg font-bold hover:bg-teal-100" title="Fokus ke Ayah">
                                        ➔
                                    </button>
                                </div>
                            </div>
                        @else
                            <div wire:click="$dispatch('create-member', { targetId: {{ $focusMember->id }}, relType: 'parent_of' })"
                                 class="bg-zinc-50 dark:bg-zinc-900/50 border-2 border-dashed border-zinc-300 dark:border-zinc-700 rounded-xl p-3.5 text-center cursor-pointer hover:border-teal-500 dark:hover:border-teal-400 hover:bg-teal-50/50 transition-colors flex items-center justify-center gap-2 text-zinc-500 dark:text-zinc-400">
                                <span class="text-base">👨</span>
                                <span class="text-xs font-semibold">+ Tambah Ayah</span>
                            </div>
                        @endif

                        {{-- Mother Card --}}
                        @if($mother)
                            <div class="bg-white dark:bg-zinc-900 border-l-4 border-l-pink-500 border border-zinc-200 dark:border-zinc-800 rounded-xl p-3.5 shadow-xs flex items-center justify-between gap-3 hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-3 min-w-0 cursor-pointer flex-1" wire:click="focusOn({{ $mother->id }})">
                                    <div class="w-11 h-11 rounded-full overflow-hidden border-2 border-pink-400 shrink-0 bg-pink-50">
                                        <img src="{{ $getAvatar($mother) }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/no_profile_pic.jpg') }}'" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold text-pink-600 dark:text-pink-400">Ibu</div>
                                        <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ $mother->first_name }} {{ $mother->last_name }}</div>
                                        @if(!$mother->is_living)
                                            <span class="text-[10px] bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400 px-1.5 py-0.2 rounded font-medium">Wafat</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button wire:click="$dispatch('show-member', { id: {{ $mother->id }} })" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg" title="Lihat Profil">
                                        <flux:icon name="eye" class="size-4" />
                                    </button>
                                    <button wire:click="focusOn({{ $mother->id }})" class="p-1.5 bg-pink-50 dark:bg-pink-950/40 text-pink-600 dark:text-pink-400 rounded-lg font-bold hover:bg-pink-100" title="Fokus ke Ibu">
                                        ➔
                                    </button>
                                </div>
                            </div>
                        @else
                            <div wire:click="$dispatch('create-member', { targetId: {{ $focusMember->id }}, relType: 'parent_of' })"
                                 class="bg-zinc-50 dark:bg-zinc-900/50 border-2 border-dashed border-zinc-300 dark:border-zinc-700 rounded-xl p-3.5 text-center cursor-pointer hover:border-pink-500 dark:hover:border-pink-400 hover:bg-pink-50/50 transition-colors flex items-center justify-center gap-2 text-zinc-500 dark:text-zinc-400">
                                <span class="text-base">👩</span>
                                <span class="text-xs font-semibold">+ Tambah Ibu</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Connector Line --}}
                <div class="flex justify-center items-center py-1 text-zinc-300 dark:text-zinc-700">
                    <div class="w-0.5 h-4 bg-zinc-300 dark:bg-zinc-700"></div>
                </div>

                {{-- 2. FOCUS MEMBER HERO CARD & SPOUSES --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 px-1">
                        <span>⭐ Anggota Terfokus</span>
                        @if(!$isPublic)
                            <button wire:click="$dispatch('create-member', { targetId: {{ $focusMember->id }}, relType: 'spouse_of' })"
                                    class="text-emerald-600 dark:text-emerald-400 font-semibold normal-case text-xs hover:underline inline-flex items-center gap-1">
                                <span>➕ Tambah Pasangan</span>
                            </button>
                        @endif
                    </div>

                    {{-- Main Focus Hero --}}
                    <div class="bg-gradient-to-br from-white to-emerald-50/40 dark:from-zinc-900 dark:to-emerald-950/20 border-2 border-emerald-500/80 dark:border-emerald-500/50 rounded-2xl p-4 sm:p-5 shadow-lg relative overflow-hidden">
                        <div class="flex items-start gap-4">
                            <div class="relative shrink-0">
                                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full overflow-hidden border-3 {{ $focusMember->gender === 'female' ? 'border-pink-400' : 'border-teal-400' }} shadow-md bg-white dark:bg-zinc-800">
                                    <img src="{{ $getAvatar($focusMember) }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/no_profile_pic.jpg') }}'" />
                                </div>
                                <div class="absolute bottom-0 right-0 w-4 h-4 rounded-full border-2 border-white dark:border-zinc-900 {{ $focusMember->is_living ? 'bg-emerald-500' : 'bg-zinc-500' }}" title="{{ $focusMember->is_living ? 'Masih Hidup' : 'Wafat' }}"></div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-lg sm:text-xl font-black text-zinc-900 dark:text-white leading-tight">
                                        {{ $focusMember->first_name }} {{ $focusMember->last_name }}
                                    </h3>
                                    @if(!$focusMember->is_living)
                                        <span class="text-xs bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 font-bold px-2 py-0.5 rounded-md">Wafat</span>
                                    @endif
                                </div>

                                <div class="mt-1 text-xs text-zinc-600 dark:text-zinc-300 space-y-0.5">
                                    @if($focusMember->profession)
                                        <p class="font-semibold text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                                            <span>💼</span> {{ $focusMember->profession }}
                                        </p>
                                    @endif
                                    @if($focusMember->birth_date || $focusMember->birth_place)
                                        <p class="text-zinc-500 dark:text-zinc-400">
                                            🎂 {{ $focusMember->birth_place ? $focusMember->birth_place . ', ' : '' }}{{ $focusMember->birth_date ? \Carbon\Carbon::parse($focusMember->birth_date)->format('d M Y') : '' }}
                                        </p>
                                    @endif
                                </div>

                                @if($focusMember->bio)
                                    <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-400 line-clamp-2 italic bg-white/70 dark:bg-zinc-800/60 p-2 rounded-lg border border-zinc-100 dark:border-zinc-800">
                                        "{{ $focusMember->bio }}"
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Action Buttons on Hero --}}
                        <div class="mt-4 pt-3 border-t border-emerald-100 dark:border-emerald-950/60 flex items-center gap-1.5 flex-wrap">
                            <flux:button size="sm" variant="primary" wire:click="$dispatch('show-member', { id: {{ $focusMember->id }} })" icon="user" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 text-xs">
                                Profil Lengkap
                            </flux:button>
                            @if(!$isPublic)
                                <flux:button size="sm" variant="subtle" wire:click="$dispatch('create-member', { targetId: {{ $focusMember->id }}, relType: 'child_of' })" icon="plus" class="text-xs">
                                    + Anak
                                </flux:button>
                                <flux:button size="sm" variant="subtle" wire:click="$dispatch('create-member', { targetId: {{ $focusMember->id }}, relType: 'spouse_of' })" icon="heart" class="text-xs">
                                    + Pasangan
                                </flux:button>
                                <flux:button size="sm" variant="subtle" wire:click="$dispatch('create-member', { targetId: {{ $focusMember->id }}, relType: 'parent_of' })" icon="users" class="text-xs">
                                    + Ortu
                                </flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="$dispatch('edit-member', { id: {{ $focusMember->id }} })" icon="pencil" class="text-xs">
                                    Edit
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    {{-- Spouses list of focus member --}}
                    @if($focusSpouses->isNotEmpty())
                        <div class="space-y-2 pt-1">
                            <div class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider px-1">
                                💍 Pasangan ({{ $focusSpouses->count() }})
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                @foreach($focusSpouses as $spouse)
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-3 shadow-xs flex items-center justify-between gap-3 hover:shadow-md transition-all">
                                        <div class="flex items-center gap-2.5 min-w-0 cursor-pointer flex-1" wire:click="focusOn({{ $spouse->id }})">
                                            <div class="w-10 h-10 rounded-full overflow-hidden border-2 {{ $spouse->gender === 'female' ? 'border-pink-400' : 'border-teal-400' }} shrink-0">
                                                <img src="{{ $getAvatar($spouse) }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/no_profile_pic.jpg') }}'" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate">
                                                    {{ $spouse->first_name }} {{ $spouse->last_name }}
                                                </div>
                                                <div class="flex items-center gap-1 text-[11px] text-zinc-500">
                                                    @if(isset($spouse->is_current_marriage) && !$spouse->is_current_marriage)
                                                        <span class="text-amber-600 dark:text-amber-400 font-semibold">(Cerai)</span>
                                                    @else
                                                        <span class="text-pink-600 dark:text-pink-400 font-semibold">❤️ Menikah</span>
                                                    @endif
                                                    @if(!$spouse->is_living)
                                                        <span class="text-red-500">• Wafat</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <button wire:click="$dispatch('show-member', { id: {{ $spouse->id }} })" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg" title="Lihat Profil">
                                                <flux:icon name="eye" class="size-4" />
                                            </button>
                                            <button wire:click="focusOn({{ $spouse->id }})" class="p-1.5 bg-zinc-100 dark:bg-zinc-800 hover:bg-emerald-100 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-300 hover:text-emerald-600 rounded-lg font-bold" title="Fokus ke Pasangan">
                                                ➔
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Connector Line --}}
                <div class="flex justify-center items-center py-1 text-zinc-300 dark:text-zinc-700">
                    <div class="w-0.5 h-4 bg-zinc-300 dark:bg-zinc-700"></div>
                </div>

                {{-- 3. CHILDREN SECTION (KETURUNAN) --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 px-1">
                        <span>⬇️ Keturunan ({{ $focusChildren->count() }} Anak)</span>
                        @if(!$isPublic)
                            <button wire:click="$dispatch('create-member', { targetId: {{ $focusMember->id }}, relType: 'child_of' })"
                                    class="text-emerald-600 dark:text-emerald-400 font-semibold normal-case text-xs hover:underline inline-flex items-center gap-1">
                                <span>➕ Tambah Anak</span>
                            </button>
                        @endif
                    </div>

                    @if($focusChildren->isNotEmpty())
                        @if($focusSpouses->count() > 1)
                            {{-- Grouped by other parent --}}
                            @php
                                $groupKey = $focusMember->gender === 'male' ? 'mother_id' : 'father_id';
                                $grouped = $focusChildren->groupBy($groupKey);
                            @endphp
                            @foreach($focusSpouses as $sp)
                                @php $spouseChildren = $grouped->get($sp->id, collect()); @endphp
                                @if($spouseChildren->isNotEmpty())
                                    <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mt-3 ml-1">
                                        Anak dari {{ $sp->first_name }} {{ $sp->last_name }} (#{{ $loop->iteration }})
                                    </p>
                                    <div class="space-y-2">
                                        @foreach($spouseChildren as $child)
                                            @include('components.vertical-child-row', ['child' => $child, 'allMembers' => $allMembers, 'getAvatar' => $getAvatar, 'getSpouses' => $getSpouses, 'countDescendants' => $countDescendants])
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                            {{-- Unassigned --}}
                            @php $unassigned = $grouped->get(null, collect()); @endphp
                            @if($unassigned->isNotEmpty())
                                <p class="text-xs font-bold text-zinc-500 uppercase tracking-wider mt-3 ml-1">Anak Lainnya</p>
                                <div class="space-y-2">
                                    @foreach($unassigned as $child)
                                        @include('components.vertical-child-row', ['child' => $child, 'allMembers' => $allMembers, 'getAvatar' => $getAvatar, 'getSpouses' => $getSpouses, 'countDescendants' => $countDescendants])
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="space-y-2">
                                @foreach($focusChildren as $child)
                                    @include('components.vertical-child-row', ['child' => $child, 'allMembers' => $allMembers, 'getAvatar' => $getAvatar, 'getSpouses' => $getSpouses, 'countDescendants' => $countDescendants])
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div wire:click="$dispatch('create-member', { targetId: {{ $focusMember->id }}, relType: 'child_of' })"
                             class="bg-zinc-50 dark:bg-zinc-900/40 border-2 border-dashed border-zinc-300 dark:border-zinc-700 rounded-xl p-6 text-center cursor-pointer hover:border-emerald-500 dark:hover:border-emerald-400 hover:bg-emerald-50/40 transition-colors">
                            <span class="text-2xl block mb-1">👶</span>
                            <span class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Belum ada data anak</span>
                            <span class="block text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium">+ Klik di sini untuk menambahkan anak</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 2: DIRECTORY & SEARCH MODE --}}
    @if($tab === 'directory')
        <div class="px-4 lg:px-6 space-y-4">
            {{-- Search Input --}}
            <div class="relative">
                <flux:input wire:model.live.debounce.250ms="search" icon="magnifying-glass" placeholder="Cari nama anggota, kota, atau profesi..." class="w-full" clearable />
            </div>

            {{-- Filter Pills --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar text-xs font-semibold">
                <button wire:click="setFilter('all')" class="px-3 py-1.5 rounded-full transition-colors shrink-0 {{ $filter === 'all' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400' }}">
                    Semua ({{ $allMembers->count() }})
                </button>
                <button wire:click="setFilter('living')" class="px-3 py-1.5 rounded-full transition-colors shrink-0 {{ $filter === 'living' ? 'bg-emerald-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400' }}">
                    Masih Hidup ({{ $allMembers->where('is_living', true)->count() }})
                </button>
                <button wire:click="setFilter('deceased')" class="px-3 py-1.5 rounded-full transition-colors shrink-0 {{ $filter === 'deceased' ? 'bg-red-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400' }}">
                    Wafat ({{ $allMembers->where('is_living', false)->count() }})
                </button>
                <button wire:click="setFilter('male')" class="px-3 py-1.5 rounded-full transition-colors shrink-0 {{ $filter === 'male' ? 'bg-teal-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400' }}">
                    Pria ({{ $allMembers->where('gender', 'male')->count() }})
                </button>
                <button wire:click="setFilter('female')" class="px-3 py-1.5 rounded-full transition-colors shrink-0 {{ $filter === 'female' ? 'bg-pink-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400' }}">
                    Wanita ({{ $allMembers->where('gender', 'female')->count() }})
                </button>
            </div>

            {{-- Members List --}}
            <div class="space-y-2">
                @forelse($directoryMembers as $m)
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-3.5 shadow-xs flex items-center justify-between gap-3 hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3 min-w-0 cursor-pointer flex-1" wire:click="focusOn({{ $m->id }})">
                            <div class="w-11 h-11 rounded-full overflow-hidden border-2 {{ $m->gender === 'female' ? 'border-pink-400' : 'border-teal-400' }} shrink-0">
                                <img src="{{ $getAvatar($m) }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/no_profile_pic.jpg') }}'" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ $m->first_name }} {{ $m->last_name }}</h4>
                                    @if(!$m->is_living)
                                        <span class="text-[10px] bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400 px-1.5 py-0.2 rounded font-medium">Wafat</span>
                                    @endif
                                </div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                    {{ $m->profession ?? ($m->gender === 'female' ? 'Wanita' : 'Pria') }}
                                    @if($m->birth_place) • {{ $m->birth_place }} @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button wire:click="$dispatch('show-member', { id: {{ $m->id }} })" class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg" title="Lihat Profil">
                                <flux:icon name="eye" class="size-4" />
                            </button>
                            <button wire:click="focusOn({{ $m->id }})" class="p-2 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 rounded-lg text-xs font-bold flex items-center gap-1" title="Buka di Explorer">
                                <span>Fokus</span>
                                <span>➔</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="bg-zinc-50 dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-800 rounded-xl p-8 text-center text-zinc-500">
                        <p class="text-sm font-medium">Tidak ada anggota yang cocok dengan pencarian.</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    {{-- STICKY BOTTOM NAVIGATION BAR (Mobile Only) --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-around px-2 z-40 shadow-lg">
        <button wire:click="setTab('explorer')"
                class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold transition-colors {{ $tab === 'explorer' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400' }}">
            <span class="text-lg">🧭</span>
            <span class="text-[11px]">Fokus</span>
        </button>

        <button wire:click="setTab('directory')"
                class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold transition-colors {{ $tab === 'directory' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400' }}">
            <span class="text-lg">👥</span>
            <span class="text-[11px]">Direktori</span>
        </button>

        <a href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug) : '#') : route('tree.show', $tree) }}"
           wire:navigate
           class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold text-zinc-500 dark:text-zinc-400 hover:text-zinc-800">
            <span class="text-lg">🌳</span>
            <span class="text-[11px]">Bagan</span>
        </a>

        @if(!$isPublic)
            <button wire:click="$dispatch('create-member', { targetId: {{ $focusMember?->id }}, relType: 'child_of' })"
                    class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <span class="text-lg font-bold">➕</span>
                <span class="text-[11px]">Tambah</span>
            </button>
        @endif
    </nav>

    {{-- Livewire Components --}}
    @if(!$isPublic)
        <livewire:member-manager :tree-id="$tree->id" />
        <livewire:import-members :tree-id="$tree->id" />
    @endif

    {{-- Floating Loading Indicator --}}
    <div wire:loading.flex class="fixed bottom-20 md:bottom-6 right-6 bg-zinc-900/90 text-white px-4 py-2.5 rounded-full shadow-2xl items-center gap-3 text-sm font-medium z-50 border border-zinc-700/50 backdrop-blur-md">
        <svg class="animate-spin h-4 w-4 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Memuat data...</span>
    </div>
</div>
