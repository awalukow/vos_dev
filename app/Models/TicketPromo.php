<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TicketPromo extends Model {
    protected $guarded=['id'];
    protected $casts=['active'=>'boolean','single_use'=>'boolean','user_specific'=>'boolean','expires_at'=>'datetime','value'=>'integer','buy_quantity'=>'integer','free_quantity'=>'integer','daily_limit'=>'integer'];
    public function customers() { return $this->belongsToMany(Customer::class,'ticket_promo_customers'); }
}
