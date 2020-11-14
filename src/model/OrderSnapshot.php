<?php

namespace Cita\eCommerce\Model;

use SilverStripe\ORM\DataObject;

class OrderSnapshot extends DataObject
{
    private static $table_name = 'OrderSnapshot';

    private static $db = [
        'StoredDetails' => 'Text',
    ];

    private static $belongs_to = [
        'Order' => Order::class,
    ];

    public function StoreJSON($order = null)
    {
        $order = $order ? $order : $this->Order();

        if (empty($order)) {
            return false;
        }

        $this->StoredDetails = json_encode($order);
        $this->write();

        return $this->ID;
    }
}
