<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\SecurityIncident;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            if (Auth::user()->role === 'admin') {
                return redirect()->route('system'); 
            } elseif (Auth::user()->role === 'employee') {
                return redirect()->route('inventory.index'); 
            }
        }
        return view('login.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $credentials['name'];
        $ipAddress = $request->ip();

        // Check if account is locked out
        $lockoutInfo = LoginAttempt::isLockedOut($username, $ipAddress);
        
        if ($lockoutInfo['locked']) {
            // Record the failed attempt (lockout violation)
            LoginAttempt::recordFailure($username, 'Account locked - too many failed attempts');
            
            // Record security incident for lockout
            SecurityIncident::recordLockout($username, $ipAddress);
            
            // Log lockout to system logs
            SystemLog::security(
                "Account lockout triggered for username: {$username}",
                'user.lockout',
                ['username' => $username, 'remaining_minutes' => $lockoutInfo['remaining_minutes']]
            );
            
            return back()->withErrors([
                'name' => "Too many failed login attempts. Please try again in {$lockoutInfo['remaining_minutes']} minute(s).",
            ])->withInput();
        }

        if (Auth::attempt($credentials)) {
            // Record successful login
            LoginAttempt::recordSuccess(Auth::id(), $username);
            
            // Log successful login to system logs
            SystemLog::audit(
                "User {$username} logged in successfully",
                'user.login',
                ['role' => Auth::user()->role]
            );
            
            $request->session()->regenerate();
            session(['first_login' => true]);
            
            if (Auth::user()->role === 'admin') {
                return redirect()->route('system');
            } elseif (Auth::user()->role === 'employee') {
                return redirect()->route('inventory.index');
            }
        }

        // Record failed login attempt
        LoginAttempt::recordFailure($username, 'Invalid credentials');
        
        // Log failed login attempt
        SystemLog::security(
            "Failed login attempt for username: {$username}",
            'user.login.failed',
            ['username' => $username]
        );
        
        // Get remaining attempts for user feedback
        $remainingAttempts = LoginAttempt::remainingAttempts($username, $ipAddress);
        
        // Record brute force incident when attempts are high
        $totalAttempts = 5 - $remainingAttempts;
        if ($totalAttempts >= 3) {
            SecurityIncident::recordBruteForce($username, $totalAttempts);
        }
        
        $errorMessage = 'The provided credentials do not match our records.';
        if ($remainingAttempts <= 3 && $remainingAttempts > 0) {
            $errorMessage .= " Warning: {$remainingAttempts} attempt(s) remaining before account lockout.";
        }

        return back()->withErrors([
            'name' => $errorMessage,
        ])->withInput();
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();
        $userName = Auth::user()?->name ?? 'Unknown';
        
        // Log logout event before destroying session
        SystemLog::audit(
            "User {$userName} logged out",
            'user.logout',
            ['user_id' => $userId]
        );
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
    
    public function viewProfile()
    {
        $user = Auth::user();
        return view('profile.view', compact('user'));
    }
}