<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot: user <-> roles (many-to-many)
        Schema::create('portal_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_user_id')->constrained('portal_users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['portal_user_id', 'role_id']);
        });

        // Seed admin user (id=1) with administrator role (id=1)
        DB::table('portal_user_roles')->insert([
            'portal_user_id' => 1,
            'role_id'        => 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Menu definitions
        Schema::create('portal_menus', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // Unique slug e.g. docsign.list
            $table->string('label');                  // Display label
            $table->string('icon')->nullable();       // Heroicon / SVG name
            $table->string('route_name')->nullable(); // Named route
            $table->string('url')->nullable();        // Fallback URL
            $table->foreignId('parent_id')->nullable()->constrained('portal_menus')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed menus
        $menus = [
            // Parent menus (no parent)
            ['key' => 'dashboard',      'label' => 'Dashboard',       'icon' => 'home',           'route_name' => 'portal.dashboard',         'url' => null, 'parent_id' => null, 'sort_order' => 1],
            ['key' => 'docsign',        'label' => 'DocSign',         'icon' => 'document-check', 'route_name' => null,                       'url' => null, 'parent_id' => null, 'sort_order' => 2],
            ['key' => 'sales',          'label' => 'Sales',           'icon' => 'chart-bar',      'route_name' => null,                       'url' => null, 'parent_id' => null, 'sort_order' => 3],
            ['key' => 'user_mgmt',      'label' => 'User Management', 'icon' => 'users',          'route_name' => null,                       'url' => null, 'parent_id' => null, 'sort_order' => 4],
            ['key' => 'settings',       'label' => 'Settings',        'icon' => 'cog-6-tooth',    'route_name' => null,                       'url' => null, 'parent_id' => null, 'sort_order' => 5],
        ];

        foreach ($menus as $menu) {
            DB::table('portal_menus')->insert(array_merge($menu, ['created_at' => now(), 'updated_at' => now()]));
        }

        $docSignId   = DB::table('portal_menus')->where('key', 'docsign')->value('id');
        $salesId     = DB::table('portal_menus')->where('key', 'sales')->value('id');
        $userMgmtId  = DB::table('portal_menus')->where('key', 'user_mgmt')->value('id');
        $settingsId  = DB::table('portal_menus')->where('key', 'settings')->value('id');

        $children = [
            ['key' => 'docsign.list',       'label' => 'Document List',  'icon' => 'document-text', 'route_name' => 'portal.docsign.index',          'url' => null, 'parent_id' => $docSignId,  'sort_order' => 1],
            ['key' => 'docsign.upload',     'label' => 'Upload',         'icon' => 'arrow-up-tray', 'route_name' => 'portal.docsign.upload',         'url' => null, 'parent_id' => $docSignId,  'sort_order' => 2],
            ['key' => 'sales.submit',       'label' => 'Submit Sales',   'icon' => 'plus-circle',   'route_name' => 'portal.sales.submit',           'url' => null, 'parent_id' => $salesId,    'sort_order' => 1],
            ['key' => 'sales.reports',      'label' => 'Reports',        'icon' => 'presentation-chart-line', 'route_name' => 'portal.sales.reports','url' => null, 'parent_id' => $salesId,    'sort_order' => 2],
            ['key' => 'user_mgmt.users',    'label' => 'Users',          'icon' => 'user',          'route_name' => 'portal.users.index',            'url' => null, 'parent_id' => $userMgmtId, 'sort_order' => 1],
            ['key' => 'user_mgmt.roles',    'label' => 'Roles',          'icon' => 'shield-check',  'route_name' => 'portal.roles.index',            'url' => null, 'parent_id' => $userMgmtId, 'sort_order' => 2],
            ['key' => 'settings.menu_perm', 'label' => 'Menu Permissions','icon' => 'lock-closed',  'route_name' => 'portal.settings.menu-permissions','url' => null,'parent_id' => $settingsId, 'sort_order' => 1],
        ];

        foreach ($children as $child) {
            DB::table('portal_menus')->insert(array_merge($child, ['created_at' => now(), 'updated_at' => now()]));
        }

        // Role-Menu permissions table
        Schema::create('role_menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('portal_menu_id')->constrained('portal_menus')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'portal_menu_id']);
        });

        // Administrator gets all menus
        $adminRoleId = DB::table('roles')->where('name', 'administrator')->value('id');
        $menuIds = DB::table('portal_menus')->pluck('id');
        foreach ($menuIds as $menuId) {
            DB::table('role_menu_permissions')->insert([
                'role_id'        => $adminRoleId,
                'portal_menu_id' => $menuId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // adm2 gets all except settings.menu_perm (they can manage users, not menus)
        $adm2RoleId = DB::table('roles')->where('name', 'adm2')->value('id');
        $adm2MenuIds = DB::table('portal_menus')->whereNotIn('key', ['settings.menu_perm'])->pluck('id');
        foreach ($adm2MenuIds as $menuId) {
            DB::table('role_menu_permissions')->insert([
                'role_id'        => $adm2RoleId,
                'portal_menu_id' => $menuId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // Pengurus: all except user_mgmt and its children
        $pengurusRoleId = DB::table('roles')->where('name', 'pengurus')->value('id');
        $pengurusMenuIds = DB::table('portal_menus')->whereNotIn('key', ['user_mgmt', 'user_mgmt.users', 'user_mgmt.roles', 'settings.menu_perm'])->pluck('id');
        foreach ($pengurusMenuIds as $menuId) {
            DB::table('role_menu_permissions')->insert([
                'role_id'        => $pengurusRoleId,
                'portal_menu_id' => $menuId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // Timker and Singers: dashboard + docsign only
        foreach (['timker', 'singers'] as $roleName) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            $basicMenuIds = DB::table('portal_menus')->whereIn('key', ['dashboard', 'docsign', 'docsign.list'])->pluck('id');
            foreach ($basicMenuIds as $menuId) {
                DB::table('role_menu_permissions')->insert([
                    'role_id'        => $roleId,
                    'portal_menu_id' => $menuId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menu_permissions');
        Schema::dropIfExists('portal_menus');
        Schema::dropIfExists('portal_user_roles');
    }
};
