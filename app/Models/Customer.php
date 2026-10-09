<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class Customer extends Authenticatable {
    use Notifiable;
    protected $guarded = ['id'];
    protected $attributes = ['is_active'=>true,'otp_attempts'=>0];
    protected $hidden = ['password','remember_token','otp_hash'];
    protected $casts = ['dob'=>'date','email_verified_at'=>'datetime','otp_expires_at'=>'datetime','otp_sent_at'=>'datetime','is_active'=>'boolean'];
    public function orders() { return $this->hasMany(TicketOrder::class); }
}