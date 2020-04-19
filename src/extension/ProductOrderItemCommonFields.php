<?php

namespace Cita\eCommerce\Extension;

use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class ProductOrderItemCommonFields extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'isDigital'     =>  'Boolean',
        'isExempt'      =>  'Boolean',
        'GSTIncluded'   =>  'Boolean',
        'NoDiscount'    =>  'Boolean'
    ];
}
