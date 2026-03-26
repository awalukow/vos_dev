<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleAttendance extends Model
{
    protected $table = 'schedule_attendances';

    protected $fillable = [
        'schedule_id',
        'portal_user_id',
        'voice',
        'status',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function user()
    {
        return $this->belongsTo(PortalUser::class, 'portal_user_id');
    }

    public function logs()
    {
        return $this->hasMany(AttendanceLog::class, 'schedule_attendance_id')->orderByDesc('changed_at');
    }
}
