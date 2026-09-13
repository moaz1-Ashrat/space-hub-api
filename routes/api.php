<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SpaceController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\PaymentController;

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::prefix('v1')->group(function () {
    // Public spaces
    Route::get('/spaces', [SpaceController::class, 'index']);
    Route::get('/spaces/{space}', [SpaceController::class, 'show']);

    // Public availability
    Route::get('/spaces/{space}/availability', [AvailabilityController::class, 'indexBySpace']);

    Route::middleware('auth:sanctum')->group(function () {
        // Spaces (owner)
        Route::post('/spaces', [SpaceController::class, 'store']);
        Route::put('/spaces/{space}', [SpaceController::class, 'update']);
        Route::delete('/spaces/{space}', [SpaceController::class, 'destroy']);
        Route::get('/spaces/owner/me', [SpaceController::class, 'mySpaces']);

        // Availability (owner)
        Route::post('/spaces/{space}/availability', [AvailabilityController::class, 'store']);
        Route::put('/availability/{availability}', [AvailabilityController::class, 'update']);
    });
});

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/bookings/customer', [BookingController::class, 'customerIndex']);
        Route::get('/bookings/owner', [BookingController::class, 'ownerIndex']);
        Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
        Route::put('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
        Route::get('/bookings/{id}', [BookingController::class, 'show']);
    });


});

Route::prefix('v1')->group(function () {

    // Webhook: OUTSIDE auth:sanctum — authenticated by Stripe signature
    Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/payments/checkout', [PaymentController::class, 'checkout']);
        Route::get('/payments/history', [PaymentController::class, 'history']);
    });
});

