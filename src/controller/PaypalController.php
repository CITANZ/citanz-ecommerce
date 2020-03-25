<?php
namespace Cita\eCommerce\Controller;
use Cita\eCommerce\eCommerce;
use SilverStripe\Dev\Debug;
use Cita\eCommerce\API\Paypal;
use Cita\eCommerce\Model\Payment;
use Cita\eCommerce\Controller\eCommerceController;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPRequest;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Payment as PaypalPayment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Transaction;
use Leochenftw\Debugger;
use PayPal\Exception\PayPalConnectionException;

class PaypalController extends eCommerceController
{
    public function index(HTTPRequest $request)
    {
        if (!$request->isPost()) {

            $data   =   [
                'payment_id'    =>  $this->request->getVar('paymentId'),
                'payer_id'      =>  $this->request->getVar('PayerID')
            ];

            if (empty($data['payment_id']) || empty($data['payer_id'])) {
                Injector::inst()->get(LoggerInterface::class)->error('Missing payment or payer id');
                return $this->httpError(400, 'Missing payment or payer id');
            }

            Injector::inst()->get(LoggerInterface::class)->info('Paypal:: get back');
            $result =   $this->handle_postback($data);
            return $this->route($result);
        } else {
            Injector::inst()->get(LoggerInterface::class)->info('Paypal:: post back');
        }

        $data   =   json_decode($this->request->getBody());

        $this->handle_postback($data);
    }

    protected function handle_postback($data)
    {
        Injector::inst()->get(LoggerInterface::class)->info(json_encode($data));

        if (is_array($data)) {
            $payment_id =   $data['payment_id'];
            $payer_id   =   $data['payer_id'];
        } else {
            // $payment_id =   $data->
        }

        try {
            $data   =   PaypalPayment::get($payment_id, eCommerce::get_paypal_apicontext());
        } catch (PayPalConnectionException $ex) {
            return $this->httpError(400, $ex->getMessage());
        }

        // Debugger::inspect($data);

        $payer  =   $data->getPayer()->getPayerInfo();
        $ref    =   $data->getTransactions()[0]->invoice_number;

        if ($Order  =   Paypal::fetch($ref)) {
            $payment = $Order->Payments()->filter(['TransacID' => $data->id])->first();
            if (empty($payment)) {
                $payment                =   Payment::create();
                $payment->PaymentMethod =   'Paypal';
                $payment->CardHolder    =   trim($payer->getFirstName() . ' ' . $payer->getLastName());
                $payment->TransacID     =   $data->id;
                $payment->Amount        =   number_format($data->getTransactions()[0]->getAmount()->getTotal(), 2);
                $payment->OrderID       =   $Order->ID;
                $payment->Status        =   $this->translate_status($data->getPayer()->getStatus());
                $payment->IP            =   'Paypal does not provide an IP address';
                $payment->PaypalPayerID =   $payer->getPayerId();
                $payment->PaypalApprovalURL =   $data->getApprovalLink();

                $payment->write();

                $Order->onPaymentUpdate($payment->Status);
            }

            return $Order;
        }

        return $this->httpError(400, 'Order not found');
    }

    private function translate_status($status)
    {
        $status   =   strtolower($status);

        if ($status == 'verified') {
            return 'Success';
        } elseif ($status == 'unverified') {
            return ucwords($status);
        }

        return 'Pending';
    }
}
