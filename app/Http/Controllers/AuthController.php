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
            } elseif (Auth::user()->role === 'security') {
                return redirect()->route('security.dashboard'); 
            } elseif (Auth::user()->role === 'employee') {
                return redirect()->route('employee.dashboard'); 
            }
        }
        return view('login.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['name', 'password']);
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
                ['username' => $username, 'remaining_seconds' => $lockoutInfo['remaining_seconds']]
            );
            
            $remainingSeconds = $lockoutInfo['remaining_seconds'];
            
            return response()->json([
                'success' => false,
                'message' => "Too many failed login attempts. Please try again in {$remainingSeconds} second(s).",
            ], 429);
        }

        // Validate credentials without logging in
        if (Auth::validate($credentials)) {
            // Check if user account is active
            $user = \App\Models\User::where('name', $username)->first();
            
            if (!$user->isActive()) {
                SystemLog::security(
                    "Deactivated account login attempt: {$username}",
                    'user.login.deactivated',
                    ['username' => $username]
                );
                
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact the administrator.',
                ], 403);
            }

            // Store credentials in session for captcha verification step
            session(['pending_login' => [
                'name' => $username,
                'password' => $credentials['password'],
                'ip' => $ipAddress,
            ]]);

            return response()->json([
                'success' => true,
                'require_captcha' => true,
                'message' => 'Credentials verified. Please complete captcha.',
            ]);
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
        $maxAttempts = config('security.login.max_attempts', 3);
        $remainingAttempts = LoginAttempt::remainingAttempts($username, $ipAddress);
        
        // Record brute force incident when attempts are high
        $totalAttempts = $maxAttempts - $remainingAttempts;
        if ($totalAttempts >= 2) {
            SecurityIncident::recordBruteForce($username, $totalAttempts);
        }
        
        $errorMessage = 'The provided credentials do not match our records.';
        if ($remainingAttempts <= 2 && $remainingAttempts > 0) {
            $errorMessage .= " Warning: {$remainingAttempts} attempt(s) remaining before account lockout.";
        }

        return response()->json([
            'success' => false,
            'message' => $errorMessage,
        ], 401);
    }

    /**
     * Verify captcha and complete login
     */
    public function verifyCaptcha(Request $request)
    {
        $request->validate([
            'captcha' => 'required|captcha',
        ], [
            'captcha.captcha' => 'Invalid captcha code. Please try again.',
        ]);

        $pendingLogin = session('pending_login');

        if (!$pendingLogin) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please login again.',
            ], 400);
        }

        $credentials = [
            'name' => $pendingLogin['name'],
            'password' => $pendingLogin['password'],
        ];

        if (Auth::attempt($credentials)) {
            // Clear pending login from session
            session()->forget('pending_login');

            // Record successful login
            LoginAttempt::recordSuccess(Auth::id(), $pendingLogin['name']);
            
            // Log successful login to system logs
            SystemLog::audit(
                "User {$pendingLogin['name']} logged in successfully",
                'user.login',
                ['role' => Auth::user()->role]
            );
            
            $request->session()->regenerate();
            session(['first_login' => true]);
            
            $role = Auth::user()->role;
            if ($role === 'admin') {
                $redirectUrl = route('system');
            } elseif ($role === 'security') {
                $redirectUrl = route('security.dashboard');
            } else {
                $redirectUrl = route('employee.dashboard');
            }

            return response()->json([
                'success' => true,
                'redirect' => $redirectUrl,
            ]);
        }

        // Clear pending login on failure
        session()->forget('pending_login');

        return response()->json([
            'success' => false,
            'message' => 'Authentication failed. Please try again.',
        ], 401);
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