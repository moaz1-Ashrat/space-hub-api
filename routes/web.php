<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Space Hub API',
        'version' => '1.0',
        'status' => 'ok',
    ]);
});

// Fallback for guests - API has no login page, return 401 JSON
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');
