<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class AdminTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::query()->with(['booking.user']);

        if ($request->filled('from')) {
            $query->where('payment_date_time', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->where('payment_date_time', '<=', $request->date('to')->endOfDay());
        }

        if ($request->filled('status')) {
            $query->where('payment_status', $request->string('status')->toString());
        }

        if ($request->filled('user_id')) {
            $query->whereHas('booking', function ($q) use ($request) {
                $q->where('user_id', (int) $request->input('user_id'));
            });
        }

        $payments = $query->orderByDesc('created_at')->paginate(15);

        $items = collect($payments->items())->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'payment_status' => $payment->payment_status,
                'payment_methode' => $payment->payment_methode,
                'payment_date_time' => $payment->payment_date_time,
                'booking_id' => $payment->booking->id ?? null,
                'customer_name' => $payment->booking && $payment->booking->user
                    ? trim($payment->booking->user->first_name . ' ' . $payment->booking->user->last_name)
                    : null,
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
        ], 200);
    }
}
