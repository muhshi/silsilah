<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Flux\Flux;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

new class extends Component
{
    use WithFileUploads;

    public $treeId;
    public $file;
    public $showModal = false;

    public function mount($treeId)
    {
        $this->treeId = $treeId;
    }

    #[On('open-import-modal')]
    public function openModal()
    {
        $this->showModal = true;
    }

    public function downloadTemplate()
    {
        $export = new class implements FromCollection, WithHeadings {
            public function collection()
            {
                return collect([
                    [1, 'Budi', 'Santoso', 'L', 'Wafat', '1950-05-12', null, null],
                    [2, 'Siti', 'Aminah', 'P', 'Wafat', '1954-08-20', null, null],
                    [3, 'Agus', 'Santoso', 'L', 'Hidup', '1978-02-15', 1, 2],
                    [4, 'Dewi', 'Lestari', 'P', 'Hidup', '1980-11-25', null, null],
                    [5, 'Rian', 'Santoso', 'L', 'Hidup', '2005-09-01', 3, 4],
                ]);
            }

            public function headings(): array
            {
                return ['No', 'Nama Depan', 'Nama Belakang', 'Jenis Kelamin (L/P)', 'Status (Hidup/Wafat)', 'Tanggal Lahir (YYYY-MM-DD)', 'No Ayah', 'No Ibu'];
            }
        };

        return Excel::download($export, 'Template_Silsilah.xlsx');
    }

    public function import()
    {
        Gate::authorize('editMembers', FamilyTree::findOrFail($this->treeId));

        $this->validate([
            'file' => 'required|file|max:5120',
        ]);

        $import = new class($this->treeId) implements ToArray {
            public $treeId;

            public function __construct($treeId)
            {
                $this->treeId = $treeId;
            }

            public function array(array $array)
            {
                $tree = FamilyTree::find($this->treeId);

                // Count prospective valid rows
                $prospectiveCount = 0;
                foreach ($array as $idx => $r) {
                    if ($idx === 0 || count($r) < 3) {
                        continue;
                    }
                    $isNewFmt = is_numeric($r[0] ?? null);
                    $fn = trim((string) ($isNewFmt ? ($r[1] ?? '') : ($r[0] ?? '')));
                    if (! empty($fn)) {
                        $prospectiveCount++;
                    }
                }

                if ($tree && ! $tree->canAddMembers($prospectiveCount)) {
                    $current = $tree->members()->count();
                    $rem = $tree->remainingMemberSlots();
                    session()->flash('error', "Gagal import: Pohon gratis dibatasi maksimal 100 anggota. Pohon ini memiliki {$current} anggota (sisa {$rem} slot), sedangkan file berisi {$prospectiveCount} anggota. Silakan upgrade ke Pohon Premium seharga Rp 5.000 untuk import tanpa batas.");

                    return;
                }

                $count = 0;
                $isHeader = true;
                $createdMembersByRef = [];
                $pendingRelations = [];

                foreach ($array as $row) {
                    if ($isHeader) {
                        $isHeader = false;
                        continue;
                    }
                    if (count($row) < 3) {
                        continue;
                    }

                    // Check if row[0] is numeric "No" (new format) or string "Nama Depan" (legacy format)
                    $isNewFormat = is_numeric($row[0] ?? null);
                    $refNo = $isNewFormat ? (int) $row[0] : null;

                    $firstName = trim((string) ($isNewFormat ? ($row[1] ?? '') : ($row[0] ?? '')));
                    if (empty($firstName)) {
                        continue;
                    }

                    $lastName = trim((string) ($isNewFormat ? ($row[2] ?? '') : ($row[1] ?? '')));
                    $genderChar = strtoupper(trim((string) ($isNewFormat ? ($row[3] ?? 'L') : ($row[2] ?? 'L'))));
                    $statusChar = strtoupper(trim((string) ($isNewFormat ? ($row[4] ?? 'HIDUP') : ($row[3] ?? 'HIDUP'))));
                    $birthDate = $isNewFormat ? ($row[5] ?? null) : ($row[4] ?? null);

                    $fatherRef = ($isNewFormat && isset($row[6]) && is_numeric($row[6])) ? (int) $row[6] : null;
                    $motherRef = ($isNewFormat && isset($row[7]) && is_numeric($row[7])) ? (int) $row[7] : null;

                    $bd = null;
                    if ($birthDate) {
                        if (is_numeric($birthDate)) {
                            $bd = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($birthDate)->format('Y-m-d');
                        } elseif (strtotime((string) $birthDate)) {
                            $bd = date('Y-m-d', strtotime((string) $birthDate));
                        }
                    }

                    $gender = in_array($genderChar, ['P', 'PEREMPUAN', 'F', 'FEMALE'], true) ? 'female' : 'male';
                    $isLiving = ! in_array($statusChar, ['W', 'WAFAT', 'MATI', 'N', 'MENINGGAL'], true);

                    $member = Member::create([
                        'family_tree_id' => $this->treeId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'gender' => $gender,
                        'is_living' => $isLiving,
                        'birth_date' => $bd,
                    ]);

                    if ($refNo !== null) {
                        $createdMembersByRef[$refNo] = $member->id;
                    }

                    if ($fatherRef !== null || $motherRef !== null) {
                        $pendingRelations[] = [
                            'member' => $member,
                            'father_ref' => $fatherRef,
                            'mother_ref' => $motherRef,
                        ];
                    }

                    $count++;
                }

                // Pass 2: Connect parent relationships & create marriages
                foreach ($pendingRelations as $item) {
                    $updates = [];
                    $fatherId = null;
                    $motherId = null;

                    if ($item['father_ref'] && isset($createdMembersByRef[$item['father_ref']])) {
                        $fatherId = $createdMembersByRef[$item['father_ref']];
                        $updates['father_id'] = $fatherId;
                    }

                    if ($item['mother_ref'] && isset($createdMembersByRef[$item['mother_ref']])) {
                        $motherId = $createdMembersByRef[$item['mother_ref']];
                        $updates['mother_id'] = $motherId;
                    }

                    if (! empty($updates)) {
                        $item['member']->update($updates);
                    }

                    if ($fatherId && $motherId) {
                        Marriage::firstOrCreate([
                            'husband_id' => $fatherId,
                            'wife_id' => $motherId,
                        ], [
                            'is_current' => true,
                        ]);
                    }
                }

                session()->flash('success', "$count anggota berhasil diimpor beserta relasi kekerabatannya.");
            }
        };

        Excel::import($import, $this->file);
        
        $this->reset('file');
        $this->showModal = false;
        $this->dispatch('refresh-tree');
    }
};
?>

<div>
    <flux:modal wire:model="showModal" class="md:w-[32rem]">
        <form wire:submit="import" class="space-y-6">
            <div>
                <flux:heading size="lg">Import Data Silsilah</flux:heading>
                <flux:subheading>Upload file Excel (.xlsx) untuk menambahkan anggota keluarga sekaligus.</flux:subheading>
            </div>

            <div class="bg-blue-50 text-blue-800 p-4 rounded-xl border border-blue-200 text-sm dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800">
                <p class="font-bold mb-2">Cara Penggunaan:</p>
                <ol class="list-decimal pl-4 space-y-1">
                    <li>Download template Excel di bawah ini.</li>
                    <li>Isi baris demi baris nama keluarga Anda. Gunakan kolom <strong>No Ayah</strong> dan <strong>No Ibu</strong> untuk menghubungkan anak ke orang tua secara otomatis.</li>
                    <li>Simpan dan pastikan format file tetap .xlsx</li>
                    <li>Upload file Excel tersebut ke form ini.</li>
                </ol>
                <div class="mt-4">
                    <flux:button size="sm" wire:click="downloadTemplate" icon="arrow-down-tray" class="!bg-white dark:!bg-zinc-800 !text-zinc-800 dark:!text-zinc-200 hover:!bg-zinc-50 dark:hover:!bg-zinc-700 border-gray-200">Download Template Excel</flux:button>
                </div>
            </div>

            <div>
                <flux:input type="file" wire:model="file" accept=".xlsx,.xls" required />
                <div wire:loading wire:target="file" class="text-xs text-primary mt-1">Mengunggah...</div>
                @error('file') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">Import Sekarang</flux:button>
            </div>
        </form>
    </flux:modal>
</div>