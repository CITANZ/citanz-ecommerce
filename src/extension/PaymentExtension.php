<?php

namespace Cita\eCommerce\Extension;

use SilverStripe\ORM\DataExtension;
use Cita\eCommerce\Model\Order;

class PaymentExtension extends DataExtension
{
    private static $has_one = array(
        'Order' => Order::class
    );

    public function onCaptured($response){
        $order = $this->owner->Order();
        $order->completePayment($this->owner->Status);
    }

    public function getData()
    {
        return [
            'transaction_id'    =>  $this->owner->TransactionReference,
            'created'           =>  $this->owner->Messages()->exists() ?
                                    $this->owner->Messages()->sort(['Created' => 'DESC'])->first()->Created : $this->owner->Created,
            'status'            =>  $this->owner->TranslatedStatus,
            'amount'            =>  $this->owner->Amount,
            'payment_method'    =>  $this->owner->GatewayTitle,
            'last_message'      =>  $this->owner->Messages()->sort('Created DESC')->first() ? $this->owner->Messages()->sort('Created DESC')->first()->Message : null,
            // 'account_no'        =>  $this->PayerAccountNumber,
            // 'bank_name'         =>  $this->PayerBankName,
            // 'sort_code'         =>  $this->PayerAccountSortCode,
            // 'card_type'         =>  $this->CardType,
            // 'card_number'       =>  $this->CardNumber,
            // 'card_holder'       =>  $this->CardHolder,
            // 'card_expiry'       =>  $this->Expiry,
            // 'approval_url'      =>  $this->Status == 'Unverified' && !empty($this->PaypalApprovalURL) ? $this->PaypalApprovalURL : null
        ];
    }

    public function getTranslatedStatus()
    {
        /*
        'Created'
        'PendingAuthorization'
        'Authorized'
        'PendingCreateCard'
        'CardCreated'
        'PendingPurchase'
        'PendingCapture'
        'Captured'
        'PendingRefund'
        'Refunded'
        'PendingVoid'
        'Void'
        */
        $status = $this->owner->PaymentStatus;
        if ($status == 'Captured') {
            return 'Successful';
        } elseif ($status == 'Void') {
            return 'Cancelled';
        } elseif ($status == 'Created') {
            return 'Failed';
        }

        return $status;
    }
}
