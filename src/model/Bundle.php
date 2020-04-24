<?php

namespace Cita\eCommerce\Model;
use Page;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Bundle extends Page
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Bundle';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'BundledPrice' => 'Currency',
        'BundleFreightCost' => 'Currency'
    ];

    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'Variants' => Variant::class
    ];

    public static function MatchBundle(&$order)
    {
        foreach ($order->Items() as $item) {

        }
    }
}
