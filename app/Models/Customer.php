<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class Customer extends Authenticatable {
    use Notifiable;
    protected $guarded = ['id'];
    protected $attributes = ['is_active'=>true,'otp_attempts'=>0];
    protected $hidden = ['password','remember_token','otp_hash','pending_email_otp_hash'];
    protected $casts = ['email_issue_at'=>'datetime','pending_email_otp_expires_at'=>'datetime','pending_email_otp_sent_at'=>'datetime','dob'=>'date','email_verified_at'=>'datetime','otp_expires_at'=>'datetime','otp_sent_at'=>'datetime','is_active'=>'boolean'];
    public function emailFailures() { return $this->hasMany(TicketEmailDelivery::class)->whereNotNull('failed_at')->latest('failed_at'); }
    public function orders() { return $this->hasMany(TicketOrder::class); }
}