<?php

namespace Hanoivip\PaymentMethodPaypal;

use Hanoivip\PaymentMethodContract\IPaymentResult;

class PaypalFailure implements IPaymentResult
{
    private $error;
    /**
     *
     * @var string transaction id
     */
    private $trans;
    
    public function __construct($trans, $error)
    {
        $this->trans = $trans;
        $this->error = $error;
    }
    
    public function getDetail()
    {
        return $this->error;
    }
    
    public function isPending()
    {
        return false;
    }
    
    public function isFailure()
    {
        return true;
    }
    
    public function isSuccess()
    {
        return false;
    }
    
    public function getAmount()
    {
        return 0;
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
    
    public function getTransId()
    {
        return $this->trans->trans_id;
    }
    
    public function getCurrency()
    {
        return 'USD';
    }
    
}