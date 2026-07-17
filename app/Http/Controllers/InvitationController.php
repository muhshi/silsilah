<?php

namespace App\Http\Controllers;

use App\Models\TreeInvitation;
use App\Models\FamilyTree;
use App\Models\User;
use App\Notifications\CollaboratorJoinedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    public function accept(Request $request, $token)
    {
        $invitation = TreeInvitation::where('token', $token)->where('status', 'pending')->first();

        if (!$invitation) {
            return redirect()->route('home')->with('error', 'Tautan undangan tidak valid atau sudah kedaluwarsa.');
        }

        if (!Auth::check()) {
            // Save intended url to redirect after Google SSO
            session(['url.intended' => url()->current()]);
            
            // Redirect to login page with a message
            return redirect()->route('login')->with('info', 'Silakan masuk dengan akun Google untuk menerima undangan kolaborasi.');
        }

        $user = Auth::user();

        // Check if email matches
        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return redirect()->route('dashboard')->with('error', 'Email akun Anda tidak cocok dengan email undangan.');
        }

        $tree = $invitation->familyTree;

        // Check if already in pivot
        $existingPivot = $tree->users()->where('user_id', $user->id)->first();
        if (!$existingPivot) {
            $tree->users()->attach($user->id, ['role' => $invitation->role]);
        } else if ($existingPivot->pivot->role !== 'owner') {
            $tree->users()->updateExistingPivot($user->id, ['role' => $invitation->role]);
        }

        // Update invitation status
        $invitation->update(['status' => 'accepted']);

        // Notify Tree Owner
        $owner = $tree->users()->wherePivot('role', 'owner')->first();
        if ($owner && $owner->id !== $user->id) {
            $owner->notify(new CollaboratorJoinedNotification($user, $tree));
        }

        return redirect()->route('tree.show', $tree->id)->with('success', 'Berhasil bergabung sebagai kolaborator!');
    }
}
