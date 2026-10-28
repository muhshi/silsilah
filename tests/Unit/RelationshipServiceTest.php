<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Services\RelationshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('calculates self relationship', function () {
    $tree = FamilyTree::factory()->create();
    $person = Member::factory()->create(['family_tree_id' => $tree->id, 'first_name' => 'Budi']);

    $service = new RelationshipService;
    $result = $service->calculate($person, $person);

    expect($result['title'])->toBe('Diri Sendiri')
        ->and($result['degree'])->toBe(0);
});

test('calculates direct spouse relationship', function () {
    $tree = FamilyTree::factory()->create();
    $husband = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Budi']);
    $wife = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'first_name' => 'Siti']);

    Marriage::create(['husband_id' => $husband->id, 'wife_id' => $wife->id, 'is_current' => true]);

    $service = new RelationshipService;
    $result1 = $service->calculate($husband, $wife);
    $result2 = $service->calculate($wife, $husband);

    expect($result1['title'])->toBe('Istri')
        ->and($result2['title'])->toBe('Suami');
});

test('calculates parent and child relationships', function () {
    $tree = FamilyTree::factory()->create();
    $father = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Ahmad']);
    $mother = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'first_name' => 'Fatimah']);
    $son = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'gender' => 'male',
        'first_name' => 'Budi',
        'father_id' => $father->id,
        'mother_id' => $mother->id,
    ]);
    $daughter = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'gender' => 'female',
        'first_name' => 'Dewi',
        'father_id' => $father->id,
        'mother_id' => $mother->id,
    ]);

    $service = new RelationshipService;

    // From son to father
    expect($service->calculate($son, $father)['title'])->toBe('Ayah Kandung');
    // From father to son
    expect($service->calculate($father, $son)['title'])->toBe('Anak Laki-laki (Kandung)');
    // From mother to daughter
    expect($service->calculate($mother, $daughter)['title'])->toBe('Anak Perempuan (Kandung)');
    // Sibling
    expect($service->calculate($son, $daughter)['title'])->toBe('Saudara Kandung (Saudari)');
});

test('calculates grandparent and grandchild relationships', function () {
    $tree = FamilyTree::factory()->create();
    $grandpa = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mochtar']);
    $father = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mahmudi', 'father_id' => $grandpa->id]);
    $grandson = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Fauzi', 'father_id' => $father->id]);

    $service = new RelationshipService;

    expect($service->calculate($grandson, $grandpa)['title'])->toBe('Kakek');
    expect($service->calculate($grandpa, $grandson)['title'])->toBe('Cucu Laki-laki');
});

test('calculates uncle aunt nephew and first cousin misanan relationships', function () {
    $tree = FamilyTree::factory()->create();
    $grandpa = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mochtar']);

    // Two brothers
    $brother1 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mahmudi', 'father_id' => $grandpa->id]);
    $brother2 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Husain', 'father_id' => $grandpa->id]);

    // Children of brothers (First Cousins / Misanan)
    $child1 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Ali', 'father_id' => $brother1->id]);
    $child2 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'first_name' => 'Zahra', 'father_id' => $brother2->id]);

    $service = new RelationshipService;

    // Uncle / Nephew
    expect($service->calculate($child1, $brother2)['title'])->toBe('Paman (Om / Paklik / Pakdhe)')
        ->and($service->calculate($brother2, $child1)['title'])->toBe('Keponakan');

    // First cousins (Misanan)
    $cousinRel = $service->calculate($child1, $child2);
    expect($cousinRel['title'])->toBe('Saudara Sepupu (Misanan)')
        ->and($cousinRel['cultural_term'])->toBe('Misanan');
});

test('calculates second cousin mindhoan relationships', function () {
    $tree = FamilyTree::factory()->create();
    $greatGrandpa = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Sastro']);

    // Two sons of great grandpa
    $grandpa1 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Kakek1', 'father_id' => $greatGrandpa->id]);
    $grandpa2 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Kakek2', 'father_id' => $greatGrandpa->id]);

    // Parents
    $parent1 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Ayah1', 'father_id' => $grandpa1->id]);
    $parent2 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Ayah2', 'father_id' => $grandpa2->id]);

    // Second cousins
    $secondCousin1 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Anak1', 'father_id' => $parent1->id]);
    $secondCousin2 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'first_name' => 'Anak2', 'father_id' => $parent2->id]);

    $service = new RelationshipService;
    $result = $service->calculate($secondCousin1, $secondCousin2);

    expect($result['title'])->toBe('Saudara Sepupu 2 Kali (Mindhoan)')
        ->and($result['cultural_term'])->toBe('Mindhoan')
        ->and($result['degree'])->toBe(6);
});

test('calculates great-grandparent and great-grandchild relationships', function () {
    $tree = FamilyTree::factory()->create();
    $greatGrandpa = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Sastro']);
    $grandpa = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Kakek', 'father_id' => $greatGrandpa->id]);
    $father = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Ayah', 'father_id' => $grandpa->id]);
    $child = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Cicit', 'father_id' => $father->id]);

    $service = new RelationshipService;

    expect($service->calculate($child, $greatGrandpa)['title'])->toBe('Buyut')
        ->and($service->calculate($child, $greatGrandpa)['cultural_term'])->toBe('Mbah Buyut')
        ->and($service->calculate($greatGrandpa, $child)['title'])->toBe('Cicit');
});

test('calculates in-law relationships', function () {
    $tree = FamilyTree::factory()->create();

    // Mertua (father-in-law) & Menantu (son-in-law)
    $fatherInLaw = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Suryo']);
    $wife = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'first_name' => 'Ratna', 'father_id' => $fatherInLaw->id]);
    $husband = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Bambang']);

    Marriage::create(['husband_id' => $husband->id, 'wife_id' => $wife->id, 'is_current' => true]);

    $service = new RelationshipService;

    // Husband to wife's father -> Mertua Laki-laki
    expect($service->calculate($husband, $fatherInLaw)['title'])->toBe('Mertua Laki-laki');
    // Father-in-law to daughter's husband -> Menantu Laki-laki
    expect($service->calculate($fatherInLaw, $husband)['title'])->toBe('Menantu Laki-laki');

    // Sibling-in-law (Saudara Ipar)
    $wifeBrother = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Doni', 'father_id' => $fatherInLaw->id]);
    expect($service->calculate($husband, $wifeBrother)['title'])->toBe('Saudara Ipar');

    // Besan
    $husbandFather = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Hartono']);
    $husband->update(['father_id' => $husbandFather->id]);

    expect($service->calculate($fatherInLaw, $husbandFather)['title'])->toBe('Besan')
        ->and($service->calculate($husbandFather, $fatherInLaw)['title'])->toBe('Besan');
});

test('handles unconnected members', function () {
    $tree = FamilyTree::factory()->create();
    $personA = Member::factory()->create(['family_tree_id' => $tree->id, 'first_name' => 'OrangA']);
    $personB = Member::factory()->create(['family_tree_id' => $tree->id, 'first_name' => 'OrangB']);

    $service = new RelationshipService;
    $result = $service->calculate($personA, $personB);

    expect($result['title'])->toBe('Kerabat Terhubung')
        ->and($result['degree'])->toBe(99);
});
