<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ticket_order_items', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->index();
            $table->foreignId('checked_in_by')->nullable()->constrained('portal_users')->nullOnDelete();
        });

        DB::table('roles')->insertOrIgnore([
            'name' => 'checkin_officer',
            'display_name' => 'Checkin Officer',
            'description' => 'Scan and validate tickets at the event entrance.',
            'level' => 25,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $parentId = DB::table('portal_menus')->where('key', 'ticketing')->value('id');
        if (!$parentId) {
            return;
        }

        $menuId = DB::table('portal_menus')->where('key', 'ticketing.checkin')->value('id');
        if (!$menuId) {
            $menuId = DB::table('portal_menus')->insertGetId([
                'key' => 'ticketing.checkin',
                'label' => 'Check In',
                'route_name' => 'portal.ticketing.checkin',
                'parent_id' => $parentId,
                'sort_order' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roleIds = DB::table('roles')->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('role_menu_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'portal_menu_id' => $parentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('role_menu_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'portal_menu_id' => $menuId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $menuId = DB::table('portal_menus')->where('key', 'ticketing.checkin')->value('id');
        if ($menuId) {
            DB::table('role_menu_permissions')->where('portal_menu_id', $menuId)->delete();
            DB::table('portal_menus')->where('id', $menuId)->delete();
        }

        $parentId = DB::table('portal_menus')->where('key', 'ticketing')->value('id');
        $newParentGrants = DB::table('roles')->whereIn('name', ['timker', 'singers'])->pluck('id');
        if ($parentId && $newParentGrants->isNotEmpty()) {
            DB::table('role_menu_permissions')
                ->where('portal_menu_id', $parentId)
                ->whereIn('role_id', $newParentGrants)
                ->delete();
        }

        DB::table('roles')->where('name', 'checkin_officer')->delete();

        Schema::table('ticket_order_items', function (Blueprint $table) {
            $table->dropForeign(['checked_in_by']);
            $table->dropIndex(['checked_in_at']);
            $table->dropColumn(['checked_in_at', 'checked_in_by']);
        });
    }
};
