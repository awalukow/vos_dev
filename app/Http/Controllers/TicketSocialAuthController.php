<?php

namespace App\Http\Controllers;

use App\Models\{Customer, CustomerSocialAccount};
use App\Services\{TicketCustomerInput, TicketSocialLogin};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Cache, DB, Hash, Log};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketSocialAuthController extends Controller
{
    public function redirect(Request $request, string $provider, TicketSocialLogin $social)
    {
        abort_unless(TicketSocialLogin::enabled($provider), 404);
        if ($provider === 'google') {
            $callback = parse_url(TicketSocialLogin::googleConfiguration()['redirect']);
            $scheme = $callback['scheme'] ?? '';
            $host = $callback['host'] ?? '';
            abort_unless(in_array($scheme, ['http', 'https'], true) && $host !== '', 404);
            $port = $callback['port'] ?? ($scheme === 'https' ? 443 : 80);
            if (strtolower($host) !== strtolower($request->getHost())
                || $scheme !== $request->getScheme() || $port !== $request->getPort()) {
                // Start OAuth on the callback origin so state stays in its session.
                $origin = $scheme.'://'.$host.(isset($callback['port']) ? ':'.$port : '');
                $path = route('tickets.social.redirect', ['provider'=>'google'], false);
                $mode = $request->query('mode') === 'register' ? 'register' : 'login';
                return redirect()->away($origin.$path.'?'.http_build_query(['mode'=>$mode]));
            }
        }
        $request->session()->forget(['social_pending', 'social_flow']);
        $driver = $social->driver($provider);
        $birthday = $provider === 'google' && $request->query('mode') === 'register'
            && config('services.google.request_birthday');
        if ($birthday) $driver->scopes(['https://www.googleapis.com/auth/user.birthday.read']);
        $response = $driver->redirect();
        parse_str(parse_url($response->getTargetUrl(), PHP_URL_QUERY), $parameters);
        $request->session()->put('social_flow', [
            'provider' => $provider, 'state' => $parameters['state'],
            'nonce' => $parameters['nonce'] ?? null, 'birthday' => $birthday,
            'expires' => time() + 900,
            'host' => $request->getHost(),
            'client_id' => $provider === 'google' ? TicketSocialLogin::googleConfiguration()['client_id'] : null,
        ]);
        return $response;
    }

    // Apple posts cross-site, without the Lax session cookie. Do not start a new
    // session on this route: it would overwrite the initiating browser's cookie.
    public function appleRelay(Request $request)
    {
        abort_unless(TicketSocialLogin::enabled('apple'), 404);
        $validator = validator($request->all(), [
            'state' => 'required|string|max:200', 'code' => 'nullable|string|max:4096',
            'error' => 'nullable|string|max:200', 'user' => 'nullable|json|max:8192',
        ]);
        abort_if($validator->fails(), 400);
        $data = $validator->validated();
        $receipt = Str::random(64);
        Cache::put('apple-callback:'.$receipt, $data, 120);
        return redirect()->route('tickets.social.callback', ['provider' => 'apple', 'receipt' => $receipt], 303)
            ->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
    }

    public function callback(Request $request, string $provider, TicketSocialLogin $social)
    {
        abort_unless(TicketSocialLogin::enabled($provider), 404);
        if ($provider === 'apple') {
            $receipt = $request->query('receipt');
            if (!is_string($receipt) || !preg_match('/^[a-zA-Z0-9]{64}$/', $receipt)) return $this->failed();
            $payload = Cache::pull('apple-callback:'.$receipt);
            if (!$payload) return $this->failed();
            $request->merge($payload);
        }
        $flow = $request->session()->pull('social_flow');
        $state = $request->input('state');
        if (!$flow || $flow['provider'] !== $provider || $flow['expires'] < time()
            || !is_string($state) || !hash_equals($flow['state'], $state)) return $this->failed();
        if (($flow['host'] ?? null) !== $request->getHost()
            || ($provider === 'google' && ($flow['client_id'] ?? null) !== TicketSocialLogin::googleConfiguration()['client_id'])) return $this->failed();
        if ($request->filled('error') || !$request->filled('code')) return $this->failed();

        try {
            $user = $social->driver($provider)->user();
            // Also bind Apple's signed nonce to this browser's original request.
            if ($provider === 'apple' && ($user->user['nonce'] ?? null) !== $flow['nonce']) return $this->failed();
        } catch (\Throwable $e) {
            // Provider exceptions may contain codes, tokens or secrets. Never log them.
            Log::warning('Customer social authentication failed.', ['provider' => $provider, 'type' => get_class($e)]);
            return $this->failed();
        }

        $subject = $user->getId();
        if (!is_string($subject) || $subject === '' || strlen($subject) > 191) return $this->failed();
        $account = CustomerSocialAccount::where('provider', $provider)->where('provider_id', $subject)->first();
        if ($account) return $this->signIn($request, $account->customer);

        $email = strtolower(trim((string) $user->getEmail()));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190
            || !filter_var($user->user['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) return $this->failed();
        $existing = Customer::where('email', $email)->first();
        $request->session()->regenerate();
        $request->session()->put('social_pending', [
            'provider' => $provider, 'provider_id' => $subject, 'email' => $email,
            'name' => mb_substr(trim((string) $user->getName()), 0, 120),
            'dob' => !$existing && $flow['birthday'] ? $social->birthday($user->token) : null,
            'expires' => time() + 900,
        ]);
        return redirect()->route('tickets.social.profile');
    }

    public function profile(Request $request)
    {
        $pending = $this->pending($request);
        $existing = Customer::where('email', $pending['email'])->first();
        return response()->view('tickets.social-profile', [
            'pending' => $pending, 'existing' => (bool) $existing,
            'alreadySignedIn' => $existing && Auth::guard('customer')->id() === $existing->id,
        ])->header('Cache-Control', 'no-store');
    }

    public function complete(Request $request)
    {
        $pending = $this->pending($request);
        $existing = Customer::where('email', $pending['email'])->first();
        $data = [];
        if ($existing && Auth::guard('customer')->id() !== $existing->id) {
            $request->validate(['password' => 'required|string|max:1024']);
            if (!$existing->is_active || !Hash::check($request->input('password'), $existing->password)) {
                throw ValidationException::withMessages(['password' => __('The password is incorrect, or the account is inactive.')]);
            }
        } elseif (!$existing) {
            $rules = [
                'phone' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){7,15}\D*$)[+0-9 ()-]+$/'],
            ];
            if (!$pending['dob']) $rules['dob'] = 'required|date_format:d/m/Y|before:today';
            if (!$pending['name']) $rules['name'] = 'required|string|max:120';
            $data = $request->validate($rules);
            $data['name'] = $pending['name'] ?: $data['name'];
            $data['dob'] = $pending['dob'] ? \Carbon\Carbon::parse($pending['dob'])->format('d/m/Y') : $data['dob'];
            $data = TicketCustomerInput::normalize($data);
        }

        try {
            $customer = DB::transaction(function () use ($pending, $existing, $data) {
                if ($existing && !$existing->fresh()->is_active) abort(403);
                $customer = $existing ?: Customer::create($data + [
                    'email' => $pending['email'], 'password' => Hash::make(Str::random(64)), 'email_verified_at' => now(),
                ]);
                CustomerSocialAccount::create([
                    'customer_id' => $customer->id, 'provider' => $pending['provider'], 'provider_id' => $pending['provider_id'],
                ]);
                if (!$customer->email_verified_at) $customer->update(['email_verified_at' => now()]);
                return $customer;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Unique constraints also protect concurrent completions and duplicate links.
            return redirect()->route('tickets.social.profile')->withErrors(['social' => __('This account could not be linked. Please sign in again.')]);
        }
        return $this->signIn($request, $customer);
    }

    private function pending(Request $request): array
    {
        $pending = $request->session()->get('social_pending');
        if (!$pending || $pending['expires'] < time()) {
            $request->session()->forget('social_pending');
            abort(redirect()->route('tickets.login')->withErrors(['social' => __('Your sign-in expired. Please try again.')]));
        }
        return $pending;
    }

    private function signIn(Request $request, Customer $customer)
    {
        if (!$customer->is_active) return $this->failed();
        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();
        $request->session()->forget(['social_pending', 'social_flow', 'state', 'state_verify', 'url.intended']);
        return redirect()->route($customer->email_verified_at ? 'tickets.events' : 'tickets.verify');
    }

    private function failed()
    {
        return redirect()->route('tickets.login')->withErrors(['social' => __('Sign-in could not be completed. Please try again.')]);
    }
}
