<?php

namespace Hanoivip\PaymentMethodPaypal;

use Illuminate\Support\ServiceProvider;

class LibServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../lang' => resource_path('lang/vendor/hanoivip'),
            __DIR__.'/../config' => config_path(),
        ]);
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadTranslationsFrom( __DIR__.'/../lang', 'hanoivip.paypal');
        $this->mergeConfigFrom( __DIR__.'/../config/paypal.php', 'paypal');
        $this->loadViewsFrom(__DIR__ . '/../views', 'hanoivip.paypal');
    }
    
    public function register()
    {
        $fake = config('paypal.is_fake', false);
        $this->commands([
        ]);
        if ($fake) {
            $this->app->bind("PaypalPaymentMethod", FakePaypalMethod::class);
        }
        else {
            $this->app->bind("PaypalPaymentMethod", PaypalMethod::class);
        }
        
    }
}
