<?php

namespace Cita\eCommerce\API;
use Cita\eCommerce\Model\Payment;

class DirectDebit
{
    public static function process($amount, $ref, &$order)
    {
        $payment                =   Payment::create();
        $payment->PaymentMethod =   'Direct Debit';
        $payment->Amount        =   $order->PayableTotal;
        $payment->OrderID       =   $order->ID;
        $payment->Status        =   'Debit Pending';
        $payment->IP            =   $_SERVER['REMOTE_ADDR'];

        $payment->write();

        $order->onPaymentUpdate($payment->Status);

        return ['URI' => '/cart/complete/debit-pending'];
    }
}
