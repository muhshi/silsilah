@props(['member', 'allMembers'])

@php
    // Get spouses from marriage relationships
    $spouses = collect();
    if ($member->gender === 'male' && $member->relationLoaded('marriagesAsHusband')) {
        $spouseIds = $member->marriagesAsHusband->pluck('wife_id');
        $spouses = $allMembers->whereIn('id', $spouseIds);
    } elseif ($member->gender === 'female' && $member->relationLoaded('marriagesAsWife')) {
        $spouseIds = $member->marriagesAsWife->pluck('husband_id');
        $spouses = $allMembers->whereIn('id', $spouseIds);
    }

    // Get ALL children sorted
    $childFilter = $member->gender === 'male' ? 'father_id' : 'mother_id';
    $allChildren = $allMembers->where($childFilter, $member->id)
        ->sortBy(function ($child) {
            $bd = $child->birth_date ? strtotime($child->birth_date) : PHP_INT_MAX;
            return [$bd, $child->order ?? 999, $child->id];
        });
@endphp

<li class="{{ $spouses->isNotEmpty() ? 'haswife' : '' }}">
    {{-- Spouse(s) --}}
    @foreach($spouses as $spouse)
        <a class="partner st-{{ $spouse->gender }}">
            <strong>{{ $spouse->first_name }}</strong>
        </a>
    @endforeach

    {{-- Main Member --}}
    <a class="{{ $spouses->isNotEmpty() ? 'haswife' : '' }} st-{{ $member->gender }}"
       wire:click.prevent="$dispatch('show-member', { id: {{ $member->id }} })">
        <strong>{{ $member->first_name }}</strong>
    </a>

    {{-- Hidden partner placeholder(s) --}}
    @for($i = 0; $i < $spouses->count(); $i++)
        <a class="partner hid"></a>
    @endfor

    {{-- Children --}}
    @if($allChildren->isNotEmpty())
        @if($spouses->count() > 1 && $member->gender === 'male')
            @php $grouped = $allChildren->groupBy('mother_id'); @endphp
            <ul>
                @foreach($spouses as $spouse)
                    @php $spouseChildren = $grouped->get($spouse->id, collect()); @endphp
                    @if($spouseChildren->isNotEmpty())
                        @foreach($spouseChildren as $child)
                            <x-simple-tree-node :member="$child" :all-members="$allMembers" />
                        @endforeach
                    @endif
                @endforeach
                @php $noMotherChildren = $grouped->get(null, collect())->merge($grouped->filter(fn($v, $k) => $k && !$spouses->pluck('id')->contains($k))->flatten(1)); @endphp
                @if($noMotherChildren->isNotEmpty())
                    @foreach($noMotherChildren as $child)
                        <x-simple-tree-node :member="$child" :all-members="$allMembers" />
                    @endforeach
                @endif
            </ul>
        @else
            <ul>
                @foreach($allChildren as $child)
                    <x-simple-tree-node :member="$child" :all-members="$allMembers" />
                @endforeach
            </ul>
        @endif
    @endif
</li>
