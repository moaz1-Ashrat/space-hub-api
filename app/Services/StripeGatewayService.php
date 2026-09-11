<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeGatewayService implements PaymentGatewayService
{
    public function createSession(Payment $payment): array
    {
        $booking = $payment->booking;

        if (! $booking) {
            abort(404, 'Booking not found for this payment');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => (int) round(((float) $payment->amount) * 100),
                    'product_data' => [
                        'name' => "Booking #{$booking->id}",
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => config('services.stripe.success_url'),
            'cancel_url' => config('services.stripe.cancel_url'),
            'metadata' => [
                'payment_id' => $payment->id,
            ],
        ], [
            'idempotency_key' => 'payment_' . $payment->id,
        ]);

        return [
            'redirect_url' => $session->url,
            'session_id' => $session->id,
        ];
    }

    public function handleWebhook(Request $request): array
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException) {
            return [
                'status' => 'error',
                'message' => 'Invalid signature',
            ];
        }

        $type = $event->type ?? null;
        $paymentId = data_get($event, 'data.object.metadata.payment_id');

        if (! in_array($type, [
            'checkout.session.completed',
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'checkout.session.expired',
        ], true)) {
            return [
                'status' => 'ok',
                'message' => 'Processed',
            ];
        }

        $payment = Payment::with('booking')->find($paymentId);
        if (! $payment || ! $payment->booking) {
            return [
                'status' => 'error',
                'message' => 'Payment or booking not found',
            ];
        }

        if (in_array($type, ['checkout.session.completed', 'payment_intent.succeeded'], true)) {
            $payment->payment_status = 'paid';
            $payment->payment_date_time = now();
            $payment->save();

            $payment->booking->booking_status = 'confirmed';
            $payment->booking->save();
        }

        if (in_array($type, ['payment_intent.payment_failed', 'checkout.session.expired'], true)) {
            $payment->payment_status = 'failed';
            $payment->payment_date_time = now();
            $payment->save();
        }

        return [
            'status' => 'ok',
            'message' => 'Processed',
        ];
    }
}
