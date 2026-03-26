<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class PortalUser extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'portal_users';

    protected $fillable = [
        'name', 'username', 'email', 'password',
        'avatar', 'is_active', 'can_upload_documents',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'    => 'datetime',
        'is_active'            => 'boolean',
        'can_upload_documents' => 'boolean',
        // NOTE: 'hashed' cast only exists in Laravel 10+.
        // For Laravel 9, we use a mutator below instead.
    ];

    // Auto-hash password whenever it is set on the model
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = \Illuminate\Support\Facades\Hash::needsRehash($value)
            ? \Illuminate\Support\Facades\Hash::make($value)
            : $value;
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'portal_user_roles', 'portal_user_id', 'role_id')
                    ->withTimestamps();
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(PortalDocument::class, 'uploaded_by');
    }

    public function documentSignatures()
    {
        return $this->hasMany(DocumentSignature::class, 'signer_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** Returns the single highest-level role this user holds */
    public function primaryRole(): ?Role
    {
        return $this->roles()->orderByDesc('level')->first();
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole('administrator');
    }

    public function isAdm2(): bool
    {
        return $this->hasRole('adm2');
    }

    /** Highest role level this user has */
    public function maxRoleLevel(): int
    {
        return $this->roles->max('level') ?? 0;
    }

    /** All menu keys this user can access (based on highest role) */
    public function accessibleMenuKeys(): array
    {
        $roleIds = $this->roles->pluck('id');

        return \DB::table('role_menu_permissions')
            ->join('portal_menus', 'portal_menus.id', '=', 'role_menu_permissions.portal_menu_id')
            ->whereIn('role_menu_permissions.role_id', $roleIds)
            ->where('portal_menus.is_active', true)
            ->pluck('portal_menus.key')
            ->unique()
            ->values()
            ->toArray();
    }
}
