<?php

namespace Cita\eCommerce\Model;
use Page;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use Cita\eCommerce\Extension\ProductVariantCommonFields;
use Cita\eCommerce\Extension\ProductOrderItemCommonFields;
use Cita\eCommerce\Controller\ProductController;
use Cita\eCommerce\Traits\TopSellerGenerator;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Product extends Page
{
    use TopSellerGenerator;

    public function getControllerName()
    {
        return ProductController::class;
    }
    /**
     * Defines whether a page can be in the root of the site tree
     * @var boolean
     */
    private static $can_be_root = false;
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Product';
    private static $description = 'You are encouraged to extend this class, rather than using it directly...';
    /**
     * Defines whether a page is displayed within the site tree
     * @var boolean
     */
    private static $show_in_sitetree = false;

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'hasVariants'   =>  'Boolean'
    ];

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        ProductOrderItemCommonFields::class,
        ProductVariantCommonFields::class
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Brand' =>  Brand::class
    ];

    /**
     * Belongs_many_many relationship
     * @var array
     */
    private static $belongs_many_many = [
        'Categories'    =>  Category::class,
        'RelatedWith'   =>  Product::class
    ];

    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'Related'   =>  Product::class
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'OrderItems'    =>  OrderItem::class,
        'Variants'      =>  Variant::class
    ];
}
