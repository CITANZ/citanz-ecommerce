<?php

namespace Cita\eCommerce\Model;

use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataObject;
/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class BundleEntry extends DataObject
{
    private static $table_name = 'Cita_eCommerce_BundleEntry';

    private static $db = [
        'Title' => 'Varchar(128)',
        'Price' => 'Currency',
        'Quantity' => 'Int',
    ];

    private static $defaults = [
        'Quantity' => 1
    ];

    private static $has_one = [
        'Bundle' => Bundle::class,
        'Order' => Order::class
    ];

    private static $many_many = [
        'Variants' => Variant::class
    ];

    private static $many_many_extraFields = [
        'Variants' => [
            'Quantity' => 'Int'
        ]
    ];

    public function getData()
    {
        return [
            'is_bundle' => true,
            'title' => $this->Title,
            'price' => $this->Price,
            'pagelink' => $this->Bundle()->Link(),
            'price_label' => '$' . number_format($this->Price, 2),
            'image' => null,
            'quantity' => 1, // maybe should allow stack
            'variants' => array_map(function($v) {
                return array_merge(
                    $v->Data,
                    [
                        'count' => $v->Quantity
                    ]
                );
            }, $this->Variants()->toArray())
        ];
    }
}
