<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\Request;

class AdminCouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query()->with(['user', 'booking']);

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->string('code') . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('is_used')) {
            $query->where('is_used', $request->boolean('is_used'));
        }

        if ($request->filled('valid')) {
            $query->where('is_used', false)
                ->where(function ($q) {
                    $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now());
                });
        }

        return CouponResource::collection(
            $query->orderByDesc('created_at')->paginate(15)
        );
    }

    public function store(StoreCouponRequest $request)
    {
        $data = $request->validated();
        $data['is_used'] = $data['is_used'] ?? false;

        $coupon = Coupon::create($data);

        return response()->json([
            'message' => 'Coupon created successfully',
            'data' => new CouponResource($coupon->load(['user', 'booking'])),
        ], 201);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon)
    {
        $coupon->update($request->validated());

        return response()->json([
            'message' => 'Coupon updated successfully',
            'data' => new CouponResource($coupon->fresh()->load(['user', 'booking'])),
        ]);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return response()->json([
            'message' => 'Coupon deleted successfully',
        ]);
    }
}