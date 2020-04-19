<?php

namespace Cita\eCommerce\Extension;
use SilverStripe\ORM\DataExtension;
use Cita\eCommerce\Model\Order;
use SilverStripe\Core\Environment;
use SilverStripe\Omnipay\Model\Payment;

class PurchaseServiceExtension extends DataExtension
{
    public function onBeforeCompletePurchase(&$gatewayData)
    {
        if ($payment = Payment::get()->filter(['Identifier' => $gatewayData['transactionId']])->first()) {

            $gateway = $payment->Gateway;

            if ($config = Environment::getEnv($gateway)) {
                $config = json_decode($config);
                $gatewayData = array_merge($gatewayData, (array) $config);
            }
        }
    }
}
