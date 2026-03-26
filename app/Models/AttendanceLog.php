<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $table = 'attendance_logs';

    public $timestamps = false;

    protected $fillable = [
        'schedule_attendance_id',
        'schedule_id',
        'portal_user_id',
        'old_status',
        'new_status',
        'voice',
        'changed_by',
        'note',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(ScheduleAttendance::class, 'schedule_attendance_id');
    }

    public function user()
    {
        return $this->belongsTo(PortalUser::class, 'portal_user_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(PortalUser::class, 'changed_by');
    }
}
