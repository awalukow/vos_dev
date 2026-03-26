# VOS Management Portal

A full Laravel management portal with role-based access control, DocSign (PDF + QR signing), and Sales module scaffolding.

## Quick Start

### 1. Install dependencies
```bash
composer require simplesoftwareio/simple-qrcode setasign/fpdi
```

### 2. Merge files into your project
Copy all directories from this package into your Laravel project root.

### 3. Update `config/auth.php`
Add the `portal` guard and `portal_users` provider (see `config/auth.php` in this package).

### 4. Register middleware

**Laravel 11+ (`bootstrap/app.php`)**:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'portal.auth' => \App\Http\Middleware\PortalAuthenticate::class,
        'portal.role' => \App\Http\Middleware\PortalRoleMiddleware::class,
    ]);
})
```

**Laravel 10 (`app/Http/Kernel.php` → `$routeMiddleware`)**:
```php
'portal.auth' => \App\Http\Middleware\PortalAuthenticate::class,
'portal.role' => \App\Http\Middleware\PortalRoleMiddleware::class,
```

### 5. Include routes in `routes/web.php`
```php
require __DIR__ . '/portal.php';
```

### 6. Register pagination view in `AppServiceProvider::boot()`
```php
\Illuminate\Pagination\Paginator::defaultView('portal.components.pagination');
```

### 7. Create storage directories
```bash
mkdir -p storage/app/portal/documents/signed
mkdir -p storage/app/temp
php artisan storage:link
```

### 8. Run migrations
```bash
php artisan migrate
```

### 9. Log in
- **URL**: `https://yourdomain.com/portal/login`
- **Username**: `admin`
- **Password**: `Admin@12345`

> ⚠️ Change the default password immediately after first login!

---

## Role Hierarchy

| Role | Level | Description |
|------|-------|-------------|
| Administrator | 100 | Full unrestricted access |
| Administrator 2 (adm2) | 80 | Manage users, cannot add admins |
| Pengurus | 60 | All menus except user management |
| Tim Kerja (Timker) | 40 | Basic access (dashboard + docsign view) |
| Singers | 20 | Basic access (dashboard + docsign view) |

Users can hold multiple roles; the **highest** role determines menu access.

---

## Features

### DocSign
- Upload PDFs (approved users only)
- Assign one or more signers with username/email autocomplete
- Interactive PDF viewer with click-to-place QR code
- Drag to reposition, resize QR stamp
- QR code value = `yourdomain.com/verify/{hashCode}` (integrates with your existing verify system)
- Signer label printed above QR (name + timestamp)
- Sign chain tracking with partially_signed / completed states
- Reject with reason

### User Management (admin / adm2)
- Create, edit, deactivate users
- Assign one or more roles
- Toggle document upload permission per user
- Live user search for signer assignment

### Menu Permission Manager (admin only)
- Visual role × menu checkbox matrix
- Toggle all / none per role
- Auto-grants parent menu when child is enabled

### Sales Module
- Stub pages with animated "On Development" screens
- Rotating funny developer quotes
- Fake progress bar, floating emojis, bouncing robot 🤖

---

## File Structure

```
app/
  Http/
    Controllers/Portal/
      AuthController.php
      DashboardController.php
      DocSignController.php
      MenuPermissionController.php
      UserController.php
    Middleware/
      PortalAuthenticate.php
      PortalRoleMiddleware.php
  Models/
    PortalUser.php
    Role.php
    PortalMenu.php
    PortalDocument.php
    DocumentSignature.php

database/migrations/
  ..._create_roles_table.php
  ..._create_portal_users_table.php
  ..._create_roles_menus_permissions_table.php
  ..._create_documents_table.php

resources/views/portal/
  auth/login.blade.php
  layouts/app.blade.php
  dashboard/index.blade.php
  docsign/
    index.blade.php
    upload.blade.php
    show.blade.php
    sign.blade.php
  users/
    index.blade.php
    create.blade.php
    edit.blade.php
    _form.blade.php
  sales/
    submit.blade.php
    reports.blade.php
    _wip.blade.php
  settings/
    menu-permissions.blade.php
  components/
    pagination.blade.php

routes/portal.php
config/auth.php        ← reference only, merge manually
```
