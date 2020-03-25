<?php
namespace Cita\eCommerce\API;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use Cita\eCommerce\eCommerce;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;
use Cita\eCommerce\Model\Order;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\PayerInfo;
use PayPal\Exception\PayPalConnectionException;


class Paypal
{
    public static function initiate($amount, $ref, &$cart)
    {
        $payer      =   new Payer();
        $payer_info =   new PayerInfo();

        $payer_info->setEmail($cart->Email);
        $payer_info->setFirstName($cart->BillingFirstname);
        $payer_info->setLastName($cart->ShippingSurname);

        $payer->setPaymentMethod("paypal");
        $payer->setPayerInfo($payer_info);

        $items      =   [];

        foreach ($cart->Items() as $item) {
            if ($item->isRefunded) {
                continue;
            }

            $payment_item   =   new Item();
            $payment_item->setName($item->ShowTitle())
                ->setCurrency(Config::inst()->get(eCommerce::class, 'DefaultCurrency'))
                ->setQuantity($item->Quantity)
                ->setSku($item->getSKU())
                ->setPrice($item->Subtotal);

            $items[]        =   $payment_item;
        }

        // set discount
        // $discount   =   new Item();
        // $discount->setName('Some sort of discount')
        //     ->setCurrency(Config::inst()->get(eCommerce::class, 'DefaultCurrency'))
        //     ->setQuantity(1)
        //     ->setPrice('-2.00');
        // $items[]    =   $discount;

        $itemList   =   new ItemList();
        $itemList->setItems($items);

        $details    =   new Details();
        $details->setShipping($cart->ShippingCost)
            ->setTax($cart->getGST())
            ->setSubtotal($cart->TotalAmount);

        $pay_amount =   new Amount();
        $pay_amount->setCurrency(Config::inst()->get(eCommerce::class, 'DefaultCurrency'))
            ->setTotal($amount)
            ->setDetails($details);

        $trans      =   new Transaction();
        $trans->setAmount($pay_amount)
            ->setItemList($itemList)
            ->setDescription($cart->Comment)
            ->setInvoiceNumber($ref);

        $returnurl  =   new RedirectUrls();
        $returnurl->setReturnUrl(Director::absoluteBaseURL() . 'cita-ecommerce/paypal-complete')
            ->setCancelUrl(Director::absoluteBaseURL() . 'cita-ecommerce/paypal-complete');
         // return $response;
        $payment    =   new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($returnurl)
            ->setTransactions([$trans]);

        $apiContext =   eCommerce::get_paypal_apicontext();
        $request    =   clone $payment;
        try {
            $payment->create($apiContext);
        } catch (PayPalConnectionException $ex) {
            Injector::inst()->get(LoggerInterface::class)->info("Paypal is glitched!");
            Injector::inst()->get(LoggerInterface::class)->info($ex->getMessage());
            return [
                'error' =>  $ex->getMessage()
            ];
        }

        $approvalUrl = $payment->getApprovalLink();

        return [
            'URL'   =>  $approvalUrl
        ];
    }

    public static function process($amount, $ref, $cart)
    {
        return static::initiate($amount, $ref, $cart);
    }

    public static function fetch($ref)
    {
        if ($order = Order::get()->filter(['MerchantReference' => $ref])->first()) {
            return $order;
        }

        return null;
    }
}
