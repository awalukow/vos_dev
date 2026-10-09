<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TicketSeat extends Model {
    protected $guarded = ['id'];
    public function ticketClass() { return $this->belongsTo(TicketClass::class); }
}