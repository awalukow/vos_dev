<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class ChangePasswordController extends Controller
{
    public function show()
    {
        $user = Auth::guard('portal')->user();

        // If not flushed, no need to be here
        if (! $user->is_password_flushed) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.auth.change-password', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::guard('portal')->user();

        $request->validate([
            'password'              => ['required', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        $user->update([
            'password'            => $request->password,
            'is_password_flushed' => false,
        ]);

        return redirect()->route('portal.dashboard')
            ->with('success', 'Password changed successfully. Welcome!');
    }
}
