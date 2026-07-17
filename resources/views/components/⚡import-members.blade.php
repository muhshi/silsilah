<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\FamilyTree;
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
                    ['Budi', 'Santoso', 'L', 'Hidup', '1980-05-12'],
                    ['Siti', 'Aminah', 'P', 'Wafat', '1982-08-20'],
                ]);
            }
            public function headings(): array
            {
                return ['Nama Depan', 'Nama Belakang', 'Jenis Kelamin (L/P)', 'Status (Hidup/Wafat)', 'Tanggal Lahir (YYYY-MM-DD)'];
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
            public function __construct($treeId) {
                $this->treeId = $treeId;
            }
            public function array(array $array)
            {
                $count = 0;
                $isHeader = true;
                foreach ($array as $row) {
                    if ($isHeader) {
                        $isHeader = false;
                        continue;
                    }
                    if (count($row) < 3) continue;

                    $firstName = trim($row[0] ?? '');
                    if (empty($firstName)) continue;

                    $lastName = trim($row[1] ?? '');
                    $genderChar = strtoupper(trim($row[2] ?? 'L'));
                    $statusChar = strtoupper(trim($row[3] ?? 'HIDUP'));
                    
                    $birthDate = $row[4] ?? null;
                    $bd = null;
                    if ($birthDate) {
                        if (is_numeric($birthDate)) {
                            $bd = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($birthDate)->format('Y-m-d');
                        } else if (strtotime($birthDate)) {
                            $bd = date('Y-m-d', strtotime($birthDate));
                        }
                    }

                    $gender = ($genderChar === 'P' || $genderChar === 'PEREMPUAN' || $genderChar === 'F') ? 'female' : 'male';
                    $isLiving = ($statusChar === 'W' || $statusChar === 'WAFAT' || $statusChar === 'MATI' || $statusChar === 'N' || $statusChar === 'MENINGGAL') ? false : true;

                    Member::create([
                        'family_tree_id' => $this->treeId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'gender' => $gender,
                        'is_living' => $isLiving,
                        'birth_date' => $bd,
                    ]);
                    $count++;
                }
                session()->flash('success', "$count anggota berhasil diimpor.");
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
                    <li>Isi baris demi baris nama-nama keluarga Anda.</li>
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