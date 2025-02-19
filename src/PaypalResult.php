<?php

namespace Hanoivip\PaymentMethodPaypal;

use Hanoivip\PaymentMethodContract\IPaymentResult;

class PaypalResult implements IPaymentResult
{
    /**
     * 
     * @var PaypalTransaction
     */
    private $log;
    
    public function __construct($log)
    {
        $this->log = $log;    
    }
    
    public function getDetail()
    {
        $state = $this->log->state;
        if (empty($state)) $state = 'pending';
        return __('hanoivip::payment.paypal.' . $this->log->state);
    }

    public function toArray()
    {
        $arr = [];
        $arr['detail'] = $this->getDetail();
        $arr['amount'] = $this->getAmount();
        $arr['isPending'] = $this->isPending();
        $arr['isFailure'] = $this->isFailure();
        $arr['isSuccess'] = $this->isSuccess();
        $arr['trans'] = $this->getTransId();
        $arr['currency'] = $this->getCurrency();
        return $arr;
    }

    public function isPending()
    {
        return !$this->isSuccess() && !$this->isFailure();
    }

    public function isFailure()
    {
        $state = $this->log->state;
        return $state == 'failed';
    }

    public function getTransId()
    {
        return $this->log->trans;
    }

    public function isSuccess()
    {
        $state = $this->log->state;
        return $state == 'approved';
    }

    public function getAmount()
    {
        if ($this->isSuccess())
            return $this->log->amount;
        return 0;
    }
    
    public function getCurrency()
    {
        return !empty($this->log->currency) ? $this->log->currency : 'USD';
    }


    
}