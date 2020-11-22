<?php

namespace Cita\eCommerce\Model;

class SubscriptionOrder extends Order
{
    private static $table_name = 'Cita_eCommerce_SubscriptionOrder';

    private static $db = [
        'isRecursive' => 'Boolean',
        'NextPaymentDate' => 'Date',
    ];

    private static $many_many = [
        'Variants' => SubscriptionVariant::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'isRecursive',
            'NextPaymentDate',
        ]);
        return $fields;
    }

    public function AddToCart($vid, $qty = 1, $isupdate = false)
    {
        if ($variant = SubscriptionVariant::get()->byID($vid)) {
            $this->Variants()->removeAll();
            $this->Variants()->add($vid, [
                'Quantity' => 1,
                'StoredTitle' => $variant->Title,
                'StoredUnitWeight' => $variant->UnitWeight,
                'StoredUnitPrice' => $variant->Price,
                'StoredisDigital' => $variant->isDigital,
                'StoredisExempt' => $variant->isExempt,
                'StoredGSTIncluded' => $variant->GSTIncluded,
                'StoredNoDiscount' => $variant->NoDiscount
            ]);

            if ($this->Discount()->exists()) {
                $this->DiscountID = 0;
            }

            $this->UpdateAmountWeight();
        }

        return $this->Data;
    }
}
