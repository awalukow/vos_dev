<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $table = 'schedule';

    // Your existing table has no timestamps columns (createdDate/modifiedDate are custom)
    public $timestamps = false;

    protected $fillable = [
        'program_date',
        'program_until',
        'event_name',
        'event_type',
        'event_detail',
        'event_image',
        'event_imageCaptionUrl',
        'isSundayService',
        'location',
        'maps_url',
        'rowstatus',
        'createdBy',
        'createdDate',
        'modifiedBy',
        'modifiedDate',
    ];

    protected $casts = [
        'program_date'  => 'datetime',
        'program_until' => 'datetime',
        'isSundayService' => 'boolean',
        'createdDate'   => 'datetime',
        'modifiedDate'  => 'datetime',
    ];

    public function attendances()
    {
        return $this->hasMany(ScheduleAttendance::class, 'schedule_id');
    }

    // Attendance count by voice and status
    public function attendanceCount(string $voice, string $status = 'hadir'): int
    {
        return $this->attendances()
            ->where('voice', $voice)
            ->where('status', $status)
            ->count();
    }

    public function userAttendance(int $userId): ?ScheduleAttendance
    {
        return $this->attendances()->where('portal_user_id', $userId)->first();
    }

    // Scope: upcoming only (program_date >= today, rowstatus = 1)
    public function scopeUpcoming($query)
    {
        return $query->where('program_date', '>=', now()->startOfDay())
                     ->where('rowstatus', 1)
                     ->orderBy('program_date', 'asc');
    }
}
