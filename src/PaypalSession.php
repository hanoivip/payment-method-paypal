<?php

namespace Hanoivip\PaymentMethodPaypal;

use Hanoivip\PaymentMethodContract\IPaymentSession;

class PaypalSession implements IPaymentSession
{
    private $trans;
    
    private $paymentId;
    
    private $checkoutUrl;
    
    public function __construct($trans)
    {
        $this->trans = $trans;
    }
    
    public function getSecureData()
    {
        return ['paymentId' => $this->paymentId];
    }

    public function getGuide()
    {
        return __('hanoivip.paypal::ui.guide');
    }

    public function getTransId()
    {
        return $this->trans->trans_id;
    }

    public function getData()
    {
        return ['checkoutUrl' => $this->checkoutUrl];
    }

    
}