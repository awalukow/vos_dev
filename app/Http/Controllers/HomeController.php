<?php

namespace App\Http\Controllers;

use App\Models\PastEventsModel;
use App\Models\Schedule;
use App\Models\TicketEvent;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        // Portal dates are Jakarta wall time; ticketing stores UTC.
        $today = Carbon::now('Asia/Jakarta')->startOfDay()->format('Y-m-d H:i:s');
        $schedules = Schedule::where('rowstatus', 1)
            ->where(function ($query) use ($today) {
                $query->where('program_date', '>=', $today)->orWhereNull('program_date');
            })->get()->map(function ($schedule) {
                return (object) [
                    'event_name' => $schedule->event_name,
                    'event_detail' => $schedule->event_detail,
                    'program_date' => $schedule->program_date
                        ? Carbon::parse($schedule->program_date->format('Y-m-d H:i:s'), 'Asia/Jakarta') : null,
                    'location' => $schedule->location,
                    'maps_url' => $schedule->maps_url,
                    'image_url' => $schedule->image_url,
                    'ticket_url' => null,
                    'is_event' => $schedule->event_type === 'Konser' || !$schedule->isSundayService,
                ];
            });

        // Read source records directly so publication and edits need no second entry.
        $ticketEvents = TicketEvent::where('published', true)->where('starts_at', '>', now())
            ->get()->map(function ($event) {
                return (object) [
                    'event_name' => $event->title,
                    'event_detail' => $event->description,
                    'program_date' => $event->starts_at->copy()->timezone('Asia/Jakarta'),
                    'location' => $event->location,
                    'maps_url' => $event->maps_url,
                    'image_url' => $event->thumbnail ? route('tickets.thumbnail', $event) : null,
                    'ticket_url' => route('tickets.select', $event),
                    'is_event' => true,
                ];
            });

        // Prefer ticketing details for a matching manual title and start time.
        $upcoming = $ticketEvents->concat($schedules)->unique(function ($event) {
            return mb_strtolower(trim($event->event_name)).'|'.$event->program_date?->format('Y-m-d H:i');
        })->sortBy(fn ($event) => $event->program_date?->timestamp ?? PHP_INT_MAX)->values();

        return view('home', [
            'events' => $upcoming->where('is_event', true)->values(),
            'services' => $upcoming->filter(fn ($event) => $event->program_date !== null)->values(),
            'pastEvents' => PastEventsModel::where('rowstatus', '>=', 0)
                ->orderBy('eventDate')->take(3)->get(),
        ]);
    }
}
