<?php
namespace App\Services;
use App\Models\{Customer,PortalSinger,TicketEvent,TicketOrder,TicketOrderItem,TicketPaymentMethod};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketBooking {
    private function fail($message) { throw ValidationException::withMessages(['booking'=>$message]); }

    // Every inventory mutation locks the event first. This serializes competing
    // checkouts and approvals on MySQL/InnoDB without relying on browser totals.
    public function reserve(Customer $customer, TicketEvent $event, array $selection): TicketOrder {
        for ($attempt=0; ; $attempt++) {
            try { return $this->reserveAttempt($customer,$event,$selection); }
            catch (\Illuminate\Database\QueryException $e) {
                // Retry the whole transaction if another checkout claimed the same code.
                if ($attempt>=9 || !in_array((string)$e->getCode(),['23000','23505'],true) || !str_contains($e->getMessage(),'booking_code')) throw $e;
            }
        }
    }
    private function reserveAttempt(Customer $customer, TicketEvent $event, array $selection): TicketOrder {
        return DB::transaction(function () use ($customer,$event,$selection) {
            $event = TicketEvent::lockForUpdate()->findOrFail($event->id);
            if (!$event->published || $event->starts_at->isPast()) $this->fail('This event is not on sale.');
            $event->orders()->where('status','awaiting_payment')->where('expires_at','<=',now())->update(['status'=>'expired']);
            if (!TicketPaymentMethod::where('active',true)->exists()) $this->fail('Payments are not available yet. Please try again later.');
            $lines = [];
            if ($event->seating_type === 'numbered') {
                $ids = array_values(array_unique($selection['seats'] ?? []));
                if (!$ids || count($ids)>10) $this->fail('Select between 1 and 10 seats.');
                $seats = $event->seats()->with('ticketClass')->whereIn('id',$ids)->get();
                if ($seats->count() !== count($ids)) $this->fail('One or more seats do not belong to this event.');
                if ($event->reservedItems()->whereIn('ticket_seat_id',$ids)->exists()) $this->fail('A selected seat was just reserved. Please choose another seat.');
                foreach ($seats as $seat) $lines[] = ['ticket_class_id'=>$seat->ticket_class_id,'ticket_seat_id'=>$seat->id,'class_name'=>$seat->ticketClass->name,'seat_label'=>$seat->label,'price'=>$seat->ticketClass->price];
            } else {
                $classes = $event->classes()->get()->keyBy('id');
                $quantities = $selection['quantities'] ?? [];
                $total = array_sum($quantities);
                if ($total < 1 || $total > 10) $this->fail('Choose between 1 and 10 tickets.');
                foreach ($quantities as $id=>$qty) {
                    if ((int)$qty === 0) continue;
                    $class = $classes->get($id);
                    if (!$class || $qty < 0 || (int)$qty != $qty) $this->fail('Invalid ticket class or quantity.');
                    $used = $event->reservedItems()->where('ticket_class_id',$id)->count();
                    if ($used + $qty > $class->capacity) $this->fail(__('Not enough tickets remain in :class.', ['class'=>$class->name]));
                    for ($i=0; $i<$qty; $i++) $lines[]=['ticket_class_id'=>$id,'ticket_seat_id'=>null,'class_name'=>$class->name,'seat_label'=>null,'price'=>$class->price];
                }
            }
            $order = TicketOrder::create(['reference'=>(string)Str::uuid(),'customer_id'=>$customer->id,'ticket_event_id'=>$event->id,'status'=>'awaiting_payment','total'=>array_sum(array_column($lines,'price')),'expires_at'=>now()->addMinutes(30)]);
            foreach ($lines as $line) $order->items()->create($line+['token'=>(string)Str::uuid()]);
            return $order;
        },3);
    }

    public function submitProof(TicketOrder $order, TicketPaymentMethod $method, string $path, ?int $singerId=null): void {
        DB::transaction(function () use ($order,$method,$path,$singerId) {
            TicketEvent::withoutGlobalScope('active')->lockForUpdate()->findOrFail($order->ticket_event_id);
            $order = TicketOrder::lockForUpdate()->findOrFail($order->id);
            $method = TicketPaymentMethod::lockForUpdate()->findOrFail($method->id);
            if (!$method->active) $this->fail('This payment method has been disabled.');
            if ($order->status !== 'awaiting_payment' || !$order->expires_at || $order->expires_at->isPast()) $this->fail('This reservation is no longer available. Please make a new booking.');
            $singer=$singerId ? PortalSinger::lockForUpdate()->find($singerId) : null;
            if ($singerId && (!$singer || !$singer->active)) throw ValidationException::withMessages(['singer_id'=>__('Please select an active singer.')]);
            $order->fill(['portal_singer_id'=>$singer?->id,'referral_code'=>$singer?->referral_code,'singer_name'=>$singer?->name]);
            $expectedTotal=$order->total;
            app(TicketPromotions::class)->applyLocked($order,$order->promo_snapshot['code']??null);
            if ($order->total!==$expectedTotal) $this->fail('This promo has changed. Apply it again and check the updated total before paying.');
            if ($order->total===0) $this->fail('Confirm your free tickets without uploading payment proof.');
            $order->update(['status'=>'payment_review','ticket_payment_method_id'=>$method->id,'payment_snapshot'=>['name'=>$method->name,'type'=>$method->type,'instructions'=>$method->instructions],'proof_path'=>$path,'proof_uploaded_at'=>now(),'expires_at'=>null]);
        },3);
    }

    public function completeFree(TicketOrder $order, ?int $singerId=null): void {
        DB::transaction(function() use($order,$singerId) {
            TicketEvent::withoutGlobalScope('active')->lockForUpdate()->findOrFail($order->ticket_event_id);
            $order=TicketOrder::lockForUpdate()->findOrFail($order->id);
            app(TicketPromotions::class)->applyLocked($order,$order->promo_snapshot['code']??null);
            if ($order->total!==0) $this->fail('This booking requires payment.');
            $singer=$singerId ? PortalSinger::lockForUpdate()->find($singerId) : null;
            if ($singerId && (!$singer || !$singer->active)) $this->fail('Please select an active singer.');
            $order->update(['status'=>'paid','expires_at'=>null,'reviewed_at'=>now(),'portal_singer_id'=>$singer?->id,'referral_code'=>$singer?->referral_code,'singer_name'=>$singer?->name]);
            TicketDelivery::audit('booking.free_confirmed',$order->reference,null,['customer_id'=>$order->customer_id]);
        },3);
    }
    public function review(TicketOrder $order, int $actor, bool $approve, ?string $note): void {
        DB::transaction(function () use ($order,$actor,$approve,$note) {
            TicketEvent::withoutGlobalScope('active')->lockForUpdate()->findOrFail($order->ticket_event_id);
            $order = TicketOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->status !== 'payment_review') $this->fail('This payment has already been reviewed or is not awaiting review.');
            $order->update(['status'=>$approve?'paid':'rejected','reviewed_by'=>$actor,'reviewed_at'=>now(),'review_note'=>$note]);
            TicketDelivery::audit($approve?'payment.approved':'payment.rejected',$order->reference,$actor,['note'=>$note]);
        },3);
    }

    public function cancel(TicketOrder $order): void {
        DB::transaction(function () use ($order) {
            TicketEvent::withoutGlobalScope('active')->lockForUpdate()->findOrFail($order->ticket_event_id);
            $order = TicketOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->status !== 'awaiting_payment') $this->fail('Only unpaid reservations can be cancelled.');
            $order->update(['status'=>'cancelled','RowStatus'=>-1]);
            $order->items()->update(['RowStatus'=>-1]);
            TicketDelivery::audit('booking.cancelled',$order->reference,null,['customer_id'=>$order->customer_id]);
        });
    }

    public function remove(TicketOrder $order, int $actor): void {
        DB::transaction(function () use ($order,$actor) {
            TicketEvent::withoutGlobalScope('active')->lockForUpdate()->findOrFail($order->ticket_event_id);
            $order=TicketOrder::lockForUpdate()->findOrFail($order->id);
            $order->update(['RowStatus'=>-1]);
            $order->items()->update(['RowStatus'=>-1]);
            TicketDelivery::audit('booking.removed',$order->reference,$actor,['status'=>$order->status]);
        },3);
    }
}
