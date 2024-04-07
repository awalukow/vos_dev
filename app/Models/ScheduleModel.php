<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleModel extends Model
{
    use HasFactory;
    protected $table = 'schedule';
    protected $casts = [
        'program_date' => 'datetime',
    ];
    protected $isService = [
        'isSundayService' => 'boolean',
    ];
}
