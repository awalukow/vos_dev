<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TicketPaymentMethod extends Model {
    protected $guarded = ['id'];
    protected $casts = ['active'=>'boolean'];
}