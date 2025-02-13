<?php

namespace Hanoivip\PaymentMethodPaypal;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use PayPal\Api\Payment;
use Exception;
use Hanoivip\Events\Payment\TransactionUpdated;

class FakePaypalController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    public function callback(Request $request)
    {
        $order = $request->input('order');
        $status = $request->input('status');// success, failure, cancel
        
        $log = PaypalTransaction::where('payment_id', $order)->first();
        if (empty($log))
        {
            return 'err1';
        }
        
        try
        {
            $this->savePaymentResult($order, "fake", $status);
            // event here
            event(new TransactionUpdated($log->trans));
            if ($status == 'success') {
                return 'ok';
            }
            return 'ok1';
        }
        catch (Exception $ex)
        {
            Log::error('Paypal payment verifier error: ' . $ex->getMessage());
            return 'err2';
            
        }
    }
    
    public function cancel(Request $request)
    {
        return 'ok';
    }
	
    
    /**
     * 
     * @param string $paymentId
     * @param string $payerId
     * @param Payment $paymentResult
     */
    private function savePaymentResult($paymentId, $payerId, $paymentResult)
    {
        try 
        {
            $log = PaypalTransaction::where('payment_id', $paymentId)->first();
            $log->payer_id = $payerId;
            $log->state = $paymentResult->getState();
            $transactions = $paymentResult->getTransactions();
            if (empty($transactions))
            {
                $log->save();
                return false;
            }
            $total = 0;
            $currency = '';
            foreach ($transactions as $tran)
            {
                $total += $tran->getAmount()->getTotal();
                $currency = $tran->getAmount()->getCurrency();
                //$invoice = $tran->getCustom();
            }
            $log->amount = $total;
            $log->currency = $currency;
            $log->save();
            return true;
        } 
        catch (Exception $ex) 
        {
            Log::error("PaypalController " . $ex->getMessage());
        }
        return false;
    }
}