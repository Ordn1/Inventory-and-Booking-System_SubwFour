<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingPublicController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\DashboardController; 
use App\Http\Controllers\ServiceTypeController; 
use App\Http\Controllers\StockOutController; 

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('system')
        : redirect()->route('login');
})->name('home');

Route::get('/login',  [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Public Booking Portal
|--------------------------------------------------------------------------
*/
Route::get('/booking',  [BookingPublicController::class,'index'])->name('booking.portal');
Route::post('/booking', [BookingPublicController::class,'store'])->name('booking.portal.store');


/*
|--------------------------------------------------------------------------
| Authenticated System Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Dashboard (single authoritative route)
    Route::get('/system', [DashboardController::class,'index'])->name('system');

    // Employees
    Route::resource('employees', EmployeeController::class)->except(['show','create']);

    // Suppliers
    Route::get('/suppliers',                       [SupplierController::class,'index'])->name('suppliers.index');
    Route::post('/suppliers',                      [SupplierController::class,'store'])->name('suppliers.store');
    Route::get('/suppliers/{supplier_id}/edit',    [SupplierController::class,'edit'])->name('suppliers.edit');
    Route::put('/suppliers/{supplier_id}',         [SupplierController::class,'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier_id}',      [SupplierController::class,'destroy'])->name('suppliers.destroy');

    // Inventory (Items)
    Route::get('/inventory',                       [ItemController::class,'index'])->name('inventory.index');
    Route::post('/inventory',                      [ItemController::class,'store'])->name('inventory.store');
    Route::get('/inventory/{item_id}/edit',        [ItemController::class,'edit'])->name('inventory.edit');
    Route::put('/inventory/{item_id}',             [ItemController::class,'update'])->name('inventory.update');
    Route::delete('/inventory/{item_id}',          [ItemController::class,'destroy'])->name('inventory.destroy');

    // Item Categories
    Route::get('/inventory/item-categories',                          [ItemCategoryController::class,'index'])->name('inventory.itemctgry');
    Route::post('/inventory/item-categories',                         [ItemCategoryController::class,'store'])->name('inventory.itemctgry.store');
    Route::get('/inventory/item-categories/{itemctgry_id}/edit',      [ItemCategoryController::class,'edit'])->name('inventory.itemctgry.edit');
    Route::put('/inventory/item-categories/{itemctgry_id}',           [ItemCategoryController::class,'update'])->name('inventory.itemctgry.update');
    Route::delete('/inventory/item-categories/{itemctgry_id}',        [ItemCategoryController::class,'destroy'])->name('inventory.itemctgry.destroy');

    // Stock-In
    Route::get('/stock-in',                       [StockInController::class,'index'])->name('stock_in.index');
    Route::post('/stock-in',                      [StockInController::class,'store'])->name('stock_in.store');
    Route::put('/stock-in/{stockin_id}',          [StockInController::class,'update'])->name('stock_in.update');
    Route::delete('/stock-in/{stockin_id}',       [StockInController::class,'destroy'])->name('stock_in.destroy');

    // Services
    Route::get('/services',                 [ServiceController::class,'index'])->name('services.index');
    Route::post('/services',                [ServiceController::class,'store'])->name('services.store');
    Route::get('/services/{service}/edit',  [ServiceController::class,'edit'])->name('services.edit');
    Route::put('/services/{service}',       [ServiceController::class,'update'])->name('services.update');
    Route::post('/services/{service}/status',[ServiceController::class,'updateStatus'])->name('services.status');

    // Service Types 
    Route::post('/service-types',           [ServiceTypeController::class,'store'])->name('service_types.store');
    Route::put('/service-types/{id}',       [ServiceTypeController::class,'update'])->name('service_types.update');
    Route::delete('/service-types/{id}',    [ServiceTypeController::class,'destroy'])->name('service_types.destroy');
   
    // System Bookings
    Route::get('/system/bookings',              [BookingController::class,'index'])->name('bookings.index');
    Route::post('/bookings/{booking}/appoint',  [BookingController::class,'appoint'])->name('bookings.appoint');

    // Reports
    Route::get('/reports',                      [ReportsController::class,'index'])->name('reports.index');
    Route::get('/stock-out',                    [StockOutController::class, 'index'])->name('stock_out.index');
    Route::get('/stock-out/{stockout}/receipt', [StockOutController::class, 'receipt'])->name('stock_out.receipt');
});