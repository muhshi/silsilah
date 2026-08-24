<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\FamilyTree;
use App\Models\TreeInvitation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public $treeId;
    public $tree;
    public $name = '';
    public $description = '';
    public $is_public = true;
    public $has_password = false;
    public $new_password = '';
    public $inviteEmail = '';
    public $activeTab = 'general';
    public $showModal = false;

    public function mount($treeId)
    {
        $this->treeId = $treeId;
        $this->loadTreeData();
    }

    public function loadTreeData()
    {
        $this->tree = FamilyTree::findOrFail($this->treeId);
        $this->name = $this->tree->name;
        $this->description = $this->tree->description ?? '';
        $this->is_public = (bool) $this->tree->is_public;
        $this->has_password = !empty($this->tree->view_password);
        $this->new_password = '';
    }

    #[On('open-tree-settings')]
    public function openSettings()
    {
        $this->loadTreeData();
        $this->showModal = true;
    }

    public function saveGeneralSettings()
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $this->tree);

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'has_password' => 'boolean',
            'new_password' => 'nullable|string|min:4',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'is_public' => $this->is_public,
        ];

        if ($this->is_public && $this->has_password) {
            if (!empty($this->new_password)) {
                $data['view_password'] = Hash::make($this->new_password);
            }
        } else {
            $data['view_password'] = null;
            $this->has_password = false;
            $this->new_password = '';
        }

        $this->tree->update($data);
        $this->loadTreeData();

        session()->flash('success_general', 'Pengaturan pohon berhasil disimpan.');
        $this->dispatch('refresh-tree');
    }

    public function deleteTree()
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $this->tree);
        $this->tree->delete();
        session()->flash('success', 'Pohon silsilah berhasil dihapus.');
        return redirect()->route('dashboard');
    }

    public function sendInvite()
    {
        \Illuminate\Support\Facades\Gate::authorize('manageCollaborators', $this->tree);

        $this->validate([
            'inviteEmail' => 'required|email'
        ]);

        // Check if already invited
        $existing = TreeInvitation::where('family_tree_id', $this->treeId)
            ->where('email', $this->inviteEmail)
            ->first();

        if ($existing) {
            session()->flash('error', 'Email tersebut sudah diundang.');
            return;
        }

        // Create Invitation
        $token = Str::random(40);
        $invitation = TreeInvitation::create([
            'family_tree_id' => $this->treeId,
            'email' => $this->inviteEmail,
            'role' => 'editor',
            'token' => $token,
        ]);

        \Illuminate\Support\Facades\Mail::to($this->inviteEmail)->send(new \App\Mail\TreeInvitationMail($invitation, $this->tree));

        $this->inviteEmail = '';
        session()->flash('success', 'Undangan berhasil dikirim.');
    }

    public function cancelInvite($id)
    {
        \Illuminate\Support\Facades\Gate::authorize('manageCollaborators', $this->tree);
        TreeInvitation::where('id', $id)->delete();
        session()->flash('success', 'Undangan dibatalkan.');
    }
    
    public function removeEditor($userId)
    {
        \Illuminate\Support\Facades\Gate::authorize('manageCollaborators', $this->tree);
        $this->tree->users()->detach($userId);
        session()->flash('success', 'Kolaborator dihapus.');
    }

    public function with()
    {
        return [
            'invitations' => TreeInvitation::where('family_tree_id', $this->treeId)->where('status', 'pending')->get(),
            'editors' => $this->tree->users()->where('role', 'editor')->get(),
        ];
    }
};
?>

<div>
    <flux:modal wire:model="showModal" class="md:w-[40rem]">
        <div class="space-y-6">
            <flux:heading size="lg">Pengaturan Pohon Silsilah</flux:heading>

            <div class="flex border-b border-outline-variant/50">
                <button wire:click="$set('activeTab', 'general')" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'general' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant' }}">
                    Umum
                </button>
                <button wire:click="$set('activeTab', 'collab')" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors flex items-center gap-1 {{ $activeTab === 'collab' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant' }}">
                    Kolaborasi
                    @if($tree->is_premium)
                        <span class="material-symbols-outlined text-[14px] {{ $activeTab === 'collab' ? 'text-primary' : 'text-tertiary' }}">workspace_premium</span>
                    @endif
                </button>
            </div>

            @if($activeTab === 'general')
                <div class="space-y-6 pt-4">
                    <form wire:submit="saveGeneralSettings" class="space-y-4">
                        <flux:input wire:model="name" label="Nama Pohon Silsilah *" placeholder="Contoh: Keluarga Trah Mangun" required />
                        <flux:textarea wire:model="description" label="Deskripsi" placeholder="Keterangan singkat silsilah keluarga" rows="2" />

                        {{-- Visibilitas: Publik / Privat --}}
                        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-bold text-sm text-zinc-900 dark:text-white">Akses Pohon Publik</h4>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Siapapun yang memiliki link dapat melihat pohon silsilah ini.</p>
                                </div>
                                <flux:switch wire:model.live="is_public" />
                            </div>

                            @if($is_public)
                                <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700/80 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="font-semibold text-xs text-zinc-800 dark:text-zinc-200">Proteksi dengan Password</h4>
                                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Pengunjung publik wajib memasukkan password sebelum melihat diagram.</p>
                                        </div>
                                        <flux:switch wire:model.live="has_password" />
                                    </div>

                                    @if($has_password)
                                        <div class="pt-1">
                                            <flux:input type="password" wire:model="new_password" label="Password Akses Publik" placeholder="{{ $tree->view_password ? 'Isi jika ingin mengganti password' : 'Masukkan password baru' }}" viewable />
                                            @if($tree->view_password && empty($new_password))
                                                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1">✓ Password proteksi saat ini sudah aktif.</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if (session()->has('success_general'))
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ session('success_general') }}</p>
                        @endif

                        <div class="flex justify-end pt-2">
                            <flux:button type="submit" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 font-medium">Simpan Perubahan</flux:button>
                        </div>
                    </form>

                    <div class="mt-6 p-4 border border-red-200 bg-red-50 dark:border-red-900/30 dark:bg-red-900/10 rounded-xl">
                        <h4 class="font-bold text-red-600 dark:text-red-400 mb-1">Zona Berbahaya</h4>
                        <p class="text-xs text-red-500 mb-3">Tindakan ini akan menghapus seluruh data pohon, anggota keluarga, dan foto-fotonya secara permanen. Tindakan ini tidak dapat dibatalkan.</p>
                        <flux:modal.trigger name="confirm-delete-tree">
                            <flux:button variant="danger" size="sm">Hapus Pohon</flux:button>
                        </flux:modal.trigger>
                    </div>
                </div>
            @endif

            @if($activeTab === 'collab')
                <div class="space-y-6 pt-4">
                    <!-- Collaboration Settings -->
                    <div>
                        <h4 class="font-bold text-on-surface mb-2">Undang Kolaborator (Editor)</h4>
                        <p class="text-xs text-on-surface-variant mb-4">Mereka dapat menambah, mengedit, atau menghapus anggota di pohon ini.</p>
                        
                        <form wire:submit="sendInvite" class="flex gap-2">
                            <div class="flex-1">
                                <flux:input wire:model="inviteEmail" type="email" placeholder="email@contoh.com" required />
                            </div>
                            <flux:button type="submit" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 font-medium">Undang</flux:button>
                        </form>
                        @if (session()->has('error'))
                            <p class="text-xs text-red-500 mt-1">{{ session('error') }}</p>
                        @endif
                        @if (session()->has('success'))
                            <p class="text-xs text-emerald-600 font-medium mt-1">{{ session('success') }}</p>
                        @endif
                    </div>

                    <!-- Pending Invitations -->
                    @if($invitations->count() > 0)
                    <div>
                        <h4 class="font-bold text-sm text-on-surface mb-2">Undangan Menunggu</h4>
                        <ul class="space-y-2 border border-outline-variant/30 rounded-lg divide-y divide-outline-variant/30">
                            @foreach($invitations as $inv)
                            <li class="p-3 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium">{{ $inv->email }}</p>
                                    <p class="text-[10px] text-primary/70 break-all select-all">Link: {{ url('/invitations/accept/' . $inv->token) }}</p>
                                </div>
                                <button wire:click="cancelInvite({{ $inv->id }})" class="text-xs text-red-500 hover:underline">Batal</button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <!-- Current Editors -->
                    @if($editors->count() > 0)
                    <div>
                        <h4 class="font-bold text-sm text-on-surface mb-2">Editor Aktif</h4>
                        <ul class="space-y-2 border border-outline-variant/30 rounded-lg divide-y divide-outline-variant/30">
                            @foreach($editors as $ed)
                            <li class="p-3 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $ed->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($ed->name) }}" class="w-8 h-8 rounded-full">
                                    <div>
                                        <p class="text-sm font-medium">{{ $ed->name }}</p>
                                        <p class="text-xs text-on-surface-variant">{{ $ed->email }}</p>
                                    </div>
                                </div>
                                <button wire:click="removeEditor({{ $ed->id }})" class="text-xs text-red-500 hover:underline">Hapus</button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            @endif

        </div>
    </flux:modal>

    <!-- Confirm Delete Modal -->
    <flux:modal name="confirm-delete-tree" class="md:w-[24rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-600">Hapus Pohon?</flux:heading>
                <flux:subheading>Apakah Anda yakin ingin menghapus pohon ini secara permanen?</flux:subheading>
            </div>
            
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button wire:click="deleteTree" variant="danger">Ya, Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>