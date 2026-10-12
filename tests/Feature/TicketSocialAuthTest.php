<?php

namespace Tests\Feature;

use App\Models\{Customer, CustomerSocialAccount};
use App\Services\TicketSocialLogin;
use GuzzleHttp\{Client, HandlerStack};
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\{Auth, Cache, DB, Hash, Http};
use Laravel\Socialite\Two\User;
use Tests\TestCase;

class TicketSocialAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:', 'cache.default'=>'array', 'session.driver'=>'array']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_10_07_000001_create_ticketing_tables.php'))->up();
        (require database_path('migrations/2026_10_12_000002_create_customer_social_accounts.php'))->up();
        foreach (['google','apple'] as $provider) config(['services.'.$provider=>[
            'enabled'=>true, 'client_id'=>'test-client', 'client_secret'=>'test-secret',
            'redirect'=>'http://localhost/tickets/auth/'.$provider.'/callback', 'request_birthday'=>true,
        ]]);
    }

    private function pending(array $overrides = []): array
    {
        return array_merge(['provider'=>'google', 'provider_id'=>'subject-123', 'email'=>'listener@example.test',
            'name'=>'Test Listener', 'dob'=>null, 'expires'=>time()+900], $overrides);
    }

    private function customer(): Customer
    {
        return Customer::create(['name'=>'Listener', 'email'=>'listener@example.test', 'phone'=>'628123456789',
            'dob'=>'1990-01-02', 'password'=>Hash::make('ExistingPassword123'), 'email_verified_at'=>now()]);
    }

    private function flow(string $provider = 'google'): array
    {
        return ['provider'=>$provider, 'state'=>'browser-state', 'nonce'=>'nonce.browser-state', 'birthday'=>$provider === 'google', 'expires'=>time()+900,
            'host'=>'localhost', 'client_id'=>$provider === 'google' ? 'test-client' : null];
    }

    private function fakeUser(array $raw = [], string $provider = 'google'): void
    {
        $user = (new User)->setRaw($raw + ['email_verified'=>true, 'nonce'=>'nonce.browser-state'])
            ->map(['id'=>'subject-123', 'email'=>'listener@example.test', 'name'=>'Test Listener'])->setToken('access-token');
        $driver = \Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($user);
        $service = \Mockery::mock(TicketSocialLogin::class)->makePartial();
        $service->shouldReceive('driver')->with($provider)->once()->andReturn($driver);
        $this->app->instance(TicketSocialLogin::class, $service);
    }

    public function test_redirects_request_birthday_only_for_google_registration(): void
    {
        foreach (['login'=>false, 'register'=>true] as $mode=>$birthday) {
            $response = $this->get(route('tickets.social.redirect', ['provider'=>'google','mode'=>$mode]));
            $response->assertRedirect();
            parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
            $this->assertSame($birthday, str_contains($query['scope'], 'user.birthday.read'));
            $this->assertSame($query['state'], session('social_flow.state'));
        }
        $response = $this->get(route('tickets.social.redirect', ['provider'=>'apple']));
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('form_post', $query['response_mode']);
        $this->assertSame($query['nonce'], session('social_flow.nonce'));
    }

    public function test_google_callback_exchanges_code_and_does_not_create_incomplete_customer(): void
    {
        config(['services.google.guzzle'=>['handler'=>HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['access_token'=>'test-token','token_type'=>'Bearer'])),
            new Response(200, [], json_encode(['sub'=>'google-123','name'=>'Listener','email'=>'LISTENER@example.test','email_verified'=>true])),
        ]))]]);
        Http::fake(['people.googleapis.com/*'=>Http::response(['birthdays'=>[['date'=>['year'=>1990,'month'=>1,'day'=>2]]]])]);
        $this->withSession(['state'=>'browser-state','social_flow'=>$this->flow()])
            ->get(route('tickets.social.callback', ['provider'=>'google','state'=>'browser-state','code'=>'valid-code']))
            ->assertRedirect(route('tickets.social.profile'))->assertSessionHas('social_pending.dob','1990-01-02');
        $this->assertGuest('customer');
        $this->assertSame(0, Customer::count());
        $this->get(route('tickets.social.profile'))->assertOk()->assertSee('id="phone"', false)->assertDontSee('id="dob"', false);
        $this->post(route('tickets.social.complete'), ['phone'=>'0812-3456-789','dob'=>'03/04/2000','email'=>'forged@example.test'])
            ->assertRedirect(route('tickets.events'));
        $this->assertAuthenticated('customer');
        $this->assertDatabaseHas('customers',['email'=>'listener@example.test','phone'=>'628123456789']);
        $this->assertSame('1990-01-02',Customer::first()->dob->format('Y-m-d'));
        $this->assertDatabaseHas('customer_social_accounts',['provider'=>'google','provider_id'=>'google-123']);
    }

    public function test_missing_birthday_requires_valid_manual_date_and_phone(): void
    {
        $this->withSession(['social_pending'=>$this->pending()])->get(route('tickets.social.profile'))
            ->assertOk()->assertSee('name="dob"', false);
        $this->post(route('tickets.social.complete'), [])->assertSessionHasErrors(['phone','dob']);
        $this->post(route('tickets.social.complete'), ['phone'=>'abc','dob'=>'31/02/1990'])->assertSessionHasErrors(['phone','dob']);
        $this->assertSame(0, Customer::count());
        $this->post(route('tickets.social.complete'), ['phone'=>'08123456789','dob'=>'02/01/1990'])->assertRedirect(route('tickets.events'));
        $this->assertNotNull(Customer::first()->email_verified_at);
    }

    public function test_birthday_api_failure_or_partial_date_falls_back_to_manual_entry(): void
    {
        Http::fakeSequence()->push([],403)->push(['birthdays'=>[['date'=>['month'=>1,'day'=>2]]]])
            ->push(['birthdays'=>[['date'=>['year'=>1990,'month'=>2,'day'=>31]]]])
            ->push(['birthdays'=>[['date'=>['year'=>2999,'month'=>1,'day'=>2]]]]);
        for ($i=0;$i<4;$i++) $this->assertNull((new TicketSocialLogin)->birthday('token'));
    }

    public function test_existing_email_requires_password_before_linking(): void
    {
        $customer = $this->customer();
        $this->withSession(['social_pending'=>$this->pending()])->post(route('tickets.social.complete'), ['password'=>'wrong'])
            ->assertSessionHasErrors('password');
        $this->assertGuest('customer');
        $this->assertSame(0,CustomerSocialAccount::count());
        $this->post(route('tickets.social.complete'), ['password'=>'ExistingPassword123'])->assertRedirect(route('tickets.events'));
        $this->assertAuthenticatedAs($customer,'customer');
        $this->assertSame(1,Customer::count());
    }

    public function test_linked_account_logs_in_without_profile_form(): void
    {
        $customer = $this->customer();
        CustomerSocialAccount::create(['customer_id'=>$customer->id,'provider'=>'google','provider_id'=>'subject-123']);
        $this->fakeUser();
        $this->withSession(['social_flow'=>$this->flow()])->get(route('tickets.social.callback', ['provider'=>'google','state'=>'browser-state','code'=>'code']))
            ->assertRedirect(route('tickets.events'))->assertSessionMissing('social_pending');
        $this->assertAuthenticatedAs($customer,'customer');
    }

    public function test_inactive_linked_account_cannot_sign_in(): void
    {
        $customer = $this->customer();
        $customer->update(['is_active'=>false]);
        CustomerSocialAccount::create(['customer_id'=>$customer->id,'provider'=>'google','provider_id'=>'subject-123']);
        $this->fakeUser();
        $this->withSession(['social_flow'=>$this->flow()])->get(route('tickets.social.callback', ['provider'=>'google','state'=>'browser-state','code'=>'code']))
            ->assertRedirect(route('tickets.login'))->assertSessionHasErrors('social');
        $this->assertGuest('customer');
    }

    public function test_invalid_state_expired_flow_and_cancellation_are_rejected_before_token_exchange(): void
    {
        $service = \Mockery::mock(TicketSocialLogin::class);
        $service->shouldNotReceive('driver');
        $this->app->instance(TicketSocialLogin::class,$service);
        foreach ([['state'=>'wrong'],['error'=>'access_denied'],['expired'=>true]] as $case) {
            $flow = $this->flow();
            if (isset($case['expired'])) $flow['expires'] = time()-1;
            $this->withSession(['social_flow'=>$flow])->get(route('tickets.social.callback', ['provider'=>'google','state'=>$case['state']??'browser-state','code'=>'code','error'=>$case['error']??null]))
                ->assertRedirect(route('tickets.login'))->assertSessionHasErrors('social');
        }
        $this->assertGuest('customer');
    }

    public function test_unverified_provider_email_cannot_register(): void
    {
        $this->fakeUser(['email_verified'=>false]);
        $this->withSession(['social_flow'=>$this->flow()])->get(route('tickets.social.callback',['provider'=>'google','state'=>'browser-state','code'=>'code']))
            ->assertRedirect(route('tickets.login'))->assertSessionMissing('social_pending');
    }

    public function test_apple_relay_preserves_cookie_and_requires_original_session(): void
    {
        $this->withSession(['social_flow'=>$this->flow('apple')]);
        $response = $this->post(route('tickets.social.apple-relay'), ['state'=>'browser-state','code'=>'code','user'=>'{"name":{"firstName":"Test"}}']);
        $response->assertStatus(303);
        $this->assertCount(0,$response->headers->getCookies());
        $this->fakeUser([], 'apple');
        $this->get($response->headers->get('Location'))->assertRedirect(route('tickets.social.profile'));
        $this->assertNull(session('social_pending.dob'));
        $this->get($response->headers->get('Location'))->assertRedirect(route('tickets.login'));
    }

    public function test_apple_nonce_mismatch_is_rejected(): void
    {
        $this->fakeUser(['nonce'=>'attacker-nonce'], 'apple');
        $this->withSession(['social_flow'=>$this->flow('apple')]);
        $response = $this->post(route('tickets.social.apple-relay'), ['state'=>'browser-state','code'=>'code']);
        $this->get($response->headers->get('Location'))->assertRedirect(route('tickets.login'))->assertSessionMissing('social_pending');
    }

    public function test_expired_profile_cannot_create_account(): void
    {
        $this->withSession(['social_pending'=>$this->pending(['expires'=>time()-1])])->post(route('tickets.social.complete'), ['dob'=>'02/01/1990','phone'=>'08123456789'])
            ->assertRedirect(route('tickets.login'));
        $this->assertSame(0,Customer::count());
    }

    public function test_authenticated_customer_can_link_another_provider_without_password(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer,'customer')->withSession(['social_pending'=>$this->pending(['provider'=>'apple'])])
            ->post(route('tickets.social.complete'))->assertRedirect(route('tickets.events'));
        $this->assertDatabaseHas('customer_social_accounts',['provider'=>'apple','customer_id'=>$customer->id]);
    }

    public function test_apple_relay_from_another_browser_cannot_link_an_account(): void
    {
        $service = \Mockery::mock(TicketSocialLogin::class);
        $service->shouldNotReceive('driver');
        $this->app->instance(TicketSocialLogin::class,$service);
        $response = $this->post(route('tickets.social.apple-relay'), ['state'=>'another-browser-state','code'=>'code']);
        $this->withSession(['social_flow'=>$this->flow('apple')])->get($response->headers->get('Location'))
            ->assertRedirect(route('tickets.login'))->assertSessionMissing('social_pending');
    }

    public function test_real_apple_provider_validates_signed_token_and_audience(): void
    {
        $key = openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
        $this->assertNotFalse($key);
        openssl_pkey_export($key,$privateKey);
        $publicKey = openssl_pkey_get_details($key);
        $encode = fn($value)=>rtrim(strtr(base64_encode($value),'+/','-_'),'=');
        Cache::put('socialite:Apple-JWKSet',['keys'=>[[
            'kty'=>'RSA','kid'=>'test-key','alg'=>'RS256','use'=>'sig',
            'n'=>$encode($publicKey['rsa']['n']),'e'=>$encode($publicKey['rsa']['e']),
        ]]],300);

        foreach (['wrong-client', 'test-client'] as $audience) {
            $claims = ['iss'=>'https://appleid.apple.com','aud'=>$audience,'sub'=>'apple-subject',
                'iat'=>time()-5,'exp'=>time()+300,'nonce'=>'nonce.browser-state',
                'email'=>'relay@privaterelay.appleid.com','email_verified'=>'true'];
            $token = \Firebase\JWT\JWT::encode($claims,$privateKey,'RS256','test-key');
            $service = \Mockery::mock(TicketSocialLogin::class)->makePartial();
            $service->shouldReceive('driver')->with('apple')->once()->andReturnUsing(function () use ($token) {
                $provider = (new TicketSocialLogin)->driver('apple');
                $provider->setHttpClient(new Client(['handler'=>HandlerStack::create(new MockHandler([
                    new Response(200,[],json_encode(['id_token'=>$token,'access_token'=>'apple-access-token'])),
                ]))]));
                return $provider;
            });
            $this->app->instance(TicketSocialLogin::class,$service);
            $this->withSession(['social_flow'=>$this->flow('apple')]);
            $response = $this->post(route('tickets.social.apple-relay'), ['state'=>'browser-state','code'=>'code','user'=>'{"name":{"firstName":"Apple","lastName":"Listener"}}']);
            $result = $this->get($response->headers->get('Location'));
            if ($audience === 'wrong-client') $result->assertRedirect(route('tickets.login'))->assertSessionMissing('social_pending');
            else $result->assertRedirect(route('tickets.social.profile'))
                ->assertSessionHas('social_pending.email','relay@privaterelay.appleid.com')
                ->assertSessionHas('social_pending.name','Apple Listener');
        }
    }

    public function test_buttons_are_config_gated_and_apple_is_hidden_until_device_detection(): void
    {
        $this->get(route('tickets.login'))->assertOk()->assertSee('/tickets/auth/google/redirect', false)
            ->assertSee('id="apple-sign-in" hidden', false);
        config(['services.google.client_id'=>null,'services.apple.client_id'=>null]);
        $this->get(route('tickets.login'))->assertOk()->assertDontSee('id="social-auth"', false);
        $this->get(route('tickets.social.redirect',['provider'=>'google']))->assertNotFound();
    }

    public function test_apple_can_be_disabled_while_google_remains_available(): void
    {
        config(['services.apple.enabled'=>false]);
        $this->get(route('tickets.login'))->assertOk()->assertSee('/tickets/auth/google/redirect', false)
            ->assertDontSee('id="apple-sign-in"', false);
        $this->get(route('tickets.social.redirect',['provider'=>'apple']))->assertNotFound();
        $this->post(route('tickets.social.apple-relay'),['state'=>'state','code'=>'code'])->assertNotFound();
    }

    private function configureGoogleHosts(): void
    {
        config(['services.google.client_id'=>'production-client', 'services.google.client_secret'=>'production-secret',
            'services.google.redirect'=>'https://voiceofsoulchoir.id/tickets/auth/google/callback',
            'services.google.development'=>[
                'client_id'=>'development-client', 'client_secret'=>'development-secret',
                'redirect'=>'https://dev.voiceofsoulchoir.id/tickets/auth/google/callback',
            ],
        ]);
    }

    public function test_google_credentials_follow_exact_host_without_leaking_between_requests(): void
    {
        $this->configureGoogleHosts();
        foreach (['voiceofsoulchoir.id'=>'production-client','dev.voiceofsoulchoir.id'=>'development-client','VOICEOFSOULCHOIR.ID'=>'production-client'] as $host=>$client) {
            $response = $this->get('https://'.$host.'/tickets/auth/google/redirect');
            $response->assertRedirect();
            parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY),$query);
            $this->assertSame($client,$query['client_id']);
            $this->assertSame('https://'.strtolower($host).'/tickets/auth/google/callback',$query['redirect_uri']);
        }
        $this->assertSame('production-client',config('services.google.client_id'));
        foreach (['localhost','dev.voiceofsoulchoir.id.attacker.test','preview.voiceofsoulchoir.id'] as $host) {
            $this->get('https://'.$host.'/tickets/auth/google/redirect')->assertNotFound();
            $this->get('https://'.$host.'/tickets/login')->assertOk()->assertDontSee('/tickets/auth/google/redirect',false);
        }
    }

    public function test_missing_development_secret_does_not_fall_back_to_production(): void
    {
        $this->configureGoogleHosts();
        config(['services.google.development.client_secret'=>null]);
        $this->get('https://dev.voiceofsoulchoir.id/tickets/auth/google/redirect')->assertNotFound();
        $this->get('https://voiceofsoulchoir.id/tickets/auth/google/redirect')->assertRedirect();
    }

    public function test_google_callback_rejects_flow_started_on_another_host(): void
    {
        $this->configureGoogleHosts();
        $this->get('https://voiceofsoulchoir.id/tickets/auth/google/redirect')->assertRedirect();
        $state = session('social_flow.state');
        $service = \Mockery::mock(TicketSocialLogin::class);
        $service->shouldNotReceive('driver');
        $this->app->instance(TicketSocialLogin::class,$service);
        $this->get('https://dev.voiceofsoulchoir.id/tickets/auth/google/callback?'.http_build_query(['state'=>$state,'code'=>'code']))
            ->assertRedirect()->assertSessionHasErrors('social')->assertSessionMissing('social_pending');
    }

    public function test_development_callback_exchanges_code_using_development_secret(): void
    {
        $this->configureGoogleHosts();
        $this->get('https://dev.voiceofsoulchoir.id/tickets/auth/google/redirect')->assertRedirect();
        $state = session('social_flow.state');
        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200,[],json_encode(['access_token'=>'test-token','token_type'=>'Bearer'])),
            new Response(200,[],json_encode(['sub'=>'dev-user','name'=>'Dev Listener','email'=>'dev@example.test','email_verified'=>true])),
        ]));
        $handler->push(\GuzzleHttp\Middleware::history($history));
        config(['services.google.development.guzzle'=>['handler'=>$handler]]);
        $this->get('https://dev.voiceofsoulchoir.id/tickets/auth/google/callback?'.http_build_query(['state'=>$state,'code'=>'test-code']))
            ->assertRedirect()->assertSessionHas('social_pending.email','dev@example.test');
        parse_str((string)$history[0]['request']->getBody(),$tokenRequest);
        $this->assertSame('development-client',$tokenRequest['client_id']);
        $this->assertSame('development-secret',$tokenRequest['client_secret']);
        $this->assertSame('https://dev.voiceofsoulchoir.id/tickets/auth/google/callback',$tokenRequest['redirect_uri']);
    }
}
