<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\FamilyTree;
use App\Models\Member;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Flux\Flux;

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
        $headers = [
            'Content-Type' => 'text/csv',
        ];
        $columns = ['Nama Depan', 'Nama Belakang', 'Jenis Kelamin (L/P)', 'Status (Hidup/Wafat)', 'Tanggal Lahir (YYYY-MM-DD)'];
        
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, ['Budi', 'Santoso', 'L', 'Hidup', '1980-05-12']);
            fputcsv($file, ['Siti', 'Aminah', 'P', 'Wafat', '1982-08-20']);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Template_Silsilah.csv"',
        ]);
    }

    public function import()
    {
        Gate::authorize('editMembers', FamilyTree::findOrFail($this->treeId));

        $this->validate([
            'file' => 'required|file|max:2048', // Allow any file temporarily because csv mimes check can be strict
        ]);

        $path = $this->file->getRealPath();
        $file = fopen($path, 'r');
        
        // Read header
        $header = fgetcsv($file);
        
        $count = 0;
        while ($row = fgetcsv($file)) {
            // Expected: Nama Depan, Nama Belakang, L/P, Hidup/Wafat, TglLahir
            if (count($row) < 3) continue;

            $firstName = trim($row[0]);
            $lastName = trim($row[1] ?? '');
            $genderChar = strtoupper(trim($row[2] ?? 'L'));
            $statusChar = strtoupper(trim($row[3] ?? 'HIDUP'));
            $birthDate = trim($row[4] ?? '');

            if (empty($firstName)) continue;

            $gender = ($genderChar === 'P' || $genderChar === 'PEREMPUAN' || $genderChar === 'F') ? 'female' : 'male';
            $isLiving = ($statusChar === 'W' || $statusChar === 'WAFAT' || $statusChar === 'MATI' || $statusChar === 'N' || $statusChar === 'MENINGGAL') ? false : true;

            $bd = null;
            if ($birthDate && strtotime($birthDate)) {
                $bd = date('Y-m-d', strtotime($birthDate));
            }

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
        fclose($file);

        $this->reset('file');
        $this->showModal = false;
        
        session()->flash('success', "$count anggota berhasil diimpor.");
        $this->dispatch('refresh-tree');
    }
};
?>

<div>
    <flux:modal wire:model="showModal" class="md:w-[32rem]">
        <form wire:submit="import" class="space-y-6">
            <div>
                <flux:heading size="lg">Import Data Silsilah</flux:heading>
                <flux:subheading>Upload file CSV untuk menambahkan anggota keluarga sekaligus.</flux:subheading>
            </div>

            <div class="bg-blue-50 text-blue-800 p-4 rounded-xl border border-blue-200 text-sm dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800">
                <p class="font-bold mb-2">Cara Penggunaan:</p>
                <ol class="list-decimal pl-4 space-y-1">
                    <li>Download template CSV di bawah ini.</li>
                    <li>Isi data keluarga menggunakan Excel atau Google Sheets.</li>
                    <li>Simpan atau Download kembali dalam format CSV (Comma Separated Values).</li>
                    <li>Upload file CSV tersebut ke form ini.</li>
                </ol>
                <div class="mt-4">
                    <flux:button size="sm" wire:click="downloadTemplate" icon="arrow-down-tray" class="!bg-white dark:!bg-zinc-800 !text-zinc-800 dark:!text-zinc-200 hover:!bg-zinc-50 dark:hover:!bg-zinc-700 border-gray-200">Download Template CSV</flux:button>
                </div>
            </div>

            <div>
                <flux:input type="file" wire:model="file" accept=".csv" required />
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