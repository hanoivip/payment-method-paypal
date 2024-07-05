<?php

use Illuminate\Support\Facades\Route;

Route::middleware([
    'web'
])->namespace('Hanoivip\PaymentMethodPaypal')->group(function () {
    Route::any('/paypal/{id}/callback', 'PaypalController@callback')->name('payment.paypal.callback');
    Route::any('/paypal/cancel', 'PaypalController@cancel')->name('payment.paypal.cancel');
});