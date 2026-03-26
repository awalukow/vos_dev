<?php

/*
|--------------------------------------------------------------------------
| Create The Application  (Laravel 10 format — NOT Laravel 11)
|--------------------------------------------------------------------------
| The fix package accidentally shipped the Laravel 11 version of this file.
| This restores the correct Laravel 10 format.
|
| DO NOT use Application::configure() — that only exists in Laravel 11.
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
*/

return $app;
