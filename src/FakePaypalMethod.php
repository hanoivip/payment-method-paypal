<?php
namespace Hanoivip\PaymentMethodPaypal;

use Hanoivip\Payment\Facades\BalanceFacade;
use Hanoivip\Shop\Facades\OrderFacade;

class FakePaypalMethod extends PaypalMethod
{   
    public function request($trans, $params)
    {
        // check trans exists
        $record = PaypalTransaction::where('trans', $trans->trans_id)->first();
        if (empty($record))
        {
            return new PaypalFailure($trans, __('hanoivip.paypal::payment.trans-not-exists'));
        }
        
        $order = $trans->order;
        $orderDetail = OrderFacade::detail($order);
        $price = $orderDetail->price;
        $currency = strtoupper($orderDetail->currency);
        // convert to USD
        if ($currency != 'USD')
        {
            $price = BalanceFacade::convert($price, $currency, 'USD');
            $currency = 'USD';
        }
        
        // save log
        $redirect_url = config('paypal.fake_redirect_url') . "?order=$order";
        $record->payment_id = $order;
        $record->payment_url = $redirect_url;
        $record->save();
        return new PaypalPending($trans, $order, $redirect_url);
    }

    public function config($cfg)
    {
		$this->cfg = $cfg;
    }
    
}