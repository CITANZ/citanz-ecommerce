<?php

namespace Cita\eCommerce\Model;
use Leochenftw\Extension\SingletonExtension;
use Leochenftw\Extension\LumberjackExtension;
use SilverStripe\Lumberjack\Model\Lumberjack;
use Cita\eCommerce\Traits\ProductPriceRangeGenerator;
use Cita\eCommerce\Traits\ProductListGenerator;
use Page;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Catalog extends Page
{
    use ProductPriceRangeGenerator, ProductListGenerator;
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Catalog';
    private static $description = 'This is the Catalog page. You can only have one Catalog page at any one time';

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        SingletonExtension::class,
        Lumberjack::class,
        LumberjackExtension::class
    ];

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'PageSize'  =>  'Int'
    ];

    /**
     * Add default values to database
     * @var array
     */
    private static $defaults = [
        'PageSize'  =>  12
    ];

    /**
     * Defines the allowed child page types
     * @var array
     */
    private static $allowed_children = [
        Product::class
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $this->add_pagesize_field($fields);
        return $fields;
    }
}
