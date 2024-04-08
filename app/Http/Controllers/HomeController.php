<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ScheduleModel;
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
        
        $events = ScheduleModel::where('program_date', '>=', $today)
                               ->where('isSundayService', false)
                               ->where('rowstatus', '>= 0')
                               ->orderBy('program_date')
                               ->take(2)
                               ->get();

        $services = ScheduleModel::where('program_date', '>=', $today)
                               ->where('isSundayService', true)
                               ->where('rowstatus', '>= 0')
                               ->orderBy('program_date')
                               ->paginate(10);
        
        return view('home'
            , ['events' => $events]
            , ['services' => $services]
        );
    }
}
