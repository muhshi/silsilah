@php
    $childSpouses = $getSpouses($child);
    $grandchildCount = $countDescendants($child);
    $borderColor = $child->gender === 'female' ? 'border-l-pink-400' : 'border-l-teal-400';
    $nameColor = $child->gender === 'female' ? 'text-pink-600 dark:text-pink-400' : 'text-teal-600 dark:text-teal-400';
@endphp

<div class="border-l-4 {{ $borderColor }} bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-lg p-4 shadow-sm">
    <div class="flex items-start gap-3">
        {{-- Photo --}}
        <div class="w-12 h-12 rounded-lg overflow-hidden border-2 border-gray-200 dark:border-zinc-600 flex-shrink-0 cursor-pointer"
             wire:click="$dispatch('show-member', { id: {{ $child->id }} })">
            <img src="{{ $getAvatar($child) }}" class="w-full h-full object-cover" onerror="this.src='https://app.pohonkeluarga.com/images/no_profile_pic.jpg'" />
        </div>

        {{-- Info --}}
        <div class="flex-1 min-w-0">
            {{-- Name (clickable → drill down) --}}
            <button wire:click="focusOn({{ $child->id }})" class="{{ $nameColor }} font-bold text-base hover:underline text-left">
                {{ $child->first_name }} {{ $child->last_name }}
            </button>

            {{-- Birth date --}}
            @if($child->birth_date)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">🎂 {{ \Carbon\Carbon::parse($child->birth_date)->format('d M Y') }}</p>
            @endif

            {{-- Wafat --}}
            @if(!$child->is_living)
                <span class="inline-block text-xs bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 px-1.5 py-0.5 rounded mt-1">Wafat</span>
            @endif

            {{-- Spouse line --}}
            @if($childSpouses->isNotEmpty())
                <div class="mt-2 pt-2 border-t border-gray-100 dark:border-zinc-800">
                    @foreach($childSpouses as $sp)
                        <span class="text-sm text-gray-500 dark:text-gray-400">❤️ {{ $sp->first_name }} {{ $sp->last_name }}</span>
                    @endforeach
                </div>
            @endif

            {{-- Children count badge → clickable --}}
            @if($grandchildCount > 0)
                <div class="mt-2">
                    <button wire:click="focusOn({{ $child->id }})"
                            class="inline-flex items-center gap-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-bold px-2.5 py-1 rounded-full hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                        {{ $grandchildCount }} anak →
                    </button>
                </div>
            @endif

            {{-- Action buttons --}}
            <div class="mt-3 pt-2 border-t border-gray-100 dark:border-zinc-800 flex items-center gap-2 flex-wrap">
                <button wire:click="$dispatch('edit-member', { id: {{ $child->id }} })"
                        class="inline-flex items-center gap-1 text-xs font-medium text-teal-600 dark:text-teal-400 hover:text-teal-700 hover:bg-teal-50 dark:hover:bg-teal-900/20 px-2 py-1 rounded transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                    Edit
                </button>
                <button wire:click="$dispatch('create-member', { targetId: {{ $child->id }}, relType: 'child_of' })"
                        class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 px-2 py-1 rounded transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah
                </button>
                <button wire:click="$dispatch('confirm-delete-member', { id: {{ $child->id }} })"
                        class="inline-flex items-center gap-1 text-xs font-medium text-red-500 dark:text-red-400 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 px-2 py-1 rounded transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
