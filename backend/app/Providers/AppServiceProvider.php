<?php

namespace App\Providers;

use App\Contracts\OtpSenderInterface;
use App\Services\Otp\drivers\GhasedakDriver;
use App\Services\Otp\drivers\MelipayamkDriver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->bind(OtpSenderInterface::class, function () {

            return match (config('sms.default')) {
                'melipayamak' => new MelipayamkDriver(),
                default => new GhasedakDriver(),
            };

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
