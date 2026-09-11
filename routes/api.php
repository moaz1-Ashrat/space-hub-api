<?php

use App\Http\Controllers\Api\V1\PaymentController;
use Illuminate\Support\Facades\Route;

// ... keep existing routes above

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/payments/checkout', [PaymentController::class, 'checkout']);
    Route::get('/payments/history', [PaymentController::class, 'history']);
});

// Excluded from Sanctum: authenticated by Stripe signature.
Route::post('/v1/payments/webhook', [PaymentController::class, 'webhook']);
