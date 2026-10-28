<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Marriage;
use App\Models\Member;
use App\Models\TreeSuggestion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TreeSuggestionService
{
    /**
     * Approve a pending tree suggestion and apply changes to the tree.
     */
    public function approve(TreeSuggestion $suggestion, ?User $reviewer = null): Member
    {
        return DB::transaction(function () use ($suggestion, $reviewer) {
            $data = $suggestion->data ?? [];
            $target = $suggestion->target_member_id ? Member::find($suggestion->target_member_id) : null;
            $member = null;

            if ($suggestion->type === 'add_child') {
                $memberData = [
                    'family_tree_id' => $suggestion->family_tree_id,
                    'first_name' => $data['first_name'] ?? '',
                    'last_name' => $data['last_name'] ?? null,
                    'gender' => $data['gender'] ?? 'male',
                    'birth_date' => $data['birth_date'] ?? null,
                    'is_living' => isset($data['is_living']) ? (bool) $data['is_living'] : true,
                    'death_date' => $data['death_date'] ?? null,
                    'birth_place' => $data['birth_place'] ?? null,
                    'death_place' => $data['death_place'] ?? null,
                ];

                if ($target) {
                    if ($target->gender === 'female') {
                        $memberData['mother_id'] = $target->id;
                        $marriage = $target->marriagesAsWife()->where('is_current', true)->first()
                            ?? $target->marriagesAsWife()->first();
                        if ($marriage && $marriage->husband_id) {
                            $memberData['father_id'] = $marriage->husband_id;
                        }
                    } else {
                        $memberData['father_id'] = $target->id;
                        $marriage = $target->marriagesAsHusband()->where('is_current', true)->first()
                            ?? $target->marriagesAsHusband()->first();
                        if ($marriage && $marriage->wife_id) {
                            $memberData['mother_id'] = $marriage->wife_id;
                        }
                    }
                }

                $member = Member::create($memberData);
            } elseif ($suggestion->type === 'add_spouse') {
                $spouseGender = $data['gender'] ?? ($target && $target->gender === 'male' ? 'female' : 'male');
                $member = Member::create([
                    'family_tree_id' => $suggestion->family_tree_id,
                    'first_name' => $data['first_name'] ?? '',
                    'last_name' => $data['last_name'] ?? null,
                    'gender' => $spouseGender,
                    'birth_date' => $data['birth_date'] ?? null,
                    'is_living' => isset($data['is_living']) ? (bool) $data['is_living'] : true,
                    'death_date' => $data['death_date'] ?? null,
                    'birth_place' => $data['birth_place'] ?? null,
                    'death_place' => $data['death_place'] ?? null,
                ]);

                if ($target) {
                    if ($target->gender === 'female' || $spouseGender === 'male') {
                        Marriage::create([
                            'husband_id' => $member->id,
                            'wife_id' => $target->id,
                            'is_current' => true,
                        ]);
                    } else {
                        Marriage::create([
                            'husband_id' => $target->id,
                            'wife_id' => $member->id,
                            'is_current' => true,
                        ]);
                    }
                }
            } elseif ($suggestion->type === 'edit_member') {
                if (! $target) {
                    throw new \RuntimeException('Target member not found for edit suggestion.');
                }

                $fillableFields = [
                    'first_name',
                    'last_name',
                    'gender',
                    'birth_date',
                    'is_living',
                    'death_date',
                    'birth_place',
                    'death_place',
                    'bio',
                    'profession',
                    'company',
                    'address',
                    'member_notes',
                ];

                $updateData = [];
                foreach ($fillableFields as $field) {
                    if (array_key_exists($field, $data) && $data[$field] !== null) {
                        $updateData[$field] = $data[$field];
                    }
                }

                $target->update($updateData);
                $member = $target;
            }

            $suggestion->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer?->id ?? auth()->id(),
                'reviewed_at' => now(),
            ]);

            ActivityLog::log(
                $suggestion->family_tree_id,
                'suggestion_approved',
                "Menyetujui usulan data ({$suggestion->type}) dari {$suggestion->contributor_name}"
            );

            return $member ?? $target ?? throw new \RuntimeException('Unable to resolve member from suggestion');
        });
    }

    /**
     * Reject a pending tree suggestion.
     */
    public function reject(TreeSuggestion $suggestion, ?User $reviewer = null): void
    {
        $suggestion->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer?->id ?? auth()->id(),
            'reviewed_at' => now(),
        ]);

        ActivityLog::log(
            $suggestion->family_tree_id,
            'suggestion_rejected',
            "Menolak usulan data dari {$suggestion->contributor_name}"
        );
    }
}
