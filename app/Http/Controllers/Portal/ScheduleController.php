<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Schedule;
use App\Models\ScheduleAttendance;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    private const VOICES = ['Sopran', 'Alto', 'Tenor', 'Bass'];

    // ── Jadwal Pelayanan ──────────────────────────────────────────────────────

    public function index()
    {
        $user      = Auth::guard('portal')->user();
        $schedules = Schedule::upcoming()->get();

        $canManage = $user->isAdministrator()
            || $user->isAdm2()
            || $user->hasRole('pengurus');

        // Attach user's own attendance status to each schedule
        $attendanceMap = ScheduleAttendance::where('portal_user_id', $user->id)
            ->whereIn('schedule_id', $schedules->pluck('id'))
            ->pluck('status', 'schedule_id');

        return view('portal.schedule.index', compact('schedules', 'user', 'canManage', 'attendanceMap'));
    }

    public function create()
    {
        $user = Auth::guard('portal')->user();
        if (!$user->isAdministrator() && !$user->isAdm2() && !$user->hasRole('pengurus')) {
            abort(403);
        }
        return view('portal.schedule.create');
    }

    public function store(Request $request)
    {
        $user = Auth::guard('portal')->user();
        if (!$user->isAdministrator() && !$user->isAdm2() && !$user->hasRole('pengurus')) {
            abort(403);
        }

        $validated = $request->validate([
            'event_name'       => ['required', 'string', 'max:255'],
            'event_type'       => ['required', 'in:Pelayanan,Konser'],
            'program_date'     => ['required', 'date'],
            'program_until'    => ['required', 'date'],
            'location'         => ['nullable', 'string', 'max:255'],
            'maps_url'         => ['nullable', 'string', 'max:1000'],
            'event_detail'     => ['nullable', 'string', 'max:1000'],
            'isSundayService'  => ['boolean'],
        ]);

        Schedule::create([
            'event_name'            => $validated['event_name'],
            'event_type'            => $validated['event_type'],
            'program_date'          => $validated['program_date'],
            'program_until'         => $validated['program_until'],
            'location'              => $validated['location'] ?? null,
            'maps_url'              => $validated['maps_url'] ?? null,
            'event_detail'          => $validated['event_detail'] ?? '',
            'event_image'           => '',          // required by original schema, unused by portal
            'event_imageCaptionUrl' => '',          // required by original schema, unused by portal
            'isSundayService'       => $request->boolean('isSundayService'),
            'rowstatus'             => 1,
            'createdBy'             => $user->name,
            'createdDate'           => now(),
            'modifiedBy'            => $user->name,
            'modifiedDate'          => now(),
        ]);

        return redirect()->route('portal.schedule.index')
            ->with('success', 'Schedule created successfully.');
    }

    public function edit(Schedule $schedule)
    {
        $user = Auth::guard('portal')->user();
        if (!$user->isAdministrator() && !$user->isAdm2() && !$user->hasRole('pengurus')) {
            abort(403);
        }
        return view('portal.schedule.edit', compact('schedule'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $user = Auth::guard('portal')->user();
        if (!$user->isAdministrator() && !$user->isAdm2() && !$user->hasRole('pengurus')) {
            abort(403);
        }

        $validated = $request->validate([
            'event_name'      => ['required', 'string', 'max:255'],
            'event_type'      => ['required', 'in:Pelayanan,Konser'],
            'program_date'    => ['required', 'date'],
            'program_until'   => ['required', 'date'],
            'location'        => ['nullable', 'string', 'max:255'],
            'maps_url'        => ['nullable', 'string', 'max:1000'],
            'event_detail'    => ['nullable', 'string', 'max:1000'],
            'isSundayService' => ['boolean'],
        ]);

        $schedule->update(array_merge($validated, [
            'isSundayService' => $request->boolean('isSundayService'),
            'modifiedBy'      => $user->name,
            'modifiedDate'    => now(),
        ]));

        return redirect()->route('portal.schedule.index')
            ->with('success', 'Schedule updated successfully.');
    }

    public function destroy(Schedule $schedule)
    {
        $user = Auth::guard('portal')->user();
        if (!$user->isAdministrator()) abort(403);

        $schedule->update(['rowstatus' => 0]);
        return back()->with('success', 'Schedule removed.');
    }

    // ── Konfirmasi Kehadiran (per user, per schedule) ─────────────────────────

    public function confirm(Request $request, Schedule $schedule)
    {
        $user = Auth::guard('portal')->user();

        $request->validate([
            'status' => ['required', 'in:hadir,tidak_hadir'],
        ]);

        $voice = $user->voice ?? 'Sopran'; // fallback if voice not set

        $existing = ScheduleAttendance::where('schedule_id', $schedule->id)
            ->where('portal_user_id', $user->id)
            ->first();

        if ($existing) {
            $oldStatus = $existing->status;

            // Log if status changes
            if ($oldStatus !== $request->status) {
                AttendanceLog::create([
                    'schedule_attendance_id' => $existing->id,
                    'schedule_id'            => $schedule->id,
                    'portal_user_id'         => $user->id,
                    'old_status'             => $oldStatus,
                    'new_status'             => $request->status,
                    'voice'                  => $existing->voice,
                    'changed_by'             => $user->id,
                    'note'                   => 'Changed by user',
                    'changed_at'             => now(),
                ]);
            }

            $existing->update(['status' => $request->status]);
        } else {
            $attendance = ScheduleAttendance::create([
                'schedule_id'    => $schedule->id,
                'portal_user_id' => $user->id,
                'voice'          => $voice,
                'status'         => $request->status,
            ]);

            AttendanceLog::create([
                'schedule_attendance_id' => $attendance->id,
                'schedule_id'            => $schedule->id,
                'portal_user_id'         => $user->id,
                'old_status'             => null,
                'new_status'             => $request->status,
                'voice'                  => $voice,
                'changed_by'             => $user->id,
                'note'                   => 'Initial confirmation',
                'changed_at'             => now(),
            ]);
        }

        $label = $request->status === 'hadir' ? 'Hadir' : 'Tidak Hadir';
        return back()->with('success', "Konfirmasi kehadiran: {$label}");
    }

    // ── Laporan Kehadiran ─────────────────────────────────────────────────────

    public function laporan()
    {
        $user = Auth::guard('portal')->user();
        $canAccess = $user->isAdministrator() || $user->isAdm2() || $user->hasRole('pengurus');
        if (!$canAccess) abort(403);

        $schedules = Schedule::where('rowstatus', 1)
            ->orderByDesc('program_date')
            ->get();

        // Build counts separately — cannot set dynamic properties on Eloquent models
        $counts = [];
        foreach ($schedules as $s) {
            $counts[$s->id] = [];
            foreach (self::VOICES as $voice) {
                $counts[$s->id][$voice] = [
                    'hadir'       => $s->attendanceCount($voice, 'hadir'),
                    'tidak_hadir' => $s->attendanceCount($voice, 'tidak_hadir'),
                ];
            }
        }

        return view('portal.schedule.laporan', compact('schedules', 'counts', 'user'));
    }

    public function laporanDetail(Schedule $schedule)
    {
        $user = Auth::guard('portal')->user();
        $canAccess = $user->isAdministrator() || $user->isAdm2() || $user->hasRole('pengurus');
        if (!$canAccess) abort(403);

        // All attendances for this schedule, grouped by voice
        $attendances = ScheduleAttendance::where('schedule_id', $schedule->id)
            ->with(['user', 'logs.changedBy'])
            ->get()
            ->groupBy('voice');

        $voices = self::VOICES;

        return view('portal.schedule.laporan-detail', compact('schedule', 'attendances', 'voices', 'user'));
    }

    public function laporanEdit(Schedule $schedule)
    {
        $user = Auth::guard('portal')->user();
        $canAccess = $user->isAdministrator() || $user->isAdm2() || $user->hasRole('pengurus');
        if (!$canAccess) abort(403);

        // All portal users with their voice and attendance status
        $portalUsers = PortalUser::where('is_active', true)
            ->with(['roles'])
            ->orderBy('voice')
            ->orderBy('name')
            ->get();

        $attendanceMap = ScheduleAttendance::where('schedule_id', $schedule->id)
            ->pluck('status', 'portal_user_id');

        $voices = self::VOICES;

        return view('portal.schedule.laporan-edit', compact('schedule', 'portalUsers', 'attendanceMap', 'voices', 'user'));
    }

    public function laporanUpdate(Request $request, Schedule $schedule)
    {
        $user = Auth::guard('portal')->user();
        $canAccess = $user->isAdministrator() || $user->isAdm2() || $user->hasRole('pengurus');
        if (!$canAccess) abort(403);

        $request->validate([
            'attendances'          => ['nullable', 'array'],
            'attendances.*.status' => ['required', 'in:hadir,tidak_hadir'],
            'notes'                => ['nullable', 'array'],
        ]);

        $submitted = $request->input('attendances', []);

        foreach ($submitted as $userId => $data) {
            $newStatus = $data['status'];
            $note      = $request->input("notes.{$userId}", 'Updated by pengurus');

            $member    = PortalUser::find($userId);
            if (!$member) continue;

            $existing = ScheduleAttendance::where('schedule_id', $schedule->id)
                ->where('portal_user_id', $userId)
                ->first();

            if ($existing) {
                if ($existing->status !== $newStatus) {
                    // Log the change
                    AttendanceLog::create([
                        'schedule_attendance_id' => $existing->id,
                        'schedule_id'            => $schedule->id,
                        'portal_user_id'         => $userId,
                        'old_status'             => $existing->status,
                        'new_status'             => $newStatus,
                        'voice'                  => $existing->voice,
                        'changed_by'             => $user->id,
                        'note'                   => $note,
                        'changed_at'             => now(),
                    ]);
                    $existing->update(['status' => $newStatus]);
                }
            } else {
                // Create new attendance record
                $attendance = ScheduleAttendance::create([
                    'schedule_id'    => $schedule->id,
                    'portal_user_id' => $userId,
                    'voice'          => $member->voice ?? 'Sopran',
                    'status'         => $newStatus,
                ]);
                AttendanceLog::create([
                    'schedule_attendance_id' => $attendance->id,
                    'schedule_id'            => $schedule->id,
                    'portal_user_id'         => $userId,
                    'old_status'             => null,
                    'new_status'             => $newStatus,
                    'voice'                  => $member->voice ?? 'Sopran',
                    'changed_by'             => $user->id,
                    'note'                   => 'Added by pengurus',
                    'changed_at'             => now(),
                ]);
            }
        }

        return redirect()->route('portal.schedule.laporan')
            ->with('success', 'Attendance updated successfully.');
    }
}
