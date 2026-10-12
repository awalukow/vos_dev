<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settingsId = DB::table('portal_menus')->where('key', 'settings')->value('id');
        $menuId = DB::table('portal_menus')->insertGetId([
            'key' => 'settings.system_maintenance',
            'label' => 'System Maintenance',
            'route_name' => 'system.maintenance',
            'parent_id' => $settingsId,
            'sort_order' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('roles')->where('name', 'administrator')->pluck('id') as $roleId) {
            DB::table('role_menu_permissions')->insert([
                'role_id' => $roleId,
                'portal_menu_id' => $menuId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $menuId = DB::table('portal_menus')->where('key', 'settings.system_maintenance')->value('id');
        DB::table('role_menu_permissions')->where('portal_menu_id', $menuId)->delete();
        DB::table('portal_menus')->where('id', $menuId)->delete();
    }
};
