<?php

use Livewire\Component;
use App\Models\FamilyTree;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
        $trees = auth()->user()->familyTrees()->withCount('members')->latest()->get();
        // Calculate total members across all trees user owns
        $totalMembers = $trees->sum('members_count');
        $totalTrees = $trees->count();
        
        return [
            'trees' => $trees,
            'totalMembers' => $totalMembers,
            'totalTrees' => $totalTrees,
        ];
    }

    public function upgradeToPremium($treeId)
    {
        $tree = auth()->user()->familyTrees()->findOrFail($treeId);
        $tree->update(['is_premium' => true]);
        
        session()->flash('success', 'Berhasil! Pohon ini sekarang adalah Pohon Premium.');
        return redirect()->route('dashboard');
    }
};
?>

<div class="space-y-6">
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-xl flex items-center gap-3 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400">check_circle</span>
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-xl flex items-center gap-3 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300">
            <span class="material-symbols-outlined text-red-600 dark:text-red-400">error</span>
            <span class="font-medium text-sm">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
        <div class="max-w-xl">
            <span class="text-primary font-headline font-bold tracking-widest text-xs uppercase mb-2 block">The Living Heritage</span>
            <h1 class="text-5xl font-headline font-extrabold text-on-surface mb-4 leading-tight">Daftar Silsilah</h1>
            <p class="text-on-surface-variant text-lg leading-relaxed">Kelola dan telusuri akar sejarah keluarga Anda. Setiap nama adalah sebuah cerita yang layak untuk diabadikan.</p>
        </div>
        <flux:modal.trigger name="create-tree-modal">
            <button class="flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-[#86A789] text-on-primary px-8 py-4 rounded-full font-bold shadow-lg shadow-primary/20 hover:scale-105 active:scale-95 transition-all w-full md:w-auto">
                <span class="material-symbols-outlined">add</span>
                Buat Silsilah
            </button>
        </flux:modal.trigger>
    </div>

    <!-- Bento Grid / Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        @foreach($trees as $tree)
            <!-- Family Card -->
            <div wire:key="tree-{{ $tree->id }}" onclick="window.location='{{ route('tree.show', $tree->id) }}'" class="group bg-gradient-to-br from-[#F4F7F4] to-white border border-outline-variant/40 leaf-shape p-8 shadow-sm transition-all hover:shadow-xl hover:shadow-primary/10 hover:border-primary/40 cursor-pointer relative overflow-hidden dark:from-zinc-800/80 dark:to-zinc-800 dark:border-zinc-700">
                <div class="flex items-start justify-between mb-8">
                    <div class="w-16 h-16 bg-primary-container/80 rounded-lg flex items-center justify-center text-primary shadow-inner">
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 1;">eco</span>
                    </div>
                    
                    <div class="flex flex-col items-end gap-1.5">
                        @if($tree->is_public)
                            <div class="bg-surface-container-highest/60 px-3 py-1 rounded-full text-[10px] font-bold text-on-surface-variant flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                PUBLIK
                            </div>
                        @else
                            <div class="bg-surface-container-highest/60 px-3 py-1 rounded-full text-[10px] font-bold text-on-surface-variant flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-outline"></span>
                                PRIVAT
                            </div>
                        @endif
                        
                        @if($tree->is_premium)
                            <div class="bg-tertiary/15 px-3 py-1 rounded-full text-[10px] font-bold text-tertiary flex items-center gap-1">
                                <span class="material-symbols-outlined text-[12px]">workspace_premium</span>
                                PREMIUM
                            </div>
                        @endif
                    </div>
                </div>
                
                <h3 class="text-2xl font-headline font-bold text-on-surface mb-2">{{ $tree->name }}</h3>
                <div class="flex items-center gap-4 text-on-surface-variant text-sm mb-6">
                    <span class="flex items-center gap-1 whitespace-nowrap">
                        <span class="material-symbols-outlined text-base">group</span>
                        {{ $tree->members_count }} members
                    </span>
                    <span class="flex items-center gap-1 whitespace-nowrap">
                        <span class="material-symbols-outlined text-base">schedule</span>
                        {{ $tree->created_at->diffForHumans() }}
                    </span>
                </div>
                
                @if($tree->members_count > 0)
                    <div class="flex -space-x-3 mb-8 h-10">
                        <div class="w-10 h-10 rounded-full border-4 border-surface-container-low bg-primary text-white flex items-center justify-center text-xs font-bold">{{ $tree->members_count }}</div>
                    </div>
                @else
                    <div class="mb-8 h-10 flex items-center">
                        <p class="text-xs text-outline italic">Belum ada anggota yang ditambahkan...</p>
                    </div>
                @endif
                
                
                <div class="flex flex-col gap-2 relative z-10">
                    <button onclick="event.stopPropagation(); window.location='{{ route('tree.show', $tree->id) }}'" class="w-full py-3 rounded-full bg-primary/10 text-primary font-bold hover:bg-primary hover:text-white transition-all">
                        Lihat Silsilah
                    </button>
                    
                    @if(!$tree->is_premium)
                        <button wire:click.stop="upgradeToPremium({{ $tree->id }})" class="w-full py-2 rounded-full border border-tertiary text-tertiary text-sm font-bold hover:bg-tertiary hover:text-white transition-all">
                            Upgrade Premium (Dummy)
                        </button>
                    @endif
                </div>
                
                <!-- Decorative background shape -->
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-primary/5 rounded-full blur-2xl group-hover:bg-primary/10 transition-colors"></div>
            </div>
        @endforeach

        <!-- Placeholder Card -->
        <flux:modal.trigger name="create-tree-modal">
            <div class="group border-4 border-dashed border-outline-variant/30 leaf-shape p-8 flex flex-col items-center justify-center text-center gap-4 hover:border-primary/50 hover:bg-primary/5 transition-all cursor-pointer h-full min-h-[300px]">
                <div class="w-16 h-16 rounded-full bg-surface-container-highest flex items-center justify-center text-outline-variant group-hover:scale-110 group-hover:bg-primary/20 group-hover:text-primary transition-all duration-300">
                    <span class="material-symbols-outlined text-3xl">add_circle</span>
                </div>
                <div>
                    <h3 class="text-xl font-headline font-bold text-on-surface">Start a New Branch</h3>
                    <p class="text-sm text-on-surface-variant mt-1">Tambah silsilah keluarga baru</p>
                </div>
            </div>
        </flux:modal.trigger>
    </div>

    <!-- Descriptive Asymmetric Section -->
    <section class="mt-20 grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
        <div class="order-2 md:order-1 relative">
            <div class="absolute -top-12 -left-12 w-64 h-64 bg-tertiary-container/20 rounded-full blur-3xl -z-10"></div>
            <img class="w-full h-80 object-cover leaf-shape shadow-2xl" alt="Momen Keluarga" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCMdSqwe1gMC3DhtrdLpwz_D0rKJJR5G8-EnTxN0ud5ZVS0WJP0mQCWkU8SScOavoMeQaqBapiiVZR6lPYXHQYmBV8mE81ZdHkua-nh3OoByKG9aHek5-xS7hhtnxvNwjegfmEJo64v_UWAAHB1jHKnnUdcjn5pMm7oBj9LVMP5plBtwRbJ8wB8o_b6F9Y2mMI1tSh3uDp5o2oB-guyq2VB44Q2d10SwgIpsIXl7v04J6dMw5KxPrrdhTn1rPk_e1ZeYjqpNVyOTIw"/>
        </div>
        <div class="order-1 md:order-2">
            <h2 class="text-3xl font-headline font-bold text-on-surface mb-6 italic">"Setiap keluarga memiliki akar yang dalam, namun setiap dahan tumbuh ke arah yang berbeda."</h2>
            <p class="text-on-surface-variant leading-relaxed mb-8">Gunakan platform Silsilah untuk mendokumentasikan setiap momen penting. Dari foto lama hingga cerita yang hampir terlupakan, biarkan warisan Anda tetap hidup untuk generasi mendatang.</p>
            
            <div class="flex gap-4">
                <div class="flex flex-col">
                    <span class="text-4xl font-headline font-extrabold text-primary">{{ $totalTrees }}</span>
                    <span class="text-xs font-bold text-on-surface-variant tracking-widest uppercase">Pohon Dibuat</span>
                </div>
                <div class="w-px h-12 bg-outline-variant/20 mx-4"></div>
                <div class="flex flex-col">
                    <span class="text-4xl font-headline font-extrabold text-tertiary">{{ $totalMembers }}</span>
                    <span class="text-xs font-bold text-on-surface-variant tracking-widest uppercase">Anggota Terdata</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Form for Create Tree -->
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