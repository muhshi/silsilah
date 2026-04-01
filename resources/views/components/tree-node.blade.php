@props(['member', 'allMembers'])

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

    // Get ALL children of this member, sorted: birth_date (nulls last) → order → id
    $childFilter = $member->gender === 'male' ? 'father_id' : 'mother_id';
    $allChildren = $allMembers->where($childFilter, $member->id)
        ->sortBy(function ($child) {
            $bd = $child->birth_date ? strtotime($child->birth_date) : PHP_INT_MAX;
            return [$bd, $child->order ?? 999, $child->id];
        });

    // Avatar helper
    $getAvatar = function($m) {
        if ($m->photo) {
            return str_starts_with($m->photo, 'http')
                ? $m->photo
                : asset('storage/' . $m->photo);
        }
        return $m->avatar_id
            ? 'https://app.pohonkeluarga.com/images/avatar/' . $m->avatar_id . '.jpg'
            : 'https://app.pohonkeluarga.com/images/no_profile_pic.jpg';
    };
    $avatarUrl = $getAvatar($member);
@endphp

<li class="{{ $spouses->isNotEmpty() ? 'haswife' : '' }}">
    {{-- Spouse(s) — smaller, only edit/delete --}}
    @foreach($spouses as $spouse)
        <a class="partner gender-{{ $spouse->gender }}" wire:click.prevent="$dispatch('show-member', { id: {{ $spouse->id }} })">
            <div class="pt-thumb">
                <img src="{{ $getAvatar($spouse) }}" onerror="this.src='https://app.pohonkeluarga.com/images/no_profile_pic.jpg'" />
            </div>
            <strong>{{ trim($spouse->first_name . ' ' . $spouse->last_name) }}</strong>
            <span class="pt-options">
                <b class="tree-edit" wire:click.stop="$dispatch('edit-member', { id: {{ $spouse->id }} })" title="Edit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                </b>
                <b class="tree-delete" wire:click.stop="$dispatch('confirm-delete-member', { id: {{ $spouse->id }} })" title="Hapus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                </b>
            </span>
        </a>
    @endforeach

    {{-- Main Member --}}
    <a class="{{ $spouses->isNotEmpty() ? 'haswife' : '' }} gender-{{ $member->gender }}" wire:click.prevent="$dispatch('show-member', { id: {{ $member->id }} })">
        @if(!$member->is_living)
            <span class="pt-dead">Wafat</span>
        @endif
        <div class="pt-thumb">
            <img src="{{ $avatarUrl }}" onerror="this.src='https://app.pohonkeluarga.com/images/no_profile_pic.jpg'" />
        </div>
        <strong>{{ trim($member->first_name . ' ' . $member->last_name) }}</strong>

        <span class="pt-options">
            <b class="tree-edit" wire:click.stop="$dispatch('edit-member', { id: {{ $member->id }} })" title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
            </b>
            <b class="tree-delete" wire:click.stop="$dispatch('confirm-delete-member', { id: {{ $member->id }} })" title="Hapus">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
            </b>
            <b class="tree-add" wire:click.stop="$dispatch('create-member', { targetId: {{ $member->id }}, relType: 'child_of' })" title="Tambah">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </b>
        </span>
    </a>

    {{-- Hidden partner placeholder(s) --}}
    @for($i = 0; $i < $spouses->count(); $i++)
        <a class="partner hid"></a>
    @endfor

    {{-- Children rendering --}}
    @if($allChildren->isNotEmpty())
        @if($spouses->count() > 1 && $member->gender === 'male')
            {{-- Multiple wives: group children by mother --}}
            @php
                $grouped = $allChildren->groupBy('mother_id');
            @endphp
            <ul>
                @foreach($spouses as $spouse)
                    @php $spouseChildren = $grouped->get($spouse->id, collect()); @endphp
                    @if($spouseChildren->isNotEmpty())
                        {{-- Wrapper li for each wife's children group --}}
                        <li class="wife-group">
                            <span class="wife-group-label">{{ $spouse->first_name }}</span>
                            <ul>
                                @foreach($spouseChildren as $child)
                                    <x-tree-node :member="$child" :all-members="$allMembers" />
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach
                {{-- Children with no mother assigned --}}
                @php $noMotherChildren = $grouped->get(null, collect())->merge($grouped->filter(fn($v, $k) => $k && !$spouses->pluck('id')->contains($k))->flatten(1)); @endphp
                @if($noMotherChildren->isNotEmpty())
                    @foreach($noMotherChildren as $child)
                        <x-tree-node :member="$child" :all-members="$allMembers" />
                    @endforeach
                @endif
            </ul>
        @else
            {{-- Single wife or female member: flat children list --}}
            <ul>
                @foreach($allChildren as $child)
                    <x-tree-node :member="$child" :all-members="$allMembers" />
                @endforeach
            </ul>
        @endif
    @endif
</li>
