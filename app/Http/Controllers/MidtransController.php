<?php
namespace App\Http\Controllers;
use App\Models\{TicketOrder,TicketPaymentMethod};
use App\Services\MidtransPayments;
use Illuminate\Http\Request;

class MidtransController extends Controller {
    public function start(Request $request, TicketOrder $order, MidtransPayments $payments) {
        abort_unless($order->customer_id===auth('customer')->id(),403);
        $data=$request->validate(['expected_total'=>'required|integer|min:1','singer_id'=>'nullable|integer']);
        return redirect()->away($payments->start($order,(int)$data['expected_total'],isset($data['singer_id'])?(int)$data['singer_id']:null));
    }
    public function refresh(Request $request, TicketOrder $order, MidtransPayments $payments) {
        abort_unless($order->customer_id===auth('customer')->id(),403);
        try { $payments->sync($order); }
        catch (\Illuminate\Http\Client\HttpClientException $e) {
            if ($request->expectsJson()) return response()->json(['message'=>'Payment status is temporarily unavailable.'],503);
            return back()->with('error','Payment status is temporarily unavailable. Please try again shortly.');
        }
        if ($request->expectsJson()) return response()->json(['status'=>$order->fresh()->status]);
        return redirect()->route('tickets.order',$order);
    }
    public function notification(Request $request, MidtransPayments $payments) {
        $data=$request->validate(['order_id'=>'required|string|max:100','status_code'=>'required|string|max:10','gross_amount'=>'required|string|max:30','signature_key'=>'required|string|size:128']);
        if (str_starts_with($data['order_id'],'payment_notif_test_')) {
            $method=TicketPaymentMethod::where('type','midtrans')->first();
            abort_unless($method && $method->server_key && $method->merchant_id
                && $request->input('merchant_id')===$method->merchant_id
                && str_starts_with($data['order_id'],'payment_notif_test_'.$method->merchant_id.'_')
                && hash_equals(hash('sha512',$data['order_id'].$data['status_code'].$data['gross_amount'].$method->server_key),$data['signature_key']),403);
            // Dashboard probes have no booking and must never issue tickets or query payment status.
            return response()->json(['received'=>true,'test'=>true]);
        }
        $order=TicketOrder::where('reference',$data['order_id'])->firstOrFail();
        $credentials=$order->gateway_credentials;
        abort_unless($credentials && hash_equals(hash('sha512',$data['order_id'].$data['status_code'].$data['gross_amount'].$credentials['server_key']),$data['signature_key']),403);
        // Read current status from Midtrans, rather than trusting stale/replayed notifications.
        $payments->sync($order);
        return response()->json(['received'=>true]);
    }
}
