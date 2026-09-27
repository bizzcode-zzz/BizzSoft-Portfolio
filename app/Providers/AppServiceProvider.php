<?php

namespace App\Providers;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Providers\Paddle\PaddleGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PaymentGateway::class,
            PaddleGateway::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
