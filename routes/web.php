<?php
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DocumentValidationController;
use Illuminate\Support\Facades\Route;

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

Route::post('/system/migrate', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return nl2br(e(\Illuminate\Support\Facades\Artisan::output()));
})->middleware(['portal.auth', 'portal.role:administrator']);

Route::get('/', function () {
    return view('home');
});

Route::get('/', [HomeController::class, 'index']);

//Route::get('/verify', 'DocumentValidationController@showDocumentValidation');
//Route::get('/verify', [DocumentValidationController::class, 'showDocumentValidation_old']);
Route::get('/verify/{id}', [DocumentValidationController::class, 'showDocumentValidation']);


require __DIR__ . '/portal.php';

require __DIR__ . '/tickets.php';
