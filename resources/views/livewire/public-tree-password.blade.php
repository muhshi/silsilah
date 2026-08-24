<?php

use Livewire\Component;
use App\Models\FamilyTree;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public string $slug;
    public string $treeName = '';
    public string $password = '';

    public function mount(string $slug): void
    {
        $this->slug = $slug;
        $tree = FamilyTree::where('slug', $slug)->firstOrFail();
        $this->treeName = $tree->name;
    }

    public function submit(): mixed
    {
        $this->validate([
            'password' => 'required|string',
        ]);

        $tree = FamilyTree::where('slug', $this->slug)->firstOrFail();
        $stored = $tree->view_password;
        $isHashed = str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$2b$');

        if ($isHashed) {
            $valid = Hash::check($this->password, $stored);
        } else {
            $valid = $this->password === $stored;

            // Auto-upgrade plain text to bcrypt
            if ($valid) {
                $tree->update(['view_password' => Hash::make($stored)]);
            }
        }

        if (! $valid) {
            $this->addError('password', 'Password salah. Silakan coba lagi.');

            return null;
        }

        session(["tree_unlocked_{$tree->id}" => true]);

        return redirect()->route('tree.public', $tree->slug);
    }
};
?>

<div class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-zinc-900 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white dark:bg-zinc-800/90 rounded-2xl shadow-xl border border-slate-200 dark:border-zinc-700/80 p-8 space-y-6 backdrop-blur-md">
            {{-- Header --}}
            <div class="text-center space-y-2">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 mb-1 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Silsilah Dilindungi Password</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Masukkan password untuk mengakses silsilah <strong class="text-slate-800 dark:text-slate-200">{{ $treeName }}</strong></p>
            </div>

            {{-- Form --}}
            <form wire:submit.prevent="submit" class="space-y-4">
                <div>
                    <flux:input
                        type="password"
                        wire:model="password"
                        label="Password"
                        placeholder="Masukkan password silsilah"
                        viewable
                        autofocus
                    />
                    @error('password')
                        <p class="text-xs text-red-500 dark:text-red-400 font-medium mt-1 flex items-center gap-1">
                            <span>⚠️</span> {{ $message }}
                        </p>
                    @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 px-4 rounded-lg shadow-sm transition-all flex justify-center items-center gap-2 cursor-pointer disabled:opacity-70">
                    <span wire:loading.remove wire:target="submit">Buka Silsilah</span>
                    <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Memverifikasi...</span>
                    </span>
                </button>
            </form>

            {{-- Back --}}
            <div class="text-center pt-2">
                <a href="{{ route('home') }}" class="text-xs text-slate-400 dark:text-slate-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                    ← Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>
