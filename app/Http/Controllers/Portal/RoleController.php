<?php
namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->with('menus')->orderByDesc('level')->get();
        return view('portal.roles.index', compact('roles'));
    }
}
