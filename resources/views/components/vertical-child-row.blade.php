@php
    $childSpouses = $getSpouses($child);
    $grandchildCount = $countDescendants($child);
    $borderColor = $child->gender === 'female' ? 'border-l-pink-500' : 'border-l-teal-500';
    $nameColor = $child->gender === 'female' ? 'text-pink-600 dark:text-pink-400' : 'text-teal-600 dark:text-teal-400';
@endphp

<div class="border-l-4 {{ $borderColor }} bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-3.5 shadow-xs hover:shadow-md transition-shadow">
    <div class="flex items-start gap-3">
        {{-- Photo --}}
        <div class="w-12 h-12 rounded-full overflow-hidden border-2 {{ $child->gender === 'female' ? 'border-pink-400' : 'border-teal-400' }} shrink-0 cursor-pointer bg-zinc-50 dark:bg-zinc-800"
             wire:click="$dispatch('show-member', { id: {{ $child->id }} })"
             title="Lihat Profil">
            <img src="{{ $getAvatar($child) }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/no_profile_pic.jpg') }}'" />
        </div>

        {{-- Info --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
                {{-- Name (clickable → drill down) --}}
                <button wire:click="focusOn({{ $child->id }})" class="{{ $nameColor }} font-bold text-sm sm:text-base hover:underline text-left truncate flex items-center gap-1.5">
                    <span>{{ $child->first_name }} {{ $child->last_name }}</span>
                    @if(!$child->is_living)
                        <span class="text-[10px] bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400 font-bold px-1.5 py-0.2 rounded shrink-0">Wafat</span>
                    @endif
                </button>

                {{-- Focus Shortcut Button --}}
                <button wire:click="focusOn({{ $child->id }})"
                        class="p-1.5 bg-zinc-100 dark:bg-zinc-800 hover:bg-emerald-100 dark:hover:bg-emerald-950/50 text-zinc-700 dark:text-zinc-300 hover:text-emerald-600 rounded-lg text-xs font-bold shrink-0 transition-colors"
                        title="Fokus ke {{ $child->first_name }}">
                    ➔
                </button>
            </div>

            {{-- Profession / Birth date --}}
            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 space-y-0.5">
                @if($child->profession)
                    <p class="truncate font-medium text-zinc-600 dark:text-zinc-300">{{ $child->profession }}</p>
                @endif
                @if($child->birth_date)
                    <p class="text-[11px]">🎂 {{ \Carbon\Carbon::parse($child->birth_date)->format('d M Y') }}</p>
                @endif
            </div>

            {{-- Spouse line --}}
            @if($childSpouses->isNotEmpty())
                <div class="mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800 flex flex-wrap gap-2">
                    @foreach($childSpouses as $sp)
                        <div class="inline-flex items-center gap-1 text-xs text-zinc-600 dark:text-zinc-400">
                            <span class="text-pink-500">❤️</span>
                            <span class="font-medium">{{ $sp->first_name }} {{ $sp->last_name }}</span>
                            @if(isset($sp->is_current_marriage) && !$sp->is_current_marriage)
                                <span class="text-[10px] bg-zinc-100 dark:bg-zinc-800 text-zinc-600 px-1 py-0.2 rounded">(Cerai)</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Children count badge → clickable --}}
            @if($grandchildCount > 0)
                <div class="mt-2">
                    <button wire:click="focusOn({{ $child->id }})"
                            class="inline-flex items-center gap-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 text-xs font-bold px-2.5 py-1 rounded-full hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors">
                        <span>👶</span>
                        <span>{{ $grandchildCount }} anak</span>
                        <span>➔</span>
                    </button>
                </div>
            @endif

            {{-- Action buttons --}}
            <div class="mt-3 pt-2 border-t border-zinc-100 dark:border-zinc-800 flex items-center gap-2 flex-wrap">
                <button wire:click="$dispatch('show-member', { id: {{ $child->id }} })"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 hover:bg-zinc-100 dark:hover:bg-zinc-800 px-2 py-1 rounded transition-colors">
                    <flux:icon name="eye" class="size-3.5" />
                    Profil
                </button>
                <button wire:click="$dispatch('create-member', { targetId: {{ $child->id }}, relType: 'child_of' })"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 px-2 py-1 rounded transition-colors">
                    <flux:icon name="plus" class="size-3.5" />
                    Tambah Anak
                </button>
                <button wire:click="$dispatch('edit-member', { id: {{ $child->id }} })"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-teal-600 dark:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-950/30 px-2 py-1 rounded transition-colors">
                    <flux:icon name="pencil" class="size-3.5" />
                    Edit
                </button>
                <button wire:click="$dispatch('confirm-delete-member', { id: {{ $child->id }} })"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 px-2 py-1 rounded transition-colors ml-auto">
                    <flux:icon name="trash" class="size-3.5" />
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
