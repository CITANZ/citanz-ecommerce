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

    private static $summary_fields = [
        'ID' => 'ID',
        'Message' => 'Message',
        'AdminUse' => 'AdminUse'
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
        return substr($this->Message, 0, 50);
    }

    public function getData()
    {
        return [
            'id' => $this->ID,
            'content' => $this->Message
        ];
    }
}
