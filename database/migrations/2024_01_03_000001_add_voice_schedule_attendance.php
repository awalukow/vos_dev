<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add voice column to portal_users ──────────────────────────────
        Schema::table('portal_users', function (Blueprint $table) {
            $table->enum('voice', ['Sopran', 'Alto', 'Tenor', 'Bass'])->nullable()->after('can_upload_documents');
        });

        // ── 2. Add portal-specific columns to existing schedule table ─────────
        // We extend the existing table — existing data stays intact
        Schema::table('schedule', function (Blueprint $table) {
            // event_type maps to Tipe Kegiatan
            $table->enum('event_type', ['Pelayanan', 'Konser'])->default('Pelayanan')->after('event_name');
            // location / maps link
            $table->string('location')->nullable()->after('event_type');
            $table->string('maps_url')->nullable()->after('location');
            // NOTE: event_image and event_imageCaptionUrl remain NOT NULL as in the original schema.
            // Portal-created schedules will store an empty string '' for these fields.
        });

        // ── 3. Schedule attendance table ─────────────────────────────────────
        // One row per user per schedule — their confirmation
        Schema::create('schedule_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('schedule_id');   // FK to schedule.id (increments = unsigned int)
            $table->foreignId('portal_user_id')->constrained('portal_users')->cascadeOnDelete();
            $table->enum('voice', ['Sopran', 'Alto', 'Tenor', 'Bass'])->nullable(); // snapshot of user's voice at time of confirmation
            $table->enum('status', ['hadir', 'tidak_hadir'])->default('hadir');
            $table->timestamps();
            $table->unique(['schedule_id', 'portal_user_id']);
        });

        // ── 4. Attendance change log ──────────────────────────────────────────
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_attendance_id')->constrained('schedule_attendances')->cascadeOnDelete();
            $table->unsignedInteger('schedule_id');
            $table->foreignId('portal_user_id')->constrained('portal_users');  // the member
            $table->enum('old_status', ['hadir', 'tidak_hadir'])->nullable();
            $table->enum('new_status', ['hadir', 'tidak_hadir']);
            $table->string('voice')->nullable();
            $table->foreignId('changed_by')->constrained('portal_users');      // who made the change
            $table->text('note')->nullable();
            $table->timestamp('changed_at')->useCurrent();
        });

        // ── 5. Seed Schedule menus ────────────────────────────────────────────
        // Parent menu
        $exists = DB::table('portal_menus')->where('key', 'schedule')->exists();
        if (!$exists) {
            DB::table('portal_menus')->insert([
                'key'        => 'schedule',
                'label'      => 'Schedule',
                'icon'       => 'calendar',
                'route_name' => null,
                'url'        => null,
                'parent_id'  => null,
                'sort_order' => 3,   // Between DocSign and Sales
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Shift Sales and User Management sort orders down to make room
            DB::table('portal_menus')
                ->whereIn('key', ['sales', 'user_mgmt', 'settings'])
                ->increment('sort_order');
        }

        $scheduleMenuId = DB::table('portal_menus')->where('key', 'schedule')->value('id');

        // Child menus
        $children = [
            [
                'key'        => 'schedule.jadwal',
                'label'      => 'Jadwal Pelayanan',
                'icon'       => 'calendar-days',
                'route_name' => 'portal.schedule.index',
                'url'        => null,
                'parent_id'  => $scheduleMenuId,
                'sort_order' => 1,
                'is_active'  => true,
            ],
            [
                'key'        => 'schedule.laporan',
                'label'      => 'Laporan Kehadiran',
                'icon'       => 'clipboard-document-list',
                'route_name' => 'portal.schedule.laporan',
                'url'        => null,
                'parent_id'  => $scheduleMenuId,
                'sort_order' => 2,
                'is_active'  => true,
            ],
        ];

        foreach ($children as $child) {
            if (!DB::table('portal_menus')->where('key', $child['key'])->exists()) {
                DB::table('portal_menus')->insert(array_merge($child, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // ── 6. Grant menu access to roles ─────────────────────────────────────
        $jadwalMenuId  = DB::table('portal_menus')->where('key', 'schedule.jadwal')->value('id');
        $laporanMenuId = DB::table('portal_menus')->where('key', 'schedule.laporan')->value('id');

        $allRoles         = DB::table('roles')->pluck('id');
        $adminPengurusIds = DB::table('roles')->whereIn('name', ['administrator', 'adm2', 'pengurus'])->pluck('id');

        // Jadwal Pelayanan — all roles
        foreach ($allRoles as $roleId) {
            DB::table('role_menu_permissions')->insertOrIgnore([
                'role_id'        => $roleId,
                'portal_menu_id' => $scheduleMenuId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            DB::table('role_menu_permissions')->insertOrIgnore([
                'role_id'        => $roleId,
                'portal_menu_id' => $jadwalMenuId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // Laporan Kehadiran — admins and pengurus only
        foreach ($adminPengurusIds as $roleId) {
            DB::table('role_menu_permissions')->insertOrIgnore([
                'role_id'        => $roleId,
                'portal_menu_id' => $laporanMenuId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('schedule_attendances');

        Schema::table('schedule', function (Blueprint $table) {
            $table->dropColumn(['event_type', 'location', 'maps_url']);
        });

        Schema::table('portal_users', function (Blueprint $table) {
            $table->dropColumn('voice');
        });

        DB::table('portal_menus')->whereIn('key', ['schedule', 'schedule.jadwal', 'schedule.laporan'])->delete();
    }
};
