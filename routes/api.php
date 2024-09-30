<?php

use App\Http\Controllers\GoogleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/google/redirect', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/google/events', [GoogleController::class, 'getGoogleEvents']);
    Route::post('/google/events', [GoogleController::class, 'addGoogleEvent']);
    Route::put('/google/events/{eventId}', [GoogleController::class, 'updateGoogleEvent']);
    Route::delete('/google/events/{eventId}', [GoogleController::class, 'deleteGoogleEvent']);
});

Route::middleware('auth:sanctum')->get('/auth/check', function (Request $request) {
    return response()->json(['authenticated' => true]);
});