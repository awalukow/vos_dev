<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TicketPaymentMethod extends Model {
    protected $guarded = ['id'];
    protected $hidden = ['server_key','client_key'];
    protected $casts = ['active'=>'boolean','server_key'=>'encrypted','client_key'=>'encrypted'];
    public function fees(int $subtotal): array {
        $fees=[];
        foreach (['processing','platform'] as $fee) {
            $value=(float)$this->{$fee.'_fee_value'};
            $fees[$fee]=(int)round($this->{$fee.'_fee_type'}==='percent' ? $subtotal*$value/100 : $value);
        }
        return $fees;
    }
}
