<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;
class TicketStaff {
    public function handle($request, Closure $next, $access = 'admin') {
        $user = Auth::guard('portal')->user();
        abort_unless($user && ($user->isAdministrator() || $user->isAdm2() || (in_array($access,['review','fees']) && $user->hasRole('ticket_operator')) || ($access==='fees' && $user->hasRole('pengurus'))),403);
        return $next($request);
    }
}
