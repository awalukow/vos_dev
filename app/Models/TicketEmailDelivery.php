<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TicketEmailDelivery extends Model {
    protected $guarded=['id'];
    protected $hidden=['error_detail'];
    protected $casts=['failed_at'=>'datetime','resolved_at'=>'datetime'];
}
