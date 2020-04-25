<?php

namespace Cita\eCommerce\Model;
use SilverStripe\ORM\DataObject;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class OrderMessage extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_OrderMessage';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Message' => 'HTMLText',
        'AdminUse' => 'Boolean',
        'Displayed' => 'Boolean'
    ];

    /**
     * Default sort ordering
     * @var array
     */
    private static $default_sort = ['Created' => 'DESC'];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Order' => Order::class
    ];

    public function getTitle()
    {
        return subtr($this->Message, 0, 50);
    }
}
