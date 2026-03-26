<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'description', 'level'];

    public function users()
    {
        return $this->belongsToMany(PortalUser::class, 'portal_user_roles', 'role_id', 'portal_user_id')
                    ->withTimestamps();
    }

    public function menus()
    {
        return $this->belongsToMany(PortalMenu::class, 'role_menu_permissions', 'role_id', 'portal_menu_id')
                    ->withTimestamps();
    }
}
