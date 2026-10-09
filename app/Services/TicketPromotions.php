<?php
namespace App\Services;
use App\Models\{TicketEvent,TicketOrder,TicketPromo};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketPromotions {
    private function fail(string $message): void { throw ValidationException::withMessages(['promo_code'=>__($message)]); }

    // Called with the event and order locked; the promo lock also serializes usage across events.
    public function applyLocked(TicketOrder $order, ?string $code): void {
        if ($order->status!=='awaiting_payment' || !$order->expires_at || $order->expires_at->lte(now())) $this->fail('This reservation is no longer available.');
        $subtotal=(int)$order->items()->sum('price');
        if (!$code) {
            $order->update(['ticket_promo_id'=>null,'promo_snapshot'=>null,'promo_applied_at'=>null,'discount'=>0,'total'=>$subtotal]);
            return;
        }
        $promo=TicketPromo::where('code',strtoupper(trim($code)))->lockForUpdate()->first();
        if (!$promo || !$promo->active || ($promo->expires_at && $promo->expires_at->lte(now()))) $this->fail('This promo code is invalid or expired.');
        if ($promo->user_specific && !$promo->customers()->where('customers.id',$order->customer_id)->exists()) $this->fail('This promo code is not available for your account.');
        // Pending reservations hold usage until expiry. Submitted/paid bookings consume it permanently,
        // including soft-deleted bookings, to prevent resetting limits by deleting an order.
        $usage=TicketOrder::withoutGlobalScope('active')->where('ticket_promo_id',$promo->id)->where('id','!=',$order->id)->where(function($q) {
            $q->whereIn('status',['payment_review','paid','midtrans_pending'])->orWhere(function($q) { $q->where('status','awaiting_payment')->where('RowStatus',0)->where('expires_at','>',now()); });
        })->lockForUpdate()->get();
        if ($promo->single_use && $usage->where('customer_id',$order->customer_id)->isNotEmpty()) $this->fail('This promo code has already been used by your account.');
        $applied=$order->ticket_promo_id==$promo->id && $order->promo_applied_at ? $order->promo_applied_at : now();
        $day=$applied->copy()->timezone('Asia/Jakarta')->startOfDay();
        if ($promo->daily_limit && $usage->where('promo_applied_at','>=',$day->copy()->timezone(config('app.timezone')))->where('promo_applied_at','<',$day->copy()->addDay()->timezone(config('app.timezone')))->count()>=$promo->daily_limit) $this->fail('This promo code has reached its daily purchase limit.');
        $prices=$order->items()->orderBy('price')->pluck('price')->sort()->values();
        $free=0;
        switch($promo->type) {
            case 'percent': $discount=intdiv($subtotal*$promo->value,100); break;
            case 'fixed': $discount=min($subtotal,$promo->value); break;
            case 'bogo': $free=intdiv($prices->count(),2); break;
            case 'bundle':
                if ($prices->count()<$promo->buy_quantity+$promo->free_quantity) $this->fail('Select the minimum paid tickets plus the free tickets before applying this promo.');
                $free=$promo->free_quantity; break;
            case 'free_ticket': $free=1; break;
            default: $this->fail('This promo code is invalid or expired.');
        }
        if (in_array($promo->type,['bogo','bundle','free_ticket'])) {
            if (!$free) $this->fail('Select the minimum paid tickets plus the free tickets before applying this promo.');
            $discount=(int)$prices->take($free)->sum();
        }
        $order->update(['ticket_promo_id'=>$promo->id,'promo_snapshot'=>['code'=>$promo->code,'type'=>$promo->type,'free_tickets'=>$free],'promo_applied_at'=>$applied,'discount'=>$discount,'total'=>max(0,$subtotal-$discount)]);
    }
    public function apply(TicketOrder $order, ?string $code): void {
        DB::transaction(function() use($order,$code) {
            TicketEvent::withoutGlobalScope('active')->lockForUpdate()->findOrFail($order->ticket_event_id);
            $this->applyLocked(TicketOrder::lockForUpdate()->findOrFail($order->id),$code);
        },3);
    }
}
