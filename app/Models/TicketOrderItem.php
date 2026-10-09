<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TicketOrderItem extends Model {
    use Concerns\HasRowStatus;
    protected $guarded = ['id'];
    public function getBookingLabelAttribute(): string {
        $number = $this->seat_label ?? $this->order->items()->withoutGlobalScope('active')->where('id','<=',$this->id)->count();
        return $this->order->booking_code.'/'.$number;
    }
    public function order() { return $this->belongsTo(TicketOrder::class,'ticket_order_id'); }
}
