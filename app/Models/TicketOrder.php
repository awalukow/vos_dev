<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TicketOrder extends Model {
    use Concerns\HasRowStatus;
    protected static function booted() {
        static::creating(function ($order) { $order->booking_code = \App\Services\BookingCode::generate(); });
    }
    protected $guarded = ['id'];
    protected $hidden = ['gateway_credentials'];
    public function getGatewayCredentialsAttribute($value) { return $value ? json_decode(decrypt($value),true) : null; }
    public function setGatewayCredentialsAttribute($value) { $this->attributes['gateway_credentials']=$value ? encrypt(json_encode($value)) : null; }
    protected $casts = ['customer_id'=>'integer','ticket_event_id'=>'integer','total'=>'integer','discount'=>'integer','promo_snapshot'=>'array','promo_applied_at'=>'datetime','expires_at'=>'datetime','proof_uploaded_at'=>'datetime','reviewed_at'=>'datetime','tickets_emailed_at'=>'datetime','payment_snapshot'=>'array'];
    public function getRouteKeyName() { return 'reference'; }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function event() { return $this->belongsTo(TicketEvent::class,'ticket_event_id')->withoutGlobalScope('active'); }
    public function items() { return $this->hasMany(TicketOrderItem::class)->orderBy('id'); }
    public function singer() { return $this->belongsTo(PortalSinger::class,'portal_singer_id'); }
    public function method() { return $this->belongsTo(TicketPaymentMethod::class,'ticket_payment_method_id'); }
}
