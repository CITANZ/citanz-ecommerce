<?php
namespace Cita\eCommerce\Controller;
use Cita\eCommerce\API\POLi;
use Cita\eCommerce\Model\Payment;
use Cita\eCommerce\Controller\eCommerceController;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPRequest;
use Cita\eCommerce\API\Stripe;

class StripeController extends eCommerceController
{
    public function index(HTTPRequest $request)
    {
        $data   =   json_decode($this->request->getBody());
        // Injector::inst()->get(LoggerInterface::class)->info('Stripe:: data back');
        // Injector::inst()->get(LoggerInterface::class)->info(json_encode($data));

        if (empty($data)) {
            return $this->httpError(400, 'No data');
        }

        $this->handle_postback($data);
    }

    protected function handle_postback($stripe_data)
    {
        Injector::inst()->get(LoggerInterface::class)->info('Stripe:: data back');
        Injector::inst()->get(LoggerInterface::class)->info(json_encode($stripe_data));

        $data       =   $stripe_data->data->object;
        $ref        =   $data->metadata->merchant_ref;
        $charges    =   $data->charges->data[0];
        $card       =   $charges->payment_method_details->card;

        if ($Order  =   Stripe::fetch($ref)) {
            $payment = $Order->Payments()->filter(['TransacID' => $data->id])->first();

            if (empty($payment)) {
                $payment                =   Payment::create();
                $payment->PaymentMethod =   'Stripe';
                $payment->CardHolder    =   $charges->billing_details->name;
                $payment->CardNumber    =   str_pad($card->last4, 16, "*", STR_PAD_LEFT);
                $payment->CardType      =   $card->network;
                $payment->TransacID     =   $data->id;
                $payment->Amount        =   number_format($data->amount_received * 0.01, 2);
                $payment->OrderID       =   $Order->ID;
                $payment->Status        =   $this->translate_status($stripe_data->type);
                $payment->IP            =   'Stripe does not provide an IP address';
                $payment->Expiry        =   str_pad($card->exp_month, 2, "0", STR_PAD_LEFT) . '/' . $card->exp_year;

                $payment->write();

                $Order->onPaymentUpdate($payment->Status);
            }

            return $Order;
        }

        return $this->httpError(400, 'Order not found');
    }

    private function translate_status($type)
    {
        $type   =   str_replace('payment_intent.', '', $type);

        if ($type == 'succeeded') {
            return 'Success';
        } elseif ($type == 'payment_failed') {
            return 'Failed';
        } elseif ($type == 'canceled') {
            return 'Cancelled';
        }

        return 'Pending';
    }
}
