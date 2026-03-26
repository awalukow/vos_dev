<?php

/**
 * ══════════════════════════════════════════════════════════════════
 * VOS PORTAL — SETUP GUIDE
 * ══════════════════════════════════════════════════════════════════
 *
 * Follow these steps to integrate the portal into your existing
 * Laravel project.
 *
 * ── STEP 1: Install Composer dependencies ──────────────────────
 *
 *   composer require simplesoftwareio/simple-qrcode
 *   composer require setasign/fpdi
 *
 * ── STEP 2: Copy all portal files ─────────────────────────────
 *
 *   Merge the following directories into your project root:
 *
 *   vos_portal/
 *   ├── app/
 *   │   ├── Models/           → app/Models/
 *   │   └── Http/
 *   │       ├── Controllers/Portal/ → app/Http/Controllers/Portal/
 *   │       └── Middleware/         → app/Http/Middleware/
 *   ├── database/migrations/  → database/migrations/
 *   ├── resources/views/portal/ → resources/views/portal/
 *   └── routes/portal.php     → routes/portal.php
 *
 * ── STEP 3: Add portal guard to config/auth.php ──────────────
 *
 *   'guards' => [
 *       // ... existing guards ...
 *       'portal' => [
 *           'driver'   => 'session',
 *           'provider' => 'portal_users',
 *       ],
 *   ],
 *
 *   'providers' => [
 *       // ... existing providers ...
 *       'portal_users' => [
 *           'driver' => 'eloquent',
 *           'model'  => App\Models\PortalUser::class,
 *       ],
 *   ],
 *
 * ── STEP 4: Register middleware ───────────────────────────────
 *
 *   In bootstrap/app.php (Laravel 11+):
 *
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->alias([
 *           'portal.auth' => \App\Http\Middleware\PortalAuthenticate::class,
 *           'portal.role' => \App\Http\Middleware\PortalRoleMiddleware::class,
 *       ]);
 *   })
 *
 *   For Laravel 10, in app/Http/Kernel.php, $routeMiddleware array:
 *
 *   'portal.auth' => \App\Http\Middleware\PortalAuthenticate::class,
 *   'portal.role' => \App\Http\Middleware\PortalRoleMiddleware::class,
 *
 * ── STEP 5: Include portal routes in routes/web.php ──────────
 *
 *   // At the bottom of routes/web.php:
 *   require __DIR__ . '/portal.php';
 *
 * ── STEP 6: Register custom pagination view ──────────────────
 *
 *   In app/Providers/AppServiceProvider.php boot():
 *
 *   \Illuminate\Pagination\Paginator::defaultView('portal.components.pagination');
 *
 *   Or use it per-call:
 *   $users->links('portal.components.pagination')
 *
 * ── STEP 7: Create storage symlink and directories ────────────
 *
 *   php artisan storage:link
 *   mkdir -p storage/app/portal/documents/signed
 *   mkdir -p storage/app/temp
 *
 * ── STEP 8: Run migrations ────────────────────────────────────
 *
 *   php artisan migrate
 *
 * ── STEP 9: Log in ───────────────────────────────────────────
 *
 *   URL:      https://yourdomain.com/portal/login
 *   Username: admin
 *   Password: Admin@12345
 *
 *   ⚠ Change the default password immediately!
 *
 * ══════════════════════════════════════════════════════════════════
 * FILE STRUCTURE CREATED
 * ══════════════════════════════════════════════════════════════════
 *
 * MIGRATIONS (run in order):
 *   000001 — roles table (5 default roles)
 *   000002 — portal_users table (1 default admin)
 *   000003 — portal_user_roles, portal_menus, role_menu_permissions
 *   000004 — portal_documents, document_signatures
 *
 * MODELS:
 *   PortalUser         — auth user with role helpers
 *   Role               — role with level-based hierarchy
 *   PortalMenu         — hierarchical menu tree
 *   PortalDocument     — uploaded PDF with hash_code
 *   DocumentSignature  — per-signer record with QR placement data
 *
 * CONTROLLERS:
 *   Portal/AuthController            — login / logout
 *   Portal/DashboardController       — dashboard stats
 *   Portal/UserController            — CRUD + AJAX search
 *   Portal/DocSignController         — upload, assign, sign (QR+PDF), reject
 *   Portal/MenuPermissionController  — role×menu permission matrix
 *
 * MIDDLEWARE:
 *   PortalAuthenticate   — portal guard check
 *   PortalRoleMiddleware — role-based access (portal.role:administrator,adm2)
 *
 * VIEWS:
 *   portal/auth/login.blade.php             — Login page
 *   portal/layouts/app.blade.php            — Master layout + sidebar
 *   portal/dashboard/index.blade.php        — Dashboard
 *   portal/docsign/index.blade.php          — Document list
 *   portal/docsign/upload.blade.php         — Upload + assign signers
 *   portal/docsign/show.blade.php           — Document detail
 *   portal/docsign/sign.blade.php           — PDF viewer + QR placement
 *   portal/users/index.blade.php            — User list
 *   portal/users/create.blade.php           — Add user
 *   portal/users/edit.blade.php             — Edit user
 *   portal/users/_form.blade.php            — Shared user form
 *   portal/sales/submit.blade.php           — "On Development"
 *   portal/sales/reports.blade.php          — "On Development"
 *   portal/sales/_wip.blade.php             — WIP animation partial
 *   portal/settings/menu-permissions.blade.php — Role×Menu matrix
 *   portal/components/pagination.blade.php  — Custom pagination
 *
 * ROUTES (routes/portal.php):
 *   GET  /portal/login           portal.login
 *   POST /portal/login           portal.login.post
 *   POST /portal/logout          portal.logout
 *   GET  /portal/                portal.dashboard
 *   GET  /portal/docsign         portal.docsign.index
 *   GET  /portal/docsign/upload  portal.docsign.upload
 *   POST /portal/docsign/upload  portal.docsign.store
 *   GET  /portal/docsign/{doc}   portal.docsign.show
 *   GET  /portal/docsign/{doc}/pdf             portal.docsign.pdf
 *   POST /portal/docsign/{doc}/assign          portal.docsign.assign
 *   GET  /portal/docsign/{doc}/sign/{sig}      portal.docsign.sign
 *   POST /portal/docsign/{doc}/sign/{sig}      portal.docsign.sign.process
 *   POST /portal/docsign/{doc}/reject/{sig}    portal.docsign.reject
 *   GET  /portal/users           portal.users.index    [admin, adm2]
 *   ...  /portal/users/*         portal.users.*        [admin, adm2]
 *   GET  /portal/users/search/query  portal.users.search
 *   GET  /portal/sales/submit    portal.sales.submit
 *   GET  /portal/sales/reports   portal.sales.reports
 *   GET  /portal/settings/menu-permissions     portal.settings.menu-permissions   [admin]
 *   POST /portal/settings/menu-permissions     portal.settings.menu-permissions.update
 */
