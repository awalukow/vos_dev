<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalAuthenticate
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Auth::guard('portal')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('portal.login')
                ->with('error', 'Please sign in to access the portal.');
        }

        $user = Auth::guard('portal')->user();

        if (! $user->is_active) {
            Auth::guard('portal')->logout();
            $request->session()->invalidate();
            return redirect()->route('portal.login')
                ->with('error', 'Your account has been deactivated. Contact an administrator.');
        }

        // If password was reset by admin, force user to change it
        if ($user->is_password_flushed) {
            $changePasswordRoute = route('portal.password.change');
            // Allow the change-password routes through so user is not caught in a redirect loop
            if (! $request->routeIs('portal.password.change') && ! $request->routeIs('portal.password.update')) {
                return redirect($changePasswordRoute);
            }
        }

        return $next($request);
    }
}
