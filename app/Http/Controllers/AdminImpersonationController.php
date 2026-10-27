<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminImpersonationController extends Controller
{
    /**
     * Start impersonating a non-admin user
     */
    public function impersonate(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        if ($user->isAdmin()) {
            return back()->with('error', 'Tidak dapat melakukan impersonasi pada akun sesama Administrator.');
        }

        if ($user->id === $admin->id) {
            return back()->with('error', 'Tidak dapat melakukan impersonasi pada akun sendiri.');
        }

        // Save original admin ID in session
        session(['impersonator_admin_id' => $admin->id]);

        AdminActivityLog::log(
            'impersonate_user',
            "Admin {$admin->name} mulai melakukan impersonasi sebagai {$user->name}",
            User::class,
            $user->id,
            ['target_email' => $user->email]
        );

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', "Mode Bantuan aktif: Anda sekarang login sebagai {$user->name}.");
    }

    /**
     * Leave impersonation and return to superadmin
     */
    public function leave(Request $request): RedirectResponse
    {
        if (! session()->has('impersonator_admin_id')) {
            return redirect()->route('dashboard');
        }

        $adminId = session('impersonator_admin_id');
        $admin = User::findOrFail($adminId);
        $currentUser = $request->user();

        AdminActivityLog::log(
            'leave_impersonation',
            "Admin {$admin->name} kembali dari sesi impersonasi pengguna {$currentUser->name}",
            User::class,
            $currentUser->id
        );

        session()->forget('impersonator_admin_id');

        Auth::login($admin);

        return redirect()->route('admin.users')->with('success', 'Berhasil kembali ke panel Administrator.');
    }
}
