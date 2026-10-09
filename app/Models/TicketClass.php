<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TicketClass extends Model {
    protected $guarded = ['id'];
    protected $casts = ['price'=>'integer','capacity'=>'integer'];
    public function event() { return $this->belongsTo(TicketEvent::class,'ticket_event_id'); }
}