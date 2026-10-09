<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\{TicketAuthController as Auth,TicketController as Tickets};
use App\Http\Controllers\Portal\TicketAdminController as Admin;
use App\Http\Middleware\{CustomerAuthenticate,TicketStaff};
use App\Http\Controllers\MidtransController;

Route::post('/tickets/midtrans/notification',[MidtransController::class,'notification'])->name('tickets.midtrans.notification');

Route::prefix('tickets')->name('tickets.')->middleware(\App\Http\Middleware\TicketLocale::class)->group(function () {
    Route::post('/language', function (\Illuminate\Http\Request $request) {
        $data=$request->validate(['locale'=>'required|in:id,en']);
        $request->session()->put('ticket_locale',$data['locale']);
        return back();
    })->name('language');
    // Fixed asset routes also support this repository's root-level front controller.
    Route::get('/assets/vos-tickets-logo-v4.png',fn()=>response()->file(public_path('assets/images/vos-tickets-logo-v4.png'),['Content-Type'=>'image/png','Cache-Control'=>'public, max-age=86400']))->name('logo');
    Route::get('/assets/payments/{logo}.svg',fn($logo)=>response()->file(public_path('assets/images/payments/'.$logo.'.svg'),['Content-Type'=>'image/svg+xml','Cache-Control'=>'public, max-age=86400']))->where('logo','qris|bank-transfer')->name('payment-logo');
    Route::get('/assets/Mitr-SemiBold.ttf',fn()=>response()->file(public_path('assets/fonts/mitr/Mitr-SemiBold.ttf'),['Content-Type'=>'font/ttf','Cache-Control'=>'public, max-age=31536000']))->name('brand-font');
    Route::get('/assets/tickets.css',fn()=>response()->file(public_path('css/tickets.css'),['Content-Type'=>'text/css','Cache-Control'=>'public, max-age=300']))->name('styles');
    Route::get('/assets/venue-designer.js',fn()=>response()->file(public_path('js/venue-designer.js'),['Content-Type'=>'application/javascript','Cache-Control'=>'public, max-age=300']))->name('designer');
    Route::get('/',[Tickets::class,'index'])->name('events');
    Route::get('/login',[Auth::class,'loginForm'])->name('login');
    Route::post('/login',[Auth::class,'login'])->name('login.submit')->middleware('throttle:6,1');
    Route::get('/register',[Auth::class,'registerForm'])->name('register');
    Route::post('/register',[Auth::class,'register'])->name('register.submit')->middleware('throttle:5,1');
    Route::get('/verify-email',[Auth::class,'verifyForm'])->name('verify');
    Route::post('/verify-email',[Auth::class,'verify'])->name('verify.submit')->middleware('throttle:6,1');
    Route::post('/resend-otp',[Auth::class,'resend'])->middleware('throttle:3,1')->name('resend');
    Route::post('/logout',[Auth::class,'logout'])->name('logout');
    Route::get('/validate/{token}',[Tickets::class,'validateTicket'])->middleware('throttle:60,1')->name('validate');
    Route::get('/receipt/{reference}',[Tickets::class,'receipt'])->middleware('throttle:60,1')->name('receipt');
    Route::get('/thumbnail/{event}',function (\App\Models\TicketEvent $event) {
        abort_unless($event->thumbnail && ($event->published || $event->starts_at->lte(now()) || auth('portal')->check()),404);
        return response()->file(Storage::disk('local')->path($event->thumbnail));
    })->name('thumbnail');
    Route::get('/memories/{event}/photos/{index}',function (\App\Models\TicketEvent $event,int $index) {
        abort_unless($event->starts_at->lte(now()) || auth('portal')->check(),404);
        $path=$event->memory_photos[$index]??null;
        abort_unless($path && Storage::disk('local')->exists($path),404);
        return response()->file(Storage::disk('local')->path($path),['X-Content-Type-Options'=>'nosniff']);
    })->whereNumber('index')->name('memory-photo');
    Route::get('/payment-image/{method}',function (\App\Models\TicketPaymentMethod $method) {
        abort_unless($method->qr_image && ($method->active || auth('portal')->check()),404);
        return response()->file(Storage::disk('local')->path($method->qr_image));
    })->name('payment-image');
    Route::middleware(CustomerAuthenticate::class)->group(function () {
        Route::post('/orders/{order}/midtrans',[MidtransController::class,'start'])->middleware('throttle:5,1')->name('midtrans.start');
        Route::post('/orders/{order}/midtrans/status',[MidtransController::class,'refresh'])->middleware('throttle:10,1')->name('midtrans.refresh');
        Route::get('/events/{event}',[Tickets::class,'event'])->name('select');
        Route::post('/events/{event}/reserve',[Tickets::class,'reserve'])->middleware('throttle:10,1')->name('reserve');
        Route::get('/orders',[Tickets::class,'orders'])->name('orders');
        Route::get('/orders/{order}',[Tickets::class,'order'])->name('order');
        Route::post('/orders/{order}/promo',[Tickets::class,'promo'])->middleware('throttle:10,1,ticket-promo')->name('promo');
        Route::post('/orders/{order}/free',[Tickets::class,'free'])->middleware('throttle:5,1,ticket-free')->name('free');
        Route::post('/orders/{order}/proof',[Tickets::class,'proof'])->middleware('throttle:5,1')->name('proof');
        Route::post('/orders/{order}/cancel',[Tickets::class,'cancel'])->name('cancel');
        Route::get('/orders/{order}/qr',[Tickets::class,'bookingQr'])->name('booking-qr');
        Route::get('/qr/{item}',[Tickets::class,'qr'])->name('qr');
    });
});
Route::prefix('portal/ticketing')->name('portal.ticketing.')->middleware('portal.auth')->group(function () {
    Route::middleware(TicketStaff::class.':review')->group(function () {
        Route::get('/dashboard',[\App\Http\Controllers\Portal\TicketReportController::class,'dashboard'])->name('dashboard');
        Route::get('/dashboard/export',[\App\Http\Controllers\Portal\TicketReportController::class,'exportDashboard'])->name('dashboard.export');
        Route::get('/orders/export',[\App\Http\Controllers\Portal\TicketReportController::class,'exportOrders'])->name('orders.export');
        Route::get('/methods',[Admin::class,'methods'])->name('methods');
        Route::post('/methods/{method}',[Admin::class,'method'])->name('methods.update');
        Route::get('/promos',[\App\Http\Controllers\Portal\TicketPromoController::class,'index'])->name('promos');
        Route::post('/promos',[\App\Http\Controllers\Portal\TicketPromoController::class,'save'])->name('promos.store');
        Route::post('/promos/{promo}',[\App\Http\Controllers\Portal\TicketPromoController::class,'save'])->name('promos.update');
        Route::get('/orders',[Admin::class,'orders'])->name('orders');
        Route::get('/orders/{order}',[Admin::class,'order'])->name('orders.show');
        Route::get('/orders/{order}/qr',[Admin::class,'bookingQr'])->name('orders.qr');
        Route::get('/orders/{order}/tickets/{item}/qr',[Admin::class,'ticketQr'])->name('orders.ticket-qr');
        Route::get('/payments',[Admin::class,'payments'])->name('payments');
        Route::get('/payments/{order}/proof',[Admin::class,'proof'])->name('proof');
        Route::post('/payments/{order}/review',[Admin::class,'review'])->name('review');
        Route::post('/payments/{order}/resend',[Admin::class,'resend'])->middleware('throttle:5,1')->name('resend');
    });
    Route::middleware(TicketStaff::class)->group(function () {
        Route::post('/methods/{method}/test-midtrans',[Admin::class,'testMidtrans'])->middleware('throttle:5,1,midtrans-connection')->name('methods.test-midtrans');
        Route::delete('/orders/{order}',[Admin::class,'removeOrder'])->name('orders.destroy');
        Route::get('/events',[Admin::class,'events'])->name('events');
        Route::get('/events/create',[Admin::class,'eventForm'])->name('events.create');
        Route::post('/events',[Admin::class,'saveEvent'])->name('events.store');
        Route::get('/events/{event}/edit',[Admin::class,'eventForm'])->name('events.edit');
        Route::get('/events/{event}/memories',[\App\Http\Controllers\Portal\TicketMemoryController::class,'edit'])->name('events.memories');
        Route::post('/events/{event}/memories',[\App\Http\Controllers\Portal\TicketMemoryController::class,'save'])->name('events.memories.save');
        Route::post('/events/{event}',[Admin::class,'saveEvent'])->name('events.update');
        Route::delete('/events/{event}',[Admin::class,'removeEvent'])->name('events.destroy');
        Route::get('/venues',[Admin::class,'venues'])->name('venues');
        Route::get('/venues/create',[Admin::class,'venueForm'])->name('venues.create');
        Route::post('/venues',[Admin::class,'saveVenue'])->name('venues.store');
        Route::get('/venues/{venue}/edit',[Admin::class,'venueForm'])->name('venues.edit');
        Route::post('/venues/{venue}',[Admin::class,'saveVenue'])->name('venues.update');
        Route::delete('/venues/{venue}',[Admin::class,'removeVenue'])->name('venues.destroy');
        Route::get('/venues/{venue}/export',[Admin::class,'exportVenue'])->name('venues.export');
        Route::get('/customers',[Admin::class,'customers'])->name('customers');
        Route::post('/customers/{customer}',[Admin::class,'customer'])->name('customers.update');
        Route::post('/customers/{customer}/otp',[Admin::class,'otp'])->middleware('throttle:10,1')->name('customers.otp');
    });
});
