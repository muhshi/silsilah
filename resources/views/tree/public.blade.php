<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tree->name }} — Silsilah Keluarga</title>
    <meta name="description" content="Silsilah keluarga {{ $tree->name }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $tree->name }}</h1>
                @if($tree->description)
                    <p class="text-sm text-gray-500">{{ $tree->description }}</p>
                @endif
            </div>
            <a href="{{ route('home') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">Buat Silsilahmu →</a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-6" x-data="{ focusId: null, breadcrumbs: [] }">
        @php
            $allMembers = $tree->members()->with(['marriagesAsHusband.wife', 'marriagesAsWife.husband'])->get();

            $wifeIds = collect();
            foreach ($allMembers->where('gender', 'male') as $m) {
                foreach ($m->marriagesAsHusband as $marriage) {
                    $wifeIds->push($marriage->wife_id);
                }
            }
            $parentless = $allMembers->whereNull('father_id')->whereNull('mother_id');
            $rootMembers = $parentless->whereNotIn('id', $wifeIds->unique());

            $getAvatar = function($m) {
                if ($m->photo) {
                    return str_starts_with($m->photo, 'http') ? $m->photo : asset('storage/' . $m->photo);
                }
                return $m->avatar_id
                    ? 'https://app.pohonkeluarga.com/images/avatar/' . $m->avatar_id . '.jpg'
                    : 'https://app.pohonkeluarga.com/images/no_profile_pic.jpg';
            };

            // Build JSON-friendly data for Alpine.js
            $membersData = $allMembers->map(function($m) use ($getAvatar, $allMembers) {
                $spouses = collect();
                if ($m->gender === 'male' && $m->relationLoaded('marriagesAsHusband')) {
                    $spouseIds = $m->marriagesAsHusband->pluck('wife_id');
                    $spouses = $allMembers->whereIn('id', $spouseIds);
                } elseif ($m->gender === 'female' && $m->relationLoaded('marriagesAsWife')) {
                    $spouseIds = $m->marriagesAsWife->pluck('husband_id');
                    $spouses = $allMembers->whereIn('id', $spouseIds);
                }

                $childFilter = $m->gender === 'male' ? 'father_id' : 'mother_id';
                $children = $allMembers->where($childFilter, $m->id)
                    ->sortBy(fn($c) => [$c->birth_date ? strtotime($c->birth_date) : PHP_INT_MAX, $c->order ?? 999, $c->id])
                    ->values();

                return [
                    'id' => $m->id,
                    'first_name' => $m->first_name,
                    'last_name' => $m->last_name ?? '',
                    'gender' => $m->gender,
                    'is_living' => $m->is_living,
                    'birth_date' => $m->birth_date,
                    'avatar' => $getAvatar($m),
                    'father_id' => $m->father_id,
                    'mother_id' => $m->mother_id,
                    'spouse_names' => $spouses->map(fn($s) => $s->first_name)->join(', '),
                    'children_ids' => $children->pluck('id')->toArray(),
                    'children_count' => $children->count(),
                ];
            })->keyBy('id');
        @endphp

        {{-- Render full tree server-side as static vertical list --}}
        @foreach($rootMembers as $member)
            @php
                $spouses = collect();
                if ($member->gender === 'male' && $member->relationLoaded('marriagesAsHusband')) {
                    $spouses = $allMembers->whereIn('id', $member->marriagesAsHusband->pluck('wife_id'));
                }
                $childFilter = $member->gender === 'male' ? 'father_id' : 'mother_id';
                $children = $allMembers->where($childFilter, $member->id)
                    ->sortBy(fn($c) => [$c->birth_date ? strtotime($c->birth_date) : PHP_INT_MAX, $c->order ?? 999, $c->id]);
            @endphp

            {{-- Root parent card --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm mb-4">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-3">
                        <img src="{{ $getAvatar($member) }}" class="w-16 h-16 rounded-full object-cover border-3 {{ $member->gender === 'female' ? 'border-pink-400' : 'border-teal-400' }} shadow" onerror="this.src='https://app.pohonkeluarga.com/images/no_profile_pic.jpg'" />
                        <div>
                            <strong class="text-lg {{ $member->gender === 'female' ? 'text-pink-600' : 'text-teal-600' }}">{{ $member->first_name }} {{ $member->last_name }}</strong>
                            @if($member->birth_date)
                                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($member->birth_date)->format('d M Y') }}</p>
                            @endif
                        </div>
                    </div>
                    @foreach($spouses as $spouse)
                        <span class="text-lg">❤️</span>
                        <div class="flex items-center gap-3">
                            <img src="{{ $getAvatar($spouse) }}" class="w-14 h-14 rounded-full object-cover border-2 border-pink-300 shadow" onerror="this.src='https://app.pohonkeluarga.com/images/no_profile_pic.jpg'" />
                            <strong class="text-base text-pink-600">{{ $spouse->first_name }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Children --}}
            <div class="space-y-2 mb-6">
                @foreach($children as $child)
                    @php
                        $cSpouses = collect();
                        if ($child->gender === 'male' && $child->relationLoaded('marriagesAsHusband')) {
                            $cSpouses = $allMembers->whereIn('id', $child->marriagesAsHusband->pluck('wife_id'));
                        } elseif ($child->gender === 'female' && $child->relationLoaded('marriagesAsWife')) {
                            $cSpouses = $allMembers->whereIn('id', $child->marriagesAsWife->pluck('husband_id'));
                        }
                        $cFilter = $child->gender === 'male' ? 'father_id' : 'mother_id';
                        $grandchildren = $allMembers->where($cFilter, $child->id)->count();
                    @endphp
                    <div class="border-l-4 {{ $child->gender === 'female' ? 'border-l-pink-400' : 'border-l-teal-400' }} bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <img src="{{ $getAvatar($child) }}" class="w-12 h-12 rounded-lg object-cover border-2 border-gray-200 flex-shrink-0" onerror="this.src='https://app.pohonkeluarga.com/images/no_profile_pic.jpg'" />
                            <div class="flex-1">
                                <strong class="text-base {{ $child->gender === 'female' ? 'text-pink-600' : 'text-teal-600' }}">{{ $child->first_name }} {{ $child->last_name }}</strong>
                                @if($child->birth_date)
                                    <p class="text-xs text-gray-500 mt-0.5">🎂 {{ \Carbon\Carbon::parse($child->birth_date)->format('d M Y') }}</p>
                                @endif
                                @if(!$child->is_living)
                                    <span class="inline-block text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded mt-1">Wafat</span>
                                @endif
                                @if($cSpouses->isNotEmpty())
                                    <p class="text-sm text-gray-500 mt-2 pt-2 border-t border-gray-100">❤️ {{ $cSpouses->pluck('first_name')->join(', ') }}</p>
                                @endif
                                @if($grandchildren > 0)
                                    <span class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-600 text-xs font-bold px-2.5 py-1 rounded-full mt-2">{{ $grandchildren }} anak</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </main>

    <footer class="text-center py-6 text-xs text-gray-400">
        Dibuat dengan <a href="{{ route('home') }}" class="text-indigo-500 hover:underline">Silsilah Keluarga</a>
    </footer>
</body>
</html>
