<?php

namespace App\Providers;

use App\Services\PaymentGatewayService;
use App\Services\StripeGatewayService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $driver = config('services.payment.driver', 'manual');

        if ($driver === 'stripe') {
            $this->app->bind(PaymentGatewayService::class, StripeGatewayService::class);
        }
    }

    public function boot(): void
    {
        //
    }
}
