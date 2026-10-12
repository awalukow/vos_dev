<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class TicketSocialLogin
{
    public static function enabled(string $provider): bool
    {
        if (!in_array($provider, ['google', 'apple'], true)) return false;
        $config = config('services.'.$provider, []);
        if ($provider === 'apple' && empty($config['enabled'])) return false;
        return !empty($config['client_id']) && !empty($config['redirect'])
            && (!empty($config['client_secret']) || ($provider === 'apple'
                && !empty($config['private_key']) && !empty($config['key_id']) && !empty($config['team_id'])));
    }

    public function driver(string $provider)
    {
        // A fresh driver avoids retaining a previous request/user in long-lived workers.
        return Socialite::forgetDrivers()->driver($provider);
    }

    public function birthday(string $token): ?string
    {
        try {
            $response = Http::withToken($token)->timeout(8)->get('https://people.googleapis.com/v1/people/me', [
                'personFields' => 'birthdays', 'sources' => 'READ_SOURCE_TYPE_PROFILE',
            ]);
            if (!$response->successful()) return null;
            foreach ($response->json('birthdays', []) as $birthday) {
                $date = $birthday['date'] ?? [];
                $year = $date['year'] ?? 0;
                $month = $date['month'] ?? 0;
                $day = $date['day'] ?? 0;
                if (!checkdate($month, $day, $year)) continue;
                $dob = sprintf('%04d-%02d-%02d', $year, $month, $day);
                if ($dob < now()->toDateString()) return $dob;
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Missing consent, incomplete birthdays and API outages all use manual entry.
        }
        return null;
    }
}
