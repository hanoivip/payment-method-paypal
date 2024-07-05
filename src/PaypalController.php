<?php

namespace Hanoivip\PaymentMethodPaypal;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Exception;
use Hanoivip\Events\Payment\TransactionUpdated;

class PaypalController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    public function callback(Request $request)
    {
        Log::debug("Paypal callback dump:" . print_r($request->all(), true));
        if (!$request->has('PayerID') && 
            !$request->has('token') && 
            !$request->has('paymentId')) {
            return view('hanoivip.paypal::payment-paypal-failure', ['error' => __('hanoivip.paypal::callback.invalid-callback')]);
        }
        $paymentId = $request->input('paymentId');
        Log::debug('Paypal payment id' . $paymentId);
        $payerId = $request->input('PayerID');
        $token = $request->input('token');
        
        $log = PaypalTransaction::where('payment_id', $paymentId)->first();
        if (empty($log))
        {
            return view('hanoivip.paypal::payment-paypal-failure', ['error' => __('hanoivip.paypal::callback.payment-id-invalid')]);
        }
        if (!Cache::has('payment_paypal_' . $paymentId))
        {
            return view('hanoivip.paypal::payment-paypal-failure', ['error' => __('hanoivip.paypal::callback.timeout')]);
        }
        //TODO: another way is passing payment method ID to callback url
		$cfg = Cache::get('payment_paypal_config_' . $paymentId);
		$apiContext = $this->config($cfg);
        try
        {
            $payment = Payment::get($paymentId, $apiContext);
            $execution = new PaymentExecution();
            $execution->setPayerId($payerId);
            $paymentResult = $payment->execute($execution, $apiContext);
            $this->savePaymentResult($paymentId, $payerId, $paymentResult);
            // event here
            event(new TransactionUpdated($log->trans));
            if ($paymentResult->getState() == 'approved') {
                return view('hanoivip.paypal::payment-paypal-success');
            }
            return view('hanoivip.paypal::payment-paypal-failure', ['error' => __('hanoivip.paypal::callback.failure')]);
        }
        catch (Exception $ex)
        {
            Log::error('Paypal payment verifier error: ' . $ex->getMessage());
            return view('hanoivip.paypal::payment-paypal-failure', ['error' => __('hanoivip.paypal::callback.exception')]);
            
        }
    }
	
	private function config($cfg)
    {
        $apiContext = new ApiContext(new OAuthTokenCredential($cfg['client_id'], $cfg['client_secret']));
        $apiContext->setConfig($cfg['other_settings']);
		return $apiContext;
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