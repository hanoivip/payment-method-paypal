<?php

namespace Hanoivip\PaymentMethodPaypal;

use Hanoivip\IapContract\Facades\IapFacade;
use Hanoivip\PaymentMethodContract\IPaymentMethod;
use Illuminate\Support\Facades\Session;
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
/**
 * Ref https://medium.com/in-laravel/how-to-integrate-paypal-into-laravel-977bf508c13
 * @author gameo
 *
 */
class PaypalMethod implements IPaymentMethod
{
    private $apiContext;
    
    public function endTrans($trans)
    {}

    public function cancel($trans)
    {}

    public function beginTrans($trans)
    {
        $order = $trans->order;
        $orderDetail = IapFacade::detail($order);
        $price = intval($orderDetail['item_price']);
        
        //Log::debug("PaypalMethod " . $price . "@" . print_r($orderDetail, true));
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $item_1 = new Item();
        $item_1->setName('Order number:' . $order)
        ->setCurrency('USD')
        ->setQuantity(1)
        ->setPrice($price);
        
        $item_list = new ItemList();
        $item_list->setItems(array(
            $item_1
        ));
        
        $amount = new Amount();
        $amount->setCurrency('USD')->setTotal($price);
        
        $transaction = new Transaction();
        $transaction->setAmount($amount)
        ->setItemList($item_list)
        ->setDescription('Pay for order ' . $order)
        ->setCustom($order);
        
        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl(URL::route('payment.paypal.callback'))->setCancelUrl(URL::route('payment.paypal.callback'));
        
        $payment = new Payment();
        $payment->setIntent('Sale')
        ->setPayer($payer)
        ->setRedirectUrls($redirect_urls)
        ->setTransactions(array($transaction));
        
        if (empty($this->apiContext))
        {
            //Session::put('paypal_error', __('hanoivip::payment.paypal.config-error'));
            throw new Exception(__('hanoivip::payment.paypal.config-error'));
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
            //Session::put('paypal_error', __('hanoivip::payment.paypal.payment-not-approved'));
            throw new Exception(__('hanoivip::payment.paypal.payment-not-approved'));
        }
        
        Session::put('paypal_payment_id', $payment->getId());
        //Session::put('paypal_api_context', $this->apiContext);
        $log = new PaypalTransaction();
        $log->trans = $trans->trans_id;
        $log->payment_id = $payment->getId();
        $log->save();
        return new PaypalSession($trans, $payment->getId(), $redirect_url);
    }

    public function request($trans, $params)
    {
        return $this->query($trans);
    }

    public function query($trans)
    {
        $log = PaypalTransaction::where('trans', $trans->trans_id);
        if (empty($log))
        {
            
        }
        return new PaypalResult($log);
    }

    public function config($cfg)
    {
        Session::put('paypal_config', $cfg);
        $this->apiContext = new ApiContext(new OAuthTokenCredential($cfg['client_id'], $cfg['secret']));
        $this->apiContext->setConfig($cfg['settings']);
    }

    
}