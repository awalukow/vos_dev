<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;          // ← use the portal Schedule model (same table)
use App\Models\PastEventsModel;
use Illuminate\Support\Carbon;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

class HomeController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index()
    {
        $today = Carbon::now();

        // Non-Sunday events (Konser etc.) — shown as event cards
        $events = Schedule::where(function ($query) use ($today) {
                        $query->where('program_date', '>=', $today)
                              ->orWhereNull('program_date');
                    })
                    ->where('isSundayService', false)
                    ->where('rowstatus', '>=', 0)
                    ->orderBy('program_date')
                    ->take(2)
                    ->get();

        // Sunday services — shown in the schedule table
        $services = Schedule::where('program_date', '>=', $today)
                        ->where('isSundayService', true)
                        ->where('rowstatus', '>=', 0)
                        ->orderBy('program_date')
                        ->paginate(5);

        $pastEvents = PastEventsModel::where('rowstatus', '>= 0')
                            ->take(3)
                            ->orderBy('eventDate')
                            ->get();

        return view('home', [
            'events'     => $events,
            'services'   => $services,
            'pastEvents' => $pastEvents,
        ]);
    }
}
