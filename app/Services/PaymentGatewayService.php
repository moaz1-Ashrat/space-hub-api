<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGatewayService
{
    public function createSession(Payment $payment): array;

    public function handleWebhook(Request $request): array;
}
