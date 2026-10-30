@props(['member', 'allMembers', 'isPublic' => false])

@php
    // Get spouses from marriage relationships
    $spouses = collect();
    $marriages = collect();
    if ($member->gender === 'male' && $member->relationLoaded('marriagesAsHusband')) {
        $marriages = $member->marriagesAsHusband;
        $spouseIds = $marriages->pluck('wife_id');
        $spouses = $allMembers->whereIn('id', $spouseIds);
    } elseif ($member->gender === 'female' && $member->relationLoaded('marriagesAsWife')) {
        $marriages = $member->marriagesAsWife;
        $spouseIds = $marriages->pluck('husband_id');
        $spouses = $allMembers->whereIn('id', $spouseIds);
    }

    // Get ALL children of this member, sorted: order (if > 0) → birth_date (nulls last) → id
    $childFilter = $member->gender === 'male' ? 'father_id' : 'mother_id';
    $allChildren = $allMembers->where($childFilter, $member->id)
        ->sortBy(function ($child) {
            $order = ($child->order !== null && (int) $child->order > 0) ? (int) $child->order : 999;
            $bd = $child->birth_date ? strtotime($child->birth_date) : PHP_INT_MAX;

            return [$order, $bd, $child->id];
        });

    // Avatar helper
    $getAvatar = function($m) {
        if ($m->photo) {
            return str_starts_with($m->photo, 'http') ? $m->photo : asset('storage/' . $m->photo);
        }
        return $m->avatar_id
            ? asset('images/avatar/' . $m->avatar_id)
            : asset('images/no_profile_pic.jpg');
    };
    $avatarUrl = $getAvatar($member);
@endphp

<li class="{{ $spouses->isNotEmpty() ? 'haswife' : '' }}">
    {{-- Spouse(s) --}}
    @foreach($spouses as $spouse)
        @php
            $marriage = $marriages->first(fn($m) => $member->gender === 'male' ? $m->wife_id === $spouse->id : $m->husband_id === $spouse->id);
            $isCurrent = $marriage ? $marriage->is_current : true;
        @endphp
        <a class="partner gender-{{ $spouse->gender }}"
           x-data="{ open: false }"
           x-on:click.outside="open = false"
           :class="{ 'show-options': open }"
           x-on:click="
               if (window.matchMedia('(hover: none)').matches) {
                   if (!open) {
                       open = true;
                       return;
                   }
               }
               $dispatch('show-member', { id: {{ $spouse->id }} });
           ">
            @if($spouses->count() > 1)
                <span class="pt-spouse-num">#{{ $loop->iteration }}</span>
            @endif
            @if(!$spouse->is_living)
                <span class="pt-dead">Wafat</span>
            @endif
            @if(!$isCurrent)
                <span class="pt-divorced {{ !$spouse->is_living ? 'has-dead' : '' }}">Cerai</span>
            @endif
            <div class="pt-thumb">
                <img src="{{ $getAvatar($spouse) }}" onerror="this.src='{{ asset('images/no_profile_pic.jpg') }}'" />
            </div>
            <strong>{{ trim($spouse->first_name . ' ' . $spouse->last_name) }}</strong>
            @if(!$isPublic)
                <span class="pt-options" onclick="event.stopPropagation()">
                    <button type="button" class="tree-edit" wire:click.stop.prevent="$dispatch('edit-member', { id: {{ $spouse->id }} })" title="Edit Data">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                    </button>
                    <button type="button" class="tree-add" wire:click.stop.prevent="$dispatch('create-member', { targetId: {{ $spouse->id }} })" title="Tambah Anggota / Relasi">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <button type="button" class="tree-delete" wire:click.stop.prevent="$dispatch('confirm-delete-member', { id: {{ $spouse->id }} })" title="Hapus">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                </span>
            @endif
        </a>
    @endforeach

    {{-- Main Member Node --}}
    <a class="{{ $spouses->isNotEmpty() ? 'haswife' : '' }} gender-{{ $member->gender }}"
       data-tour="member-card"
       x-data="{ open: false }"
       x-on:click.outside="open = false"
       :class="{ 'show-options': open }"
       x-on:click="
           if (window.matchMedia('(hover: none)').matches) {
               if (!open) {
                   open = true;
                   return;
               }
           }
           $dispatch('show-member', { id: {{ $member->id }} });
       ">
        @if(!$member->is_living)
            <span class="pt-dead">Wafat</span>
        @endif
        <div class="pt-thumb">
            <img src="{{ $avatarUrl }}" onerror="this.src='{{ asset('images/no_profile_pic.jpg') }}'" />
        </div>
        <strong>@if($member->order && (int)$member->order > 0)<span class="text-xs opacity-75 font-normal mr-0.5">#{{ $member->order }}</span>@endif{{ trim($member->first_name . ' ' . $member->last_name) }}</strong>

        @if(!$isPublic)
            <span class="pt-options" onclick="event.stopPropagation()">
                <button type="button" class="tree-edit" wire:click.stop.prevent="$dispatch('edit-member', { id: {{ $member->id }} })" title="Edit Data">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                </button>
                <button type="button" class="tree-add" data-tour="add-rel-btn" wire:click.stop.prevent="$dispatch('create-member', { targetId: {{ $member->id }} })" title="Tambah Anggota / Relasi">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <button type="button" class="tree-delete" wire:click.stop.prevent="$dispatch('confirm-delete-member', { id: {{ $member->id }} })" title="Hapus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                </button>
            </span>
        @endif
    </a>

    {{-- Hidden partner placeholder(s) --}}
    @for($i = 0; $i < $spouses->count(); $i++)
        <a class="partner hid"></a>
    @endfor

    {{-- Children rendering --}}
    @if($allChildren->isNotEmpty())
        @if($spouses->count() > 1)
            {{-- Multiple spouses: group children by other parent --}}
            @php
                $groupKey = $member->gender === 'male' ? 'mother_id' : 'father_id';
                $grouped = $allChildren->groupBy($groupKey);
            @endphp
            <ul>
                @foreach($spouses as $spouse)
                    @php $spouseChildren = $grouped->get($spouse->id, collect()); @endphp
                    @if($spouseChildren->isNotEmpty())
                        @php
                            $spMarriage = $marriages->first(fn($m) => $member->gender === 'male' ? $m->wife_id === $spouse->id : $m->husband_id === $spouse->id);
                            $spIsCurrent = $spMarriage ? $spMarriage->is_current : true;
                        @endphp
                        <li class="wife-group">
                            <span class="wife-group-label">{{ $spouse->first_name }} @if($spouses->count() > 1) (#{{ $loop->iteration }}) @endif @if(!$spIsCurrent) (Cerai) @endif</span>
                            <ul>
                                @foreach($spouseChildren as $child)
                                    <x-tree-node :member="$child" :all-members="$allMembers" :is-public="$isPublic" />
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach
                {{-- Children with no other parent assigned --}}
                @php $unassignedChildren = $grouped->get(null, collect())->merge($grouped->filter(fn($v, $k) => $k && !$spouses->pluck('id')->contains($k))->flatten(1)); @endphp
                @if($unassignedChildren->isNotEmpty())
                    @foreach($unassignedChildren as $child)
                        <x-tree-node :member="$child" :all-members="$allMembers" :is-public="$isPublic" />
                    @endforeach
                @endif
            </ul>
        @else
            {{-- Single wife or female member: flat children list --}}
            <ul>
                @foreach($allChildren as $child)
                    <x-tree-node :member="$child" :all-members="$allMembers" :is-public="$isPublic" />
                @endforeach
            </ul>
        @endif
    @endif
</li>
