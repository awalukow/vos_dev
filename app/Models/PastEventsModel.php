<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PastEventsModel extends Model
{
    use HasFactory;

    protected $table = 'PastEvents'; 

    protected $fillable = [
        'eventName',
        'eventDescription',
        'eventImage',
        'eventDate',
        'Rowstatus',
        'CreatedBy',
        'CreatedDate',
        'ModifiedBy',
        'ModifiedDate',
    ];
}
