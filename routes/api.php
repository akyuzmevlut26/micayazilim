<?php

use App\Http\Controllers\Api\Log\LogController;
use App\Http\Controllers\Api\Product\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', function (Request $request) {
    return response()->json([
        'status' => true,
        'status_code' => 200
    ]);
});

// Product
Route::post('product/refresh', [ProductController::class, 'refresh']);
Route::apiResource('product', ProductController::class);

// Log
Route::apiResource('log', LogController::class);
