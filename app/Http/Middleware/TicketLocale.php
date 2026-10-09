<?php
namespace App\Http\Middleware;

use Closure;

class TicketLocale
{
    public function handle($request, Closure $next)
    {
        app()->setLocale($request->session()->get('ticket_locale', 'id'));
        return $next($request);
    }
}
