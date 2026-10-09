<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class BookingCode {
    public static function generate(): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {
            $code = '';
            for ($i = 0; $i < 5; $i++) $code .= $alphabet[random_int(0, 35)];
        } while (DB::table('ticket_orders')->where('booking_code', $code)->exists());
        return $code;
    }
}
