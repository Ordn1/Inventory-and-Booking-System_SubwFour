<?php

namespace App\Http\Controllers;

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
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            session(['first_login' => true]);
            if (Auth::user()->role === 'admin') {
                return redirect()->route('system');
            } elseif (Auth::user()->role === 'employee') {
                return redirect()->route('inventory.index');
            }
        }

        return back()->withErrors([
            'name' => 'The provided credentials do not match our records.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
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