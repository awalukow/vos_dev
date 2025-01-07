<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ScheduleModel;
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
        
        $events = ScheduleModel::where(function($query) use ($today) {
                                    $query->where('program_date', '>=', $today)
                                        ->orWhereNull('program_date');
                                })
                                ->where('isSundayService', false)
                                ->where('rowstatus', '>=', 0)
                                ->orderBy('program_date')
                                ->take(2)
                                ->get();


        $services = ScheduleModel::where('program_date', '>=', $today)
                               ->where('isSundayService', true)
                               ->where('rowstatus', '>= 0')
                               ->orderBy('program_date')
                               ->paginate(5);

        $pastEvents = PastEventsModel::where('rowstatus', '>= 0')
                                    ->take(3)
                                    ->orderBy('eventDate')
                                    ->get();
        
        return view('home', [
            'events' => $events,
            'services' => $services,
            'pastEvents' => $pastEvents
        ]);           
    }
}
