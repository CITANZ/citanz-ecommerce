<?php

namespace Cita\eCommerce\Model;
use Leochenftw\Extension\SingletonExtension;
use SilverStripe\Dev\Debug;
use Leochenftw\Extension\LumberjackExtension;
use SilverStripe\Lumberjack\Model\Lumberjack;
use Cita\eCommerce\Traits\ProductListGenerator;
use Page;
use Cita\eCommerce\Model\Product;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Catalog extends Page
{
    use ProductListGenerator;
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

    public function getPriceRanges()
    {
        $range = array_map('round', array_unique(Product::get()->column('SortingPrice')));
        $range = array_unique($range);

        asort($range);

        $divider = count($range) / 10;
        $divider = $divider > 10 ? 10 : $divider;
        $ranges = array_chunk($range, ceil(count($range) / $divider));

        $refined = [];

        foreach ($ranges as $chunk) {
            $refined[] = [
                'from' => floor(min($chunk)),
                'to' => ceil(max($chunk))
            ];
        }

        return $refined;
    }
}
