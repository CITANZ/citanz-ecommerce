<?php

namespace Cita\eCommerce\API;
use Cita\eCommerce\Model\Payment;

class Invoice
{
    public static function process($amount, $ref, &$order)
    {
        $payment                =   Payment::create();
        $payment->PaymentMethod =   'Invoice';
        $payment->Amount        =   $order->PayableTotal;
        $payment->OrderID       =   $order->ID;
        $payment->Status        =   'Invoice Pending';
        $payment->IP            =   $_SERVER['REMOTE_ADDR'];

        $payment->write();

        $order->onPaymentUpdate($payment->Status);

        return ['URI' => '/cart/complete/invoice-pending'];
    }
}
