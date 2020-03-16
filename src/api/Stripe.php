<?php
namespace Cita\eCommerce\API;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use Cita\eCommerce\eCommerce;
use SilverStripe\Core\Environment;
use Stripe\PaymentIntent;
use Cita\eCommerce\Model\Order;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

class Stripe
{
    public static function initiate($amount, $ref, &$cart)
    {
        $settings   =   eCommerce::get_stripe_settings();

        \Stripe\Stripe::setApiKey(Director::isDev() ? $settings->secret_dev : $settings->secret);
        $intent     =   PaymentIntent::create([
                            'amount'                =>  round($amount * 100),
                            'currency'              =>  strtolower(Config::inst()->get(eCommerce::class, 'DefaultCurrency')),
                            'payment_method_types'  =>  ['card'],
                            'metadata'              =>  [
                                'order_id'      =>  $cart->ID,
                                'merchant_ref'  =>  $ref
                            ],
                        ]);

        return ['client_secret' => $intent->client_secret];
    }

    public static function process($amount, $ref, &$cart)
    {
        $response   =   static::initiate($amount, $ref, $cart);
        return $response;
    }

    public static function fetch($ref)
    {
        if ($order = Order::get()->filter(['MerchantReference' => $ref])->first()) {
            return $order;
        }

        return null;
    }
}
