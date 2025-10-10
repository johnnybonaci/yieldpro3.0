<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AccessTokenController;

/*
|--------------------------------------------------------------------------
| Access Token
|--------------------------------------------------------------------------
|
*/

Route::post('/token', [AccessTokenController::class, 'token']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
