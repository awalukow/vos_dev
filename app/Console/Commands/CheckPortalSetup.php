<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class CheckPortalSetup extends Command
{
    protected $signature   = 'portal:check';
    protected $description = 'Diagnose VOS portal setup issues';

    public function handle(): void
    {
        $this->info('');
        $this->info('══════════════════════════════════════');
        $this->info('  VOS Portal Setup Diagnostics');
        $this->info('══════════════════════════════════════');
        $this->info('');

        // 1. Laravel version
        $version = app()->version();
        $this->line("  Laravel version: <fg=cyan>{$version}</>");
        $this->info('');

        // 2. bootstrap/app.php sanity (L10 vs L11)
        $bootstrapContent = file_get_contents(base_path('bootstrap/app.php'));
        $usesL11Style     = str_contains($bootstrapContent, 'Application::configure');
        $this->checkItem(
            'bootstrap/app.php is correct for Laravel 10',
            ! $usesL11Style,
            'Replace bootstrap/app.php with the one from vos_l10_fix.zip'
        );

        // 3. Routes registered
        $hasLogin     = Route::has('portal.login');
        $hasDashboard = Route::has('portal.dashboard');
        $this->checkItem('portal.login route exists',     $hasLogin,
            "Add this line at the bottom of routes/web.php:  require __DIR__ . '/portal.php';");
        $this->checkItem('portal.dashboard route exists', $hasDashboard);

        // 4. Auth config  — use null !== config() instead of isset(config())
        $portalGuard    = config('auth.guards.portal');
        $portalProvider = config('auth.providers.portal_users');
        $this->checkItem(
            "'portal' guard in config/auth.php",
            null !== $portalGuard,
            "Add 'portal' guard to config/auth.php"
        );
        $this->checkItem(
            "'portal_users' provider in config/auth.php",
            null !== $portalProvider,
            "Add 'portal_users' provider to config/auth.php"
        );

        // 5. Middleware classes
        $this->checkItem(
            'PortalAuthenticate class exists',
            class_exists(\App\Http\Middleware\PortalAuthenticate::class),
            'Copy app/Http/Middleware/PortalAuthenticate.php from vos_l10_fix.zip'
        );
        $this->checkItem(
            'PortalRoleMiddleware class exists',
            class_exists(\App\Http\Middleware\PortalRoleMiddleware::class),
            'Copy app/Http/Middleware/PortalRoleMiddleware.php from vos_l10_fix.zip'
        );

        // 6. Models
        $this->checkItem('App\Models\PortalUser exists', class_exists(\App\Models\PortalUser::class),
            'Copy app/Models/PortalUser.php from vos_portal.zip');
        $this->checkItem('App\Models\Role exists',       class_exists(\App\Models\Role::class),
            'Copy app/Models/Role.php from vos_portal.zip');

        // 7. Database tables
        foreach (['portal_users', 'roles', 'portal_menus', 'role_menu_permissions', 'portal_documents', 'document_signatures'] as $table) {
            try {
                \DB::table($table)->count();
                $this->checkItem("Table '{$table}' exists", true);
            } catch (\Exception $e) {
                $this->checkItem("Table '{$table}' exists", false, 'Run: php artisan migrate');
            }
        }

        // 8. Default admin user
        try {
            $admin = \DB::table('portal_users')->where('username', 'admin')->first();
            $this->checkItem('Default admin user exists', null !== $admin);
        } catch (\Exception $e) {
            $this->checkItem('Default admin user exists', false, 'Run: php artisan migrate');
        }

        // 9. Composer packages
        $this->checkItem(
            'simplesoftwareio/simple-qrcode installed',
            class_exists(\SimpleSoftwareIO\QrCode\QrCodeServiceProvider::class),
            'Run: composer require simplesoftwareio/simple-qrcode'
        );
        $this->checkItem(
            'setasign/fpdi installed',
            class_exists(\setasign\Fpdi\Fpdi::class),
            'Run: composer require setasign/fpdi'
        );

        $this->info('');
        $this->info('After any changes run:');
        $this->line('  php artisan route:clear');
        $this->line('  php artisan config:clear');
        $this->line('  php artisan cache:clear');
        $this->info('');

        if ($hasLogin) {
            $this->info('Login URL: ' . route('portal.login'));
        }

        $this->info('');
    }

    private function checkItem(string $label, bool $ok, string $hint = ''): void
    {
        $icon = $ok ? '<fg=green>✓</>' : '<fg=red>✗</>';
        $this->line("  {$icon}  {$label}");
        if (! $ok && $hint) {
            $this->line("       <fg=yellow>→ {$hint}</>");
        }
    }
}
