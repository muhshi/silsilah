<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $familyTree = \App\Models\FamilyTree::factory()->create([
            'name' => 'Keluarga Tester',
            'description' => 'Silsilah keluarga utama untuk testing otomatis.',
            'is_public' => true,
        ]);

        $familyTree->users()->attach($user->id, ['role' => 'owner']);

        // Gen 1: Kakek & Nenek
        $kakek = \App\Models\Member::factory()->create([
            'family_tree_id' => $familyTree->id,
            'gender' => 'male',
            'first_name' => 'Kakek Sugiono',
            'birth_date' => '1950-01-01',
            'is_living' => false,
            'avatar_id' => 18,
        ]);

        $nenek = \App\Models\Member::factory()->create([
            'family_tree_id' => $familyTree->id,
            'gender' => 'female',
            'first_name' => 'Nenek Sumiati',
            'birth_date' => '1955-05-05',
            'is_living' => true,
            'avatar_id' => 11,
        ]);

        \App\Models\Marriage::factory()->create([
            'husband_id' => $kakek->id,
            'wife_id' => $nenek->id,
            'marriage_date' => '1975-01-01',
        ]);

        // Gen 2: Ayah (Anak Kakek Nenek) & Ibu
        $ayah = \App\Models\Member::factory()->create([
            'family_tree_id' => $familyTree->id,
            'gender' => 'male',
            'first_name' => 'Budi Santoso',
            'father_id' => $kakek->id,
            'mother_id' => $nenek->id,
            'avatar_id' => 2,
        ]);

        $ibu = \App\Models\Member::factory()->create([
            'family_tree_id' => $familyTree->id,
            'gender' => 'female',
            'first_name' => 'Siti Aminah',
            'avatar_id' => 3,
        ]);

        \App\Models\Marriage::factory()->create([
            'husband_id' => $ayah->id,
            'wife_id' => $ibu->id,
            'marriage_date' => '2000-02-02',
        ]);

        // Gen 2: Saudara Perempuan Ayah (Anak Kakek Nenek)
        \App\Models\Member::factory()->create([
            'family_tree_id' => $familyTree->id,
            'gender' => 'female',
            'first_name' => 'Sri Wahyuni',
            'father_id' => $kakek->id,
            'mother_id' => $nenek->id,
            'avatar_id' => 4,
        ]);

        // Gen 3: Anak-anak dari Ayah dan Ibu
        \App\Models\Member::factory()->create([
            'family_tree_id' => $familyTree->id,
            'gender' => 'male',
            'first_name' => 'Dimas',
            'father_id' => $ayah->id,
            'mother_id' => $ibu->id,
            'avatar_id' => 5,
        ]);

        \App\Models\Member::factory()->create([
            'family_tree_id' => $familyTree->id,
            'gender' => 'female',
            'first_name' => 'Dinda',
            'father_id' => $ayah->id,
            'mother_id' => $ibu->id,
            'avatar_id' => 6,
        ]);
        
        // Buat folder storage untuk avatars jika belum ada dummy files
        $avatarPath = public_path('images/avatar');
        if (!file_exists($avatarPath)) {
            @mkdir($avatarPath, 0755, true);
        }
    }
}
