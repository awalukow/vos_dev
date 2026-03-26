# VOS Portal Fix — Laravel 10

The previous fix package accidentally shipped a Laravel 11 `bootstrap/app.php`.
This package corrects that and gives you the exact steps for Laravel 10.

---

## DO THIS FIRST — Restore bootstrap/app.php

Copy `bootstrap/app.php` from this package into your project root's `bootstrap/` folder,
replacing the broken one. This will make artisan work again immediately.

---

## Step 1 — Register middleware in `app/Http/Kernel.php`

Open your existing `app/Http/Kernel.php`. Find `$routeMiddleware` and add these two lines:

```php
'portal.auth' => \App\Http\Middleware\PortalAuthenticate::class,
'portal.role' => \App\Http\Middleware\PortalRoleMiddleware::class,
```

The full `Kernel.php` in this package shows exactly where they go.
You can either copy that file wholesale, or just add the two lines manually.

---

## Step 2 — Add portal middleware classes

Copy these two files into your project's `app/Http/Middleware/` folder:

- `app/Http/Middleware/PortalAuthenticate.php`
- `app/Http/Middleware/PortalRoleMiddleware.php`

---

## Step 3 — Update `config/auth.php`

Add the portal guard and provider. Open your `config/auth.php` and add:

In `'guards'`:
```php
'portal' => [
    'driver'   => 'session',
    'provider' => 'portal_users',
],
```

In `'providers'`:
```php
'portal_users' => [
    'driver' => 'eloquent',
    'model'  => App\Models\PortalUser::class,
],
```

---

## Step 4 — Add the require line to `routes/web.php`

Open your existing `routes/web.php`. Add this at the very bottom:

```php
require __DIR__ . '/portal.php';
```

Make sure `routes/portal.php` exists (it was in the original vos_portal.zip).

---

## Step 5 — Clear caches

```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

---

## Step 6 — Run migrations (if not done yet)

```bash
php artisan migrate
```

---

## Step 7 — Run the diagnostic command

Copy `app/Console/Commands/CheckPortalSetup.php` into your project, then:

```bash
php artisan portal:check
```

---

## Step 8 — Verify routes loaded

```bash
php artisan route:list --name=portal
```

You should see entries for `portal.login`, `portal.dashboard`, `portal.docsign.index`, etc.

---

## Login

- URL: `http://localhost:8000/portal/login`
- Username: `admin`
- Password: `Admin@12345`
