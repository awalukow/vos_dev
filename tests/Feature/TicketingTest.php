<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Customer,PortalUser,Role,TicketEvent,TicketClass,TicketVenue,TicketOrder,TicketPaymentMethod};
use App\Services\{TicketBooking,TicketDelivery,TicketLayout,TicketCustomerInput};
use Illuminate\Support\Facades\{DB,Schema,Hash,Mail,Storage};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class TicketingTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','mail.default'=>'array','session.driver'=>'array','cache.default'=>'array']);
        DB::purge('sqlite');
        Schema::create('roles',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->string('display_name');$t->text('description')->nullable();$t->integer('level');$t->timestamps();});
        Schema::create('portal_users',function(Blueprint $t){$t->id();$t->string('name');$t->string('username');$t->string('email');$t->string('password');$t->boolean('is_active')->default(true);$t->boolean('is_password_flushed')->default(false);$t->rememberToken();$t->softDeletes();$t->timestamps();});
        Schema::create('portal_user_roles',function(Blueprint $t){$t->id();$t->unsignedBigInteger('portal_user_id');$t->unsignedBigInteger('role_id');$t->timestamps();});
        (require database_path('migrations/2026_10_07_000001_create_ticketing_tables.php'))->up();
        (require database_path('migrations/2026_10_08_000001_add_ticket_event_layout_dividers.php'))->up();
        Schema::create('portal_menus', function(Blueprint $t) {
            $t->id(); $t->string('key')->unique(); $t->string('label'); $t->string('icon')->nullable();
            $t->string('route_name')->nullable(); $t->string('url')->nullable();
            $t->unsignedBigInteger('parent_id')->nullable(); $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('role_menu_permissions', function(Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('role_id'); $t->unsignedBigInteger('portal_menu_id');
            $t->timestamps(); $t->unique(['role_id','portal_menu_id']);
        });
        foreach (['administrator','adm2'] as $name) Role::create(['name'=>$name,'display_name'=>$name,'level'=>100]);
        (require database_path('migrations/2024_01_01_000004_create_documents_table.php'))->up();
        (require database_path('migrations/2024_01_02_000001_create_upload_access_requests_table.php'))->up();
        (require database_path('migrations/2026_10_07_000002_add_ticketing_portal_menus.php'))->up();
        (require database_path('migrations/2026_10_07_000003_add_ticket_order_list_menu.php'))->up();
        DB::table('portal_menus')->insert(['key'=>'user_mgmt','label'=>'User Management','sort_order'=>4,'is_active'=>true]);
        if ($this->getName() !== 'test_booking_code_migration_backfills_existing_orders_without_changing_references') (require database_path('migrations/2026_10_07_000004_add_singers_and_booking_codes.php'))->up();
        TicketPaymentMethod::where('type','transfer')->update(['active'=>true,'instructions'=>'Test Bank 123, VOS']);
        if ($this->getName() !== 'test_row_status_migration_defaults_existing_records_to_active') (require database_path('migrations/2026_10_08_000003_add_ticket_row_status.php'))->up();
        Storage::fake('local');
        (require database_path('migrations/2026_10_08_000004_create_ticket_promos.php'))->up();
        (require database_path('migrations/2026_10_09_000001_add_midtrans_payments.php'))->up();
    }
    private function customer(bool $verified=true): Customer {
        return Customer::create(['name'=>'Test Listener','dob'=>'1995-01-01','email'=>uniqid().'@example.test','phone'=>'+628123456789','password'=>Hash::make('StrongPass123!'),'email_verified_at'=>$verified?now():null]);
    }
    private function event(string $type='free',int $capacity=3): TicketEvent {
        $e=TicketEvent::create(['title'=>'A Night of Harmony','starts_at'=>now()->addDays(5),'location'=>'Jakarta','seating_type'=>$type,'published'=>true,'thumbnail'=>'ticket-media/example.png']);
        $c=$e->classes()->create(['name'=>'Regular','color'=>'#713cce','price'=>150000,'capacity'=>$capacity]);
        if($type==='numbered')for($i=0;$i<$capacity;$i++)$e->seats()->create(['ticket_class_id'=>$c->id,'label'=>'A'.($i+1),'x'=>$i,'y'=>0]);
        return $e;
    }
    private function staff(string $role='administrator'): PortalUser {
        $r=Role::firstOrCreate(['name'=>$role],['display_name'=>$role,'level'=>100]);
        $u=PortalUser::create(['name'=>'Staff','username'=>uniqid(),'email'=>uniqid().'@example.test','password'=>'StrongPass123!','is_active'=>true]);
        $u->roles()->attach($r);return $u;
    }
    private function reserve(TicketEvent $event,?Customer $customer=null,int $qty=1): TicketOrder {
        return app(TicketBooking::class)->reserve($customer??$this->customer(),$event,['quantities'=>[$event->classes->first()->id=>$qty]]);
    }
    private function midtrans(): TicketPaymentMethod {
        $method=TicketPaymentMethod::where('type','midtrans')->firstOrFail();
        $method->update(['active'=>true,'merchant_id'=>'merchant-test','server_key'=>'SB-server-secret','client_key'=>'SB-client-secret','processing_fee_type'=>'percent','processing_fee_value'=>2.5,'platform_fee_value'=>5000]);
        return $method;
    }
    private function fakeMidtrans(): void {
        \Illuminate\Support\Facades\Http::fake(['*/snap/v1/transactions'=>\Illuminate\Support\Facades\Http::response(['redirect_url'=>'https://app.sandbox.midtrans.com/snap/v2/test-token'])]);
    }
    /** @dataProvider midtransConnectionResponses */
    public function test_midtrans_connection_reports_provider_results(int $httpStatus, $body, bool $ok): void {
        $method=$this->midtrans(); $method->update(['active'=>false]);
        $before=$method->fresh()->getAttributes();
        \Illuminate\Support\Facades\Http::fake(['*'=>\Illuminate\Support\Facades\Http::response($body,$httpStatus)]);
        $this->actingAs($this->staff(),'portal')->post(route('portal.ticketing.methods.test-midtrans',$method),['server_key'=>'unsaved-key','environment'=>'production'])
            ->assertRedirect(route('portal.ticketing.methods'))->assertSessionHas('midtrans_connection.ok',$ok);
        $this->get(route('portal.ticketing.methods'))->assertOk()->assertSee('Test connection')->assertSee('role="status"',false)->assertDontSee('SB-server-secret');
        \Illuminate\Support\Facades\Http::assertSent(fn($r)=>$r->method()==='GET' && str_starts_with($r->url(),'https://api.sandbox.midtrans.com/v2/connection-test-') && $r->hasHeader('Authorization','Basic '.base64_encode('SB-server-secret:')));
        $this->assertSame($before,$method->fresh()->getAttributes());
        $this->assertSame(0,TicketOrder::count());
    }
    public static function midtransConnectionResponses(): array {
        return [
            'application not found'=>[200,['status_code'=>'404'],true],
            'http not found'=>[404,['status_code'=>'404'],true],
            'bad credentials'=>[401,['status_code'=>'401'],false],
            'application auth failure'=>[200,['status_code'=>'401'],false],
            'generic not found'=>[404,'Not found',false],
            'provider outage'=>[503,['status_code'=>'404'],false],
            'malformed response'=>[200,'Not JSON',false],
        ];
    }
    public function test_midtrans_connection_requires_admin_and_a_saved_key(): void {
        $method=TicketPaymentMethod::where('type','midtrans')->firstOrFail();
        \Illuminate\Support\Facades\Http::fake();
        $this->actingAs($this->staff('ticket_operator'),'portal')->get(route('portal.ticketing.methods'))->assertOk()->assertDontSee('Test connection');
        $this->post(route('portal.ticketing.methods.test-midtrans',$method))->assertForbidden();
        $this->actingAs($this->staff(),'portal')->post(route('portal.ticketing.methods.test-midtrans',$method))->assertSessionHas('midtrans_connection.ok',false);
        $this->post(route('portal.ticketing.methods.test-midtrans',TicketPaymentMethod::where('type','transfer')->first()))->assertNotFound();
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
    public function test_midtrans_connection_handles_timeout_and_production_environment(): void {
        $method=$this->midtrans(); $method->update(['environment'=>'production']);
        \Illuminate\Support\Facades\Http::fake(function($request) {
            $this->assertStringStartsWith('https://api.midtrans.com/v2/connection-test-',$request->url());
            throw new \Illuminate\Http\Client\ConnectionException('Internal details must not be exposed');
        });
        $this->actingAs($this->staff('adm2'),'portal')->post(route('portal.ticketing.methods.test-midtrans',$method))
            ->assertSessionHas('midtrans_connection.ok',false)
            ->assertSessionHas('midtrans_connection.message','Could not reach Midtrans. Check network connectivity and try again.');
    }
    public function test_midtrans_defaults_disabled_and_operator_permissions_are_enforced(): void {
        $method=TicketPaymentMethod::where('type','midtrans')->firstOrFail();
        $this->assertFalse($method->active);
        $operator=$this->staff('ticket_operator');
        $this->actingAs($operator,'portal')->get(route('portal.ticketing.methods'))->assertOk()->assertSee('QRIS (Automated Check)')->assertDontSee('name="server_key"',false);
        $fees=['processing_fee_type'=>'percent','processing_fee_value'=>2.5,'platform_fee_type'=>'fixed','platform_fee_value'=>5000];
        $this->post(route('portal.ticketing.methods.update',$method),$fees+['server_key'=>'injected'])->assertForbidden();
        $this->post(route('portal.ticketing.methods.update',$method),$fees+['active'=>1])->assertSessionHasErrors('active');
        $method=$this->midtrans();
        $this->post(route('portal.ticketing.methods.update',$method),$fees)->assertSessionHasNoErrors();
        $this->assertFalse($method->fresh()->active);
        $this->post(route('portal.ticketing.methods.update',TicketPaymentMethod::where('type','transfer')->first()),['name'=>'Changed'])->assertForbidden();
        $this->assertNotSame('SB-server-secret',DB::table('ticket_payment_methods')->where('id',$method->id)->value('server_key'));
        $this->assertStringNotContainsString('SB-server-secret',$method->toJson());
    }
    public function test_midtrans_checkout_snapshots_fees_and_is_idempotent(): void {
        $method=$this->midtrans(); $this->fakeMidtrans();
        $order=$this->reserve($this->event());
        $this->actingAs($order->customer,'customer')->withSession(['ticket_locale'=>'en'])->get(route('tickets.order',$order))->assertOk()->assertSee('Processing Fee')->assertSee('158.750');
        $url=app(\App\Services\MidtransPayments::class)->start($order,158750);
        $this->assertSame('https://app.sandbox.midtrans.com/snap/v2/test-token',$url);
        $order->refresh();
        $this->assertSame(158750,$order->total);
        $this->assertSame(['processing'=>3750,'platform'=>5000],$order->payment_snapshot['fees']);
        $this->assertSame('midtrans_pending',$order->status);
        $method->update(['active'=>false,'server_key'=>'changed']);
        $this->assertSame($url,app(\App\Services\MidtransPayments::class)->start($order,158750));
        \Illuminate\Support\Facades\Http::assertSentCount(1);
        \Illuminate\Support\Facades\Http::assertSent(fn($r)=>$r['transaction_details']['gross_amount']===158750 && $r['enabled_payments']===['other_qris']);
        $this->assertSame('SB-server-secret',$order->gateway_credentials['server_key']);
        $this->actingAs($order->customer,'customer')->get(route('tickets.order',$order))->assertOk()->assertSee('Complete your QRIS payment')->assertDontSee('Upload payment proof');
    }
    public function test_midtrans_rejects_changed_quotes_and_foreign_customers(): void {
        $this->midtrans(); $this->fakeMidtrans(); $order=$this->reserve($this->event());
        $this->actingAs($this->customer(),'customer')->post(route('tickets.midtrans.start',$order),['expected_total'=>158750])->assertForbidden();
        $this->actingAs($order->customer,'customer')->post(route('tickets.midtrans.start',$order),['expected_total'=>150000])->assertSessionHasErrors('payment');
        $this->assertSame('awaiting_payment',$order->fresh()->status);
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
    public function test_midtrans_dashboard_probe_requires_valid_signature_and_does_not_create_payments(): void {
        $method=$this->midtrans();
        $method->update(['active'=>false]);
        \Illuminate\Support\Facades\Http::fake();
        $this->mock(TicketDelivery::class,function($mock){$mock->shouldNotReceive('tickets');});
        $body=['order_id'=>'payment_notif_test_merchant-test_'.\Illuminate\Support\Str::uuid(),
            'merchant_id'=>'merchant-test','status_code'=>'200','gross_amount'=>'105000.00'];
        $body['signature_key']=hash('sha512',$body['order_id'].$body['status_code'].$body['gross_amount'].'SB-server-secret');
        $this->postJson(route('tickets.midtrans.notification'),$body)->assertOk()->assertExactJson(['received'=>true,'test'=>true]);
        $this->postJson(route('tickets.midtrans.notification'),array_merge($body,['signature_key'=>str_repeat('0',128)]))->assertForbidden();
        $this->postJson(route('tickets.midtrans.notification'),array_merge($body,['merchant_id'=>'another-merchant']))->assertForbidden();
        $body['order_id']='unknown-real-order';
        $body['signature_key']=hash('sha512',$body['order_id'].$body['status_code'].$body['gross_amount'].'SB-server-secret');
        $this->postJson(route('tickets.midtrans.notification'),$body)->assertNotFound();
        $this->assertSame(0,TicketOrder::count());
        $this->assertSame(0,DB::table('ticket_audit_logs')->count());
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
    public function test_midtrans_webhook_confirms_once_using_current_provider_status(): void {
        $method=$this->midtrans(); $this->fakeMidtrans(); $order=$this->reserve($this->event());
        app(\App\Services\MidtransPayments::class)->start($order,158750);
        $method->update(['active'=>false,'server_key'=>'new-key']);
        $body=['order_id'=>$order->reference,'status_code'=>'200','gross_amount'=>'158750.00'];
        $body['signature_key']=hash('sha512',$body['order_id'].$body['status_code'].$body['gross_amount'].'SB-server-secret');
        $this->postJson(route('tickets.midtrans.notification'),array_merge($body,['signature_key'=>str_repeat('0',128)]))->assertForbidden();
        \Illuminate\Support\Facades\Http::fake(['*/status'=>\Illuminate\Support\Facades\Http::response($body+['transaction_status'=>'settlement','payment_type'=>'qris','currency'=>'IDR','fraud_status'=>'accept'])]);
        $this->mock(TicketDelivery::class,function($mock){$mock->shouldReceive('tickets')->once()->andReturn(true);});
        $this->postJson(route('tickets.midtrans.notification'),$body)->assertOk();
        $this->postJson(route('tickets.midtrans.notification'),$body)->assertOk();
        $this->assertSame('paid',$order->fresh()->status);
        $this->assertSame(1,DB::table('ticket_audit_logs')->where('action','payment.midtrans_confirmed')->count());
    }
    public function test_midtrans_json_refresh_confirms_payment_without_webhook(): void {
        $this->midtrans(); $this->fakeMidtrans(); $order=$this->reserve($this->event());
        app(\App\Services\MidtransPayments::class)->start($order,158750);
        \Illuminate\Support\Facades\Http::fake(['*/status'=>\Illuminate\Support\Facades\Http::response([
            'order_id'=>$order->reference,'gross_amount'=>'158750.00','currency'=>'IDR',
            'transaction_status'=>'settlement','payment_type'=>'qris','fraud_status'=>'accept',
        ])]);
        $this->mock(TicketDelivery::class,function($mock){$mock->shouldReceive('tickets')->once()->andReturn(true);});
        $this->actingAs($this->customer(),'customer')->postJson(route('tickets.midtrans.refresh',$order))->assertForbidden();
        $this->actingAs($order->customer,'customer')->postJson(route('tickets.midtrans.refresh',$order))->assertOk()->assertExactJson(['status'=>'paid']);
        $this->postJson(route('tickets.midtrans.refresh',$order))->assertOk()->assertExactJson(['status'=>'paid']);
        $this->assertSame(1,DB::table('ticket_audit_logs')->where('action','payment.midtrans_confirmed')->count());
    }
    public function test_midtrans_json_refresh_retries_after_network_failure_without_releasing_booking(): void {
        $this->midtrans(); $this->fakeMidtrans(); $order=$this->reserve($this->event());
        app(\App\Services\MidtransPayments::class)->start($order,158750);
        $attempts=0;
        \Illuminate\Support\Facades\Http::fake(['*/status'=>function() use(&$attempts,$order){
            if (++$attempts===1) throw new \Illuminate\Http\Client\ConnectionException('timeout');
            return \Illuminate\Support\Facades\Http::response([
                'order_id'=>$order->reference,'gross_amount'=>'158750.00','currency'=>'IDR','transaction_status'=>'pending',
            ]);
        }]);
        $this->actingAs($order->customer,'customer')->postJson(route('tickets.midtrans.refresh',$order))->assertStatus(503);
        $this->assertSame('midtrans_pending',$order->fresh()->status);
        $this->postJson(route('tickets.midtrans.refresh',$order))->assertOk()->assertExactJson(['status'=>'midtrans_pending']);
        $this->post(route('tickets.midtrans.refresh',$order))->assertRedirect(route('tickets.order',$order));
    }
    public function test_midtrans_holds_inventory_until_verified_terminal_status(): void {
        $this->midtrans(); $this->fakeMidtrans(); $event=$this->event('free',1); $order=$this->reserve($event);
        $payments=app(\App\Services\MidtransPayments::class); $payments->start($order,158750);
        $order->refresh()->update(['expires_at'=>now()->subMinute()]);
        $this->assertSame(1,$event->reservedItems()->count());
        $this->actingAs($order->customer,'customer')->post(route('tickets.cancel',$order))->assertSessionHasErrors();
        $this->post(route('tickets.promo',$order),['promo_code'=>''])->assertSessionHasErrors();
        $payments->applyStatus($order,['order_id'=>$order->reference,'gross_amount'=>'158750.00','currency'=>'IDR','transaction_status'=>'expire']);
        $this->assertSame('expired',$order->fresh()->status);
        $this->assertSame(0,$event->reservedItems()->count());
    }
    public function test_midtrans_amount_mismatch_does_not_issue_tickets(): void {
        $this->midtrans(); $this->fakeMidtrans(); $order=$this->reserve($this->event());
        app(\App\Services\MidtransPayments::class)->start($order,158750);
        \Illuminate\Support\Facades\Http::fake(['*/status'=>\Illuminate\Support\Facades\Http::response(['order_id'=>$order->reference,'gross_amount'=>'1.00','transaction_status'=>'settlement','payment_type'=>'qris','currency'=>'IDR'])]);
        $this->actingAs($order->customer,'customer')->post(route('tickets.midtrans.refresh',$order))->assertStatus(422);
        $this->assertSame('midtrans_pending',$order->fresh()->status);
    }
    public function test_midtrans_admin_can_save_keys_without_exposing_or_flashing_them(): void {
        $method=$this->midtrans();
        $this->actingAs($this->staff(),'portal')->get(route('portal.ticketing.methods'))->assertOk()->assertDontSee('SB-server-secret')->assertDontSee('SB-client-secret');
        $data=['environment'=>'sandbox','merchant_id'=>'merchant-test','server_key'=>'replacement-secret','client_key'=>'replacement-client','active'=>1,'processing_fee_type'=>'percent','processing_fee_value'=>1.25,'platform_fee_type'=>'fixed','platform_fee_value'=>1000];
        $this->post(route('portal.ticketing.methods.update',$method),$data)->assertSessionHasNoErrors();
        $this->assertSame('replacement-secret',$method->fresh()->server_key);
        $data['processing_fee_value']=-1;
        $this->post(route('portal.ticketing.methods.update',$method),$data)->assertSessionHasErrors()->assertSessionMissing('_old_input.server_key')->assertSessionMissing('_old_input.client_key');
        $this->actingAs($this->staff('member'),'portal')->get(route('portal.ticketing.methods'))->assertForbidden();
    }
    public function test_midtrans_timeout_keeps_inventory_and_abandoned_checkout_expires_safely(): void {
        $this->midtrans(); $order=$this->reserve($this->event());
        \Illuminate\Support\Facades\Http::fake(['*/snap/v1/transactions'=>function(){throw new \Illuminate\Http\Client\ConnectionException('timeout');}]);
        $this->actingAs($order->customer,'customer')->post(route('tickets.midtrans.start',$order),['expected_total'=>158750])->assertSessionHasErrors('payment');
        $this->assertSame('midtrans_pending',$order->fresh()->status);
        $this->assertSame(1,$order->event->reservedItems()->count());
        \Illuminate\Support\Facades\Http::fake(['*/status'=>\Illuminate\Support\Facades\Http::response(['status_code'=>'404'],404)]);
        app(\App\Services\MidtransPayments::class)->sync($order->fresh());
        $this->assertSame('midtrans_pending',$order->fresh()->status);
        $order->update(['expires_at'=>now()->subMinutes(6)]);
        $this->artisan('tickets:sync-midtrans')->assertExitCode(0);
        $this->assertSame('expired',$order->fresh()->status);
        $this->assertSame(0,$order->event->reservedItems()->count());
    }
    public function test_midtrans_fees_apply_after_discounts_and_manual_proof_is_rejected(): void {
        $method=$this->midtrans(); $this->fakeMidtrans(); $order=$this->reserve($this->event());
        \App\Models\TicketPromo::create(['code'=>'HALF','type'=>'percent','value'=>50]);
        app(\App\Services\TicketPromotions::class)->apply($order,'HALF');
        app(\App\Services\MidtransPayments::class)->start($order,81875);
        $this->assertSame(75000,$order->fresh()->payment_snapshot['subtotal']);
        $this->assertSame(1875,$order->fresh()->payment_snapshot['fees']['processing']);
        $other=$this->reserve($this->event());
        $this->expectException(ValidationException::class);
        app(TicketBooking::class)->submitProof($other,$method,'proof.png');
    }
    public function test_login_returns_to_concert_list_instead_of_saved_payment_page(): void {
        $customer=$this->customer(); $order=$this->reserve($this->event(),$customer);
        $this->get(route('tickets.order',$order))->assertRedirect(route('tickets.login'));
        $this->post(route('tickets.login.submit'),['email'=>$customer->email,'password'=>'StrongPass123!'])
            ->assertRedirect(route('tickets.events'))->assertSessionMissing('url.intended');
        $this->assertAuthenticatedAs($customer,'customer');
    }
    public function test_registration_verification_returns_to_concert_list(): void {
        Mail::fake();
        $event=$this->event();
        $this->get(route('tickets.select',$event))->assertRedirect(route('tickets.login'));
        $this->post(route('tickets.register.submit'),['name'=>'Listener','dob'=>'02/01/1990','email'=>'listener@example.test','phone'=>'08123456789','password'=>'StrongPass123!','password_confirmation'=>'StrongPass123!'])
            ->assertRedirect(route('tickets.verify'));
        $customer=Customer::where('email','listener@example.test')->firstOrFail();
        $customer->update(['otp_hash'=>Hash::make('123456')]);
        $this->post(route('tickets.verify.submit'),['otp'=>'123456'])
            ->assertRedirect(route('tickets.events'))->assertSessionMissing('url.intended');
        $this->assertNotNull($customer->fresh()->email_verified_at);
        $this->assertSame(0,TicketOrder::count());
    }
    public function test_unverified_login_returns_to_concert_list_after_otp(): void {
        $customer=$this->customer(false); $order=$this->reserve($this->event(),$customer);
        $code=app(TicketDelivery::class)->issueOtp($customer);
        $this->get(route('tickets.order',$order))->assertRedirect(route('tickets.login'));
        $this->post(route('tickets.login.submit'),['email'=>$customer->email,'password'=>'StrongPass123!'])
            ->assertRedirect(route('tickets.verify'));
        $this->post(route('tickets.verify.submit'),['otp'=>$code])
            ->assertRedirect(route('tickets.events'))->assertSessionMissing('url.intended');
    }
    public function test_event_removal_hides_event_and_preserves_existing_bookings(): void {
        $event=$this->event(); $customer=$this->customer(); $order=$this->reserve($event,$customer);
        $staff=$this->staff();
        $this->assertSame(0,$event->RowStatus);
        $this->actingAs($staff,'portal')->delete(route('portal.ticketing.events.destroy',$event))->assertRedirect(route('portal.ticketing.events'));
        $this->assertDatabaseHas('ticket_events',['id'=>$event->id,'RowStatus'=>-1]);
        $this->assertDatabaseHas('ticket_orders',['id'=>$order->id,'RowStatus'=>0]);
        $this->assertDatabaseHas('ticket_audit_logs',['action'=>'event.removed','subject'=>(string)$event->id,'actor_id'=>$staff->id]);
        $this->get(route('portal.ticketing.events'))->assertOk()->assertDontSee($event->title);
        $this->get(route('tickets.events'))->assertOk()->assertDontSee($event->title);
        $this->get(route('portal.ticketing.events.edit',$event))->assertNotFound();
        $this->actingAs($customer,'customer')->get(route('tickets.select',$event))->assertNotFound();
        $this->post(route('tickets.reserve',$event),['quantities'=>[$event->classes->first()->id=>1]])->assertNotFound();
        $this->get(route('tickets.order',$order))->assertOk()->assertSee($event->title);
        app(TicketBooking::class)->submitProof($order,TicketPaymentMethod::first(),'proof.png');
        app(TicketBooking::class)->review($order,$staff->id,true,null);
        $this->get(route('tickets.receipt',$order->reference))->assertOk();
    }
    public function test_qr_verification_is_private_to_owner_and_active_ticket_staff(): void {
        $owner=$this->customer(); $order=$this->reserve($this->event(),$owner);
        foreach (['tickets.receipt'=>$order->reference,'tickets.validate'=>$order->items->first()->token] as $route=>$key) {
            $this->get(route($route,$key))->assertForbidden()->assertDontSee($order->booking_code);
            $this->actingAs($this->customer(),'customer')->get(route($route,$key))->assertForbidden();
            $this->actingAs($owner,'customer')->get(route($route,$key))->assertOk()->assertSee($order->booking_code);
            auth('customer')->logout();
            foreach (['administrator','adm2','ticket_operator'] as $role) {
                $staff=$this->staff($role);
                $this->actingAs($staff,'portal')->get(route($route,$key))->assertOk();
                $staff->update(['is_active'=>false]);
                $this->get(route($route,$key))->assertForbidden();
                auth('portal')->logout();
            }
            $this->actingAs($this->staff('member'),'portal')->get(route($route,$key))->assertForbidden();
            auth('portal')->logout();
        }
    }
    public function test_all_promo_types_calculate_on_selected_tickets_and_never_add_inventory(): void {
        $order=$this->reserve($this->event('free',10),null,4);
        $order->items->first()->update(['price'=>100000]); // mixed prices; cheapest is free first
        foreach ([['percent',25,137500],['fixed',900000,550000],['bogo',0,250000],['bundle',0,250000],['free_ticket',0,100000]] as [$type,$value,$discount]) {
            \App\Models\TicketPromo::create(['code'=>strtoupper($type),'type'=>$type,'value'=>$value,'buy_quantity'=>2,'free_quantity'=>2]);
            app(\App\Services\TicketPromotions::class)->apply($order,$type);
            $this->assertSame($discount,$order->fresh()->discount);
            $this->assertSame(550000-$discount,$order->fresh()->total);
            $this->assertSame(4,$order->items()->count());
        }
        app(\App\Services\TicketPromotions::class)->apply($order,null);
        $this->assertSame(550000,$order->fresh()->total);
        $this->assertNull($order->fresh()->ticket_promo_id);
    }
    public function test_promo_eligibility_expiry_and_minimum_are_enforced(): void {
        $order=$this->reserve($this->event());
        $promo=\App\Models\TicketPromo::create(['code'=>'SPECIAL','type'=>'bogo','user_specific'=>true]);
        $this->actingAs($order->customer,'customer');
        $apply=fn()=>$this->post(route('tickets.promo',$order),['promo_code'=>'SPECIAL']);
        $apply()->assertSessionHasErrors('promo_code');
        $promo->customers()->attach($order->customer_id);
        $apply()->assertSessionHasErrors('promo_code'); // BOGO needs two selected tickets
        $promo->update(['type'=>'free_ticket','expires_at'=>now()->subSecond()]);
        $apply()->assertSessionHasErrors('promo_code');
        $promo->update(['expires_at'=>null,'active'=>false]);
        $apply()->assertSessionHasErrors('promo_code');
        $promo->update(['active'=>true]);
        $this->flushSession();
        $apply()->assertSessionHasNoErrors()->assertSessionHas('promo_success');
        $this->get(route('tickets.order',$order))->assertOk()->assertSee('promo-success')->assertSee('Hapus promo')->assertDontSee('Gunakan promo')->assertDontSee('<dialog',false)->assertDontSee('showModal',false)->assertDontSee('id="proof"',false);
        $this->post(route('tickets.free',$order))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('paid',$order->fresh()->status);
        $this->assertSame(0,$order->fresh()->total);
        $this->post(route('tickets.free',$order))->assertSessionHasErrors();
    }
    public function test_single_use_and_daily_limits_hold_and_release_usage(): void {
        $event=$this->event('free',10); $customer=$this->customer();
        $first=$this->reserve($event,$customer); $second=$this->reserve($event,$customer); $other=$this->reserve($event);
        $promo=\App\Models\TicketPromo::create(['code'=>'LIMIT','type'=>'percent','value'=>10,'single_use'=>true,'daily_limit'=>1]);
        $service=app(\App\Services\TicketPromotions::class);
        $service->apply($first,'LIMIT');
        $this->actingAs($customer,'customer')->post(route('tickets.promo',$second),['promo_code'=>'LIMIT'])->assertSessionHasErrors('promo_code');
        $this->actingAs($other->customer,'customer')->post(route('tickets.promo',$other),['promo_code'=>'LIMIT'])->assertSessionHasErrors('promo_code');
        app(TicketBooking::class)->cancel($first);
        $service->apply($second,'LIMIT');
        $service->apply($second,'LIMIT'); // idempotent, does not count itself
        $second->update(['expires_at'=>now()->subSecond()]);
        $service->apply($other,'LIMIT');
        $this->assertSame(15000,$other->fresh()->discount);
        $this->assertSame(1,\App\Models\TicketPromo::count());
    }
    public function test_promo_is_revalidated_when_submitting_payment_and_cannot_change_paid_order(): void {
        $order=$this->reserve($this->event());
        $promo=\App\Models\TicketPromo::create(['code'=>'PAY','type'=>'percent','value'=>10]);
        app(\App\Services\TicketPromotions::class)->apply($order,'PAY');
        $promo->update(['value'=>20]);
        $this->actingAs($order->customer,'customer')->post(route('tickets.proof',$order),['method'=>TicketPaymentMethod::first()->id,'proof'=>UploadedFile::fake()->image('proof.png')])->assertSessionHasErrors('booking');
        $this->assertSame(135000,$order->fresh()->total);
        $this->assertSame('awaiting_payment',$order->fresh()->status);
        $this->assertCount(0,Storage::disk('local')->allFiles('ticket-proofs'));
        app(\App\Services\TicketPromotions::class)->apply($order,'PAY');
        app(TicketBooking::class)->submitProof($order,TicketPaymentMethod::first(),'proof.png');
        $this->assertSame(120000,$order->fresh()->total);
        $this->post(route('tickets.promo',$order),['promo_code'=>''])->assertSessionHasErrors('promo_code');
    }
    public function test_promo_portal_permissions_bulk_customers_and_validation(): void {
        $a=$this->customer(); $b=$this->customer();
        $data=['code'=>' bulk ','type'=>'percent','value'=>15,'active'=>1,'user_specific'=>1,'single_use'=>1,'daily_limit'=>5,'customer_emails'=>$a->email.",\n".$b->email];
        $this->post(route('portal.ticketing.promos.store'),$data)->assertRedirect(route('portal.login'));
        $this->actingAs($this->staff('member'),'portal')->post(route('portal.ticketing.promos.store'),$data)->assertForbidden();
        $this->actingAs($this->staff('ticket_operator'),'portal')->post(route('portal.ticketing.promos.store'),$data)->assertSessionHasNoErrors()->assertRedirect();
        $promo=\App\Models\TicketPromo::firstOrFail();
        $this->assertSame('BULK',$promo->code); $this->assertSame(2,$promo->customers()->count());
        $this->get(route('portal.ticketing.promos',['edit'=>$promo->id]))->assertOk()->assertSee('BULK')->assertSee($a->email);
        $this->post(route('portal.ticketing.promos.update',$promo),array_merge($data,['value'=>101]))->assertSessionHasErrors('value');
        $this->post(route('portal.ticketing.promos.update',$promo),array_merge($data,['customer_emails'=>'missing@example.test']))->assertSessionHasErrors('customer_emails');
        $this->assertSame(2,$promo->customers()->count());
    }
    public function test_daily_limit_resets_at_jakarta_midnight_and_deleted_paid_orders_still_count_for_single_use(): void {
        $this->travelTo(\Carbon\Carbon::parse('2030-01-01 23:50:00','Asia/Jakarta'));
        try {
            $customer=$this->customer(); $event=$this->event('free',10);
            $first=$this->reserve($event,$customer);
            $promo=\App\Models\TicketPromo::create(['code'=>'DAY','type'=>'free_ticket','single_use'=>true,'daily_limit'=>1]);
            app(\App\Services\TicketPromotions::class)->apply($first,'DAY');
            app(TicketBooking::class)->completeFree($first);
            app(TicketBooking::class)->remove($first,$this->staff()->id);
            $this->travelTo(\Carbon\Carbon::parse('2030-01-02 00:01:00','Asia/Jakarta'));
            $other=$this->reserve($event);
            app(\App\Services\TicketPromotions::class)->apply($other,'DAY');
            $this->assertSame(0,$other->fresh()->total);
            $repeat=$this->reserve($event,$customer);
            $this->actingAs($customer,'customer')->post(route('tickets.promo',$repeat),['promo_code'=>'DAY'])->assertSessionHasErrors('promo_code');
        } finally { $this->travelBack(); }
    }
    public function test_numbered_promo_keeps_selected_seats_and_owner_only_mutations(): void {
        $event=$this->event('numbered',3); $owner=$this->customer();
        $order=app(TicketBooking::class)->reserve($owner,$event,['seats'=>$event->seats->take(2)->pluck('id')->all()]);
        \App\Models\TicketPromo::create(['code'=>'PAIR','type'=>'bogo']);
        $this->actingAs($this->customer(),'customer')->post(route('tickets.promo',$order),['promo_code'=>'PAIR'])->assertForbidden();
        $this->post(route('tickets.free',$order))->assertForbidden();
        $this->actingAs($owner,'customer')->post(route('tickets.promo',$order),['promo_code'=>'PAIR'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(150000,$order->fresh()->total);
        $this->assertSame(['A1','A2'],$order->items->pluck('seat_label')->all());
        $this->assertSame(1,$event->availability()['remaining']);
        $this->post(route('tickets.free',$order))->assertSessionHasErrors('booking');
    }
    public function test_promo_migration_grants_portal_menu_to_authorized_roles(): void {
        $this->assertTrue(Schema::hasColumn('ticket_orders','discount'));
        $this->assertSame(1,DB::table('portal_menus')->where('key','ticketing.promos')->count());
        foreach (['administrator','adm2','ticket_operator'] as $role) {
            $this->assertContains('ticketing.promos',$this->staff($role)->accessibleMenuKeys());
        }
        $this->assertNotContains('ticketing.promos',$this->staff('member')->accessibleMenuKeys());
    }
    public function test_removed_paid_booking_preserves_history_and_releases_numbered_seat(): void {
        $event=$this->event('numbered',1); $customer=$this->customer(); $staff=$this->staff();
        $booking=app(TicketBooking::class);
        $order=$booking->reserve($customer,$event,['seats'=>[$event->seats->first()->id]]);
        $item=$order->items->first();
        $booking->submitProof($order,TicketPaymentMethod::first(),'proof.png');
        $booking->review($order,$staff->id,true,'Verified');
        $this->actingAs($staff,'portal')->delete(route('portal.ticketing.orders.destroy',$order))->assertRedirect(route('portal.ticketing.orders'));
        $this->assertDatabaseHas('ticket_orders',['id'=>$order->id,'RowStatus'=>-1,'status'=>'paid','proof_path'=>'proof.png','review_note'=>'Verified','total'=>150000]);
        $this->assertDatabaseHas('ticket_order_items',['id'=>$item->id,'RowStatus'=>-1,'token'=>$item->token]);
        $this->assertDatabaseHas('ticket_audit_logs',['action'=>'payment.approved','subject'=>$order->reference]);
        $this->assertDatabaseHas('ticket_audit_logs',['action'=>'booking.removed','subject'=>$order->reference,'actor_id'=>$staff->id]);
        $this->assertSame(1,$event->availability()['remaining']);
        $this->get(route('portal.ticketing.orders'))->assertOk()->assertDontSee($order->reference);
        $this->get(route('portal.ticketing.payments'))->assertOk()->assertDontSee($order->reference);
        $this->get(route('portal.ticketing.orders.show',$order))->assertNotFound();
        $this->actingAs($customer,'customer')->get(route('tickets.orders'))->assertOk()->assertDontSee($order->booking_code);
        foreach (['tickets.order'=>[$order],'tickets.booking-qr'=>[$order],'tickets.qr'=>[$item],'tickets.validate'=>[$item->token],'tickets.receipt'=>[$order->reference]] as $route=>$args) {
            $this->get(route($route,$args))->assertNotFound();
        }
        $this->assertFalse(app(TicketDelivery::class)->tickets($order));
        $replacement=$booking->reserve($customer,$event,['seats'=>[$item->ticket_seat_id]]);
        $this->assertNotSame($order->id,$replacement->id);
    }
    public function test_only_admins_can_remove_events_and_bookings(): void {
        $event=$this->event(); $order=$this->reserve($event);
        $this->delete(route('portal.ticketing.events.destroy',$event))->assertRedirect(route('portal.login'));
        $this->actingAs($this->staff('ticket_operator'),'portal');
        $this->delete(route('portal.ticketing.events.destroy',$event))->assertForbidden();
        $this->delete(route('portal.ticketing.orders.destroy',$order))->assertForbidden();
        $this->get(route('portal.ticketing.orders'))->assertOk()->assertDontSee('Delete booking');
        $this->assertDatabaseHas('ticket_events',['id'=>$event->id,'RowStatus'=>0]);
        $this->assertDatabaseHas('ticket_orders',['id'=>$order->id,'RowStatus'=>0]);
    }
    public function test_customer_cancellation_soft_removes_booking_and_keeps_event_inventory_locked(): void {
        $event=$this->event(); $customer=$this->customer(); $order=$this->reserve($event,$customer);
        $item=$order->items->first();
        $this->actingAs($customer,'customer')->post(route('tickets.cancel',$order))->assertRedirect(route('tickets.orders'));
        $this->assertDatabaseHas('ticket_orders',['id'=>$order->id,'RowStatus'=>-1,'status'=>'cancelled']);
        $this->assertDatabaseHas('ticket_order_items',['id'=>$item->id,'RowStatus'=>-1]);
        $this->get(route('tickets.orders'))->assertOk()->assertDontSee($order->booking_code);
        $this->actingAs($this->staff(),'portal')->post(route('portal.ticketing.events.update',$event),['title'=>$event->title,'starts_at'=>'2030-10-01T19:00','location'=>'Jakarta','seating_type'=>'free','published'=>1,'classes_json'=>json_encode([['name'=>'Changed','price'=>1,'color'=>'#111111','capacity'=>999]])])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ticket_classes',['id'=>$item->ticket_class_id,'name'=>'Regular','price'=>150000,'capacity'=>3]);
    }
    public function test_row_status_migration_defaults_existing_records_to_active(): void {
        $eventId=DB::table('ticket_events')->insertGetId(['title'=>'Existing concert','starts_at'=>now()->addDay(),'location'=>'Jakarta','seating_type'=>'free']);
        $classId=DB::table('ticket_classes')->insertGetId(['ticket_event_id'=>$eventId,'name'=>'Regular','color'=>'#111111','price'=>100,'capacity'=>3]);
        $orderId=DB::table('ticket_orders')->insertGetId(['customer_id'=>$this->customer()->id,'ticket_event_id'=>$eventId,'reference'=>(string)\Illuminate\Support\Str::uuid(),'booking_code'=>'ABCDE','status'=>'paid','total'=>100]);
        $itemId=DB::table('ticket_order_items')->insertGetId(['ticket_order_id'=>$orderId,'ticket_class_id'=>$classId,'class_name'=>'Regular','price'=>100,'token'=>(string)\Illuminate\Support\Str::uuid()]);
        $migration=require database_path('migrations/2026_10_08_000003_add_ticket_row_status.php');
        $migration->up();
        foreach (['ticket_events'=>$eventId,'ticket_orders'=>$orderId,'ticket_order_items'=>$itemId] as $table=>$id) {
            $this->assertDatabaseHas($table,['id'=>$id,'RowStatus'=>0]);
        }
    }
    public function test_storefront_lists_both_seating_types_until_their_start_time(): void {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-07 23:41:00','Asia/Jakarta')->utc());
        try {
            $free=$this->event('free');
            $free->update(['title'=>'Future free concert','starts_at'=>\Carbon\Carbon::parse('2026-10-08 06:45:00','Asia/Jakarta')->utc()]);
            $numbered=$this->event('numbered');
            $numbered->update(['title'=>'Evening numbered concert','starts_at'=>now()->addMinute()]);
            $draft=$this->event();
            $draft->update(['title'=>'Unpublished concert','published'=>false]);
            $this->get(route('tickets.events'))->assertOk()->assertSee($free->title)->assertSee($numbered->title)->assertDontSee($draft->title);

            $this->travel(1)->minutes();
            $this->get(route('tickets.events'))->assertOk()->assertSee($free->title)->assertDontSee($numbered->title);
            $this->actingAs($this->staff(),'portal')->get(route('portal.ticketing.events'))
                ->assertOk()->assertSee($numbered->title)->assertSee('Hidden from storefront — start time reached')->assertSee('Visible on storefront');
            $this->get(route('portal.ticketing.events.edit',$numbered))->assertOk()->assertSee('Published concerts appear on the storefront until their start time');
        } finally {
            $this->travelBack();
        }
    }
    public function test_customer_guard_and_verification_are_separate_from_portal(): void {
        $event=$this->event();
        $this->actingAs($this->staff(),'portal')->get(route('tickets.select',$event))->assertRedirect(route('tickets.login'));
        $this->actingAs($this->customer(false),'customer')->get(route('tickets.select',$event))->assertRedirect(route('tickets.verify'));
        $this->actingAs($this->customer(),'customer')->get(route('tickets.select',$event))->assertOk()->assertSee('Pilih pengalaman Anda.');
    }
    public function test_registration_hashes_password_and_requires_email_otp(): void {
        $this->post(route('tickets.register'),['name'=>'Listener','dob'=>'02/01/1990','email'=>'LISTENER@example.test','phone'=>'+628123456789','password'=>'StrongPass123!','password_confirmation'=>'StrongPass123!'])->assertRedirect(route('tickets.verify'));
        $c=Customer::where('email','listener@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('StrongPass123!',$c->password));
        $this->assertNull($c->email_verified_at);$this->assertNotNull($c->otp_hash);
        $this->assertFalse(auth('portal')->check());
    }
    public function test_otp_is_hashed_limited_and_single_use(): void {
        $c=$this->customer(false);$d=app(TicketDelivery::class);$code=$d->issueOtp($c);
        $this->assertNotSame($code,$c->fresh()->otp_hash);
        for($i=0;$i<5;$i++)$this->assertFalse($d->verifyOtp($c,'000000'));
        $this->assertFalse($d->verifyOtp($c,$code));
        $new=$d->issueOtp($c,true,1);$this->assertTrue($d->verifyOtp($c,$new));$this->assertFalse($d->verifyOtp($c,$new));
        $this->assertDatabaseHas('ticket_audit_logs',['action'=>'otp.manual_generated','actor_id'=>1]);
    }
    public function test_expired_otp_fails(): void {
        $c=$this->customer(false);$d=app(TicketDelivery::class);$code=$d->issueOtp($c);$this->travel(11)->minutes();
        $this->assertFalse($d->verifyOtp($c,$code));$this->travelBack();
    }
    public function test_free_inventory_cannot_be_oversold(): void {
        $e=$this->event('free',2);$order=$this->reserve($e,null,2);
        $this->assertSame(300000,(int)$order->total);$this->assertCount(2,$order->items);
        $this->expectException(ValidationException::class);$this->reserve($e);
    }
    public function test_numbered_seat_cannot_be_reserved_twice_or_from_another_event(): void {
        $e=$this->event('numbered');$seat=$e->seats()->first();
        $booking=app(TicketBooking::class);$booking->reserve($this->customer(),$e,['seats'=>[$seat->id]]);
        try{$booking->reserve($this->customer(),$e,['seats'=>[$seat->id]]);$this->fail('Duplicate seat allowed');}catch(ValidationException $expected){}
        $other=$this->event('numbered');
        $this->expectException(ValidationException::class);$booking->reserve($this->customer(),$other,['seats'=>[$seat->id]]);
    }
    public function test_expired_holds_release_inventory_and_reject_late_proof(): void {
        $e=$this->event('free',1);$first=$this->reserve($e);$this->travel(31)->minutes();$second=$this->reserve($e);
        $this->assertSame('expired',$first->fresh()->status);$this->assertNotSame($first->id,$second->id);
        $this->expectException(ValidationException::class);
        app(TicketBooking::class)->submitProof($first,TicketPaymentMethod::first(),'proof.png');
    }
    public function test_review_keeps_inventory_then_rejection_releases_it(): void {
        $e=$this->event('free',1);$o=$this->reserve($e);$b=app(TicketBooking::class);
        $b->submitProof($o,TicketPaymentMethod::first(),'proof.png');
        $this->travel(2)->days();$this->assertSame(0,$e->availability()['remaining']);
        $b->review($o,1,false,'No matching transfer');$this->assertSame(1,$e->availability()['remaining']);$this->travelBack();
    }
    public function test_approval_is_single_use_and_qr_png_is_real(): void {
        $e=$this->event();$o=$this->reserve($e);$b=app(TicketBooking::class);$b->submitProof($o,TicketPaymentMethod::first(),'proof.png');$b->review($o,1,true,null);
        $png=app(TicketDelivery::class)->qr(route('tickets.validate',$o->items->first()->token));
        $this->assertSame("\x89PNG\r\n\x1a\n",substr($png,0,8));
        $this->assertTrue(app(TicketDelivery::class)->tickets($o));$this->assertNotNull($o->fresh()->tickets_emailed_at);
        $this->expectException(ValidationException::class);$b->review($o,1,true,null);
    }
    public function test_customer_cannot_read_another_booking_or_its_qr(): void {
        $o=$this->reserve($this->event());$this->actingAs($this->customer(),'customer')->get(route('tickets.order',$o))->assertForbidden();
        $this->get(route('tickets.qr',$o->items->first()))->assertForbidden();
    }
    public function test_ticket_operator_can_review_but_cannot_manage_configuration_or_otp(): void {
        $this->actingAs($this->staff('ticket_operator'),'portal');
        $this->get(route('portal.ticketing.payments'))->assertOk();
        $this->get(route('portal.ticketing.events'))->assertForbidden();
        $this->post(route('portal.ticketing.customers.otp',$this->customer(false)))->assertForbidden();
    }
    public function test_proof_upload_and_approval_http_flow(): void {
        $c=$this->customer();$o=$this->reserve($this->event(),$c);
        $this->actingAs($c,'customer')->post(route('tickets.proof',$o),['method'=>TicketPaymentMethod::first()->id,'proof'=>UploadedFile::fake()->image('proof.png')])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('payment_review',$o->fresh()->status);Storage::disk('local')->assertExists($o->fresh()->proof_path);
        $this->actingAs($this->staff('ticket_operator'),'portal')->post(route('portal.ticketing.review',$o),['decision'=>'approve'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('paid',$o->fresh()->status);
        $this->actingAs($c,'customer')->get(route('tickets.order',$o))->assertOk()->assertSee('QR pemesanan Anda');
        $this->get(route('tickets.validate',$o->items->first()->token))->assertOk()->assertSee('Tiket valid.')->assertDontSee($c->email);
    }
    public function test_disabled_payment_method_cannot_accept_proof(): void {
        $o=$this->reserve($this->event());$m=TicketPaymentMethod::first();$m->update(['active'=>false]);
        $this->expectException(ValidationException::class);app(TicketBooking::class)->submitProof($o,$m,'proof.png');
    }
    public function test_layout_rejects_overlapping_seats(): void {
        $this->expectException(ValidationException::class);
        app(TicketLayout::class)->parse(json_encode(['version'=>1,'seats'=>[['label'=>'A1','class'=>'VIP','x'=>0,'y'=>0],['label'=>'A2','class'=>'VIP','x'=>0,'y'=>0]]]));
    }
    public function test_removing_used_venue_disables_events_but_preserves_bookings(): void {
        $venue=TicketVenue::create(['name'=>'Removed hall','layout'=>['version'=>1,'seats'=>[['label'=>'A1','class'=>'Regular','x'=>0,'y'=>0]]]]);
        $event=$this->event('numbered');$event->update(['ticket_venue_id'=>$venue->id]);
        $other=$this->event();
        $customer=$this->customer();
        $order=app(TicketBooking::class)->reserve($customer,$event,['seats'=>[$event->seats()->first()->id]]);
        $this->actingAs($this->staff('singers'),'portal')->delete(route('portal.ticketing.venues.destroy',$venue))->assertForbidden();
        $this->assertDatabaseHas('ticket_venues',['id'=>$venue->id]);
        $this->actingAs($this->staff(),'portal')->delete(route('portal.ticketing.venues.destroy',$venue))->assertRedirect(route('portal.ticketing.venues'));
        $this->assertDatabaseMissing('ticket_venues',['id'=>$venue->id]);
        $this->assertFalse($event->fresh()->published);$this->assertNull($event->fresh()->ticket_venue_id);
        $this->assertTrue($other->fresh()->published);
        $this->assertSame(3,$event->seats()->count());$this->assertSame(1,$order->items()->count());
        $this->assertDatabaseHas('ticket_audit_logs',['action'=>'venue.removed','subject'=>(string)$venue->id]);
        $this->actingAs($customer,'customer')->get(route('tickets.select',$event))->assertNotFound();
        $this->post(route('tickets.reserve',$event),['seats'=>[$event->seats()->latest('id')->first()->id]])->assertSessionHasErrors();
        $this->post(route('portal.ticketing.events.update',$event),['title'=>$event->title,'starts_at'=>'2030-10-01T19:00','location'=>'Jakarta','seating_type'=>'numbered','published'=>1,'classes_json'=>'[]'])->assertSessionHasErrors('ticket_venue_id');
        $this->assertFalse($event->fresh()->published);
        $unused=TicketVenue::create(['name'=>'Unused','layout'=>$venue->layout]);
        $this->delete(route('portal.ticketing.venues.destroy',$unused))->assertRedirect();
        $this->assertDatabaseMissing('ticket_venues',['id'=>$unused->id]);
    }
    public function test_gereja_layout_geometry_and_divider_round_trip(): void {
        $layout=app(TicketLayout::class)->parse(file_get_contents(base_path('docs/gereja-toraja-jakarta.seating.json')));
        $this->assertCount(450,$layout['seats']);
        $this->assertSame([['label'=>'BALCONY','y'=>16]],$layout['dividers']);
        $rows=collect($layout['seats'])->groupBy('y');
        foreach(range(0,14) as $y) {
            $this->assertCount(20,$rows[$y]);
            $this->assertSame(range(1,20),$rows[$y]->map(fn($s)=>(int)substr($s['label'],1))->all());
            $this->assertEquals(33,$rows[$y]->min('x')+$rows[$y]->max('x'));
            $this->assertSame(1.25,$rows[$y][5]['x']-$rows[$y][4]['x']);
            $this->assertSame(1.25,$rows[$y][15]['x']-$rows[$y][14]['x']);
            $this->assertSame(5,$rows[$y][10]['x']-$rows[$y][9]['x']);
        }
        foreach(range(18,22) as $y) {$this->assertCount(30,$rows[$y]);$this->assertSame(33,$rows[$y]->min('x')+$rows[$y]->max('x'));}
        $this->actingAs($this->staff(),'portal')->post(route('portal.ticketing.venues.store'),['name'=>'Gereja Toraja Jakarta','layout'=>json_encode($layout)])->assertSessionHasNoErrors();
        $venue=TicketVenue::firstOrFail();
        $this->get(route('portal.ticketing.venues.export',$venue))->assertExactJson($layout);
        $this->post(route('portal.ticketing.events.store'),['title'=>'Balcony concert','starts_at'=>'2030-10-01T19:00','location'=>'Jakarta','seating_type'=>'numbered','ticket_venue_id'=>$venue->id,'classes_json'=>json_encode([['name'=>'Regular','color'=>'#713cce','price'=>100000,'capacity'=>0]])])->assertSessionHasNoErrors();
        $event=TicketEvent::firstOrFail();$this->assertSame($layout['dividers'],$event->layout_dividers);
        $this->assertEquals(4.75,$event->seats()->where('label','A1')->firstOrFail()->x);
        $this->assertEquals(24.25,$event->seats()->where('label','A16')->firstOrFail()->x);
        $venue->update(['layout'=>['version'=>1,'seats'=>$layout['seats']]]);
        $event->update(['published'=>true]);
        $this->actingAs($this->customer(),'customer')->get(route('tickets.select',$event))->assertOk()->assertSee('BALCONY')->assertSee('venue-canvas');
    }
    public function test_layout_rejects_divider_overlapping_a_seat(): void {
        $this->expectException(ValidationException::class);
        app(TicketLayout::class)->parse(json_encode(['version'=>1,'seats'=>[['label'=>'A1','class'=>'Regular','x'=>0,'y'=>0]],'dividers'=>[['label'=>'BALCONY','y'=>0]]]));
    }
    public function test_admin_event_creation_snapshots_venue_and_prices(): void {
        $this->actingAs($this->staff(),'portal');
        $v=TicketVenue::create(['name'=>'Test Hall','layout'=>['version'=>1,'seats'=>[['label'=>'A1','class'=>'VIP','x'=>0,'y'=>0]]]]);
        $this->post(route('portal.ticketing.events.store'),['title'=>'Test concert','starts_at'=>'2030-10-01T19:00','location'=>'Test Hall','seating_type'=>'numbered','ticket_venue_id'=>$v->id,'classes_json'=>json_encode([['name'=>'VIP','color'=>'#713cce','price'=>300000,'capacity'=>0]]),'published'=>1,'thumbnail'=>UploadedFile::fake()->image('concert.jpg')])->assertSessionHasNoErrors()->assertRedirect(route('portal.ticketing.events'));
        $e=TicketEvent::firstOrFail();$this->assertSame(1,$e->classes->first()->capacity);$this->assertSame('A1',$e->seats->first()->label);
        $this->assertSame('12:00',$e->starts_at->format('H:i'));
        $this->get(route('portal.ticketing.events.edit',$e))->assertOk();
    }
    public function test_all_customer_and_admin_pages_render(): void {
        $e=$this->event();$this->event('numbered');
        foreach(['tickets.events','tickets.login','tickets.register'] as $route)$this->get(route($route))->assertOk();
        $this->actingAs($this->customer(),'customer');
        $this->get(route('tickets.select',TicketEvent::where('seating_type','numbered')->first()))->assertOk();
        $o=$this->reserve($e,auth('customer')->user());$this->get(route('tickets.order',$o))->assertOk();
        $this->get(route('tickets.orders'))->assertOk();
        $this->actingAs($this->staff(),'portal');
        foreach(['events','events.create','venues','venues.create','customers','methods','payments','orders'] as $r)$this->get(route('portal.ticketing.'.$r))->assertOk();
    }
    public function test_almost_sold_and_sold_out_badges_follow_inventory(): void {
        $e=$this->event('free',10);$this->reserve($e,null,9);
        $this->get(route('tickets.events'))->assertSee('Hampir Habis')->assertDontSee('Habis terjual');
        $this->reserve($e);$this->get(route('tickets.events'))->assertSee('Habis terjual');
    }
    public function test_forged_prices_use_server_prices(): void {
        $e=$this->event();$this->actingAs($this->customer(),'customer')->post(route('tickets.reserve',$e),['quantities'=>[$e->classes->first()->id=>2],'total'=>1,'price'=>1])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(300000,TicketOrder::first()->total);
    }
    public function test_cancellation_releases_inventory(): void {
        $e=$this->event('free',1);$o=$this->reserve($e);
        app(TicketBooking::class)->cancel($o);$this->assertSame(1,$e->availability()['remaining']);
    }
    public function test_booked_event_keeps_inventory_and_prices(): void {
        $this->actingAs($this->staff(),'portal');$e=$this->event();$o=$this->reserve($e);
        $this->post(route('portal.ticketing.events.update',$e),['title'=>'Updated concert','starts_at'=>'2030-10-01T19:00','location'=>'Jakarta','seating_type'=>'free','published'=>1,'classes_json'=>json_encode([['name'=>'Changed','price'=>1,'color'=>'#111111','capacity'=>999]])])->assertSessionHasNoErrors();
        $this->assertSame(150000,$e->fresh()->classes->first()->price);
        $this->assertSame(3,$e->fresh()->classes->first()->capacity);
        $this->assertSame(150000,$o->fresh()->total);
    }
    public function test_deactivated_customer_cannot_access_tickets(): void {
        $c=$this->customer();$c->update(['is_active'=>false]);
        $this->actingAs($c,'customer')->get(route('tickets.orders'))->assertRedirect(route('tickets.login'));
    }
    public function test_venue_import_export_and_default_class_json(): void {
        $this->actingAs($this->staff(),'portal');
        $layout=['version'=>1,'seats'=>[['label'=>'B1','class'=>'Balcony','x'=>4,'y'=>8]]];
        $this->post(route('portal.ticketing.venues.store'),['name'=>'Balcony Hall','layout'=>json_encode($layout)])->assertSessionHasNoErrors()->assertRedirect();
        $venue=TicketVenue::firstOrFail();
        $this->get(route('portal.ticketing.venues.export',$venue))->assertOk()->assertExactJson($layout);
        $this->get(route('portal.ticketing.venues.edit',$venue))->assertOk();
        $html=$this->get(route('portal.ticketing.events.create'))->assertOk()->getContent();
        $dom=new \DOMDocument();@$dom->loadHTML($html);
        $classes=json_decode($dom->getElementById('classes-json')->getAttribute('value'),true);
        $this->assertSame('Regular',$classes[0]['name']);
    }
    public function test_otp_cooldown_and_manual_generator(): void {
        $c=$this->customer(false);$d=app(TicketDelivery::class);$d->issueOtp($c);
        try{$d->issueOtp($c);$this->fail('OTP resend cooldown missing');}catch(ValidationException $expected){}
        $this->actingAs($this->staff(),'portal')->post(route('portal.ticketing.customers.otp',$c))->assertSessionHas('manual_otp')->assertSessionHasNoErrors();
    }
    public function test_qris_requires_image_before_activation(): void {
        $this->actingAs($this->staff(),'portal');$m=TicketPaymentMethod::where('type','qris')->first();
        $this->post(route('portal.ticketing.methods.update',$m),['name'=>'QRIS','instructions'=>'Scan to pay','active'=>1])->assertSessionHasErrors('qr_image');
        $this->assertFalse($m->fresh()->active);
    }
    public function test_asset_routes_support_root_front_controller(): void {
        $this->get(route('tickets.styles'))->assertOk()->assertHeader('Content-Type','text/css; charset=UTF-8');
        $this->get(route('tickets.designer'))->assertOk()->assertHeader('Content-Type','application/javascript');
    }
    public function test_legacy_migration_endpoint_requires_admin_post(): void {
        $this->get('/system/migrate?key=migratenow')->assertStatus(405);
        $this->post('/system/migrate')->assertRedirect(route('portal.login'));
        $this->actingAs($this->staff('ticket_operator'),'portal')->post('/system/migrate')->assertForbidden();
    }
    public function test_ticket_admin_uses_shared_portal_shell_and_sidebar(): void {
        $this->actingAs($this->staff(),'portal');
        foreach (['events','events.create','venues','venues.create','customers','methods','payments','orders'] as $page) {
            $response=$this->get(route('portal.ticketing.'.$page))->assertOk();
            $response->assertSee('id="sidebar"',false)->assertSee('class="topbar"',false)
                ->assertSee('portal-ticketing')->assertDontSee('class="admin-nav"',false)
                ->assertDontSee('class="ticket-app"',false)->assertDontSee(route('tickets.styles'),false);
            foreach (['events','venues','customers','methods','payments','orders'] as $menu) $response->assertSee(route('portal.ticketing.'.$menu),false);
        }
        $event=$this->event();
        $html=$this->get(route('portal.ticketing.events.edit',$event))->assertOk()->getContent();
        $dom=new \DOMDocument();@$dom->loadHTML($html);
        $xpath=new \DOMXPath($dom);
        $links=$xpath->query('//a[@href="'.route('portal.ticketing.events').'"]');
        $this->assertStringContainsString('active',$links->item(0)->getAttribute('class'));
    }
    public function test_operator_sidebar_contains_payment_approvals_and_orders(): void {
        $this->actingAs($this->staff('ticket_operator'),'portal');
        $response=$this->get(route('portal.ticketing.payments'))->assertOk();
        $response->assertSee('Payment Approvals')->assertSee('Order List')->assertSee('Payment Methods')->assertSee('id="sidebar"',false);
        foreach (['events','venues','customers'] as $page) $response->assertDontSee(route('portal.ticketing.'.$page),false);
    }
    public function test_ticket_navigation_obeys_portal_menu_permissions(): void {
        $staff=$this->staff();$this->actingAs($staff,'portal');
        $menuId=DB::table('portal_menus')->where('key','ticketing.venues')->value('id');
        DB::table('role_menu_permissions')->where('portal_menu_id',$menuId)->delete();
        $this->get(route('portal.ticketing.events'))->assertOk()->assertDontSee(route('portal.ticketing.venues'),false);
    }

    public function test_language_defaults_to_indonesian_and_remembers_english(): void {
        $this->get(route('tickets.register'))->assertOk()->assertSee('lang="id"',false)->assertSee('Tanggal lahir')->assertSee('DD/MM/YYYY');
        $this->post(route('tickets.language'),['locale'=>'en'])->assertRedirect()->assertSessionHas('ticket_locale','en');
        $this->get(route('tickets.register'))->assertOk()->assertSee('lang="en"',false)->assertSee('Date of birth')->assertDontSee('Tanggal lahir');
        $this->post(route('tickets.language'),['locale'=>'fr'])->assertSessionHasErrors('locale')->assertSessionHas('ticket_locale','en');
        $this->post(route('tickets.language'),['locale'=>'id'])->assertSessionHas('ticket_locale','id');
        $this->get(route('tickets.login'))->assertSee('Masuk');
    }
    public function test_registration_stores_day_first_date_and_normalizes_phone(): void {
        $this->post(route('tickets.register'),['name'=>'Listener','dob'=>'23/04/1990','email'=>'normalized@example.test','phone'=>'0812-3456-789','password'=>'StrongPass123!','password_confirmation'=>'StrongPass123!'])->assertSessionHasNoErrors()->assertRedirect(route('tickets.verify'));
        $c=Customer::where('email','normalized@example.test')->firstOrFail();
        $this->assertSame('1990-04-23',$c->dob->format('Y-m-d'));
        $this->assertSame('628123456789',$c->phone);
        foreach(['628123456789','+62 812 3456 789','8123456789','08123456789'] as $phone) {
            $this->assertSame('628123456789',TicketCustomerInput::normalize(['dob'=>'23/04/1990','phone'=>$phone])['phone']);
        }
    }
    public function test_birth_date_rejects_invalid_iso_and_future_dates(): void {
        foreach(['1990-04-23','31/02/1990',now()->addYear()->format('d/m/Y')] as $dob) {
            $this->post(route('tickets.register'),['name'=>'Listener','dob'=>$dob,'email'=>'invalid@example.test','phone'=>'08123456789','password'=>'StrongPass123!','password_confirmation'=>'StrongPass123!'])->assertSessionHasErrors('dob');
        }
        $this->assertSame(0,Customer::count());
    }
    public function test_portal_customer_edit_uses_day_first_date_and_normalized_phone(): void {
        $c=$this->customer();
        $this->actingAs($this->staff(),'portal')->get(route('portal.ticketing.customers'))->assertOk()->assertSee('01/01/1995');
        $this->post(route('portal.ticketing.customers.update',$c),['name'=>$c->name,'dob'=>'23/04/1990','phone'=>'08123456789','is_active'=>1])->assertSessionHasNoErrors();
        $this->assertSame('1990-04-23',$c->fresh()->dob->format('Y-m-d'));
        $this->assertSame('628123456789',$c->fresh()->phone);
    }
    public function test_booking_summary_groups_classes_and_only_shows_assigned_seats(): void {
        $c=$this->customer();$event=$this->event('numbered');
        $o=app(TicketBooking::class)->reserve($c,$event,['seats'=>$event->seats()->pluck('id')->all()]);
        $html=$this->actingAs($c,'customer')->get(route('tickets.order',$o))->assertOk()->assertSee('A1, A2, A3')->assertSee('3 Tiket')->getContent();
        $this->assertSame(1,substr_count($html,'class="row tiny booking-class"'));
        $this->assertStringContainsString('Rp 450.000',$html);
        $free=$this->reserve($this->event(),$c,2);
        $this->get(route('tickets.order',$free))->assertOk()->assertSee('2 Tiket')->assertSee('Bebas memilih kursi')->assertDontSee('class="booking-seats"',false);
    }
    public function test_portal_orders_expose_paid_barcodes_only_to_authorized_staff(): void {
        $o=$this->reserve($this->event());$other=$this->reserve($this->event());
        $this->get(route('portal.ticketing.orders'))->assertRedirect(route('portal.login'));
        $this->actingAs($this->staff('member'),'portal')->get(route('portal.ticketing.orders'))->assertForbidden();
        $this->get(route('portal.ticketing.orders.ticket-qr',[$o,$o->items->first()]))->assertForbidden();
        $this->actingAs($this->staff('ticket_operator'),'portal');
        $this->get(route('portal.ticketing.orders'))->assertOk()->assertSee($o->reference);
        $this->get(route('portal.ticketing.orders.show',$o))->assertOk()->assertDontSee(route('portal.ticketing.orders.qr',$o),false);
        $this->get(route('portal.ticketing.orders.qr',$o))->assertForbidden();
        $this->get(route('portal.ticketing.orders.ticket-qr',[$o,$o->items->first()]))->assertForbidden();
        $b=app(TicketBooking::class);$b->submitProof($o,TicketPaymentMethod::first(),'proof.png');$b->review($o,1,true,null);
        $this->get(route('portal.ticketing.orders.show',$o))->assertOk()->assertSee(route('portal.ticketing.orders.ticket-qr',[$o,$o->items->first()]),false);
        foreach(['portal.ticketing.orders.qr'=>[$o],'portal.ticketing.orders.ticket-qr'=>[$o,$o->items->first()]] as $route=>$args) {
            $response=$this->get(route($route,$args))->assertOk()->assertHeader('Content-Type','image/png');
            $this->assertSame("\x89PNG\r\n\x1a\n",substr($response->getContent(),0,8));
        }
        $this->get(route('portal.ticketing.orders.ticket-qr',[$o,$other->items->first()]))->assertForbidden();
        $this->get(route('portal.ticketing.orders',['q'=>$o->reference,'status'=>'paid']))->assertOk()->assertSee($o->reference)->assertDontSee($other->reference);
        $this->get(route('portal.ticketing.orders',['status'=>'rejected']))->assertOk()->assertDontSee($o->reference);
    }
    public function test_singer_management_permissions_menu_and_validation(): void {
        $this->actingAs($this->staff('ticket_operator'),'portal')->get(route('portal.singers.index'))->assertForbidden();
        $this->post(route('portal.singers.store'),['name'=>'Singer','referral_code'=>'REF'])->assertForbidden();
        $this->actingAs($this->staff(),'portal')->get(route('portal.singers.index'))->assertOk()->assertSee('User Management')->assertSee('Singer List');
        $this->post(route('portal.singers.store'),['name'=>'Maya','referral_code'=>'maya01','active'=>1])->assertSessionHasNoErrors()->assertRedirect();
        $singer=\App\Models\PortalSinger::firstOrFail();
        $this->assertSame('MAYA01',$singer->referral_code);
        $this->post(route('portal.singers.store'),['name'=>'Duplicate','referral_code'=>'maya01'])->assertSessionHasErrors('referral_code');
        $this->post(route('portal.singers.update',$singer),['name'=>'Maya Updated','referral_code'=>'MAYA01'])->assertSessionHasNoErrors();
        $this->assertFalse($singer->fresh()->active);
        $this->get(route('portal.singers.index'))->assertOk()->assertSee('Maya Updated');
    }
    public function test_checkout_snapshots_referral_and_displays_it_in_orders(): void {
        $singer=\App\Models\PortalSinger::create(['name'=>'Maya','referral_code'=>'MAYA01','active'=>true]);
        $c=$this->customer(); $order=$this->reserve($this->event(),$c);
        $this->actingAs($c,'customer')->get(route('tickets.order',$order))->assertOk()->assertSee('Maya');
        $this->post(route('tickets.proof',$order),['singer_id'=>$singer->id,'method'=>TicketPaymentMethod::first()->id,'proof'=>UploadedFile::fake()->image('proof.png')])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ticket_orders',['id'=>$order->id,'portal_singer_id'=>$singer->id,'singer_name'=>'Maya','referral_code'=>'MAYA01']);
        $singer->update(['name'=>'New name','referral_code'=>'NEW','active'=>false]);
        $this->get(route('tickets.orders'))->assertOk()->assertSee('Maya')->assertSee('MAYA01')->assertSee($order->booking_code);
        $this->actingAs($this->staff(),'portal');
        $html=$this->get(route('portal.ticketing.orders',['q'=>'MAYA01']))->assertOk()->assertSee($order->reference)->getContent();
        $dom=new \DOMDocument(); @$dom->loadHTML($html); $xpath=new \DOMXPath($dom);
        $this->assertSame('Open order',$xpath->query('//table/thead/tr/th[last()]')->item(0)->textContent);
        $this->get(route('portal.ticketing.orders',['q'=>$order->booking_code]))->assertOk()->assertSee($order->reference);
    }
    public function test_invalid_and_inactive_referrals_reject_proof_without_storing_files(): void {
        $singer=\App\Models\PortalSinger::create(['name'=>'Inactive Singer','referral_code'=>'OFF','active'=>false]);
        $c=$this->customer(); $order=$this->reserve($this->event(),$c);
        $this->actingAs($c,'customer')->get(route('tickets.order',$order))->assertOk()->assertDontSee('Inactive Singer');
        foreach ([$singer->id,99999] as $id) {
            $this->post(route('tickets.proof',$order),['singer_id'=>$id,'method'=>TicketPaymentMethod::first()->id,'proof'=>UploadedFile::fake()->image('proof.png')])->assertSessionHasErrors('singer_id');
        }
        $this->assertSame('awaiting_payment',$order->fresh()->status);
        $this->assertSame([],Storage::disk('local')->allFiles('ticket-proofs'));
        $this->expectException(ValidationException::class);
        app(TicketBooking::class)->submitProof($order,TicketPaymentMethod::first(),'proof.png',$singer->id);
    }
    public function test_booking_codes_and_ticket_labels_survive_payment_and_are_in_qr_views(): void {
        $c=$this->customer(); $o=$this->reserve($this->event(),$c,2); $reference=$o->reference; $code=$o->booking_code;
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{5}$/',$code);
        $this->assertSame($code.'/1',$o->items[0]->booking_label);
        $this->assertSame($code.'/2',$o->items[1]->booking_label);
        $numbered=$this->event('numbered');
        $other=app(TicketBooking::class)->reserve($c,$numbered,['seats'=>[$numbered->seats->first()->id]]);
        $this->assertNotSame($code,$other->booking_code);
        $this->assertSame($other->booking_code.'/A1',$other->items->first()->booking_label);
        $b=app(TicketBooking::class);$b->submitProof($o,TicketPaymentMethod::first(),'proof.png');$b->review($o,1,true,null);
        $this->assertSame($reference,$o->fresh()->reference);$this->assertSame($code,$o->fresh()->booking_code);
        $this->actingAs($c,'customer')->get(route('tickets.order',$o))->assertOk()->assertSee($code.'/1')->assertSee($code.'/2');
        $this->get(route('tickets.validate',$o->items[1]->token))->assertOk()->assertSee($code.'/2');
        $png=$this->get(route('tickets.booking-qr',$o))->assertOk()->getContent();
        $size=getimagesizefromstring($png); $this->assertGreaterThan($size[0],$size[1]);
        $this->assertTrue(app(TicketDelivery::class)->tickets($o));
        $this->actingAs($this->staff(),'portal')->get(route('portal.ticketing.orders.show',$o))->assertOk()->assertSee($code.'/1');
    }
    public function test_booking_code_migration_backfills_existing_orders_without_changing_references(): void {
        $customer=$this->customer(); $event=$this->event(); $reference=(string)\Illuminate\Support\Str::uuid(); $token=(string)\Illuminate\Support\Str::uuid();
        $id=DB::table('ticket_orders')->insertGetId(['reference'=>$reference,'customer_id'=>$customer->id,'ticket_event_id'=>$event->id,'status'=>'paid','total'=>150000,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('ticket_order_items')->insert(['ticket_order_id'=>$id,'ticket_class_id'=>$event->classes->first()->id,'class_name'=>'Regular','price'=>150000,'token'=>$token,'created_at'=>now(),'updated_at'=>now()]);
        $o=TicketOrder::findOrFail($id);
        $migration=require database_path('migrations/2026_10_07_000004_add_singers_and_booking_codes.php');
        $migration->up();
        $o->refresh();
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{5}$/',$o->booking_code);
        $this->assertSame($reference,$o->reference); $this->assertSame($token,$o->items->first()->token);
        $this->assertDatabaseHas('portal_menus',['key'=>'user_mgmt.singers','parent_id'=>DB::table('portal_menus')->where('key','user_mgmt')->value('id')]);
        $other=$this->reserve($this->event());
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('ticket_orders')->where('id',$other->id)->update(['booking_code'=>$o->booking_code]);
    }

}
