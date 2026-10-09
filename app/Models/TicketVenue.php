<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TicketVenue extends Model {
    protected $guarded = ['id'];
    protected $casts = ['layout'=>'array'];
}