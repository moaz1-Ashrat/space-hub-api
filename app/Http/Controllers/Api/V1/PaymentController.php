<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentGatewayService $gateway)
    {
        $this->middleware('auth:sanctum')->except('webhook');
    }

    public function checkout(CheckoutRequest $request)
    {
        $data = $request->validated();
        $paymentId = (int) $data['payment_id'];
        $method = $data['payment_method'];

        if ($method === 'manual') {
            $payment = DB::transaction(function () use ($paymentId) {
                $payment = Payment::with('booking')->lockForUpdate()->findOrFail($paymentId);

                $booking = $payment->booking;
                if (! $booking) {
                    abort(404, 'Booking not found for this payment');
                }

                if ($booking->user_id !== auth()->id()) {
                    abort(403, 'Forbidden');
                }

                if ($payment->payment_status === 'paid') {
                    abort(400, 'Payment already completed');
                }

                if ($payment->payment_status === 'failed' || $payment->payment_status === 'pending') {
                    $payment->payment_status = 'paid';
                    $payment->payment_methode = 'manual';
                    $payment->payment_date_time = now();
                    $payment->save();

                    $booking->booking_status = 'confirmed';
                    $booking->save();
                }

                return $payment->load('booking');
            });

            return response()->json([
                'data' => new PaymentResource($payment),
            ], 200);
        }

        if ($method === 'card') {
            $payment = Payment::with('booking')->findOrFail($paymentId);

            if (! $payment->booking) {
                abort(404, 'Booking not found for this payment');
            }

            if ($payment->booking->user_id !== auth()->id()) {
                abort(403, 'Forbidden');
            }

            if ($payment->payment_status === 'paid') {
                abort(400, 'Payment already completed');
            }

            $result = $this->gateway->createSession($payment);

            return response()->json([
                'data' => [
                    'redirect_url' => $result['redirect_url'],
                    'session_id' => $result['session_id'],
                ],
            ], 200);
        }

        return response()->json([
            'message' => 'Payment method not supported yet',
        ], 422);
    }

    public function history(Request $request)
    {
        $payments = Payment::whereHas('booking', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
            ->with('booking')
            ->orderByDesc('created_at')
            ->paginate(15);

        return PaymentResource::collection($payments)->response();
    }

    public function webhook(Request $request)
    {
        $result = $this->gateway->handleWebhook($request);
        $code = $result['status'] === 'ok' ? 200 : 400;

        return response()->json($result, $code);
    }
}
