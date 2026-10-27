<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;

test('download template provides relational columns', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    Excel::fake();

    Livewire::actingAs($user)
        ->test('⚡import-members', ['treeId' => $tree->id])
        ->call('downloadTemplate');

    Excel::assertDownloaded('Template_Silsilah.xlsx');
});

test('import excel connects parent and marriage relationships', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    // Create a mock excel file
    $export = new class implements FromArray
    {
        public function array(): array
        {
            return [
                ['No', 'Nama Depan', 'Nama Belakang', 'Jenis Kelamin (L/P)', 'Status (Hidup/Wafat)', 'Tanggal Lahir (YYYY-MM-DD)', 'No Ayah', 'No Ibu'],
                [1, 'Kakek', 'Santoso', 'L', 'Wafat', '1940-01-01', null, null],
                [2, 'Nenek', 'Aminah', 'P', 'Wafat', '1945-05-10', null, null],
                [3, 'Ayah', 'Santoso', 'L', 'Hidup', '1970-07-20', 1, 2],
            ];
        }
    };

    $rawExcel = Excel::raw($export, Maatwebsite\Excel\Excel::XLSX);
    $uploadedFile = UploadedFile::fake()->createWithContent('test_import_tree.xlsx', $rawExcel);

    Livewire::actingAs($user)
        ->test('⚡import-members', ['treeId' => $tree->id])
        ->set('file', $uploadedFile)
        ->call('import');

    $kakek = Member::where('family_tree_id', $tree->id)->where('first_name', 'Kakek')->first();
    $nenek = Member::where('family_tree_id', $tree->id)->where('first_name', 'Nenek')->first();
    $ayah = Member::where('family_tree_id', $tree->id)->where('first_name', 'Ayah')->first();

    expect($kakek)->not->toBeNull();
    expect($nenek)->not->toBeNull();
    expect($ayah)->not->toBeNull();

    // Verify parent relationships
    expect($ayah->father_id)->toBe($kakek->id);
    expect($ayah->mother_id)->toBe($nenek->id);

    // Verify marriage automatically created
    $marriage = Marriage::where('husband_id', $kakek->id)->where('wife_id', $nenek->id)->first();
    expect($marriage)->not->toBeNull();
});
