<?php
/**
 * FOR LARAVEL 10 ONLY
 * --------------------
 * If you are on Laravel 10, open app/Http/Kernel.php
 * and add these two lines to the $routeMiddleware array:
 *
 *   protected $routeMiddleware = [
 *       // ... existing entries ...
 *       'portal.auth' => \App\Http\Middleware\PortalAuthenticate::class,
 *       'portal.role' => \App\Http\Middleware\PortalRoleMiddleware::class,
 *   ];
 *
 * Then open routes/web.php and add at the very bottom:
 *
 *   require __DIR__ . '/portal.php';
 *
 * Then clear the route cache:
 *   php artisan route:clear
 *   php artisan config:clear
 *   php artisan cache:clear
 */
