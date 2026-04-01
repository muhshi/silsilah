<?php

use Livewire\Component;
use App\Models\FamilyTree;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public $showModal = false;
    public $name = '';
    public $description = '';
    public $is_public = false;
    public $view_password = '';

    public function createTree()
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $tree = FamilyTree::create([
            'name' => $this->name,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'view_password' => $this->view_password ? Hash::make($this->view_password) : null,
        ]);

        $tree->users()->attach(auth()->id(), ['role' => 'owner']);

        return redirect()->route('tree.show', $tree->id);
    }

    public function with()
    {
        return [
            'trees' => auth()->user()->familyTrees()->withCount('members')->latest()->get(),
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold dark:text-white">Silsilah Keluargaku</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Daftar Silsilah yang Anda kelola!</p>
        </div>
        
        <flux:modal.trigger name="create-tree-modal">
            <flux:button variant="primary" icon="plus">Buat Silsilah</flux:button>
        </flux:modal.trigger>
    </div>

    @if($trees->isEmpty())
        <div class="flex flex-col items-center justify-center p-12 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
            <div class="flex items-center justify-center w-12 h-12 mb-4 rounded-full bg-indigo-50 dark:bg-indigo-900/20">
                <flux:icon.users class="w-6 h-6 text-indigo-500" />
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Belum ada Silsilah</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mulai dengan membuat silsilah keluarga pertama Anda.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($trees as $tree)
                <flux:card class="relative flex flex-col transition hover:border-indigo-500 group">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium dark:text-white">
                                <a href="{{ route('tree.show', $tree->id) }}" class="focus:outline-none">
                                    <span class="absolute inset-0" aria-hidden="true"></span>
                                    {{ $tree->name }}
                                </a>
                            </h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Dikelola sejak {{ $tree->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-50 dark:bg-gray-800 text-gray-500">
                            <flux:icon.users class="w-5 h-5" />
                        </div>
                    </div>
                    
                    <div class="mt-4 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                        <div class="flex items-center gap-1.5 pl-3 border-l-2 border-indigo-500">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $tree->members_count }}</span>
                            <span>Anggota</span>
                        </div>
                        @if($tree->is_public)
                            <flux:badge size="sm" color="green" class="ml-auto">Publik</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc" class="ml-auto">Privat</flux:badge>
                        @endif
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif

    <flux:modal name="create-tree-modal" class="md:w-[32rem]">
        <form wire:submit="createTree" class="space-y-6">
            <div>
                <flux:heading size="lg">Buat Silsilah Keluarga</flux:heading>
                <flux:subheading>Masukkan detail awal untuk silsilah ini.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="name" label="Nama Keluarga" placeholder="Misal: Keluarga Ahmad Jamil" required />
                
                <flux:textarea wire:model="description" label="Deskripsi (Opsional)" placeholder="Keterangan singkat tentang keluarga ini" />
                
                <flux:switch wire:model="is_public" label="Publik" description="Silsilah dapat dilihat siapa saja (dengan URL)" />

                <flux:input type="password" wire:model="view_password" label="Password Silsilah (Opsional)" placeholder="Password untuk memproteksi tampilan publik" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan & Mulai</flux:button>
            </div>
        </form>
    </flux:modal>
</div>