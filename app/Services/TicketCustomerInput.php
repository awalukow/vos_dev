<?php
namespace App\Services;

class TicketCustomerInput
{
    public static function normalize(array $data): array
    {
        $data['dob'] = \Carbon\Carbon::createFromFormat('!d/m/Y', $data['dob'])->format('Y-m-d');
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        $data['phone'] = str_starts_with($phone, '62') ? $phone : '62'.ltrim($phone, '0');
        return $data;
    }
}
