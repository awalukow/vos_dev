<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Contracts\Factory;

class TicketSocialLogin
{
    public static function enabled(string $provider): bool
    {
        if (!in_array($provider, ['google', 'apple'], true)) return false;
        $config = $provider === 'google' ? self::googleConfiguration() : config('services.'.$provider, []);
        if ($provider === 'apple' && empty($config['enabled'])) return false;
        return !empty($config['client_id']) && !empty($config['redirect'])
            && (!empty($config['client_secret']) || ($provider === 'apple'
                && !empty($config['private_key']) && !empty($config['key_id']) && !empty($config['team_id'])));
    }

    public function driver(string $provider)
    {
        // Resolve the registered manager without depending on a facade namespace.
        $socialite = app(Factory::class);
        if ($provider === 'google') {
            // Resolve per request without mutating global config or reusing a driver
            // from another host in a long-lived worker.
            abort_unless(self::enabled('google'), 404);
            return $socialite->buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, self::googleConfiguration());
        }
        // A fresh driver avoids retaining a previous request/user in long-lived workers.
        return $socialite->forgetDrivers()->driver($provider);
    }

    public static function googleConfiguration(): array
    {
        $production = config('services.google', []);
        $development = $production['development'] ?? [];
        $localRedirect = $production['local_redirect'] ?? null;
        unset($production['development'], $production['local_redirect']);
        $host = strtolower(request()->getHost());
        foreach ([$development, $production] as $candidate) {
            $redirectHost = parse_url($candidate['redirect'] ?? '', PHP_URL_HOST);
            if (is_string($redirectHost) && strtolower($redirectHost) === $host) {
                // Never build callbacks from an arbitrary Host header, nor fall
                // back to production credentials when development is incomplete.
                return $candidate;
            }
        }
        // Local PHP development uses the development client, but must return to
        // the local session, never to the public development server.
        $localHost = parse_url($localRedirect ?? '', PHP_URL_HOST);
        $localScheme = parse_url($localRedirect ?? '', PHP_URL_SCHEME);
        $localPort = parse_url($localRedirect ?? '', PHP_URL_PORT) ?: ($localScheme === 'https' ? 443 : 80);
        if (in_array($localHost, ['localhost', '127.0.0.1', '[::1]'], true)
            && $host === $localHost && request()->getPort() === $localPort && request()->getScheme() === $localScheme) {
            return array_replace($development, ['redirect' => $localRedirect]);
        }
        return [];
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
