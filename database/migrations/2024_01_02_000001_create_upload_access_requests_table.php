<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Upload access requests table ──────────────────────────────────
        Schema::create('upload_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_user_id')->constrained('portal_users')->cascadeOnDelete();
            $table->text('message')->nullable();          // Request message from user
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('portal_users')->nullOnDelete();
            $table->text('reviewer_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // ── 2. Add docsign.access_approval menu ──────────────────────────────
        $docSignId = DB::table('portal_menus')->where('key', 'docsign')->value('id');

        if ($docSignId) {
            // Only insert if not already present (safe to re-run)
            $exists = DB::table('portal_menus')->where('key', 'docsign.access_approval')->exists();

            if (!$exists) {
                DB::table('portal_menus')->insert([
                    'key'        => 'docsign.access_approval',
                    'label'      => 'Access Approval',
                    'icon'       => 'shield-check',
                    'route_name' => 'portal.docsign.access-approval',
                    'url'        => null,
                    'parent_id'  => $docSignId,
                    'sort_order' => 3,
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Also ensure docsign.upload menu exists (may need to be visible
            // to all roles so they see "Upload" and get the request form)
            $uploadMenuId = DB::table('portal_menus')->where('key', 'docsign.upload')->value('id');
            $accessApprovalMenuId = DB::table('portal_menus')->where('key', 'docsign.access_approval')->value('id');

            // Grant docsign.access_approval to administrator and pengurus only
            $targetRoles = DB::table('roles')->whereIn('name', ['administrator', 'adm2', 'pengurus'])->pluck('id');

            foreach ($targetRoles as $roleId) {
                // Parent docsign menu
                DB::table('role_menu_permissions')->insertOrIgnore([
                    'role_id'        => $roleId,
                    'portal_menu_id' => $accessApprovalMenuId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            // Grant docsign.upload to ALL roles so the menu is visible
            // (the controller decides whether to show upload form or request form)
            $allRoleIds = DB::table('roles')->pluck('id');
            foreach ($allRoleIds as $roleId) {
                DB::table('role_menu_permissions')->insertOrIgnore([
                    'role_id'        => $roleId,
                    'portal_menu_id' => $uploadMenuId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                // Also grant the parent docsign menu
                DB::table('role_menu_permissions')->insertOrIgnore([
                    'role_id'        => $roleId,
                    'portal_menu_id' => $docSignId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_access_requests');
        DB::table('portal_menus')->where('key', 'docsign.access_approval')->delete();
    }
};
