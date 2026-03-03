<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\PasswordHistory;
use App\Models\SystemLog;
use App\Rules\StrongPassword;
use App\Rules\EmployeePassword;

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
        $user = auth()->user();
        $isAjax = $request->ajax() || $request->wantsJson();
        
        // Use EmployeePassword rule for employees, StrongPassword for admins
        $passwordRule = $user->role === 'employee' 
            ? new EmployeePassword() 
            : new StrongPassword();

        $validator = validator($request->all(), [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                $passwordRule,
            ],
        ]);

        if ($validator->fails()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }
            return back()->withErrors($validator);
        }

        $newPassword = $request->password;

        // Check if new password was used before
        if (PasswordHistory::wasUsedBefore($user->id, $newPassword)) {
            $historyCount = config('security.password.history_count', 5);
            $error = "You cannot reuse any of your last {$historyCount} passwords.";
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => ['password' => [$error]]
                ], 422);
            }
            return back()->withErrors(['password' => $error]);
        }

        // Check if new password is same as current
        if (Hash::check($newPassword, $user->password)) {
            $error = 'New password must be different from current password.';
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => ['password' => [$error]]
                ], 422);
            }
            return back()->withErrors(['password' => $error]);
        }

        // Update password with history tracking
        $user->updatePassword($newPassword);

        // Delete the approved password change request (if employee)
        if ($user->role === 'employee') {
            \App\Models\PasswordChangeRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->delete();
        }

        // Log the password change
        SystemLog::security('Password changed successfully', 'password_change', [
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'message' => 'Your password has been changed successfully.'
            ]);
        }

        return redirect()->route('system')
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
