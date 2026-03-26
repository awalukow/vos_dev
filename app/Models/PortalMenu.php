<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalMenu extends Model
{
    protected $table = 'portal_menus';

    protected $fillable = [
        'key', 'label', 'icon', 'route_name', 'url',
        'parent_id', 'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function parent()
    {
        return $this->belongsTo(PortalMenu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PortalMenu::class, 'parent_id')->orderBy('sort_order');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_menu_permissions', 'portal_menu_id', 'role_id')
                    ->withTimestamps();
    }

    public function getUrlAttribute($value): ?string
    {
        if ($this->route_name && \Route::has($this->route_name)) {
            try {
                return route($this->route_name);
            } catch (\Exception $e) {
                //
            }
        }
        return $value;
    }
}
