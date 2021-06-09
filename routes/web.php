<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\bot\updateController;
use App\Http\Middleware\telBotUpdateMiddleware;
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

Route::get('/', function () {
    return view('welcome');
});

Route::post('telegramBot/update', [updateController::class, 'update'])->middleware([telBotUpdateMiddleware::class]);
