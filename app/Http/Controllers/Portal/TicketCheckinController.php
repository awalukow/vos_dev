<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\TicketOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketCheckinController extends Controller
{
    public function index()
    {
        return response()->view('portal.ticketing.checkin')->header('Cache-Control', 'private, no-store');
    }

    public function scan(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'uuid']]);

        $result = DB::transaction(function () use ($data, $request) {
            $item = TicketOrderItem::query()
                ->where('token', $data['token'])
                ->lockForUpdate()
                ->with(['order.customer', 'order.event', 'ticketClass'])
                ->first();

            if (!$item || !$item->order || $item->order->RowStatus !== 0 || $item->order->status !== 'paid') {
                return ['result' => 'invalid'];
            }

            if ($item->checked_in_at) {
                return [
                    'result' => 'already_checked_in',
                    'checked_in_at' => $item->checked_in_at->timezone('Asia/Jakarta')->format('H:i:s'),
                    'guest' => $item->order->customer->name,
                    'ticket' => $item->class_name,
                    'ticket_color' => $item->ticketClass?->color,
                    'event' => $item->order->event->title,
                ];
            }

            $item->checked_in_at = now();
            $item->checked_in_by = $request->user('portal')->id;
            $item->save();

            return [
                'result' => 'checked_in',
                'checked_in_at' => $item->checked_in_at->timezone('Asia/Jakarta')->format('H:i:s'),
                'guest' => $item->order->customer->name,
                'ticket' => $item->class_name,
                'ticket_color' => $item->ticketClass?->color,
                'event' => $item->order->event->title,
            ];
        }, 3);

        return response()->json($result)->header('Cache-Control', 'no-store');
    }
}
