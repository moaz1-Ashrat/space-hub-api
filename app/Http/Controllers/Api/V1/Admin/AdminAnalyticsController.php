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

    /**
     * Demographics — avg age, breakdown by role, gender, and age buckets.
     */
    public function demographics()
    {
        $base = DB::table('users')->whereNotNull('date_of_birth');

        // المتوسط العام
        $avgAge = round(
            (float) ((clone $base)
                ->selectRaw('AVG(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())) as a')
                ->value('a') ?? 0),
            1
        );

        // المتوسط لكل دور
        $byRole = (clone $base)
            ->selectRaw('role, ROUND(AVG(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())), 1) as avg_age, COUNT(*) as count')
            ->groupBy('role')
            ->get()
            ->map(fn ($r) => [
                'role' => $r->role,
                'avg_age' => (float) $r->avg_age,
                'count' => (int) $r->count,
            ])
            ->values();

        // التوزيع حسب النوع
        $byGender = (clone $base)
            ->selectRaw('COALESCE(gender, "unknown") as gender, COUNT(*) as count')
            ->groupBy('gender')
            ->get();

        // شرائح العمر
        $ageBuckets = (clone $base)
            ->selectRaw("
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 25 THEN '18-24'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
                    ELSE '55+'
                END as bucket,
                COUNT(*) as count
            ")
            ->groupBy('bucket')
            ->get();

        $order = ['18-24', '25-34', '35-44', '45-54', '55+'];
        $buckets = collect($order)->map(function ($b) use ($ageBuckets) {
            $found = $ageBuckets->firstWhere('bucket', $b);
            return ['label' => $b, 'count' => $found ? (int) $found->count : 0];
        });

        return response()->json([
            'data' => [
                'avg_age' => $avgAge,
                'by_role' => $byRole,
                'by_gender' => [
                    'labels' => $byGender->pluck('gender'),
                    'counts' => $byGender->pluck('count')->map(fn ($c) => (int) $c),
                ],
                'age_distribution' => [
                    'labels' => $buckets->pluck('label'),
                    'counts' => $buckets->pluck('count'),
                ],
            ],
        ]);
    }

    /**
     * Coupons Analytics — totals, usage rate, discounts, monthly usage, top coupons.
     */
    public function coupons()
    {
        $total = DB::table('coupons')->count();
        $used = DB::table('coupons')->where('is_used', true)->count();
        $available = DB::table('coupons')
            ->where('is_used', false)
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now());
            })
            ->count();
        $expired = DB::table('coupons')
            ->where('is_used', false)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now())
            ->count();

        $usageRate = $total > 0 ? round(($used / $total) * 100, 1) : 0.0;
        $totalDiscount = (float) DB::table('coupons')->where('is_used', true)->sum('discount_value');

        // الإيرادات من الحجوزات اللي استخدمت كوبون
        $revenueWithCoupons = (float) DB::table('bookings')
            ->join('coupons', 'coupons.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.booking_status', ['confirmed', 'completed'])
            ->sum('bookings.customer_paid');

        // الاستخدام الشهري (آخر 6 أشهر)
        $monthly = DB::table('coupons')
            ->whereNotNull('used_at')
            ->selectRaw("DATE_FORMAT(used_at, '%Y-%m') as month_key, DATE_FORMAT(used_at, '%b %Y') as month_label, COUNT(*) as count, SUM(discount_value) as discount_total")
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key', 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        // Top 5 الأكثر استخدامًا
        $topCoupons = DB::table('coupons')
            ->where('is_used', true)
            ->orderByDesc('used_at')
            ->limit(5)
            ->get(['code', 'discount_value', 'used_at'])
            ->map(fn ($c) => [
                'code' => $c->code,
                'discount_value' => (float) $c->discount_value,
                'used_at' => $c->used_at,
            ]);

        return response()->json([
            'data' => [
                'totals' => [
                    'total' => $total,
                    'used' => $used,
                    'available' => $available,
                    'expired' => $expired,
                    'usage_rate' => $usageRate,
                    'total_discount_given' => $totalDiscount,
                    'revenue_with_coupons' => $revenueWithCoupons,
                ],
                'monthly_usage' => [
                    'labels' => $monthly->pluck('month_label'),
                    'counts' => $monthly->pluck('count')->map(fn ($c) => (int) $c),
                    'discounts' => $monthly->pluck('discount_total')->map(fn ($d) => (float) $d),
                ],
                'top_coupons' => $topCoupons,
            ],
        ]);
    }
}
