<?php
namespace App\Services;

use App\Models\{PortalSinger,TicketEvent,TicketOrder,TicketPaymentMethod};
use Illuminate\Support\Facades\{DB,Http};
use Illuminate\Validation\ValidationException;

class MidtransPayments {
    private function fail(string $message): void { throw ValidationException::withMessages(['payment'=>$message]); }

    public function testConnection(TicketPaymentMethod $method): array {
        if (!$method->server_key) return ['ok'=>false,'message'=>'Save a Midtrans server key before testing the connection.'];
        if (!in_array($method->environment,['sandbox','production'],true)) return ['ok'=>false,'message'=>'Save a valid Midtrans environment before testing.'];
        $host=$method->environment==='production'?'api.midtrans.com':'api.sandbox.midtrans.com';
        try {
            // An authenticated lookup of a unique, nonexistent order tests access
            // without creating a checkout, charging money, or changing any order.
            $response=Http::withBasicAuth($method->server_key,'')->acceptJson()
                ->connectTimeout(5)->timeout(10)->withoutRedirecting()
                ->get('https://'.$host.'/v2/connection-test-'.\Illuminate\Support\Str::uuid().'/status');
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['ok'=>false,'message'=>'Could not reach Midtrans. Check network connectivity and try again.'];
        }
        $code=(string)$response->json('status_code');
        if (in_array($response->status(),[401,403],true) || in_array($code,['401','403'],true)) {
            return ['ok'=>false,'message'=>'Midtrans rejected access. Check the saved server key and ensure it matches the selected environment.'];
        }
        // Midtrans can return its application-level 404 inside HTTP 200 or 404.
        // A generic web-server 404, malformed response, or outage is not success.
        if (in_array($response->status(),[200,404],true) && $code==='404') {
            return ['ok'=>true,'message'=>'Connected to Midtrans ('.$method->environment.'). The saved server key was accepted. No payment was created.'];
        }
        return ['ok'=>false,'message'=>'Midtrans returned an unexpected response (HTTP '.$response->status().'). Check your account configuration or try again later.'];
    }

    public function start(TicketOrder $original, int $expectedTotal, ?int $singerId=null): string {
        $order=DB::transaction(function() use($original,$expectedTotal,$singerId) {
            TicketEvent::withoutGlobalScope('active')->lockForUpdate()->findOrFail($original->ticket_event_id);
            $order=TicketOrder::lockForUpdate()->findOrFail($original->id);
            if ($order->status==='midtrans_pending') return $order;
            $method=TicketPaymentMethod::where('type','midtrans')->lockForUpdate()->firstOrFail();
            if (!$method->active || !$method->server_key) $this->fail('Midtrans is currently unavailable.');
            app(TicketPromotions::class)->applyLocked($order,$order->promo_snapshot['code']??null);
            if ($order->total<=0) $this->fail('Confirm your free tickets without payment.');
            $fees=$method->fees($order->total);
            $total=$order->total+array_sum($fees);
            if ($total!==$expectedTotal) $this->fail('The total or fees have changed. Review the updated amount before paying.');
            $singer=$singerId ? PortalSinger::lockForUpdate()->find($singerId) : null;
            if ($singerId && (!$singer || !$singer->active)) $this->fail('Please select an active singer.');
            // Commit the inventory hold before contacting the provider. A timeout must
            // never release seats for a payment that may have been created remotely.
            $order->update(['status'=>'midtrans_pending','total'=>$total,'ticket_payment_method_id'=>$method->id,
                'payment_snapshot'=>['name'=>$method->name,'type'=>'midtrans','subtotal'=>$order->total,'fees'=>$fees],
                'gateway_credentials'=>['server_key'=>$method->server_key,'environment'=>$method->environment],
                'expires_at'=>now()->addMinutes(30),'portal_singer_id'=>$singer?->id,'singer_name'=>$singer?->name,'referral_code'=>$singer?->referral_code]);
            return $order;
        },3);
        if ($order->gateway_url) return $order->gateway_url;
        // Serialize double clicks without keeping an inventory/event lock across HTTP.
        return DB::transaction(function() use($order) {
            $order=TicketOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->status!=='midtrans_pending') $this->fail('This payment is no longer pending.');
            if ($order->gateway_url) return $order->gateway_url;
            if ($order->expires_at->isPast()) $this->fail('This checkout has expired. Refresh payment status to resolve the booking.');
            $credentials=$order->gateway_credentials;
            $host=$credentials['environment']==='production'?'app.midtrans.com':'app.sandbox.midtrans.com';
            try {
                $response=Http::withBasicAuth($credentials['server_key'],'')->acceptJson()->timeout(20)->post('https://'.$host.'/snap/v1/transactions',[
                    'transaction_details'=>['order_id'=>$order->reference,'gross_amount'=>$order->total],
                    'enabled_payments'=>['other_qris'],
                    'customer_details'=>['first_name'=>$order->customer->name,'email'=>$order->customer->email],
                    'callbacks'=>['finish'=>route('tickets.order',$order)],
                    'expiry'=>['start_time'=>$order->expires_at->copy()->subMinutes(30)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s O'),'unit'=>'minutes','duration'=>30],
                ]);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $this->fail('Midtrans could not be reached. Your booking remains reserved. Retry or refresh payment status.');
            }
            $url=$response->json('redirect_url');
            if (!$response->successful() || !is_string($url) || parse_url($url,PHP_URL_SCHEME)!=='https' || parse_url($url,PHP_URL_HOST)!==$host) {
                // Keep provider diagnostics server-side; never log credentials or the full payload.
                $errors=$response->json('error_messages',[]);
                $errors=is_array($errors)?$errors:[$errors];
                $errors=array_map(function($error) use($credentials,$order) {
                    if (!is_string($error)) return 'Non-text provider error';
                    $sensitive=array_filter([$credentials['server_key'],$order->customer->name,$order->customer->email]);
                    return substr(str_replace($sensitive,'[redacted]',$error),0,500);
                },array_slice($errors,0,5));
                \Illuminate\Support\Facades\Log::warning('Midtrans checkout rejected',[
                    'order_reference'=>$order->reference,
                    'environment'=>$credentials['environment'],
                    'http_status'=>$response->status(),
                    'provider_errors'=>$errors,
                    'valid_redirect'=>is_string($url) && parse_url($url,PHP_URL_SCHEME)==='https' && parse_url($url,PHP_URL_HOST)===$host,
                ]);
                $this->fail('Midtrans checkout could not be opened. Your booking remains reserved; please contact the organizer if retrying does not help.');
            }
            $order->update(['gateway_url'=>$url]);
            return $url;
        });
    }

    public function sync(TicketOrder $order): void {
        $credentials=$order->gateway_credentials;
        if (!$credentials || $order->status!=='midtrans_pending') return;
        $host=$credentials['environment']==='production'?'api.midtrans.com':'api.sandbox.midtrans.com';
        $response=Http::withBasicAuth($credentials['server_key'],'')->acceptJson()->timeout(15)
            ->get('https://'.$host.'/v2/'.rawurlencode($order->reference).'/status');
        // 404 can mean Snap is still open without a payment selected. It is NOT
        // evidence that a remote checkout can no longer accept payment.
        if ((string)$response->json('status_code')==='404') {
            // The explicit blanket expiry closes both Snap and QRIS. Allow a
            // reconciliation grace period, then release abandoned, uncharged Snap pages.
            if ($order->expires_at && $order->expires_at->copy()->addMinutes(5)->isPast()) {
                DB::transaction(function() use($order) {
                    TicketEvent::withoutGlobalScope('active')->lockForUpdate()->findOrFail($order->ticket_event_id);
                    $locked=TicketOrder::lockForUpdate()->findOrFail($order->id);
                    if ($locked->status==='midtrans_pending') $locked->update(['status'=>'expired']);
                },3);
            }
            return;
        }
        $response->throw();
        $this->applyStatus($order,$response->json());
    }

    public function applyStatus(TicketOrder $original, array $data): void {
        $paid=DB::transaction(function() use($original,$data) {
            TicketEvent::withoutGlobalScope('active')->lockForUpdate()->findOrFail($original->ticket_event_id);
            $order=TicketOrder::lockForUpdate()->findOrFail($original->id);
            abort_unless(($data['order_id']??null)===$order->reference && isset($data['gross_amount']) && is_numeric($data['gross_amount']) && (float)$data['gross_amount']===(float)$order->total && ($data['currency']??null)==='IDR',422);
            if ($order->status!=='midtrans_pending') return false;
            $status=$data['transaction_status']??'';
            if ($status==='settlement' && in_array($data['payment_type']??'', ['qris','gopay','shopeepay'],true) && in_array($data['fraud_status']??'accept',['accept'],true)) {
                $order->update(['status'=>'paid','reviewed_at'=>now(),'expires_at'=>null]);
                TicketDelivery::audit('payment.midtrans_confirmed',$order->reference,null,['transaction_id'=>$data['transaction_id']??null]);
                return true;
            }
            if (in_array($status,['deny','cancel','expire'],true)) {
                $order->update(['status'=>$status==='expire'?'expired':'rejected','review_note'=>'Midtrans: '.$status]);
                TicketDelivery::audit('payment.midtrans_'.$status,$order->reference);
            }
            return false;
        },3);
        if ($paid) app(TicketDelivery::class)->tickets($original->fresh());
    }
}
