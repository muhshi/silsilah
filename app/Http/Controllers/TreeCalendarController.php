<?php

namespace App\Http\Controllers;

use App\Models\FamilyTree;
use App\Services\FamilyReminderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TreeCalendarController extends Controller
{
    public function __construct(
        protected FamilyReminderService $reminderService
    ) {}

    /**
     * Serve RFC 5545 iCalendar (.ics) feed for upcoming tree events.
     */
    public function feed(Request $request, string|int|FamilyTree $tree): Response
    {
        $treeModel = $this->resolveTree($tree);

        // Authorization check:
        // Public trees can be subscribed by anyone (unless protected by view_password).
        // Private trees require authenticated tree owner/collaborator or admin.
        if (! $treeModel->is_public) {
            if (! auth()->check()) {
                abort(403, 'Akses kalender silsilah privat memerlukan login.');
            }

            $user = auth()->user();
            $hasAccess = $user->isAdmin()
                || $treeModel->users()->where('users.id', $user->id)->exists();

            if (! $hasAccess) {
                abort(403, 'Anda tidak memiliki hak akses ke kalender silsilah ini.');
            }
        } elseif (! empty($treeModel->view_password)) {
            $user = auth()->user();
            $hasAccess = ($user && ($user->isAdmin() || $treeModel->users()->where('users.id', $user->id)->exists()))
                || session("tree_unlocked_{$treeModel->id}");

            if (! $hasAccess) {
                abort(403, 'Pohon dilindungi kata sandi.');
            }
        }

        $icsContent = $this->reminderService->generateIcsFeed($treeModel);
        $filename = "silsilah-{$treeModel->slug}.ics";

        return response($icsContent, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Alias for feed() to maintain backwards/plan compatibility.
     */
    public function exportIcs(Request $request, string|int|FamilyTree $tree): Response
    {
        return $this->feed($request, $tree);
    }

    /**
     * Resolve FamilyTree model by slug or ID.
     */
    protected function resolveTree(string|int|FamilyTree $tree): FamilyTree
    {
        if ($tree instanceof FamilyTree) {
            return $tree;
        }

        if (is_numeric($tree)) {
            return FamilyTree::where('id', $tree)->first()
                ?? FamilyTree::where('slug', $tree)->firstOrFail();
        }

        return FamilyTree::where('slug', $tree)->first()
            ?? FamilyTree::where('id', $tree)->firstOrFail();
    }
}
