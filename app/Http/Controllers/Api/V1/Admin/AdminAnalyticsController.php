<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    /**
     * Summary — high-level KPIs from platform_metrics view.
     */
    public function summary()
    {
        $metrics = DB::table('platform_metrics')->first();

        return response()->json(['data' => $metrics ? (array) $metrics : null]);
    }

    /**
     * Revenue — last N months from monthly_revenue view.
     */
    public function revenue(Request $request)
    {
        $months = (int) $request->input('months', 6);

        $rows = DB::table('monthly_revenue')
            ->orderBy('month_key', 'desc')
            ->limit($months)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'data' => [
                'labels' => $rows->pluck('month_label'),
                'revenue' => $rows->pluck('total_revenue')->map(fn ($v) => (float) $v),
                'owner_payout' => $rows->pluck('owner_payout')->map(fn ($v) => (float) $v),
                'platform_commission' => $rows->pluck('platform_commission')->map(fn ($v) => (float) $v),
                'bookings_count' => $rows->pluck('bookings_count')->map(fn ($v) => (int) $v),
                'unique_customers' => $rows->pluck('unique_customers')->map(fn ($v) => (int) $v),
            ],
        ]);
    }

    /**
     * Growth — users and spaces over time (last 6 months).
     */
    public function growth()
    {
        $usersByMonth = DB::table('users')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') AS month_key, DATE_FORMAT(created_at, '%b %Y') AS month_label, COUNT(*) AS count")
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key', 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        $spacesByMonth = DB::table('spaces')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') AS month_key, DATE_FORMAT(created_at, '%b %Y') AS month_label, COUNT(*) AS count")
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key', 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'data' => [
                'users' => [
                    'labels' => $usersByMonth->pluck('month_label'),
                    'counts' => $usersByMonth->pluck('count')->map(fn ($v) => (int) $v),
                ],
                'spaces' => [
                    'labels' => $spacesByMonth->pluck('month_label'),
                    'counts' => $spacesByMonth->pluck('count')->map(fn ($v) => (int) $v),
                ],
            ],
        ]);
    }

    /**
     * Distribution — booking statuses + space types.
     */
    public function distribution()
    {
        $bookings = DB::table('booking_distribution')->get();

        $spaceTypes = DB::table('spaces')
            ->selectRaw('space_type, COUNT(*) AS count')
            ->groupBy('space_type')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'data' => [
                'bookings' => [
                    'labels' => $bookings->pluck('booking_status'),
                    'counts' => $bookings->pluck('count')->map(fn ($v) => (int) $v),
                ],
                'space_types' => [
                    'labels' => $spaceTypes->pluck('space_type'),
                    'counts' => $spaceTypes->pluck('count')->map(fn ($v) => (int) $v),
                ],
            ],
        ]);
    }

    /**
     * Top Spaces — from space_performance view.
     */
    public function topSpaces(Request $request)
    {
        $limit = (int) $request->input('limit', 10);

        $spaces = DB::table('space_performance')
            ->where('approval_status', 'approved')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $spaces]);
    }

    /**
     * Top Owners — from owner_revenue_summary view.
     */
    public function topOwners(Request $request)
    {
        $limit = (int) $request->input('limit', 10);

        $owners = DB::table('owner_revenue_summary')
            ->orderByDesc('total_earnings')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $owners]);
    }

    /**
     * Top Customers — from top_customers view.
     */
    public function topCustomers(Request $request)
    {
        $limit = (int) $request->input('limit', 10);

        $customers = DB::table('top_customers')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $customers]);
    }
}