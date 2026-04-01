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

<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8 space-y-6">
            {{-- Header --}}
            <div class="text-center space-y-2">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-indigo-50 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900">Silsilah Dilindungi Password</h1>
                <p class="text-sm text-gray-500">Masukkan password untuk melihat silsilah <strong class="text-gray-700">{{ $treeName }}</strong></p>
            </div>

            {{-- Form --}}
            <form wire:submit="submit" class="space-y-4">
                <flux:input
                    type="password"
                    wire:model="password"
                    label="Password"
                    placeholder="Masukkan password silsilah"
                    autofocus
                />

                <flux:button type="submit" variant="primary" class="w-full">
                    Buka Silsilah
                </flux:button>
            </form>

            {{-- Back --}}
            <div class="text-center">
                <a href="{{ route('home') }}" class="text-sm text-gray-400 hover:text-indigo-600 transition-colors">
                    ← Kembali ke beranda
                </a>
            </div>
        </div>
    </div>
</div>
