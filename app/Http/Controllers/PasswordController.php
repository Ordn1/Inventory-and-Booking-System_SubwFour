<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\PasswordHistory;
use App\Models\SystemLog;
use App\Rules\StrongPassword;

class PasswordController extends Controller
{
    /**
     * Show the change password form
     */
    public function showChangeForm()
    {
        $user = auth()->user();
        $isExpired = $user->isPasswordExpired();
        $daysRemaining = $user->daysUntilPasswordExpires();
        
        return view('auth.change-password', [
            'isExpired' => $isExpired,
            'daysRemaining' => $daysRemaining,
            'mustChange' => $user->must_change_password,
        ]);
    }

    /**
     * Handle password change request
     */
    public function change(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                new StrongPassword(),
            ],
        ]);

        $user = auth()->user();
        $newPassword = $request->password;

        // Check if new password was used before
        if (PasswordHistory::wasUsedBefore($user->id, $newPassword)) {
            $historyCount = config('security.password.history_count', 5);
            return back()->withErrors([
                'password' => "You cannot reuse any of your last {$historyCount} passwords.",
            ]);
        }

        // Check if new password is same as current
        if (Hash::check($newPassword, $user->password)) {
            return back()->withErrors([
                'password' => 'New password must be different from current password.',
            ]);
        }

        // Update password with history tracking
        $user->updatePassword($newPassword);

        // Log the password change
        SystemLog::security('Password changed successfully', 'password_change', [
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Your password has been changed successfully.');
    }

    /**
     * Force user to change password (admin action)
     */
    public function forceChange(Request $request, $userId)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $user = \App\Models\User::findOrFail($userId);
        $user->update(['must_change_password' => true]);

        SystemLog::audit('Forced password change for user', 'force_password_change', [
            'admin_id' => auth()->id(),
            'target_user_id' => $userId,
            'target_email' => $user->email,
        ]);

        return back()->with('success', "User {$user->email} will be required to change their password on next login.");
    }
}
