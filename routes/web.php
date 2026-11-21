<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pelanggan (publik)
|--------------------------------------------------------------------------
*/
Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/meja/{table}', [StorefrontController::class, 'table'])->whereNumber('table')->name('table.menu');
Route::post('/pesanan', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('orders.store');
Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('orders.show'); // {order} = token acak

/*
|--------------------------------------------------------------------------
| Staf: /admin/*  (nama route sama dengan versi sebelumnya, jadi view tidak perlu diubah)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'login'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'authenticate'])->name('authenticate');
    Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {

        // Admin dan kasir
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/pesanan/riwayat', [Admin\OrderHistoryController::class, 'index'])->name('orders.history');
        Route::get('/pesanan', [Admin\OrderBoardController::class, 'index'])->name('orders');
        Route::patch('/pesanan/{order}', [Admin\OrderBoardController::class, 'update'])->name('orders.update');

        Route::get('/menu', [Admin\MenuController::class, 'index'])->name('menu');
        Route::patch('/menu/{product}/stok', [Admin\MenuController::class, 'toggleStock'])->name('menu.stock');

        Route::get('/meja', [Admin\TableController::class, 'index'])->name('tables');

        Route::get('/kanal', [Admin\ChannelController::class, 'index'])->name('channels');
        Route::post('/kanal/pesanan', [Admin\ExternalOrderController::class, 'store'])->name('channels.order');

        // Khusus admin (pengelola)
        Route::middleware('role:admin')->group(function () {
            Route::post('/menu', [Admin\MenuController::class, 'store'])->name('menu.save');
            Route::get('/menu/{product}/edit', [Admin\MenuController::class, 'edit'])->name('menu.edit');
            Route::patch('/menu/{product}', [Admin\MenuController::class, 'update'])->name('menu.update');
            Route::delete('/menu/{product}', [Admin\MenuController::class, 'destroy'])->name('menu.delete');

            Route::post('/meja', [Admin\TableController::class, 'store'])->name('tables.store');
            Route::delete('/meja/{table:number}', [Admin\TableController::class, 'destroy'])
                ->whereNumber('table')->name('tables.destroy');

            Route::get('/staf', [Admin\StaffController::class, 'index'])->name('staff');
            Route::post('/staf', [Admin\StaffController::class, 'store'])->name('staff.store');
            Route::patch('/staf/{user}', [Admin\StaffController::class, 'update'])->name('staff.update');
            Route::delete('/staf/{user}', [Admin\StaffController::class, 'destroy'])->name('staff.destroy');
        });
    });
});
