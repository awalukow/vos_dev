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
                               ->orderBy('program_date')
                               ->take(2)
                               ->get();
        
        return view('home', ['events' => $events]);
    }
}
