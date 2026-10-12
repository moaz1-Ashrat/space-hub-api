<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerAnalyticsController extends Controller
{
    /**
     * Summary — owner's KPIs from view.
     */
    public function summary(Request $request)
    {
        $ownerId = $request->user()->id;

        $row = DB::table('owner_revenue_summary')
            ->where('owner_id', $ownerId)
            ->first();

        return response()->json(['data' => $row ? (array) $row : null]);
    }

    /**
     * Revenue — owner's monthly revenue (last 6 months).
     */
    public function revenue(Request $request)
    {
        $ownerId = $request->user()->id;
        $months = (int) $request->input('months', 6);

        $rows = DB::table('bookings')
            ->join('spaces', 'spaces.id', '=', 'bookings.space_id')
            ->where('spaces.user_id', $ownerId)
            ->whereIn('bookings.booking_status', ['confirmed', 'completed'])
            ->selectRaw("
                DATE_FORMAT(bookings.created_at, '%Y-%m') AS month_key,
                DATE_FORMAT(bookings.created_at, '%b %Y') AS month_label,
                COUNT(*) AS bookings_count,
                COALESCE(SUM(bookings.owner_payout), 0) AS owner_payout,
                COALESCE(SUM(bookings.total_amount), 0) AS total_revenue
            ")
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key', 'desc')
            ->limit($months)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'data' => [
                'labels' => $rows->pluck('month_label'),
                'owner_payout' => $rows->pluck('owner_payout')->map(fn ($v) => (float) $v),
                'total_revenue' => $rows->pluck('total_revenue')->map(fn ($v) => (float) $v),
                'bookings_count' => $rows->pluck('bookings_count')->map(fn ($v) => (int) $v),
            ],
        ]);
    }

    /**
     * Top Spaces — owner's top-performing spaces from view.
     */
    public function topSpaces(Request $request)
    {
        $ownerId = $request->user()->id;
        $limit = (int) $request->input('limit', 5);

        $spaces = DB::table('space_performance')
            ->where('owner_id', $ownerId)
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $spaces]);
    }

    /**
     * Popular Days.
     */
    public function popularDays(Request $request)
    {
        $ownerId = $request->user()->id;

        $days = DB::table('bookings')
            ->join('spaces', 'spaces.id', '=', 'bookings.space_id')
            ->where('spaces.user_id', $ownerId)
            ->whereIn('bookings.booking_status', ['confirmed', 'completed'])
            ->selectRaw("
                DAYOFWEEK(bookings.booking_date) AS day_num,
                DAYNAME(bookings.booking_date) AS day_name,
                COUNT(*) AS count
            ")
            ->groupBy('day_num', 'day_name')
            ->orderBy('day_num')
            ->get();

        return response()->json([
            'data' => [
                'labels' => $days->pluck('day_name'),
                'counts' => $days->pluck('count')->map(fn ($v) => (int) $v),
            ],
        ]);
    }

    /**
     * Booking Status Distribution.
     */
    public function distribution(Request $request)
    {
        $ownerId = $request->user()->id;

        $statuses = DB::table('bookings')
            ->join('spaces', 'spaces.id', '=', 'bookings.space_id')
            ->where('spaces.user_id', $ownerId)
            ->selectRaw('bookings.booking_status, COUNT(*) AS count')
            ->groupBy('bookings.booking_status')
            ->get();

        return response()->json([
            'data' => [
                'labels' => $statuses->pluck('booking_status'),
                'counts' => $statuses->pluck('count')->map(fn ($v) => (int) $v),
            ],
        ]);
    }
}