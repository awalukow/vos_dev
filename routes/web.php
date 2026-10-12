<?php
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DocumentValidationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('/system/migrate', function (\Illuminate\Http\Request $request) {
    $force = $request->boolean('force');
    $output = '$ php artisan migrate' . ($force ? ' --force' : '') . PHP_EOL;
    $successful = false;

    try {
        $exitCode = Artisan::call('migrate', ['--force' => $force, '--no-interaction' => true]);
        $output .= Artisan::output();

        if ($exitCode === 0) {
            $output .= PHP_EOL . '$ php artisan optimize:clear' . PHP_EOL;
            $exitCode = Artisan::call('optimize:clear');
            $output .= Artisan::output();
            $successful = $exitCode === 0;
        }
    } catch (\Throwable $exception) {
        report($exception);
        $output .= PHP_EOL . $exception->getMessage();
    }

    return response()->view('system.maintenance', compact('output', 'successful'), $successful ? 200 : 500);
})->middleware(['portal.auth', 'portal.role:administrator'])->name('system.migrate');

Route::middleware(['portal.auth', 'portal.role:administrator'])->group(function () {
    Route::view('/system/maintenance', 'system.maintenance')->name('system.maintenance');

    Route::post('/system/maintenance', function () {
        $output = '$ php artisan optimize:clear' . PHP_EOL;
        $successful = false;

        try {
            $exitCode = Artisan::call('optimize:clear');
            $output .= Artisan::output();

            if ($exitCode === 0) {
                $output .= PHP_EOL . '$ composer dump-autoload' . PHP_EOL;
                $process = new Process(['composer', 'dump-autoload', '--no-interaction', '--no-ansi'], base_path());
                $process->setTimeout(120);
                $process->run(function ($type, $buffer) use (&$output) {
                    $output .= $buffer;
                });
                $successful = $process->isSuccessful();
            }
        } catch (\Throwable $exception) {
            report($exception);
            $output .= PHP_EOL . $exception->getMessage();
        }

        return response()->view('system.maintenance', compact('output', 'successful'), $successful ? 200 : 500);
    })->name('system.maintenance.run');
});

Route::get('/migrate', function () {
        if (request('key') !== '8f3a7c1e-bd42-4e9f-98c7-64a2c8e12a9f') {
            abort(403, 'Unauthorized');
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            return 'Migration completed successfully!';
        } catch (\Exception $e) {
            return 'Migration failed: ' . $e->getMessage();
        }
    });

Route::get('/', function () {
    return view('home');
});

Route::get('/', [HomeController::class, 'index']);

Route::get('/schedule/{schedule}/image', function (\App\Models\Schedule $schedule) {
    abort_unless($schedule->rowstatus == 1
        && preg_match('#^schedule-images/[a-zA-Z0-9]+\.(jpg|jpeg|png|webp)$#', $schedule->event_image), 404);
    $disk = \Illuminate\Support\Facades\Storage::disk('local');
    abort_unless($disk->exists($schedule->event_image), 404);
    return response()->file($disk->path($schedule->event_image));
})->name('schedule.image');

//Route::get('/verify', 'DocumentValidationController@showDocumentValidation');
//Route::get('/verify', [DocumentValidationController::class, 'showDocumentValidation_old']);
Route::get('/verify/{id}', [DocumentValidationController::class, 'showDocumentValidation']);


require __DIR__ . '/portal.php';

require __DIR__ . '/tickets.php';
