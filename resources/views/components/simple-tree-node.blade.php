@props(['member', 'allMembers'])

@php
    // Get spouses from marriage relationships
    $spouses = collect();
    if ($member->gender === 'male' && $member->relationLoaded('marriagesAsHusband')) {
        $spouses = $member->marriagesAsHusband->map(function($m) use ($allMembers) {
            $wife = $allMembers->firstWhere('id', $m->wife_id);
            if ($wife) {
                $wife = clone $wife;
                $wife->is_current_marriage = $m->is_current;
            }
            return $wife;
        })->filter();
    } elseif ($member->gender === 'female' && $member->relationLoaded('marriagesAsWife')) {
        $spouses = $member->marriagesAsWife->map(function($m) use ($allMembers) {
            $husband = $allMembers->firstWhere('id', $m->husband_id);
            if ($husband) {
                $husband = clone $husband;
                $husband->is_current_marriage = $m->is_current;
            }
            return $husband;
        })->filter();
    }

    // Get ALL children sorted
    $childFilter = $member->gender === 'male' ? 'father_id' : 'mother_id';
    $allChildren = $allMembers->where($childFilter, $member->id)
        ->sortBy(function ($child) {
            $bd = $child->birth_date ? strtotime($child->birth_date) : PHP_INT_MAX;
            return [$bd, $child->order ?? 999, $child->id];
        });

    $getYears = function($m) {
        $b = $m->birth_date ? \Carbon\Carbon::parse($m->birth_date)->format('Y') : null;
        $d = $m->death_date ? \Carbon\Carbon::parse($m->death_date)->format('Y') : null;
        if ($b && $d) return "{$b}-{$d}";
        if ($b) return "{$b}";
        if ($d) return "w. {$d}";
        return null;
    };
@endphp

<li class="{{ $spouses->isNotEmpty() ? 'haswife' : '' }}">
    {{-- Spouse(s) --}}
    @foreach($spouses as $spouse)
        @php $spouseYears = $getYears($spouse); @endphp
        <a class="partner st-{{ $spouse->gender }} inline-flex items-center gap-1 cursor-pointer"
           wire:click.prevent="$dispatch('show-member', { id: {{ $spouse->id }} })"
           title="{{ trim($spouse->first_name . ' ' . $spouse->last_name) }}">
            @if($spouses->count() > 1)
                <span class="st-num">#{{ $loop->iteration }}</span>
            @endif
            <span class="st-icon">{{ $spouse->gender === 'female' ? '👩' : '👨' }}</span>
            <strong class="st-name">{{ trim($spouse->first_name . ' ' . $spouse->last_name) }}</strong>
            @if($spouseYears)
                <span class="st-year">({{ $spouseYears }})</span>
            @endif
            @if(isset($spouse->is_current_marriage) && !$spouse->is_current_marriage)
                <span class="st-tag st-tag-divorce">Cerai</span>
            @endif
            @if(!$spouse->is_living)
                <span class="st-tag st-tag-dead">Wafat</span>
            @endif
        </a>
    @endforeach

    {{-- Main Member --}}
    @php $memberYears = $getYears($member); @endphp
    <a class="{{ $spouses->isNotEmpty() ? 'haswife' : '' }} st-{{ $member->gender }} inline-flex items-center gap-1 cursor-pointer"
       wire:click.prevent="$dispatch('show-member', { id: {{ $member->id }} })"
       title="{{ trim($member->first_name . ' ' . $member->last_name) }}">
        <span class="st-icon">{{ $member->gender === 'female' ? '👩' : '👨' }}</span>
        <strong class="st-name">{{ trim($member->first_name . ' ' . $member->last_name) }}</strong>
        @if($memberYears)
            <span class="st-year">({{ $memberYears }})</span>
        @endif
        @if(!$member->is_living)
            <span class="st-tag st-tag-dead">Wafat</span>
        @endif
    </a>

    {{-- Hidden partner placeholder(s) --}}
    @for($i = 0; $i < $spouses->count(); $i++)
        <a class="partner hid"></a>
    @endfor

    {{-- Children --}}
    @if($allChildren->isNotEmpty())
        @if($spouses->count() > 1)
            @php
                $groupKey = $member->gender === 'male' ? 'mother_id' : 'father_id';
                $grouped = $allChildren->groupBy($groupKey);
            @endphp
            <ul>
                @foreach($spouses as $spouse)
                    @php $spouseChildren = $grouped->get($spouse->id, collect()); @endphp
                    @if($spouseChildren->isNotEmpty())
                        <li class="wife-group">
                            <span class="wife-group-label">{{ $spouse->first_name }} @if($spouses->count() > 1) (#{{ $loop->iteration }}) @endif @if(isset($spouse->is_current_marriage) && !$spouse->is_current_marriage) (Cerai) @endif</span>
                            <ul>
                                @foreach($spouseChildren as $child)
                                    <x-simple-tree-node :member="$child" :all-members="$allMembers" />
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach
                @php $unassignedChildren = $grouped->get(null, collect())->merge($grouped->filter(fn($v, $k) => $k && !$spouses->pluck('id')->contains($k))->flatten(1)); @endphp
                @if($unassignedChildren->isNotEmpty())
                    <li class="wife-group">
                        <span class="wife-group-label">Lainnya</span>
                        <ul>
                            @foreach($unassignedChildren as $child)
                                <x-simple-tree-node :member="$child" :all-members="$allMembers" />
                            @endforeach
                        </ul>
                    </li>
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
