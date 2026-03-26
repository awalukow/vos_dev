<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalMenu;
use App\Models\Role;
use Illuminate\Http\Request;

class MenuPermissionController extends Controller
{
    public function index()
    {
        $roles = Role::orderByDesc('level')->get();
        $menus = PortalMenu::whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        // Build a matrix: role_id => [menu_id => bool]
        $permissions = [];
        foreach ($roles as $role) {
            $menuIds = $role->menus()->pluck('portal_menus.id')->toArray();
            $permissions[$role->id] = array_fill_keys($menuIds, true);
        }

        return view('portal.settings.menu-permissions', compact('roles', 'menus', 'permissions'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'permissions'         => ['nullable', 'array'],
            'permissions.*'       => ['array'],
            'permissions.*.*'     => ['boolean'],
        ]);

        $roles = Role::all();
        $menus = PortalMenu::all();

        foreach ($roles as $role) {
            $grantedMenuIds = [];
            foreach ($menus as $menu) {
                $checked = $request->input("permissions.{$role->id}.{$menu->id}", false);
                if ($checked) {
                    $grantedMenuIds[] = $menu->id;
                    // Auto-grant parent if child granted
                    if ($menu->parent_id && !in_array($menu->parent_id, $grantedMenuIds)) {
                        $grantedMenuIds[] = $menu->parent_id;
                    }
                }
            }
            $role->menus()->sync($grantedMenuIds);
        }

        return back()->with('success', 'Menu permissions updated successfully.');
    }
}
