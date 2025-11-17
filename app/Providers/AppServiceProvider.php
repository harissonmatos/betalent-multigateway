<?php

namespace App\Providers;

use App\Services\Payment\Gateway1Client;
use App\Services\Payment\Gateway2Client;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            return new PaymentGatewayManager(
                $app->make(Gateway1Client::class),
                $app->make(Gateway2Client::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
