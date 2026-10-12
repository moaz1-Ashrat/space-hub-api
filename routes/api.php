<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SpaceController;
use App\Http\Controllers\Api\V1\SpaceImageController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReviewController;

// Admin
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminSpaceController;
use App\Http\Controllers\Api\V1\Admin\AdminTransactionController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AdminCouponController;
use App\Http\Controllers\Api\V1\CouponController;

// Owner
use App\Http\Controllers\Api\V1\Owner\OwnerAnalyticsController;

// ============================================
// AUTH
// ============================================
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Password reset (public + rate-limited)
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Profile & Password
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
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
    Route::get('/coupons/my', [CouponController::class, 'my']);

    Route::middleware('auth:sanctum')->group(function () {
        // Spaces (owner)
        Route::post('/spaces', [SpaceController::class, 'store']);
        Route::put('/spaces/{space}', [SpaceController::class, 'update']);
        Route::delete('/spaces/{space}', [SpaceController::class, 'destroy']);
        Route::get('/spaces/owner/me', [SpaceController::class, 'mySpaces']);

        // Space Images
        Route::post('/spaces/{space}/images', [SpaceImageController::class, 'store']);
        Route::delete('/spaces/images/{image}', [SpaceImageController::class, 'destroy']);
        Route::put('/spaces/images/{image}/primary', [SpaceImageController::class, 'setPrimary']);

        // Availability (owner)
        Route::post('/spaces/{space}/availability', [AvailabilityController::class, 'store']);
        Route::put('/availability/{availability}', [AvailabilityController::class, 'update']);
        Route::delete('/availability/{availability}', [AvailabilityController::class, 'destroy']);
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
    // Public
    Route::get('/spaces/{space}/reviews', [ReviewController::class, 'indexBySpace']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::get('/reviews/me', [ReviewController::class, 'myReviews']);
    });
});

// ============================================
// ADMIN
// ============================================
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Users
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::put('/users/{id}/suspend', [AdminUserController::class, 'suspend']);
    Route::put('/users/{id}/activate', [AdminUserController::class, 'activate']);

    // Spaces
    Route::get('/spaces', [AdminSpaceController::class, 'index']);
    Route::get('/spaces/pending', [AdminSpaceController::class, 'pending']);
    Route::put('/spaces/{id}/approve', [AdminSpaceController::class, 'approve']);
    Route::put('/spaces/{id}/reject', [AdminSpaceController::class, 'reject']);

    // Transactions
    Route::get('/transactions', [AdminTransactionController::class, 'index']);

    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/summary', [AdminAnalyticsController::class, 'summary']);
        Route::get('/revenue', [AdminAnalyticsController::class, 'revenue']);
        Route::get('/growth', [AdminAnalyticsController::class, 'growth']);
        Route::get('/distribution', [AdminAnalyticsController::class, 'distribution']);
        Route::get('/top-spaces', [AdminAnalyticsController::class, 'topSpaces']);
        Route::get('/top-owners', [AdminAnalyticsController::class, 'topOwners']);
        Route::get('/top-customers', [AdminAnalyticsController::class, 'topCustomers']);
    });
    Route::prefix('coupons')->group(function () {
    Route::get('/', [AdminCouponController::class, 'index']);
    Route::post('/', [AdminCouponController::class, 'store']);
    Route::put('/{coupon}', [AdminCouponController::class, 'update']);
    Route::delete('/{coupon}', [AdminCouponController::class, 'destroy']);
});
});

// ============================================
// OWNER ANALYTICS
// ============================================
Route::prefix('v1/owner')->middleware(['auth:sanctum'])->group(function () {
    Route::prefix('analytics')->group(function () {
        Route::get('/summary', [OwnerAnalyticsController::class, 'summary']);
        Route::get('/revenue', [OwnerAnalyticsController::class, 'revenue']);
        Route::get('/top-spaces', [OwnerAnalyticsController::class, 'topSpaces']);
        Route::get('/popular-days', [OwnerAnalyticsController::class, 'popularDays']);
        Route::get('/distribution', [OwnerAnalyticsController::class, 'distribution']);
    });
});
