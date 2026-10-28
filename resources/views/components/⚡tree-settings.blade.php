<?php

use App\Models\ActivityLog;
use App\Models\AdminActivityLog;
use App\Models\FamilyTree;
use App\Models\TreeInvitation;
use App\Models\TreeSuggestion;
use App\Services\TreeSuggestionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

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

    public $activeTab = 'stats';

    public $showModal = false;

    public $overrideReason = 'cash_cod';

    public $overrideNotes = '';

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
        $this->has_password = ! empty($this->tree->view_password);
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
            if (! empty($this->new_password)) {
                $data['view_password'] = Hash::make($this->new_password);
            }
        } else {
            $data['view_password'] = null;
            $this->has_password = false;
            $this->new_password = '';
        }

        $this->tree->update($data);
        $this->loadTreeData();

        ActivityLog::log(
            $this->treeId,
            'settings_updated',
            'Memperbarui pengaturan umum pohon silsilah'
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

        if (! $this->tree->is_premium) {
            session()->flash('error', 'Fitur kolaborasi hanya tersedia untuk Pohon Premium. Silakan upgrade pohon ini seharga Rp 5.000.');

            return;
        }

        $this->validate([
            'inviteEmail' => 'required|email',
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

        ActivityLog::log(
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

        if (! $this->tree->is_premium) {
            session()->flash('error', 'Fitur kolaborasi hanya tersedia untuk Pohon Premium. Silakan upgrade pohon ini seharga Rp 5.000.');

            return;
        }

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
            'Membuat tautan undangan kolaborasi umum'
        );

        session()->flash('success', 'Link undangan kolaborasi berhasil dibuat.');
    }

    public function cancelInvite($id)
    {
        \Illuminate\Support\Facades\Gate::authorize('manageCollaborators', $this->tree);
        TreeInvitation::where('family_tree_id', $this->treeId)->where('id', $id)->delete();
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

    public function adminOverridePremium(bool $makePremium)
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);

        $this->validate([
            'overrideReason' => 'required|string|in:cash_cod,bank_transfer,gateway_issue,special_promo,other',
            'overrideNotes' => 'nullable|string|max:200',
        ]);

        $reasonMap = [
            'cash_cod' => 'Bayar Tunai / COD saat tatap muka',
            'bank_transfer' => 'Transfer Bank Manual',
            'gateway_issue' => 'Kendala Gateway QRIS',
            'special_promo' => 'Kompensasi / Promo Khusus',
            'other' => 'Lainnya',
        ];

        $reasonLabel = $reasonMap[$this->overrideReason] ?? $this->overrideReason;
        $notesText = trim($this->overrideNotes ?? '');
        $fullDescription = 'Super Admin '.auth()->user()->name.($makePremium ? ' mengaktifkan status Premium manual' : ' mengembalikan status Standar')." (Metode: {$reasonLabel}".($notesText ? " - {$notesText}" : '').')';

        $this->tree->update(['is_premium' => $makePremium]);
        $this->loadTreeData();

        ActivityLog::log(
            $this->treeId,
            'admin_override_premium',
            $fullDescription,
            [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'is_premium' => $makePremium,
                'reason' => $this->overrideReason,
                'notes' => $this->overrideNotes,
            ]
        );

        AdminActivityLog::log(
            'tree_premium_override',
            Str::limit($fullDescription, 245),
            'family_trees',
            $this->tree->id,
            [
                'is_premium' => $makePremium,
                'reason' => $this->overrideReason,
                'notes' => $this->overrideNotes,
            ]
        );

        session()->flash('success_override', $makePremium ? 'Status pohon berhasil diubah menjadi Premium!' : 'Status pohon dikembalikan ke Standar.');
        $this->dispatch('refresh-tree');
    }

    public function approveSuggestion(int $id)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $this->tree);

        $suggestion = TreeSuggestion::where('family_tree_id', $this->treeId)->findOrFail($id);
        app(TreeSuggestionService::class)->approve($suggestion, auth()->user());

        session()->flash('success_suggestion', 'Usulan perubahan data berhasil disetujui dan diterapkan ke pohon silsilah.');
        $this->dispatch('refresh-tree');
    }

    public function rejectSuggestion(int $id)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $this->tree);

        $suggestion = TreeSuggestion::where('family_tree_id', $this->treeId)->findOrFail($id);
        app(TreeSuggestionService::class)->reject($suggestion, auth()->user());

        session()->flash('success_suggestion', 'Usulan perubahan data telah ditolak.');
    }

    public function with()
    {
        return [
            'stats' => $this->tree->getStatistics(),
            'invitations' => TreeInvitation::where('family_tree_id', $this->treeId)->where('status', 'pending')->get(),
            'editors' => $this->tree->users()->wherePivot('role', 'editor')->get(),
            'logs' => ActivityLog::with('user')
                ->where('family_tree_id', $this->treeId)
                ->latest()
                ->take(50)
                ->get(),
            'pendingSuggestionsCount' => TreeSuggestion::where('family_tree_id', $this->treeId)
                ->where('status', 'pending')
                ->count(),
            'suggestions' => TreeSuggestion::with(['targetMember', 'reviewer'])
                ->where('family_tree_id', $this->treeId)
                ->latest()
                ->get(),
        ];
    }
};
?>

<div>
    <flux:modal wire:model="showModal" class="md:w-[48rem] max-w-3xl">
        <div class="space-y-6">
            {{-- Header of modal --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-zinc-200 dark:border-zinc-800">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <flux:heading size="lg">Kelola Pohon Silsilah</flux:heading>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @if($tree->is_premium)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 flex items-center gap-1">
                                    👑 Premium
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                    Standar (Gratis - Max 100)
                                </span>
                            @endif

                            @if($tree->is_public)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center gap-1">
                                    🌍 Publik
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700 flex items-center gap-1">
                                    🔒 Privat
                                </span>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mt-0.5 truncate max-w-md">
                        {{ $tree->name }}
                    </p>
                </div>
            </div>

            {{-- Five Navigation Tabs --}}
            <div class="flex border-b border-zinc-200 dark:border-zinc-800 overflow-x-auto gap-1">
                <button type="button" wire:click="$set('activeTab', 'stats')" class="px-3.5 py-2 border-b-2 font-medium text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-1.5 cursor-pointer {{ $activeTab === 'stats' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-semibold' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    <span class="material-symbols-outlined text-base">bar_chart</span>
                    <span>{{ 'Ringkasan & Statistik' }}</span>
                </button>
                <button type="button" wire:click="$set('activeTab', 'license')" class="px-3.5 py-2 border-b-2 font-medium text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-1.5 cursor-pointer {{ $activeTab === 'license' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-semibold' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    <span class="material-symbols-outlined text-base">workspace_premium</span>
                    <span>{{ 'Status & Lisensi' }}</span>
                </button>
                <button type="button" wire:click="$set('activeTab', 'collab')" class="px-3.5 py-2 border-b-2 font-medium text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-1.5 cursor-pointer {{ $activeTab === 'collab' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-semibold' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    <span class="material-symbols-outlined text-base">group</span>
                    <span>Kolaborasi</span>
                </button>
                <button type="button" wire:click="$set('activeTab', 'logs')" class="px-3.5 py-2 border-b-2 font-medium text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-1.5 cursor-pointer {{ $activeTab === 'logs' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-semibold' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    <span class="material-symbols-outlined text-base">history</span>
                    <span>Riwayat Aktivitas</span>
                    <span class="text-[10px] bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 px-1.5 py-0.5 rounded-full font-mono">{{ $logs->count() }}</span>
                </button>
                <button type="button" wire:click="$set('activeTab', 'reminders')" class="px-3.5 py-2 border-b-2 font-medium text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-1.5 cursor-pointer {{ $activeTab === 'reminders' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-semibold' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    <span class="material-symbols-outlined text-base">calendar_month</span>
                    <span>Agenda &amp; Pengingat</span>
                </button>
                <button type="button" wire:click="$set('activeTab', 'suggestions')" class="px-3.5 py-2 border-b-2 font-medium text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-1.5 cursor-pointer {{ $activeTab === 'suggestions' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-semibold' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    <span class="material-symbols-outlined text-base">how_to_reg</span>
                    <span>Usulan Data</span>
                    @if($pendingSuggestionsCount > 0)
                        <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-500 text-white animate-pulse">
                            {{ $pendingSuggestionsCount }}
                        </span>
                    @endif
                </button>
                <button type="button" wire:click="$set('activeTab', 'general')" class="px-3.5 py-2 border-b-2 font-medium text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-1.5 cursor-pointer {{ $activeTab === 'general' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-semibold' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    <span class="material-symbols-outlined text-base">settings</span>
                    <span>Pengaturan</span>
                </button>
            </div>

            {{-- Tab 1: Ringkasan & Statistik --}}
            @if($activeTab === 'stats')
                <div class="space-y-6 pt-2">
                    {{-- 4 Stat Cards Grid --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        {{-- Card 1: Total Anggota --}}
                        <div class="p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 block mb-1">Total Anggota</span>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-2xl font-extrabold text-zinc-900 dark:text-white tabular-nums font-headline">{{ $stats['total_members'] }}</span>
                                <span class="text-xs text-zinc-400">jiwa</span>
                            </div>
                        </div>

                        {{-- Card 2: Laki-laki vs Perempuan --}}
                        <div class="p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 block mb-1">Gender</span>
                            <div class="text-xs text-zinc-700 dark:text-zinc-300 space-y-0.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-blue-600 dark:text-blue-400 font-semibold">Laki-laki:</span>
                                    <span class="font-bold tabular-nums">{{ $stats['male_count'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-rose-600 dark:text-rose-400 font-semibold">Perempuan:</span>
                                    <span class="font-bold tabular-nums">{{ $stats['female_count'] }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Card 3: Hidup vs Wafat --}}
                        <div class="p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 block mb-1">Status Kehidupan</span>
                            <div class="text-xs text-zinc-700 dark:text-zinc-300 space-y-0.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Hidup:</span>
                                    <span class="font-bold tabular-nums">{{ $stats['alive_count'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-zinc-500 font-semibold">Wafat:</span>
                                    <span class="font-bold tabular-nums">{{ $stats['deceased_count'] }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Card 4: Kunjungan Publik --}}
                        <div class="p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 block mb-1">Kunjungan Publik</span>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-2xl font-extrabold text-zinc-900 dark:text-white tabular-nums font-headline">{{ $stats['views_count'] }}</span>
                                <span class="text-xs text-zinc-400">kali</span>
                            </div>
                        </div>
                    </div>

                    {{-- Public Link card with direct copy button if public --}}
                    @if($tree->is_public && $tree->slug)
                        @php
                            $publicUrl = route('tree.public', $tree->slug);
                        @endphp
                        <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60"
                             x-data="{ copied: false }">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <span class="text-[11px] font-bold text-emerald-900 dark:text-emerald-200 uppercase tracking-wider block">Tautan Akses Publik Silsilah</span>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-300 font-mono truncate select-all mt-0.5">{{ $publicUrl }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button"
                                            @click="navigator.clipboard.writeText('{{ $publicUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 cursor-pointer">
                                        <span class="material-symbols-outlined text-sm" x-text="copied ? 'check' : 'content_copy'"></span>
                                        <span x-text="copied ? 'Tersalin!' : 'Salin Tautan'"></span>
                                    </button>
                                    <a href="{{ $publicUrl }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-semibold flex items-center gap-1 transition-all">
                                        <span>Buka</span>
                                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Tab 2: Status & Lisensi --}}
            @if($activeTab === 'license')
                <div class="space-y-6 pt-2">
                    {{-- Status Lisensi Card --}}
                    <div class="p-5 rounded-2xl border {{ $tree->is_premium ? 'bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-transparent border-amber-300 dark:border-amber-800/60' : 'bg-zinc-50 dark:bg-zinc-800/50 border-zinc-200 dark:border-zinc-700' }} space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl {{ $tree->is_premium ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' }} flex items-center justify-center text-2xl shadow-inner shrink-0">
                                    {{ $tree->is_premium ? '👑' : '🌳' }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-base text-zinc-900 dark:text-white">
                                        {{ $tree->is_premium ? 'Pohon Premium (Unlimited)' : 'Pohon Standar (Gratis)' }}
                                    </h4>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                        {{ $tree->is_premium ? 'Semua fitur premium aktif tanpa batasan waktu.' : 'Kapasitas maksimal hingga 100 anggota keluarga.' }}
                                    </p>
                                </div>
                            </div>

                            @if(!$tree->is_premium)
                                <a href="{{ route('dashboard', ['upgrade_tree' => $tree->id]) }}" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white text-xs font-bold shadow-md shadow-amber-500/20 transition-all inline-flex items-center gap-1.5 cursor-pointer shrink-0">
                                    <span class="material-symbols-outlined text-sm">workspace_premium</span>
                                    <span>Upgrade Rp 5.000</span>
                                </a>
                            @endif
                        </div>

                        {{-- Quota progress if free --}}
                        @if(!$tree->is_premium)
                            <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700/80 space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-medium text-zinc-600 dark:text-zinc-400">Penggunaan Kuota Anggota:</span>
                                    <span class="font-bold font-mono text-zinc-900 dark:text-white">{{ $stats['total_members'] }} / 100 Anggota</span>
                                </div>
                                <div class="w-full h-2.5 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-300" style="width: {{ min(100, ($stats['total_members'] / 100) * 100) }}%"></div>
                                </div>
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                    Sisa slot: <strong>{{ $stats['remaining_slots'] }} anggota</strong> lagi sebelum mencapai batas paket gratis.
                                </p>
                            </div>

                            {{-- Features included in upgrade --}}
                            <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700/80">
                                <h5 class="text-xs font-bold text-zinc-900 dark:text-white mb-2">Keunggulan Pohon Premium (Rp 5.000 Sekali Bayar):</h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-sm">check_circle</span>
                                        <span>Kapasitas anggota tak terbatas (unlimited)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-sm">check_circle</span>
                                        <span>Undang editor & kolaborator keluarga</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-sm">check_circle</span>
                                        <span>Ekspor Buku Silsilah Lengkap (PDF Book)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-sm">check_circle</span>
                                        <span>Sekali bayar berlaku selamanya (seumur hidup)</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Super Admin Override Panel --}}
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <div class="p-5 rounded-2xl bg-zinc-900 text-white dark:bg-zinc-950 border border-zinc-700 shadow-md space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-amber-400">admin_panel_settings</span>
                                    <h4 class="font-bold text-sm text-white">Super Admin Override (Bypass Pembayaran)</h4>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30">
                                    Panel ini hanya terlihat oleh Super Admin
                                </span>
                            </div>

                            <p class="text-xs text-zinc-300 leading-relaxed">
                                Aktifkan status Premium langsung untuk pohon silsilah ini jika pengguna membayar tunai/COD, transfer bank manual, atau kendala transaksi lainnya.
                            </p>

                            @if(session()->has('success_override'))
                                <div class="p-3 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 text-xs font-semibold">
                                    {{ session('success_override') }}
                                </div>
                            @endif

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-zinc-900 dark:text-zinc-100">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 mb-1">Alasan Override *</label>
                                    <select wire:model="overrideReason" class="w-full text-xs rounded-xl bg-white dark:bg-zinc-800 border border-zinc-600 text-zinc-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-amber-500">
                                        <option value="cash_cod">Bayar Tunai / COD saat tatap muka</option>
                                        <option value="bank_transfer">Transfer Bank Manual</option>
                                        <option value="gateway_issue">Kendala Gateway QRIS</option>
                                        <option value="special_promo">Kompensasi / Promo Khusus</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                    @error('overrideReason')
                                        <span class="text-red-400 text-[11px] block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 mb-1">Catatan / Referensi</label>
                                    <input type="text" wire:model="overrideNotes" placeholder="No. referensi transfer / catatan" class="w-full text-xs rounded-xl bg-white dark:bg-zinc-800 border border-zinc-600 text-zinc-900 dark:text-white px-3 py-2 placeholder-zinc-400 focus:ring-2 focus:ring-amber-500" />
                                    @error('overrideNotes')
                                        <span class="text-red-400 text-[11px] block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="pt-2 flex justify-end">
                                @if(!$tree->is_premium)
                                    <button type="button" wire:click="adminOverridePremium(true)" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-zinc-900 font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer shadow-sm">
                                        <span class="material-symbols-outlined text-sm">workspace_premium</span>
                                        <span>Jadikan Pohon Premium Manual</span>
                                    </button>
                                @else
                                    <button type="button" wire:click="adminOverridePremium(false)" class="px-4 py-2 rounded-xl bg-zinc-700 hover:bg-zinc-600 text-zinc-200 font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer shadow-sm">
                                        <span class="material-symbols-outlined text-sm">undo</span>
                                        <span>Kembalikan ke Status Standar</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Tab 3: Kolaborasi --}}
            @if($activeTab === 'collab')
                @if(!$tree->is_premium)
                    <div class="pt-2">
                        <div class="p-5 rounded-2xl bg-gradient-to-br from-amber-500/10 via-orange-500/5 to-amber-500/10 border border-amber-500/30 text-amber-950 dark:text-amber-200 space-y-4">
                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-3xl shrink-0 mt-0.5">workspace_premium</span>
                                <div>
                                    <h4 class="font-bold text-sm text-zinc-900 dark:text-white">Fitur Kolaborasi Khusus Pohon Premium</h4>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-300 mt-1 leading-relaxed">
                                        Undang sanak saudara dan kerabat keluarga besar sebagai editor untuk melengkapi nama, foto, dan cerita silsilah bersama-sama.
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs text-zinc-700 dark:text-zinc-300 font-medium pt-1">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-base">check_circle</span>
                                    <span>Undang editor keluarga tanpa batas</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-base">check_circle</span>
                                    <span>Kapasitas anggota unlimited (bebas batas 100)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-base">check_circle</span>
                                    <span>Riwayat aktivitas kolaborasi tersimpan</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-base">check_circle</span>
                                    <span>Cukup Rp 5.000 sekali bayar untuk selamanya</span>
                                </div>
                            </div>

                            <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-amber-200/80 dark:border-amber-800/40">
                                <div>
                                    <span class="text-[11px] text-zinc-500 dark:text-zinc-400 block">Biaya Upgrade Sekali Bayar:</span>
                                    <span class="text-base font-extrabold text-amber-600 dark:text-amber-400 font-mono">Rp 5.000</span>
                                </div>
                                <a href="{{ route('dashboard', ['upgrade_tree' => $tree->id]) }}"
                                   class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white text-xs font-bold shadow-md shadow-amber-500/20 transition-all cursor-pointer">
                                    <span class="material-symbols-outlined text-sm">workspace_premium</span>
                                    <span>Upgrade ke Premium Sekarang (Rp 5.000)</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="space-y-6 pt-2">
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
                                            $inviteUrl = route('invitations.accept', ['token' => $inv->token]);
                                            $inviteMsg = "Halo! Kamu diundang untuk berkolaborasi mengelola pohon silsilah keluarga \"{$tree->name}\" di Silsilah.\n\nKlik tautan berikut untuk bergabung sebagai Editor:\n{$inviteUrl}";
                                        @endphp
                                        <li class="p-3 flex items-center justify-between gap-3" x-data="{
                                            msgCopied: false,
                                            copyText(text) {
                                                if (navigator.clipboard && window.isSecureContext) {
                                                    navigator.clipboard.writeText(text);
                                                } else {
                                                    const ta = document.createElement('textarea');
                                                    ta.value = text;
                                                    ta.style.position = 'fixed';
                                                    ta.style.left = '-999999px';
                                                    ta.style.top = '-999999px';
                                                    document.body.appendChild(ta);
                                                    ta.focus();
                                                    ta.select();
                                                    try { document.execCommand('copy'); } catch(e) {}
                                                    document.body.removeChild(ta);
                                                }
                                                this.msgCopied = true;
                                                setTimeout(() => this.msgCopied = false, 2500);
                                            }
                                        }">
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
                                                        x-on:click="copyText({{ json_encode($inviteMsg) }})"
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
                                <h4 class="font-bold text-sm text-zinc-900 dark:text-white mb-2">Editor Aktif</h4>
                                <ul class="space-y-2 border border-zinc-200 dark:border-zinc-700/60 rounded-xl divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach($editors as $ed)
                                        <li class="p-3 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <img src="{{ $ed->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($ed->name) }}" class="w-8 h-8 rounded-full border border-zinc-200 dark:border-zinc-700">
                                                <div>
                                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $ed->name }}</p>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $ed->email }}</p>
                                                </div>
                                            </div>
                                            <button wire:click="removeEditor({{ $ed->id }})" class="text-xs text-red-500 hover:underline cursor-pointer">Hapus</button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            @endif

            {{-- Tab 4: Riwayat Aktivitas --}}
            @if($activeTab === 'logs')
                <div class="space-y-4 pt-2">
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
                                    <img src="{{ $log->user?->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($log->user?->name ?? 'System') }}" class="w-8 h-8 rounded-full shrink-0 mt-0.5 border border-zinc-200 dark:border-zinc-700">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                                {{ $log->user?->name ?? 'Sistem' }}
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

            {{-- Tab 5: Agenda & Pengingat --}}
            @if($activeTab === 'reminders')
                <div class="space-y-6 pt-2">
                    <livewire:family-reminders :tree-id="$treeId" :as-modal="false" />
                </div>
            @endif

            {{-- Tab: Usulan Data (Crowdsourcing) --}}
            @if($activeTab === 'suggestions')
                <div class="space-y-4 pt-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-sm text-zinc-900 dark:text-white">Kotak Masuk Usulan Data Kerabat</h4>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Tinjau penambahan anak, pasangan, atau koreksi data yang diusulkan oleh anggota keluarga.</p>
                        </div>
                    </div>

                    @if(session()->has('success_suggestion'))
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl text-xs flex items-center gap-2">
                            <flux:icon name="check-circle" class="size-4 shrink-0" />
                            <span>{{ session('success_suggestion') }}</span>
                        </div>
                    @endif

                    @if($suggestions->isEmpty())
                        <div class="text-center py-10 px-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-800 space-y-3">
                            <span class="p-3 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 inline-block text-2xl">
                                📬
                            </span>
                            <div class="max-w-sm mx-auto">
                                <h5 class="font-bold text-sm text-zinc-800 dark:text-zinc-200">Belum Ada Usulan Masuk</h5>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                    Keluarga atau kerabat dapat mengusulkan penambahan anak, pasangan, atau koreksi tanggal melalui menu publik silsilah.
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($suggestions as $sug)
                                <div class="p-4 rounded-2xl border transition-all {{ $sug->status === 'pending' ? 'bg-amber-50/50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800/60' : ($sug->status === 'approved' ? 'bg-zinc-50 dark:bg-zinc-800/40 border-zinc-200 dark:border-zinc-800' : 'bg-zinc-50/50 dark:bg-zinc-800/20 border-zinc-200/60 dark:border-zinc-800/50 opacity-70') }}">
                                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                        <div class="space-y-2 flex-1">
                                            {{-- Badges row --}}
                                            <div class="flex items-center gap-2 flex-wrap">
                                                @if($sug->type === 'add_child')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                                        👶 Tambah Anak
                                                    </span>
                                                @elseif($sug->type === 'add_spouse')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                                        💍 Tambah Pasangan
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                        ✏️ Koreksi Anggota
                                                    </span>
                                                @endif

                                                @if($sug->status === 'pending')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white">
                                                        Menunggu Ditinjau
                                                    </span>
                                                @elseif($sug->status === 'approved')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-600 text-white">
                                                        ✓ Disetujui
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-600 text-white">
                                                        ✕ Ditolak
                                                    </span>
                                                @endif

                                                <span class="text-[11px] text-zinc-400">
                                                    {{ $sug->created_at->diffForHumans() }}
                                                </span>
                                            </div>

                                            {{-- Details --}}
                                            <div>
                                                <h5 class="text-sm font-bold text-zinc-900 dark:text-white">
                                                    {{ $sug->data['first_name'] ?? '-' }} {{ $sug->data['last_name'] ?? '' }}
                                                    <span class="text-xs font-normal text-zinc-500">
                                                        ({{ ($sug->data['gender'] ?? 'male') === 'male' ? 'Laki-laki' : 'Perempuan' }})
                                                    </span>
                                                </h5>
                                                
                                                <div class="text-xs text-zinc-600 dark:text-zinc-300 mt-1 space-y-0.5">
                                                    @if($sug->targetMember)
                                                        <p>
                                                            <span class="font-semibold text-zinc-500">Rujukan:</span>
                                                            {{ $sug->targetMember->first_name }} {{ $sug->targetMember->last_name }} (#M{{ $sug->targetMember->id }})
                                                        </p>
                                                    @endif

                                                    @if(!empty($sug->data['birth_date']))
                                                        <p><span class="font-semibold text-zinc-500">Lahir:</span> {{ $sug->data['birth_date'] }} {{ !empty($sug->data['birth_place']) ? 'di '.$sug->data['birth_place'] : '' }}</p>
                                                    @endif

                                                    @if(isset($sug->data['is_living']) && ! $sug->data['is_living'])
                                                        <p><span class="font-semibold text-zinc-500">Status:</span> Wafat {{ !empty($sug->data['death_date']) ? '('.$sug->data['death_date'].')' : '' }}</p>
                                                    @endif

                                                    @if($sug->notes)
                                                        <p class="italic text-zinc-500 mt-1 bg-white/60 dark:bg-zinc-900/60 p-2 rounded-lg border border-zinc-200/50 dark:border-zinc-700/50">
                                                            "{{ $sug->notes }}"
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Contributor info --}}
                                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400 pt-1 border-t border-zinc-200/60 dark:border-zinc-700/60 flex items-center gap-3">
                                                <span>Pengusul: <strong class="text-zinc-700 dark:text-zinc-200">{{ $sug->contributor_name }}</strong></span>
                                                @if($sug->contributor_contact)
                                                    @php
                                                        $waNumber = preg_replace('/[^0-9]/', '', $sug->contributor_contact);
                                                        if (str_starts_with($waNumber, '0')) {
                                                            $waNumber = '62' . substr($waNumber, 1);
                                                        }
                                                    @endphp
                                                    <span>Kontak: 
                                                        <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener noreferrer" class="text-emerald-600 dark:text-emerald-400 hover:underline inline-flex items-center gap-0.5">
                                                            {{ $sug->contributor_contact }}
                                                        </a>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Actions for pending --}}
                                        @if($sug->status === 'pending')
                                            <div class="flex sm:flex-col gap-2 shrink-0 pt-2 sm:pt-0">
                                                <button type="button"
                                                        wire:click="approveSuggestion({{ $sug->id }})"
                                                        class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1.5 cursor-pointer">
                                                    <flux:icon name="check" class="size-3.5" />
                                                    <span>Setujui</span>
                                                </button>
                                                <button type="button"
                                                        wire:click="rejectSuggestion({{ $sug->id }})"
                                                        class="px-3.5 py-2 rounded-xl bg-zinc-100 hover:bg-rose-100 hover:text-rose-700 dark:bg-zinc-800 dark:hover:bg-rose-950/40 text-zinc-700 dark:text-zinc-300 text-xs font-semibold transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                                    <flux:icon name="x-mark" class="size-3.5" />
                                                    <span>Tolak</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Tab 6: Pengaturan --}}
            @if($activeTab === 'general')
                <div class="space-y-6 pt-2">
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
