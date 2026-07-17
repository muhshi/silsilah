<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\FamilyTree;
use App\Models\TreeInvitation;
use Illuminate\Support\Str;

new class extends Component
{
    public $treeId;
    public $tree;
    public $inviteEmail = '';
    public $activeTab = 'general';
    public $showModal = false;

    public function mount($treeId)
    {
        $this->treeId = $treeId;
        $this->tree = FamilyTree::findOrFail($treeId);
    }

    #[On('open-tree-settings')]
    public function openSettings()
    {
        $this->showModal = true;
    }

    public function sendInvite()
    {
        if (!$this->tree->is_premium) {
            session()->flash('error', 'Fitur kolaborasi hanya tersedia untuk Pohon Premium.');
            return;
        }

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
        
        // Refresh tree relation if needed, or simply re-fetch
    }

    public function cancelInvite($id)
    {
        TreeInvitation::where('id', $id)->delete();
        session()->flash('success', 'Undangan dibatalkan.');
    }
    
    public function removeEditor($userId)
    {
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
                <div class="space-y-4 pt-4">
                    <p class="text-sm text-on-surface-variant">
                        Untuk sementara, pengaturan umum seperti nama dan visibilitas dikelola saat pembuatan pohon.
                        Anda dapat memperbaruinya di masa mendatang.
                    </p>
                </div>
            @endif

            @if($activeTab === 'collab')
                <div class="space-y-6 pt-4">
                    @if(!$tree->is_premium)
                        <div class="bg-tertiary/10 border border-tertiary/20 p-4 rounded-xl flex items-start gap-4">
                            <span class="material-symbols-outlined text-tertiary mt-0.5">workspace_premium</span>
                            <div>
                                <h4 class="font-bold text-tertiary mb-1">Fitur Premium</h4>
                                <p class="text-sm text-on-surface-variant mb-3">Kolaborasi bersama anggota keluarga lain hanya tersedia untuk Pohon Premium.</p>
                                <button onclick="window.location='{{ route('dashboard') }}'" class="text-xs bg-tertiary text-white px-3 py-1.5 rounded-full font-bold">Upgrade ke Premium</button>
                            </div>
                        </div>
                    @else
                        <!-- Premium Collaboration Settings -->
                        <div>
                            <h4 class="font-bold text-on-surface mb-2">Undang Kolaborator (Editor)</h4>
                            <p class="text-xs text-on-surface-variant mb-4">Mereka dapat menambah, mengedit, atau menghapus anggota di pohon ini.</p>
                            
                            <form wire:submit="sendInvite" class="flex gap-2">
                                <div class="flex-1">
                                    <flux:input wire:model="inviteEmail" type="email" placeholder="email@contoh.com" required />
                                </div>
                                <flux:button type="submit" variant="primary">Undang</flux:button>
                            </form>
                            @if (session()->has('error'))
                                <p class="text-xs text-red-500 mt-1">{{ session('error') }}</p>
                            @endif
                            @if (session()->has('success'))
                                <p class="text-xs text-green-600 mt-1">{{ session('success') }}</p>
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
                        
                    @endif
                </div>
            @endif

        </div>
    </flux:modal>
</div>