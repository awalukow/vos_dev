<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;
class CustomerAuthenticate {
    public function handle($request, Closure $next) {
        $customer = Auth::guard('customer')->user();
        if (!$customer) return redirect()->guest(route('tickets.login'));
        if (!$customer->is_active) {
            Auth::guard('customer')->logout();
            return redirect()->route('tickets.login')->withErrors(['email'=>'This account is inactive. Please contact the organizer.']);
        }
        if (!$customer->email_verified_at) return redirect()->route('tickets.verify');
        return $next($request);
    }
}