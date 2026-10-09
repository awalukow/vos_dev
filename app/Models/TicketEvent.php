<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TicketEvent extends Model {
    use Concerns\HasRowStatus;
    protected $guarded = ['id'];
    protected $casts = ['starts_at'=>'datetime','limited_seating'=>'boolean','published'=>'boolean','layout_dividers'=>'array'];
    public function classes() { return $this->hasMany(TicketClass::class); }
    public function seats() { return $this->hasMany(TicketSeat::class); }
    public function venue() { return $this->belongsTo(TicketVenue::class, 'ticket_venue_id'); }
    public function orders() { return $this->hasMany(TicketOrder::class); }
    public function reservedItems() {
        return TicketOrderItem::whereHas('order', function ($q) {
            $q->where('ticket_event_id', $this->id)->where(function ($q) {
                $q->whereIn('status',['paid','payment_review','midtrans_pending'])
                  ->orWhere(function ($q) { $q->where('status','awaiting_payment')->where('expires_at','>',now()); });
            });
        });
    }
    public function availability(): array {
        $capacity = $this->classes->sum('capacity');
        $used = $this->reservedItems()->count();
        return ['capacity'=>$capacity,'used'=>$used,'remaining'=>max(0,$capacity-$used),'almost'=>$capacity > 0 && $used / $capacity > .8];
    }
}
