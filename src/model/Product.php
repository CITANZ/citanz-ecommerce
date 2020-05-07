<?php

namespace Cita\eCommerce\Model;
use Page;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use Cita\eCommerce\Extension\ProductVariantCommonFields;
use Cita\eCommerce\Extension\ProductOrderItemCommonFields;
use Cita\eCommerce\Controller\ProductController;
use Cita\eCommerce\Traits\TopSellerGenerator;
use Leochenftw\Grid;
use Leochenftw\Util;
use SilverStripe\TagField\TagField;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Product extends Page
{
    use TopSellerGenerator;

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'LowestPirce' => 'Currency',
        'HighestPrice' => 'Currency'
    ];

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        ProductVariantCommonFields::class
    ];

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
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Brand' => Brand::class
    ];

    /**
     * Belongs_many_many relationship
     * @var array
     */
    private static $belongs_many_many = [
        'Categories' => Category::class,
        'RelatedWith' => Product::class
    ];

    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'Related' => Product::class
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Variants' => Variant::class
    ];

    private static $cascade_deletes = [
        'Variants'
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab(
            'Root.ProductDetails',
            [
                TagField::create(
                    'Categories',
                    'Categories',
                    Category::get(),
                    $this->Categories()
                )->setShouldLazyLoad(true)->setCanCreate(true),
            ]
        );

        $fields->addFieldsToTab(
            'Root.Variants',
            [
                Grid::make('Variants', 'Variants', $this->owner->Variants(), true, 'GridFieldConfig_RelationEditor'),
                LiteralField::create(
                    'PriceRange',
                    "<p><strong>Price range</strong>: $this->LowestPirce - $this->HighestPrice</p>"
                )
            ]
        );

        return $fields;
    }

    public function UpdatePrices($do_write = false)
    {
        $lowest = $this->Variants()->sort(['Price' => 'ASC'])->first();
        $highest = $this->Variants()->sort(['Price' => 'DESC'])->first();

        if ($lowest) {
            $this->LowestPirce = $lowest->Price;
        }

        if ($highest) {
            $this->HighestPrice = $highest->Price;
        }

        if ($do_write) {
            $this->write();
            if ($this->isPublished()) {
                $this->doPublish();
            }
        }
    }

    public function getPriceLabel()
    {
        if ($this->Variants()->exists()) {
            $numbers    =   [];
            $numbers[]  =   $this->Variants()->sort(['Price' => 'ASC'])->first()->Price;
            $numbers[]  =   $this->Variants()->sort(['Price' => 'DESC'])->first()->Price;
            $specials   =   [];
            foreach ($this->Variants() as $variant) {
                if ($special = $variant->get_special_price()) {
                    $numbers[]  =   $special;
                    if (!empty($special)) {
                        $specials[] = $special;
                    }
                }
            }

            if (count($specials) == $this->Variants()->count()) {
                $numbers = $specials;
            }

            if (!empty($numbers)) {
                sort($numbers);
                $lowest     =   $numbers[0];
                $highest    =   $numbers[count($numbers) - 1];

                return $lowest == $highest ? ('$' . number_format($lowest, 2)) : ('$' . number_format($lowest, 2) . ' - $' . number_format($highest, 2));
            }
        }

        return '$' . number_format(0, 2);
    }

    public function getBaseData()
    {
        return [
            'id'            =>  $this->ID,
            'price'         =>  $this->Price,
            'price_label'   =>  $this->PriceLabel,
            'special_price' =>  $this->SpecialPrice,
            'special_rate'  =>  $this->SpecialDiscountRate,
            'image'         =>  $this->Image()->exists() ?
                                $this->Image()->getAbsoluteURL() : null
        ];
    }

    public function getData()
    {
        return array_merge(
            parent::getData(),
            $this->getBaseData(),
            [
                'link' => $this->Link(),
                'title' => $this->Title,
                'content' => Util::preprocess_content($this->Content),
                'variants' => $this->Variants()->getData()
            ]
        );
    }

    public function getSpecialPrice()
    {
        if ($this->Variants()->count() == 1) {
            $variant = $this->Variants()->first();
            if (!empty($variant->SpecialPrice)) {
                if (empty($variant->SpecialToDate)) {
                    if (strtotime($variant->SpecialFromDate) <= time()) {
                        return $variant->SpecialPrice;
                    }
                } elseif (strtotime($variant->SpecialToDate) >= time()) {
                    return $variant->SpecialPrice;
                }
            }
        }

        return null;
    }

    public function getSpecialDiscountRate()
    {
        if ($this->Variants()->count() == 1) {
            $variant = $this->Variants()->first();
            $n = (float) $variant->SpecialPrice;
            $price = (float) $variant->Price;

            if (!empty($price)) {
                return ceil(($price - $n) / $price * -100);
            }
        }

        return 0;
    }
}
