<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\FamilyTree;
use App\Models\TreeInvitation;
use App\Models\ActivityLog;
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

        \App\Models\ActivityLog::log(
            $this->treeId,
            'settings_updated',
            "Memperbarui pengaturan umum pohon silsilah"
        );

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

        \App\Models\ActivityLog::log(
            $this->treeId,
            'invitation_sent',
            "Mengirim undangan kolaborasi ke '{$this->inviteEmail}'"
        );

        \Illuminate\Support\Facades\Mail::to($this->inviteEmail)->send(new \App\Mail\TreeInvitationMail($invitation, $this->tree));

        $this->inviteEmail = '';
        session()->flash('success', 'Undangan berhasil dikirim.');
    }

    public function createLinkInvite()
    {
        \Illuminate\Support\Facades\Gate::authorize('manageCollaborators', $this->tree);

        $token = Str::random(40);
        $invitation = TreeInvitation::create([
            'family_tree_id' => $this->treeId,
            'email' => null,
            'role' => 'editor',
            'token' => $token,
        ]);

        ActivityLog::log(
            $this->treeId,
            'invitation_sent',
            "Membuat tautan undangan kolaborasi umum"
        );

        session()->flash('success', 'Link undangan kolaborasi berhasil dibuat.');
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
        $user = \App\Models\User::find($userId);
        $name = $user ? $user->name : 'Kolaborator';
        $this->tree->users()->detach($userId);

        ActivityLog::log(
            $this->treeId,
            'collaborator_removed',
            "Menghapus kolaborator '{$name}'"
        );

        session()->flash('success', 'Kolaborator dihapus.');
    }

    public function with()
    {
        return [
            'invitations' => TreeInvitation::where('family_tree_id', $this->treeId)->where('status', 'pending')->get(),
            'editors' => $this->tree->users()->where('role', 'editor')->get(),
            'logs' => ActivityLog::with('user')
                ->where('family_tree_id', $this->treeId)
                ->latest()
                ->take(50)
                ->get(),
        ];
    }
};
?>

<div>
    <flux:modal wire:model="showModal" class="md:w-[42rem]">
        <div class="space-y-6">
            <flux:heading size="lg">Pengaturan Pohon Silsilah</flux:heading>

            <div class="flex border-b border-outline-variant/50">
                <button wire:click="$set('activeTab', 'general')" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'general' ? 'border-primary text-primary font-semibold' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    Umum
                </button>
                <button wire:click="$set('activeTab', 'collab')" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors flex items-center gap-1 {{ $activeTab === 'collab' ? 'border-primary text-primary font-semibold' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    Kolaborasi
                </button>
                <button wire:click="$set('activeTab', 'logs')" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors flex items-center gap-1.5 {{ $activeTab === 'logs' ? 'border-primary text-primary font-semibold' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    <span>Riwayat Aktivitas</span>
                    <span class="text-xs bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 px-1.5 py-0.5 rounded-full font-mono">{{ $logs->count() }}</span>
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
                    <div class="space-y-3">
                        <div>
                            <h4 class="font-bold text-sm text-zinc-900 dark:text-white mb-1">Undang Kolaborator (Editor)</h4>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Kolaborator dapat menambah, mengedit, atau menghapus anggota di silsilah ini.</p>

                            <form wire:submit="sendInvite" class="flex gap-2">
                                <div class="flex-1">
                                    <flux:input wire:model="inviteEmail" type="email" placeholder="Masukkan email calon editor" />
                                </div>
                                <flux:button type="submit" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 font-medium">Kirim Email</flux:button>
                            </form>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <span class="text-xs text-zinc-400">atau</span>
                            <button type="button" wire:click="createLinkInvite" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline inline-flex items-center gap-1 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                <span>+ Buat Link Undangan Langsung</span>
                            </button>
                        </div>

                        @if (session()->has('error'))
                            <p class="text-xs text-red-500 font-medium mt-1">{{ session('error') }}</p>
                        @endif
                        @if (session()->has('success'))
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mt-1">{{ session('success') }}</p>
                        @endif
                    </div>

                    <!-- Pending Invitations -->
                    @if($invitations->count() > 0)
                    <div>
                        <h4 class="font-bold text-sm text-zinc-900 dark:text-white mb-2">Undangan Menunggu</h4>
                        <ul class="space-y-2 border border-zinc-200 dark:border-zinc-700/60 rounded-xl divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($invitations as $inv)
                                @php
                                    $inviteUrl = url('/invitations/accept/' . $inv->token);
                                    $inviteMsg = "Halo! Kamu diundang untuk berkolaborasi mengelola pohon silsilah keluarga \"{$tree->name}\" di Silsilah.\n\nKlik tautan berikut untuk bergabung sebagai Editor:\n{$inviteUrl}";
                                @endphp
                                <li class="p-3 flex items-center justify-between gap-3" x-data="{ msgCopied: false }">
                                    <div class="min-w-0 flex-1">
                                        @if($inv->email)
                                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ $inv->email }}</p>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                                🔗 Link Undangan Langsung
                                            </span>
                                        @endif
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 break-all select-all font-mono mt-1">{{ $inviteUrl }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <button type="button"
                                                x-on:click="navigator.clipboard.writeText({{ json_encode($inviteMsg) }}); msgCopied = true; setTimeout(() => msgCopied = false, 2500)"
                                                class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-medium flex items-center gap-1 cursor-pointer">
                                            <span x-show="!msgCopied">Salin Pesan Undangan</span>
                                            <span x-show="msgCopied" class="text-emerald-600 dark:text-emerald-400 font-bold">Pesan Tersalin!</span>
                                        </button>
                                        <span class="text-zinc-300 dark:text-zinc-700">•</span>
                                        <button wire:click="cancelInvite({{ $inv->id }})" class="text-xs text-red-500 hover:underline cursor-pointer">Batal</button>
                                    </div>
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

            @if($activeTab === 'logs')
                <div class="space-y-4 pt-4">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-sm text-zinc-900 dark:text-white">Riwayat Perubahan Silsilah</h4>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">50 Aktivitas Terakhir</span>
                    </div>

                    @if($logs->isEmpty())
                        <div class="text-center py-10 text-zinc-400 dark:text-zinc-500 text-xs border border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl">
                            Belum ada catatan aktivitas di pohon silsilah ini.
                        </div>
                    @else
                        <div class="space-y-2.5 max-h-[22rem] overflow-y-auto pr-1">
                            @foreach($logs as $log)
                                <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl border border-zinc-200/60 dark:border-zinc-700/50 flex items-start gap-3 text-xs">
                                    <img src="{{ $log->user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($log->user->name ?? 'System') }}" class="w-8 h-8 rounded-full shrink-0 mt-0.5 border border-zinc-200 dark:border-zinc-700">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                                {{ $log->user->name ?? 'Sistem' }}
                                            </p>
                                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500 whitespace-nowrap" title="{{ $log->created_at->format('d M Y H:i:s') }}">
                                                {{ $log->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        <p class="text-zinc-600 dark:text-zinc-300 mt-0.5 leading-snug">
                                            {{ $log->description }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
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