<?php

namespace Hanoivip\PaymentMethodPaypal;

use Hanoivip\PaymentMethodContract\IPaymentResult;

class PaypalPending implements IPaymentResult
{
    /**
     *
     * @var string transaction id
     */
    private $trans;
	
	private $paymentId;
    
    private $url;
    
    public function __construct($trans, $paymentId, $url)
    {
        $this->trans = $trans;
		$this->paymentId = $paymentId;
        $this->url = $url;
    }
    
    public function getDetail()
    {
        return $this->url;
    }
    
    public function isPending()
    {
        return true;
    }
    
    public function isFailure()
    {
        return false;
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