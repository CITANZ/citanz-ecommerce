<?php
namespace Cita\eCommerce\Service;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use Cita\eCommerce\eCommerce;
use SilverStripe\Core\Environment;
use SilverStripe\Omnipay\Model\Payment;
use SilverStripe\Omnipay\Service\ServiceFactory;

class PaymentService
{
    public static function initiate($gateway, &$order, $stripe_token = null)
    {
        $amount = number_format($order->PayableTotal, 2, '.', '');

        if (Director::isDev() && $gateway == 'PaymentExpress_PxPay') {
            $amount =   round($amount);
        }

        $payment = Payment::create()->init($gateway, $amount, 'NZD');
        $payment->OrderID = $order->ID;
        $payment->setSuccessUrl(Director::absoluteBaseURL() . 'cita-ecommerce/complete/' . $order->ID);
        $payment->setFailureUrl(Director::absoluteBaseURL() . 'cita-ecommerce/complete/' . $order->ID);
        $payment->write();

        if ($config = Environment::getEnv($gateway)) {
            $config = (array) json_decode($config);

            if ($gateway == 'Paystation_Hosted') {
                $config = array_merge($config, [
                    'merchantSession' =>  (microtime(true) * 1000) . '-' . $order->MerchantReference
                ]);
            } elseif ($gateway == 'Stripe') {
                $config = array_merge($config, [
                    'token' => $stripe_token,
                    'receipt_email' => $order->Email,
                    'shipping' => [
                        'address' => [
                            'line1' => $order->ShippingAddress,
                            'line2' => $order->ShippingSuburb,
                            'city' => $order->ShippingTown,
                            'state' => $order->ShippingRegion,
                            'country' => $order->ShippingCountry,
                            'postal_code' => $order->ShippingPostcode,
                        ],
                        'name' => trim($order->ShippingFirstname . ' ' . $order->ShippingSurname)
                    ],
                    'description' => $order->DirectCartItemList,
                    'metadata' => [
                        'Order ID' => $order->ID,
                        'Reference' => $order->CustomerReference,
                        'Customer' => trim($order->BillingFirstname . ' ' . $order->BillingSurname)
                    ],
                ]);
            }

            try {
                $response = ServiceFactory::create()
                   ->getService($payment, ServiceFactory::INTENT_PURCHASE)
                   ->initiate($config);

                return $response->getTargetUrl();
            } catch (Exception $e) {
                error_log($e->getMessage());
                return $e->getMessage;
            }
        }

        throw new \Exception("Please define '$gateway' in your .env file");
    }
}
