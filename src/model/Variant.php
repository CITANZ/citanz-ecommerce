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
use SilverStripe\ORM\ArrayList;
use Cita\eCommerce\Model\Order;

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
        'VariantTitle' => 'Varchar(128)',
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
        'Title' => 'Title',
        'Digital' => 'Digital',
        'Price' => 'Price',
        'Stock' => 'Stock'
    ];

    public function getDigital()
    {
        return $this->isDigital ? '✔️' : '❌';
    }

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'Product.Title',
        'VariantTitle'
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
        'Orders' => Order::class,
        'Bundles' => Bundle::class
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        if ($variant_title = $fields->fieldByName('Root.Main.VariantTitle')) {
            $variant_title->setDescription('Leave empty if you wish to use the same name as the product title');
        }

        $fields->removeByName([
            'Title',
            'ProductID',
            'Tags',
            'UnitWeight',
            'Width',
            'Height',
            'Depth',
            'isExempt',
            'isDigital',
            'GSTIncluded'
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

        if ($this->InfiniteStock) {
            $fields->removeByName([
                'OutOfStock'
            ]);
        }

        $fields->addFieldsToTab(
            'Root.ProductDetails',
            [
                TextField::create('SKU', 'SKU')
            ]
        );

        $product_detail_fields = [
            CurrencyField::create('Price'),
            CheckboxField::create(
                'isDigital',
                'is Digital Product'
            )->setDescription('means no freight required'),
            Wrapper::create(
                TextField::create('Width'),
                TextField::create('Height'),
                TextField::create('Depth')
            )->displayIf('isDigital')->isNotChecked()->end(),
            Wrapper::create(
                TextField::create('UnitWeight')->setDescription('in KG. If you are not charging the freight cost on weight, leave it 0.00')
            )->displayIf('isDigital')->isNotChecked()->end(),
            CheckboxField::create(
                'NoDiscount',
                'This product does not accept any discount'
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

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public function getData()
    {
        return array_merge($this->getBaseData(), [
            'link'          =>  $this->Product()->exists() ? $this->Product()->Link() : null,
            'title'         =>  $this->Title,
            'variant_title' =>  $this->VariantTitle,
            'content'       =>  Util::preprocess_content(empty($this->Content) ? $this->Product()->Content : $this->Content)
        ]);
    }

    public function getTitle()
    {
        if ($this->Product()->exists()) {
            return trim($this->Product()->Title . ($this->VariantTitle ? " - $this->VariantTitle" : ""));
        }

        $result = $this->extend('getProductTitle');
        if (!empty($result)) {
            return $result[0];
        }

        return null;
    }

    public function getStock()
    {
        if ($this->InfiniteStock) {
            return 'Infinite';
        }

        return $this->StockCount;
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

    public function getMiniData()
    {
        return [
            'id' => $this->ID,
            'title' => $this->Title,
            'price' => $this->Price
        ];
    }

    public function getBaseData()
    {
        $special_price = $this->get_special_price();
        $data = [
            'id'            =>  $this->ID,
            'sku'           =>  $this->SKU,
            'price'         =>  $this->Price,
            'stock'         =>  $this->Stock,
            'price_label'   =>  '$' . number_format($special_price ? $special_price : $this->Price, 2),
            'special_price' =>  $this->get_special_price(),
            'special_rate'  =>  $this->calc_special_price_discount_rate(),
            'image'         =>  $this->Image()->exists() ?
                                $this->Image()->getAbsoluteURL() : null
        ];

        $this->extend('getBaseData', $data);

        return $data;
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

    public function getisSoldout()
    {
        if (!$this->InfiniteStock && ($this->OutOfStock || $this->StockCount <= 0)) {
            return true;
        }

        return false;
    }
}
