<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\FamilyTree;
use App\Models\Member;

new class extends Component
{
    public $tree;
    public ?int $focusMemberId = null;
    public $isPublic = false;
    public $publicSlug = null;

    public function mount($id, $isPublic = false, $publicSlug = null)
    {
        $this->isPublic = $isPublic;
        $this->publicSlug = $publicSlug;

        $this->tree = FamilyTree::with([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ])->findOrFail($id);

        if (!$this->isPublic) {
            if ($this->tree->users()->where('user_id', auth()->id())->doesntExist() && !$this->tree->is_public) {
                abort(403, 'Unauthorized access.');
            }
        }

        $this->focusMemberId = request()->query('member') ? (int) request()->query('member') : null;
    }

    #[On('refresh-tree')]
    public function refreshTree()
    {
        $this->mount($this->tree->id);
    }

    public function focusOn(int $memberId)
    {
        $this->focusMemberId = $memberId;
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
        $getAvatar = function($m) {
            if ($m->photo) {
                return asset('storage/' . $m->photo);
            }
            return $m->avatar_id
                ? asset('images/avatar/' . $m->avatar_id)
                : asset('images/no_profile_pic.jpg');
        };

        // Determine the focus member and their context
        if ($this->focusMemberId) {
            $focusMember = $allMembers->firstWhere('id', $this->focusMemberId);
        } else {
            $focusMember = null;
        }

        // Get spouses for a member
        $getSpouses = function($member) use ($allMembers) {
            if ($member->gender === 'male' && $member->relationLoaded('marriagesAsHusband')) {
                return $allMembers->whereIn('id', $member->marriagesAsHusband->pluck('wife_id'));
            } elseif ($member->gender === 'female' && $member->relationLoaded('marriagesAsWife')) {
                return $allMembers->whereIn('id', $member->marriagesAsWife->pluck('husband_id'));
            }
            return collect();
        };

        // Get children sorted
        $getChildren = function($member) use ($allMembers) {
            $childFilter = $member->gender === 'male' ? 'father_id' : 'mother_id';
            return $allMembers->where($childFilter, $member->id)
                ->sortBy(function ($child) {
                    $bd = $child->birth_date ? strtotime($child->birth_date) : PHP_INT_MAX;
                    return [$bd, $child->order ?? 999, $child->id];
                });
        };

        // Count grandchildren for a given child
        $countDescendants = function($member) use ($allMembers) {
            $count = 0;
            $count += $allMembers->where('father_id', $member->id)->count();
            $count += $allMembers->where('mother_id', $member->id)->count();
            return $count;
        };

        // Root members (if no focus)
        if (!$focusMember) {
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
        } else {
            $rootMembers = collect([$focusMember]);
        }

        // Build breadcrumbs for focus member
        $breadcrumbs = collect();
        if ($focusMember) {
            $current = $focusMember;
            $visited = collect();
            while ($current && !$visited->contains($current->id)) {
                $visited->push($current->id);
                $breadcrumbs->prepend($current);
                $parentId = $current->father_id ?? $current->mother_id;
                $current = $parentId ? $allMembers->firstWhere('id', $parentId) : null;
            }
        }

        return [
            'tree' => $this->tree,
            'allMembers' => $allMembers,
            'rootMembers' => $rootMembers,
            'focusMember' => $focusMember,
            'getAvatar' => $getAvatar,
            'getSpouses' => $getSpouses,
            'getChildren' => $getChildren,
            'countDescendants' => $countDescendants,
            'breadcrumbs' => $breadcrumbs,
        ];
    }
};
?>

<div class="w-full max-w-3xl mx-auto">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between px-4 lg:px-8 py-4 gap-3">
        <div class="min-w-0">
            <h2 class="text-2xl font-bold dark:text-white truncate">{{ $tree->name }}</h2>
            @if($tree->description)
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $tree->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if(!$isPublic)
                <flux:button size="sm" icon="arrow-left" href="{{ route('tree.show', $tree->id) }}" wire:navigate class="!bg-zinc-100 !text-zinc-700 hover:!bg-zinc-200 dark:!bg-zinc-800 dark:!text-zinc-300 dark:hover:!bg-zinc-700">Horizontal</flux:button>
                <flux:button size="sm" icon="document-text" href="{{ route('tree.simple', $tree->id) }}" wire:navigate class="!bg-indigo-50 !text-indigo-700 hover:!bg-indigo-100 dark:!bg-indigo-900/30 dark:!text-indigo-400 dark:hover:!bg-indigo-900/50">Simple View</flux:button>
                @if($tree->slug)
                    <flux:button size="sm" icon="share" x-data="{ shared: false }"
                        x-on:click="navigator.clipboard.writeText('{{ url('/public/tree/' . $tree->slug) }}'); shared = true; setTimeout(() => shared = false, 2000)"
                        class="!bg-emerald-50 !text-emerald-700 hover:!bg-emerald-100 dark:!bg-emerald-900/30 dark:!text-emerald-400 dark:hover:!bg-emerald-900/50">
                        <span x-show="!shared">Share</span>
                        <span x-show="shared" class="text-emerald-600 dark:text-emerald-400">Tersalin!</span>
                    </flux:button>
                @endif
                <flux:button size="sm" icon="arrow-down-on-square" wire:click="$dispatch('open-import-modal')" class="!bg-zinc-100 !text-zinc-700 hover:!bg-zinc-200 dark:!bg-zinc-800 dark:!text-zinc-300 dark:hover:!bg-zinc-700">Import Data</flux:button>
                <flux:button size="sm" variant="primary" icon="plus" wire:click="$dispatch('create-member')">Anggota Baru</flux:button>
            @else
                <flux:button size="sm" icon="arrow-left" href="{{ $publicSlug ? url('/public/tree/'.$publicSlug) : '#' }}" class="!bg-zinc-100 !text-zinc-700 hover:!bg-zinc-200 dark:!bg-zinc-800 dark:!text-zinc-300 dark:hover:!bg-zinc-700">Horizontal</flux:button>
                <flux:button size="sm" icon="document-text" href="{{ $publicSlug ? url('/public/tree/'.$publicSlug.'/simple') : '#' }}" class="!bg-indigo-50 !text-indigo-700 hover:!bg-indigo-100 dark:!bg-indigo-900/30 dark:!text-indigo-400 dark:hover:!bg-indigo-900/50">Simple View</flux:button>
            @endif

            <flux:dropdown>
                <flux:button size="sm" icon="arrow-down-tray" class="!bg-purple-50 !text-purple-700 hover:!bg-purple-100 dark:!bg-purple-900/30 dark:!text-purple-400 dark:hover:!bg-purple-900/50">
                    Export
                </flux:button>
                <flux:menu>
                    <flux:menu.item icon="photo" href="{{ route('tree.export', ['id' => $tree->id, 'format' => 'png', 'view' => 'vertical']) }}">
                        Export Gambar (PNG)
                    </flux:menu.item>
                    <flux:menu.item icon="document-arrow-down" href="{{ route('tree.export', ['id' => $tree->id, 'format' => 'pdf', 'view' => 'vertical']) }}">
                        Export Dokumen (PDF)
                    </flux:menu.item>
                    <flux:menu.item icon="code-bracket" href="{{ route('tree.export', ['id' => $tree->id, 'format' => 'json']) }}">
                        Export Data (JSON)
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Breadcrumbs --}}
    @if($focusMember)
        <div class="px-4 lg:px-8 pb-3">
            <nav class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 flex-wrap">
                <button wire:click="goToRoot" class="hover:text-indigo-600 dark:hover:text-indigo-400 font-medium">🏠 Root</button>
                @foreach($breadcrumbs as $i => $crumb)
                    <span class="mx-1">›</span>
                    @if($loop->last)
                        <span class="text-gray-800 dark:text-gray-200 font-semibold">{{ $crumb->first_name }}</span>
                    @else
                        <button wire:click="focusOn({{ $crumb->id }})" class="hover:text-indigo-600 dark:hover:text-indigo-400 font-medium">{{ $crumb->first_name }}</button>
                    @endif
                @endforeach
            </nav>
        </div>
    @endif

    {{-- Tree Content --}}
    <div class="px-4 lg:px-8 pb-8 space-y-6">
        @foreach($rootMembers as $member)
            @php
                $spouses = $getSpouses($member);
                $children = $getChildren($member);
            @endphp

            {{-- Parent Card --}}
            <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-md border border-gray-200 dark:border-zinc-700 p-5">
                <div class="flex items-center gap-4 flex-wrap">
                    {{-- Primary Member --}}
                    <div class="flex items-center gap-3 cursor-pointer" wire:click="$dispatch('show-member', { id: {{ $member->id }} })">
                        <div class="w-16 h-16 rounded-full overflow-hidden border-2 {{ $member->gender === 'female' ? 'border-pink-500' : 'border-teal-500' }} shadow-md">
                            <img src="{{ $getAvatar($member) }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/no_profile_pic.jpg') }}'" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold {{ $member->gender === 'female' ? 'text-pink-600 dark:text-pink-400' : 'text-teal-600 dark:text-teal-400' }}">{{ $member->first_name }} {{ $member->last_name }}</h3>
                                @if(!$member->is_living)
                                    <span class="text-xs bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded font-medium">Wafat</span>
                                @endif
                            </div>
                            @if($member->birth_date)
                                <p class="text-xs text-gray-500 dark:text-gray-400">🎂 {{ \Carbon\Carbon::parse($member->birth_date)->format('d M Y') }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Spouse(s) --}}
                    @foreach($spouses as $spouse)
                        <span class="text-lg">❤️</span>
                        <div class="flex items-center gap-3 cursor-pointer" wire:click="$dispatch('show-member', { id: {{ $spouse->id }} })">
                            <div class="w-14 h-14 rounded-full overflow-hidden border-2 {{ $spouse->gender === 'female' ? 'border-pink-300' : 'border-teal-300' }} shadow relative">
                                <img src="{{ $getAvatar($spouse) }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/no_profile_pic.jpg') }}'" />
                                @if($spouses->count() > 1)
                                    <span class="absolute top-0 left-0 bg-amber-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow">#{{ $loop->iteration }}</span>
                                @endif
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <strong class="text-base {{ $spouse->gender === 'female' ? 'text-pink-600 dark:text-pink-400' : 'text-teal-600 dark:text-teal-400' }}">{{ $spouse->first_name }} {{ $spouse->last_name }}</strong>
                                    @if(!$spouse->is_living)
                                        <span class="text-xs bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 px-1.5 py-0.5 rounded font-medium">Wafat</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Children List --}}
            @if($children->isNotEmpty())
                @if($spouses->count() > 1)
                    {{-- Grouped by spouse (mother_id if father, father_id if mother) --}}
                    @php
                        $groupKey = $member->gender === 'male' ? 'mother_id' : 'father_id';
                        $grouped = $children->groupBy($groupKey);
                    @endphp
                    @foreach($spouses as $spouse)
                        @php $spouseChildren = $grouped->get($spouse->id, collect()); @endphp
                        @if($spouseChildren->isNotEmpty())
                            <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mt-4 ml-1">Anak dari {{ $spouse->first_name }} {{ $spouse->last_name }} @if($spouses->count() > 1) (#{{ $loop->iteration }}) @endif</p>
                            <div class="space-y-2 mt-1">
                                @foreach($spouseChildren as $child)
                                    @include('components.vertical-child-row', ['child' => $child, 'allMembers' => $allMembers, 'getAvatar' => $getAvatar, 'getSpouses' => $getSpouses, 'countDescendants' => $countDescendants])
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                    {{-- Children without spouse assigned --}}
                    @php $unassigned = $grouped->get(null, collect()); @endphp
                    @if($unassigned->isNotEmpty())
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mt-4 ml-1">Anak (Lainnya)</p>
                        <div class="space-y-2 mt-1">
                            @foreach($unassigned as $child)
                                @include('components.vertical-child-row', ['child' => $child, 'allMembers' => $allMembers, 'getAvatar' => $getAvatar, 'getSpouses' => $getSpouses, 'countDescendants' => $countDescendants])
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="space-y-2">
                        @foreach($children as $child)
                            @include('components.vertical-child-row', ['child' => $child, 'allMembers' => $allMembers, 'getAvatar' => $getAvatar, 'getSpouses' => $getSpouses, 'countDescendants' => $countDescendants])
                        @endforeach
                    </div>
                @endif
            @endif
        @endforeach
    </div>

    @if(!$isPublic)
        <livewire:member-manager :tree-id="$tree->id" />
        <livewire:import-members :tree-id="$tree->id" />
    @endif

    {{-- Floating Loading Indicator --}}
    <div wire:loading.flex class="fixed bottom-6 right-6 bg-zinc-900/90 text-white px-4 py-2.5 rounded-full shadow-2xl items-center gap-3 text-sm font-medium z-50 border border-zinc-700/50 backdrop-blur-md">
        <svg class="animate-spin h-4 w-4 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Memuat data...</span>
    </div>
</div>
