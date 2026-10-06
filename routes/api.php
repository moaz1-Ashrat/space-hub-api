<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SpaceController;
use App\Http\Controllers\Api\V1\SpaceImageController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminSpaceController;
use App\Http\Controllers\Api\V1\Admin\AdminTransactionController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;

// ============================================
// AUTH
// ============================================
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// ============================================
// SPACES + FEATURES + AVAILABILITY + IMAGES
// ============================================
Route::prefix('v1')->group(function () {
    // Public
    Route::get('/spaces', [SpaceController::class, 'index']);
    Route::get('/spaces/{space}', [SpaceController::class, 'show']);
    Route::get('/features', [\App\Http\Controllers\Api\V1\FeatureController::class, 'index']);
    Route::get('/spaces/{space}/availability', [AvailabilityController::class, 'indexBySpace']);

    Route::middleware('auth:sanctum')->group(function () {
        // Spaces (owner)
        Route::post('/spaces', [SpaceController::class, 'store']);
        Route::put('/spaces/{space}', [SpaceController::class, 'update']);
        Route::delete('/spaces/{space}', [SpaceController::class, 'destroy']);
        Route::get('/spaces/owner/me', [SpaceController::class, 'mySpaces']);

        // ⚠️ Space Images — في المكان الصحيح
        Route::post('/spaces/{space}/images', [SpaceImageController::class, 'store']);
        Route::delete('/spaces/images/{image}', [SpaceImageController::class, 'destroy']);
        Route::put('/spaces/images/{image}/primary', [SpaceImageController::class, 'setPrimary']);

        // Availability (owner)
        Route::post('/spaces/{space}/availability', [AvailabilityController::class, 'store']);
        Route::put('/availability/{availability}', [AvailabilityController::class, 'update']);
    });
});

// ============================================
// BOOKINGS
// ============================================
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

// ============================================
// PAYMENTS
// ============================================
Route::prefix('v1')->group(function () {
    // Webhook: OUTSIDE auth:sanctum — authenticated by Stripe signature
    Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/payments/checkout', [PaymentController::class, 'checkout']);
        Route::get('/payments/history', [PaymentController::class, 'history']);
    });
});

// ============================================
// REVIEWS
// ============================================
Route::prefix('v1')->group(function () {
    Route::get('/spaces/{space}/reviews', [ReviewController::class, 'indexBySpace']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/reviews', [ReviewController::class, 'store']);
    });
});

// ============================================
// ADMIN
// ============================================
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::put('/users/{id}/suspend', [AdminUserController::class, 'suspend']);
    Route::put('/users/{id}/activate', [AdminUserController::class, 'activate']);

    Route::get('/spaces/pending', [AdminSpaceController::class, 'pending']);
    Route::put('/spaces/{id}/approve', [AdminSpaceController::class, 'approve']);
    Route::put('/spaces/{id}/reject', [AdminSpaceController::class, 'reject']);

    Route::get('/transactions', [AdminTransactionController::class, 'index']);
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
});
