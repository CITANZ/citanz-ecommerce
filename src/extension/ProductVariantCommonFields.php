<?php

namespace Cita\eCommerce\Extension;

use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\HTMLEditor\HtmlEditorField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\DataExtension;
use Cita\eCommerce\Model\Variant;
use Cita\eCommerce\Model\Tag;
use SilverStripe\TagField\TagField;
use Cita\eCommerce\Model\Product;
use UncleCheese\DisplayLogic\Forms\Wrapper;
use Leochenftw\Grid;

class ProductVariantCommonFields extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'SKU'                   =>  'Varchar(64)',
        'OutOfStock'            =>  'Boolean',
        'Price'                 =>  'Currency',
        'Height'                =>  'Decimal',
        'Width'                 =>  'Decimal',
        'Depth'                 =>  'Decimal',
        'UnitWeight'            =>  'Decimal',
        'ShortDesc'             =>  'HTMLText',
        'StockCount'            =>  'Int',
        'StockLowWarningPoint'  =>  'Int',
        'SpecialPrice'          =>  'Currency',
        'SpecialFromDate'       =>  'Datetime',
        'SpecialToDate'         =>  'Datetime',
        'SortingPrice'          =>  'Currency'
    ];

    private static $indexes = [
        'SKU'   =>  [
            'type'      =>  'unique'
        ]
    ];

    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'Tags'  =>  Tag::class
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Image' =>  Image::class
    ];
/**
     * Relationship version ownership
     * @var array
     */
    private static $owns = [
        'Image'
    ];

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;

        if ($owner->isDigital) {
            $fields->removeByName([
                'Width',
                'Height',
                'Depth',
                'UnitWeight'
            ]);
        }

        $fields->addFieldsToTab(
            'Root.Main',
            [
                HtmlEditorField::create(
                    'ShortDesc',
                    'Short Description'
                ),
                HtmlEditorField::create('Content'),
            ],
            'Content'
        );

        $fields->addFieldToTab(
            'Root.Main',
            HtmlEditorField::create(
                'ShortDesc',
                'Short Description'
            ),
            'Content'
        );

        $fields->removeByName([
            'isExempt',
            'isDigital',
            'SortingPrice'
        ]);

        $fields->addFieldsToTab(
            'Root.ProductDetails',
            [
                TextField::create('SKU', 'SKU'),
                UploadField::create(
                    'Image',
                    'Product Image'
                )
            ]
        );

        $product_detail_fields = [
            TagField::create(
                'Tags',
                'Tags',
                Tag::get(),
                $this->owner->Tags()
            )->setShouldLazyLoad(true)->setCanCreate(true),
            CheckboxField::create(
                'isDigital',
                'is Digital Product'
            )->setDescription('means no freight required'),
            CheckboxField::create(
                'NoDiscount',
                'This product does not accept any discout'
            ),
            CheckboxField::create(
                'isExempt',
                'This product is not subject to GST'
            ),
            CurrencyField::create('Price'),
            TextField::create('Width'),
            TextField::create('Height'),
            TextField::create('Depth'),
            TextField::create('UnitWeight')->setDescription('in KG. If you are not charging the freight cost on weight, leave it 0.00'),
            CheckboxField::create('OutOfStock', 'Out of Stock')
        ];

        if ($this->owner->ClassName == Variant::class) {
            $fields->addFieldsToTab(
                'Root.ProductDetails',
                $product_detail_fields
            );
        } else {

            $fields->removeByName([
                'Variants'
            ]);

            $fields->addFieldsToTab(
                'Root.ProductDetails',
                [
                    CheckboxField::create(
                        'hasVariants',
                        'Product has variants'
                    )
                ]
            );

            if ($this->owner->exists()) {
                $fields->addFieldsToTab(
                    'Root.ProductDetails',
                    Wrapper::create(
                        Grid::make('Variants', 'Variants', $this->owner->Variants(), true, 'GridFieldConfig_RelationEditor')
                    )->displayIf('hasVariants')->isChecked()->end()
                );

                foreach ($product_detail_fields as $product_detail_field) {
                    $fields->addFieldsToTab(
                        'Root.ProductDetails',
                        $f = Wrapper::create($product_detail_field)->displayIf('hasVariants')->isNotChecked()->end()
                    );
                }
            }

        }


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
                )
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

    public function getBaseData()
    {
        return [
            'id'            =>  $this->owner->ID,
            'sku'           =>  $this->owner->SKU,
            'class'         =>  $this->owner->ClassName,
            'price'         =>  $this->owner->Price,
            'price_label'   =>  $this->owner->getPriceLabel(),
            'special_price' =>  $this->owner->get_special_price(),
            'special_rate'  =>  $this->owner->calc_special_price_discount_rate(),
            'image'         =>  $this->owner->Image()->exists() ?
                                $this->owner->Image()->getAbsoluteURL() : null
        ];
    }

    public function get_special_price()
    {
        if (!empty($this->owner->SpecialPrice)) {
            if (empty($this->owner->SpecialToDate)) {
                if (strtotime($this->owner->SpecialFromDate) <= time()) {
                    return $this->owner->SpecialPrice;
                }
            } elseif (strtotime($this->owner->SpecialToDate) >= time()) {
                return $this->owner->SpecialPrice;
            }
        }

        return null;
    }

    public function calc_special_price_discount_rate()
    {
        if ($this->owner->get_special_price()) {
            $n      =   (float) $this->owner->get_special_price();
            $price  =   (float) $this->owner->Price;
            return ceil(($price - $n) / $price * -100);
        }

        return null;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->owner->isDigital) {
            $this->owner->UnitWeight    =   0;
        }

        $this->owner->SortingPrice =    !empty($this->owner->get_special_price()) ? $this->owner->get_special_price() : $this->owner->Price;
    }

    public function getPriceLabel()
    {
        if (($this->owner instanceof Product) && $this->owner->hasVariants && $this->owner->Variants()->exists()) {
            $numbers    =   [];
            $numbers[]  =   $this->owner->Variants()->sort(['Price' => 'ASC'])->first()->Price;
            $numbers[]  =   $this->owner->Variants()->sort(['Price' => 'DESC'])->first()->Price;
            foreach ($this->owner->Variants() as $variant) {
                if ($special = $variant->get_special_price()) {
                    $numbers[]  =   $special;
                }
            }

            if (!empty($numbers)) {
                sort($numbers);
                $lowest     =   $numbers[0];
                $highest    =   $numbers[count($numbers) - 1];

                return $lowest == $highest ? ('$' . number_format($lowest, 2)) : ('$' . number_format($lowest, 2) . ' - $' . number_format($highest, 2));
            }
        }

        return '$' . number_format($this->owner->Price, 2);
    }
}
