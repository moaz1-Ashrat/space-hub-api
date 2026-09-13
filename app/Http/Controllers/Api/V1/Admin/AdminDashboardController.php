<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Space;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => [
                'total_users' => User::count(),
                'total_customers' => User::where('role', 'customer')->count(),
                'total_owners' => User::where('role', 'space_owner')->count(),
                'total_spaces' => Space::count(),
                'pending_spaces' => Space::where('approval_status', 'pending')->count(),
                'total_bookings' => Booking::count(),
                'total_revenue' => (float) Payment::where('payment_status', 'paid')->sum('amount'),
            ],
        ], 200);
    }
}
