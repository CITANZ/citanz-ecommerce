<?php

namespace Cita\eCommerce\Controller;
use Cita\eCommerce\API\DPS;
use Cita\eCommerce\Model\Payment;
use Cita\eCommerce\Controller\eCommerceController;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPRequest;

class DPSController extends eCommerceController
{
    public function index(HTTPRequest $request)
    {
        if (!$request->isPost()) {
            Injector::inst()->get(LoggerInterface::class)->info('DPS:: get back');
            if ($token = $request->getVar('result')) {
                $result = $this->handle_postback($token);
                return $this->route($result);
            }
        }

        Injector::inst()->get(LoggerInterface::class)->info('DPS:: post back');

        $token = $request->postVar('result');

        if (empty($token)) {
            $token = $request->getVar('result');
        }

        if (empty($token)) {
            return $this->httpError(400, 'Token is missing');
        }

        $this->handle_postback($token);
    }

    protected function handle_postback($data)
    {
        $result =   DPS::fetch($data);

        Injector::inst()->get(LoggerInterface::class)->info('DPS:: result');
        Injector::inst()->get(LoggerInterface::class)->info(json_encode($result));

        if ($Order = $this->getOrder($result['MerchantReference'])) {
            $payment = $Order->Payments()->filter(['TransacID' => $result['TxnId']])->first();

            if (empty($payment)) {
                $payment                =   new Payment();
                $payment->PaymentMethod =   'DPS';
                $payment->CardType      =   $result['CardHolderName'] == 'User Cancelled' ? 'N/A' : $result['CardName'];
                $payment->CardNumber    =   $result['CardHolderName'] == 'User Cancelled' ? 'N/A' : $result['CardNumber'];
                $payment->CardHolder    =   $result['CardHolderName'] == 'User Cancelled' ? 'N/A' : $result['CardHolderName'];
                $payment->Expiry        =   $result['CardHolderName'] == 'User Cancelled' ? 'N/A' : $result['DateExpiry'];
                $payment->TransacID     =   $result['TxnId'];
                $payment->Amount        =   $result['Success'] == '1' ? $result['AmountSettlement'] : 0;
                $payment->OrderID       =   $Order->ID;
                $payment->Status        =   $this->translate_status($result);
                $payment->Message       =   $result['ResponseText'];
                $payment->IP            =   $result['ClientInfo'];

                $payment->write();

                $Order->onPaymentUpdate($payment->Status);
            }

            return $this->route_data($payment->Status, $Order->ID);
        }

        return $this->httpError(400, 'Order not found');
    }

    private function translate_status(&$result)
    {
        if ($result['Success'] == '1') {
            return 'Success';
        } else if ($result['CardHolderName'] == 'User Cancelled') {
            return 'Cancelled';
        }

        return 'Failed';
    }
}
