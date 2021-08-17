<?php

namespace Cita\eCommerce\Model;

use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use Cita\eCommerce\Model\Order;
use Cita\eCommerce\Model\Address;
use SilverStripe\Security\Group;

use Ramsey\Uuid\Uuid;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;

class CustomerGroup extends DataObject
{
    private static $table_name = 'Cita_eCommerce_CustomerGroup';

    private static $db = [
        'Title' => 'Varchar(128)',
        'Description' => 'Text',
        'Weight' => 'Int',
    ];

    private static $has_one = [
        'Discount'  =>  Discount::class,
    ];

    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'Customers' => Customer::class,
    ];

    /**
     * Default sort ordering
     * @var array
     */
    private static $default_sort = ['Weight' => 'DESC'];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject.
     *
     * @var array
     */
    private static $summary_fields = [
        'Title' => 'Title',
        'Description' => 'Description',
    ];
}
