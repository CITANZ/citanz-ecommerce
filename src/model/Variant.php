<?php

namespace Cita\eCommerce\Model;

use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use Leochenftw\Util;
use Leochenftw\Extension\SortOrderExtension;
use Cita\eCommerce\Extension\ProductVariantCommonFields;
use Cita\eCommerce\Extension\ProductOrderItemCommonFields;
use SilverStripe\TagField\TagField;
use SilverStripe\Forms\CheckboxField;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Variant extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Variant';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(128)',
        'Content' => 'HTMLText',
        'SKU' => 'Varchar(64)',
        'InfiniteStock' => 'Boolean',
        'OutOfStock' => 'Boolean',
        'Price' => 'Currency',
        'Height' => 'Decimal',
        'Width' => 'Decimal',
        'Depth' => 'Decimal',
        'UnitWeight' => 'Decimal',
        'StockCount' => 'Int',
        'StockLowWarningPoint' => 'Int',
        'SpecialPrice' => 'Currency',
        'SpecialFromDate' => 'Datetime',
        'SpecialToDate' => 'Datetime'
    ];

    private static $indexes = [
        'SKU'   =>  [
            'type'      =>  'unique'
        ]
    ];

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        ProductOrderItemCommonFields::class,
        ProductVariantCommonFields::class,
        SortOrderExtension::class
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Product.Title' =>  'Product',
        'Title'         =>  'Title',
        'Price'         =>  'Price'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Product' =>  Product::class
    ];

    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'Tags'  =>  Tag::class
    ];

    /**
     * Belongs_many_many relationship
     * @var array
     */
    private static $belongs_many_many = [
        'Bundles' => Bundle::class
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'ProductID',
            'Tags',
            'UnitWeight'
        ]);

        $fields->addFieldToTab(
            'Root.Main',
            TagField::create(
                'Tags',
                'Tags',
                Tag::get(),
                $this->Tags()
            )->setShouldLazyLoad(true)->setCanCreate(true),
            'ShortDesc'
        );

        if ($this->isDigital) {
            $fields->removeByName([
                'Width',
                'Height',
                'Depth'
            ]);
        }

        if ($this->InfiniteStock) {
            $fields->removeByName([
                'OutOfStock'
            ]);
        }

        $fields->removeByName([
            'isExempt',
            'isDigital',
            'GSTIncluded'
        ]);

        $fields->addFieldsToTab(
            'Root.ProductDetails',
            [
                TextField::create('SKU', 'SKU')
            ]
        );

        $product_detail_fields = [
            CurrencyField::create('Price'),
            TextField::create('Width'),
            TextField::create('Height'),
            TextField::create('Depth'),
            CheckboxField::create(
                'isDigital',
                'is Digital Product'
            )->setDescription('means no freight required'),
            Wrapper::create(
                TextField::create('UnitWeight')->setDescription('in KG. If you are not charging the freight cost on weight, leave it 0.00')
            )->displayIf('isDigital')->isNotChecked()->end(),
            CheckboxField::create(
                'NoDiscount',
                'This product does not accept any discout'
            ),
            CheckboxField::create(
                'GSTIncluded',
                'The price has already included GST'
            ),
            CheckboxField::create(
                'isExempt',
                'This product is not subject to GST'
            )
        ];

        $fields->addFieldsToTab(
            'Root.ProductDetails',
            $product_detail_fields
        );

        $fields->addFieldsToTab(
            'Root.Inventory',
            [
                TextField::create(
                    'StockCount',
                    'Stock Count'
                ),
                TextField::create(
                    'StockLowWarningPoint',
                    'StockLow Warning Point'
                ),
                CheckboxField::create('InfiniteStock', 'Infinite Stock'),
                CheckboxField::create('OutOfStock', 'Out of Stock')
            ]
        );

        $fields->addFieldsToTab(
            'Root.Promotion',
            [
                CurrencyField::create(
                    'SpecialPrice',
                    'Special Price'
                ),
                DatetimeField::create(
                    'SpecialFromDate',
                    'From'
                ),
                DatetimeField::create(
                    'SpecialToDate',
                    'To'
                )
            ]
        );

        return $fields;
    }

    public function getData()
    {
        return array_merge($this->getBaseData(), [
            'link'          =>  $this->Product()->exists() ? $this->Product()->Link() : null,
            'title'         =>  $this->getProductTitle(),
            'variant_title' =>  $this->Title,
            'content'       =>  Util::preprocess_content(empty($this->Content) ? $this->Product()->Content : $this->Content)
        ]);
    }

    public function getProductTitle()
    {
        if ($this->Product()->exists()) {
            return trim($this->Product()->Title);
        }

        $result = $this->extend('getProductTitle');
        if (!empty($result)) {
            return $result[0];
        }

        return null;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->isDigital) {
            $this->UnitWeight = 0;
        }

        if ($this->InfiniteStock) {
            $this->OutOfStock = false;
        }

        $this->SortingPrice = !empty($this->get_special_price()) ? $this->get_special_price() : $this->Price;
    }

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if ($this->Product()->exists()) {
            $this->Product()->SortingPrice = $this->SortingPrice;
            $this->Product()->UpdatePrices();
            $this->Product()->write();
            if ($this->Product()->isPublished()) {
                $this->Product()->doPublish();
            }
        }
    }

    public function getBaseData()
    {
        $special_price = $this->get_special_price();
        return [
            'id'            =>  $this->ID,
            'sku'           =>  $this->SKU,
            'price'         =>  $this->Price,
            'price_label'   =>  $special_price ?
                                $special_price :
                                ('$' . number_format($this->Price, 2)),
            'special_price' =>  $this->get_special_price(),
            'special_rate'  =>  $this->calc_special_price_discount_rate(),
            'image'         =>  $this->Image()->exists() ?
                                $this->Image()->getAbsoluteURL() : null
        ];
    }

    public function get_special_price()
    {
        if (!empty($this->SpecialPrice)) {
            if (empty($this->SpecialToDate)) {
                if (strtotime($this->SpecialFromDate) <= time()) {
                    return $this->SpecialPrice;
                }
            } elseif (strtotime($this->SpecialToDate) >= time()) {
                return $this->SpecialPrice;
            }
        }

        return 0;
    }

    public function calc_special_price_discount_rate()
    {
        if ($this->get_special_price()) {
            $n      =   (float) $this->get_special_price();
            $price  =   (float) $this->Price;
            return ceil(($price - $n) / $price * -100);
        }

        return 0;
    }
}
