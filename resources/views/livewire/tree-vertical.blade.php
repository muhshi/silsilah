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
        $allMembers = $this->tree->members;

        // Helper: get avatar URL
        $getAvatar = function($m) {
            if ($m->photo) {
                return str_starts_with($m->photo, 'http') ? $m->photo : asset('storage/' . $m->photo);
            }
            return $m->avatar_id
                ? 'https://app.pohonkeluarga.com/images/avatar/' . $m->avatar_id . '.jpg'
                : 'https://app.pohonkeluarga.com/images/no_profile_pic.jpg';
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
            $wifeIds = collect();
            foreach ($allMembers->where('gender', 'male') as $m) {
                foreach ($m->marriagesAsHusband as $marriage) {
                    $wifeIds->push($marriage->wife_id);
                }
            }
            $parentless = $allMembers->whereNull('father_id')->whereNull('mother_id');
            $rootMembers = $parentless->whereNotIn('id', $wifeIds->unique());
        } else {
            $rootMembers = collect([$focusMember]);
        }

        // Build breadcrumbs for focus member
        $breadcrumbs = collect();
        if ($focusMember) {
            $current = $focusMember;
            while ($current) {
                $breadcrumbs->prepend($current);
                $parentId = $current->father_id ?? $current->mother_id;
                $current = $parentId ? $allMembers->firstWhere('id', $parentId) : null;
            }
        }

        return compact('allMembers', 'rootMembers', 'focusMember', 'getAvatar', 'getSpouses', 'getChildren', 'countDescendants', 'breadcrumbs');
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
                <flux:button size="sm" variant="primary" icon="plus" wire:click="$dispatch('create-member')">Anggota Baru</flux:button>
            @else
                <flux:button size="sm" icon="arrow-left" href="{{ $publicSlug ? url('/public/tree/'.$publicSlug) : '#' }}" class="!bg-zinc-100 !text-zinc-700 hover:!bg-zinc-200 dark:!bg-zinc-800 dark:!text-zinc-300 dark:hover:!bg-zinc-700">Horizontal</flux:button>
                <flux:button size="sm" icon="document-text" href="{{ $publicSlug ? url('/public/tree/'.$publicSlug.'/simple') : '#' }}" class="!bg-indigo-50 !text-indigo-700 hover:!bg-indigo-100 dark:!bg-indigo-900/30 dark:!text-indigo-400 dark:hover:!bg-indigo-900/50">Simple View</flux:button>
            @endif
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
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-5 shadow-sm">
                <div class="flex items-center gap-4 flex-wrap">
                    {{-- Main member --}}
                    <div class="flex items-center gap-3 cursor-pointer" wire:click="$dispatch('show-member', { id: {{ $member->id }} })">
                        <div class="w-16 h-16 rounded-full overflow-hidden border-3 {{ $member->gender === 'female' ? 'border-pink-400' : 'border-teal-400' }} shadow">
                            <img src="{{ $getAvatar($member) }}" class="w-full h-full object-cover" onerror="this.src='https://app.pohonkeluarga.com/images/no_profile_pic.jpg'" />
                        </div>
                        <div>
                            <strong class="text-lg {{ $member->gender === 'female' ? 'text-pink-600 dark:text-pink-400' : 'text-teal-600 dark:text-teal-400' }}">{{ $member->first_name }} {{ $member->last_name }}</strong>
                            @if($member->birth_date)
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($member->birth_date)->format('d M Y') }}</p>
                            @endif
                            @if(!$member->is_living)
                                <span class="text-xs bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 px-1.5 py-0.5 rounded">Wafat</span>
                            @endif
                        </div>
                    </div>

                    {{-- Spouse(s) --}}
                    @foreach($spouses as $spouse)
                        <span class="text-lg">❤️</span>
                        <div class="flex items-center gap-3 cursor-pointer" wire:click="$dispatch('show-member', { id: {{ $spouse->id }} })">
                            <div class="w-14 h-14 rounded-full overflow-hidden border-2 {{ $spouse->gender === 'female' ? 'border-pink-300' : 'border-teal-300' }} shadow">
                                <img src="{{ $getAvatar($spouse) }}" class="w-full h-full object-cover" onerror="this.src='https://app.pohonkeluarga.com/images/no_profile_pic.jpg'" />
                            </div>
                            <div>
                                <strong class="text-base {{ $spouse->gender === 'female' ? 'text-pink-600 dark:text-pink-400' : 'text-teal-600 dark:text-teal-400' }}">{{ $spouse->first_name }}</strong>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Children List --}}
            @if($children->isNotEmpty())
                @if($spouses->count() > 1 && $member->gender === 'male')
                    {{-- Grouped by wife --}}
                    @php $grouped = $children->groupBy('mother_id'); @endphp
                    @foreach($spouses as $spouse)
                        @php $spouseChildren = $grouped->get($spouse->id, collect()); @endphp
                        @if($spouseChildren->isNotEmpty())
                            <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mt-4 ml-1">Anak dari {{ $spouse->first_name }}</p>
                            <div class="space-y-2">
                                @foreach($spouseChildren as $child)
                                    @include('components.vertical-child-row', ['child' => $child, 'allMembers' => $allMembers, 'getAvatar' => $getAvatar, 'getSpouses' => $getSpouses, 'countDescendants' => $countDescendants])
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                    {{-- Children without mother --}}
                    @php $noMother = $grouped->get(null, collect()); @endphp
                    @if($noMother->isNotEmpty())
                        <div class="space-y-2 mt-2">
                            @foreach($noMother as $child)
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
    @endif
</div>
