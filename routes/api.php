<?php

use App\Http\Controllers\Api\v1\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/hello', function() {
    return ['message' => 'Hello, World!'];
});


Route::prefix('v1')->group(function() {
    Route::apiResource('posts', PostController::class);
});