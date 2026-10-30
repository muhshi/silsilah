<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Models\Member;
use App\Models\FamilyTree;
use App\Models\Marriage;
use Flux\Flux;

new class extends Component
{
    use WithFileUploads;

    public $treeId;
    public $isPublic = false;
    public $treeSlug = null;
    public $memberId;
    
    // Detail view
    public $viewMember = null;

    public function mount($treeId, $isPublic = false, $treeSlug = null)
    {
        $this->treeId = $treeId;
        $this->isPublic = $isPublic;
        $this->treeSlug = $treeSlug;
    }
    
    // Pribadi
    public $first_name = '';
    public $last_name = '';
    public $gender = 'male';
    public $is_living = true;
    public $birth_date = '';
    public $death_date = '';
    public $birth_place = '';
    public $marriage_date = '';
    public $address = '';
    public $profession = '';
    public $bio = '';
    
    // Photo & Avatar
    public $photo;
    public $photo_url = '';
    public $avatar_id;

    // Relation context
    public $relType = 'child_of'; // child_of, spouse_of, ex_of, parent_of
    public $targetMemberId = null;
    public $marriage_status = 'married'; // married, divorced

    // Manual parent selectors
    public $father_id;
    public $mother_id;
    public $other_parent_id;

    public function autoDetectGender(): void
    {
        if (in_array($this->relType, ['spouse_of', 'ex_of']) && $this->targetMemberId) {
            $target = Member::find($this->targetMemberId);
            if ($target) {
                $this->gender = $target->gender === 'male' ? 'female' : 'male';
            }
        }
    }

    public function updatedTargetMemberId($value)
    {
        $this->autoDetectGender();
    }

    public function updatedRelType($value)
    {
        $this->autoDetectGender();
        if ($this->relType === 'child_of' && $this->targetMemberId) {
            $spouses = $this->getTargetSpouses();
            if ($spouses->isNotEmpty()) {
                $this->other_parent_id = $spouses->first()->id;
            }
        }
    }

    protected function getTargetSpouses()
    {
        if (!$this->targetMemberId) {
            return collect();
        }

        $target = Member::with(['marriagesAsHusband.wife', 'marriagesAsWife.husband'])->find($this->targetMemberId);
        if (!$target) {
            return collect();
        }

        if ($target->gender === 'male') {
            return $target->marriagesAsHusband->map(fn($m) => $m->wife)->filter();
        } else {
            return $target->marriagesAsWife->map(fn($m) => $m->husband)->filter();
        }
    }

    #[On('create-member')]
    public function createMember($targetId = null, $relType = 'child_of')
    {
        // Normalize array payload if dispatched from Alpine JS ($dispatch('create-member', { targetId: X, relType: Y }))
        if (is_array($targetId)) {
            $relType = $targetId['relType'] ?? $targetId['rel_type'] ?? 'child_of';
            $targetId = $targetId['targetId'] ?? $targetId['target_id'] ?? null;
        }

        $this->resetForm();
        $this->targetMemberId = $targetId ? (int) $targetId : null;
        $this->relType = $relType;
        $this->marriage_status = $relType === 'ex_of' ? 'divorced' : 'married';
        $this->is_living = true;
        $this->gender = 'male';

        $this->autoDetectGender();

        if ($this->relType === 'child_of' && $this->targetMemberId) {
            $spouses = $this->getTargetSpouses();
            if ($spouses->isNotEmpty()) {
                $this->other_parent_id = $spouses->first()->id;
            }
        }

        Flux::modal('member-modal')->show();
    }

    #[On('edit-member')]
    public function editMember($id)
    {
        if (is_array($id)) {
            $id = $id['id'] ?? null;
        }

        $this->resetForm();
        $this->memberId = $id ? (int) $id : null;
        
        $member = Member::findOrFail($this->memberId);
        $this->first_name = $member->first_name;
        $this->last_name = $member->last_name;
        $this->gender = $member->gender;
        $this->is_living = $member->is_living;
        $this->birth_date = $member->birth_date ? \Carbon\Carbon::parse($member->birth_date)->format('Y-m-d') : '';
        $this->death_date = $member->death_date ? \Carbon\Carbon::parse($member->death_date)->format('Y-m-d') : '';
        $this->birth_place = $member->birth_place ?? '';
        $this->profession = $member->profession ?? '';
        $this->bio = $member->bio ?? '';
        $this->father_id = $member->father_id;
        $this->mother_id = $member->mother_id;
        $this->avatar_id = $member->avatar_id;
        $this->address = $member->address ?? '';

        $marriages = Marriage::where('husband_id', $this->memberId)->orWhere('wife_id', $this->memberId)->get();
        if ($marriages->count() === 1 && $marriages->first()->marriage_date) {
            $this->marriage_date = \Carbon\Carbon::parse($marriages->first()->marriage_date)->format('Y-m-d');
        }

        // If photo is a URL, put it in photo_url field
        if ($member->photo && str_starts_with($member->photo, 'http')) {
            $this->photo_url = $member->photo;
        }

        Flux::modal('member-modal')->show();
    }

    public function with()
    {
        $tree = FamilyTree::find($this->treeId);
        $targetMember = null;
        $targetSpouses = collect();
        $availableMembers = collect();

        if ($this->targetMemberId) {
            $targetMember = Member::with(['marriagesAsHusband.wife', 'marriagesAsWife.husband'])->find($this->targetMemberId);
            if ($targetMember && $this->relType === 'child_of') {
                if ($targetMember->gender === 'male') {
                    $targetSpouses = $targetMember->marriagesAsHusband->map(fn($m) => $m->wife)->filter();
                } else {
                    $targetSpouses = $targetMember->marriagesAsWife->map(fn($m) => $m->husband)->filter();
                }
            }
        }

        if ($this->memberId || (!$this->targetMemberId)) {
            $excludedIds = collect();
            if ($this->memberId) {
                $current = Member::find($this->memberId);
                if ($current) {
                    $excludedIds = $current->getAllDescendantIds()->push($this->memberId);
                }
            }

            $availableMembers = Member::where('family_tree_id', $this->treeId)
                ->when($excludedIds->isNotEmpty(), fn($q) => $q->whereNotIn('id', $excludedIds))
                ->orderBy('first_name')
                ->get();
        }

        $existingMarriages = collect();
        if ($this->memberId) {
            $current = Member::with(['marriagesAsHusband.wife', 'marriagesAsWife.husband'])->find($this->memberId);
            if ($current) {
                $existingMarriages = $current->gender === 'male'
                    ? $current->marriagesAsHusband
                    : $current->marriagesAsWife;
            }
        }

        return [
            'tree' => $tree,
            'hasReachedLimit' => $tree ? $tree->hasReachedMemberLimit() : false,
            'isPremium' => $tree ? $tree->is_premium : false,
            'membersCount' => $tree ? $tree->members()->count() : 0,
            'targetMember' => $targetMember,
            'targetSpouses' => $targetSpouses,
            'availableMembers' => $availableMembers,
            'existingMarriages' => $existingMarriages,
        ];
    }

    public function save()
    {
        $tree = FamilyTree::findOrFail($this->treeId);
        \Illuminate\Support\Facades\Gate::authorize('editMembers', $tree);

        if (! $this->memberId && ! $tree->canAddMembers(1)) {
            $this->addError('first_name', 'Batas maksimal 100 anggota untuk pohon gratis telah tercapai. Silakan upgrade pohon ini ke Premium seharga Rp 5.000 untuk menambah anggota tanpa batas.');
            session()->flash('limit_error', 'Batas maksimal 100 anggota untuk pohon gratis telah tercapai. Silakan upgrade pohon ini ke Premium seharga Rp 5.000 untuk menambah anggota tanpa batas.');

            return;
        }

        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'gender' => 'required|in:male,female',
            'is_living' => 'boolean',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
            'father_id' => 'nullable|exists:members,id',
            'mother_id' => 'nullable|exists:members,id',
        ]);

        // Anti-cycle validation: Cannot select self or any descendant as parent
        if ($this->memberId) {
            $current = Member::find($this->memberId);
            if ($current) {
                $descendants = $current->getAllDescendantIds();
                if ($this->father_id && ($this->father_id == $this->memberId || $descendants->contains((int) $this->father_id))) {
                    $this->addError('father_id', 'Keturunan Anda tidak dapat dipilih sebagai Ayah.');

                    return;
                }
                if ($this->mother_id && ($this->mother_id == $this->memberId || $descendants->contains((int) $this->mother_id))) {
                    $this->addError('mother_id', 'Keturunan Anda tidak dapat dipilih sebagai Ibu.');

                    return;
                }
            }
        }

        $data = [
            'family_tree_id' => $this->treeId,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'is_living' => $this->is_living,
            'birth_date' => $this->birth_date ?: null,
            'death_date' => $this->death_date ?: null,
            'birth_place' => $this->birth_place,
            'address' => $this->address,
            'profession' => $this->profession,
            'bio' => $this->bio,
            'father_id' => $this->father_id ?: null,
            'mother_id' => $this->mother_id ?: null,
            'avatar_id' => $this->avatar_id ?: null,
        ];

        // Auto-assign avatar if none selected and no photo/url
        if (!$this->memberId && !$this->avatar_id && !$this->photo && !$this->photo_url) {
            $maleAvatars = ['anak laki.png', 'anak.png', 'bayi 1.png', 'bapak peci hitam.png', 'dewasa laki.png', 'kakek peci putih.png', 'makasar.png', 'pemuda.png', 'pria sorban.png', 'remaja 1.png', 'remaja timur.png'];
            $femaleAvatars = ['anak perempuan.png', 'dewasa perempuan.png', 'hijab.png', 'ibu hijab kacamata.png', 'nenek hijab.png', 'nenek makasar.png', 'remaja perempuan hijab.png', 'remaja.png'];
            
            $this->avatar_id = $this->gender === 'male' 
                ? $maleAvatars[array_rand($maleAvatars)] 
                : $femaleAvatars[array_rand($femaleAvatars)];
            $data['avatar_id'] = $this->avatar_id;
        }

        // Handle photo URL (priority: upload > url > avatar)
        if ($this->photo) {
            $path = $this->compressAndStorePhoto($this->photo);
            $data['photo'] = $path;
            $data['avatar_id'] = null;
        } elseif ($this->photo_url) {
            $data['photo'] = $this->photo_url;
            $data['avatar_id'] = null;
        }

        // Handle relation context for new members
        if (!$this->memberId && $this->targetMemberId) {
            $target = Member::find($this->targetMemberId);
            if ($target) {
                if ($this->relType === 'child_of') {
                    if ($target->gender === 'male') {
                        $data['father_id'] = $target->id;
                        if ($this->other_parent_id) {
                            $data['mother_id'] = $this->other_parent_id;
                        }
                    } else {
                        $data['mother_id'] = $target->id;
                        if ($this->other_parent_id) {
                            $data['father_id'] = $this->other_parent_id;
                        }
                    }
                }
            }
        }

        if ($this->memberId) {
            $member = Member::findOrFail($this->memberId);
            $member->update($data);

            if ($this->marriage_date) {
                $marriages = Marriage::where('husband_id', $this->memberId)->orWhere('wife_id', $this->memberId)->get();
                if ($marriages->count() === 1) {
                    $marriages->first()->update(['marriage_date' => $this->marriage_date]);
                }
            }

            \App\Models\ActivityLog::log(
                $this->treeId,
                'member_updated',
                "Mengubah data anggota '{$member->first_name} {$member->last_name}'"
            );
        } else {
            $member = Member::create($data);
            \App\Models\ActivityLog::log(
                $this->treeId,
                'member_created',
                "Menambahkan anggota baru '{$member->first_name} {$member->last_name}'"
            );
            
            // Parent Of - set member as parent of target
            if ($this->relType === 'parent_of' && $this->targetMemberId) {
                $target = Member::find($this->targetMemberId);
                if ($target) {
                    if ($member->gender === 'male') {
                        $target->update(['father_id' => $member->id]);
                    } else {
                        $target->update(['mother_id' => $member->id]);
                    }
                }
            }
            
            // Spouse Of - create marriage
            if (in_array($this->relType, ['spouse_of', 'ex_of']) && $this->targetMemberId) {
                $target = Member::find($this->targetMemberId);
                if ($target) {
                    $husbandId = $member->gender === 'male' ? $member->id : $target->id;
                    $wifeId = $member->gender === 'female' ? $member->id : $target->id;
                    
                    $isCurrent = ($this->relType === 'spouse_of') && ($this->marriage_status === 'married');

                    Marriage::create([
                        'husband_id' => $husbandId,
                        'wife_id' => $wifeId,
                        'marriage_date' => $this->marriage_date ?: null,
                        'is_current' => $isCurrent,
                    ]);
                }
            }
        }

        Flux::modal('member-modal')->close();
        $this->dispatch('member-updated');
        session()->flash('success', $this->memberId ? 'Anggota berhasil diperbarui.' : 'Anggota berhasil ditambahkan.');
        $this->dispatch('refresh-tree');
    }

    public function deleteMember()
    {
        \Illuminate\Support\Facades\Gate::authorize('editMembers', FamilyTree::findOrFail($this->treeId));
        if ($this->memberId) {
            $member = Member::find($this->memberId);
            if ($member) {
                $name = "{$member->first_name} {$member->last_name}";
                Marriage::where('husband_id', $this->memberId)->orWhere('wife_id', $this->memberId)->delete();
                $member->delete();
                \App\Models\ActivityLog::log(
                    $this->treeId,
                    'member_deleted',
                    "Menghapus anggota '{$name}'"
                );
            }
            Flux::modal('member-modal')->close();
            $this->dispatch('refresh-tree');
        }
    }

    public function updateMarriageStatus($marriageId, $isCurrent)
    {
        $tree = FamilyTree::findOrFail($this->treeId);
        \Illuminate\Support\Facades\Gate::authorize('editMembers', $tree);

        $marriage = Marriage::findOrFail($marriageId);
        $marriage->update(['is_current' => (bool) $isCurrent]);

        $statusLabel = $isCurrent ? 'Menikah (Aktif)' : 'Cerai';
        \App\Models\ActivityLog::log(
            $this->treeId,
            'marriage_updated',
            "Mengubah status pernikahan menjadi {$statusLabel}"
        );

        session()->flash('success', 'Status pernikahan berhasil diperbarui.');
        $this->dispatch('refresh-tree');
    }

    public function deleteMarriage($marriageId)
    {
        $tree = FamilyTree::findOrFail($this->treeId);
        \Illuminate\Support\Facades\Gate::authorize('editMembers', $tree);

        $marriage = Marriage::findOrFail($marriageId);
        $marriage->delete();

        \App\Models\ActivityLog::log(
            $this->treeId,
            'marriage_deleted',
            "Menghapus relasi pernikahan"
        );

        session()->flash('success', 'Relasi pernikahan berhasil dihapus.');
        $this->dispatch('refresh-tree');
    }

    #[On('show-member')]
    public function showMember($id)
    {
        $this->viewMember = Member::with(['marriagesAsHusband.wife', 'marriagesAsWife.husband'])->findOrFail($id);
        Flux::modal('detail-modal')->show();
    }

    #[On('confirm-delete-member')]
    public function confirmDeleteMember($id)
    {
        $this->memberId = $id;
        $member = Member::find($id);
        if ($member) {
            $name = "{$member->first_name} {$member->last_name}";
            Marriage::where('husband_id', $id)->orWhere('wife_id', $id)->delete();
            $member->delete();
            \App\Models\ActivityLog::log(
                $this->treeId,
                'member_deleted',
                "Menghapus anggota '{$name}'"
            );
        }
        $this->dispatch('refresh-tree');
    }

    public function editFromDetail()
    {
        if ($this->viewMember) {
            Flux::modal('detail-modal')->close();
            $this->editMember($this->viewMember->id);
        }
    }

    public function deleteFromDetail()
    {
        if ($this->viewMember) {
            $id = $this->viewMember->id;
            Marriage::where('husband_id', $id)->orWhere('wife_id', $id)->delete();
            Member::findOrFail($id)->delete();
            Flux::modal('detail-modal')->close();
            $this->viewMember = null;
            $this->dispatch('refresh-tree');
        }
    }

    /**
     * Compress and resize uploaded photo to save server space.
     * Max 400px, JPEG quality 80%.
     */
    public function compressAndStorePhoto($uploadedFile): string
    {
        $maxSize = 400;
        $quality = 80;

        $tempPath = $uploadedFile->getRealPath();
        $imageInfo = getimagesize($tempPath);
        $mime = $imageInfo['mime'] ?? '';

        $source = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($tempPath),
            'image/png' => imagecreatefrompng($tempPath),
            'image/gif' => imagecreatefromgif($tempPath),
            'image/webp' => imagecreatefromwebp($tempPath),
            default => imagecreatefromjpeg($tempPath),
        };

        $origW = imagesx($source);
        $origH = imagesy($source);

        // Calculate new dimensions (max 400px on longest side)
        if ($origW > $maxSize || $origH > $maxSize) {
            if ($origW >= $origH) {
                $newW = $maxSize;
                $newH = (int) ($origH * ($maxSize / $origW));
            } else {
                $newH = $maxSize;
                $newW = (int) ($origW * ($maxSize / $origH));
            }
        } else {
            $newW = $origW;
            $newH = $origH;
        }

        $resized = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        $filename = 'avatars/' . uniqid('photo_') . '.jpg';
        $storagePath = storage_path('app/public/' . $filename);

        // Ensure directory exists
        if (!is_dir(dirname($storagePath))) {
            mkdir(dirname($storagePath), 0755, true);
        }

        imagejpeg($resized, $storagePath, $quality);
        imagedestroy($source);
        imagedestroy($resized);

        return $filename;
    }

    public function resetForm()
    {
        $this->reset([
            'memberId', 'first_name', 'last_name', 'gender', 'is_living',
            'birth_date', 'death_date', 'birth_place', 'address', 'profession', 'bio',
            'photo', 'photo_url', 'avatar_id', 'father_id', 'mother_id', 'other_parent_id', 'relType', 'targetMemberId',
            'marriage_date', 'marriage_status'
        ]);
    }
};
?>

<div>
    <flux:modal name="member-modal" class="md:w-[40rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $memberId ? 'Edit Anggota' : 'Tambah Anggota Baru' }}</flux:heading>
                @if($targetMember)
                    <flux:subheading>
                        Menambahkan anggota baru terkait dengan <strong>{{ $targetMember->first_name }} {{ $targetMember->last_name }}</strong>
                    </flux:subheading>
                @else
                    <flux:subheading>Masukkan detail informasi anggota silsilah.</flux:subheading>
                @endif
            </div>

            @if(!$memberId && $hasReachedLimit)
                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-900 dark:text-amber-200 space-y-2">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-2xl shrink-0 mt-0.5">warning</span>
                        <div class="space-y-1">
                            <h4 class="font-bold text-sm">Batas Maksimal 100 Anggota Tercapai</h4>
                            <p class="text-xs text-on-surface-variant leading-relaxed">
                                Pohon standar dibatasi hingga 100 anggota keluarga. Tingkatkan pohon ini menjadi <strong>👑 Pohon Premium</strong> seharga <strong>Rp 5.000</strong> untuk membuka kuota anggota tanpa batas (unlimited) dan kolaborasi editor.
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-1">
                        <a href="{{ route('dashboard', ['upgrade_tree' => $treeId]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition-all shadow-xs">
                            <span class="material-symbols-outlined text-sm">workspace_premium</span>
                            <span>Upgrade ke Premium (Rp 5.000)</span>
                        </a>
                    </div>
                </div>
            @endif

            {{-- Tab: Pribadi --}}
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="first_name" label="Nama Depan *" placeholder="Isi nama depan" required />
                    <flux:input wire:model="last_name" label="Nama Belakang" placeholder="Isi nama belakang" />
                </div>

                {{-- Jenis Hubungan (only for new member with target) --}}
                @if(!$memberId && $targetMemberId)
                    <flux:select wire:model.live="relType" label="Jenis Hubungan">
                        <flux:select.option value="child_of">Anak</flux:select.option>
                        <flux:select.option value="spouse_of">Pasangan</flux:select.option>
                        <flux:select.option value="ex_of">Mantan (Cerai)</flux:select.option>
                        <flux:select.option value="parent_of">Orang Tua</flux:select.option>
                    </flux:select>
                @endif

                {{-- Status Pernikahan (Saat menambah pasangan baru) --}}
                @if(!$memberId && in_array($relType, ['spouse_of', 'ex_of']))
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-2">
                        <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Status Pernikahan</label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer {{ $marriage_status === 'married' ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 font-bold' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}">
                                <input type="radio" wire:model.live="marriage_status" value="married" class="sr-only">
                                <span>💍 Menikah (Aktif)</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer {{ $marriage_status === 'divorced' ? 'border-amber-500 bg-amber-50/70 dark:bg-amber-950/40 text-amber-800 dark:text-amber-200 font-bold' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}">
                                <input type="radio" wire:model.live="marriage_status" value="divorced" class="sr-only">
                                <span>💔 Cerai Hidup</span>
                            </label>
                        </div>
                    </div>
                @endif

                {{-- Pasangan / Orang Tua Kedua (only when relType === 'child_of') --}}
                @if(!$memberId && $targetMember && $relType === 'child_of' && $targetSpouses->isNotEmpty())
                    @php
                        $parentLabel = $targetMember->gender === 'male' ? 'Pilih Ibu (Pasangan)' : 'Pilih Ayah (Pasangan)';
                    @endphp
                    <flux:select wire:model="other_parent_id" :label="$parentLabel">
                        <flux:select.option value="">-- Tidak Diatur --</flux:select.option>
                        @foreach($targetSpouses as $sp)
                            <flux:select.option value="{{ $sp->id }}">
                                {{ $sp->first_name }} {{ $sp->last_name }} @if($targetSpouses->count() > 1) (#{{ $loop->iteration }}) @endif
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:radio.group wire:model="gender" label="Jenis Kelamin *">
                            <flux:radio value="female" label="Wanita" />
                            <flux:radio value="male" label="Pria" />
                        </flux:radio.group>
                    </div>
                    <div class="flex items-end pb-2">
                        <flux:switch wire:model="is_living" label="Hidup" />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input type="date" wire:model="birth_date" label="Tgl Lahir" />
                    <flux:input type="date" wire:model="marriage_date" label="Tgl Pernikahan" />
                    <flux:input type="date" wire:model="death_date" label="Tgl Wafat" x-bind:disabled="$wire.is_living" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="birth_place" label="Tempat Lahir" placeholder="Isi tempat lahir" />
                    <flux:input wire:model="profession" label="Profesi" placeholder="Isi profesi / pekerjaan" />
                </div>

                <flux:input wire:model="address" label="Alamat" placeholder="Isi alamat tempat tinggal" />

                {{-- Photo: Upload or URL --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Upload Foto</label>
                        <div class="mt-1">
                            <input type="file" wire:model="photo" accept="image/*" class="text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/40 dark:file:text-indigo-300" />
                            <div wire:loading wire:target="photo" class="text-xs text-indigo-500 mt-1">Uploading...</div>
                        </div>
                    </div>
                    <flux:input wire:model="photo_url" label="Atau URL Foto" placeholder="https://contoh.com/foto.jpg" />
                </div>

                {{-- Avatar picker --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Atau Pilih Ikon Karakter Avatar</label>
                        @if($avatar_id)
                            <button type="button" wire:click="$set('avatar_id', '')" class="text-[11px] font-semibold text-red-600 dark:text-red-400 hover:underline">
                                ✕ Reset Pilihan
                            </button>
                        @endif
                    </div>
                    <div class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-9 gap-2 p-2.5 bg-zinc-50 dark:bg-zinc-800/40 rounded-2xl border border-zinc-200/80 dark:border-zinc-700/60 max-h-44 overflow-y-auto">
                        @php
                        $allAvatars = ['anak laki.png', 'anak perempuan.png', 'anak.png', 'bapak peci hitam.png', 'bayi 1.png', 'dewasa laki.png', 'dewasa perempuan.png', 'hijab.png', 'ibu hijab kacamata.png', 'kakek peci putih.png', 'makasar.png', 'nenek hijab.png', 'nenek makasar.png', 'pemuda.png', 'pria sorban.png', 'remaja 1.png', 'remaja perempuan hijab.png', 'remaja timur.png', 'remaja.png'];
                        @endphp
                        @foreach($allAvatars as $avatarName)
                            <label for="sradioe_{{ Str::slug($avatarName) }}"
                                   class="cursor-pointer relative flex flex-col items-center justify-center p-1 rounded-xl transition-all {{ $avatar_id === $avatarName ? 'ring-2 ring-emerald-500 bg-emerald-100/80 dark:bg-emerald-950/80 scale-105 shadow-sm' : 'hover:bg-zinc-200/60 dark:hover:bg-zinc-700/50' }}"
                                   title="{{ str_replace('.png', '', $avatarName) }}">
                                <input type="radio" name="avatar" wire:model.live="avatar_id" value="{{ $avatarName }}" id="sradioe_{{ Str::slug($avatarName) }}" class="sr-only" />
                                <img src="{{ asset('images/avatar/' . $avatarName) }}" class="w-10 h-10 object-contain rounded-lg pointer-events-none" alt="{{ $avatarName }}" />
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Relasi Manual (Edit mode only) --}}
            @if($memberId || (!$targetMemberId && $availableMembers->isNotEmpty()))
                <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-zinc-800">
                    <div class="grid grid-cols-2 gap-4">
                        <flux:select wire:model="father_id" label="Ayah" placeholder="-- Tidak Diketahui --">
                            <flux:select.option value="">-- Tidak Diketahui --</flux:select.option>
                            @foreach($availableMembers->where('gender', 'male') as $m)
                                <flux:select.option value="{{ $m->id }}">{{ $m->first_name }} {{ $m->last_name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="mother_id" label="Ibu" placeholder="-- Tidak Diketahui --">
                            <flux:select.option value="">-- Tidak Diketahui --</flux:select.option>
                            @foreach($availableMembers->where('gender', 'female') as $m)
                                <flux:select.option value="{{ $m->id }}">{{ $m->first_name }} {{ $m->last_name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            @endif

            {{-- Daftar Pasangan & Status Hubungan (Edit mode) --}}
            @if($memberId && $existingMarriages->isNotEmpty())
                <div class="space-y-3 pt-4 border-t border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                            💍 Pasangan & Status Hubungan
                        </label>
                    </div>
                    <div class="space-y-2">
                        @foreach($existingMarriages as $m)
                            @php
                                $sp = $m->husband_id == $memberId ? $m->wife : $m->husband;
                                $isDivorced = !$m->is_current;
                                $isDeceasedSpouse = $sp && !$sp->is_living;
                            @endphp
                            @if($sp)
                                <div class="flex items-center justify-between p-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-base">💍</span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate">
                                                {{ $sp->first_name }} {{ $sp->last_name }}
                                            </div>
                                            <div class="text-[11px] flex items-center gap-1.5 flex-wrap">
                                                @if($isDeceasedSpouse && $isDivorced)
                                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">Cerai Mati (Wafat)</span>
                                                @elseif($isDivorced)
                                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">Cerai Hidup</span>
                                                @else
                                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">Menikah (Aktif)</span>
                                                @endif
                                                @if($m->marriage_date)
                                                    <span class="text-zinc-400">• {{ \Carbon\Carbon::parse($m->marriage_date)->format('d M Y') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        @if($m->is_current)
                                            <flux:button size="xs" variant="subtle" wire:click="updateMarriageStatus({{ $m->id }}, false)" title="Tandai sebagai Cerai Hidup">
                                                Ubah ke Cerai
                                            </flux:button>
                                        @else
                                            <flux:button size="xs" variant="subtle" wire:click="updateMarriageStatus({{ $m->id }}, true)" title="Tandai sebagai Menikah Aktif">
                                                Ubah ke Menikah
                                            </flux:button>
                                        @endif
                                        <flux:button size="xs" variant="ghost" wire:click="deleteMarriage({{ $m->id }})" wire:confirm="Hapus relasi pernikahan dengan {{ $sp->first_name }}?" class="!text-red-500" title="Hapus Relasi Pasangan">
                                            ✕
                                        </flux:button>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Biografi / Catatan --}}
            <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-zinc-800">
                <flux:textarea wire:model="bio" label="Catatan Bio" rows="2" placeholder="Isi catatan biografi atau riwayat singkat" />
            </div>

            {{-- Footer --}}
            <div class="flex justify-between items-center w-full pt-4 border-t border-gray-100 dark:border-zinc-800">
                <div class="flex gap-2">
                    @if($memberId)
                        <flux:dropdown>
                            <flux:button variant="subtle" icon="plus" size="sm">Tambah Relasi</flux:button>
                            <flux:menu>
                                <flux:menu.item wire:click="$dispatch('create-member', { targetId: {{ $memberId }}, relType: 'child_of' })" icon="user">Anak</flux:menu.item>
                                <flux:menu.item wire:click="$dispatch('create-member', { targetId: {{ $memberId }}, relType: 'spouse_of' })" icon="heart">Pasangan</flux:menu.item>
                                <flux:menu.item wire:click="$dispatch('create-member', { targetId: {{ $memberId }}, relType: 'ex_of' })">Mantan</flux:menu.item>
                                <flux:menu.item wire:click="$dispatch('create-member', { targetId: {{ $memberId }}, relType: 'parent_of' })" icon="users">Orang Tua</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>

                        <flux:button variant="danger" size="sm" wire:click="deleteMember" wire:confirm="Apakah Anda yakin ingin menghapus anggota ini?">Hapus</flux:button>
                    @endif
                </div>
                <div class="flex gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Tutup</flux:button>
                    </flux:modal.close>
                    @if(!$memberId && $hasReachedLimit)
                        <a href="{{ route('dashboard', ['upgrade_tree' => $treeId]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition-all shadow-xs">
                            <span class="material-symbols-outlined text-sm">workspace_premium</span>
                            <span>Upgrade ke Premium (Rp 5.000)</span>
                        </a>
                    @else
                        <flux:button type="submit" wire:loading.attr="disabled" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 dark:!bg-emerald-600 dark:hover:!bg-emerald-700 font-medium">
                            <span wire:loading.remove wire:target="save">Simpan</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Menyimpan...</span>
                            </span>
                        </flux:button>
                    @endif
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Detail View Modal --}}
    <flux:modal name="detail-modal" class="md:w-[28rem]">
        @if($viewMember)
            @php
                $vm = $viewMember;
                $vmAvatar = $vm->photo 
                    ? asset('storage/' . $vm->photo) 
                    : ($vm->avatar_id 
                        ? asset('images/avatar/' . $vm->avatar_id) 
                        : asset('images/no_profile_pic.jpg'));
            @endphp
            <div class="text-center">
                <div class="mx-auto w-24 h-24 rounded-full overflow-hidden border-4 {{ $vm->gender === 'female' ? 'border-pink-300' : 'border-teal-300' }} shadow-md">
                    <img src="{{ $vmAvatar }}" class="w-full h-full object-cover" />
                </div>
                <h3 class="mt-3 text-xl font-bold {{ $vm->gender === 'female' ? 'text-pink-600 dark:text-pink-400' : 'text-teal-600 dark:text-teal-400' }}">
                    {{ $vm->first_name }} {{ $vm->last_name }}
                </h3>
                @if($vm->birth_date)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Lahir {{ \Carbon\Carbon::parse($vm->birth_date)->format('d M Y') }}
                        @if($vm->birth_place) di {{ $vm->birth_place }} @endif
                    </p>
                @endif
                @if(!$vm->is_living && $vm->death_date)
                    <p class="text-sm text-red-500 mt-0.5">
                        Wafat {{ \Carbon\Carbon::parse($vm->death_date)->format('d M Y') }}
                    </p>
                @endif
            </div>

            @if(!$this->isPublic)
                <div class="mt-4 grid grid-cols-3 gap-2">
                    <button type="button"
                            wire:click="$dispatch('create-member', { targetId: {{ $vm->id }}, relType: 'parent_of' })"
                            class="p-2 bg-zinc-50 dark:bg-zinc-800/80 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-zinc-200 dark:border-zinc-700 rounded-xl text-center transition-colors">
                        <span class="text-base block">👨‍👩‍👧</span>
                        <span class="text-[11px] font-bold text-zinc-700 dark:text-zinc-300 block mt-0.5">+ Ortu</span>
                    </button>
                    <button type="button"
                            wire:click="$dispatch('create-member', { targetId: {{ $vm->id }}, relType: 'child_of' })"
                            class="p-2 bg-zinc-50 dark:bg-zinc-800/80 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-zinc-200 dark:border-zinc-700 rounded-xl text-center transition-colors">
                        <span class="text-base block">👶</span>
                        <span class="text-[11px] font-bold text-zinc-700 dark:text-zinc-300 block mt-0.5">+ Anak</span>
                    </button>
                    <button type="button"
                            wire:click="$dispatch('create-member', { targetId: {{ $vm->id }}, relType: 'spouse_of' })"
                            class="p-2 bg-zinc-50 dark:bg-zinc-800/80 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-zinc-200 dark:border-zinc-700 rounded-xl text-center transition-colors">
                        <span class="text-base block">💍</span>
                        <span class="text-[11px] font-bold text-zinc-700 dark:text-zinc-300 block mt-0.5">+ Pasangan</span>
                    </button>
                </div>
            @endif

            <div class="mt-4 space-y-2.5 text-sm">
                @if($vm->father_id)
                    @php $vmFather = \App\Models\Member::find($vm->father_id); @endphp
                    @if($vmFather)
                        <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-zinc-700">
                            <span class="text-gray-500">Ayah</span>
                            <span class="font-medium text-teal-600 dark:text-teal-400">{{ $vmFather->first_name }} {{ $vmFather->last_name }}</span>
                        </div>
                    @endif
                @endif
                @if($vm->mother_id)
                    @php $vmMother = \App\Models\Member::find($vm->mother_id); @endphp
                    @if($vmMother)
                        <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-zinc-700">
                            <span class="text-gray-500">Ibu</span>
                            <span class="font-medium text-pink-600 dark:text-pink-400">{{ $vmMother->first_name }} {{ $vmMother->last_name }}</span>
                        </div>
                    @endif
                @endif
                @if($vm->profession)
                    <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-zinc-700">
                        <span class="text-gray-500">Profesi</span>
                        <span class="font-medium dark:text-white">{{ $vm->profession }}</span>
                    </div>
                @endif
                @if($vm->address)
                    <div class="flex justify-between py-1.5 border-b border-gray-100 dark:border-zinc-700">
                        <span class="text-gray-500">Alamat</span>
                        <span class="font-medium dark:text-white text-right">{{ $vm->address }}</span>
                    </div>
                @endif
                @if($vm->bio)
                    <div class="py-1.5 border-b border-gray-100 dark:border-zinc-700">
                        <span class="text-gray-500 block mb-1">Bio</span>
                        <span class="dark:text-white">{{ $vm->bio }}</span>
                    </div>
                @endif
                @php
                    $vmSpouses = $vm->gender === 'male' 
                        ? $vm->marriagesAsHusband->map(fn($m) => ['spouse' => $m->wife, 'is_current' => $m->is_current, 'date' => $m->marriage_date])
                        : $vm->marriagesAsWife->map(fn($m) => ['spouse' => $m->husband, 'is_current' => $m->is_current, 'date' => $m->marriage_date]);
                @endphp
                @if($vmSpouses->filter(fn($s) => $s['spouse'])->isNotEmpty())
                    <div class="py-1.5 border-b border-gray-100 dark:border-zinc-700">
                        <span class="text-gray-500 block mb-1">Pasangan</span>
                        <div class="space-y-1.5">
                            @foreach($vmSpouses as $spInfo)
                                @if($spInfo['spouse'])
                                    <div class="flex items-center justify-between">
                                        <span class="dark:text-white">{{ $spInfo['spouse']->first_name }} {{ $spInfo['spouse']->last_name }}</span>
                                        @if(!$spInfo['is_current'])
                                            <span class="text-xs bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700 px-1.5 py-0.5 rounded font-medium">Cerai</span>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex justify-between items-center mt-5 pt-4 border-t border-gray-100 dark:border-zinc-800">
                <div>
                    @if(!$this->isPublic)
                        <flux:button variant="danger" size="sm" wire:click="deleteFromDetail" wire:confirm="Apakah Anda yakin ingin menghapus anggota ini?">Hapus</flux:button>
                    @endif
                </div>
                <div class="flex gap-2">
                    @php
                        $focusUrl = $this->isPublic
                            ? ($this->treeSlug ? route('tree.public.vertical', ['slug' => $this->treeSlug, 'member' => $vm->id]) : '#')
                            : route('tree.vertical', ['tree' => $this->treeId, 'member' => $vm->id]);
                    @endphp
                    <a href="{{ $focusUrl }}"
                       wire:navigate
                       class="inline-flex items-center gap-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 font-bold px-3 py-1.5 rounded-lg text-xs hover:bg-emerald-100 transition-colors">
                        <span>🧭 Fokus Silsilah</span>
                    </a>
                    @if(!$this->isPublic)
                        <flux:button variant="primary" size="sm" wire:click="editFromDetail">Edit</flux:button>
                    @else
                        <flux:modal.close>
                            <flux:button variant="ghost" size="sm">Tutup</flux:button>
                        </flux:modal.close>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>
</div>