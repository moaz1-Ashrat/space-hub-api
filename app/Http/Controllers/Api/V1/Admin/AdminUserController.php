<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return AdminUserResource::collection(
            $query->orderByDesc('created_at')->paginate(15)
        );
    }

    /**
     * Show detailed user info with role-specific stats.
     */
    public function show($id)
    {
        $user = User::with(['customer', 'spaceOwner', 'admin'])->findOrFail($id);

        $data = [
            'id' => $user->id,
            'name' => trim($user->first_name . ' ' . $user->last_name),
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'gender' => $user->gender,
            'created_at' => $user->created_at,
            'customer' => null,
            'space_owner' => null,
            'admin' => null,
        ];

        // Customer-specific stats
        if ($user->role === 'customer' && $user->customer) {
            $totalBookings = Booking::where('user_id', $user->id)->count();
            $totalSpent = Booking::where('user_id', $user->id)
                ->whereIn('booking_status', ['confirmed', 'completed'])
                ->sum('total_amount');
            $totalReviews = Review::where('customer_id', $user->id)->count();

            $data['customer'] = [
                'favorite' => $user->customer->favorite,
                'total_bookings' => $totalBookings,
                'total_spent' => (float) $totalSpent,
                'total_reviews' => $totalReviews,
            ];
        }

        // Owner-specific stats
        if ($user->role === 'space_owner' && $user->spaceOwner) {
            $totalSpaces = Space::where('user_id', $user->id)->count();
            $approvedSpaces = Space::where('user_id', $user->id)
                ->where('approval_status', 'approved')
                ->count();
            $totalEarnings = Booking::whereHas('space', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->whereIn('booking_status', ['confirmed', 'completed'])
                ->sum('owner_payout');

            $data['space_owner'] = [
                'tax_registration_number' => $user->spaceOwner->tax_registration_number,
                'total_spaces' => $totalSpaces,
                'approved_spaces' => $approvedSpaces,
                'total_earnings' => (float) $totalEarnings,
            ];
        }

        // Admin-specific info
        if ($user->role === 'admin' && $user->admin) {
            $data['admin'] = [
                'level_of_authority' => $user->admin->level_of_authority,
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function suspend($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = false;
        $user->save();
        $user->tokens()->delete();

        return response()->json([
            'data' => new AdminUserResource($user),
        ]);
    }

    public function activate($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = true;
        $user->save();

        return response()->json([
            'data' => new AdminUserResource($user),
        ]);
    }
}