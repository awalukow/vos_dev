<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('portal')->check()) {
            return redirect()->route('portal.dashboard');
        }
        return view('portal.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login'    => ['required', 'string'],  // email OR username
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $attempt = Auth::guard('portal')->attempt(
            [$field => $credentials['login'], 'password' => $credentials['password'], 'is_active' => true],
            $request->boolean('remember')
        );

        if (!$attempt) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'Invalid credentials or inactive account.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard'))
            ->with('success', 'Welcome back, ' . Auth::guard('portal')->user()->name . '!');
    }

    public function logout(Request $request)
    {
        Auth::guard('portal')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')
            ->with('success', 'You have been logged out successfully.');
    }
}
