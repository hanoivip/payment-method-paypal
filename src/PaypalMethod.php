<?php

namespace Hanoivip\PaymentMethodPaypal;

use Carbon\Carbon;
use Hanoivip\PaymentMethodContract\IPaymentMethod;
use Hanoivip\Shop\Facades\OrderFacade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use PayPal\Api\Amount;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Exception;
use Hanoivip\Payment\Facades\BalanceFacade;
/**
 * Ref https://medium.com/in-laravel/how-to-integrate-paypal-into-laravel-977bf508c13
 * @author gameo
 *
 * Session not work in WebView react native
 */
class PaypalMethod implements IPaymentMethod
{   
    private $apiContext;
	
	private $cfg;
    
    public function endTrans($trans)
    {}

    public function cancel($trans)
    {}

    /**
     * TODO: make abstract class
     * + Support transaction creation
     * + Model da hinh
     * + Check timeout
     * 
     * {@inheritDoc}
     * @see \Hanoivip\PaymentMethodContract\IPaymentMethod::beginTrans()
     */
    public function beginTrans($trans)
    {
        $exists = PaypalTransaction::where('trans', $trans->trans_id)->get();
        if ($exists->isNotEmpty())
            throw new Exception('Paypal transaction already exists');
        $log = new PaypalTransaction();
        $log->trans = $trans->trans_id;
        $log->state = 'created';
        $log->save();
        $session = new PaypalSession($trans);
        return $session;
    }
    
    public function request($trans, $params)
    {
        // check trans exists
        $record = PaypalTransaction::where('trans', $trans->trans_id)->first();
        if (empty($record))
        {
            return new PaypalFailure($trans, __('hanoivip.paypal::payment.trans-not-exists'));
        }
        // check trans timeout
        //??
        
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
        
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $item_1 = new Item();
        $item_1->setName('Order number:' . $order)
        ->setCurrency($currency)
        ->setQuantity(1)
        ->setPrice($price);
        
        $item_list = new ItemList();
        $item_list->setItems(array(
            $item_1
        ));
        
        $amount = new Amount();
        $amount->setCurrency($currency)->setTotal($price);
        
        $transaction = new Transaction();
        $transaction->setAmount($amount)
        ->setItemList($item_list)
        ->setDescription('Pay for order ' . $order)
        ->setCustom($order);
        
        $redirect_urls = new RedirectUrls();
        $redirect_urls
        ->setReturnUrl(URL::route('payment.paypal.callback', ['id' => $this->cfg['id']]))
        ->setCancelUrl(URL::route('payment.paypal.cancel'));
        
        $payment = new Payment();
        $payment->setIntent('Sale')
        ->setPayer($payer)
        ->setRedirectUrls($redirect_urls)
        ->setTransactions(array($transaction));
        
        if (empty($this->apiContext))
        {
            throw new Exception(__('hanoivip.paypal::payment.config-error'));
        }
        $payment->create($this->apiContext);
        
        $redirect_url = null;
        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }
        if (empty($redirect_url))
        {
            throw new Exception(__('hanoivip.paypal::payment.payment-not-approved'));
        }
        
        // save log
        $record->payment_id = $payment->getId();
        $record->payment_url = $redirect_url;
        $record->save();
		// api context can not be serialize to cache
		//Cache::put('payment_paypal_config_' . $payment->getId(), $this->cfg, Carbon::now()->addMinutes(10));
        return new PaypalPending($trans, $payment->getId(), $redirect_url);
    }


    public function query($trans, $force = false)
    {
        $log = PaypalTransaction::where('trans', $trans->trans_id)->first();
        if (empty($log))
        {
            
        }
        return new PaypalResult($log);
    }

    public function config($cfg)
    {
		$this->cfg = $cfg;
        $this->apiContext = new ApiContext(new OAuthTokenCredential($cfg['client_id'], $cfg['client_secret']));
        $this->apiContext->setConfig($cfg['other_settings']);
    }
    
    public function openPaymentPage($transId, $guide, $session)
    {
        // no need, just start payemnt
        return response()->redirectToRoute('newtopup.do', ['trans' => $transId]);
    }

    public function openPendingPage($trans)
    {
        $record = PaypalTransaction::where('trans', $trans->trans_id)->first();
        return response()->redirectTo($record->payment_url);
    }

    public function validate($params)
    {
        return [];
    }


    
}