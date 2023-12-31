<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::post('/set-provider', [App\Http\Controllers\HomeController::class, 'setProviderSessionVar'])->name('set-provider-session');
Route::post('/new-conversion', [App\Http\Controllers\HomeController::class, 'newConversion'])->name('new-conversion');
Route::post('/refresh-currencies-dropdowns', [App\Http\Controllers\HomeController::class, 'refreshCurrenciesDropdowns'])->name('refresh-currencies');
Route::post('/get-history-rates', [App\Http\Controllers\HomeController::class, 'getHistoryRates'])->name('getHistoryRates');
