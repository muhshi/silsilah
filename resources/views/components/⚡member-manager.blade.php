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
    public $memberId;
    
    // Detail view
    public $viewMember = null;
    
    // Pribadi
    public $first_name = '';
    public $last_name = '';
    public $gender = 'male';
    public $is_living = true;
    public $birth_date = '';
    public $death_date = '';
    public $birth_place = '';
    public $marriage_date = '';
    public $member_notes = '';
    public $profession = '';
    public $bio = '';
    
    // Photo & Avatar
    public $photo;
    public $photo_url = '';
    public $avatar_id;

    // Relation context
    public $relType = 'child_of'; // child_of, spouse_of, ex_of, parent_of
    public $targetMemberId = null;

    // Manual parent selectors
    public $father_id;
    public $mother_id;

    public function getAvailableMembersProperty()
    {
        return Member::where('family_tree_id', $this->treeId)
            ->when($this->memberId, fn($q) => $q->where('id', '!=', $this->memberId))
            ->orderBy('first_name')->get();
    }

    #[On('create-member')]
    public function createMember($targetId = null, $relType = 'child_of')
    {
        $this->resetForm();
        $this->targetMemberId = $targetId;
        $this->relType = $relType;
        $this->is_living = true;
        $this->gender = 'male';

        Flux::modal('member-modal')->show();
    }

    #[On('edit-member')]
    public function editMember($id)
    {
        $this->resetForm();
        $this->memberId = $id;
        
        $member = Member::findOrFail($id);
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
        $this->member_notes = $member->member_notes ?? '';
        
        // If photo is a URL, put it in photo_url field
        if ($member->photo && str_starts_with($member->photo, 'http')) {
            $this->photo_url = $member->photo;
        }

        Flux::modal('member-modal')->show();
    }

    public function save()
    {
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

        $data = [
            'family_tree_id' => $this->treeId,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'is_living' => $this->is_living,
            'birth_date' => $this->birth_date ?: null,
            'death_date' => $this->death_date ?: null,
            'birth_place' => $this->birth_place,
            'profession' => $this->profession,
            'bio' => $this->bio,
            'father_id' => $this->father_id ?: null,
            'mother_id' => $this->mother_id ?: null,
            'avatar_id' => $this->avatar_id ?: null,
        ];

        // Auto-assign avatar if none selected and no photo/url
        if (!$this->memberId && !$this->avatar_id && !$this->photo && !$this->photo_url) {
            $data['avatar_id'] = $this->gender === 'male' ? rand(1, 9) : rand(10, 18);
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
                    } else {
                        $data['mother_id'] = $target->id;
                    }
                }
            }
        }

        if ($this->memberId) {
            $member = Member::findOrFail($this->memberId);
            $member->update($data);
        } else {
            $member = Member::create($data);
            
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
                    $husband_id = $target->gender === 'male' ? $target->id : $member->id;
                    $wife_id = $target->gender === 'female' ? $target->id : $member->id;
                    
                    Marriage::create([
                        'husband_id' => $husband_id,
                        'wife_id' => $wife_id,
                        'marriage_date' => $this->marriage_date ?: now(),
                        'is_current' => $this->relType === 'spouse_of',
                    ]);
                }
            }
        }

        Flux::modal('member-modal')->close();
        $this->dispatch('refresh-tree');
    }

    public function deleteMember()
    {
        if ($this->memberId) {
            Marriage::where('husband_id', $this->memberId)->orWhere('wife_id', $this->memberId)->delete();
            Member::findOrFail($this->memberId)->delete();
            Flux::modal('member-modal')->close();
            $this->dispatch('refresh-tree');
        }
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
        Marriage::where('husband_id', $id)->orWhere('wife_id', $id)->delete();
        Member::findOrFail($id)->delete();
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
            'birth_date', 'death_date', 'birth_place', 'profession', 'bio',
            'photo', 'photo_url', 'avatar_id', 'father_id', 'mother_id', 'relType', 'targetMemberId',
            'marriage_date', 'member_notes'
        ]);
    }
};
?>

<div>
    <flux:modal name="member-modal" class="md:w-[40rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $memberId ? 'Edit Anggota' : 'Tambah Anggota Baru' }}</flux:heading>
                @if($targetMemberId)
                    @php
                        $targetName = \App\Models\Member::find($targetMemberId)?->first_name ?? '';
                    @endphp
                    <flux:subheading>
                        Menambahkan anggota baru terkait dengan <strong>{{ $targetName }}</strong>
                    </flux:subheading>
                @else
                    <flux:subheading>Masukkan detail informasi anggota silsilah.</flux:subheading>
                @endif
            </div>

            {{-- Tab: Pribadi --}}
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="first_name" label="Nama Depan *" placeholder="Isi nama depan" required />
                    <flux:input wire:model="last_name" label="Nama Belakang" placeholder="Isi nama belakang" />
                </div>

                {{-- Jenis Hubungan (only for new member with target) --}}
                @if(!$memberId && $targetMemberId)
                    <flux:select wire:model="relType" label="Jenis Hubungan">
                        <flux:select.option value="child_of">Anak</flux:select.option>
                        <flux:select.option value="spouse_of">Pasangan</flux:select.option>
                        <flux:select.option value="ex_of">Mantan</flux:select.option>
                        <flux:select.option value="parent_of">Orang Tua</flux:select.option>
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

                <flux:input wire:model="member_notes" label="Keterangan" placeholder="Info tambahan, tampil di diagram" />

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
                <div class="form-avatar">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 block mb-2">Atau pilih ikon</label>
                    <div class="form-inline">
                        @for($i = 1; $i <= 18; $i++)
                            <div class="form-group">
                                <input type="radio" name="avatar" wire:model="avatar_id" value="{{ $i }}" id="sradioe{{ $i }}" class="choice image" />
                                <label for="sradioe{{ $i }}"><b><img src="https://app.pohonkeluarga.com/images/avatar/{{ $i }}.jpg" alt="Avatar {{ $i }}" /></b></label>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- Relasi Manual (Edit mode only) --}}
            @if($memberId || (!$targetMemberId && $this->availableMembers->count() > 0))
                <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-zinc-800">
                    <div class="grid grid-cols-2 gap-4">
                        <flux:select wire:model="father_id" label="Ayah" placeholder="-- Tidak Diketahui --">
                            <flux:select.option value="">-- Tidak Diketahui --</flux:select.option>
                            @foreach($this->availableMembers->where('gender', 'male') as $m)
                                <flux:select.option value="{{ $m->id }}">{{ $m->first_name }} {{ $m->last_name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="mother_id" label="Ibu" placeholder="-- Tidak Diketahui --">
                            <flux:select.option value="">-- Tidak Diketahui --</flux:select.option>
                            @foreach($this->availableMembers->where('gender', 'female') as $m)
                                <flux:select.option value="{{ $m->id }}">{{ $m->first_name }} {{ $m->last_name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            @endif

            {{-- Biografi --}}
            <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-zinc-800">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="birth_place" label="Tempat Lahir" placeholder="Isi tempat lahir" />
                    <flux:input wire:model="profession" label="Profesi" placeholder="Isi profesi" />
                </div>
                <flux:textarea wire:model="bio" label="Catatan Bio" rows="2" placeholder="Isi catatan bio" />
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
                    <flux:button type="submit" variant="primary">Submit</flux:button>
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
                        ? 'https://app.pohonkeluarga.com/images/avatar/' . $vm->avatar_id . '.jpg' 
                        : 'https://app.pohonkeluarga.com/images/no_profile_pic.jpg');
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

            <div class="mt-5 space-y-3 text-sm">
                @if($vm->profession)
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-zinc-700">
                        <span class="text-gray-500">Profesi</span>
                        <span class="font-medium dark:text-white">{{ $vm->profession }}</span>
                    </div>
                @endif
                @if($vm->bio)
                    <div class="py-2 border-b border-gray-100 dark:border-zinc-700">
                        <span class="text-gray-500 block mb-1">Bio</span>
                        <span class="dark:text-white">{{ $vm->bio }}</span>
                    </div>
                @endif
            </div>

            <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-100 dark:border-zinc-800">
                <div class="flex gap-2">
                    <flux:button variant="danger" size="sm" wire:click="deleteFromDetail" wire:confirm="Apakah Anda yakin ingin menghapus anggota ini?">Hapus</flux:button>
                </div>
                <div class="flex gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" size="sm">Tutup</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" size="sm" wire:click="editFromDetail">Edit</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>