<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Get valid coupons for the authenticated user
     * (own coupons + generic coupons with user_id = null)
     */
    public function my(Request $request)
    {
        $userId = $request->user()->id;

        $coupons = Coupon::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereNull('user_id');
            })
            ->where('is_used', false)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', now());
            })
            ->orderBy('expiry_date')
            ->get();

        return CouponResource::collection($coupons);
    }
}